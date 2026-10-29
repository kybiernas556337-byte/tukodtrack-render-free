<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Delivery;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->q);
        $purchases = Purchase::with(['supplier', 'items.product', 'deliveries' => fn ($q) => $q->select('id', 'purchase_id', 'received_at', 'proof_name')])->withCount('items')
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->whereLike('reference', "%$q%")
                ->orWhereHas('supplier', fn ($s) => $s->whereLike('name', "%$q%"))))
            ->latest()->limit(200)->get();

        return view('purchases.index', [
            'purchases' => $purchases, 'q' => $q,
            'awaiting' => Purchase::whereIn('status', ['pending', 'partial'])->count(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** Saving a purchase order never changes stock. Stock only increases in receive(). */
    public function store(Request $r)
    {
        $d = $r->validate([
            'supplier_id' => 'required|exists:suppliers,id', 'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);
        DB::transaction(function () use ($d) {
            $po = Purchase::create(['supplier_id' => $d['supplier_id'], 'created_by' => auth()->id(), 'notes' => $d['notes'] ?? null, 'status' => 'pending']);
            $po->update(['reference' => 'PO-'.str_pad($po->id, 5, '0', STR_PAD_LEFT)]);
            foreach ($d['items'] as $i) {
                $po->items()->create(['product_id' => $i['product_id'], 'ordered_qty' => $i['quantity'], 'unit_cost' => $i['unit_cost'] ?? 0]);
            }
            ActivityLog::record('Purchase order created', $po->reference);
        });

        return redirect()->route('purchases.index')->with('success', 'Saved successfully');
    }

    public function receive(Request $r, Purchase $purchase)
    {
        $d = $r->validate([
            'delivery_reference' => 'nullable|string|max:60', 'received' => 'required|array', 'received.*' => 'nullable|numeric|min:0',
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:3072',
            'verified' => 'accepted', 'remarks' => 'nullable|string|max:150',
        ], ['verified.accepted' => 'Please confirm that you verified the delivery before posting it.']);
        $proof = $r->file('proof');
        $d['proof_data'] = base64_encode(file_get_contents($proof->getRealPath()));
        $d['proof_mime'] = $proof->getMimeType();
        $d['proof_name'] = substr(basename($proof->getClientOriginalName()), 0, 200);
        try {
            DB::transaction(function () use ($d, $purchase) {
                $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
                $delivery = null;
                foreach ($purchase->items()->with('product')->get() as $it) {
                    $q = round((float) ($d['received'][$it->id] ?? 0), 3);
                    if ($q <= 0) {
                        continue;
                    }
                    if ($q > $it->ordered_qty - $it->received_qty + 0.0005) {
                        throw new \RuntimeException("Received quantity for {$it->product->name} is more than what is outstanding.");
                    }
                    $delivery ??= Delivery::create([
                        'purchase_id' => $purchase->id, 'delivery_reference' => $d['delivery_reference'] ?? null,
                        'received_by' => auth()->id(), 'received_at' => now(),
                        'proof_data' => $d['proof_data'], 'proof_mime' => $d['proof_mime'], 'proof_name' => $d['proof_name'], 'remarks' => $d['remarks'] ?? null,
                    ]);
                    $di = $delivery->items()->create(['purchase_item_id' => $it->id, 'quantity' => $q]);
                    $it->increment('received_qty', $q);
                    StockService::post($it->product, $q, 'delivery', [
                        'purchase_item_id' => $it->id, 'delivery_item_id' => $di->id,
                        'reference' => ($d['delivery_reference'] ?? null) ?: $purchase->reference,
                    ]);
                }
                if (! $delivery) {
                    throw new \RuntimeException('Enter at least one received quantity.');
                }

                $done = $purchase->items()->get()->every(fn ($i) => $i->received_qty + 0.0005 >= $i->ordered_qty);
                $purchase->update(['status' => $done ? 'received' : 'partial', 'received_at' => $done ? now() : null]);
                ActivityLog::record('Delivery received', Str::limit($purchase->reference.' · verified against PO and delivery receipt'.(($d['remarks'] ?? null) ? ' · '.$d['remarks'] : ''), 250));
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.index')->with('success', 'Delivery received and stock updated.');
    }
}
