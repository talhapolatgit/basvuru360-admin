import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function validationMessage(error) {
    const data = error?.response?.data;
    if (!data) {
        return 'İşlem sırasında bir hata oluştu.';
    }
    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    if (typeof data.message === 'string' && data.message) {
        return data.message;
    }
    return 'İşlem sırasında bir hata oluştu.';
}

function setModalOpen(modal, open) {
    if (!modal) return;
    modal.hidden = !open;
    if (open) {
        document.body.classList.add('modal-open');
        return;
    }
    if (![...document.querySelectorAll('.confirm-modal')].some((el) => !el.hidden)) {
        document.body.classList.remove('modal-open');
    }
}

function optionRow(value = '') {
    const row = document.createElement('div');
    row.className = 'kres-soru-secenek-row';
    row.dataset.secenekRow = '1';
    row.innerHTML = `
        <input type="text" name="secenekler[]" class="form-control" value="${String(value).replaceAll('"', '&quot;')}" placeholder="Seçenek" autocomplete="off">
        <button type="button" class="kres-soru-icon-btn" data-secenek-up title="Yukarı" aria-label="Yukarı">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
        </button>
        <button type="button" class="kres-soru-icon-btn" data-secenek-down title="Aşağı" aria-label="Aşağı">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <button type="button" class="kres-soru-icon-btn kres-soru-icon-btn--danger" data-secenek-sil title="Sil" aria-label="Sil">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        </button>
    `;
    return row;
}

