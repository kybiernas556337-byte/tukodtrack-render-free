<?php
namespace App\Http\Controllers;

use App\Models\{ActivityLog, Supplier};
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->q);
        $suppliers = Supplier::when($q, fn ($x) => $x->where(fn ($w) => $w->whereLike('name', "%$q%")
            ->orWhereLike('contact_person', "%$q%")->orWhereLike('address', "%$q%")))->orderBy('name')->get();
        return view('suppliers.index', compact('suppliers', 'q'));
    }

    /** Purchase history for one supplier (read-only, for management review). */
    public function show(Supplier $supplier)
    {
        $purchases = \App\Models\Purchase::with('items.product')->where('supplier_id', $supplier->id)->latest()->get();
        return view('suppliers.show', compact('supplier', 'purchases'));
    }

    private function rules(): array
    {
        return ['name' => 'required|string|max:150', 'contact_person' => 'nullable|string|max:120', 'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:150', 'address' => 'nullable|string|max:255'];
    }

    public function store(Request $r)
    {
        $s = Supplier::create($r->validate($this->rules()));
        ActivityLog::record('Supplier added', $s->name);
        return redirect()->route('suppliers.index')->with('success', 'Saved successfully');
    }

    public function update(Request $r, Supplier $supplier)
    {
        $supplier->update($r->validate($this->rules()));
        ActivityLog::record('Supplier updated', $supplier->name);
        return redirect()->route('suppliers.index')->with('success', 'Saved successfully');
    }
}
