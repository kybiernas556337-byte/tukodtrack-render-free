@props(['title', 'sub'])
<div class="head">
  <div>
    <div class="eyebrow">LESSER HARDWARE AND MATERIALS SUPPLY</div>
    <h1>{{ $title }}</h1>
    <p class="sub">{{ $sub }}</p>
  </div>
  <div class="head-actions">{{ $slot }}</div>
</div>
@if(\App\Models\Setting::get('sample_loaded'))
  <div class="banner"><b>Sample records</b> This store includes illustrative data for testing.</div>
@endif