export function initKresSoruFormuShowPage() {
    const root = document.querySelector('[data-kres-soru-builder]');
    if (!root) return;

    const modal = document.getElementById('kres-soru-modal');
    const form = modal?.querySelector('[data-soru-form]');
    const tipSelect = form?.querySelector('[data-soru-tip]');
    const alanlar = form?.querySelector('[data-soru-alanlari]');
    const secenekAlani = form?.querySelector('[data-secenek-alani]');
    const secenekList = form?.querySelector('[data-secenek-list]');
    const kaydetBtn = form?.querySelector('[data-soru-kaydet]');
    const title = modal?.querySelector('[data-soru-modal-title]');
    const storeUrl = root.dataset.storeUrl;
    const siraUrl = root.dataset.siraUrl;
    const secenekTipleri = (() => {
        try {
            return JSON.parse(root.dataset.secenekTipleri || '[]');
        } catch {
            return [];
        }
    })();

    const list = root.querySelector('[data-soru-list]');

    function htmlToCard(html) {
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        return wrap.querySelector('[data-soru-card]');
    }

    function reindexCards() {
        list?.querySelectorAll('[data-soru-index]').forEach((el, index) => {
            el.textContent = String(index + 1);
        });
    }

    function syncEmptyState() {
        const empty = list?.querySelector('[data-soru-empty]');
        if (!empty) return;
        empty.hidden = list.querySelectorAll('[data-soru-card]').length > 0;
    }

    function upsertCard(html, id) {
        if (!list || !html) return;
        const card = htmlToCard(html);
        if (!card) return;

        const existing = list.querySelector(`[data-soru-card][data-soru-id="${id}"]`);
        if (existing) {
            existing.replaceWith(card);
        } else {
            const empty = list.querySelector('[data-soru-empty]');
            if (empty) {
                list.insertBefore(card, empty);
            } else {
                list.append(card);
            }
        }

        syncEmptyState();
        reindexCards();
    }

    function removeCard(card) {
        card.remove();
        syncEmptyState();
        reindexCards();
    }

    if (!modal || !form || !tipSelect) return;

    const tipSecenekGerekli = () => secenekTipleri.includes(tipSelect.value);

    const syncTip = () => {
        const selected = Boolean(tipSelect.value);
        alanlar.hidden = !selected;
        kaydetBtn.disabled = !selected;
        const baslik = form.querySelector('[data-soru-baslik]');
        if (baslik) {
            baslik.required = selected;
        }
        const needOptions = tipSecenekGerekli();
        secenekAlani.hidden = !needOptions;
        if (needOptions && secenekList.children.length === 0) {
            secenekList.append(optionRow(), optionRow());
        }
        const isSayi = tipSelect.value === 'sayi';
        const isCheckbox = tipSelect.value === 'checkbox';
        const sayiAlani = form.querySelector('[data-sayi-alani]');
        const tamSayiAlani = form.querySelector('[data-tam-sayi-alani]');
        const secimHint = form.querySelector('[data-secim-adet-hint]');
        const minInput = form.querySelector('[data-soru-min-deger]');
        const maxInput = form.querySelector('[data-soru-max-deger]');
        const minLabel = form.querySelector('[data-min-label]');
        const maxLabel = form.querySelector('[data-max-label]');
        if (sayiAlani) sayiAlani.hidden = !(isSayi || isCheckbox);
        if (tamSayiAlani) tamSayiAlani.hidden = !isSayi;
        if (secimHint) secimHint.hidden = !isCheckbox;
        if (minLabel) minLabel.textContent = isCheckbox ? 'Minimum seçim' : 'Minimum';
        if (maxLabel) maxLabel.textContent = isCheckbox ? 'Maksimum seçim' : 'Maksimum';
        if (minInput) {
            minInput.step = isCheckbox ? '1' : 'any';
            minInput.min = isCheckbox ? '0' : '';
        }
        if (maxInput) {
            maxInput.step = isCheckbox ? '1' : 'any';
            maxInput.min = isCheckbox ? '1' : '';
        }
        if (!isSayi) {
            const tamSayi = form.querySelector('[data-soru-tam-sayi]');
            if (tamSayi) tamSayi.checked = false;
        }
        if (!isSayi && !isCheckbox) {
            if (minInput) minInput.value = '';
            if (maxInput) maxInput.value = '';
        }
    };

    const resetForm = () => {
        form.reset();
        form.action = storeUrl;
        form.querySelector('[data-method-field]')?.remove();
        secenekList.innerHTML = '';
        if (title) title.textContent = 'Yeni Soru';
        syncTip();
    };

    const openModal = () => setModalOpen(modal, true);
    const closeModal = () => setModalOpen(modal, false);

    tipSelect.addEventListener('change', syncTip);

    form.querySelector('[data-secenek-ekle]')?.addEventListener('click', () => {
        secenekList.append(optionRow());
    });

    secenekList.addEventListener('click', (event) => {
        const row = event.target.closest('[data-secenek-row]');
        if (!row) return;

        if (event.target.closest('[data-secenek-sil]')) {
            if (secenekList.children.length <= 1) {
                row.querySelector('input').value = '';
                return;
            }
            row.remove();
            return;
        }

        if (event.target.closest('[data-secenek-up]') && row.previousElementSibling) {
            secenekList.insertBefore(row, row.previousElementSibling);
        }

        if (event.target.closest('[data-secenek-down]') && row.nextElementSibling) {
            secenekList.insertBefore(row.nextElementSibling, row);
        }
    });

    document.querySelector('[data-soru-ekle]')?.addEventListener('click', () => {
        resetForm();
        openModal();
        tipSelect.focus();
    });

    modal.querySelectorAll('[data-soru-modal-close]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('click', (event) => {
        const card = event.target.closest('[data-soru-card]');
        if (!card || !root.contains(card)) return;

        if (event.target.closest('[data-soru-duzenle]')) {
            resetForm();
            form.action = card.dataset.updateUrl;
            let methodField = form.querySelector('[data-method-field]');
            if (!methodField) {
                methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.setAttribute('data-method-field', '');
                form.prepend(methodField);
            }
            methodField.value = 'PUT';
            tipSelect.value = card.dataset.tip || '';
            form.querySelector('[data-soru-baslik]').value = card.dataset.baslik || '';
            form.querySelector('[data-soru-aciklama]').value = card.dataset.aciklama || '';
            form.querySelector('[data-soru-zorunlu]').checked = card.dataset.zorunlu === '1';
            form.querySelector('[data-soru-tam-sayi]').checked = card.dataset.tamSayi === '1';
            form.querySelector('[data-soru-min-deger]').value = card.dataset.minDeger || '';
            form.querySelector('[data-soru-max-deger]').value = card.dataset.maxDeger || '';
            secenekList.innerHTML = '';
            let options = [];
            try {
                options = JSON.parse(card.dataset.secenekler || '[]');
            } catch {
                options = [];
            }
            if (Array.isArray(options) && options.length) {
                options.forEach((etiket) => secenekList.append(optionRow(etiket)));
            }
            if (title) title.textContent = 'Soruyu Düzenle';
            syncTip();
            openModal();
            return;
        }

        if (event.target.closest('[data-soru-sil]')) {
            const ad = card.dataset.baslik || 'bu soru';
            if (!window.confirm(`"${ad}" sorusunu silmek istiyor musunuz?`)) {
                return;
            }
            window.axios.delete(card.dataset.deleteUrl, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            }).then((response) => {
                showToast(response.data.message || 'Soru silindi.', 'success');
                removeCard(card);
            }).catch((error) => {
                showToast(validationMessage(error), 'error');
            });
        }
    });

    async function saveOrder() {
        const ids = [...root.querySelectorAll('[data-soru-card]')].map((card) => Number(card.dataset.soruId));
        if (ids.length < 2) return;
        try {
            const { data } = await window.axios.put(siraUrl, { sira: ids }, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            root.querySelectorAll('[data-soru-index]').forEach((el, index) => {
                el.textContent = String(index + 1);
            });
            showToast(data.message || 'Soru sırası güncellendi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        }
    }

    function dragAfterCard(list, y, dragging) {
        return [...list.querySelectorAll('[data-soru-card]')]
            .filter((child) => child !== dragging)
            .reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                }
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    function initSoruDrag(list) {
        if (!list) return;

        list.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            if (event.target.closest('.kres-soru-card__actions, [data-soru-duzenle], [data-soru-sil]')) {
                return;
            }

            const card = event.target.closest('[data-soru-card]');
            if (!card || !list.contains(card)) return;
            if (list.querySelectorAll('[data-soru-card]').length < 2) return;

            const startX = event.clientX;
            const startY = event.clientY;
            const origin = card.getBoundingClientRect();
            const startOrder = [...list.querySelectorAll('[data-soru-card]')];
            let dragging = false;
            let placeholder = null;

            const onMove = (moveEvent) => {
                const dx = moveEvent.clientX - startX;
                const dy = moveEvent.clientY - startY;

                if (!dragging) {
                    if (Math.abs(dx) < 5 && Math.abs(dy) < 5) {
                        return;
                    }

                    dragging = true;
                    placeholder = document.createElement('div');
                    placeholder.className = 'kres-soru-placeholder';
                    placeholder.style.height = `${origin.height}px`;
                    card.after(placeholder);
                    card.classList.add('is-dragging');
                    card.style.width = `${origin.width}px`;
                    card.style.left = `${origin.left}px`;
                    card.style.top = `${origin.top}px`;
                    document.body.classList.add('is-kres-soru-dragging');
                }

                moveEvent.preventDefault();
                card.style.top = `${origin.top + (moveEvent.clientY - startY)}px`;
                card.style.left = `${origin.left + (moveEvent.clientX - startX)}px`;

                const after = dragAfterCard(list, moveEvent.clientY, card);
                if (after) {
                    list.insertBefore(placeholder, after);
                } else {
                    list.appendChild(placeholder);
                }
            };

            const onUp = async () => {
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onUp);
                window.removeEventListener('pointercancel', onUp);

                if (!dragging) {
                    return;
                }

                placeholder.replaceWith(card);
                card.classList.remove('is-dragging');
                card.style.width = '';
                card.style.left = '';
                card.style.top = '';
                document.body.classList.remove('is-kres-soru-dragging');

                const next = [...list.querySelectorAll('[data-soru-card]')];
                if (startOrder.some((el, index) => el !== next[index])) {
                    await saveOrder();
                }
            };

            window.addEventListener('pointermove', onMove, { passive: false });
            window.addEventListener('pointerup', onUp);
            window.addEventListener('pointercancel', onUp);
        });
    }

    initSoruDrag(root.querySelector('[data-soru-list]'));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!tipSelect.value) {
            showToast('Önce soru tipi seçin.', 'error');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Kaydediliyor...';
        }

        try {
            const formData = new FormData(form);
            const { data } = await window.axios.post(form.action, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            closeModal();
            showToast(data.message || 'Soru kaydedildi.', 'success');
            upsertCard(data.html, data.id);
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = previousLabel || 'Kaydet';
            }
        }
    });
}

