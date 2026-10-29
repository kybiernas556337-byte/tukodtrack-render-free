@extends('layouts.app')
@section('crumb', 'Inventory report')
@section('content')
<x-page-head title="Inventory report" sub="Current inventory valuation, low-stock alerts, and item-by-item quantities." />
<div class="kpis" style="background:white;border-radius:12px;margin-bottom:24px;flex-wrap:wrap;gap:25px">
  <div><span>Active products</span><div class="n">{{ $products->count() }}</div></div>
  <div><span>Low or out of stock</span><div class="n">{{ $low }}</div></div>
  <div><span>Out of stock</span><div class="n">{{ $out }}</div></div>
  <div><span>Estimated retail stock value</span><div class="n">{{ peso($value) }}</div></div>
</div>
<section class="card">
  <div class="toolbar" style="flex-wrap:wrap">
    <strong>Inventory status report</strong>
    <span class="muted">Value is quantity on hand × selling price; it is not inventory cost.</span>
    <a class="btn ghost" href="{{ route('inventory.export') }}" style="margin-left:auto">Export CSV</a>
  </div>
  <table class="tbl"><thead><tr><th>Code</th><th>Product</th><th>Category</th><th class="r">Available</th><th class="r">Reorder level</th><th class="r">Retail value</th><th>Status</th></tr></thead><tbody>
  @forelse($products as $p)
    <tr><td>{{ $p->code }}</td><td>{{ $p->name }}</td><td>{{ $p->category?->name ?? '—' }}</td>
    <td class="r">{{ qty($p->quantity_on_hand) }} {{ $p->base_unit }}</td><td class="r">{{ qty($p->reorder_level) }}</td>
    <td class="r">{{ peso($p->quantity_on_hand * $p->selling_price) }}</td>
    <td><span class="badge {{ $p->badge() }}">{{ $p->status() }}</span></td></tr>
  @empty <tr><td colspan="7">No active inventory records.</td></tr> @endforelse
  </tbody></table>
</section>
@endsection
