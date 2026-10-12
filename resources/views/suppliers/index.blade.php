@extends('layouts.app')
@section('crumb', 'Suppliers')
@section('content')
@php $owner = auth()->user()->isOwner(); @endphp
<x-page-head title="Suppliers" sub="The people and businesses that keep your shelves stocked.">
  @if($owner)<button class="btn" data-supplier-add><x-icon name="plus" :size="16" /> Add supplier</button>@endif
</x-page-head>
<section class="card">
  <form class="toolbar" method="GET"><div class="search"><x-icon name="search" :size="18" /><input class="input" name="q" value="{{ $q }}" placeholder="Search suppliers"></div></form>
  @if($suppliers->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Supplier</th><th>Contact person</th><th>Phone</th><th>Email</th><th>Address</th><th></th></tr></thead><tbody>
    @foreach($suppliers as $s)
      <tr><td><b>{{ $s->name }}</b></td><td>{{ $s->contact_person ?: '—' }}</td><td>{{ $s->phone ?: '—' }}</td><td>{{ $s->email ?: '—' }}</td><td>{{ $s->address ?: '—' }}</td>
        <td class="r"><a class="link" href="{{ route('suppliers.show', $s) }}">History</a>@if($owner)<button class="link" data-supplier-edit="{{ json_encode($s->only(['id', 'name', 'contact_person', 'phone', 'email', 'address'])) }}">Edit</button>@endif</td></tr>
    @endforeach
  </tbody></table>@endif
</section>
@if($owner)
<x-modal id="supplierModal" title="Add supplier" sub="Keep supplier details together for purchasing.">
  <form method="POST" action="{{ route('suppliers.store') }}" data-store="{{ route('suppliers.store') }}" data-base="{{ url('/suppliers') }}">
    @csrf <input type="hidden" name="_method" value="POST">
    <div class="grid2">
      <div class="f"><label>Supplier name</label><input class="input" name="name" required></div>
      <div class="f"><label>Contact person</label><input class="input" name="contact_person"></div>
      <div class="f"><label>Phone</label><input class="input" name="phone"></div>
      <div class="f"><label>Email</label><input class="input" name="email" type="email"></div>
      <div class="f"><label>Address</label><input class="input" name="address"></div>
    </div>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Save changes</button></div>
  </form>
</x-modal>
@endif
@endsection
