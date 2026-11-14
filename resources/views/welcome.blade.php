<!DOCTYPE html>
<html lang="id"><head>@include('partials.head', ['title' => 'Selamat datang'])</head>
<body class="min-h-screen bg-emerald-50 text-zinc-900">
<main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center gap-6 px-6 py-16">
    <p class="font-semibold tracking-wide text-emerald-700">HARGATANI ? BALI</p>
    <h1 class="text-4xl font-bold leading-tight sm:text-6xl">Informasi harga untuk keputusan panen Anda.</h1>
    <p class="text-lg leading-relaxed text-zinc-600">Tempat petani dan pembeli terhubung langsung. Buat akun untuk mulai bergabung dengan HargaTani.</p>
    <div class="flex flex-wrap gap-3">
        @auth
        <a class="rounded-lg bg-emerald-800 px-6 py-3 text-white" href="{{ route('dashboard') }}">Buka dashboard</a>
        @else
        <a class="rounded-lg bg-emerald-800 px-6 py-3 text-white" href="{{ route('register') }}">Daftar akun</a>
        <a class="rounded-lg border border-emerald-800 px-6 py-3 text-emerald-900" href="{{ route('login') }}">Masuk</a>
        @endauth
    </div>
    <p class="text-sm text-zinc-500">Dashboard harga, etalase, dan pemesanan sedang dikembangkan.</p>
</main>@fluxScripts</body></html>
