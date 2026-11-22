<x-layouts::public :title="$product->name">
    <div class="ht-wrap market-page">
        <a href="{{ route('catalog.index') }}" class="ht-link inline-flex min-h-11 w-fit items-center gap-2 text-sm">
            <flux:icon.arrow-left class="size-4" /> Hasil Panen
        </a>

        <article class="ht-card grid min-w-0 items-start gap-6 p-4 sm:p-6 lg:grid-cols-2 lg:gap-8">
            <img src="{{ route('catalog.photo', $product) }}" alt="Foto {{ $product->name }}"
                class="aspect-[3/2] w-full rounded-control bg-primary-soft object-cover">

            <div class="min-w-0 space-y-5 break-words">
                <header class="market-title">
                    <div>
                        <p class="mb-1">{{ $commodityName }}</p>
                        <h1>{{ $product->name }}</h1>
                    </div>
                </header>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <p class="text-2xl font-bold break-all text-primary tabular-nums">Rp{{ number_format($product->price_per_kg, 0, ',', '.') }} <small class="text-sm font-medium text-ink-muted">/kg</small></p>
                    @if ($product->isSoldOut())
                        <span class="rounded-full bg-warning-soft px-3 py-1.5 text-sm font-semibold text-warning">Stok habis</span>
                    @else
                        <span class="rounded-full bg-primary-soft px-3 py-1.5 text-sm font-semibold text-success">
                            Tersedia {{ str_replace('.', ',', $product->stock_kg) }} kg
                        </span>
                    @endif
                </div>

                <p class="text-sm leading-relaxed whitespace-pre-line text-ink">{{ $product->description }}</p>

                <dl class="grid gap-4 border-t border-line pt-5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-ink-muted">Petani</dt>
                        <dd class="mt-1 font-semibold">{{ $product->farmer->name }}</dd>
                        @if ($product->farmer->district)
                            <dd class="mt-1 text-ink-muted">{{ $product->farmer->district }}, Bali</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-ink-muted">Tanggal panen</dt>
                        <dd class="mt-1 font-semibold"><time datetime="{{ $product->harvested_on->toDateString() }}">{{ $product->harvested_on->translatedFormat('j F Y') }}</time></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-ink-muted">Lokasi pengambilan</dt>
                        <dd class="mt-1 font-semibold">{{ $product->pickup_location }}</dd>
                        <dd class="mt-1 text-ink-muted">{{ $product->district }}, Bali</dd>
                    </div>
                </dl>

                @unless ($product->isSoldOut())
                    @if (auth()->guest() || auth()->user()?->role === \App\Enums\Role::Pembeli)
                        <a class="ht-button ht-primary" href="{{ route('orders.create', $product) }}">Pesan</a>
                    @endif
                @endunless
            </div>
        </article>
    </div>
</x-layouts::public>
