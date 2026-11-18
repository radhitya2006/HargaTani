@php
    $name = $commodities[$commodity]['name'];
    $formatPrice = fn (int $amount) => 'Rp'.number_format($amount, 0, ',', '.');
    $pricesByDate = [];
    foreach ($observations as $observation) {
        $pricesByDate[$observation['date']->toDateString()] = $observation['price'];
    }
    $series = [];
    for ($date = $latest ? $latest['date']->subDays($days - 1) : null; $date !== null && $date <= $latest['date']; $date = $date->addDay()) {
        $series[] = ['date' => $date->translatedFormat('j M'), 'price' => $pricesByDate[$date->toDateString()] ?? null];
    }
@endphp

<x-layouts::public title="Harga Pasar">
    <div class="ht-wrap market-page">
        <header class="market-header">
            <div class="market-title">
                <span class="ht-icon"><flux:icon.chart-bar /></span>
                <div>
                    <h1>Harga Pasar</h1>
                    <p>Harga eceran pasar tradisional Bali</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="market-demo" href="{{ $sourceUrl }}"><span aria-hidden="true"></span> PIHPS Bank Indonesia</a>
                <a class="ht-button" href="{{ route('calculator.show', ['komoditas' => $commodity]) }}"><flux:icon.calculator class="size-4" /> Hitung harga jual</a>
            </div>
        </header>

        @if ($isStale)
            <p class="market-note" role="status">Data terakhir berusia lebih dari tujuh hari kalender.</p>
        @endif

        <form action="{{ route('prices.index') }}" method="GET" class="ht-card market-filters" aria-label="Filter harga pasar">
            <div>
                <label for="komoditas">Komoditas</label>
                <select id="komoditas" name="komoditas">
                    @foreach ($commodities as $slug => $item)
                        <option value="{{ $slug }}" @selected($commodity === $slug)>{{ $item['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="periode">Periode</label>
                <select id="periode" name="periode">
                    @foreach ($periods as $period)
                        <option value="{{ $period }}" @selected($days === $period)>{{ $period }} hari</option>
                    @endforeach
                </select>
            </div>
            <button class="ht-button ht-primary" type="submit">Terapkan filter</button>
        </form>

        @if ($latest === null)
            <section class="ht-card market-history" role="status">
                <h2>Belum ada harga untuk {{ $name }}</h2>
                <p>Harga akan tampil setelah pengamatan PIHPS tersedia. Anda dapat memilih komoditas lain.</p>
            </section>
        @else
        <dl class="market-summary" aria-label="Ringkasan harga {{ $name }}">
            <div class="ht-card">
                <dt>Harga terakhir</dt>
                <dd>{{ $formatPrice($latest['price']) }} <small>/kg</small></dd>
                <dd class="market-note"><time datetime="{{ $latest['date']->toDateString() }}">{{ $latest['date']->translatedFormat('j F Y') }}</time></dd>
            </div>
            <div class="ht-card">
                <dt>Perubahan harga</dt>
                <dd class="market-change {{ $change > 0 ? 'market-rise' : '' }}">
                    {{ $change === null ? 'Belum tersedia' : ($change === 0 ? 'Stabil' : ($change > 0 ? 'Naik ' : 'Turun ').$formatPrice(abs($change))) }}
                </dd>
                @if ($previous)
                    <dd class="market-note">Dibanding {{ $previous['date']->translatedFormat('j F Y') }} ({{ $formatPrice($previous['price']) }}/kg)</dd>
                @else
                    <dd class="market-note">Belum ada pengamatan pembanding pada periode ini.</dd>
                @endif
            </div>
            <div class="ht-card">
                <dt>Rentang harga {{ $days }} hari</dt>
                <dd>{{ $formatPrice($minimum) }}–{{ $formatPrice($maximum) }}</dd>
                <dd class="market-note">Terendah hingga tertinggi per kg</dd>
            </div>
        </dl>

        <section class="ht-card market-history" aria-labelledby="market-history-title">
            <header class="market-history-heading">
                <div>
                    <h2 id="market-history-title">Tren harga {{ $name }}</h2>
                    <p>{{ $latest['date']->subDays($days - 1)->translatedFormat('j F Y') }} – {{ $latest['date']->translatedFormat('j F Y') }}</p>
                </div>
                <span class="market-unit">Rp/kg</span>
            </header>

            <div class="market-chart">
                <canvas data-market-chart='@json($series)' role="img" aria-labelledby="market-history-title" aria-describedby="market-history-source">
                    Harga per tanggal tersedia pada tabel riwayat di bawah.
                </canvas>
                <p id="market-chart-fallback" hidden>Grafik tidak dapat dimuat. Buka tabel riwayat untuk melihat harga per tanggal.</p>
                <noscript><p>Aktifkan JavaScript untuk menampilkan grafik. Harga per tanggal tersedia pada tabel riwayat.</p></noscript>
            </div>
            <p id="market-history-source" class="market-note">
                Sumber <a href="{{ $sourceUrl }}" class="ht-link">PIHPS Bank Indonesia</a> · {{ count($observations) }} pengamatan. Jeda grafik menunjukkan tanggal tanpa pengamatan.
            </p>

            <details class="market-table">
                <summary>Lihat tabel riwayat harga</summary>
                <div class="market-table-scroll">
                    <table aria-label="Riwayat harga {{ $name }}">
                        <thead><tr><th scope="col">Tanggal pengamatan</th><th scope="col">Harga per kg</th></tr></thead>
                        <tbody>
                            @foreach (array_reverse($observations) as $observation)
                                <tr>
                                    <td><time datetime="{{ $observation['date']->toDateString() }}">{{ $observation['date']->translatedFormat('j F Y') }}</time></td>
                                    <td>{{ $formatPrice($observation['price']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
        @endif
    </div>
</x-layouts::public>
