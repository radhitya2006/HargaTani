<x-layouts::app title="Produk Saya">
    <div class="space-y-6">
        <x-ui.page-heading title="Produk Saya">
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" :href="route('petani.products.create')" wire:navigate>Tambah produk</flux:button>
            </x-slot:actions>
        </x-ui.page-heading>

        @if ($products->isEmpty())
            <x-ui.panel class="max-w-form">
                <h2 class="text-lg font-semibold">Belum ada produk</h2>
                <p class="mt-2 text-ink-muted">Tambahkan hasil panen pertama Anda dengan foto, harga per kg, dan lokasi pengambilan.</p>
            </x-ui.panel>
        @else
            <ul class="grid list-none gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($products as $product)
                    <li>
                        <article class="flex h-full flex-col overflow-hidden rounded-panel border border-line bg-surface shadow-xs">
                            <img src="{{ route('petani.products.photo', $product) }}" alt="" loading="lazy"
                                class="aspect-[16/10] w-full bg-background object-cover">

                            <div class="flex flex-1 flex-col gap-4 p-4 sm:p-5">
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm text-ink-muted">{{ $commodities[$product->commodity]['name'] }}</p>
                                        @include('products.status', ['product' => $product])
                                    </div>
                                    <h2 class="mt-2 text-lg font-semibold break-words">{{ $product->name }}</h2>
                                    <p class="mt-1 text-sm text-ink-muted">{{ $product->district }}</p>
                                </div>

                                <dl class="grid grid-cols-2 gap-3 border-t border-line pt-3 text-sm">
                                    <div>
                                        <dt class="text-ink-muted">Harga per kg</dt>
                                        <dd class="mt-1 font-semibold tabular-nums">Rp{{ number_format($product->price_per_kg, 0, ',', '.') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-ink-muted">Stok tersedia</dt>
                                        <dd class="mt-1 font-semibold tabular-nums">{{ str_replace('.', ',', $product->stock_kg) }} kg</dd>
                                    </div>
                                </dl>

                                <flux:button class="mt-auto w-fit" :href="route('petani.products.edit', $product)"
                                    :aria-label="'Kelola '.$product->name" wire:navigate>Kelola produk</flux:button>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            {{ $products->links() }}
        @endif
    </div>
</x-layouts::app>
