<x-layouts::app :title="'Dashboard '.$role->label()">
    <div class="mx-auto w-full max-w-4xl space-y-5 p-4">
        <flux:heading size="xl">Dashboard {{ $role->label() }}</flux:heading>
        <flux:text>Selamat datang, {{ auth()->user()->name }}. Akun {{ $role->value }} Anda sudah siap.</flux:text>
        <flux:text>Fitur harga pasar, produk, dan pesanan akan tersedia pada tahap pengembangan berikutnya.</flux:text>
        <flux:button :href="route('profile.edit')" wire:navigate>Pengaturan akun</flux:button>
    </div>
</x-layouts::app>