function parseSecimSiniri(value) {
    if (value === undefined || value === null || String(value).trim() === '') {
        return null;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

function checkboxGroupBoxes(group) {
    return [...group.querySelectorAll('input[type="checkbox"]')];
}

function syncCheckboxSecimSinirlari(group) {
    const max = parseSecimSiniri(group.dataset.maxSecim);
    const boxes = checkboxGroupBoxes(group);
    const selectedCount = boxes.filter((box) => box.checked).length;

    boxes.forEach((box) => {
        box.disabled = !box.checked && max != null && selectedCount >= max;
    });
}

function syncPreviewCheckboxLimits(root) {
    root?.querySelectorAll('[data-checkbox-secim]').forEach((group) => {
        syncCheckboxSecimSinirlari(group);
    });
}

function formatCepTelefonu(raw) {
    let digits = String(raw || '').replace(/\D/g, '');

    if (digits.startsWith('00')) {
        digits = digits.slice(2);
    }
    if (digits.startsWith('90')) {
        digits = digits.slice(2);
    }
    if (digits.startsWith('5')) {
        digits = `0${digits}`;
    }
    if (digits.length >= 2 && digits[1] !== '5') {
        digits = `05${digits.slice(2)}`;
    }
    if (digits.length === 1 && digits !== '0') {
        digits = '0';
    }

    digits = digits.slice(0, 11);

    const parts = [digits.slice(0, 4), digits.slice(4, 7), digits.slice(7, 9), digits.slice(9, 11)].filter(Boolean);

    return parts.join(' ');
}

function bindPreviewCepTelefonu(root) {
    if (!root || root.dataset.cepTelefonuBound === '1') {
        return;
    }

    root.dataset.cepTelefonuBound = '1';

    root.addEventListener('input', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.matches('[data-cep-telefonu]')) {
            return;
        }

        input.value = formatCepTelefonu(input.value);
    });

    root.addEventListener('blur', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.matches('[data-cep-telefonu]')) {
            return;
        }

        const formatted = formatCepTelefonu(input.value);
        input.value = formatted;
        const digitCount = formatted.replace(/\D/g, '').length;
        if (digitCount > 0 && digitCount !== 11) {
            showToast('Cep telefonunu 05xx xxx xx xx formatında girin.', 'error');
        }
    }, true);
}

