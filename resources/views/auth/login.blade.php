<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · TukodTrack</title><link rel="stylesheet" href="{{ asset('css/tukod.css') }}"></head>
<body>
<div class="login"><div class="box">
  <div class="brand"><span class="logo">T</span><div><b>TukodTrack</b><small>HARDWARE OPERATIONS</small></div></div>
  <h2 style="font-size:26px">Sign in</h2>
  <p class="sub" style="margin-bottom:22px">Lesser Hardware and Materials Supply</p>
  <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="f"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
      @error('email')<div class="err">{{ $message }}</div>@enderror</div>
    <div class="f"><label for="password">Password</label><input class="input" id="password" name="password" type="password" required></div>
    <label style="display:flex;gap:8px;align-items:center;font-weight:400"><input type="checkbox" name="remember"> Keep me signed in</label>
    <button class="btn" style="width:100%;justify-content:center;margin-top:20px">Sign in</button>
  </form>
</div></div>
</body></html>
