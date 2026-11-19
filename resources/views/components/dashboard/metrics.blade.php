@props(['metrics'])

<dl {{ $attributes->class('grid gap-4 md:grid-cols-3') }}>
    @foreach ($metrics as $label => $value)
        <x-ui.panel class="min-w-0">
            <dt class="text-sm font-medium text-ink-muted">{{ $label }}</dt>
            <dd class="mt-2 text-3xl font-bold tracking-tight text-primary tabular-nums">{{ number_format($value, 0, ',', '.') }}</dd>
        </x-ui.panel>
    @endforeach
</dl>
