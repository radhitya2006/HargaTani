<x-layouts::app title="Ringkasan">
    <div class="space-y-8">
        <x-ui.page-heading title="Ringkasan Admin" :description="'Halo, '.auth()->user()->name.'.'">
            <x-slot:actions>
                <flux:button variant="primary" icon="chart-bar" :href="route('prices.index')">Lihat Harga Pasar</flux:button>
            </x-slot:actions>
        </x-ui.page-heading>

        <x-dashboard.metrics :metrics="$metrics" aria-label="Jumlah pengguna aktif dan produk tayang" />

        <x-ui.panel>
            <section aria-labelledby="dashboard-sync-heading">
                <h2 id="dashboard-sync-heading" class="text-xl font-semibold">Sinkronisasi PIHPS</h2>
                @if ($lastSuccessfulSync)
                    @php
                        $lastSuccessfulSyncAt = \Carbon\CarbonImmutable::parse($lastSuccessfulSync)->locale('id');
                    @endphp
                    <p class="mt-2 text-sm text-ink-muted">
                        Sinkronisasi terakhir yang berhasil pada
                        <time datetime="{{ $lastSuccessfulSyncAt->toIso8601String() }}">{{ $lastSuccessfulSyncAt->translatedFormat('j F Y, H.i') }} WITA</time>.
                    </p>
                @else
                    <p class="mt-2 text-sm text-ink-muted">Belum ada sinkronisasi yang berhasil.</p>
                @endif

                @if ($syncRuns->isEmpty())
                    <p class="mt-4 border-t border-line pt-4 text-ink-muted">Belum ada riwayat sinkronisasi.</p>
                @else
                    <h3 class="mt-5 text-sm font-semibold">Proses terbaru</h3>
                    <ul class="mt-2 divide-y divide-line border-t border-line">
                        @foreach ($syncRuns as $run)
                            @php
                                $startedAt = \Carbon\CarbonImmutable::parse($run->started_at)->locale('id');
                                $statusLabel = match ($run->status) {
                                    'success' => 'Berhasil',
                                    'failed' => 'Gagal',
                                    'running' => 'Sedang berjalan',
                                    default => 'Status tidak diketahui',
                                };
                            @endphp
                            <li class="grid gap-2 py-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <time class="text-sm font-medium" datetime="{{ $startedAt->toIso8601String() }}">{{ $startedAt->translatedFormat('j F Y, H.i') }} WITA</time>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-primary-soft text-success' => $run->status === 'success',
                                        'bg-warning-soft text-danger' => $run->status === 'failed',
                                        'bg-background text-ink-muted' => ! in_array($run->status, ['success', 'failed'], true),
                                    ])>{{ $statusLabel }}</span>
                                </div>
                                <dl class="flex flex-wrap gap-x-4 gap-y-1 text-sm tabular-nums">
                                    <div class="flex gap-1">
                                        <dt class="text-ink-muted">Harga baru</dt>
                                        <dd class="font-semibold">{{ $run->inserted }}</dd>
                                    </div>
                                    <div class="flex gap-1">
                                        <dt class="text-ink-muted">Direvisi</dt>
                                        <dd class="font-semibold">{{ $run->updated }}</dd>
                                    </div>
                                </dl>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </x-ui.panel>
    </div>
</x-layouts::app>
