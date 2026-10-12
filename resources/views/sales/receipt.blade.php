<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $sale->receipt_no }}</title><link rel="stylesheet" href="{{ asset('css/tukod.css') }}"></head>
<body style="background:#f1f3f2">
<div class="rcpt">
  <h2>Lesser Hardware<br>and Materials Supply</h2>
  <p style="text-align:center">Cabantian, Davao City</p><hr>
  <div>Receipt: {{ $sale->receipt_no }}</div><div>Date: {{ dt($sale->sale_date) }}</div><div>Cashier: {{ $sale->cashier?->name }}</div>
  @if($sale->status === 'voided')<div><b>*** VOIDED ***</b></div>@endif<hr>
  <table>@foreach($sale->items as $i)
    <tr><td colspan="2">{{ $i->product->name }}</td></tr>
    <tr><td>{{ qty($i->quantity) }} {{ $i->product->base_unit }} × {{ peso($i->unit_price) }}</td><td class="r">{{ peso($i->line_total) }}</td></tr>
  @endforeach</table><hr>
  <table>
    <tr><td><b>TOTAL</b></td><td class="r"><b>{{ peso($sale->total_amount) }}</b></td></tr>
    <tr><td>Amount received</td><td class="r">{{ peso($sale->amount_paid) }}</td></tr>
    <tr><td>Change</td><td class="r">{{ peso($sale->change_amount) }}</td></tr>
  </table><hr>
  <p style="text-align:center">Thank you for your purchase!</p>
  <p class="no-print" style="text-align:center;margin-top:18px"><button class="btn" onclick="window.print()">Print</button> <a class="btn ghost" href="{{ route('sales.index') }}">Back</a></p>
</div>
</body></html>
