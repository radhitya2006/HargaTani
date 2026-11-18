<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="ht-landing" id="beranda">
    <x-skip-link />
    <aside class="ht-sidebar ht-sidebar-desktop" aria-label="Menu HargaTani">
        <x-landing-brand />
        <x-landing-navigation />
    </aside>

    <header class="ht-mobile-header">
        <x-landing-brand />
        <button type="button" class="ht-button" id="ht-menu-trigger" aria-haspopup="dialog" aria-controls="ht-menu" aria-expanded="false">
            <flux:icon.bars-3 class="size-5" />
            Menu
        </button>
    </header>

    <dialog class="ht-sidebar ht-menu-dialog" id="ht-menu" aria-label="Menu HargaTani">
        <div class="ht-menu-heading">
            <x-landing-brand />
            <form method="dialog">
                <button type="submit" class="ht-menu-close" aria-label="Tutup menu" autofocus>
                    <flux:icon.x-mark class="size-5" />
                </button>
            </form>
        </div>
        <x-landing-navigation />
    </dialog>

    <div class="ht-landing-content">
        <main id="main-content" tabindex="-1">{{ $slot }}</main>
        <footer class="ht-footer">
            <div class="ht-wrap">
                <div class="ht-footer-grid">
                    <div><x-landing-brand /><p>Harga lebih terbuka.<br>Panen lebih bermakna.</p></div>
                    <nav aria-label="Jelajahi">
                        <strong>Jelajahi</strong>
                        <a href="{{ route('prices.index') }}">Harga Pasar</a>
                        <a href="{{ route('home') }}#hasil-panen">Hasil Panen</a>
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
                    <aside class="ht-demo-note"><strong>Mode demonstrasi</strong><p>Harga pasar bersumber dari PIHPS. Penawaran hasil panen dan stok menggunakan data contoh.</p></aside>
                </div>
                <small class="ht-copyright">© {{ now()->year }} HargaTani. Tumbuh bersama, dari Bali.</small>
            </div>
        </footer>
    </div>
    @fluxScripts
</body>
</html>
