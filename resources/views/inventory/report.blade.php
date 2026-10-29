@extends('layouts.app')
@section('crumb','Inventory report')
@section('content')
<x-page-head title="Inventory report" sub="Current stock snapshot, generated {{ now()->format('M j, Y g:i A') }}. Values use selling prices, not purchase cost.">
<button class="btn ghost" data-print>Print</button><a class="btn" href="{{ route('inventory.export', request()->only('q','filter')) }}">Export CSV</a>
</x-page-head>
<form method="GET" class="toolbar no-print"><input class="input" name="q" value="{{ $q }}" placeholder="Product or code"><select name="filter" class="input"><option value="">All active stock</option><option value="low" @selected($filter === 'low')>Low stock</option></select><button class="btn">Filter</button></form>
<p>{{ $rows->count() }} products · {{ $rows->filter(fn($p)=>$p->quantity_on_hand <= $p->reorder_level)->count() }} at or below reorder level · Retail stock value: <b>{{ peso($rows->sum(fn($p)=>$p->quantity_on_hand*$p->selling_price)) }}</b></p>
<section class="card"><table class="tbl"><thead><tr><th>Code / product</th><th>Unit</th><th class="r">Available</th><th class="r">Reorder at</th><th>Status</th><th class="r">Retail value</th></tr></thead><tbody>
@forelse($rows as $p)<tr><td>{{ $p->code }} — {{ $p->name }}</td><td>{{ $p->base_unit }}</td><td class="r">{{ qty($p->quantity_on_hand) }}</td><td class="r">{{ qty($p->reorder_level) }}</td><td><span class="badge">{{ $p->status() }}</span></td><td class="r">{{ peso($p->quantity_on_hand*$p->selling_price) }}</td></tr>@empty<tr><td colspan="6">No matching products.</td></tr>@endforelse
</tbody></table></section>
@endsection
