@extends('layouts.app')
@section('crumb', 'Products')
@section('content')
@php $owner = auth()->user()->isOwner(); @endphp
<x-page-head title="Products" sub="Your product catalog, prices, and stock levels.">
  @if($owner)<button class="btn" data-product-add><x-icon name="plus" :size="16" /> Add product</button>@endif
</x-page-head>

<section class="card">
  <form class="toolbar" method="GET">
    <div class="search"><x-icon name="search" :size="18" /><input class="input" name="q" value="{{ $q }}" placeholder="Search name, code, or category"></div>
    @if($owner)<a class="btn ghost" style="margin-left:auto" href="{{ route('products.export') }}"><x-icon name="download" :size="18" /> Export CSV</a>@endif
  </form>
  @if($products->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Product</th><th>Unit</th><th>Price</th><th>Available</th><th>Reorder at</th><th>Status</th>@if($owner)<th>Actions</th>@endif</tr></thead><tbody>
    @foreach($products as $p)
      <tr>
        <td><b>{{ $p->name }}</b><br><small>{{ $p->code }} · {{ $p->category?->name }}</small></td>
        <td>{{ $p->base_unit }}</td><td>{{ peso($p->selling_price) }}</td><td><b>{{ qty($p->quantity_on_hand) }}</b></td><td>{{ qty($p->reorder_level) }}</td>
        <td><span class="badge {{ $p->badge() }}">{{ $p->status() }}</span></td>
        @if($owner)
        <td>
          <button class="link" data-product-edit="{{ json_encode(['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'category' => $p->category?->name, 'base_unit' => $p->base_unit, 'selling_price' => $p->selling_price, 'reorder_level' => $p->reorder_level]) }}">Edit</button>
          <form method="POST" action="{{ route('products.archive', $p) }}" style="display:inline">@csrf<button class="link">{{ $p->is_active ? 'Archive' : 'Restore' }}</button></form>
        </td>
        @endif
      </tr>
    @endforeach
  </tbody></table>@endif
</section>

@if($owner)
<x-modal id="productModal" title="Add product" sub="Maintain the product's base unit, price, and reorder level.">
  <form method="POST" action="{{ route('products.store') }}" data-store="{{ route('products.store') }}" data-base="{{ url('/products') }}">
    @csrf <input type="hidden" name="_method" value="POST">
    <div class="grid2">
      <div class="f"><label>Product name</label><input class="input" name="name" required></div>
      <div class="f"><label>Product code</label><input class="input" name="code" required></div>
      <div class="f"><label>Category</label><input class="input" name="category" list="catList" value="Construction" required>
        <datalist id="catList">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist></div>
      <div class="f"><label>Base unit</label>
        @php
          $unitGroups = [
            'Count' => ['piece', 'pair', 'set', 'dozen', 'pack', 'box', 'carton', 'bundle', 'roll', 'sheet', 'length'],
            'Bulk' => ['bag', 'sack'],
            'Weight' => ['kg', 'gram', 'ton'],
            'Length / area / volume' => ['meter', 'foot', 'sq. meter', 'cu. meter'],
            'Liquids and containers' => ['liter', 'gallon', 'can', 'pail', 'bottle', 'tube', 'drum'],
          ];
        @endphp
        <select class="input" name="base_unit" required>
          @foreach($unitGroups as $group => $units)
            <optgroup label="{{ $group }}">
              @foreach($units as $unit)<option value="{{ $unit }}" @selected($unit === 'piece')>{{ $unit }}</option>@endforeach
            </optgroup>
          @endforeach
        </select></div>
      <div class="f"><label>Selling price (PHP)</label><input class="input" name="selling_price" type="number" step="0.01" min="0" required></div>
      <div class="f"><label>Reorder level</label><input class="input" name="reorder_level" type="number" step="any" min="0" value="0" required></div>
      <div class="f" data-add-only><label>Verified opening stock</label><input class="input" name="opening_stock" type="number" step="any" min="0" value="0"></div>
    </div>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Save changes</button></div>
  </form>
</x-modal>
@endif
@endsection
