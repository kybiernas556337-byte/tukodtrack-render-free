@extends('layouts.app')
@section('crumb', 'Purchases')
@section('content')
@php $owner = auth()->user()->isOwner(); $picker = \App\Models\Product::picker(); @endphp
<x-page-head title="Purchases" sub="Plan replenishment and record incoming deliveries.">
  @if($owner)<button class="btn" data-open="#poModal"><x-icon name="plus" :size="16" /> New purchase</button>@endif
</x-page-head>
<section class="card">
  <form class="toolbar" method="GET"><div class="search"><x-icon name="search" :size="18" /><input class="input" name="q" value="{{ $q }}" placeholder="Search purchase or supplier"></div>
    <span class="count">{{ $awaiting }} awaiting delivery</span></form>
  @if($purchases->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Reference</th><th>Supplier</th><th>Date</th><th>Items</th><th>Status</th><th>Notes</th><th></th></tr></thead><tbody>
    @foreach($purchases as $po)
      <tr><td><b>{{ $po->reference }}</b></td><td>{{ $po->supplier?->name }}</td><td>{{ dt($po->created_at, 'M j, Y') }}</td><td>{{ $po->items_count }}</td>
        <td><span class="badge {{ $po->status === 'received' ? 'ok' : 'warn' }}">{{ $po->statusLabel() }}</span></td><td>{{ $po->notes ?: '—' }}</td>
        <td class="r">@if($po->status !== 'received')<button class="link" data-open="#recv{{ $po->id }}">Receive</button>@endif</td></tr>
    @endforeach
  </tbody></table>@endif
</section>

@foreach($purchases->where('status', '!=', 'received') as $po)
<x-modal id="recv{{ $po->id }}" title="Receive delivery" sub="{{ $po->reference }} · {{ $po->supplier?->name }}. Stock increases only for what you enter here.">
  <form method="POST" action="{{ route('purchases.receive', $po) }}">
    @csrf
    <div class="f"><label>Delivery reference (optional)</label><input class="input" name="delivery_reference" maxlength="60" placeholder="Delivery receipt no."></div>
    <table class="tbl ln"><thead><tr><th>Product</th><th>Ordered</th><th>Received so far</th><th>Receiving now</th></tr></thead><tbody>
      @foreach($po->items as $it)
        <tr><td><b>{{ $it->product->name }}</b><br><small>{{ $it->product->code }}</small></td><td>{{ qty($it->ordered_qty) }}</td><td>{{ qty($it->received_qty) }}</td>
          <td><input class="input sm" type="number" step="any" min="0" max="{{ $it->ordered_qty - $it->received_qty }}" name="received[{{ $it->id }}]" value="{{ $it->ordered_qty - $it->received_qty }}"> {{ $it->product->base_unit }}</td></tr>
      @endforeach
    </tbody></table>
    <div class="f" style="margin-top:14px"><label>Delivery remarks (optional)</label><input class="input" name="remarks" maxlength="150" placeholder="e.g. 2 bags damaged, returned to driver"></div>
    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:500"><input type="checkbox" name="verified" value="1" required style="margin-top:3px">
      I checked the delivered items against the purchase order and the delivery receipt.</label>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Post delivery</button></div>
  </form>
</x-modal>
@endforeach

@if($owner)
<x-modal id="poModal" title="New purchase order" sub="Ordered quantities enter stock only when a delivery is received." wide>
  <form method="POST" action="{{ route('purchases.store') }}" id="poForm" data-products="{{ json_encode($picker) }}">
    @csrf
    <div class="f"><label>Supplier</label>
      <select class="input" name="supplier_id" required><option value="">{{ $suppliers->isEmpty() ? 'Add a supplier first' : 'Choose supplier' }}</option>
        @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
    <input class="input" list="poList" data-search placeholder="Search product name or code" autocomplete="off">
    <datalist id="poList">@foreach($picker as $p)<option value="{{ $p['code'] }} — {{ $p['name'] }}">@endforeach</datalist>
    <div data-lines class="lines"><x-empty title="Select a product to begin" /></div>
    <div class="f"><label>Purchase notes</label><input class="input" name="notes" maxlength="500"></div>
    <p class="muted" data-total style="text-align:right"></p>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn" data-submit disabled>Save purchase order</button></div>
  </form>
</x-modal>
@endif
@endsection