function bindPreviewCheckboxLimits(root) {
    if (!root || root.dataset.checkboxSecimBound === '1') {
        return;
    }

    root.dataset.checkboxSecimBound = '1';

    root.addEventListener('click', (event) => {
        const input =
            event.target.closest('input[type="checkbox"]') ||
            event.target.closest('label')?.querySelector('input[type="checkbox"]');
        if (!input || !root.contains(input)) {
            return;
        }

        const group = input.closest('[data-checkbox-secim]');
        if (!group) {
            return;
        }

        const max = parseSecimSiniri(group.dataset.maxSecim);
        const selectedCount = checkboxGroupBoxes(group).filter((box) => box.checked).length;

        if (!input.checked && max != null && selectedCount >= max) {
            event.preventDefault();
            showToast(`En fazla ${max} seçim yapabilirsiniz.`, 'error');
        }
    });

    root.addEventListener('change', (event) => {
        const input = event.target;
        if (input.type !== 'checkbox') {
            return;
        }

        const group = input.closest('[data-checkbox-secim]');
        if (!group) {
            return;
        }

        const min = parseSecimSiniri(group.dataset.minSecim);
        const max = parseSecimSiniri(group.dataset.maxSecim);
        const selectedCount = checkboxGroupBoxes(group).filter((box) => box.checked).length;

        if (input.checked && max != null && selectedCount > max) {
            input.checked = false;
            showToast(`En fazla ${max} seçim yapabilirsiniz.`, 'error');
        } else if (!input.checked && min != null && selectedCount < min && selectedCount + 1 >= min) {
            showToast(`En az ${min} seçim yapmalısınız.`, 'error');
        }

        syncCheckboxSecimSinirlari(group);
    });
}

export function initKresSoruOnizleme() {
    const modal = document.getElementById('kres-soru-onizleme-modal');
    if (!modal) return;

    const body = modal.querySelector('[data-soru-onizleme-govde]');
    const title = modal.querySelector('[data-soru-onizleme-title]');
    const staticHtml = body?.innerHTML || '';

    bindPreviewCheckboxLimits(body);
    bindPreviewCepTelefonu(body);
    syncPreviewCheckboxLimits(body);

    modal.querySelectorAll('[data-soru-onizleme-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(modal, false));
    });

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-soru-onizle]');
        if (!btn) return;

        event.preventDefault();
        const url = btn.dataset.onizlemeUrl;
        if (title && btn.dataset.ad) {
            title.textContent = `${btn.dataset.ad} — Önizleme`;
        }

        if (url && body) {
            body.innerHTML = '<p class="form-hint">Önizleme yükleniyor…</p>';
            setModalOpen(modal, true);
            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'text/html',
                    },
                });
                if (!response.ok) {
                    throw new Error('Önizleme yüklenemedi');
                }
                body.innerHTML = await response.text();
                syncPreviewCheckboxLimits(body);
            } catch (error) {
                body.innerHTML = staticHtml;
                setModalOpen(modal, false);
                showToast(error.message || 'Önizleme yüklenemedi.', 'error');
            }
            return;
        }

        setModalOpen(modal, true);
    });
}

export function initKresSoruFormlariDelete() {
    if (!document.getElementById('kres-soru-formlari-table')) return;

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-form-delete]');
        if (!btn) return;

        event.preventDefault();
        const ad = btn.dataset.ad || 'bu formu';
        if (!window.confirm(`"${ad}" soru formunu silmek istiyor musunuz?`)) {
            return;
        }

        try {
            const { data } = await window.axios.delete(btn.dataset.deleteUrl, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            showToast(data.message || 'Form silindi.', 'success');
            window.location.reload();
        } catch (error) {
            showToast(validationMessage(error), 'error');
        }
    });
}
