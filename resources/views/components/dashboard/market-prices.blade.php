@props(['cards'])

<section aria-labelledby="dashboard-market-heading">
    <h2 id="dashboard-market-heading" class="text-xl font-semibold">Harga Pasar Bali</h2>
    <p class="mt-1 text-sm text-ink-muted">Harga eceran pasar tradisional dari PIHPS Bank Indonesia.</p>

    <div class="mt-4 grid gap-4 md:grid-cols-3">
        @foreach ($cards as $slug => $card)
            <x-ui.panel class="flex min-w-0 flex-col">
                <h3 class="font-semibold">{{ $card['name'] }}</h3>
                @if ($card['latest'])
                    <p class="mt-3 text-2xl font-bold tracking-tight text-primary tabular-nums">
                        Rp{{ number_format($card['latest']['price'], 0, ',', '.') }}<span class="ml-1 text-sm font-normal text-ink-muted">/kg</span>
                    </p>
                    <p class="mt-1 text-sm text-ink-muted">
                        <time datetime="{{ $card['latest']['date']->toDateString() }}">{{ $card['latest']['date']->translatedFormat('j F Y') }}</time>
                    </p>
                    @if ($card['change'] === null)
                        <p class="mt-3 text-sm text-ink-muted">Belum ada pengamatan pembanding.</p>
                    @elseif ($card['change'] === 0)
                        <p class="mt-3 text-sm text-ink-muted">Tetap dibanding pengamatan sebelumnya.</p>
                    @else
                        <p class="mt-3 text-sm text-ink-muted">
                            <span class="font-semibold text-ink">{{ $card['change'] > 0 ? 'Naik' : 'Turun' }} Rp{{ number_format(abs($card['change']), 0, ',', '.') }}</span>
                            dari pengamatan sebelumnya.
                        </p>
                    @endif
                    @if ($card['isStale'])
                        <p class="mt-2 text-sm text-warning">Data terakhir berusia lebih dari tujuh hari.</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-ink-muted">Belum ada harga dari PIHPS.</p>
                @endif

                <div class="mt-auto pt-4">
                    <flux:button size="sm" :href="route('prices.index', ['komoditas' => $slug])" :aria-label="'Lihat grafik '.$card['name']">
                        Lihat grafik
                    </flux:button>
                </div>
            </x-ui.panel>
        @endforeach
    </div>
</section>
