@extends('layouts.app')
@section('crumb', 'Utilities')
@section('content')
<x-page-head title="Utilities" sub="Keep a backup and review the record of store activity." />
<div class="two-eq">
  <section class="card"><x-icon name="download" :size="28" /><h3>Download a store backup</h3>
    <p>Download a full PostgreSQL SQL backup including schema, accounts, delivery proofs, and stock history. Keep this file private. Restore SQL into a separate database using PostgreSQL tools; see the deployment guide.</p>
    <a class="btn" href="{{ route('utilities.backup') }}"><x-icon name="download" :size="18" /> Download backup</a></section>
  <section class="card"><x-icon name="refresh" :size="28" /><h3>Import an older JSON backup</h3>
    <p>For legacy JSON files only. New SQL backups are restored using PostgreSQL tools. You will see its date and contents first, then confirm with your password. Current business records are replaced; account access is preserved.</p>
    <form method="POST" action="{{ route('utilities.restore.preview') }}" enctype="multipart/form-data">
      @csrf <input type="file" name="backup" id="backupFile" accept=".json,application/json" hidden onchange="this.form.submit()">
      <button type="button" class="btn ghost" onclick="document.getElementById('backupFile').click()">Select backup file</button>
    </form></section>
</div>
<section class="card">
  <div class="ch"><div><h3>Activity log</h3><p class="sub" style="margin-top:6px">Recent changes and the people who made them</p></div></div>
  @if($logs->isEmpty())<x-empty />@else
  <table class="tbl"><thead><tr><th>Date</th><th>Activity</th><th>Details</th><th>User</th></tr></thead><tbody>
    @foreach($logs as $l)<tr><td>{{ dt($l->created_at) }}</td><td>{{ $l->activity }}</td><td>{{ $l->details }}</td><td>{{ $l->user?->name ?? '—' }}</td></tr>@endforeach
  </tbody></table>@endif
</section>
@endsection
