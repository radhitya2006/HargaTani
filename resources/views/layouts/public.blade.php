<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="ht-landing">
    <x-skip-link />
    <header class="ht-header" id="beranda">
        <div class="ht-wrap ht-header-inner">
            <x-landing-brand />
            <nav aria-label="Navigasi utama" class="ht-nav">
                <a href="#beranda">Beranda</a>
                <a href="#harga-pasar">Harga Pasar</a>
                <a href="#hasil-panen">Hasil Panen</a>
            </nav>
            <div class="ht-header-actions">
                @auth
                    <a class="ht-button ht-primary" href="{{ route('dashboard') }}">Dashboard <span aria-hidden="true">↗</span></a>
                @else
                    <a href="{{ route('login') }}">Masuk</a>
                    <a class="ht-button ht-primary" href="{{ route('register') }}">Daftar sekarang <span aria-hidden="true">↗</span></a>
                @endauth
            </div>
        </div>
    </header>
    <main id="main-content" tabindex="-1">{{ $slot }}</main>
    <footer class="ht-footer">
        <div class="ht-wrap">
            <div class="ht-footer-grid">
                <div><x-landing-brand /><p>Harga lebih terbuka.<br>Panen lebih bermakna.</p></div>
                <nav aria-label="Jelajahi"><strong>Jelajahi</strong><a href="#harga-pasar">Harga Pasar</a><a href="#hasil-panen">Hasil Panen</a><a href="#cara-kerja">Cara kerja</a></nav>
                <nav aria-label="Akun"><strong>Akun</strong>
                    @auth
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                        <a href="{{ route('profile.edit') }}">Pengaturan</a>
                    @else
                        <a href="{{ route('login') }}">Masuk</a><a href="{{ route('register') }}">Daftar</a>
                    @endauth
                </nav>
                <aside class="ht-demo-note"><strong>Mode demonstrasi</strong><p>Harga, stok, dan hasil panen yang ditampilkan merupakan contoh.</p></aside>
            </div>
            <small class="ht-copyright">© {{ now()->year }} HargaTani. Tumbuh bersama, dari Bali.</small>
        </div>
    </footer>
    @fluxScripts
</body>
</html>
