@php($title = $isFarmer ? 'Pesanan Masuk' : 'Pesanan Saya')

<x-layouts::app :title="$title">
    <div class="space-y-6">
        <x-ui.page-heading :title="$title">
            @unless ($isFarmer)
                <x-slot:actions>
                    <flux:button icon="shopping-bag" :href="route('catalog.index')">Jelajahi Hasil Panen</flux:button>
                </x-slot:actions>
            @endunless
        </x-ui.page-heading>

        @if ($orders->isEmpty())
            <x-ui.panel class="max-w-form">
                @if ($orders->total() > 0)
                    <p class="text-ink-muted">Halaman ini kosong.</p>
                    <flux:button class="mt-3" :href="route('orders.index')">Kembali ke halaman pertama</flux:button>
                @else
                    <p class="text-ink-muted">{{ $isFarmer ? 'Belum ada pesanan masuk.' : 'Anda belum membuat pesanan.' }}</p>
                @endif
            </x-ui.panel>
        @else
            <ul class="grid list-none gap-4 md:grid-cols-2">
                @foreach ($orders as $order)
                    <li>
                        <x-ui.panel class="flex h-full flex-col gap-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-ink-muted">Pesanan #{{ $order->id }}</p>
                                @include('orders.status', ['status' => $order->status])
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-lg font-semibold break-words">{{ $order->product->name }}</h2>
                                <p class="break-words text-ink-muted">{{ ($isFarmer ? $order->buyer?->name : $order->farmer?->name) ?? 'Akun dihapus' }}</p>
                                <p class="text-sm font-semibold tabular-nums">
                                    {{ str_replace('.', ',', $order->quantity_kg) }} kg · {{ $order->formattedTotal() }}
                                </p>
                            </div>
                            <flux:button class="mt-auto w-fit" :href="route('orders.show', $order)"
                                :aria-label="'Lihat pesanan #'.$order->id">Lihat pesanan</flux:button>
                        </x-ui.panel>
                    </li>
                @endforeach
            </ul>

            {{ $orders->links() }}
        @endif
    </div>
</x-layouts::app>
