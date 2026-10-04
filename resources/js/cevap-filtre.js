const BOS = '__bos__';

function parseSecenekler(option) {
    try {
        const parsed = JSON.parse(option?.dataset.secenekler || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

/**
 * Başvuru listelerindeki "Ek soruya göre filtrele" alanı.
 * Değer değiştiğinde onChange çağrılır; params() istek parametrelerini döndürür.
 */
export function initCevapFiltre(panel, onChange) {
    const root = panel?.querySelector('[data-cevap-filtre]');
    if (!root) {
        return { params: () => ({}) };
    }

    const soruSelect = root.querySelector('[data-cevap-soru]');
    const degerSelect = root.querySelector('[data-cevap-deger-secim]');
    const degerInput = root.querySelector('[data-cevap-deger-metin]');
    const temizleBtn = root.querySelector('[data-cevap-filtre-temizle]');
    let debounce = null;
    let sonParams = '';

    function params() {
        const soru = soruSelect.value;
        if (!soru) return {};
        const secmeli = soruSelect.selectedOptions[0]?.dataset.secmeli === '1';
        const deger = secmeli ? degerSelect.value : degerInput.value.trim();
        return deger ? { cevap_soru: soru, cevap_deger: deger } : {};
    }

    function bildir() {
        const yeni = JSON.stringify(params());
        if (yeni === sonParams) return;
        sonParams = yeni;
        temizleBtn.hidden = !soruSelect.value;
        onChange?.();
    }

    function alanlariHazirla() {
        const option = soruSelect.selectedOptions[0];
        const soru = soruSelect.value;
        const secmeli = option?.dataset.secmeli === '1';

        degerSelect.innerHTML = '';
        degerInput.value = '';
        degerSelect.hidden = !soru || !secmeli;
        degerInput.hidden = !soru || secmeli;
        temizleBtn.hidden = !soru;

        if (soru && secmeli) {
            degerSelect.append(new Option('Cevap seçin', ''));
            parseSecenekler(option).forEach((secenek) => {
                degerSelect.append(new Option(secenek.etiket, String(secenek.id)));
            });
            degerSelect.append(new Option('Cevaplamayanlar', BOS));
        }
    }

    soruSelect.addEventListener('change', () => {
        alanlariHazirla();
        bildir();
        if (!degerSelect.hidden) degerSelect.focus();
        if (!degerInput.hidden) degerInput.focus();
    });

    degerSelect.addEventListener('change', bildir);

    degerInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(bildir, 400);
    });

    temizleBtn.addEventListener('click', () => {
        soruSelect.value = '';
        alanlariHazirla();
        bildir();
    });

    alanlariHazirla();

    return { params };
}

export function basvuruCookieKey(baseKey, panel) {
    const formId = panel?.dataset.soruFormuId;
    return formId ? `${baseKey}_form${formId}` : baseKey;
}
