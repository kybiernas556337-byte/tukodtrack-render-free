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

<section class="card pad" style="max-width:700px">
  <h3 style="margin-bottom:10px">Create additional role</h3>
  <p class="muted" style="margin-bottom:16px">Custom roles inherit cashier or stock personnel permissions. Owner privileges cannot be assigned through a custom role.</p>
  <form method="POST" action="{{ route('users.roles.store') }}" data-confirm="Add this new role to the list?">
    @csrf
    <div class="f"><label>New role name</label><input class="input" name="role_name" maxlength="80" required placeholder="e.g. Assistant Cashier"></div>
    <div class="f"><label>Access template</label><select class="input" name="base_role" required><option value="cashier">Cashier access</option><option value="stock">Stock personnel access</option></select></div>
    <button class="btn">Add role</button>
  </form>
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
    <div class="f"><label>Password <small class="muted" data-edit-only hidden>(leave blank to keep the current one)</small></label><input class="input" name="password" type="password" minlength="8" autocomplete="new-password" aria-describedby="account-password-rules" required><small id="account-password-rules" class="muted">At least 8 characters, one uppercase letter, one lowercase letter, and one special symbol (!, @, #, etc.).</small>></div>
    <div class="f"><label>Role</label><select class="input" name="role">
      @foreach($roles as $k => $label)<option value="{{ $k }}" @selected($k === 'cashier')>{{ $label }}</option>@endforeach</select></div>
    <div class="switch"><span>Active account</span><input type="checkbox" name="is_active" value="1" checked></div>
    <div class="mfoot"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Save changes</button></div>
  </form>
</x-modal>
@endsection
