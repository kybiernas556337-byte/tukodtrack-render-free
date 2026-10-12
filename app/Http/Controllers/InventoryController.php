<?php
namespace App\Http\Controllers;

use App\Models\{ActivityLog, Product, StockAdjustment, StockMovement};
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $r)
    {
        $tab = in_array($r->tab, ['counts', 'movements']) ? $r->tab : 'levels';
        $q = trim((string) $r->q);
        $data = ['tab' => $tab, 'q' => $q, 'filter' => $r->filter, 'products' => Product::active()->orderBy('name')->get()];

        if ($tab === 'levels') {
            $data['rows'] = $this->levels($r);
        } elseif ($tab === 'counts') {
            $data['counts'] = StockAdjustment::with(['product', 'requester', 'approver'])->latest('requested_at')->limit(200)->get();
        } else {
            $data['moves'] = StockMovement::with(['product', 'recorder'])
                ->when($q, fn ($x) => $x->where(fn ($w) => $w->whereLike('reference', "%$q%")->orWhereLike('movement_type', "%$q%")
                    ->orWhereHas('product', fn ($p) => $p->whereLike('name', "%$q%")->orWhereLike('code', "%$q%"))))
                ->latest()->latest('id')->limit(300)->get();
        }
        return view('inventory.index', $data);
    }

    private function levels(Request $r)
    {
        $q = trim((string) $r->q);
        return Product::with('category')->active()
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->whereLike('name', "%$q%")->orWhereLike('code', "%$q%")))
            ->when($r->filter === 'low', fn ($x) => $x->low())
            ->orderBy('name')->get();
    }

    public function export(Request $r)
    {
        $rows = $this->levels($r)->map(fn ($p) => [$p->code, $p->name, $p->base_unit, $p->selling_price, $p->quantity_on_hand, $p->reorder_level, $p->status()]);
        return csv_download('stock-levels-' . date('Ymd') . '.csv', ['Code', 'Product', 'Unit', 'Price', 'Available', 'Reorder at', 'Status'], $rows);
    }

    /** Stock personnel submit a count; it does not change stock until the owner approves. */
    public function count(Request $r)
    {
        $d = $r->validate(['product_id' => 'required|exists:products,id', 'counted_qty' => 'required|numeric|min:0', 'reason' => 'nullable|string|max:255']);
        $p = Product::findOrFail($d['product_id']);
        $delta = round($d['counted_qty'] - $p->quantity_on_hand, 3);
        if ($delta != 0 && blank($d['reason'] ?? null)) {
            return back()->with('error', 'Please give a reason for the difference.');
        }
        StockAdjustment::create([
            'product_id' => $p->id, 'requested_by' => auth()->id(), 'expected_qty' => $p->quantity_on_hand,
            'counted_qty' => $d['counted_qty'], 'quantity_delta' => $delta, 'reason' => $d['reason'] ?? null,
            'status' => 'pending', 'requested_at' => now(),
        ]);
        ActivityLog::record('Physical count submitted', $p->code . ' · counted ' . qty($d['counted_qty']));
        return redirect()->route('inventory.index', ['tab' => 'counts'])->with('success', 'Saved successfully');
    }

    public function approve(StockAdjustment $adjustment)
    {
        if ($adjustment->status !== 'pending') return back()->with('error', 'This count was already reviewed.');
        DB::transaction(function () use ($adjustment) {
            $p = Product::lockForUpdate()->findOrFail($adjustment->product_id);
            $delta = round($adjustment->counted_qty - $p->quantity_on_hand, 3);   // difference against the balance today
            $adjustment->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now(), 'quantity_delta' => $delta]);
            if ($delta != 0) {
                StockService::post($p, $delta, 'adjustment', [
                    'adjustment_id' => $adjustment->id, 'reference' => $p->code,
                    'reason' => $adjustment->reason ?: 'Approved physical count',
                ]);
            }
            ActivityLog::record('Count approved', $p->code . ' · ' . ($delta >= 0 ? '+' : '') . qty($delta));
        });
        return back()->with('success', 'Count approved and stock updated.');
    }

    public function reject(StockAdjustment $adjustment)
    {
        if ($adjustment->status !== 'pending') return back()->with('error', 'This count was already reviewed.');
        $adjustment->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        ActivityLog::record('Count rejected', $adjustment->product->code);
        return back()->with('success', 'Count rejected. Stock was not changed.');
    }
}
