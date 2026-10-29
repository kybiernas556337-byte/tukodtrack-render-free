@extends('layouts.app')
@section('crumb', 'Utilities')
@section('content')
<x-page-head title="Utilities" sub="Keep a backup and review the record of store activity." />
<div class="two-eq">
  <section class="card"><x-icon name="download" :size="28" /><h3>Download a store backup</h3>
    <p>Download a PostgreSQL SQL backup containing the database structure and records. Keep this file private.</p>
    <a class="btn" href="{{ route('utilities.backup') }}"><x-icon name="download" :size="18" /> Download backup</a></section>
  <section class="card"><x-icon name="refresh" :size="28" /><h3>Restore SQL backup</h3>
    <p>SQL backup restoration is performed using PostgreSQL administration tools on a separate Neon branch. The web app intentionally cannot execute uploaded SQL against the live database. Restoring a full SQL dump into an existing database may overwrite its records.</p>
    <p><strong>Safe restore procedure:</strong> create a new, <strong>empty</strong> Neon recovery database (a copied branch is not empty), connect using its credentials, then use the guarded script in <code>scripts/restore-sql-to-empty-db.sh</code> to import the downloaded SQL file. Verify data before switching any application connection. Do not run this against production.</p>
    <small class="muted">Older JSON imports are not supported in this SQL-only interface.</small>
    </section>
</div>
<section class="card">
  <div class="ch"><div><h3>Activity log</h3><p class="sub" style="margin-top:6px">Recent changes and the people who made them</p></div></div>
  @if($logs->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Date</th><th>Activity</th><th>Details</th><th>User</th></tr></thead><tbody>
    @foreach($logs as $l)<tr><td>{{ dt($l->created_at) }}</td><td>{{ $l->activity }}</td><td>{{ $l->details }}</td><td>{{ $l->user?->name ?? '—' }}</td></tr>@endforeach
  </tbody></table>@endif
</section>
@endsection
