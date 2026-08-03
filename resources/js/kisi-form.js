import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function setFieldValue(root, selector, value) {
    if (value === null || value === undefined || value === '') {
        return;
    }

    const el = root.querySelector(selector);
    if (!el) {
        return;
    }

    el.value = String(value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function readTcVeDogum(root) {
    const tc = (root.querySelector('[data-kimlik-tc]')?.value || '').trim();
    const dogum = (root.querySelector('[data-kimlik-dogum]')?.value || '').trim();

    if (!/^\d{11}$/.test(tc)) {
        showToast('TC Kimlik No 11 haneli olmalıdır.', 'error');
        return null;
    }
    if (!dogum) {
        showToast('Doğum tarihi zorunludur.', 'error');
        return null;
    }

    return { tc, dogum };
}

async function sorgula(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = data?.errors
            ? Object.values(data.errors).flat()[0]
            : data?.message;
        throw new Error(firstError || 'Sorgulama başarısız');
    }

    return data;
}

export function initKisiForm() {
    document.querySelectorAll('[data-kisi-form]').forEach((root) => {
        if (root.dataset.kisiFormInit === '1') {
            return;
        }
        root.dataset.kisiFormInit = '1';

        const kimlikBtn = root.querySelector('[data-kimlik-sorgula]');
        const kimlikUrl = root.dataset.kimlikSorgulaUrl;
        const adresBtn = root.querySelector('[data-adres-sorgula]');
        const adresUrl = root.dataset.adresSorgulaUrl;

        kimlikBtn?.addEventListener('click', async () => {
            if (!kimlikUrl) {
                return;
            }

            const input = readTcVeDogum(root);
            if (!input) {
                return;
            }

            kimlikBtn.disabled = true;
            kimlikBtn.classList.add('is-loading');

            try {
                const data = await sorgula(kimlikUrl, {
                    tc_kimlik_no: input.tc,
                    dogum_tarihi: input.dogum,
                });

                const kisi = data.kisi || {};
                [
                    'ad',
                    'soyad',
                    'cinsiyet',
                    'dogum_yeri',
                    'medeni_durum',
                    'uyruk',
                    'anne_adi',
                    'baba_adi',
                ].forEach((key) => setFieldValue(root, `[data-kimlik-alan="${key}"]`, kisi[key]));

                if (kisi.dogum_tarihi) {
                    setFieldValue(root, '[data-kimlik-dogum]', String(kisi.dogum_tarihi).slice(0, 10));
                }

                showToast(data.message || 'Kimlik bilgileri getirildi.', 'success');
            } catch (error) {
                showToast(error.message || 'Kimlik sorgulama başarısız', 'error');
            } finally {
                kimlikBtn.disabled = false;
                kimlikBtn.classList.remove('is-loading');
            }
        });

        adresBtn?.addEventListener('click', async () => {
            if (!adresUrl) {
                return;
            }

            const input = readTcVeDogum(root);
            if (!input) {
                return;
            }

            adresBtn.disabled = true;
            adresBtn.classList.add('is-loading');

            try {
                const data = await sorgula(adresUrl, {
                    tc_kimlik_no: input.tc,
                    dogum_tarihi: input.dogum,
                });

                const adres = data.adres || {};
                ['il', 'ilce', 'adres'].forEach((key) => {
                    setFieldValue(root, `[data-adres-alan="${key}"]`, adres[key]);
                });

                showToast(data.message || 'Adres bilgileri getirildi.', 'success');
            } catch (error) {
                showToast(error.message || 'Adres sorgulama başarısız', 'error');
            } finally {
                adresBtn.disabled = false;
                adresBtn.classList.remove('is-loading');
            }
        });
    });
}
