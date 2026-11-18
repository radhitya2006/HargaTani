<x-layouts::app.sidebar :title="$title ?? null">
    <main data-flux-main id="main-content" tabindex="-1" class="min-w-0 [grid-area:main]">
        <div class="mx-auto w-full max-w-page px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </div>
    </main>
</x-layouts::app.sidebar>
