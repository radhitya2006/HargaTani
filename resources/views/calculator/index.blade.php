@php
    $formatPrice = fn (int $amount) => 'Rp'.number_format($amount, 0, ',', '.');
    $commodity = $inputs['komoditas'];
    $latest = $reference['latest'] ?? null;
    $fields = [
        'biaya_panen' => ['Total biaya satu panen (Rp)', 'Masukkan rupiah bulat tanpa pemisah ribuan. Contoh: 1500000.', 'numeric'],
        'jumlah_kg' => ['Jumlah panen layak jual (kg)', 'Gunakan hingga dua angka desimal. Contoh: 75,50.', 'decimal'],
        'tambahan_keuntungan' => ['Tambahan keuntungan (%)', 'Persentase tambahan terhadap biaya. Masukkan 0 jika tanpa tambahan.', 'decimal'],
    ];
@endphp

<x-layouts::public title="Kalkulator Harga Jual">
    <div class="ht-wrap market-page">
        <header class="market-header">
            <div class="market-title">
                <span class="ht-icon"><flux:icon.calculator /></span>
                <div>
                    <h1>Kalkulator Harga Jual</h1>
                    <p>Hitung biaya per kilogram dan bandingkan harga usulan dengan harga eceran pasar tradisional Bali.</p>
                </div>
            </div>
        </header>

        <div class="grid items-start gap-4 md:grid-cols-2">
            <form action="{{ route('calculator.calculate') }}" method="POST" class="ht-card market-history grid gap-4" aria-labelledby="calculator-form-title">
                @csrf
                <h2 id="calculator-form-title" class="text-lg font-bold">Rincian panen</h2>

                <div class="grid gap-1.5">
                    <label for="komoditas" class="text-sm font-semibold">Komoditas</label>
                    <select id="komoditas" name="komoditas" required
                        class="min-h-11 w-full rounded-control border border-control-line bg-white px-3"
                        @if ($validationErrors?->has('komoditas')) aria-invalid="true" aria-describedby="komoditas-error" @endif>
                        <option value="" disabled @selected(! isset($commodities[$commodity]))>Pilih komoditas</option>
                        @foreach ($commodities as $slug => $item)
                            <option value="{{ $slug }}" @selected($commodity === $slug)>{{ $item['name'] }}</option>
                        @endforeach
                    </select>
                    @if ($validationErrors?->has('komoditas'))
                        <p id="komoditas-error" class="text-sm text-danger">{{ $validationErrors->first('komoditas') }}</p>
                    @endif
                </div>

                @foreach ($fields as $name => [$label, $hint, $inputMode])
                    @php($hasError = $validationErrors?->has($name))
                    <div class="grid gap-1.5">
                        <label for="{{ $name }}" class="text-sm font-semibold">{{ $label }}</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="text" inputmode="{{ $inputMode }}" required
                            value="{{ $inputs[$name] ?? '' }}"
                            class="min-h-11 w-full rounded-control border border-control-line bg-white px-3"
                            aria-describedby="{{ $name }}-hint{{ $hasError ? ' '.$name.'-error' : '' }}"
                            @if ($hasError) aria-invalid="true" @endif>
                        <p id="{{ $name }}-hint" class="text-sm text-ink-muted">{{ $hint }}</p>
                        @if ($hasError)
                            <p id="{{ $name }}-error" class="text-sm text-danger">{{ $validationErrors->first($name) }}</p>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="ht-button ht-primary cursor-pointer">Hitung harga jual</button>
            </form>

            <section class="ht-card market-history grid gap-4" aria-labelledby="calculator-result-title">
                <h2 id="calculator-result-title" class="text-lg font-bold">Hasil perhitungan</h2>
                @if ($result === null)
                    <p class="text-sm text-ink-muted">Isi rincian panen untuk melihat biaya dan harga usulan per kilogram.</p>
                @else
                    <dl class="grid gap-3">
                        <div class="flex flex-wrap justify-between gap-2 text-sm">
                            <dt>Biaya per kg</dt>
                            <dd class="font-semibold tabular-nums">{{ $formatPrice($result['costPerKg']) }} /kg</dd>
                        </div>
                        <div class="rounded-control bg-primary-soft p-4">
                            <dt class="text-sm font-semibold">Harga usulan</dt>
                            <dd class="text-2xl font-bold break-words text-primary tabular-nums">{{ $formatPrice($result['suggestedPrice']) }} <small class="text-sm font-medium">/kg</small></dd>
                        </div>
                    </dl>

                    <div class="grid gap-2 border-t border-line pt-4">
                        <h3 class="text-sm font-semibold">Harga eceran pasar tradisional Bali</h3>
                        <p class="text-sm">{{ $commodities[$commodity]['name'] }}</p>
                        @if ($latest === null)
                            <p class="text-sm text-ink-muted">Belum ada harga referensi untuk komoditas ini.</p>
                        @else
                            @php($difference = $result['suggestedPrice'] - $latest['price'])
                            <p class="font-semibold tabular-nums">{{ $formatPrice($latest['price']) }} /kg</p>
                            <p class="text-sm text-ink-muted">
                                <time datetime="{{ $latest['date']->toDateString() }}">{{ $latest['date']->translatedFormat('j F Y') }}</time>
                            </p>
                            @if ($reference['isStale'])
                                <p class="text-sm text-warning">Data terakhir berusia lebih dari tujuh hari kalender.</p>
                            @endif
                            <p class="text-sm">
                                @if ($difference === 0)
                                    Harga usulan sama dengan harga referensi.
                                @else
                                    Harga usulan {{ $formatPrice(abs($difference)) }}/kg lebih {{ $difference > 0 ? 'tinggi' : 'rendah' }} dari harga referensi.
                                @endif
                            </p>
                        @endif
                        <a href="{{ $sourceUrl }}" class="ht-link justify-self-start text-sm underline">PIHPS Bank Indonesia</a>
                    </div>

                    <div class="grid gap-1.5 border-t border-line pt-4">
                        <label for="harga-pilihan" class="text-sm font-semibold">Harga jual pilihan Anda (Rp/kg)</label>
                        <input id="harga-pilihan" type="number" inputmode="numeric" min="0" step="1"
                            value="{{ $result['suggestedPrice'] }}"
                            class="min-h-11 w-full rounded-control border border-control-line bg-white px-3"
                            aria-describedby="harga-pilihan-hint">
                        <p id="harga-pilihan-hint" class="text-sm text-ink-muted">Sesuaikan harga dengan kebutuhan Anda. Isian ini tidak disimpan.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::public>
