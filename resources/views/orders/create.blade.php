<x-layouts::app title="Pesan hasil panen">
    <div class="max-w-form space-y-5">
        <flux:button variant="ghost" icon="arrow-left" :href="route('catalog.show', $product)">Kembali ke produk</flux:button>
        <x-ui.page-heading title="Pesan hasil panen" />

        <x-ui.panel>
            <h2 class="text-xl font-semibold break-words">{{ $product->name }}</h2>
            <p class="mt-1 break-words text-ink-muted">{{ $product->farmer->name }}</p>
            <p class="mt-3 font-semibold tabular-nums">Rp{{ number_format($product->price_per_kg, 0, ',', '.') }}/kg</p>
            <p class="mt-1 text-sm text-ink-muted">Stok tersedia {{ str_replace('.', ',', $product->stock_kg) }} kg</p>
        </x-ui.panel>

        @if ($product->isSoldOut())
            <p role="status" class="rounded-control bg-warning-soft p-4 text-warning">Stok habis. Produk ini belum bisa dipesan.</p>
        @else
            <x-ui.panel>
                <form method="POST" action="{{ route('orders.store', $product) }}" class="space-y-5" data-order-form>
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ $token }}">
                    <input type="hidden" name="price_per_kg" value="{{ $product->price_per_kg }}">

                    @if ($errors->hasAny(['order', 'submission_token', 'price_per_kg']))
                        <p role="alert" class="rounded-control bg-warning-soft p-3 text-sm text-warning">
                            {{ $errors->first(['order', 'submission_token', 'price_per_kg']) }}
                        </p>
                    @endif
                    <p data-order-error role="alert" class="rounded-control bg-warning-soft p-3 text-sm text-warning" hidden></p>

                    <flux:input name="quantity_kg" label="Jumlah pesanan (kg)" type="text" inputmode="decimal"
                        maxlength="11" required placeholder="Contoh 2,50"
                        :value="is_scalar(old('quantity_kg')) ? (string) old('quantity_kg') : ''" />
                    <flux:textarea name="notes" label="Catatan untuk petani (opsional)" rows="3" maxlength="500">{{ is_string(old('notes')) ? old('notes') : '' }}</flux:textarea>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                        <p class="font-semibold">Total pesanan</p>
                        <output class="text-lg font-bold tabular-nums" data-order-total aria-live="polite">—</output>
                    </div>
                    <p class="text-sm text-ink-muted">Pembayaran dan pengambilan disepakati langsung setelah petani menerima pesanan.</p>
                    <flux:button variant="primary" type="submit">Kirim pesanan</flux:button>
                </form>
            </x-ui.panel>
        @endif
    </div>
</x-layouts::app>
