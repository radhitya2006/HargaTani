const initializedForms = new WeakSet();

function toHundredths(value) {
    const amount = value.trim().replace(',', '.');
    if (!/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/.test(amount)) {
        return null;
    }

    const [whole, fraction = ''] = amount.split('.');
    return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
}

function formatWeight(hundredths) {
    const whole = hundredths / 100n;
    const fraction = String(hundredths % 100n).padStart(2, '0').replace(/0+$/, '');

    return fraction ? `${whole},${fraction}` : String(whole);
}

function initializeOrderForm() {
    const form = document.querySelector('[data-order-form]');
    if (!form || initializedForms.has(form)) return;
    initializedForms.add(form);

    const quantity = form.elements.namedItem('quantity_kg');
    const price = BigInt(form.dataset.pricePerKg);
    const stock = toHundredths(form.dataset.stockKg);
    const step = 100n;
    const output = form.querySelector('[data-order-total]');
    const error = form.querySelector('[data-order-error]');
    const submit = form.querySelector('button[type="submit"]');
    const decrease = form.querySelector('[data-order-decrease]');
    const increase = form.querySelector('[data-order-increase]');
    const rupiah = new Intl.NumberFormat('id-ID');
    const connectionError = 'Koneksi terputus. Periksa koneksi Anda, lalu kirim ulang pesanan.';

    function showError(message) {
        error.textContent = message;
        error.hidden = message === '';
    }

    function updateQuantity() {
        const amount = toHundredths(quantity.value);
        decrease.disabled = amount === null || amount <= step;
        increase.disabled = amount === null || amount >= stock;

        output.value = amount === null || amount === 0n || amount > stock
            ? '—'
            : 'Rp' + rupiah.format((amount * price + 50n) / 100n);
    }

    function adjustQuantity(change) {
        const current = toHundredths(quantity.value);
        if (current === null) return;

        const next = current + change;
        const minimum = stock < step ? stock : step;
        const bounded = next < minimum ? minimum : next > stock ? stock : next;
        quantity.value = formatWeight(bounded);
        quantity.dispatchEvent(new Event('input', { bubbles: true }));
    }

    decrease.addEventListener('click', () => adjustQuantity(-step));
    increase.addEventListener('click', () => adjustQuantity(step));
    decrease.hidden = false;
    increase.hidden = false;
    quantity.addEventListener('input', () => {
        showError('');
        updateQuantity();
    });
    updateQuantity();

    form.addEventListener('submit', async (event) => {
        const amount = toHundredths(quantity.value);
        const exceedsStock = amount !== null && amount > stock;
        if (amount === null || amount === 0n || exceedsStock) {
            event.preventDefault();
            showError(exceedsStock
                ? 'Stok tersedia tidak mencukupi.'
                : 'Masukkan jumlah minimal 0,01 kg dengan paling banyak dua angka desimal.');
            quantity.focus();
            return;
        }
        if (form.method === 'get') return;

        event.preventDefault();
        if (submit.disabled) return;
        if (!navigator.onLine) {
            showError(connectionError);
            return;
        }

        submit.disabled = true;
        showError('');
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            if (response.status === 422) {
                const { errors } = await response.json();
                showError(Object.values(errors).flat().join(' '));
            } else if (response.status === 401 || response.status === 419) {
                showError('Sesi Anda berakhir. Muat ulang halaman dan masuk kembali untuk melanjutkan.');
            } else if (response.ok && response.headers.get('content-type')?.includes('application/json')) {
                const { redirect } = await response.json();
                window.location.assign(redirect);
            } else {
                showError('Pesanan belum dapat dikonfirmasi. Coba kirim ulang.');
            }
        } catch {
            showError(connectionError);
        } finally {
            submit.disabled = false;
        }
    });
}

initializeOrderForm();
document.addEventListener('livewire:navigated', initializeOrderForm);
