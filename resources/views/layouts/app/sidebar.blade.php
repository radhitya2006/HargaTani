@php
    $role = auth()->user()->role;
    $roleDashboard = $role->value.'.dashboard';
    $isPetani = $role === \App\Enums\Role::Petani;
    $isAdmin = $role === \App\Enums\Role::Admin;
    $dashboardRoute = $isAdmin ? 'dashboard' : $roleDashboard;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh">
        <x-skip-link />
        <flux:header class="ht-site-header">
            <x-sidebar-menu>
                <x-slot:brand>
                    <x-landing-brand :href="route($dashboardRoute)" aria-label="HargaTani, dashboard" wire:navigate />
                </x-slot:brand>

                <flux:sidebar.nav aria-label="Navigasi utama">
                    <flux:sidebar.group :heading="__('Platform')" class="grid">
                        @if ($isAdmin)
                            <flux:sidebar.item icon="home" class="min-h-11" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                                Ringkasan
                            </flux:sidebar.item>
                        @endif
                        <flux:sidebar.item icon="layout-grid" class="min-h-11" :href="route($roleDashboard)" :current="request()->routeIs($roleDashboard)" wire:navigate>
                            Dashboard {{ $role->label() }}
                        </flux:sidebar.item>
                        @if ($isPetani)
                            <flux:sidebar.item icon="cube" class="min-h-11" :href="route('petani.products.index')" :current="request()->routeIs('petani.products.*')" wire:navigate>
                                Produk Saya
                            </flux:sidebar.item>
                        @elseif ($role === \App\Enums\Role::Pembeli)
                            <flux:sidebar.item icon="shopping-bag" class="min-h-11" :href="route('catalog.index')">
                                Hasil Panen
                            </flux:sidebar.item>
                        @endif
                        @unless ($isAdmin)
                            <flux:sidebar.item icon="clipboard-document-list" class="min-h-11" :href="route('orders.index')" :current="request()->routeIs('orders.*')" wire:navigate>
                                {{ $isPetani ? 'Pesanan Masuk' : 'Pesanan Saya' }}
                            </flux:sidebar.item>
                        @endunless
                        <flux:sidebar.item icon="cog" class="min-h-11" :href="route('profile.edit')" :current="request()->routeIs('profile.*', 'security.*')" wire:navigate>
                            Pengaturan akun
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>

                @unless ($isAdmin)
                    <div class="ht-sidebar-account">
                        <a class="ht-button ht-primary" href="{{ route('home') }}">
                            Kembali ke Beranda <flux:icon.arrow-up-right class="size-4" />
                        </a>
                    </div>
                @endunless
            </x-sidebar-menu>

            <flux:spacer />

            <x-theme-toggle compact />
            <x-account-menu />
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
