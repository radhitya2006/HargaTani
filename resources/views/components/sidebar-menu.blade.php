<div class="ht-sidebar-menu" x-data="{ open: false }" x-bind:data-open="open"
    x-on:pointerleave="if ($event.pointerType === 'mouse' && !$refs.panel.querySelector(':focus-visible')) open = false"
    x-on:focusout="if (!$el.contains($event.relatedTarget) && !$el.matches(':hover')) open = false"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="if (open) { open = false; if ($refs.panel.contains(document.activeElement)) $refs.trigger.focus(); }">
    <button type="button" class="ht-button ht-sidebar-trigger" aria-label="Menu navigasi" aria-controls="ht-sidebar"
        aria-expanded="false" x-bind:aria-expanded="open.toString()" x-ref="trigger"
        x-on:pointerenter="if ($event.pointerType === 'mouse') open = true"
        x-on:click="if (!open) { open = true; if ($event.detail === 0) $nextTick(() => $refs.heading.querySelector('a')?.focus()); }">
        <flux:icon.bars-3 class="size-5" aria-hidden="true" />
    </button>

    <aside class="ht-sidebar" id="ht-sidebar" aria-label="Menu HargaTani" inert x-bind:inert="!open" x-ref="panel"
        x-on:click="if ($event.target.closest('a[href]')) open = false">
        <div class="ht-sidebar-panel">
            <div class="ht-sidebar-heading" x-ref="heading">
                {{ $brand }}
            </div>
            {{ $slot }}
        </div>
    </aside>
</div>
