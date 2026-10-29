<?php
namespace App\Http\Controllers;

use App\Models\{ActivityLog, Category, Product};
use App\Services\StockService;
use App\Services\ReportPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->q);
        $products = Product::with('category')
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->whereLike('name', "%$q%")->orWhereLike('code', "%$q%")
                ->orWhereHas('category', fn ($c) => $c->whereLike('name', "%$q%"))))
            ->orderBy('name')->get();
        return view('products.index', ['products' => $products, 'q' => $q, 'categories' => Category::orderBy('name')->pluck('name')]);
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => 'required|string|max:150',
            'code' => ['required', 'string', 'max:40', Rule::unique('products', 'code')->ignore($id)],
            'category' => 'required|string|max:60',
            'base_unit' => 'required|string|max:30',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
        ];
    }

    private function fields(array $d): array
    {
        return [
            'name' => trim($d['name']), 'code' => strtoupper(trim($d['code'])),
            'category_id' => Category::firstOrCreate(['name' => trim($d['category'])])->id,
            'base_unit' => trim($d['base_unit']), 'selling_price' => $d['selling_price'], 'reorder_level' => $d['reorder_level'],
        ];
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules());
        DB::transaction(function () use ($d) {
            $p = Product::create($this->fields($d) + ['quantity_on_hand' => 0]);
            if (($d['opening_stock'] ?? 0) > 0) {
                StockService::post($p, (float) $d['opening_stock'], 'opening', ['reference' => $p->code, 'reason' => 'Verified opening stock']);
            }
            ActivityLog::record('Product added', $p->code . ' · ' . $p->name);
        });
        return redirect()->route('products.index')->with('success', 'Saved successfully');
    }

    public function update(Request $r, Product $product)
    {
        $d = $r->validate($this->rules($product->id));
        $product->update($this->fields($d));
        ActivityLog::record('Product updated', $product->code . ' · ' . $product->name);
        return redirect()->route('products.index')->with('success', 'Saved successfully');
    }

    public function archive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        ActivityLog::record($product->is_active ? 'Product restored' : 'Product archived', $product->code . ' · ' . $product->name);
        return back()->with('success', 'Saved successfully');
    }

    public function export()
    {
        $rows = Product::with('category')->orderBy('name')->get()->map(fn ($p) => [
            $p->code, $p->name, $p->category?->name, $p->base_unit, $p->selling_price, $p->quantity_on_hand, $p->reorder_level, $p->status(),
        ]);
        return ReportPdf::download('products-' . date('Ymd') . '.pdf', 'Product Catalog Report', ['Code', 'Product', 'Category', 'Unit', 'Price', 'Available', 'Reorder at', 'Status'], $rows, ['Generated' => now()->format('Y-m-d H:i'), 'Prepared by' => auth()->user()->name]);
    }
}
