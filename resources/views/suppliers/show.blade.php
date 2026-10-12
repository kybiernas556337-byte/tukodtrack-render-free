@extends('layouts.app')
@section('crumb', 'Suppliers')
@section('content')
<x-page-head title="{{ $supplier->name }}" sub="Purchase history with this supplier.">
  <a class="btn ghost" href="{{ route('suppliers.index') }}">Back to suppliers</a>
</x-page-head>
<section class="card pad">
  <div class="row" style="gap:40px;flex-wrap:wrap">
    <div><small class="muted">Contact person</small><div>{{ $supplier->contact_person ?: '—' }}</div></div>
    <div><small class="muted">Phone</small><div>{{ $supplier->phone ?: '—' }}</div></div>
    <div><small class="muted">Email</small><div>{{ $supplier->email ?: '—' }}</div></div>
    <div><small class="muted">Address</small><div>{{ $supplier->address ?: '—' }}</div></div>
  </div>
</section>
<section class="card">
  @if($purchases->isEmpty())<x-empty title="No purchases yet" text="Purchase orders for this supplier will appear here." />@else
  <table class="tbl"><thead><tr><th>Reference</th><th>Date</th><th>Products ordered</th><th class="r">Ordered value</th><th>Status</th><th>Notes</th></tr></thead><tbody>
    @foreach($purchases as $po)
      <tr><td><b>{{ $po->reference }}</b></td><td>{{ dt($po->created_at, 'M j, Y') }}</td>
        <td>@foreach($po->items as $i){{ $i->product->name }} · {{ qty($i->received_qty) }}/{{ qty($i->ordered_qty) }} {{ $i->product->base_unit }}<br>@endforeach</td>
        <td class="r">{{ peso($po->items->sum(fn ($i) => $i->ordered_qty * $i->unit_cost)) }}</td>
        <td><span class="badge {{ $po->status === 'received' ? 'ok' : 'warn' }}">{{ $po->statusLabel() }}</span></td><td>{{ $po->notes ?: '—' }}</td></tr>
    @endforeach
  </tbody></table>@endif
</section>
@endsection
