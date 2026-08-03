import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (![...document.querySelectorAll('.confirm-modal')].some((el) => !el.hidden)) {
        document.body.classList.remove('modal-open');
    }
}

export function initEntegrasyonlarPage() {
    const root = document.querySelector('[data-entegrasyonlar]');
    if (!root) return;

    const form = root.querySelector('[data-entegrasyonlar-form]');
    if (!form) return;

    const canUpdate = !form.querySelector('[data-entegrasyon-tur-aktif]')?.disabled;

    function syncCard(card) {
        if (!card) return;

        const toggle = card.querySelector('[data-entegrasyon-tur-aktif]');
        const badge = card.querySelector('[data-entegrasyon-durum]');
        const switchLabel = toggle?.closest('.switch-label')?.querySelector('span:last-child');
        const radios = card.querySelectorAll('[data-entegrasyon-saglayici]');
        const aktif = Boolean(toggle?.checked);

        card.classList.toggle('is-pasif', !aktif);
        card.dataset.turAktif = aktif ? '1' : '0';

        if (switchLabel) {
            switchLabel.textContent = aktif ? 'Aktif' : 'Pasif';
        }

        radios.forEach((radio) => {
            radio.disabled = !aktif || !canUpdate;
            radio.required = aktif;
            const wrap = radio.closest('[data-entegrasyon-saglayici-wrap]');
            wrap?.classList.toggle('is-active', aktif && radio.checked);
        });

        if (!badge) return;

        if (!aktif) {
            badge.className = 'status status-hazirlik';
            badge.textContent = 'Pasif';
            return;
        }

        const selected = [...radios].find((radio) => radio.checked);
        const ad = selected?.closest('[data-entegrasyon-saglayici-wrap]')?.querySelector('.entegrasyon-saglayici-ad')?.textContent?.trim();
        badge.className = 'status status-aktif';
        badge.textContent = ad ? `Aktif: ${ad}` : 'Aktif';
    }

    function applyTurler(turler) {
        (turler || []).forEach((tur) => {
            const card = root.querySelector(`[data-entegrasyon-tur="${tur.tur}"]`);
            if (!card) return;

            const toggle = card.querySelector('[data-entegrasyon-tur-aktif]');
            if (toggle) {
                toggle.checked = Boolean(tur.tur_aktif);
            }

            const radios = card.querySelectorAll('[data-entegrasyon-saglayici]');
            radios.forEach((radio) => {
                radio.checked = radio.value === tur.aktif;
            });

            (tur.saglayicilar || []).forEach((saglayici) => {
                const modal = root.querySelector(
                    `[data-entegrasyon-ayar-modal][data-tur="${tur.tur}"][data-saglayici="${saglayici.kod}"]`
                );
                if (!modal) return;

                (saglayici.alanlar || []).forEach((alan) => {
                    const input = modal.querySelector(`[data-alan-kod="${alan.kod}"]`);
                    if (!input) return;

                    if (alan.gizli) {
                        if (input instanceof HTMLInputElement) {
                            input.value = '';
                            input.placeholder = alan.dolu
                                ? 'Kayıtlı değer korunacak (değiştirmek için yeni değer girin)'
                                : (alan.placeholder || '');
                        }
                        const hint = input.closest('.form-group')?.querySelector('[data-gizli-hint]');
                        if (hint) {
                            hint.hidden = !alan.dolu;
                        }
                    } else if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement) {
                        input.value = alan.deger ?? '';
                    }
                });
            });

            syncCard(card);
        });
    }

    form.querySelectorAll('[data-entegrasyon-tur]').forEach((card) => syncCard(card));

    form.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;

        if (input.matches('[data-entegrasyon-tur-aktif]')) {
            syncCard(input.closest('[data-entegrasyon-tur]'));
            return;
        }

        if (input.type === 'radio' && input.matches('[data-entegrasyon-saglayici]')) {
            syncCard(input.closest('[data-entegrasyon-tur]'));
        }
    });

    root.addEventListener('click', (event) => {
        const openBtn = event.target.closest('[data-entegrasyon-ayar-open]');
        if (openBtn) {
            const tur = openBtn.getAttribute('data-tur');
            const saglayici = openBtn.getAttribute('data-saglayici');
            const modal = root.querySelector(
                `[data-entegrasyon-ayar-modal][data-tur="${tur}"][data-saglayici="${saglayici}"]`
            );
            openModal(modal);
            return;
        }

        const closeBtn = event.target.closest('[data-entegrasyon-ayar-close]');
        if (closeBtn) {
            closeModal(closeBtn.closest('[data-entegrasyon-ayar-modal]'));
        }
    });

    root.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const open = [...root.querySelectorAll('[data-entegrasyon-ayar-modal]')].find((el) => !el.hidden);
        if (open) {
            closeModal(open);
        }
    });

    root.querySelectorAll('[data-entegrasyon-ayar-form]').forEach((ayarForm) => {
        ayarForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const modal = ayarForm.closest('[data-entegrasyon-ayar-modal]');
            const saveBtn = ayarForm.querySelector('[data-entegrasyon-ayar-save]');
            const previousLabel = saveBtn?.textContent;
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Kaydediliyor...';
            }

            try {
                const response = await fetch(ayarForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: new FormData(ayarForm),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const firstError = data?.errors
                        ? Object.values(data.errors).flat()[0]
                        : data?.message;
                    throw new Error(firstError || 'Kayıt başarısız');
                }

                showToast(data.message || 'Sağlayıcı ayarları kaydedildi.', 'success');
                applyTurler(data.turler);
                closeModal(modal);
            } catch (error) {
                showToast(error.message || 'Kayıt başarısız', 'error');
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = previousLabel || 'Kaydet';
                }
            }
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.querySelector('.btn-cta-text')?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            const label = submitBtn.querySelector('.btn-cta-text');
            if (label) label.textContent = 'Kaydediliyor...';
        }

        try {
            const body = new FormData(form);
            form.querySelectorAll('[data-entegrasyon-tur]').forEach((card) => {
                const tur = card.getAttribute('data-entegrasyon-tur');
                const aktif = card.querySelector('[data-entegrasyon-tur-aktif]')?.checked;
                if (!tur || aktif) return;

                const selected = card.querySelector('[data-entegrasyon-saglayici]:checked');
                if (selected && !body.has(`aktif[${tur}]`)) {
                    body.set(`aktif[${tur}]`, selected.value);
                }
            });

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data?.errors
                    ? Object.values(data.errors).flat()[0]
                    : data?.message;
                throw new Error(firstError || 'Kayıt başarısız');
            }

            showToast(data.message || 'Entegrasyon ayarları kaydedildi.', 'success');
            applyTurler(data.turler);
        } catch (error) {
            showToast(error.message || 'Kayıt başarısız', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                const label = submitBtn.querySelector('.btn-cta-text');
                if (label) label.textContent = previousLabel || 'Ayarları Kaydet';
            }
        }
    });
}
