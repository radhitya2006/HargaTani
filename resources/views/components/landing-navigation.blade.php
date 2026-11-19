<nav aria-label="Navigasi utama" class="ht-nav">
    <a class="ht-nav-link" href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif><flux:icon.home class="size-5" /> Beranda</a>
    <a class="ht-nav-link" href="{{ route('prices.index') }}" @if (request()->routeIs('prices.index')) aria-current="page" @endif><flux:icon.chart-bar class="size-5" /> Harga Pasar</a>
    <a class="ht-nav-link" href="{{ route('calculator.show') }}" @if (request()->routeIs('calculator.*')) aria-current="page" @endif><flux:icon.calculator class="size-5" /> Kalkulator Harga</a>
    <a class="ht-nav-link" href="{{ route('catalog.index') }}" @if (request()->routeIs('catalog.*')) aria-current="page" @endif><flux:icon.shopping-bag class="size-5" /> Hasil Panen</a>
</nav>

<div class="ht-sidebar-account">
    @auth
        <a class="ht-button ht-primary" href="{{ route('dashboard') }}">Dashboard <flux:icon.arrow-up-right class="size-4" /></a>
    @else
        <a class="ht-button" href="{{ route('login') }}">Masuk</a>
        <a class="ht-button ht-primary" href="{{ route('register') }}">Daftar sekarang <flux:icon.arrow-up-right class="size-4" /></a>
    @endauth
</div>
