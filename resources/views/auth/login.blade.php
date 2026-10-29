<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#17382d"><title>Sign in · TukodTrack</title><link rel="stylesheet" href="{{ asset('css/tukod.css') }}"></head>
<body class="auth-page">
<main class="auth-split">
  <section class="auth-feature" aria-label="About TukodTrack">
    <div class="auth-feature-shade"></div>
    <div class="auth-feature-inner">
      <div class="auth-brand"><span class="auth-brand-mark">T</span><div><strong>TukodTrack</strong><small>HARDWARE OPERATIONS</small></div></div>
      <div class="auth-hero">
        <span class="auth-pill">Hardware business management</span>
        <h1>Build stronger.<br><em>Manage smarter.</em></h1>
        <p>All your hardware operations in one place—from product inventory and sales to supplier deliveries and business reporting.</p>
        <ul class="auth-feature-list">
          <li><span aria-hidden="true">✓</span> Real-time inventory monitoring</li>
          <li><span aria-hidden="true">✓</span> Sales and transaction tracking</li>
          <li><span aria-hidden="true">✓</span> Purchasing and stock insights</li>
        </ul>
      </div>
      <p class="auth-feature-footer">Lesser Hardware and Materials Supply</p>
    </div>
  </section>
  <section class="auth-form-side">
    <div class="auth-form-wrap">
      <div class="auth-mobile-brand"><span class="auth-brand-mark">T</span><strong>TukodTrack</strong></div>
      <p class="auth-eyebrow">WELCOME BACK</p>
      <h2>Sign in to your account</h2>
      <p class="auth-form-sub">Access your hardware operations dashboard securely.</p>
      @if(session('status'))<div class="auth-success" role="status">{{ session('status') }}</div>@endif
      @if($errors->any())<div class="auth-error" role="alert">We couldn't sign you in. Please check your details below.</div>@endif
      <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="auth-field"><label for="email">Email address</label>
          <input class="auth-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="username" required autofocus>
          @error('email')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>
        <div class="auth-field"><label for="password">Password</label>
          <div class="auth-password-wrap"><input class="auth-input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
          <button class="auth-password-toggle" type="button" id="togglePassword" aria-label="Show password" aria-pressed="false">Show</button></div>
          @error('password')<small class="auth-field-error">{{ $message }}</small>@enderror
        </div>
        <label class="auth-remember"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> Keep me signed in</label>
        <button class="auth-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
      </form>
      <p class="auth-help">Access is managed by your store administrator. Contact them if you need an account or password assistance.</p>
      <p class="auth-copyright">© {{ date('Y') }} TukodTrack · Hardware Operations</p>
    </div>
  </section>
</main>
<script>
(function () {
  const toggle = document.getElementById('togglePassword');
  const field = document.getElementById('password');
  if (!toggle || !field) return;
  toggle.addEventListener('click', function () {
    const visible = field.type === 'password';
    field.type = visible ? 'text' : 'password';
    toggle.textContent = visible ? 'Hide' : 'Show';
    toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', String(visible));
  });
})();
</script>
</body></html>
