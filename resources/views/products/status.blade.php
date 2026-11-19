@php
    $status = $product->status;
    $soldOut = $product->isSoldOut();
@endphp

<span @class([
    'rounded-full px-2.5 py-1 text-xs font-semibold',
    'bg-primary-soft text-success' => $status === \App\Enums\ProductStatus::Active && ! $soldOut,
    'bg-warning-soft text-warning' => $soldOut,
    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => $status !== \App\Enums\ProductStatus::Active,
])>{{ $soldOut ? 'Habis' : $status->label() }}</span>
