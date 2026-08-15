import { showToast } from './toast';
import {
    applyColumnOrder,
    applyTablePreferences,
    applyVisibility,
    clearColumnPrefs,
    currentOrder,
    currentVisible,
    initColumnDrag,
    initSorting,
    initVisibilityToggles,
    markClean,
    markDirty,
    saveColumnPrefs,
    syncColumnDropdownOrder,
} from './table-columns';

const BASVURU_COOKIE_KEY = 'kres_basvuru_table_prefs';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function personalize(template, adSoyad) {
    return String(template || '').replaceAll('{ad_soyad}', adSoyad || '');
}

function insertToken(input, token) {
    if (!input || !token) return;
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    input.value = input.value.slice(0, start) + token + input.value.slice(end);
    const cursor = start + token.length;
    input.focus();
    input.setSelectionRange(cursor, cursor);
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function validationMessage(error) {
    const data = error?.response?.data;
    if (data?.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    if (typeof data?.message === 'string' && data.message) {
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

function bindBasvuruColumnSearch(table) {
    if (!table) return;
    const inputs = table.querySelectorAll('[data-column-search]');
    const rows = () => Array.from(table.querySelectorAll('tbody tr')).filter((row) => row.querySelector('td[data-column]'));

    const apply = () => {
        const filters = Array.from(inputs)
            .map((input) => ({
                key: input.dataset.columnSearch,
                value: (input.value || '').trim().toLocaleLowerCase('tr-TR'),
            }))
            .filter((f) => f.value !== '');

        rows().forEach((row) => {
            const ok = filters.every((f) => {
                const cell = row.querySelector(`td[data-column="${f.key}"]`);
                const text = (cell?.textContent || '').trim().toLocaleLowerCase('tr-TR');
                return text.includes(f.value);
            });
            row.style.display = ok ? '' : 'none';
        });
    };

    inputs.forEach((input) => {
        input.addEventListener('input', apply);
    });
}

function formatNumber(value) {
    return new Intl.NumberFormat('tr-TR').format(Number(value) || 0);
}

function syncYedek(root) {
    const select = root.querySelector('[data-yedek-toggle]');
    const field = root.querySelector('[data-yedek-field]');
    if (!select || !field) return;
    const opt = select.selectedOptions[0];
    const isYedek = opt && opt.dataset.kod === 'yedek';
    field.hidden = !isYedek;
    if (!isYedek) {
        const input = field.querySelector('input');
        if (input) input.value = '';
    }
}

function wireKisiSearch(input, araUrl) {
    const targetId = input.dataset.kisiTarget;
    const labelKey = input.dataset.kisiLabel;
    const wrap = input.closest('.form-group');
    const hidden = document.getElementById(targetId);
    const picked = wrap?.querySelector(`[data-kisi-picked="${labelKey}"]`);
    const results = wrap?.querySelector('[data-kisi-results]');
    if (!hidden || !results) return;

    let timer = null;
    const clearPick = () => {
        hidden.value = '';
        if (picked) {
            picked.textContent = labelKey === 'kisi_label' ? 'Öğrenci seçilmedi' : 'Seçilmedi';
        }
    };

    input.addEventListener('input', () => {
        clearPick();
        const q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 2) {
            results.hidden = true;
            results.innerHTML = '';
            return;
        }
        timer = setTimeout(async () => {
            try {
                const res = await fetch(`${araUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                results.innerHTML = '';
                (data.items || []).forEach((item) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'kres-kisi-result';
                    btn.textContent = item.label;
                    btn.addEventListener('click', () => {
                        hidden.value = item.id;
                        if (picked) picked.textContent = item.label;
                        input.value = item.tam_adi;
                        results.hidden = true;
                    });
                    results.appendChild(btn);
                });
                results.hidden = !(data.items || []).length;
            } catch {
                results.hidden = true;
            }
        }, 250);
    });
}

export function initKresGrupDetailPage() {
    const root = document.querySelector('[data-kres-grup-page]');
    if (!root) return;

    const panel = root.querySelector('[data-basvuru-panel]');
    const araUrl = root.dataset.kisiAraUrl || '';

    // Modals (create basvuru / durum)
    root.querySelectorAll('[data-kres-modal-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.getAttribute('data-kres-modal-open');
            const map = {
                'basvuru-create': 'kres-basvuru-create-modal',
            };
            const modal = document.getElementById(map[key]);
            if (!modal) return;
            modal.hidden = false;
            document.body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('.confirm-modal').forEach((modal) => {
        modal.querySelectorAll('[data-kres-modal-close]').forEach((el) => {
            el.addEventListener('click', () => {
                modal.hidden = true;
                if (![...document.querySelectorAll('.confirm-modal')].some((m) => !m.hidden)) {
                    document.body.classList.remove('modal-open');
                }
            });
        });
    });

    document.querySelectorAll('[data-yedek-toggle]').forEach((sel) => {
        sel.addEventListener('change', () => syncYedek(sel.closest('form')));
        syncYedek(sel.closest('form'));
    });

    document.querySelectorAll('[data-kisi-search]').forEach((input) => wireKisiSearch(input, araUrl));

    if (!panel) return;

    const url = panel.dataset.basvuruUrl;
    const content = panel.querySelector('[data-basvuru-content]');
    const totalEl = panel.querySelector('[data-basvuru-total]');
    const filters = panel.querySelectorAll('[data-basvuru-filter]');
    const excelLink = panel.querySelector('[data-basvuru-excel]');
    const excelBaseHref = excelLink?.getAttribute('href') || '';
    const columnToggle = panel.querySelector('[data-basvuru-column-toggle]');
    const columnPicker = panel.querySelector('[data-basvuru-column-picker]');
    const columnDropdown = panel.querySelector('#basvuruColumnDropdown');
    const columnResetBtn = panel.querySelector('[data-basvuru-column-reset]');
    const columnSaveBtn = panel.querySelector('[data-basvuru-column-save]');
    const durumModal = document.getElementById('kres-basvuru-durum-modal');

    if (!url || !content) return;

    let currentDurum = panel.dataset.basvuruDurum || 'tumu';
    let currentSort = '';
    let currentDirection = 'desc';
    let currentPage = Number(new URL(window.location.href).searchParams.get('page') || '1') || 1;
    let loading = false;
    let requestId = 0;

    function setColumnPickerOpen(open) {
        columnDropdown?.classList.toggle('open', open);
        columnPicker?.classList.toggle('is-open', open);
        columnToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function setActiveFilter(durum) {
        currentDurum = durum;
        panel.dataset.basvuruDurum = durum;
        filters.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.basvuruFilter === durum);
        });
    }

    function updateUrl(durum, page = null) {
        const pageUrl = new URL(window.location.href);
        if (durum && durum !== 'tumu') {
            pageUrl.searchParams.set('basvuru_durum', durum);
        } else {
            pageUrl.searchParams.delete('basvuru_durum');
        }
        if (currentSort) {
            pageUrl.searchParams.set('sort', currentSort);
            pageUrl.searchParams.set('direction', currentDirection);
        } else {
            pageUrl.searchParams.delete('sort');
            pageUrl.searchParams.delete('direction');
        }
        if (page && Number(page) > 1) {
            pageUrl.searchParams.set('page', String(page));
        } else {
            pageUrl.searchParams.delete('page');
        }
        history.replaceState({}, '', pageUrl.pathname + pageUrl.search);
    }

    function updateExcelLink() {
        if (!excelLink || !excelBaseHref) return;
        const exportUrl = new URL(excelBaseHref, window.location.origin);
        exportUrl.search = '';
        if (currentDurum && currentDurum !== 'tumu') {
            exportUrl.searchParams.set('basvuru_durum', currentDurum);
        }
        if (currentSort) {
            exportUrl.searchParams.set('sort', currentSort);
            exportUrl.searchParams.set('direction', currentDirection);
        }
        excelLink.href = exportUrl.pathname + exportUrl.search;
    }

    function pinIslemlerPrefs(prefs) {
        const order = (prefs.order || []).filter((key) => key !== 'islemler');
        order.push('islemler');
        const visible = (prefs.visible || []).filter((key) => key !== 'islemler');
        visible.push('islemler');
        return { order, visible };
    }

    function closeRowMenus() {
        document.querySelectorAll('[data-row-actions].is-open').forEach((wrap) => {
            wrap.classList.remove('is-open');
            wrap.querySelector('[data-action-toggle]')?.setAttribute('aria-expanded', 'false');
            const dropdown = wrap.querySelector('[data-action-dropdown]');
            if (dropdown) {
                dropdown.hidden = true;
                dropdown.classList.remove('is-dropup');
                dropdown.style.top = '';
                dropdown.style.bottom = '';
                dropdown.style.left = '';
                dropdown.style.right = '';
                dropdown.style.position = '';
            }
        });
        document.querySelectorAll('.table-wrapper.has-open-action-menu').forEach((el) => {
            el.classList.remove('has-open-action-menu');
        });
    }

    function openDurumModal(btn) {
        if (!durumModal) return;
        const form = durumModal.querySelector('[data-durum-form]');
        if (!form) return;
        closeRowMenus();
        form.action = btn.dataset.url;
        form.durum_id.value = btn.dataset.durumId || '';
        form.yedek_sira.value = btn.dataset.yedekSira || '';
        durumModal.querySelector('[data-durum-kisi]').textContent = btn.dataset.kisi || '';
        syncYedek(form);
        setModalOpen(durumModal, true);
    }

    const smsModal = document.getElementById('kres-basvuru-sms-modal');
    const epostaModal = document.getElementById('kres-basvuru-eposta-modal');
    const smsOnizlemeModal = document.getElementById('kres-sms-onizleme-modal');
    const epostaOnizlemeModal = document.getElementById('kres-eposta-onizleme-modal');
    let mesajAlici = { ad: '', rol: '', sendUrl: '', telefonVar: false, emailVar: false, email: '' };
    let mesajGonderiliyor = false;

    function aliciEtiket() {
        const rol = mesajAlici.rol === 'veli' ? 'veli' : 'öğrenci';
        return `${mesajAlici.ad || 'Bu kişi'} (${rol})`;
    }

    smsModal?.querySelectorAll('[data-kres-sms-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(smsModal, false));
    });
    epostaModal?.querySelectorAll('[data-kres-eposta-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(epostaModal, false));
    });
    smsOnizlemeModal?.querySelectorAll('[data-kres-sms-onizleme-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(smsOnizlemeModal, false));
    });
    epostaOnizlemeModal?.querySelectorAll('[data-kres-eposta-onizleme-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(epostaOnizlemeModal, false));
    });

    const smsMesaj = smsModal?.querySelector('[data-kres-sms-mesaj]');
    const smsChar = smsModal?.querySelector('[data-kres-sms-char-count]');
    smsMesaj?.addEventListener('input', () => {
        if (smsChar) smsChar.textContent = String(smsMesaj.value.length);
    });
    smsModal?.querySelector('[data-kres-sms-insert]')?.addEventListener('click', (event) => {
        insertToken(smsMesaj, event.currentTarget.getAttribute('data-kres-sms-insert') || '');
    });
    smsModal?.querySelector('[data-kres-sms-onizle]')?.addEventListener('click', () => {
        const mesaj = smsMesaj?.value?.trim() || '';
        if (!mesaj) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            smsMesaj?.focus();
            return;
        }
        const alici = smsOnizlemeModal?.querySelector('[data-kres-sms-onizleme-alici]');
        const bubble = smsOnizlemeModal?.querySelector('[data-kres-sms-onizleme-mesaj]');
        if (alici) alici.innerHTML = `<strong>${escapeHtml(aliciEtiket())}</strong>`;
        if (bubble) bubble.textContent = personalize(mesaj, mesajAlici.ad || 'Ad Soyad');
        setModalOpen(smsOnizlemeModal, true);
    });
    smsModal?.querySelector('[data-kres-sms-send]')?.addEventListener('click', async () => {
        const mesaj = smsMesaj?.value?.trim() || '';
        const sendBtn = smsModal.querySelector('[data-kres-sms-send]');
        if (!mesaj) {
            showToast('SMS metni zorunludur.', 'error');
            smsMesaj?.focus();
            return;
        }
        if (!mesajAlici.telefonVar) {
            showToast('Bu kişi için kayıtlı telefon numarası bulunamadı.', 'error');
            return;
        }
        if (!mesajAlici.sendUrl || mesajGonderiliyor) return;
        mesajGonderiliyor = true;
        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesaj', mesaj);
            const { data } = await window.axios.post(mesajAlici.sendUrl, formData, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            setModalOpen(smsModal, false);
            showToast(data.message || 'SMS gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            mesajGonderiliyor = false;
            sendBtn.disabled = false;
        }
    });

    const epostaKonu = epostaModal?.querySelector('[data-kres-eposta-konu]');
    const epostaMesaj = epostaModal?.querySelector('[data-kres-eposta-mesaj]');
    const epostaChar = epostaModal?.querySelector('[data-kres-eposta-char-count]');
    epostaMesaj?.addEventListener('input', () => {
        if (epostaChar) epostaChar.textContent = String(epostaMesaj.value.length);
    });
    epostaModal?.querySelectorAll('[data-kres-eposta-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const token = btn.getAttribute('data-kres-eposta-insert') || '';
            const target = btn.getAttribute('data-kres-eposta-insert-target') || 'mesaj';
            insertToken(target === 'konu' ? epostaKonu : epostaMesaj, token);
        });
    });
    epostaModal?.querySelector('[data-kres-eposta-onizle]')?.addEventListener('click', () => {
        const konu = epostaKonu?.value?.trim() || '';
        const mesaj = epostaMesaj?.value?.trim() || '';
        if (!konu) {
            showToast('Önizleme için önce konu yazın.', 'error');
            epostaKonu?.focus();
            return;
        }
        if (!mesaj) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            epostaMesaj?.focus();
            return;
        }
        const alici = epostaOnizlemeModal?.querySelector('[data-kres-eposta-onizleme-alici]');
        const kime = epostaOnizlemeModal?.querySelector('[data-kres-eposta-onizleme-kime]');
        const konuEl = epostaOnizlemeModal?.querySelector('[data-kres-eposta-onizleme-konu]');
        const mesajEl = epostaOnizlemeModal?.querySelector('[data-kres-eposta-onizleme-mesaj]');
        if (alici) alici.innerHTML = `Alıcı: <strong>${escapeHtml(aliciEtiket())}</strong>`;
        if (kime) kime.textContent = mesajAlici.email || 'E-posta yok';
        if (konuEl) konuEl.textContent = personalize(konu, mesajAlici.ad || 'Ad Soyad');
        if (mesajEl) mesajEl.textContent = personalize(mesaj, mesajAlici.ad || 'Ad Soyad');
        setModalOpen(epostaOnizlemeModal, true);
    });
    epostaModal?.querySelector('[data-kres-eposta-send]')?.addEventListener('click', async () => {
        const konu = epostaKonu?.value?.trim() || '';
        const mesaj = epostaMesaj?.value?.trim() || '';
        const sendBtn = epostaModal.querySelector('[data-kres-eposta-send]');
        if (!konu) {
            showToast('E-posta konusu zorunludur.', 'error');
            epostaKonu?.focus();
            return;
        }
        if (!mesaj) {
            showToast('E-posta metni zorunludur.', 'error');
            epostaMesaj?.focus();
            return;
        }
        if (!mesajAlici.emailVar) {
            showToast('Bu kişi için kayıtlı e-posta adresi bulunamadı.', 'error');
            return;
        }
        if (!mesajAlici.sendUrl || mesajGonderiliyor) return;
        mesajGonderiliyor = true;
        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('konu', konu);
            formData.append('mesaj', mesaj);
            const { data } = await window.axios.post(mesajAlici.sendUrl, formData, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            setModalOpen(epostaModal, false);
            showToast(data.message || 'E-posta gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            mesajGonderiliyor = false;
            sendBtn.disabled = false;
        }
    });

    panel.addEventListener('click', (event) => {
        const durumBtn = event.target.closest('[data-kres-durum-open]');
        if (durumBtn && panel.contains(durumBtn)) {
            event.preventDefault();
            openDurumModal(durumBtn);
            return;
        }

        const smsBtn = event.target.closest('[data-kres-sms-ac]');
        if (smsBtn && panel.contains(smsBtn)) {
            event.preventDefault();
            closeRowMenus();
            mesajAlici = {
                ad: smsBtn.dataset.ad || '',
                rol: smsBtn.dataset.rol || '',
                sendUrl: smsBtn.dataset.sendUrl || '',
                telefonVar: smsBtn.dataset.telefonVar === '1',
                emailVar: false,
                email: '',
            };
            const aliciEl = smsModal?.querySelector('[data-kres-sms-alici]');
            const noTel = smsModal?.querySelector('[data-kres-sms-no-telefon]');
            if (aliciEl) {
                aliciEl.innerHTML = `<strong>${escapeHtml(aliciEtiket())}</strong> için SMS gönderilecek.`;
            }
            if (noTel) noTel.hidden = mesajAlici.telefonVar;
            if (smsMesaj) smsMesaj.value = '';
            if (smsChar) smsChar.textContent = '0';
            setModalOpen(smsModal, true);
            smsMesaj?.focus();
            return;
        }

        const epostaBtn = event.target.closest('[data-kres-eposta-ac]');
        if (epostaBtn && panel.contains(epostaBtn)) {
            event.preventDefault();
            closeRowMenus();
            mesajAlici = {
                ad: epostaBtn.dataset.ad || '',
                rol: epostaBtn.dataset.rol || '',
                sendUrl: epostaBtn.dataset.sendUrl || '',
                telefonVar: false,
                emailVar: epostaBtn.dataset.emailVar === '1',
                email: epostaBtn.dataset.email || '',
            };
            const aliciEl = epostaModal?.querySelector('[data-kres-eposta-alici]');
            const noEmail = epostaModal?.querySelector('[data-kres-eposta-no-email]');
            if (aliciEl) {
                aliciEl.innerHTML = `<strong>${escapeHtml(aliciEtiket())}</strong> için e-posta gönderilecek.`;
            }
            if (noEmail) noEmail.hidden = mesajAlici.emailVar;
            if (epostaKonu) epostaKonu.value = '';
            if (epostaMesaj) epostaMesaj.value = '';
            if (epostaChar) epostaChar.textContent = '0';
            setModalOpen(epostaModal, true);
            epostaKonu?.focus();
        }
    });

    function bindTableInteractions() {
        const table = content.querySelector('#kres-basvuru-table');
        if (!table) return;

        applyTablePreferences(table, BASVURU_COOKIE_KEY, panel, { pinnedKeys: ['islemler'] });

        const saveBtn = content.querySelector('#basvuru-save-column-prefs');
        const toggles = panel.querySelectorAll('.column-toggle');

        initColumnDrag(table, {
            pinnedKeys: ['islemler'],
            onChange: () => {
                syncColumnDropdownOrder(columnDropdown, currentOrder(table).filter((key) => key !== 'islemler'));
                markDirty(null, panel);
            },
        });

        initVisibilityToggles(table, toggles, {
            onChange: () => markDirty(null, panel),
        });

        initSorting(table, {
            onSort: (key, direction) => {
                currentSort = key;
                currentDirection = direction;
                loadBasvurular({ durum: currentDurum, page: 1, force: true });
            },
        });

        saveBtn?.addEventListener('click', () => {
            saveColumnPrefs(BASVURU_COOKIE_KEY, pinIslemlerPrefs({
                order: currentOrder(table),
                visible: currentVisible(table),
            }));
            markClean(null, panel);
            showToast('Kolon düzenlemeleri kaydedildi');
        });

        bindBasvuruColumnSearch(table);

        content.querySelectorAll('[data-pagination] a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const pageUrl = new URL(link.href, window.location.origin);
                const page = pageUrl.searchParams.get('page') || '1';
                loadBasvurular({ durum: currentDurum, page, force: true });
            });
        });

    }

    async function loadBasvurular({ durum = currentDurum, page = currentPage, force = false } = {}) {
        if (loading && !force) return;
        const thisRequest = ++requestId;
        loading = true;
        currentPage = Number(page) || 1;
        setActiveFilter(durum);
        setColumnPickerOpen(false);
        updateExcelLink();
        content.classList.add('is-loading');

        const params = {
            basvuru_durum: durum || 'tumu',
            page: currentPage,
        };
        if (currentSort) {
            params.sort = currentSort;
            params.direction = currentDirection;
        }

        try {
            const { data } = await window.axios.get(url, {
                params,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (thisRequest !== requestId) return;

            content.innerHTML = data.html || '';
            if (totalEl) {
                totalEl.textContent = data.total != null
                    ? `Toplam Kayıt: ${formatNumber(data.total)}`
                    : 'Toplam Kayıt: —';
            }
            currentSort = data.sort || '';
            currentDirection = data.direction || 'desc';
            updateUrl(data.durum || currentDurum, currentPage);
            updateExcelLink();
            bindTableInteractions();
        } catch {
            if (thisRequest !== requestId) return;
            content.innerHTML = '<div class="empty-state"><div class="empty-state-title">Başvurular yüklenemedi</div></div>';
            showToast('Başvurular yüklenemedi', 'error');
        } finally {
            if (thisRequest === requestId) {
                loading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    filters.forEach((btn) => {
        btn.addEventListener('click', () => {
            loadBasvurular({ durum: btn.dataset.basvuruFilter, page: 1, force: true });
        });
    });

    columnToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        setColumnPickerOpen(!columnDropdown?.classList.contains('open'));
    });
    columnDropdown?.addEventListener('click', (event) => event.stopPropagation());
    document.addEventListener('click', (event) => {
        if (!columnDropdown?.classList.contains('open')) {
            return;
        }
        if (event.target.closest('[data-basvuru-column-picker]')) {
            return;
        }
        setColumnPickerOpen(false);
    });

    columnSaveBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const table = content.querySelector('#kres-basvuru-table');
        if (!table) return;
        saveColumnPrefs(BASVURU_COOKIE_KEY, pinIslemlerPrefs({
            order: currentOrder(table),
            visible: currentVisible(table),
        }));
        markClean(null, panel);
        showToast('Kolon düzenlemeleri kaydedildi');
        setColumnPickerOpen(false);
    });

    columnResetBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const table = content.querySelector('#kres-basvuru-table');
        if (!table) return;
        const defaults = {
            order: JSON.parse(table.dataset.defaultOrder || '[]'),
            visible: JSON.parse(table.dataset.defaultVisible || '[]'),
        };
        clearColumnPrefs(BASVURU_COOKIE_KEY);
        applyColumnOrder(table, defaults.order);
        applyVisibility(table, defaults.visible, {
            dropdownRoot: columnDropdown || panel,
            alwaysVisibleKeys: ['islemler'],
        });
        syncColumnDropdownOrder(columnDropdown, defaults.order.filter((key) => key !== 'islemler'));
        markClean(null, panel);
        showToast('Sütunlar varsayılana sıfırlandı');
    });

    const initialSort = new URL(window.location.href).searchParams.get('sort') || '';
    const initialDirection = new URL(window.location.href).searchParams.get('direction') || 'desc';
    if (initialSort) {
        currentSort = initialSort;
        currentDirection = initialDirection === 'asc' ? 'asc' : 'desc';
    }

    loadBasvurular({ durum: currentDurum, page: currentPage, force: true });
}
