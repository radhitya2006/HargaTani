@props(['product'])

<article class="ht-card grid min-w-0">
    <a href="{{ route('catalog.show', $product) }}" class="group flex h-full flex-col rounded-panel">
        <img src="{{ route('catalog.photo', $product) }}" alt="" loading="lazy"
            class="aspect-[16/10] w-full rounded-t-panel bg-primary-soft object-cover">

        <div class="ht-product-info flex flex-1 flex-col break-words">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <small>{{ $product->district }}, Bali</small>
                @if ($product->isSoldOut())
                    <span class="rounded-full bg-warning-soft px-2.5 py-1 text-xs font-semibold text-warning">Habis</span>
                @endif
            </div>

            <h3 class="group-hover:underline group-hover:underline-offset-4">{{ $product->name }}</h3>
            <div class="ht-between mt-auto flex-wrap gap-x-3 gap-y-1">
                <p class="ht-offer break-all">Rp{{ number_format($product->price_per_kg, 0, ',', '.') }} <small>/kg</small></p>
                @unless ($product->isSoldOut())
                    <small>Stok {{ str_replace('.', ',', $product->stock_kg) }} kg</small>
                @endunless
            </div>
            <p class="ht-farmer"><flux:icon.user-circle class="size-4 shrink-0" /> {{ $product->farmer->name }}</p>
        </div>
    </a>
</article>
