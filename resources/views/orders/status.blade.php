@php
    $tone = match ($status) {
        \App\Enums\OrderStatus::Pending => 'bg-warning-soft text-warning',
        \App\Enums\OrderStatus::Accepted, \App\Enums\OrderStatus::Completed => 'bg-primary-soft text-success',
        default => 'bg-background text-ink-muted',
    };
@endphp

<span class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-semibold {{ $tone }}">{{ $status->label() }}</span>
