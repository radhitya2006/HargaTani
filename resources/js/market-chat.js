const initialized = new WeakSet();
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });

function formatDate(date) {
    return dateFormat.format(new Date(`${date}T00:00:00Z`));
}

function element(tag, className, text) {
    const node = document.createElement(tag);
    node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function initializeMarketChat() {
    const panel = document.querySelector('[data-market-chat]');
    if (!panel || initialized.has(panel)) return;
    initialized.add(panel);

    const form = panel.querySelector('[data-chat-form]');
    const question = form.elements.namedItem('question');
    const buttons = form.querySelectorAll('button');
    const log = panel.querySelector('[data-chat-log]');
    const messages = panel.querySelector('[data-chat-messages]');
    const source = panel.querySelector('[data-chat-source]');
    const status = panel.querySelector('[data-chat-status]');
    const { url, csrf } = panel.dataset;
    let pending = false;

    function attribution(start, end, detail = '') {
        const node = source.content.cloneNode(true);
        const dates = start === end ? formatDate(start) : `${formatDate(start)} – ${formatDate(end)}`;
        node.querySelector('[data-chat-period]').textContent = dates + detail;
        return node;
    }

    function renderResults(results) {
        const periods = element('ol', 'mt-2 grid gap-2');
        for (const result of results) {
            const row = element('li', 'rounded-control border border-line bg-background p-3');
            row.append(element('p', 'text-sm font-semibold', `${result.period} hari`));
            const items = element('ul', 'mt-1 grid gap-1.5 text-sm');
            for (const item of result.items) {
                const name = element('strong', 'font-semibold', item.commodity_name);
                const detail = item.observed_on ? ` · ${formatDate(item.observed_on)}` : '';
                const line = element('li', 'wrap-anywhere');
                line.append(name, document.createTextNode(` · ${item.text}${detail}`));
                items.append(line);
            }
            if (result.items.length) row.append(items);
            if (result.missing.length) {
                row.append(element('p', 'mt-1 text-xs text-ink-muted', `Belum ada pengamatan untuk ${result.missing.join(', ')}.`));
            }
            row.append(attribution(result.start, result.end));
            periods.append(row);
        }
        return periods;
    }

    function addMessage(speaker, text, reference = null, results = null) {
        const message = element('li', speaker === 'Anda'
            ? 'ml-auto w-fit max-w-full rounded-control bg-primary-soft px-3 py-2 wrap-anywhere sm:max-w-[85%]'
            : 'w-fit max-w-full rounded-control border border-line px-3 py-2 wrap-anywhere sm:max-w-[85%]');
        message.append(
            element('p', 'mb-1 text-xs font-semibold text-ink-muted', speaker),
            element('p', 'text-sm leading-relaxed', text),
        );

        if (reference) {
            const observed = reference.observed_on ? ` · Tercatat ${formatDate(reference.observed_on)}` : '';
            message.append(
                element('p', 'mt-1 text-xs text-ink-muted', reference.commodity_name),
                attribution(reference.start, reference.end, ` · ${reference.count} pengamatan${observed}`),
            );
        }
        if (Array.isArray(results)) message.append(renderResults(results));
        messages.append(message);
    }

    function setPending(value) {
        pending = value;
        question.readOnly = value;
        for (const button of buttons) button.disabled = value;
    }

    for (const button of form.querySelectorAll('[data-chat-question]')) {
        button.addEventListener('click', () => {
            question.value = button.dataset.chatQuestion;
            form.requestSubmit();
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = question.value.trim();
        if (pending || !text) return;
        if (!navigator.onLine) {
            status.textContent = 'Anda sedang offline. Pertanyaan tetap ada di kolom.';
            return;
        }

        setPending(true);
        status.textContent = 'Mencari jawaban…';
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ question: text }),
            });
            if (response.redirected || response.status === 401 || response.status === 419) {
                status.textContent = 'Sesi berakhir. Masuk kembali untuk bertanya.';
                return;
            }
            if (response.status === 429) {
                status.textContent = 'Terlalu banyak permintaan. Coba lagi dalam satu menit.';
                return;
            }

            const answer = await response.json().catch(() => null);
            if (!response.ok || typeof answer?.text !== 'string') {
                status.textContent = answer?.message ?? 'Jawaban belum tersedia. Coba lagi.';
                return;
            }

            log.hidden = false;
            addMessage('Anda', text);
            addMessage('HargaTani', answer.text, answer.reference, answer.results);
            log.scrollTop = log.scrollHeight;
            question.value = '';
            status.textContent = '';
        } catch {
            status.textContent = 'Koneksi terputus. Pertanyaan tetap ada di kolom.';
        } finally {
            setPending(false);
        }
    });

    form.hidden = false;
}

function openLinkedChat() {
    if (window.location.hash === '#tanya-harga-pasar') {
        const chat = document.getElementById('tanya-harga-pasar');
        if (chat) chat.open = true;
    }
}

function initializeChatPage() {
    openLinkedChat();
    initializeMarketChat();
}

initializeChatPage();
document.addEventListener('livewire:navigated', initializeChatPage);
window.addEventListener('hashchange', openLinkedChat);
