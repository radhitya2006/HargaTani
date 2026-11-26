@use('App\Services\GeminiAssistant')
@use('App\Services\PihpsBaliPrices')

<details id="tanya-harga-pasar" class="ht-card market-chat group mt-6 min-w-0 scroll-mt-20"
    @if (GeminiAssistant::available())
        data-market-chat data-url="{{ route('ai.market-chat') }}" data-csrf="{{ csrf_token() }}"
    @endif>
    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 rounded-panel p-4 sm:px-6 [&::-webkit-details-marker]:hidden">
        <h3 class="flex items-center gap-2 text-lg font-semibold">
            <flux:icon.chat-bubble-left-right class="size-5 shrink-0 text-primary" />
            Tanya Harga Pasar
        </h3>
        <flux:icon.chevron-down class="size-5 shrink-0 text-ink-muted group-open:rotate-180 motion-safe:transition-transform motion-safe:duration-200" />
    </summary>

    <div class="grid min-w-0 gap-4 border-t border-line p-4 sm:p-6">
        <p class="text-sm text-ink-muted">
            Bandingkan harga eceran komoditas Bali dari
            <a class="ht-link underline" href="{{ PihpsBaliPrices::SOURCE_URL }}">PIHPS Bank Indonesia</a>.
        </p>

        @if (! GeminiAssistant::available())
            <p class="text-sm text-ink-muted">Chatbot belum tersedia.</p>
        @else
            @guest
                <p class="text-sm text-ink-muted">Percakapan tamu tidak disimpan oleh HargaTani.</p>
            @endguest

            <div role="log" aria-label="Percakapan harga pasar" tabindex="0"
                class="max-h-96 overflow-y-auto overscroll-contain rounded-control border border-line bg-surface p-3" data-chat-log hidden>
                <ol class="grid gap-3" data-chat-messages></ol>
            </div>
            <template data-chat-source>
                <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-ink-muted">
                    <span data-chat-period></span>
                    <a class="ht-link underline" href="{{ PihpsBaliPrices::SOURCE_URL }}">PIHPS Bank Indonesia</a>
                </p>
            </template>

            <form class="grid min-w-0 gap-3" data-chat-form hidden>
                <div class="flex flex-wrap gap-2">
                    @foreach (['Komoditas termahal', 'Komoditas termurah', 'Harga terakhir', 'Ringkas tren', 'Naik atau turun?', 'Rata-rata berapa?'] as $shortcut)
                        <button type="button" class="ht-button px-3 py-2 text-sm disabled:opacity-60"
                            data-chat-question="{{ $shortcut }}">{{ $shortcut }}</button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <label class="grid min-w-0 flex-[1_1_14rem] gap-1 text-sm font-medium">
                        Pertanyaan tentang harga pasar
                        <input type="text" name="question" required maxlength="300" autocomplete="off"
                            placeholder="Contoh: Harga cabai rawit hari ini?"
                            class="min-h-11 w-full min-w-0 rounded-control border border-control-line bg-surface px-3 text-base">
                    </label>
                    <button type="submit" class="ht-button ht-primary cursor-pointer disabled:opacity-60">Kirim</button>
                </div>
                <p class="text-sm text-ink-muted" role="status" data-chat-status></p>
            </form>
            <noscript><p class="text-sm text-ink-muted">Aktifkan JavaScript untuk bertanya tentang harga pasar.</p></noscript>
        @endif
    </div>
</details>
