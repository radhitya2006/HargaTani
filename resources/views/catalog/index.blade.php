<x-layouts::public title="Hasil Panen">
    <div class="ht-wrap market-page">
        <header class="market-title">
            <span class="ht-icon"><flux:icon.shopping-bag /></span>
            <div>
                <h1>Hasil Panen</h1>
                <p>Telusuri hasil panen yang ditawarkan petani di Bali.</p>
            </div>
        </header>

        <form method="GET" action="{{ route('catalog.index') }}" aria-label="Cari dan filter hasil panen"
            class="ht-card grid min-w-0 items-end gap-4 p-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
            <flux:input name="q" type="search" label="Nama produk" :value="$search" maxlength="120" placeholder="Contoh: bawang merah" />

            <flux:select name="komoditas" label="Komoditas">
                <option value="">Semua komoditas</option>
                @foreach ($commodities as $slug => $item)
                    <option value="{{ $slug }}" @selected($commodity === $slug)>{{ $item['name'] }}</option>
                @endforeach
            </flux:select>

            <flux:select name="lokasi" label="Kabupaten/kota">
                <option value="">Semua lokasi</option>
                @foreach ($districts as $option)
                    <option value="{{ $option }}" @selected($district === $option)>{{ $option }}</option>
                @endforeach
            </flux:select>

            <div class="flex flex-wrap gap-2">
                <button class="ht-button ht-primary cursor-pointer" type="submit">Cari</button>
                @if ($hasFilters)
                    <a class="ht-button" href="{{ route('catalog.index') }}">Reset filter</a>
                @endif
            </div>
        </form>

        <section aria-labelledby="catalog-results">
            @if ($products->isEmpty())
                <div class="ht-card market-history">
                    @if ($products->total() > 0)
                        <h2 id="catalog-results" class="mt-0!">Halaman ini kosong</h2>
                        <a class="ht-link mt-2 inline-block underline" href="{{ $products->url(1) }}">Kembali ke halaman pertama</a>
                    @elseif ($hasFilters)
                        <h2 id="catalog-results" class="mt-0!">Produk tidak ditemukan</h2>
                        <p class="ht-intro">Coba ubah nama produk, komoditas, atau lokasi.</p>
                    @else
                        <h2 id="catalog-results" class="mt-0!">Belum ada hasil panen</h2>
                    @endif
                </div>
            @else
                <h2 id="catalog-results" class="sr-only">Hasil pencarian</h2>
                <p class="text-sm text-ink-muted">{{ $products->total() }} produk</p>
                <div class="ht-grid ht-market-grid">
                    @foreach ($products as $product)
                        <x-catalog.product-card :$product />
                    @endforeach
                </div>
            @endif
            @if ($products->hasPages())
                <div class="mt-6">{{ $products->onEachSide(1)->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts::public>
