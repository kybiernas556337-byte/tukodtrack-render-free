@extends('layouts.app')
@section('crumb', 'Overview')
@section('content')
<x-page-head title="Store overview" sub="A clear view of sales, stock, and what needs attention.">
  @if(auth()->user()->hasRole('owner', 'cashier'))
    <button class="btn" data-open="#saleModal"><x-icon name="plus" :size="16" /> New sale</button>
  @endif
</x-page-head>

@include('partials.setup')

<div class="stats">
  <div class="stat dark"><div class="t"><span>Today's sales</span><x-icon name="cart" /></div><div class="n">{{ peso($todaySales) }}</div><small>Completed transactions today</small></div>
  <div class="stat"><div class="t"><span>Transactions</span><x-icon name="doc" /></div><div class="n">{{ $todayCount }}</div><small class="muted">Sales recorded today</small></div>
  <div class="stat"><div class="t"><span>Active products</span><x-icon name="box" /></div><div class="n">{{ $productCount }}</div><small class="muted">In the product catalog</small></div>
  <div class="stat"><div class="t"><span>Low-stock products</span><x-icon name="alert" /></div><div class="n">{{ $lowCount }}</div><small class="muted">At or below the reorder level</small></div>
  <div class="stat"><div class="t"><span>Pending purchases</span><x-icon name="truck" /></div><div class="n">{{ $pending }}</div><small class="muted">Waiting for delivery</small></div>
</div>

<div class="two">
  <section class="card">
    <div class="ch"><div><h3>Stock that needs attention</h3><p class="sub" style="margin-top:6px">{{ $lowCount ? $lowCount . ' product(s) are at or below their reorder levels' : 'All active products are above their reorder levels' }}</p></div>
      @if(auth()->user()->hasRole('owner', 'stock'))<a class="link" href="{{ route('inventory.index') }}">View inventory</a>@endif</div>
    @if($lowStock->isEmpty())
      <x-empty title="No low-stock products" />
    @else
      <table class="tbl"><thead><tr><th>Product</th><th>Available</th><th>Reorder at</th></tr></thead><tbody>
        @foreach($lowStock as $p)
          <tr><td><b>{{ $p->name }}</b><br><small>{{ $p->code }}</small></td><td>{{ qty($p->quantity_on_hand) }} {{ $p->base_unit }}</td><td>{{ qty($p->reorder_level) }}</td></tr>
        @endforeach
      </tbody></table>
    @endif
  </section>

  <section class="card pad">
    <div class="eyebrow" style="margin-top:6px">DAILY OPERATIONS</div>
    <h3 style="font-size:22px;margin-bottom:10px">Keep the store moving</h3>
    <div class="ops">
      @if(auth()->user()->hasRole('owner', 'cashier'))
        <button type="button" data-open="#saleModal"><i><x-icon name="cart" /></i><span><b>Record a sale</b><small>Calculate totals and issue a receipt</small></span></button>
      @endif
      <a href="{{ route('products.index') }}"><i><x-icon name="box" /></i><span><b>Check stock</b><small>Find a product and its availability</small></span></a>
      @if(auth()->user()->hasRole('owner', 'stock'))
        <a href="{{ route('purchases.index') }}"><i><x-icon name="truck" /></i><span><b>Receive a delivery</b><small>Post stock against a purchase</small></span></a>
      @endif
    </div>
    <p class="muted" style="margin-top:18px;display:flex;gap:10px;align-items:center"><x-icon name="shield" :size="18" /> Stock updates are saved together with each completed transaction.</p>
  </section>
</div>

@if(auth()->user()->hasRole('owner', 'cashier'))
<section class="card">
  <div class="ch"><div><h3>Recent sales</h3><p class="sub" style="margin-top:6px">Your latest recorded transactions</p></div><a class="link" href="{{ route('sales.index') }}">View all sales</a></div>
  @if($recent->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th class="r">Total</th><th>Status</th></tr></thead><tbody>
    @foreach($recent as $s)
      <tr><td><b>{{ $s->receipt_no }}</b></td><td>{{ dt($s->sale_date) }}</td><td>{{ $s->cashier?->name }}</td><td class="r">{{ peso($s->total_amount) }}</td>
        <td><span class="badge {{ $s->status === 'completed' ? 'ok' : 'bad' }}">{{ ucfirst($s->status) }}</span></td></tr>
    @endforeach
  </tbody></table>@endif
</section>
@include('partials.sale-modal')
@endif
@endsection
