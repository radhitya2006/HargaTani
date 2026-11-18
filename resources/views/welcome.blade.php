@php
    $harvests = [
        ['product' => 'Cabai Merah Segar', 'offer' => '42.000', 'stock' => 120, 'place' => 'Tabanan', 'farmer' => 'I Made Sujana', 'tone' => 'chili'],
        ['product' => 'Bawang Merah Pilihan', 'offer' => '30.000', 'stock' => 85, 'place' => 'Bangli', 'farmer' => 'Ni Wayan Sari', 'tone' => 'shallot'],
        ['product' => 'Tomat Segar Kebun', 'offer' => '10.000', 'stock' => 200, 'place' => 'Buleleng', 'farmer' => 'I Ketut Darma', 'tone' => 'tomato'],
        ['product' => 'Beras Medium Lokal', 'offer' => '12.500', 'stock' => 350, 'place' => 'Gianyar', 'farmer' => 'I Nyoman Adi', 'tone' => 'rice'],
    ];
    $accountUrl = auth()->check() ? route('dashboard') : route('register');
@endphp

<x-layouts::public title="Beranda">
    <section class="ht-wrap ht-hero" aria-labelledby="hero-title">
        <div>
            <p class="ht-tag"><span class="ht-dot"></span> Dari tanah Bali, untuk kita semua</p>
            <h1 id="hero-title">Kenali harganya.<br>Hargai <em>hasil panennya.</em></h1>
            <p class="ht-lead">Pantau harga pasar, tentukan harga jual yang lebih terukur, dan temukan hasil panen langsung dari petani lokal.</p>
            <div class="ht-actions">
                <a class="ht-button ht-primary" href="{{ route('prices.index') }}">Lihat harga pasar <span aria-hidden="true">→</span></a>
                <a class="ht-button" href="#hasil-panen"><flux:icon.shopping-bag class="size-4" /> Jelajahi hasil panen</a>
            </div>
            <div class="ht-benefits"><span>✓ &nbsp; Harga lebih transparan</span><span>Terhubung dengan petani</span></div>
        </div>
        <div class="ht-hero-art">
            <div class="ht-hero-placeholder">
                <div><small>Tabanan, Bali</small><p>Tumbuh dari tanah yang baik.</p></div>
            </div>
            <div class="ht-float ht-float-top"><span class="ht-icon"><flux:icon.user-circle /></span><div><strong>Dari petani, langsung.</strong><small>Lebih dekat dengan sumbernya</small></div></div>
            <div class="ht-float ht-float-bottom"><span class="ht-icon"><flux:icon.sun /></span><div><strong>Panen hari ini, peluang esok.</strong><small>Langkah kecil untuk pertanian yang lebih baik</small></div></div>
        </div>
    </section>
    <div class="ht-demo-bar"><div class="ht-wrap ht-between">
        <p><b>Demo</b> Sumber harga tercantum pada setiap kartu. Hasil panen yang ditawarkan adalah data contoh.</p>
        <a class="ht-link" href="{{ $accountUrl }}">Jelajahi HargaTani →</a>
    </div></div>

    <section class="ht-wrap ht-section" id="harga-pasar" tabindex="-1" aria-labelledby="market-title">
        <p class="ht-eyebrow">Informasi untuk langkah lebih pasti</p>
        <h2 id="market-title">Harga pasar, sekilas.</h2>
        <p class="ht-intro">Referensi harga komoditas untuk keputusan yang lebih terukur.</p>
        <div class="ht-grid ht-market-grid">
            @foreach ($marketCards as $slug => $item)
                @php
                    $change = $item['change'];
                @endphp
                <article class="ht-card ht-price ht-{{ $item['tone'] }}">
                    <h3>
                        <span class="ht-swatch" aria-hidden="true"></span>
                        <a class="ht-link" href="{{ route('prices.index', ['komoditas' => $slug]) }}">{{ $item['name'] }}</a>
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
        <div class="ht-meta"><small>Referensi harga eceran per kilogram</small></div>
    </section>

    <section class="ht-calc" id="kalkulator" aria-label="Kalkulator harga">
        <div class="ht-wrap ht-between">
            <div class="ht-calc-copy"><span class="ht-icon"><flux:icon.calculator /></span><div><h3>Panen sudah siap. Harga jualnya sudah tepat?</h3><p>Hitung biaya dan tentukan tambahan keuntungan per kilogram.</p></div></div>
            <a class="ht-button ht-primary" href="{{ route('calculator.show') }}">Buka kalkulator <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <section class="ht-wrap ht-section" id="hasil-panen" tabindex="-1" aria-labelledby="product-title">
        <p class="ht-eyebrow">Segar dari kebun lokal</p>
        <h2 id="product-title">Kenali hasil panen di sekitar Anda.</h2>
        <p class="ht-intro">Dari tangan yang menanam, untuk kebutuhan Anda.</p>
        <div class="ht-grid">
            @foreach ($harvests as $item)
                <article class="ht-card ht-product ht-{{ $item['tone'] }}">
                    <div class="ht-product-placeholder"><small>● &nbsp; Panen segar</small><small>Data contoh</small></div>
                    <div class="ht-product-info"><small>{{ $item['place'] }}, Bali</small>
                        <h3>{{ $item['product'] }}</h3>
                        <div class="ht-between"><p class="ht-offer">Rp{{ $item['offer'] }} <small>/kg</small></p><small>Stok {{ $item['stock'] }} kg</small></div>
                        <p class="ht-farmer"><flux:icon.user-circle class="size-4" /> {{ $item['farmer'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="cara-kerja" class="ht-wrap ht-steps" aria-labelledby="steps-title">
        <p class="ht-eyebrow">Sederhana, dari awal hingga panen</p>
        <h2 id="steps-title">Tiga langkah, lebih banyak kepastian.</h2>
        <ol>
            @foreach ([
                ['chart-bar', 'Kenali harga pasar', 'Lihat referensi harga dan pergerakan komoditas sebelum mengambil keputusan.'],
                ['calculator', 'Hitung harga yang sesuai', 'Pahami biaya dan tentukan tambahan keuntungan untuk hasil kerja Anda.'],
                ['shopping-bag', 'Temukan peluang panen', 'Kenali hasil panen dan petani lokal di sekitar Anda.'],
            ] as [$icon, $label, $description])
                <li class="ht-card"><div class="ht-between"><span class="ht-icon"><flux:icon :name="$icon" /></span><span aria-hidden="true" class="ht-index">0{{ $loop->iteration }}</span></div><h3>{{ $label }}</h3><p>{{ $description }}</p></li>
            @endforeach
        </ol>
    </section>

    <div class="ht-wrap ht-cta-wrap"><section class="ht-cta">
        <p class="ht-eyebrow">Tumbuh bersama HargaTani</p>
        <h2>Hasil kerja baik, layak dihargai dengan baik.</h2>
        <p>Mulai dari satu harga yang lebih terbuka, satu panen yang lebih berarti.</p>
        <a class="ht-button ht-gold" href="{{ $accountUrl }}">{{ auth()->check() ? 'Buka dashboard' : 'Mulai langkah Anda' }} ↗</a>
    </section></div>
</x-layouts::public>
