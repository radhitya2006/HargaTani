@props(['compact' => false])

<button type="button"
    {{ $attributes->class(['ht-button ht-theme-toggle', 'ht-theme-toggle--compact' => $compact]) }}
    x-data x-cloak aria-label="Mode gelap" aria-pressed="false"
    x-bind:aria-pressed="$flux.dark.toString()" x-on:click="$flux.dark = ! $flux.dark"
    @if ($compact) title="Mode gelap" @endif>
    <flux:icon.moon class="size-5 shrink-0 dark:hidden" aria-hidden="true" />
    <flux:icon.sun class="hidden size-5 shrink-0 dark:block" aria-hidden="true" />
    @unless ($compact)
        <span>Mode gelap</span>
    @endunless
</button>
