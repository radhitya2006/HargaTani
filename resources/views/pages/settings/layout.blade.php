<div class="grid min-w-0 gap-5 md:grid-cols-[12rem_minmax(0,1fr)] md:items-start">
    <div class="rounded-panel border border-line bg-surface p-2">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item class="min-h-11" :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item class="min-h-11" :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
        </flux:navlist>
    </div>
    <x-ui.panel class="min-w-0 max-w-form">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </x-ui.panel>
</div>
