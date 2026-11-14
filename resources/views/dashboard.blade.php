<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4">
        <flux:heading size="xl">Selamat datang di HargaTani</flux:heading>
        <flux:text>Halo, {{ auth()->user()->name }}. Anda masuk sebagai {{ auth()->user()->role->label() }}.</flux:text>
        <flux:button variant="primary" :href="route(auth()->user()->role->value.'.dashboard')" wire:navigate>Buka dashboard {{ auth()->user()->role->label() }}</flux:button>
        <flux:text>Pantau harga, tentukan harga jual, dan pasarkan hasil panen Anda. Fitur pertanian akan tersedia secara bertahap.</flux:text>
    </div>
</x-layouts::app>
