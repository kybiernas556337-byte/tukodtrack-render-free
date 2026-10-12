@extends('layouts.app')
@section('crumb', 'Sales')
@section('content')
<x-page-head title="Sales" sub="Record purchases and keep every transaction in one place.">
  <button class="btn" data-open="#saleModal"><x-icon name="plus" :size="16" /> New sale</button>
</x-page-head>
@include('partials.setup')

<section class="card">
  <form class="toolbar" method="GET">
    <div class="search"><x-icon name="search" :size="18" /><input class="input" name="q" value="{{ $q }}" placeholder="Search transactions"></div>
    <span class="count">{{ $sales->count() }} transactions</span>
  </form>
  @if($sales->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Items</th><th class="r">Total</th><th>Status</th><th></th></tr></thead><tbody>
    @foreach($sales as $s)
      <tr>
        <td><b>{{ $s->receipt_no }}</b></td><td>{{ dt($s->sale_date) }}</td><td>{{ $s->cashier?->name }}</td><td>{{ $s->items_count }}</td>
        <td class="r">{{ peso($s->total_amount) }}</td>
        <td><span class="badge {{ $s->status === 'completed' ? 'ok' : 'bad' }}">{{ ucfirst($s->status) }}</span>@if($s->void_reason)<br><small>{{ $s->void_reason }}</small>@endif</td>
        <td class="r">
          <a class="link" href="{{ route('sales.receipt', $s) }}" target="_blank">Receipt</a>
          @if(auth()->user()->isOwner() && $s->status === 'completed')
            <form method="POST" action="{{ route('sales.void', $s) }}" style="display:inline" onsubmit="return voidPrompt(this)">@csrf<input type="hidden" name="void_reason"><button class="link bad">Void</button></form>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody></table>@endif
</section>
@include('partials.sale-modal')
@endsection
