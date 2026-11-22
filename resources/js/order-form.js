const form = document.querySelector('[data-order-form]');

if (form) {
    const quantity = form.elements.namedItem('quantity_kg');
    const price = form.elements.namedItem('price_per_kg');
    const output = form.querySelector('[data-order-total]');
    const error = form.querySelector('[data-order-error]');
    const submit = form.querySelector('button[type="submit"]');
    const rupiah = new Intl.NumberFormat('id-ID');
    const connectionError = 'Koneksi terputus. Periksa koneksi Anda, lalu kirim ulang pesanan.';

    function showError(message) {
        error.textContent = message;
        error.hidden = message === '';
    }

    function updateTotal() {
        const amount = quantity.value.trim().replace(',', '.');
        if (!/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/.test(amount)) {
            output.value = '—';
            return;
        }

        const [whole, fraction = ''] = amount.split('.');
        const hundredths = BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
        const total = (hundredths * BigInt(price.value) + 50n) / 100n;
        output.value = hundredths === 0n ? '—' : 'Rp' + rupiah.format(total);
    }

    quantity.addEventListener('input', updateTotal);
    updateTotal();

    form.addEventListener('submit', async (event) => {
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
