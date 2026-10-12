@if(auth()->user()->isOwner() && \App\Models\Product::count() === 0)
<section class="card setup">
  <div>
    <div class="eyebrow">LET'S SET UP YOUR STORE</div>
    <h3>Start with your product catalog</h3>
    <p class="sub" style="margin:0">Add your products and enter the opening stock quantities.</p>
  </div>
  <div class="row">
    @unless(app()->environment('production'))
      <form method="POST" action="{{ route('sample.load') }}">@csrf<button class="btn ghost">Load sample data</button></form>
    @endunless
    <a class="btn" href="{{ route('products.index', ['add' => 1]) }}"><x-icon name="plus" :size="16" /> Add first product</a>
  </div>
</section>
@endif
