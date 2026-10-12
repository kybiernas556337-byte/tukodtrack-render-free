@props(['id', 'title', 'sub' => null, 'wide' => false])
<dialog id="{{ $id }}" class="modal{{ $wide ? ' wide' : '' }}">
  <div class="modal-body">
    <button type="button" class="modal-x" data-close aria-label="Close"><x-icon name="x" :size="18" /></button>
    <h2>{{ $title }}</h2>
    @if($sub)<p class="sub">{{ $sub }}</p>@endif
    {{ $slot }}
  </div>
</dialog>
