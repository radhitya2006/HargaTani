<x-layouts::app :title="'Dashboard '.$role->label()">
    <div class="space-y-6">
        <x-ui.page-heading :title="'Dashboard '.$role->label()" :description="'Selamat datang, '.auth()->user()->name.'.'" />
        @if ($role === \App\Enums\Role::Petani)
            <x-ui.panel class="max-w-form">
                <h2 class="text-xl font-semibold">Kalkulator Harga Jual</h2>
                <p class="mt-2 text-ink-muted">Hitung biaya per kilogram dan bandingkan harga usulan dengan harga pasar.</p>
                <flux:button class="mt-5" :href="route('calculator.show')">Buka kalkulator</flux:button>
            </x-ui.panel>
        @endif
        <x-ui.panel class="max-w-form">
            <h2 class="text-xl font-semibold">Akun {{ $role->label() }}</h2>
            <p class="mt-2 text-ink-muted">Kelola nama, email, dan kata sandi Anda melalui pengaturan akun.</p>
            <flux:button class="mt-5" :href="route('profile.edit')" wire:navigate>Pengaturan akun</flux:button>
        </x-ui.panel>
    </div>
</x-layouts::app>
