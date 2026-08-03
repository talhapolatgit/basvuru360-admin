import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function ageFromDate(value) {
    if (!value) return null;
    const birth = new Date(`${value}T00:00:00`);
    if (Number.isNaN(birth.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age -= 1;
    }
    return age;
}

function normalizeYer(value) {
    return String(value || '')
        .trim()
        .replaceAll('I', 'ı')
        .replaceAll('İ', 'i')
        .toLocaleLowerCase('tr-TR');
}

function formatKosullarOzeti(kosullar) {
    if (!kosullar) return '—';
    const parts = [];
    if (kosullar.minimum_yas != null || kosullar.maksimum_yas != null) {
        parts.push(`${kosullar.minimum_yas ?? '—'} - ${kosullar.maksimum_yas ?? '—'} yaş`);
    }
    if (kosullar.cinsiyet_sarti_label) {
        parts.push(kosullar.cinsiyet_sarti_label);
    }
    if (kosullar.ikamet_sarti === 'evet') {
        parts.push(kosullar.hizmet_ilcesi ? `${kosullar.hizmet_ilcesi} ikamet` : 'İkamet eden');
    } else if (kosullar.ikamet_sarti === 'kismen') {
        parts.push('Sınırlı ilçe dışı');
    }
    return parts.length ? parts.join('. ') : 'Koşul tanımlanmamış';
}

export function initBasvuruCreatePage() {
    const form = document.querySelector('[data-basvuru-create]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const stepBtns = [...form.querySelectorAll('[data-basvuru-step-btn]')];
    const panels = [...form.querySelectorAll('[data-basvuru-step-panel]')];
    const prevBtn = form.querySelector('[data-basvuru-prev]');
    const nextBtn = form.querySelector('[data-basvuru-next]');
    const submitBtn = form.querySelector('[data-basvuru-submit]');
    const kursHidden = form.querySelector('[data-basvuru-kurs-select] [data-select-value]');
    const ozetRoot = form.querySelector('[data-basvuru-kurs-ozet]');
    const ozetBody = form.querySelector('[data-basvuru-kurs-ozet-body]');
    const ozetEmpty = ozetRoot?.querySelector('.basvuru-kurs-ozet-empty');
    const evrakNote = form.querySelector('[data-basvuru-kurs-evrak-note]');
    const evrakStep = form.querySelector('[data-basvuru-evrak-step]');
    const evrakPanel = form.querySelector('[data-basvuru-evrak-panel]');
    const evrakList = form.querySelector('[data-basvuru-evrak-list]');
    const dogumInput = form.querySelector('[data-basvuru-dogum]');
    const veliBadge = form.querySelector('[data-basvuru-veli-badge]');
    const veliReqs = form.querySelectorAll('[data-veli-req]');

    let step = 1;
    let maxStep = 3;
    let hasEvrak = false;
    let kursOzet = null;
    const ozetUrlTemplate = form.dataset.kursOzetUrlTemplate || '';

    function syncVeliRequired() {
        const age = ageFromDate(dogumInput?.value || '');
        const required = age !== null && age < 18;
        veliBadge?.classList.toggle('is-hidden', !required);
        veliReqs.forEach((el) => el.classList.toggle('is-hidden', !required));
        return required;
    }

    function validateKursKosullari({ dogum, cinsiyet, il, ilce }) {
        const kosullar = kursOzet?.kosullar;
        if (!kosullar) return null;

        const yas = ageFromDate(dogum);
        const min = kosullar.minimum_yas;
        const max = kosullar.maksimum_yas;

        if (min != null || max != null) {
            if (yas === null) {
                return 'Yaş koşulu için doğum tarihi zorunludur.';
            }
            if (min != null && max != null && (yas < min || yas > max)) {
                return `Bu kursa başvurmak için ${min}-${max} yaş aralığında olmalısınız.`;
            }
            if (min != null && yas < min) {
                return `Bu kursa başvurmak için en az ${min} yaşında olmalısınız.`;
            }
            if (max != null && yas > max) {
                return `Bu kursa başvurmak için en fazla ${max} yaşında olmalısınız.`;
            }
        }

        if (kosullar.cinsiyet_sarti) {
            if (!cinsiyet) {
                return 'Bu kurs için cinsiyet seçimi zorunludur.';
            }
            if (cinsiyet !== kosullar.cinsiyet_sarti) {
                return `Bu kurs yalnızca ${kosullar.cinsiyet_sarti_label || kosullar.cinsiyet_sarti} katılımcılara açıktır.`;
            }
        }

        const ikamet = kosullar.ikamet_sarti;
        const hizmetIlcesi = String(kosullar.hizmet_ilcesi || '').trim();
        if ((ikamet === 'evet' || ikamet === 'kismen') && hizmetIlcesi) {
            if (!ilce) {
                return 'İkamet koşulu için ilçe bilgisi zorunludur.';
            }
            const yerelIlce = normalizeYer(ilce) === normalizeYer(hizmetIlcesi);
            const hizmetIli = String(kosullar.hizmet_ili || '').trim();
            const yerelIl = !hizmetIli || !il || normalizeYer(il) === normalizeYer(hizmetIli);
            const yerel = yerelIlce && yerelIl;

            if (ikamet === 'evet' && !yerel) {
                return `Bu başvuru yalnızca ${hizmetIlcesi} ilçesinde ikamet edenlere açıktır.`;
            }
            // kismen kontenjan sunucuda doğrulanır
        }

        return null;
    }

    function setOzet(data) {
        kursOzet = data;
        if (!data) {
            ozetRoot?.classList.add('is-empty');
            if (ozetEmpty) ozetEmpty.hidden = false;
            if (ozetBody) ozetBody.hidden = true;
            hasEvrak = false;
            syncEvrakUi([]);
            return;
        }

        ozetRoot?.classList.remove('is-empty');
        if (ozetEmpty) ozetEmpty.hidden = true;
        if (ozetBody) ozetBody.hidden = false;

        const map = {
            kurs_no: data.kurs_no ? `#${data.kurs_no}` : '—',
            brans: data.brans || '—',
            merkez: data.merkez || '—',
            durum: data.durum || '—',
            baslama: data.baslama || '—',
            bitis: data.bitis || '—',
            kontenjan: data.kontenjan ?? '—',
            kayit_sayisi: data.kayit_sayisi ?? '0',
            ana_sayisi: data.doluluk?.ana_sayisi ?? data.kayit_sayisi ?? '0',
            yedek_kontenjan: data.yedek_kontenjan ?? data.doluluk?.yedek_kontenjan ?? '0',
            yedek_sayisi: data.doluluk?.yedek_sayisi ?? '0',
            kosullar: formatKosullarOzeti(data.kosullar),
        };
        Object.entries(map).forEach(([key, value]) => {
            const el = form.querySelector(`[data-ozet="${key}"]`);
            if (el) el.textContent = String(value);
        });

        const tipler = data.evrak_tipleri || [];
        hasEvrak = Boolean(data.evrak_zorunlu && tipler.length);
        const listEl = form.querySelector('[data-ozet="evrak_listesi"]');
        if (listEl) {
            listEl.textContent = tipler.map((t) => t.ad).join(', ') || '—';
        }
        evrakNote?.classList.toggle('is-hidden', !hasEvrak);
        syncEvrakUi(tipler);
    }

    function syncEvrakUi(tipler) {
        maxStep = hasEvrak ? 4 : 3;
        if (evrakStep) {
            evrakStep.hidden = !hasEvrak;
            evrakStep.disabled = !hasEvrak;
        }
        if (evrakPanel) {
            evrakPanel.hidden = true;
        }
        if (!evrakList) return;

        if (!hasEvrak) {
            evrakList.innerHTML = '';
            return;
        }

        evrakList.innerHTML = tipler.map((tip) => `
            <div class="basvuru-evrak-item">
                <div class="basvuru-evrak-item-head">
                    <strong>${escapeHtml(tip.ad)}</strong>
                    ${tip.aciklama ? `<span class="basvuru-evrak-desc">${escapeHtml(tip.aciklama)}</span>` : ''}
                </div>
                <input
                    type="file"
                    name="evrak[${tip.id}]"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                    required
                >
            </div>
        `).join('');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    async function loadKursOzet(kursId) {
        if (!kursId || !ozetUrlTemplate) {
            setOzet(null);
            return;
        }

        const url = ozetUrlTemplate.replace('__ID__', encodeURIComponent(kursId));
        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Kurs özeti alınamadı');
            }
            setOzet(data);
        } catch (error) {
            setOzet(null);
            showToast(error.message || 'Kurs özeti alınamadı', 'error');
        }
    }

    function goTo(next) {
        const target = Math.min(Math.max(1, next), maxStep);
        step = target;

        panels.forEach((panel) => {
            const n = Number(panel.getAttribute('data-basvuru-step-panel'));
            const active = n === step;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });

        stepBtns.forEach((btn) => {
            const n = Number(btn.getAttribute('data-basvuru-step-btn'));
            if (n > maxStep) {
                btn.hidden = true;
                return;
            }
            btn.hidden = false;
            btn.disabled = n > step && !(kursHidden?.value && n <= maxStep);
            btn.classList.toggle('is-active', n === step);
            btn.classList.toggle('is-done', n < step);
        });

        if (prevBtn) prevBtn.hidden = step === 1;
        if (nextBtn) nextBtn.hidden = step === maxStep;
        if (submitBtn) submitBtn.hidden = step !== maxStep;

        if (step === 3) {
            syncVeliRequired();
        }
    }

    function validateStep(current) {
        if (current === 1) {
            if (!kursHidden?.value) {
                showToast('Lütfen bir kurs seçin.', 'error');
                return false;
            }
            return true;
        }

        if (current === 2) {
            const tc = form.querySelector('#tc_kimlik_no')?.value?.trim() || '';
            const dogum = form.querySelector('#dogum_tarihi')?.value?.trim() || '';
            const ad = form.querySelector('#ad')?.value?.trim() || '';
            const soyad = form.querySelector('#soyad')?.value?.trim() || '';
            const cinsiyet = form.querySelector('#cinsiyet')?.value?.trim() || '';
            const il = form.querySelector('#il')?.value?.trim() || '';
            const ilce = form.querySelector('#ilce')?.value?.trim() || '';
            const adres = form.querySelector('#adres')?.value?.trim() || '';

            if (!/^\d{11}$/.test(tc)) {
                showToast('TC Kimlik No 11 haneli olmalıdır.', 'error');
                return false;
            }
            if (!dogum || !ad || !soyad || !il || !ilce || !adres) {
                showToast('Katılımcı ve adres bilgilerini tamamlayın.', 'error');
                return false;
            }

            const kosulHata = validateKursKosullari({ dogum, cinsiyet, il, ilce });
            if (kosulHata) {
                showToast(kosulHata, 'error');
                return false;
            }

            return true;
        }

        if (current === 3) {
            const required = syncVeliRequired();
            const veliTc = form.querySelector('#veli_tc_kimlik_no')?.value?.trim() || '';
            const veliDogum = form.querySelector('#veli_dogum_tarihi')?.value?.trim() || '';
            const veliAd = form.querySelector('#veli_ad')?.value?.trim() || '';
            const veliSoyad = form.querySelector('#veli_soyad')?.value?.trim() || '';
            const anyVeli = veliTc || veliDogum || veliAd || veliSoyad;

            if (required || anyVeli) {
                if (!/^\d{11}$/.test(veliTc)) {
                    showToast('Veli TC Kimlik No 11 haneli olmalıdır.', 'error');
                    return false;
                }
                if (!veliDogum || !veliAd || !veliSoyad) {
                    showToast('Veli bilgilerini tamamlayın.', 'error');
                    return false;
                }
                const kisiTc = form.querySelector('#tc_kimlik_no')?.value?.trim() || '';
                if (veliTc === kisiTc) {
                    showToast('Veli TC Kimlik No katılımcıdan farklı olmalıdır.', 'error');
                    return false;
                }
            }
            return true;
        }

        if (current === 4 && hasEvrak) {
            const files = [...evrakList.querySelectorAll('input[type="file"]')];
            if (files.some((input) => !input.files?.length)) {
                showToast('Tüm zorunlu evrakları yükleyin.', 'error');
                return false;
            }
        }

        return true;
    }

    function onKursChanged() {
        loadKursOzet(kursHidden?.value || '');
        stepBtns.forEach((btn) => {
            const n = Number(btn.getAttribute('data-basvuru-step-btn'));
            if (n > 1 && n <= maxStep) btn.disabled = !kursHidden?.value;
        });
    }

    kursHidden?.addEventListener('change', onKursChanged);
    kursHidden?.addEventListener('input', onKursChanged);

    dogumInput?.addEventListener('change', syncVeliRequired);
    dogumInput?.addEventListener('input', syncVeliRequired);

    prevBtn?.addEventListener('click', () => goTo(step - 1));
    nextBtn?.addEventListener('click', () => {
        if (!validateStep(step)) return;
        goTo(step + 1);
    });

    stepBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const n = Number(btn.getAttribute('data-basvuru-step-btn'));
            if (n < step) {
                goTo(n);
                return;
            }
            // İleri gitmek için ara adımları doğrula
            for (let i = step; i < n; i += 1) {
                if (!validateStep(i)) return;
            }
            goTo(n);
        });
    });

    form.addEventListener('submit', (event) => {
        if (!validateStep(1) || !validateStep(2) || !validateStep(3) || (hasEvrak && !validateStep(4))) {
            event.preventDefault();
        }
    });

    // old() ile gelindiyse kurs özetini yükle
    if (kursHidden?.value) {
        loadKursOzet(kursHidden.value);
    }
    syncVeliRequired();
    goTo(1);
}
