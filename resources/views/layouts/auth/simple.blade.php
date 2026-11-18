<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body>
        <x-skip-link />
        <main id="main-content" tabindex="-1" class="flex min-h-svh flex-col items-center justify-center px-4 py-8 sm:px-6">
            <div class="flex w-full max-w-auth flex-col gap-6">
                <x-app-logo :href="route('home')" class="self-center" />
                <x-ui.panel class="sm:p-8">
                    {{ $slot }}
                </x-ui.panel>
            </div>
        </main>
        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>
