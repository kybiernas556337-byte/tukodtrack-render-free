@php $picker = \App\Models\Product::picker(); @endphp
<x-modal id="saleModal" title="New sale" sub="Add products and enter the payment received." wide>
  <form method="POST" action="{{ route('sales.store') }}" id="saleForm" data-products="{{ json_encode($picker) }}">
    @csrf
    <input class="input" list="saleList" data-search placeholder="Search product name or code" autocomplete="off">
    <datalist id="saleList">@foreach($picker as $p)<option value="{{ $p['code'] }} — {{ $p['name'] }}">@endforeach</datalist>
    <div data-lines class="lines"><x-empty title="Select a product to begin" /></div>
    <div class="paybox">
      <div><span>Total amount</span><div class="big" data-total>₱0.00</div><small data-change>Change: ₱0.00</small></div>
      <div style="width:263px"><label for="amount_paid">Amount received (PHP)</label>
        <input class="input" id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" placeholder="0.00"></div>
    </div>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn" data-submit disabled>Complete sale</button></div>
  </form>
</x-modal>
