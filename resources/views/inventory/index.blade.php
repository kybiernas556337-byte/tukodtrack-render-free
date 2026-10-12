@extends('layouts.app')
@section('crumb', 'Inventory')
@section('content')
@php $owner = auth()->user()->isOwner(); @endphp
<x-page-head title="Inventory" sub="Track stock movements and reconcile physical counts.">
  <button class="btn" data-open="#countModal"><x-icon name="clipboard" :size="16" /> Record count</button>
</x-page-head>

<div class="tabs">
  <a class="{{ $tab === 'levels' ? 'on' : '' }}" href="{{ route('inventory.index') }}">Stock levels</a>
  <a class="{{ $tab === 'counts' ? 'on' : '' }}" href="{{ route('inventory.index', ['tab' => 'counts']) }}">Physical counts</a>
  <a class="{{ $tab === 'movements' ? 'on' : '' }}" href="{{ route('inventory.index', ['tab' => 'movements']) }}">Stock movements</a>
</div>

<section class="card">
@if($tab === 'levels')
  <form class="toolbar" method="GET">
    <div class="search"><x-icon name="search" :size="18" /><input class="input" name="q" value="{{ $q }}" placeholder="Search products"></div>
    <select class="input" name="filter" style="width:210px;margin-left:auto" onchange="this.form.submit()">
      <option value="">All stock</option><option value="low" @selected($filter === 'low')>Low stock</option>
    </select>
    <a class="btn ghost" href="{{ route('inventory.export', ['q' => $q, 'filter' => $filter]) }}"><x-icon name="download" :size="18" /> Export</a>
  </form>
  @if($rows->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Product</th><th>Unit</th><th>Price</th><th>Available</th><th>Reorder at</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    @foreach($rows as $p)
      <tr><td><b>{{ $p->name }}</b><br><small>{{ $p->code }} · {{ $p->category?->name }}</small></td><td>{{ $p->base_unit }}</td><td>{{ peso($p->selling_price) }}</td>
        <td><b>{{ qty($p->quantity_on_hand) }}</b></td><td>{{ qty($p->reorder_level) }}</td>
        <td><span class="badge {{ $p->badge() }}">{{ $p->status() }}</span></td>
        <td><a class="link" href="{{ route('inventory.index', ['tab' => 'movements', 'q' => $p->code]) }}">History</a></td></tr>
    @endforeach
  </tbody></table>@endif

@elseif($tab === 'counts')
  @if($counts->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Date</th><th>Product</th><th>Expected</th><th>Counted</th><th>Difference</th><th>Reason</th><th>Submitted by</th><th>Status</th>@if($owner)<th></th>@endif</tr></thead><tbody>
    @foreach($counts as $c)
      <tr><td>{{ dt($c->requested_at) }}</td><td><b>{{ $c->product->name }}</b><br><small>{{ $c->product->code }}</small></td>
        <td>{{ qty($c->expected_qty) }}</td><td>{{ qty($c->counted_qty) }}</td>
        <td class="{{ $c->quantity_delta < 0 ? 'neg' : ($c->quantity_delta > 0 ? 'pos' : '') }}">{{ $c->quantity_delta > 0 ? '+' : '' }}{{ qty($c->quantity_delta) }}</td>
        <td>{{ $c->reason ?: '—' }}</td><td>{{ $c->requester?->name }}</td>
        <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad', 'pending' => 'warn'][$c->status] }}">{{ ucfirst($c->status) }}</span></td>
        @if($owner)<td class="r">
          @if($c->status === 'pending')
            <form method="POST" action="{{ route('inventory.approve', $c) }}" style="display:inline">@csrf<button class="link">Approve</button></form>
            <form method="POST" action="{{ route('inventory.reject', $c) }}" style="display:inline">@csrf<button class="link bad">Reject</button></form>
          @endif</td>@endif
      </tr>
    @endforeach
  </tbody></table>@endif

@else
  <form class="toolbar" method="GET"><input type="hidden" name="tab" value="movements">
    <div class="search"><x-icon name="search" :size="18" /><input class="input" name="q" value="{{ $q }}" placeholder="Search product, reference, or type"></div></form>
  @if($moves->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Date</th><th>Product</th><th>Movement</th><th>Quantity</th><th>Reference</th><th>Recorded by</th></tr></thead><tbody>
    @foreach($moves as $m)
      <tr><td>{{ dt($m->created_at) }}</td><td><b>{{ $m->product->name }}</b><br><small>{{ $m->reason }}</small></td><td>{{ $m->movement_type }}</td>
        <td class="{{ $m->quantity_delta < 0 ? 'neg' : 'pos' }}">{{ $m->quantity_delta > 0 ? '+' : '' }}{{ qty($m->quantity_delta) }} {{ $m->product->base_unit }}</td>
        <td>{{ $m->reference }}</td><td>{{ $m->recorder?->name }}</td></tr>
    @endforeach
  </tbody></table>@endif
@endif
</section>

<x-modal id="countModal" title="Record physical count" sub="The owner reviews counts before inventory changes.">
  <form method="POST" action="{{ route('inventory.count') }}">
    @csrf
    <div class="f"><label>Product</label>
      <select class="input" name="product_id" required><option value="">Choose product</option>
        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }} ({{ qty($p->quantity_on_hand) }} {{ $p->base_unit }} in system)</option>@endforeach</select></div>
    <div class="f"><label>Actual physical count</label><input class="input" name="counted_qty" type="number" step="any" min="0" required></div>
    <div class="f"><label>Reason for difference</label><input class="input" name="reason" maxlength="255"></div>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Submit for review</button></div>
  </form>
</x-modal>
@endsection
