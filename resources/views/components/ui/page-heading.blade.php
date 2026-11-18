@props(['title', 'description' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-4') }}>
    <div class="min-w-0 space-y-2">
        <h1 class="text-page font-bold tracking-tight md:text-[2rem] md:leading-tight">{{ $title }}</h1>
        @if ($description)
            <p class="max-w-2xl text-base leading-relaxed text-ink-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
