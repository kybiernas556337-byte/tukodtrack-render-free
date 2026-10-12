@extends('layouts.app')
@section('crumb', 'User access')
@section('content')
<x-page-head title="User access" sub="Manage who can access your store and what they can do.">
  <button class="btn" data-user-add><x-icon name="plus" :size="16" /> Add account</button>
</x-page-head>
<div class="note"><x-icon name="shield" /> Accounts sign in with their email and password. Add an employee's email, set a temporary password, and assign their role.</div>

<section class="card">
  <table class="tbl"><thead><tr><th>Name</th><th>Sign-in email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
    @foreach($users as $u)
      <tr><td><b>{{ $u->name }}</b>@if($u->id === auth()->id())<br><small>Your account</small>@endif</td><td>{{ $u->email }}</td><td>{{ $u->roleLabel() }}</td>
        <td><span class="badge {{ $u->is_active ? 'ok' : 'muted' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
        <td class="r"><button class="link" data-user-edit="{{ json_encode($u->only(['id', 'name', 'email', 'role', 'is_active'])) }}">Edit access</button></td></tr>
    @endforeach
  </tbody></table>
</section>

<div class="roles">
  <div class="card"><h3>Owner / Manager</h3><p>Catalog, purchases, approvals, reports, accounts, and backups.</p></div>
  <div class="card"><h3>Cashier</h3><p>Product availability, sales recording, and receipt printing.</p></div>
  <div class="card"><h3>Stock Personnel</h3><p>Stock monitoring, delivery receiving, and count submissions.</p></div>
</div>

<x-modal id="userModal" title="Add account" sub="Access is linked to the employee's sign-in email.">
  <form method="POST" action="{{ route('users.store') }}" data-store="{{ route('users.store') }}" data-base="{{ url('/user-access') }}">
    @csrf <input type="hidden" name="_method" value="POST">
    <div class="f"><label>Full name</label><input class="input" name="name" required></div>
    <div class="f"><label>Sign-in email</label><input class="input" name="email" type="email" required></div>
    <div class="f"><label>Password <small class="muted" data-edit-only hidden>(leave blank to keep the current one)</small></label><input class="input" name="password" type="password" minlength="8" autocomplete="new-password" required></div>
    <div class="f"><label>Role</label><select class="input" name="role">
      @foreach(\App\Models\User::ROLES as $k => $label)<option value="{{ $k }}" @selected($k === 'cashier')>{{ $label }}</option>@endforeach</select></div>
    <div class="switch"><span>Active account</span><input type="checkbox" name="is_active" value="1" checked></div>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Save changes</button></div>
  </form>
</x-modal>
@endsection
