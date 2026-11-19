@props(['showDashboard' => false])

@php
    $user = auth()->user();
@endphp

<flux:dropdown position="bottom" align="end" class="shrink-0">
    @if ($user)
        <flux:profile :initials="$user->initials()" icon-trailing="chevron-down"
            aria-label="Menu akun" data-test="sidebar-menu-button" />
    @else
        <flux:button variant="outline" icon="user-circle" aria-label="Menu akun" />
    @endif

    <flux:menu class="max-w-[calc(100vw-2rem)]">
        @if ($user)
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                <flux:avatar :name="$user->name" :initials="$user->initials()" class="shrink-0" />
                <div class="grid min-w-0 flex-1 text-start leading-tight">
                    <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                </div>
            </div>
            <flux:menu.separator />
            @if ($showDashboard)
                <flux:menu.item :href="route('dashboard')" icon="squares-2x2">Dashboard</flux:menu.item>
            @endif
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
            <flux:menu.separator />
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer" data-test="logout-button">{{ __('Log out') }}</flux:menu.item>
            </form>
        @else
            <flux:menu.item :href="route('login')" icon="arrow-right-end-on-rectangle">Masuk</flux:menu.item>
            <flux:menu.item :href="route('register')" icon="user-plus">Daftar</flux:menu.item>
        @endif
    </flux:menu>
</flux:dropdown>
