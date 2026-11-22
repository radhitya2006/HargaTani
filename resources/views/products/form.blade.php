@use('App\Enums\ProductStatus')

@php
    $title = $product ? 'Kelola Produk' : 'Tambah Produk';
    $fieldValue = static function (string $field, string|int|null $default = null): string {
        $value = old($field, $default);

        return is_scalar($value) ? (string) $value : '';
    };
    $commodity = $fieldValue('commodity', $product?->commodity);
    $district = $fieldValue('district', $product?->district ?? $defaultDistrict);
    $resetStock = $product && $fieldValue('stock_before', $product->stock_kg) !== $product->stock_kg;
    $currentStock = $product ? str_replace('.', ',', $product->stock_kg) : '';
@endphp

<x-layouts::app :title="$title">
    <div class="space-y-6">
        <a href="{{ route('petani.products.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-primary" wire:navigate>
            <flux:icon.arrow-left class="size-4" /> Produk Saya
        </a>

        <x-ui.page-heading :title="$title" :description="$product?->name" />

        @if (session('status'))
            <p role="status" class="rounded-control bg-primary-soft px-4 py-3 text-sm font-medium text-success">{{ session('status') }}</p>
        @endif

        <div class="grid items-start gap-5 {{ $product ? 'lg:grid-cols-[minmax(0,2fr)_minmax(15rem,1fr)]' : 'max-w-form' }}">
            <x-ui.panel>
                <form action="{{ $product ? route('petani.products.update', $product) : route('petani.products.store') }}"
                    method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @if ($product)
                        @method('PATCH')
                        <input type="hidden" name="stock_before" value="{{ $product->stock_kg }}">
                    @endif

                    <flux:input name="name" label="Nama produk" :value="$fieldValue('name', $product?->name)" required maxlength="120" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:select name="commodity" label="Komoditas" required>
                            <option value="" disabled @selected(! $commodity)>Pilih komoditas</option>
                            @foreach ($commodities as $slug => $item)
                                <option value="{{ $slug }}" @selected($commodity === $slug)>{{ $item['name'] }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input name="harvested_on" label="Tanggal panen" type="date" required
                            :max="today()->toDateString()" :value="$fieldValue('harvested_on', $product?->harvested_on->toDateString())" />
                    </div>

                    <div class="grid items-start gap-5 sm:grid-cols-2">
                        <flux:input name="price_per_kg" label="Harga per kg (Rp)" type="number" inputmode="numeric" min="1" max="9999999999"
                            step="1" :value="$fieldValue('price_per_kg', $product?->price_per_kg)" required />
                        <div class="space-y-2">
                            <flux:input name="stock_kg" label="Stok tersedia (kg)" inputmode="decimal"
                                :value="$resetStock ? $currentStock : $fieldValue('stock_kg', $currentStock)"
                                description:trailing="Isi stok di luar alokasi pesanan yang telah diterima. Gunakan hingga dua angka desimal." required />
                            <flux:error name="stock_before" />
                            @if ($resetStock)
                                <p role="alert" class="text-sm text-warning">Stok berubah sejak formulir dibuka. Periksa stok terbaru sebelum menyimpan.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid items-start gap-5 sm:grid-cols-2">
                        <flux:select name="district" label="Kabupaten/kota" required>
                            <option value="" disabled @selected(! $district)>Pilih kabupaten/kota</option>
                            @foreach ($districts as $option)
                                <option value="{{ $option }}" @selected($district === $option)>{{ $option }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input name="pickup_location" label="Lokasi pengambilan" required maxlength="250"
                            :value="$fieldValue('pickup_location', $product?->pickup_location)"
                            description:trailing="Lokasi pengambilan ditampilkan di etalase saat produk diterbitkan." />
                    </div>

                    <div class="grid gap-2">
                        <label for="description" class="text-sm font-medium">Deskripsi produk</label>
                        <textarea id="description" name="description" rows="4" maxlength="2000" required data-flux-control
                            @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
                            class="w-full rounded-control border border-control-line px-3 py-2">{{ $fieldValue('description', $product?->description) }}</textarea>
                        @error('description')
                            <p id="description-error" class="text-sm text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="photo" class="text-sm font-medium">{{ $product ? 'Ganti foto produk' : 'Foto produk' }}</label>
                        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp" @required(! $product)
                            class="min-h-11 w-full rounded-control border border-control-line text-sm file:me-3 file:min-h-11 file:border-0 file:bg-primary-soft file:px-3 file:font-medium file:text-primary"
                            aria-describedby="photo-hint{{ $errors->has('photo') ? ' photo-error' : '' }}"
                            @error('photo') aria-invalid="true" @enderror>
                        <p id="photo-hint" class="text-sm text-ink-muted">JPEG, PNG, atau WebP, maksimal 2 MB.</p>
                        @error('photo')
                            <p id="photo-error" class="text-sm text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <flux:button type="submit" variant="primary">{{ $product ? 'Simpan perubahan' : 'Simpan draf' }}</flux:button>
                </form>
            </x-ui.panel>

            @if ($product)
                <x-ui.panel class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-semibold">Status produk</h2>
                        @include('products.status', ['product' => $product])
                    </div>

                    <img src="{{ route('petani.products.photo', $product) }}" alt="Foto {{ $product->name }}"
                        class="aspect-[16/10] w-full rounded-control bg-background object-cover">

                    <form action="{{ route('petani.products.status', $product) }}" method="POST" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PATCH')

                        @if ($product->status === ProductStatus::Active && ! $product->isSoldOut())
                            <flux:button type="submit" name="action" value="sell_out">Tandai habis</flux:button>
                        @elseif ($product->isSoldOut())
                            <p class="text-sm text-ink-muted">Isi kembali stok tersedia untuk menandai produk tersedia.</p>
                        @else
                            <flux:button type="submit" name="action" value="publish" variant="primary">
                                {{ $product->status === ProductStatus::Archived ? 'Terbitkan kembali' : 'Terbitkan produk' }}
                            </flux:button>
                        @endif

                        @if ($product->status !== ProductStatus::Archived)
                            <flux:button type="submit" name="action" value="archive">Arsipkan</flux:button>
                        @endif
                    </form>
                    <flux:error name="action" />
                </x-ui.panel>
            @endif
        </div>
    </div>
</x-layouts::app>
