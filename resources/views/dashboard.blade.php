<x-layouts::app title="Ringkasan">
    <div class="space-y-6">
        <x-ui.page-heading title="Ringkasan" :description="'Halo, '.auth()->user()->name.'.'" />
        <x-ui.panel class="max-w-form">
            <h2 class="text-xl font-semibold">Dashboard {{ auth()->user()->role->label() }}</h2>
            <flux:button class="mt-5" variant="primary" :href="route(auth()->user()->role->value.'.dashboard')" wire:navigate>
                Buka dashboard
            </flux:button>
        </x-ui.panel>
    </div>
</x-layouts::app>
