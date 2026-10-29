<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->q);
        $sales = Sale::with('cashier')->withCount('items')
            ->when($q, fn ($x) => $x->whereLike('receipt_no', "%$q%")
                ->orWhereHas('cashier', fn ($c) => $c->whereLike('name', "%$q%")))
            ->latest('sale_date')->limit(200)->get();

        return view('sales.index', compact('sales', 'q'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        try {
            $sale = DB::transaction(function () use ($data) {
                $sale = Sale::create([
                    'cashier_id' => auth()->id(), 'sale_date' => now(),
                    'total_amount' => 0, 'amount_paid' => $data['amount_paid'], 'change_amount' => 0, 'status' => 'completed',
                ]);
                $total = 0;
                foreach ($data['items'] as $row) {
                    $p = Product::lockForUpdate()->findOrFail($row['product_id']);
                    if (! $p->is_active) {
                        throw new \RuntimeException("{$p->name} is archived.");
                    }
                    $q = round((float) $row['quantity'], 3);
                    $line = round($q * $p->selling_price, 2);
                    $total += $line;
                    $item = $sale->items()->create(['product_id' => $p->id, 'quantity' => $q, 'unit_price' => $p->selling_price, 'line_total' => $line]);
                    // sale item + stock movement are saved together; a shortage rolls everything back
                    StockService::post($p, -$q, 'sale', ['sale_item_id' => $item->id]);
                }
                if ($data['amount_paid'] < $total) {
                    throw new \RuntimeException('Amount received is less than the total.');
                }

                $sale->update([
                    'receipt_no' => 'RCPT-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                    'total_amount' => $total,
                    'change_amount' => round($data['amount_paid'] - $total, 2),
                ]);
                StockMovement::whereIn('sale_item_id', $sale->items()->pluck('id'))->update(['reference' => $sale->receipt_no]);
                ActivityLog::record('Sale completed', $sale->receipt_no.' · '.peso($total));

                return $sale;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.index')->with('success', 'Saved successfully')->with('receipt', route('sales.receipt', $sale));
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'cashier']);

        return view('sales.receipt', compact('sale'));
    }

    public function void(Request $r, Sale $sale)
    {
        $r->validate(['void_reason' => 'required|string|max:255']);

        DB::transaction(function () use ($r, $sale) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['void_reason' => 'This sale is already voided.']);
            }
            foreach ($sale->items()->with('product')->get() as $item) {
                $orig = StockMovement::where('sale_item_id', $item->id)->where('movement_type', 'sale')->first();
                StockService::post($item->product, $item->quantity, 'reversal', [
                    'sale_item_id' => $item->id, 'reverses_movement_id' => $orig?->id,
                    'reference' => $sale->receipt_no, 'reason' => $r->void_reason,
                ]);
            }
            $sale->update(['status' => 'voided', 'voided_by' => auth()->id(), 'voided_at' => now(), 'void_reason' => $r->void_reason]);
            ActivityLog::record('Sale voided', $sale->receipt_no.' · '.$r->void_reason);
        });

        return back()->with('success', 'Sale voided and stock restored.');
    }
}
