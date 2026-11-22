<x-layouts::app :title="'Dashboard '.$role->label()">
    <div class="space-y-8">
        <x-ui.page-heading :title="'Dashboard '.$role->label()" :description="'Selamat datang, '.auth()->user()->name.'.'">
            <x-slot:actions>
                @if ($role === \App\Enums\Role::Petani)
                    <flux:button variant="primary" icon="plus" :href="route('petani.products.create')" wire:navigate>Tambah Produk</flux:button>
                    <flux:button :href="route('petani.products.index')" wire:navigate>Kelola Produk</flux:button>
                    <flux:button :href="route('calculator.show')">Kalkulator Harga Jual</flux:button>
                @else
                    <flux:button variant="primary" icon="shopping-bag" :href="route('catalog.index')">Jelajahi Hasil Panen</flux:button>
                @endif
                <flux:button :href="route('orders.index')" wire:navigate>
                    {{ $role === \App\Enums\Role::Petani ? 'Pesanan Masuk' : 'Pesanan Saya' }}
                </flux:button>
            </x-slot:actions>
        </x-ui.page-heading>

        @if ($role === \App\Enums\Role::Petani)
            <x-dashboard.metrics :metrics="$metrics" aria-label="Jumlah produk saya" />

            <x-ui.panel>
                <section aria-labelledby="recent-products-heading">
                    <h2 id="recent-products-heading" class="text-xl font-semibold">Produk terakhir diperbarui</h2>
                    @if ($products->isEmpty())
                        <p class="mt-3 text-ink-muted">Belum ada produk aktif atau draf. Gunakan Tambah Produk untuk membuat penawaran hasil panen.</p>
                    @else
                        <ul class="mt-4 divide-y divide-line border-t border-line">
                            @foreach ($products as $product)
                                <li class="grid gap-3 py-3 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold break-words">{{ $product->name }}</h3>
                                        <p class="mt-1 text-sm text-ink-muted">Stok {{ str_replace('.', ',', $product->stock_kg) }} kg</p>
                                    </div>
                                    <div>@include('products.status', ['product' => $product])</div>
                                    <flux:button class="w-fit" size="sm" :href="route('petani.products.edit', $product)"
                                        :aria-label="'Edit '.$product->name" wire:navigate>Edit produk</flux:button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </x-ui.panel>
        @else
            <section aria-labelledby="recent-harvest-heading">
                <h2 id="recent-harvest-heading" class="text-xl font-semibold">Hasil Panen Terbaru</h2>
                @if ($products->isEmpty())
                    <x-ui.panel class="mt-4">
                        <p class="text-ink-muted">Belum ada hasil panen dengan stok tersedia. Buka etalase untuk melihat penawaran yang sudah tayang.</p>
                    </x-ui.panel>
                @else
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        @foreach ($products as $product)
                            <x-catalog.product-card :product="$product" />
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <x-dashboard.market-prices :cards="$marketCards" />
    </div>
</x-layouts::app>
