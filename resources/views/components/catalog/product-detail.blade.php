@props(['product', 'checkout' => false])

<div class="product-detail">
    <nav aria-label="Jejak navigasi" class="product-detail-breadcrumb">
        <a href="{{ route('catalog.index') }}" class="ht-link">Hasil Panen</a>
        <flux:icon.chevron-right class="size-4 shrink-0" />
        @if ($checkout)
            <a href="{{ route('catalog.show', $product) }}" class="ht-link wrap-anywhere">{{ $product->name }}</a>
            <flux:icon.chevron-right class="size-4 shrink-0" />
            <span aria-current="page">Pesan</span>
        @else
            <span aria-current="page" class="wrap-anywhere">{{ $product->name }}</span>
        @endif
    </nav>

    <article class="product-detail-grid">
        <img src="{{ route('catalog.photo', $product) }}" alt="Foto {{ $product->name }}" class="product-detail-photo">

        <div class="product-detail-purchase">
            <header>
                <p class="product-detail-badge">{{ \App\Support\PriceCommodities::ALL[$product->commodity]['name'] }}</p>
                <h1 class="product-detail-title">{{ $product->name }}</h1>
                <div class="product-detail-meta">
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.map-pin class="size-[1.1em] shrink-0" />
                        {{ $product->district }}, Bali
                    </span>
                    <span aria-hidden="true">·</span>
                    <time datetime="{{ $product->harvested_on->toDateString() }}" aria-label="Tanggal panen {{ $product->harvested_on->translatedFormat('j F Y') }}">
                        Panen {{ $product->harvested_on->translatedFormat('j F Y') }}
                    </time>
                </div>
                <p class="product-detail-price">
                    Rp{{ number_format($product->price_per_kg, 0, ',', '.') }}<small>/kg</small>
                </p>
            </header>

            <x-catalog.order-panel :$product :$checkout />
        </div>

        <section class="ht-card product-detail-description">
            <h2 class="text-lg font-bold sm:text-xl">Tentang hasil panen</h2>
            @if ($product->description_ai_generated)
                <p class="mt-2 w-fit rounded-full bg-primary-soft px-2 py-1 text-xs font-semibold text-primary">Dibuat oleh AI</p>
            @endif
            <p class="mt-2 leading-relaxed whitespace-pre-line text-[var(--muted)]">{{ $product->description }}</p>

            <dl class="mt-4 grid gap-3 border-t border-line pt-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-[var(--muted)]">Petani</dt>
                    <dd class="mt-1 font-semibold">{{ $product->farmer->name }}</dd>
                    @if ($product->farmer->district)
                        <dd class="mt-1 text-[var(--muted)]">{{ $product->farmer->district }}, Bali</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-[var(--muted)]">Lokasi pengambilan</dt>
                    <dd class="mt-1 font-semibold">{{ $product->pickup_location }}</dd>
                </div>
            </dl>
        </section>
    </article>
</div>
