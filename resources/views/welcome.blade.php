@use('Illuminate\Support\Facades\Gate')

@php
    $accountUrl = auth()->check() ? route('dashboard') : route('register');
    $canCalculatePrice = Gate::allows('use-selling-price-calculator');
    $marketStep = ['chart-bar', 'Kenali harga pasar', 'Lihat referensi harga dan pergerakan komoditas sebelum mengambil keputusan.'];
    $catalogStep = ['shopping-bag', 'Temukan peluang panen', 'Kenali hasil panen dan petani lokal di sekitar Anda.'];
    $steps = $canCalculatePrice
        ? [$marketStep, ['calculator', 'Hitung harga yang sesuai', 'Pahami biaya dan tentukan tambahan keuntungan untuk hasil kerja Anda.'], $catalogStep]
        : [$marketStep, $catalogStep, ['magnifying-glass', 'Periksa detail produk', 'Lihat harga per kg, stok, dan lokasi pengambilan.']];
@endphp

<x-layouts::public title="Beranda">
    <section class="ht-wrap ht-hero" aria-labelledby="hero-title">
        <div>
            <p class="ht-tag"><span class="ht-dot"></span> Dari tanah Bali, untuk kita semua</p>
            <h1 id="hero-title">Kenali harganya.<br>Hargai <em>hasil panennya.</em></h1>
            <p class="ht-lead">{{ $canCalculatePrice
                ? 'Pantau harga pasar, tentukan harga jual yang lebih terukur, dan temukan hasil panen langsung dari petani lokal.'
                : 'Bandingkan harga pasar dan temukan hasil panen langsung dari petani lokal.' }}</p>
            <div class="ht-actions">
                <a class="ht-button ht-primary" href="{{ route('prices.index') }}">Lihat harga pasar <span aria-hidden="true">→</span></a>
                <a class="ht-button" href="{{ route('catalog.index') }}"><flux:icon.shopping-bag class="size-4" /> Jelajahi hasil panen</a>
            </div>
            <div class="ht-benefits"><span>✓ &nbsp; Harga lebih transparan</span><span>Terhubung dengan petani</span></div>
        </div>
        <div class="ht-hero-art">
            <div class="ht-hero-photo">
                <img src="{{ asset('images/beranda-petani.webp') }}" alt="Petani tersenyum sambil memegang bulir padi di sawah" width="1448" height="1086" fetchpriority="high">
            </div>
            <a class="ht-hero-card ht-hero-card--farmer" href="{{ route('catalog.index') }}">
                <span class="ht-icon" aria-hidden="true"><flux:icon.user-circle /></span>
                <span class="ht-hero-card-copy">
                    <strong>Dari petani, langsung.</strong>
                    <small>Jelajahi hasil panen petani</small>
                </span>
                <span class="ht-hero-card-arrow" aria-hidden="true"><flux:icon.arrow-up-right /></span>
            </a>
            <a class="ht-hero-card ht-hero-card--location" href="{{ route('catalog.index', ['lokasi' => 'Tabanan']) }}">
                <span class="ht-hero-card-copy">
                    <small>Lihat panen di Tabanan, Bali</small>
                    <strong>Tumbuh dari tanah yang baik.</strong>
                </span>
                <span class="ht-hero-card-arrow" aria-hidden="true"><flux:icon.arrow-up-right /></span>
            </a>
            <a class="ht-hero-card ht-hero-card--market" href="{{ route('prices.index') }}">
                <span class="ht-icon" aria-hidden="true"><flux:icon.sun /></span>
                <span class="ht-hero-card-copy">
                    <strong>Panen hari ini, peluang esok.</strong>
                    <small>Bandingkan harga pasar</small>
                </span>
                <span class="ht-hero-card-arrow" aria-hidden="true"><flux:icon.arrow-up-right /></span>
            </a>
        </div>
    </section>
    <div class="ht-demo-bar"><div class="ht-wrap ht-between">
        <p><b>Sumber</b> Harga pasar dari PIHPS. Harga jual dan stok diisi oleh petani.</p>
        <a class="ht-link" href="{{ $accountUrl }}">Jelajahi HargaTani →</a>
    </div></div>

    <section class="ht-wrap ht-section" id="harga-pasar" tabindex="-1" aria-labelledby="market-title">
        <header data-scroll-reveal>
            <p class="ht-eyebrow">Informasi untuk langkah lebih pasti</p>
            <h2 id="market-title">Harga pasar, sekilas.</h2>
            <p class="ht-intro">Referensi harga komoditas untuk keputusan yang lebih terukur.</p>
        </header>
        <div class="ht-grid ht-market-grid ht-home-cards">
            @foreach ($marketCards as $slug => $item)
                @php
                    $change = $item['change'];
                @endphp
                <article class="ht-card ht-price ht-{{ $item['tone'] }}" data-scroll-reveal>
                    <h3>
                        <a class="ht-link ht-price-link" href="{{ route('prices.index', ['komoditas' => $slug]) }}">
                            <img class="ht-price-photo" src="{{ asset('images/komoditas/'.$slug.'.webp') }}"
                                width="44" height="44" loading="lazy" decoding="async" alt="">
                            <span>{{ $item['name'] }}</span>
                        </a>
                    </h3>
                    @if ($item['latest'])
                    <p class="ht-amount">Rp{{ number_format($item['latest']['price'], 0, ',', '.') }}<small>/kg</small></p>
                    <div class="ht-price-foot">
                        @if ($change === null)
                            <span>Belum ada pembanding</span>
                        @else
                            <span><strong>{{ $change === 0 ? 'Stabil' : ($change > 0 ? '+' : '−').'Rp'.number_format(abs($change), 0, ',', '.') }}</strong> dari pengamatan sebelumnya</span>
                        @endif
                    </div>
                    <p class="ht-meta">PIHPS Bank Indonesia · <time datetime="{{ $item['latest']['date']->toDateString() }}">{{ $item['latest']['date']->translatedFormat('j F Y') }}</time></p>
                    @if ($item['isStale'])
                        <p class="market-note">Data terakhir berusia lebih dari tujuh hari.</p>
                    @endif
                    @else
                        <p>Belum ada harga dari PIHPS Bank Indonesia.</p>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="ht-meta" data-scroll-reveal><small>Referensi harga eceran per kilogram</small></div>
        @include('home.market-chat')
    </section>

    @if ($canCalculatePrice)
        <section class="ht-calc" id="kalkulator" aria-label="Kalkulator harga">
            <div class="ht-wrap ht-between" data-scroll-reveal>
                <div class="ht-calc-copy"><span class="ht-icon"><flux:icon.calculator /></span><div><h3>Panen sudah siap. Harga jualnya sudah tepat?</h3><p>Hitung biaya dan tentukan tambahan keuntungan per kilogram.</p></div></div>
                <a class="ht-button ht-primary" href="{{ route('calculator.show') }}">Buka kalkulator <span aria-hidden="true">→</span></a>
            </div>
        </section>
    @endif

    <section class="ht-wrap ht-section" id="hasil-panen" tabindex="-1" aria-labelledby="product-title" data-scroll-reveal>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="product-title">Hasil Panen Terbaru</h2>
            <a class="ht-button" href="{{ route('catalog.index') }}">Lihat semua hasil panen <flux:icon.arrow-right class="size-4" /></a>
        </div>
        @if ($featuredProducts->isEmpty())
            <p class="ht-intro">Belum ada hasil panen yang diterbitkan.</p>
        @else
            <div class="ht-grid ht-market-grid ht-home-cards">
                @foreach ($featuredProducts as $product)
                    <x-catalog.product-card :$product />
                @endforeach
            </div>
        @endif
    </section>

    <section id="cara-kerja" class="ht-wrap ht-steps" aria-labelledby="steps-title" data-scroll-reveal>
        <p class="ht-eyebrow">Sederhana, dari awal hingga panen</p>
        <h2 id="steps-title">Tiga langkah, lebih banyak kepastian.</h2>
        <ol class="ht-home-cards">
            @foreach ($steps as [$icon, $label, $description])
                <li class="ht-card"><div class="ht-between"><span class="ht-icon"><flux:icon :name="$icon" /></span><span aria-hidden="true" class="ht-index">0{{ $loop->iteration }}</span></div><h3>{{ $label }}</h3><p>{{ $description }}</p></li>
            @endforeach
        </ol>
    </section>

    <div class="ht-wrap ht-cta-wrap"><section class="ht-cta" data-scroll-reveal>
        <p class="ht-eyebrow">Tumbuh bersama HargaTani</p>
        <h2>Hasil kerja baik, layak dihargai dengan baik.</h2>
        <p>Mulai dari satu harga yang lebih terbuka, satu panen yang lebih berarti.</p>
        <a class="ht-button ht-gold" href="{{ $accountUrl }}">{{ auth()->check() ? 'Buka dashboard' : 'Mulai langkah Anda' }} ↗</a>
    </section></div>
</x-layouts::public>
