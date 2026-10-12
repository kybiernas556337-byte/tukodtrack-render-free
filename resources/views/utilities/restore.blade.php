@extends('layouts.app')
@section('crumb', 'Utilities')
@section('content')
<x-page-head title="Restore business records" sub="Review the selected backup, then confirm with your password." />

<section class="card pad">
  <div class="row" style="gap:48px;flex-wrap:wrap;margin-bottom:22px">
    <div><small class="muted">Backup file</small><div><b>{{ $name }}</b></div></div>
    <div><small class="muted">Backup date</small><div><b>{{ $created->format('M j, Y, g:i A') }}</b></div></div>
    <div><small class="muted">Today</small><div><b>{{ now()->format('M j, Y, g:i A') }}</b></div></div>
  </div>
  <table class="tbl" style="max-width:640px"><thead><tr><th>Record</th><th class="r">In the backup</th><th class="r">Recorded after the backup date</th></tr></thead><tbody>
    @foreach($counts as $label => $n)
      <tr><td>{{ $label }}</td><td class="r">{{ $n }}</td><td class="r">{{ $later[$label] ?? '—' }}</td></tr>
    @endforeach
  </tbody></table>
  @if(array_sum($later) > 0)
    <div class="alert" style="margin:20px 0 0">{{ $later['Sales'] }} sale(s), {{ $later['Purchases'] }} purchase(s) and {{ $later['Stock movements'] }} stock movement(s) were recorded after this backup.
      They will be removed by the restore and must be reconciled (re-entered or checked against paper records) afterward.</div>
  @else
    <p class="muted" style="margin-top:18px">No transactions were recorded after this backup date.</p>
  @endif
</section>

<section class="card pad" style="max-width:640px">
  <form method="POST" action="{{ route('utilities.restore') }}">
    @csrf <input type="hidden" name="token" value="{{ $token }}">
    <div class="f"><label for="pw">Your password (management credentials)</label>
      <input class="input" id="pw" name="password" type="password" autocomplete="current-password" required autofocus>
      @error('password')<div class="err">{{ $message }}</div>@enderror</div>
    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:500"><input type="checkbox" name="acknowledge" value="1" style="margin-top:3px">
      I understand that the current business records will be replaced by this backup, and that I must reconcile any transactions recorded after the backup date.</label>
    @error('acknowledge')<div class="err">{{ $message }}</div>@enderror
    <div class="mfoot"><a class="btn ghost" href="{{ route('utilities.index') }}">Cancel</a><button class="btn">Restore backup</button></div>
  </form>
</section>
@endsection
