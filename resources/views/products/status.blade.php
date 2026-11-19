@php
    $status = $product->status;
    $soldOut = $product->isSoldOut();
@endphp

<span @class([
    'rounded-full px-2.5 py-1 text-xs font-semibold',
    'bg-primary-soft text-success' => $status === \App\Enums\ProductStatus::Active && ! $soldOut,
    'bg-amber-50 text-warning' => $soldOut,
    'bg-zinc-100 text-zinc-700' => $status !== \App\Enums\ProductStatus::Active,
])>{{ $soldOut ? 'Habis' : $status->label() }}</span>
