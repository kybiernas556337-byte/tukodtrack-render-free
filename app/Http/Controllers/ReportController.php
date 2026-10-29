<?php
namespace App\Http\Controllers;

use App\Models\{Product, Sale, StockMovement};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\ReportPdf;

class ReportController extends Controller
{
    private function range(Request $r): array
    {
        $r->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $from = Carbon::parse($r->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($r->input('to', now()->toDateString()))->endOfDay();
        return [$from, $to];
    }

    private function tab(Request $r): string { return in_array($r->tab, ['inventory', 'activity']) ? $r->tab : 'summary'; }

    public function index(Request $r)
    {
        [$from, $to] = $this->range($r);
        $tab = $this->tab($r);
        $d = ['from' => $from, 'to' => $to, 'tab' => $tab];

        $sales = Sale::with('cashier')->whereBetween('sale_date', [$from, $to]);
        $d['net'] = (clone $sales)->where('status', 'completed')->sum('total_amount');
        $d['completed'] = (clone $sales)->where('status', 'completed')->count();
        $d['voided'] = (clone $sales)->where('status', 'voided')->count();

        if ($tab === 'summary') $d['sales'] = (clone $sales)->latest('sale_date')->get();
        if ($tab === 'inventory') $d['products'] = Product::active()->orderBy('name')->get();
        if ($tab === 'activity') $d['moves'] = StockMovement::with(['product', 'recorder'])->whereBetween('created_at', [$from, $to])->latest()->get();
        return view('reports.index', $d);
    }

    public function export(Request $r)
    {
        [$from, $to] = $this->range($r);
        $tab = $this->tab($r);
        $name = "report-$tab-" . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';

        if ($tab === 'inventory') {
            $rows = Product::active()->orderBy('name')->get()->map(fn ($p) => [$p->code, $p->name, $p->base_unit, $p->quantity_on_hand, $p->reorder_level, $p->status()]);
            return ReportPdf::download($name, 'Current Inventory Report', ['Code', 'Product', 'Unit', 'Available', 'Reorder at', 'Status'], $rows, ['Generated' => now()->format('Y-m-d H:i'), 'Prepared by' => auth()->user()->name]);
        }
        if ($tab === 'activity') {
            $rows = StockMovement::with(['product', 'recorder'])->whereBetween('created_at', [$from, $to])->latest()->get()
                ->map(fn ($m) => [dt($m->created_at), $m->product->name, $m->movement_type, $m->quantity_delta, $m->reference, $m->recorder?->name]);
            return ReportPdf::download($name, 'Stock Activity Report', ['Date', 'Product', 'Movement', 'Quantity', 'Reference', 'Recorded by'], $rows, ['Period' => $from->toDateString() . ' to ' . $to->toDateString(), 'Generated' => now()->format('Y-m-d H:i'), 'Prepared by' => auth()->user()->name]);
        }
        $rows = Sale::with('cashier')->whereBetween('sale_date', [$from, $to])->latest('sale_date')->get()
            ->map(fn ($s) => [$s->receipt_no, dt($s->sale_date), $s->cashier?->name, $s->total_amount, $s->amount_paid, $s->change_amount, $s->status]);
        return ReportPdf::download($name, 'Sales Summary Report', ['Receipt', 'Date', 'Cashier', 'Total', 'Paid', 'Change', 'Status'], $rows, ['Period' => $from->toDateString() . ' to ' . $to->toDateString(), 'Net sales' => (string) (clone $sales)->where('status', 'completed')->sum('total_amount'), 'Generated' => now()->format('Y-m-d H:i'), 'Prepared by' => auth()->user()->name]);
    }
}
