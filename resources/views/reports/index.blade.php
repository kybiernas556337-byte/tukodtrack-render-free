@extends('layouts.app')
@section('crumb', 'Reports')
@section('content')
@php $qs = ['from' => $from->toDateString(), 'to' => $to->toDateString()]; @endphp
<x-page-head title="Reports" sub="Review sales performance and inventory activity." />
@php $tabNames = ['summary' => 'Sales summary', 'inventory' => 'Current inventory', 'activity' => 'Stock activity']; @endphp
<p class="report-meta"><b>{{ $tabNames[$tab] }}</b> ·
  @if($tab === 'inventory')Balances as of the generation date
  @else Period: {{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}@endif
  · Generated: {{ dt(now()) }} by {{ auth()->user()->name }}</p>

<form method="GET" class="date-row">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div><label>From</label><input class="input" type="date" name="from" value="{{ $from->toDateString() }}" onchange="this.form.submit()"></div>
  <div><label>To</label><input class="input" type="date" name="to" value="{{ $to->toDateString() }}" onchange="this.form.submit()"></div>
  <button type="button" class="btn ghost" data-print><x-icon name="print" :size="18" /> Print report</button>
  <a class="btn ghost" href="{{ route('reports.export', $qs + ['tab' => $tab]) }}"><x-icon name="download" :size="18" /> Export CSV</a>
</form>

<div class="tabs">
  <a class="{{ $tab === 'summary' ? 'on' : '' }}" href="{{ route('reports.index', $qs + ['tab' => 'summary']) }}">Sales summary</a>
  <a class="{{ $tab === 'inventory' ? 'on' : '' }}" href="{{ route('reports.index', $qs + ['tab' => 'inventory']) }}">Current inventory</a>
  <a class="{{ $tab === 'activity' ? 'on' : '' }}" href="{{ route('reports.index', $qs + ['tab' => 'activity']) }}">Stock activity</a>
</div>

@if($tab === 'summary')
<section class="card kpis">
  <div><span>Net sales in selected period</span><div class="n">{{ peso($net) }}</div></div>
  <div><span>Completed transactions</span><div class="n">{{ $completed }}</div></div>
  <div><span>Voided transactions</span><div class="n">{{ $voided }}</div></div>
</section>
<section class="card">
  @if($sales->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th class="r">Total</th><th>Status</th></tr></thead><tbody>
    @foreach($sales as $s)<tr><td><b>{{ $s->receipt_no }}</b></td><td>{{ dt($s->sale_date) }}</td><td>{{ $s->cashier?->name }}</td><td class="r">{{ peso($s->total_amount) }}</td>
      <td><span class="badge {{ $s->status === 'completed' ? 'ok' : 'bad' }}">{{ ucfirst($s->status) }}</span></td></tr>@endforeach
  </tbody></table>@endif
</section>
@elseif($tab === 'inventory')
<section class="card">
  @if($products->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Product</th><th>Unit</th><th>Available</th><th>Reorder at</th><th>Status</th></tr></thead><tbody>
    @foreach($products as $p)<tr><td><b>{{ $p->name }}</b><br><small>{{ $p->code }}</small></td><td>{{ $p->base_unit }}</td><td><b>{{ qty($p->quantity_on_hand) }}</b></td><td>{{ qty($p->reorder_level) }}</td>
      <td><span class="badge {{ $p->badge() }}">{{ $p->status() }}</span></td></tr>@endforeach
  </tbody></table>@endif
</section>
@else
<section class="card">
  @if($moves->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Date</th><th>Product</th><th>Movement</th><th>Quantity</th><th>Reference</th><th>Recorded by</th></tr></thead><tbody>
    @foreach($moves as $m)<tr><td>{{ dt($m->created_at) }}</td><td><b>{{ $m->product->name }}</b></td><td>{{ $m->movement_type }}</td>
      <td class="{{ $m->quantity_delta < 0 ? 'neg' : 'pos' }}">{{ $m->quantity_delta > 0 ? '+' : '' }}{{ qty($m->quantity_delta) }} {{ $m->product->base_unit }}</td><td>{{ $m->reference }}</td><td>{{ $m->recorder?->name }}</td></tr>@endforeach
  </tbody></table>@endif
</section>
@endif
@endsection
