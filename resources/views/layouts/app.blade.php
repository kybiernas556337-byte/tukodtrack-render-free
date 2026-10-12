<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('crumb', 'TukodTrack') · TukodTrack</title>
  <link rel="stylesheet" href="{{ asset('css/tukod.css') }}">
</head>
<body>
@php
  $u = auth()->user();
  $lowCount = \App\Models\Product::active()->low()->count();
  $nav = [
    ['route' => 'overview', 'label' => 'Overview', 'icon' => 'grid', 'roles' => ['owner', 'cashier', 'stock'], 'on' => 'overview'],
    ['route' => 'sales.index', 'label' => 'Sales', 'icon' => 'cart', 'roles' => ['owner', 'cashier'], 'on' => 'sales.*'],
    ['route' => 'products.index', 'label' => 'Products', 'icon' => 'box', 'roles' => ['owner', 'cashier', 'stock'], 'on' => 'products.*'],
    ['route' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'warehouse', 'roles' => ['owner', 'stock'], 'on' => 'inventory.*'],
    ['route' => 'suppliers.index', 'label' => 'Suppliers', 'icon' => 'truck', 'roles' => ['owner', 'stock'], 'on' => 'suppliers.*'],
    ['route' => 'purchases.index', 'label' => 'Purchases', 'icon' => 'clipboard', 'roles' => ['owner', 'stock'], 'on' => 'purchases.*'],
    ['route' => 'reports.index', 'label' => 'Reports', 'icon' => 'chart', 'roles' => ['owner'], 'on' => 'reports.*'],
  ];
  $mgmt = [
    ['route' => 'users.index', 'label' => 'User access', 'icon' => 'user', 'on' => 'users.*'],
    ['route' => 'utilities.index', 'label' => 'Utilities', 'icon' => 'gear', 'on' => 'utilities.*'],
  ];
@endphp
<div class="shell">
  <aside class="side">
    <div class="brand"><span class="logo">T</span><div><b>TukodTrack</b><small>HARDWARE<br>OPERATIONS</small></div></div>
    <nav>
      <div class="nav-h">WORKSPACE</div>
      @foreach($nav as $n)
        @if(in_array($u->role, $n['roles']))
          <a href="{{ route($n['route']) }}" class="nav-i {{ request()->routeIs($n['on']) ? 'on' : '' }}">
            <x-icon :name="$n['icon']" /> <span>{{ $n['label'] }}</span>
            @if($n['label'] === 'Inventory' && $lowCount)<em>{{ $lowCount }}</em>@endif
          </a>
        @endif
      @endforeach
      @if($u->isOwner())
        <div class="nav-h">MANAGEMENT</div>
        @foreach($mgmt as $n)
          <a href="{{ route($n['route']) }}" class="nav-i {{ request()->routeIs($n['on']) ? 'on' : '' }}"><x-icon :name="$n['icon']" /> <span>{{ $n['label'] }}</span></a>
        @endforeach
      @endif
    </nav>
    <div class="store"><x-icon name="warehouse" :size="26" /><div><b>Lesser Hardware</b><small>Cabantian, Davao City</small></div></div>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="signout"><x-icon name="logout" /> Sign out</button></form>
  </aside>

  <div class="main">
    <header class="top">
      <button class="tg" data-toggle-side aria-label="Toggle sidebar"><x-icon name="panel" /></button>
      <div class="crumb"><span>Store workspace</span><span>›</span><b>@yield('crumb')</b></div>
      <div class="who">
        <span class="av">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
        <div><b>{{ $u->name }}</b><small>{{ $u->roleLabel() }}</small></div>
      </div>
    </header>

    <main class="page">
      @if(session('success'))
        <div class="toast" data-toast><x-icon name="check" /> {{ session('success') }}
          @if(session('receipt'))<a href="{{ session('receipt') }}" target="_blank">Print receipt</a>@endif</div>
      @endif
      @if(session('error'))<div class="toast bad" data-toast><x-icon name="alert" /> {{ session('error') }}</div>@endif
      @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif

      @yield('content')

      <div class="foot">
        <span>TukodTrack · Lesser Hardware and Materials Supply</span>
        <button type="button" data-reload><x-icon name="refresh" :size="18" /> Refresh records</button>
      </div>
    </main>
  </div>
</div>
<script src="{{ asset('js/tukod.js') }}"></script>
</body>
</html>
