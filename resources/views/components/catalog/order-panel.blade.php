@props(['product', 'checkout'])

@php
    $canOrder = Gate::allows('create', \App\Models\Order::class);
    $isGuest = auth()->guest();
    $stock = \Brick\Math\BigDecimal::of($product->stock_kg);
@endphp

<section class="ht-card product-order-panel" aria-label="Pemesanan hasil panen">
    @if ($product->isSoldOut())
        <p role="status" class="rounded-control bg-warning-soft p-4 text-warning">Stok habis. Produk ini belum bisa dipesan.</p>
    @else
        <p class="text-[var(--muted)]">
            Stok tersedia <strong class="font-bold text-[var(--forest)]">{{ str_replace('.', ',', (string) $stock->strippedOfTrailingZeros()) }} kg</strong>
        </p>

        @if ($canOrder || $isGuest)
            @php
                $defaultQuantity = $stock->isLessThan('1') ? (string) $stock->strippedOfTrailingZeros() : '1';
                $requestedQuantity = request()->query('quantity_kg');
                $normalized = is_string($requestedQuantity) ? str_replace(',', '.', trim($requestedQuantity)) : '';
                if ($checkout && preg_match(\App\Models\Product::WEIGHT_PATTERN, $normalized)) {
                    $requestedWeight = \Brick\Math\BigDecimal::of($normalized);
                    if ($requestedWeight->isPositive() && $requestedWeight->isLessThanOrEqualTo($stock)) {
                        $defaultQuantity = (string) $requestedWeight->strippedOfTrailingZeros();
                    }
                }

                $quantity = old('quantity_kg', $defaultQuantity);
                $quantity = is_scalar($quantity) ? (string) $quantity : '';
                $weight = str_replace(',', '.', $quantity);
                $total = null;
                if (preg_match(\App\Models\Product::WEIGHT_PATTERN, $weight)) {
                    $weight = \Brick\Math\BigDecimal::of($weight);
                    if ($weight->isPositive() && $weight->isLessThanOrEqualTo($stock)) {
                        $total = (string) $weight->multipliedBy($product->price_per_kg)->toScale(0, \Brick\Math\RoundingMode::HalfUp);
                    }
                }
            @endphp

            <form action="{{ route($canOrder ? 'orders.store' : 'orders.create', $product) }}"
                method="{{ $canOrder ? 'POST' : 'GET' }}" class="product-order-form" data-order-form
                data-price-per-kg="{{ $product->price_per_kg }}" data-stock-kg="{{ $product->stock_kg }}">
                @if ($canOrder)
                    @php($oldToken = old('submission_token'))
                    @csrf
                    <input type="hidden" name="submission_token"
                        value="{{ is_string($oldToken) && Str::isUuid($oldToken) ? $oldToken : Str::uuid() }}">
                    <input type="hidden" name="price_per_kg" value="{{ $product->price_per_kg }}">
                @endif

                @if ($errors->hasAny(['order', 'submission_token', 'price_per_kg']))
                    <p role="alert" class="rounded-control bg-warning-soft p-3 text-sm text-warning">
                        {{ $errors->first(['order', 'submission_token', 'price_per_kg']) }}
                    </p>
                @endif
                <p data-order-error role="alert" class="rounded-control bg-warning-soft p-3 text-sm text-warning" hidden></p>

                <div>
                    <label for="order-quantity" class="mb-2 block font-semibold">Jumlah pesanan (kg)</label>
                    <div class="product-order-quantity">
                        <button type="button" data-order-decrease aria-label="Kurangi jumlah" hidden>
                            <flux:icon.minus class="size-5" />
                        </button>
                        <input id="order-quantity" name="quantity_kg" type="text" inputmode="decimal"
                            maxlength="11" required value="{{ str_replace('.', ',', $quantity) }}"
                            @error('quantity_kg') aria-invalid="true" aria-describedby="order-quantity-error" @enderror>
                        <button type="button" data-order-increase aria-label="Tambah jumlah" hidden>
                            <flux:icon.plus class="size-5" />
                        </button>
                    </div>
                    @error('quantity_kg')
                        <p id="order-quantity-error" class="mt-2 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="product-order-total">
                    <label for="order-total">{{ $checkout ? 'Total pesanan' : 'Total perkiraan' }}</label>
                    <output id="order-total" for="order-quantity" data-order-total aria-live="polite">
                        {{ $total === null ? '—' : 'Rp'.preg_replace('/\B(?=(?:[0-9]{3})+$)/', '.', $total) }}
                    </output>
                </div>

                @if ($canOrder)
                    <details @if (filled(old('notes')) || $errors->has('notes')) open @endif>
                        <summary class="w-fit cursor-pointer text-sm text-[var(--muted)]">Catatan untuk petani (opsional)</summary>
                        <flux:textarea name="notes" label="Catatan untuk petani" rows="3" maxlength="500" class="mt-3">{{ is_string(old('notes')) ? old('notes') : '' }}</flux:textarea>
                    </details>
                @endif

                <button type="submit" class="ht-button ht-primary product-order-submit">
                    <flux:icon.shopping-bag class="size-5 shrink-0" />
                    {{ $checkout ? 'Kirim pesanan' : 'Pesan hasil panen' }}
                    <flux:icon.arrow-right class="size-4 shrink-0" />
                </button>
                <p class="product-order-note">
                    @if ($isGuest)
                        Masuk sebagai pembeli untuk melanjutkan.
                    @else
                        Pembayaran dan pengambilan disepakati setelah petani menerima pesanan.
                    @endif
                </p>
            </form>
        @endif
    @endif
</section>
