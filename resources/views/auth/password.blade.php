@extends('layouts.app')
@section('crumb','Change password')
@section('content')
<x-page-head title="Change password" sub="Use a unique passphrase of at least 12 characters." />
@if(auth()->user()->must_change_password)<div class="alert">Please replace your temporary password before continuing.</div>@endif
<form class="card" method="POST" action="{{ route('password.update') }}">@csrf
@foreach(['current_password'=>'Current password','password'=>'New password','password_confirmation'=>'Confirm new password'] as $name=>$label)
<div class="f"><label for="{{ $name }}">{{ $label }}</label><input class="input" id="{{ $name }}" name="{{ $name }}" type="password" autocomplete="{{ $name === 'current_password' ? 'current-password' : 'new-password' }}" required></div>
@endforeach
<button class="btn">Change password</button></form>
@endsection
