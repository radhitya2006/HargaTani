@props(['title', 'heading', 'description'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="ht-auth">
        <x-skip-link />

        <div class="ht-auth-story">
            <x-landing-brand />

            <div class="ht-auth-story-copy">
                <span class="ht-brand-icon ht-auth-emblem" aria-hidden="true"><i></i></span>
                <p class="ht-auth-tagline">Langkah baik,<br>dimulai dari sini.</p>
                <p class="ht-auth-story-description">
                    Kenali harga, temukan peluang, dan bangun hubungan yang lebih dekat dengan hasil panen lokal.
                </p>
            </div>
        </div>

        <main id="main-content" tabindex="-1" class="ht-auth-main">
            <a href="{{ route('home') }}" class="ht-auth-back">
                <flux:icon.arrow-left class="size-4" />
                Kembali ke beranda
            </a>

            <div class="ht-auth-content">
                <header class="ht-auth-heading">
                    <p>HargaTani · Akun Anda</p>
                    <h1>{{ $heading }}</h1>
                    <div>{{ $description }}</div>
                </header>

                <x-auth-session-status :status="session('status')" />

                {{ $slot }}

                <div class="ht-auth-switch">{{ $switch }}</div>
            </div>
        </main>

        @fluxScripts
    </body>
</html>
