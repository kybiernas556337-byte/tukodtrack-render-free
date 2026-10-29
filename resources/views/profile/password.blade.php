@extends('layouts.app')
@section('crumb', 'Change password')
@section('content')
<x-page-head title="Change password" sub="Keep your account secure by using a unique password." />
<section class="card pad" style="max-width:620px">
  <form action="{{ route('password.update') }}" method="POST">
    @csrf
    <div class="f"><label for="current_password">Current password</label><input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>@error('current_password')<div class="err">{{ $message }}</div>@enderror</div>
    <div class="f"><label for="password">New password</label><input class="input" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required><small class="muted">At least 12 characters, including upper and lower case letters and a number.</small>@error('password')<div class="err">{{ $message }}</div>@enderror</div>
    <div class="f"><label for="password_confirmation">Confirm new password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
    <div class="mfoot"><a href="{{ route('overview') }}" class="btn ghost">Cancel</a><button class="btn" type="submit">Update password</button></div>
  </form>
</section>
@endsection
