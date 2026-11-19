<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="ht-landing" id="beranda">
    <x-skip-link />
    <header class="ht-site-header">
        <x-sidebar-menu>
            <x-slot:brand>
                <x-landing-brand />
            </x-slot:brand>
            <x-landing-navigation />
        </x-sidebar-menu>
        <x-theme-toggle compact class="ms-auto" />
        <x-account-menu show-dashboard />
    </header>

    <div class="ht-landing-content">
        <main id="main-content" tabindex="-1">{{ $slot }}</main>
        <footer class="ht-footer">
            <div class="ht-wrap">
                <div class="ht-footer-grid">
                    <div><x-landing-brand /><p>Harga lebih terbuka.<br>Panen lebih bermakna.</p></div>
                    <nav aria-label="Jelajahi">
                        <strong>Jelajahi</strong>
                        <a href="{{ route('prices.index') }}">Harga Pasar</a>
                        <a href="{{ route('catalog.index') }}">Hasil Panen</a>
                        <a href="{{ route('home') }}#cara-kerja">Cara kerja</a>
                    </nav>
                    <nav aria-label="Akun"><strong>Akun</strong>
                        @auth
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                            <a href="{{ route('profile.edit') }}">Pengaturan</a>
                        @else
                            <a href="{{ route('login') }}">Masuk</a><a href="{{ route('register') }}">Daftar</a>
                        @endauth
                    </nav>
                    <aside class="ht-demo-note"><strong>Sumber data</strong><p>Harga pasar bersumber dari PIHPS. Harga jual dan stok diisi oleh petani.</p></aside>
                </div>
                <small class="ht-copyright">© {{ now()->year }} HargaTani. Tumbuh bersama, dari Bali.</small>
            </div>
        </footer>
    </div>
    @fluxScripts
</body>
</html>
