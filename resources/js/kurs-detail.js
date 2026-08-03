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

const BASVURU_COOKIE_KEY = 'kurs_basvuru_table_prefs';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (![...document.querySelectorAll('.confirm-modal')].some((el) => !el.hidden)) {
        document.body.classList.remove('modal-open');
    }
}

function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
}

function validationMessage(error) {
    const data = error?.response?.data;
    if (!data) {
        return 'İşlem sırasında bir hata oluştu.';
    }
    if (typeof data.message === 'string' && data.message && !data.errors) {
        return data.message;
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

function updateOgretmenUi(payload) {
    const nameEl = document.querySelector('.lesson-ogretmen-name');
    const openBtn = document.querySelector('[data-ogretmen-modal-open]');
    const modal = document.getElementById('ogretmen-modal');
    const titleEl = modal?.querySelector('#ogretmen-modal-title');
    const list = modal?.querySelector('[data-ogretmen-list]');
    const picker = modal?.querySelector('[data-searchable-select][data-name="ogretmen_picker"]');

    const teachers = Array.isArray(payload.ogretmenler) ? payload.ogretmenler : [];
    const hasTeacher = teachers.length > 0;
    const names = hasTeacher
        ? teachers.map((t) => t.tam_adi).filter(Boolean).join(', ')
        : '—';

    if (nameEl) {
        nameEl.textContent = names;
    }

    if (openBtn) {
        openBtn.textContent = hasTeacher ? 'Düzenle' : 'Öğretmen Ata';
        openBtn.classList.toggle('is-change', hasTeacher);
        openBtn.classList.toggle('is-publish', !hasTeacher);
    }

    if (titleEl) {
        titleEl.textContent = hasTeacher ? 'Öğretmenleri Düzenle' : 'Öğretmen Ata';
    }

    if (list) {
        list.innerHTML = '';
        if (!hasTeacher) {
            const empty = document.createElement('p');
            empty.className = 'evrak-empty';
            empty.setAttribute('data-ogretmen-empty', '');
            empty.textContent = 'Henüz öğretmen atanmadı.';
            list.appendChild(empty);
        } else {
            teachers.forEach((teacher) => {
                list.appendChild(buildOgretmenChip(teacher.id, teacher.tam_adi));
            });
        }
    }

    if (picker) {
        resetOgretmenPicker(picker);
        syncOgretmenPickerOptions(picker, list);
    }
}

function buildOgretmenChip(id, label) {
    const item = document.createElement('div');
    item.className = 'evrak-item';
    item.setAttribute('data-ogretmen-item', '');
    item.setAttribute('data-id', String(id));
    item.innerHTML =
        '<input type="hidden" name="ogretmen_ids[]" value="' + String(id) + '">' +
        '<span class="evrak-item-label"></span>' +
        '<button type="button" class="evrak-item-remove" data-ogretmen-remove title="Kaldır" aria-label="Kaldır">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>' +
        '</button>';
    item.querySelector('.evrak-item-label').textContent = label || '';
    return item;
}

function resetOgretmenPicker(picker) {
    const valueInput = picker.querySelector('[data-select-value]');
    const label = picker.querySelector('[data-select-label]');
    const options = picker.querySelectorAll('.select-option');
    const emptyOption = picker.querySelector('.select-option[data-value=""]');
    const placeholder = emptyOption?.dataset.label || 'Öğretmen seçin';

    if (valueInput) valueInput.value = '';
    if (label) label.textContent = placeholder;
    options.forEach((option) => {
        option.classList.toggle('selected', (option.dataset.value || '') === '');
    });
    picker.classList.remove('is-invalid');
    picker.querySelector('[data-select-toggle]')?.classList.remove('is-invalid');
}

function syncOgretmenPickerOptions(picker, list) {
    if (!picker || !list) return;
    const selected = new Set(
        [...list.querySelectorAll('[data-ogretmen-item]')].map((el) => String(el.dataset.id || ''))
    );
    picker.querySelectorAll('.select-option').forEach((option) => {
        const value = option.dataset.value || '';
        if (value === '') return;
        if (selected.has(value)) {
            option.classList.add('hidden');
            option.setAttribute('aria-disabled', 'true');
        } else {
            option.removeAttribute('aria-disabled');
            // Keep search filter: only unhide if not matching an active search hide... 
            // Re-apply solely for assignment; search handler may have hidden others.
            const searchInput = picker.querySelector('[data-select-search]');
            const query = (searchInput?.value || '').trim().toLocaleLowerCase('tr-TR');
            const text = (option.dataset.label || option.textContent || '').toLocaleLowerCase('tr-TR');
            option.classList.toggle('hidden', query !== '' && !text.includes(query));
        }
    });
}

function initOgretmenAssign() {
    const modal = document.getElementById('ogretmen-modal');
    const openBtn = document.querySelector('[data-ogretmen-modal-open]');
    const form = modal?.querySelector('.ogretmen-modal-form');
    if (!modal || !openBtn || !form) return;

    const list = form.querySelector('[data-ogretmen-list]');
    const picker = form.querySelector('[data-searchable-select][data-name="ogretmen_picker"]');
    const addBtn = form.querySelector('[data-ogretmen-add]');

    function ensureEmptyHint() {
        if (!list) return;
        const hasItems = list.querySelectorAll('[data-ogretmen-item]').length > 0;
        let empty = list.querySelector('[data-ogretmen-empty]');
        if (hasItems) {
            empty?.remove();
            return;
        }
        if (!empty) {
            empty = document.createElement('p');
            empty.className = 'evrak-empty';
            empty.setAttribute('data-ogretmen-empty', '');
            empty.textContent = 'Henüz öğretmen atanmadı.';
            list.appendChild(empty);
        }
    }

    function addOgretmen() {
        if (!picker || !list) return;
        const valueInput = picker.querySelector('[data-select-value]');
        const id = valueInput?.value;
        if (!id) return;
        if (list.querySelector('[data-ogretmen-item][data-id="' + id + '"]')) {
            resetOgretmenPicker(picker);
            return;
        }

        const selectedOption = picker.querySelector('.select-option[data-value="' + id + '"]');
        const label = selectedOption?.dataset.label
            || picker.querySelector('[data-select-label]')?.textContent
            || '';

        list.querySelector('[data-ogretmen-empty]')?.remove();
        list.appendChild(buildOgretmenChip(id, label));
        resetOgretmenPicker(picker);
        syncOgretmenPickerOptions(picker, list);
    }

    openBtn.addEventListener('click', () => {
        syncOgretmenPickerOptions(picker, list);
        openModal(modal);
    });
    modal.querySelectorAll('[data-ogretmen-modal-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
    addBtn?.addEventListener('click', addOgretmen);

    picker?.querySelector('[data-select-search]')?.addEventListener('input', () => {
        requestAnimationFrame(() => syncOgretmenPickerOptions(picker, list));
    });

    list?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-ogretmen-remove]');
        if (!btn) return;
        btn.closest('[data-ogretmen-item]')?.remove();
        ensureEmptyHint();
        syncOgretmenPickerOptions(picker, list);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Kaydediliyor...';
        }

        try {
            const formData = new FormData(form);
            formData.delete('ogretmen_picker');
            const { data } = await window.axios.post(form.action, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            updateOgretmenUi(data);
            closeModal(modal);
            showToast(data.message || 'Öğretmen ataması güncellendi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = previousLabel || 'Kaydet';
            }
        }
    });

    syncOgretmenPickerOptions(picker, list);
}

function updateYayinUi(payload) {
    const statusWrap = document.querySelector('.lesson-yayin-status');
    const openBtn = document.querySelector('[data-yayin-modal-open]');

    if (statusWrap) {
        const label = payload.basvuru_durumu_label
            || (payload.onlinede_yayinlansin ? 'Açık' : 'Kapalı');
        const statusClass = payload.basvuru_durumu_class
            || (payload.onlinede_yayinlansin ? 'status-aktif' : 'status-hazirlik');
        statusWrap.innerHTML = `<span class="status ${statusClass}">${label}</span>`;
    }

    if (openBtn) {
        openBtn.dataset.action = payload.onlinede_yayinlansin ? 'unpublish' : 'publish';
        openBtn.dataset.canPublish = payload.yayinlanabilir ? '1' : '0';
        openBtn.classList.toggle('is-unpublish', payload.onlinede_yayinlansin);
        openBtn.classList.toggle('is-publish', !payload.onlinede_yayinlansin);
        openBtn.textContent = payload.onlinede_yayinlansin ? 'Yayından Kaldır' : 'Yayına Al';
    }
}

function initYayinToggle() {
    const modal = document.getElementById('yayin-modal');
    const openBtn = document.querySelector('[data-yayin-modal-open]');
    if (!modal || !openBtn) return;

    const titleEl = modal.querySelector('[data-yayin-modal-title]');
    const bodyEl = modal.querySelector('[data-yayin-modal-body]');
    const formEl = modal.querySelector('[data-yayin-modal-form]');
    const confirmBtn = modal.querySelector('[data-yayin-modal-confirm]');
    const cancelBtn = modal.querySelector('[data-yayin-modal-cancel]');

    function openYayinModal() {
        const action = openBtn.getAttribute('data-action');
        const canPublish = openBtn.getAttribute('data-can-publish') === '1';

        formEl.hidden = false;
        cancelBtn.textContent = 'Vazgeç';

        if (action === 'publish') {
            titleEl.textContent = 'Yayına Al';
            confirmBtn.textContent = 'Yayına Al';
            confirmBtn.className = 'btn btn-primary btn-wide';

            if (!canPublish) {
                bodyEl.innerHTML =
                    '<p>Bu kurs yayına alınacaktır fakat başvuru tarih aralığında online kanalda görünür hale gelecektir. Şu an başvuruya açmak için başvuru tarih aralığını güncellemeniz gerekmektedir.</p>' +
                    '<p style="margin-top:0.75rem;">Bu kursu yayına almak istediğinize emin misiniz?</p>';
            } else {
                bodyEl.textContent = 'Bu kursu yayına almak istediğinize emin misiniz? Online başvurular görünür hale gelecektir.';
            }
        } else {
            titleEl.textContent = 'Yayından Kaldır';
            bodyEl.textContent = 'Bu kursu yayından kaldırmak istediğinize emin misiniz? Online başvurular kapanacaktır.';
            confirmBtn.textContent = 'Yayından Kaldır';
            confirmBtn.className = 'btn btn-danger btn-wide';
        }

        openModal(modal);
    }

    openBtn.addEventListener('click', openYayinModal);
    modal.querySelectorAll('[data-yayin-modal-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    formEl.addEventListener('submit', async (event) => {
        event.preventDefault();

        const previousLabel = confirmBtn?.textContent;
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'İşleniyor...';
        }

        try {
            const formData = new FormData(formEl);
            const { data } = await window.axios.post(formEl.action, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            updateYayinUi(data);
            closeModal(modal);
            showToast(data.message || 'Yayın durumu güncellendi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = previousLabel || 'Onayla';
            }
        }
    });
}

function initEscapeClose() {
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        const openModals = [...document.querySelectorAll('.confirm-modal')].filter((el) => !el.hidden);
        const top = openModals.at(-1);
        if (top) closeModal(top);
    });
}

function formatNumber(value) {
    return new Intl.NumberFormat('tr-TR').format(Number(value) || 0);
}

function bindBasvuruColumnSearch(table) {
    if (!table) return;

    const inputs = table.querySelectorAll('.column-search[data-column-search]');
    inputs.forEach((input) => {
        input.addEventListener('input', () => {
            const filters = [...inputs].map((el) => ({
                key: el.dataset.columnSearch,
                value: el.value.trim().toLocaleLowerCase('tr-TR'),
            }));

            table.querySelectorAll('tbody tr').forEach((row) => {
                if (row.classList.contains('empty-row')) return;

                const match = filters.every(({ key, value }) => {
                    if (!value) return true;
                    const cell = row.querySelector(`[data-column="${key}"]`);
                    const text = (cell?.textContent || '').toLocaleLowerCase('tr-TR');
                    return text.includes(value);
                });
                row.style.display = match ? '' : 'none';
            });
        });
    });
}

function initBasvuruPanel() {
    const panel = document.querySelector('[data-basvuru-panel]');
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
    if (!url || !content) return;

    let currentDurum = panel.dataset.basvuruDurum || 'tumu';
    let currentSort = '';
    let currentDirection = 'desc';
    let loaded = false;
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
        pageUrl.searchParams.set('tab', 'basvurular');
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

    function bindTableInteractions() {
        const table = content.querySelector('#kurs-basvuru-table');
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

        const persistBasvuruPrefs = () => {
            saveColumnPrefs(BASVURU_COOKIE_KEY, pinIslemlerPrefs({
                order: currentOrder(table),
                visible: currentVisible(table),
            }));
            markClean(null, panel);
            showToast('Kolon düzenlemeleri kaydedildi');
        };

        saveBtn?.addEventListener('click', persistBasvuruPrefs);

        bindBasvuruColumnSearch(table);
        bindBasvuruActions(table);
    }

    function closeBasvuruRowMenus() {
        content.querySelectorAll('[data-row-actions].is-open').forEach((wrap) => {
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
        content.querySelectorAll('.table-wrapper.has-open-action-menu').forEach((el) => {
            el.classList.remove('has-open-action-menu');
        });
    }

    function setModalOpen(modal, open) {
        if (!modal) return;
        modal.hidden = !open;
        document.body.classList.toggle('modal-open', open);
    }

    function bindBasvuruActions() {
        if (panel.dataset.basvuruActionsBound === '1') return;
        panel.dataset.basvuruActionsBound = '1';

        const durumModal = document.getElementById('basvuru-durum-modal');
        const basariModal = document.getElementById('basvuru-basari-modal');
        const baslamaModal = document.getElementById('basvuru-baslama-modal');
        const evraklarModal = document.getElementById('basvuru-evraklar-modal');
        const durumText = durumModal?.querySelector('[data-basvuru-durum-text]');
        const durumSelect = durumModal?.querySelector('[data-basvuru-durum-select]');
        const durumGerekceWrap = durumModal?.querySelector('[data-basvuru-durum-gerekce-wrap]');
        const durumGerekce = durumModal?.querySelector('[data-basvuru-durum-gerekce]');
        const durumBaslamaWrap = durumModal?.querySelector('[data-basvuru-durum-baslama-wrap]');
        const durumBaslamaInput = durumModal?.querySelector('[data-basvuru-durum-baslama-input]');
        const durumKilitHint = durumModal?.querySelector('[data-basvuru-durum-kilit-hint]');
        const durumSmsWrap = durumModal?.querySelector('[data-basvuru-durum-sms-wrap]');
        const durumSms = durumModal?.querySelector('[data-basvuru-durum-sms]');
        const durumEpostaWrap = durumModal?.querySelector('[data-basvuru-durum-eposta-wrap]');
        const durumEposta = durumModal?.querySelector('[data-basvuru-durum-eposta]');
        const durumConfirm = durumModal?.querySelector('[data-basvuru-durum-confirm]');
        const basariText = basariModal?.querySelector('[data-basvuru-basari-text]');
        const basariHint = basariModal?.querySelector('[data-basvuru-basari-hint]');
        const basariSelect = basariModal?.querySelector('[data-basvuru-basari-select]');
        const basariConfirm = basariModal?.querySelector('[data-basvuru-basari-confirm]');
        const baslamaText = baslamaModal?.querySelector('[data-basvuru-baslama-text]');
        const baslamaInput = baslamaModal?.querySelector('[data-basvuru-baslama-input]');
        const baslamaConfirm = baslamaModal?.querySelector('[data-basvuru-baslama-confirm]');
        const evraklarSubtitle = evraklarModal?.querySelector('[data-basvuru-evraklar-subtitle]');
        const evraklarSummary = evraklarModal?.querySelector('[data-basvuru-evraklar-summary]');
        const evraklarList = evraklarModal?.querySelector('[data-basvuru-evraklar-list]');
        const evrakUploadToggle = evraklarModal?.querySelector('[data-basvuru-evrak-upload-toggle]');
        const evrakUploadPanel = evraklarModal?.querySelector('[data-basvuru-evrak-upload-panel]');
        const evrakTipSelect = evraklarModal?.querySelector('[data-basvuru-evrak-tip-select]');
        const evrakDosyaInput = evraklarModal?.querySelector('[data-basvuru-evrak-dosya]');
        const evrakUploadCancel = evraklarModal?.querySelector('[data-basvuru-evrak-upload-cancel]');
        const evrakUploadSubmit = evraklarModal?.querySelector('[data-basvuru-evrak-upload-submit]');

        let pendingEvrakUploadUrl = '';
        let pendingEvrakKatilimci = '';
        let pendingEvraklar = [];

        let evrakTipOptions = [];
        try {
            evrakTipOptions = JSON.parse(evraklarModal?.dataset.evrakTipleri || '[]');
        } catch {
            evrakTipOptions = [];
        }

        let pendingDurumUrl = '';
        let pendingBasariUrl = '';
        let pendingBaslamaUrl = '';
        let pendingPage = 1;
        let pendingBasariEditable = false;
        let pendingBelgeAllowed = false;
        let pendingMevcutDurumKod = '';
        let pendingBasariKod = '';
        let pendingKursBaslama = '';
        let pendingKursBitis = '';
        let pendingBaslamaTarihi = '';
        const olumluBasariKodlari = ['sertifika_hak_etti', 'katilim_belgesi_hak_etti'];

        function currentPage() {
            const pageUrl = new URL(window.location.href);
            return Number(pageUrl.searchParams.get('page') || '1') || 1;
        }

        function kesinKayitKilitliMi() {
            return pendingMevcutDurumKod === 'kesin_kayit'
                && olumluBasariKodlari.includes(pendingBasariKod);
        }

        function defaultBaslamaTarihi() {
            if (pendingMevcutDurumKod === 'kesin_kayit' && pendingBaslamaTarihi) {
                return pendingBaslamaTarihi;
            }

            return pendingKursBaslama || '';
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function formatFileSize(bytes) {
            const size = Number(bytes || 0);
            if (!size) return 'Boyut bilgisi yok';
            const units = ['B', 'KB', 'MB', 'GB'];
            let value = size;
            let unitIndex = 0;
            while (value >= 1024 && unitIndex < units.length - 1) {
                value /= 1024;
                unitIndex += 1;
            }
            const digits = value >= 10 || unitIndex === 0 ? 0 : 1;
            return `${value.toFixed(digits)} ${units[unitIndex]}`;
        }

        function setEvrakUploadPanelOpen(open) {
            if (evrakUploadPanel) {
                evrakUploadPanel.hidden = !open;
            }
            evrakUploadToggle?.classList.toggle('is-open', open);
            evrakUploadToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function resetEvrakUploadForm() {
            if (evrakTipSelect) {
                evrakTipSelect.value = '';
            }
            if (evrakDosyaInput) {
                evrakDosyaInput.value = '';
            }
        }

        function fillEvrakTipSelect() {
            if (!evrakTipSelect) return;

            const options = Array.isArray(evrakTipOptions) ? evrakTipOptions : [];
            evrakTipSelect.innerHTML = [
                '<option value="">Seçiniz</option>',
                ...options.map((tip) => `<option value="${escapeHtml(String(tip.id))}">${escapeHtml(tip.ad || 'Evrak')}</option>`),
            ].join('');
        }

        function renderEvraklarModal(katilimci, evraklar = []) {
            pendingEvrakKatilimci = katilimci || 'Bu başvuru';
            pendingEvraklar = Array.isArray(evraklar) ? evraklar : [];

            if (!evraklarSummary || !evraklarList) return;

            const count = pendingEvraklar.length;
            if (evraklarSubtitle) {
                evraklarSubtitle.textContent = `"${pendingEvrakKatilimci}" için yüklenen evraklar`;
            }
            evraklarSummary.innerHTML = `
                <div class="basvuru-evraklar-summary-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.5a5.5 5.5 0 0 1-7.78-7.78l9.9-9.9a3.5 3.5 0 1 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 1 1-2.12-2.12l8.49-8.48"/></svg>
                </div>
                <div>
                    <div class="basvuru-evraklar-summary-title">${count > 0 ? `${count} evrak yüklendi` : 'Yüklenmiş evrak bulunmuyor'}</div>
                    <div class="basvuru-evraklar-summary-text">${count > 0 ? 'Evrak tipine göre dosyaları görüntüleyebilir veya yeni evrak ekleyebilirsiniz.' : 'Bu başvuruya henüz dosya eklenmemiş. Sağ üstteki + ile yükleyebilirsiniz.'}</div>
                </div>
            `;

            if (!count) {
                evraklarList.innerHTML = `
                    <div class="empty-state basvuru-evraklar-empty">
                        <div class="empty-state-title">Evrak yok</div>
                        <p class="empty-state-text">Bu başvuru için yüklenmiş evrak kaydı bulunamadı.</p>
                    </div>
                `;
                return;
            }

            evraklarList.innerHTML = pendingEvraklar.map((evrak) => {
                const type = escapeHtml(evrak?.tip || 'Evrak');
                const mime = escapeHtml(evrak?.mime || 'Tür bilgisi yok');
                const uploadedBy = escapeHtml(evrak?.yukleyen || 'Bilinmiyor');
                const uploadedAt = escapeHtml(evrak?.yuklenme_tarihi || 'Tarih yok');
                const description = evrak?.aciklama ? `<p class="basvuru-evrak-card-desc">${escapeHtml(evrak.aciklama)}</p>` : '';
                const url = evrak?.url ? escapeHtml(evrak.url) : '';
                const deleteUrl = evrak?.delete_url ? escapeHtml(evrak.delete_url) : '';
                const openAction = url
                    ? `<a href="${url}" class="basvuru-evrak-card-link" target="_blank" rel="noopener noreferrer">
                            <span>Dosyayı Aç</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                       </a>`
                    : `<span class="basvuru-evrak-card-link is-disabled">Dosya bağlantısı yok</span>`;
                const deleteAction = deleteUrl
                    ? `<button
                            type="button"
                            class="basvuru-evrak-card-delete"
                            data-basvuru-evrak-sil
                            data-delete-url="${deleteUrl}"
                            data-tip="${type}"
                            title="Evrakı sil"
                            aria-label="Evrakı sil"
                       >
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                <path d="M10 11v6"/>
                                <path d="M14 11v6"/>
                            </svg>
                       </button>`
                    : '';

                return `
                    <article class="basvuru-evrak-card">
                        <div class="basvuru-evrak-card-main">
                            <div class="basvuru-evrak-card-info">
                                <h4 class="basvuru-evrak-card-name">${type}</h4>
                                ${description}
                            </div>
                            <div class="basvuru-evrak-card-actions">
                                ${openAction}
                                ${deleteAction}
                            </div>
                        </div>
                        <div class="basvuru-evrak-meta-list">
                            <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Boyut</span><strong>${formatFileSize(evrak?.boyut)}</strong></span>
                            <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Biçim</span><strong>${mime}</strong></span>
                            <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Yükleyen</span><strong>${uploadedBy}</strong></span>
                            <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Yüklenme</span><strong>${uploadedAt}</strong></span>
                        </div>
                    </article>
                `;
            }).join('');
        }

        function syncDurumFormVisibility() {
            const isIptal = durumSelect?.value === 'iptal';
            const isKesinKayit = durumSelect?.value === 'kesin_kayit';

            if (durumGerekceWrap) {
                durumGerekceWrap.hidden = !isIptal;
            }
            if (!isIptal && durumGerekce) {
                durumGerekce.value = '';
            }

            if (durumBaslamaWrap) {
                const wasHidden = durumBaslamaWrap.hidden;
                durumBaslamaWrap.hidden = !isKesinKayit;

                if (isKesinKayit && durumBaslamaInput) {
                    durumBaslamaInput.min = pendingKursBaslama || '';
                    durumBaslamaInput.max = pendingKursBitis || '';

                    if (wasHidden || !durumBaslamaInput.value) {
                        durumBaslamaInput.value = defaultBaslamaTarihi();
                    }
                }
            }

            syncDurumSmsOption(durumSelect?.value || '');
            syncDurumEpostaOption(durumSelect?.value || '');
        }

        function smsAyarForDurum(durumKod) {
            if (durumKod === 'kesin_kayit') {
                return durumModal?.dataset.smsOnayAyar || 'istege_bagli';
            }
            if (durumKod === 'iptal') {
                return durumModal?.dataset.smsIptalAyar || 'istege_bagli';
            }
            if (durumKod === 'yedek') {
                return durumModal?.dataset.smsYedekAyar || 'istege_bagli';
            }

            return null;
        }

        function epostaAyarForDurum(durumKod) {
            if (durumKod === 'kesin_kayit') {
                return durumModal?.dataset.epostaOnayAyar || 'istege_bagli';
            }
            if (durumKod === 'iptal') {
                return durumModal?.dataset.epostaIptalAyar || 'istege_bagli';
            }
            if (durumKod === 'yedek') {
                return durumModal?.dataset.epostaYedekAyar || 'istege_bagli';
            }

            return null;
        }

        function syncBildirimOption(ayar, wrap, checkbox) {
            const show = ayar !== null;

            if (wrap) {
                wrap.hidden = !show;
            }
            if (!checkbox) {
                return;
            }

            if (!show) {
                checkbox.checked = false;
                checkbox.disabled = false;
                return;
            }

            if (ayar === 'evet') {
                checkbox.checked = true;
                checkbox.disabled = true;
            } else if (ayar === 'hayir') {
                checkbox.checked = false;
                checkbox.disabled = true;
            } else {
                checkbox.disabled = false;
            }
        }

        function syncDurumSmsOption(durumKod = durumSelect?.value || '') {
            syncBildirimOption(smsAyarForDurum(durumKod), durumSmsWrap, durumSms);
        }

        function syncDurumEpostaOption(durumKod = durumSelect?.value || '') {
            syncBildirimOption(epostaAyarForDurum(durumKod), durumEpostaWrap, durumEposta);
        }

        function syncDurumKilitOptions() {
            const kilitli = kesinKayitKilitliMi();

            durumSelect?.querySelectorAll('option').forEach((option) => {
                const isKesinKayit = option.value === 'kesin_kayit';
                option.disabled = kilitli && !isKesinKayit;
            });

            if (durumSelect && kilitli) {
                durumSelect.value = 'kesin_kayit';
            }

            if (durumKilitHint) {
                durumKilitHint.hidden = !kilitli;
            }

            syncDurumFormVisibility();
        }

        function syncBasariBelgeOptions({ durumKod = '', basariId = '' } = {}) {
            const kursTamamlanan = panel.dataset.kursDurum === 'tamamlanan';
            const kesinKayit = durumKod === 'kesin_kayit';
            pendingBasariEditable = kesinKayit;
            pendingBelgeAllowed = kursTamamlanan && kesinKayit;

            basariSelect?.querySelectorAll('option').forEach((option) => {
                if (option.value === '') {
                    option.disabled = !pendingBasariEditable;
                    return;
                }

                const isCurrent = String(option.value) === String(basariId || '');
                const requiresBelge = option.dataset.requiresBelge === '1';
                option.disabled = !pendingBasariEditable || (requiresBelge && !pendingBelgeAllowed && !isCurrent);
            });

            if (basariHint) {
                basariHint.hidden = pendingBasariEditable && pendingBelgeAllowed;
            }

            if (basariConfirm) {
                basariConfirm.disabled = !pendingBasariEditable;
            }
        }

        async function postBasvuruUpdate(url, fields) {
            const formData = new FormData();
            Object.entries(fields).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    formData.append(key, value);
                }
            });
            formData.append('_method', 'PUT');

            return window.axios.post(url, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
        }

        async function reloadAfterAction() {
            await loadBasvurular({
                durum: currentDurum,
                page: pendingPage || currentPage(),
                force: true,
            });
        }

        function selectValueForDurumKod(kod) {
            if (kod === 'kesin_kayit' || kod === 'iptal' || kod === 'yedek') {
                return kod;
            }
            return '';
        }

        content.addEventListener('click', (event) => {
            const durumBtn = event.target.closest('[data-basvuru-durum-ac]');
            if (durumBtn) {
                event.preventDefault();
                closeBasvuruRowMenus();
                pendingDurumUrl = durumBtn.dataset.updateUrl || '';
                pendingPage = currentPage();
                pendingMevcutDurumKod = durumBtn.dataset.durumKod || '';
                pendingBasariKod = durumBtn.dataset.basariKod || '';
                pendingKursBaslama = panel.dataset.kursBaslama || '';
                pendingKursBitis = panel.dataset.kursBitis || '';
                pendingBaslamaTarihi = durumBtn.dataset.baslamaTarihi || '';
                if (durumText) {
                    durumText.textContent = `"${durumBtn.dataset.katilimci || 'Bu başvuru'}" için başvuru durumunu seçin.`;
                }
                if (durumSelect) {
                    durumSelect.value = selectValueForDurumKod(durumBtn.dataset.durumKod || '');
                }
                if (durumGerekce) {
                    durumGerekce.value = durumBtn.dataset.iptalGerekceId || '';
                }
                if (durumBaslamaInput) {
                    durumBaslamaInput.value = '';
                }
                syncDurumKilitOptions();
                setModalOpen(durumModal, true);
                return;
            }

            const basariBtn = event.target.closest('[data-basvuru-basari]');
            if (basariBtn) {
                event.preventDefault();
                closeBasvuruRowMenus();
                pendingBasariUrl = basariBtn.dataset.updateUrl || '';
                pendingPage = currentPage();
                if (basariText) {
                    basariText.textContent = `"${basariBtn.dataset.katilimci || 'Bu başvuru'}" için başarı durumunu seçin.`;
                }
                if (basariSelect) {
                    basariSelect.value = basariBtn.dataset.basariId || '';
                }
                syncBasariBelgeOptions({
                    durumKod: basariBtn.dataset.durumKod || '',
                    basariId: basariBtn.dataset.basariId || '',
                });
                setModalOpen(basariModal, true);
                return;
            }

            const baslamaBtn = event.target.closest('[data-basvuru-baslama-ac]');
            if (baslamaBtn) {
                event.preventDefault();
                closeBasvuruRowMenus();
                pendingBaslamaUrl = baslamaBtn.dataset.updateUrl || '';
                pendingPage = currentPage();
                if (baslamaText) {
                    baslamaText.textContent = `"${baslamaBtn.dataset.katilimci || 'Bu başvuru'}" için kursa başlama tarihini seçin.`;
                }
                if (baslamaInput) {
                    baslamaInput.min = panel.dataset.kursBaslama || '';
                    baslamaInput.max = panel.dataset.kursBitis || '';
                    baslamaInput.value = baslamaBtn.dataset.baslamaTarihi || panel.dataset.kursBaslama || '';
                }
                setModalOpen(baslamaModal, true);
                return;
            }

            const evraklarBtn = event.target.closest('[data-basvuru-evraklar-ac]');
            if (evraklarBtn) {
                event.preventDefault();
                closeBasvuruRowMenus();
                let evraklar = [];
                try {
                    evraklar = JSON.parse(evraklarBtn.dataset.evraklar || '[]');
                } catch {
                    evraklar = [];
                }
                pendingEvrakUploadUrl = evraklarBtn.dataset.uploadUrl || '';
                fillEvrakTipSelect();
                resetEvrakUploadForm();
                setEvrakUploadPanelOpen(false);
                renderEvraklarModal(evraklarBtn.dataset.katilimci || 'Bu başvuru', evraklar);
                setModalOpen(evraklarModal, true);
            }
        });

        durumSelect?.addEventListener('change', syncDurumFormVisibility);

        durumModal?.querySelectorAll('[data-basvuru-durum-close]').forEach((el) => {
            el.addEventListener('click', () => setModalOpen(durumModal, false));
        });
        basariModal?.querySelectorAll('[data-basvuru-basari-close]').forEach((el) => {
            el.addEventListener('click', () => setModalOpen(basariModal, false));
        });
        baslamaModal?.querySelectorAll('[data-basvuru-baslama-close]').forEach((el) => {
            el.addEventListener('click', () => setModalOpen(baslamaModal, false));
        });
        evraklarModal?.querySelectorAll('[data-basvuru-evraklar-close]').forEach((el) => {
            el.addEventListener('click', () => {
                setEvrakUploadPanelOpen(false);
                resetEvrakUploadForm();
                setModalOpen(evraklarModal, false);
            });
        });

        evrakUploadToggle?.addEventListener('click', () => {
            const willOpen = Boolean(evrakUploadPanel?.hidden);
            if (willOpen && !pendingEvrakUploadUrl) {
                showToast('Bu başvuru için evrak yükleme adresi bulunamadı.', 'error');
                return;
            }
            if (willOpen && (!Array.isArray(evrakTipOptions) || evrakTipOptions.length === 0)) {
                showToast('Yüklenebilecek evrak tipi tanımlı değil.', 'error');
                return;
            }
            setEvrakUploadPanelOpen(willOpen);
            if (willOpen) {
                fillEvrakTipSelect();
                evrakTipSelect?.focus();
            } else {
                resetEvrakUploadForm();
            }
        });

        evrakUploadCancel?.addEventListener('click', () => {
            setEvrakUploadPanelOpen(false);
            resetEvrakUploadForm();
        });

        evrakUploadSubmit?.addEventListener('click', async () => {
            if (!pendingEvrakUploadUrl) {
                showToast('Bu başvuru için evrak yükleme adresi bulunamadı.', 'error');
                return;
            }

            const tipId = evrakTipSelect?.value || '';
            const file = evrakDosyaInput?.files?.[0] || null;

            if (!tipId) {
                showToast('Evrak tipi seçilmelidir.', 'error');
                evrakTipSelect?.focus();
                return;
            }
            if (!file) {
                showToast('Dosya seçilmelidir.', 'error');
                evrakDosyaInput?.focus();
                return;
            }

            const formData = new FormData();
            formData.append('evrak_tipi_id', tipId);
            formData.append('dosya', file);

            evrakUploadSubmit.disabled = true;
            try {
                const { data } = await window.axios.post(pendingEvrakUploadUrl, formData, {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const nextEvraklar = Array.isArray(data.evraklar) ? data.evraklar : pendingEvraklar;
                renderEvraklarModal(pendingEvrakKatilimci, nextEvraklar);
                resetEvrakUploadForm();
                setEvrakUploadPanelOpen(false);
                showToast(data.message || 'Evrak yüklendi.', 'success');
                await reloadAfterAction();
            } catch (error) {
                showToast(validationMessage(error) || 'Evrak yüklenemedi.', 'error');
            } finally {
                evrakUploadSubmit.disabled = false;
            }
        });

        evraklarList?.addEventListener('click', async (event) => {
            const deleteBtn = event.target.closest('[data-basvuru-evrak-sil]');
            if (!deleteBtn) return;

            event.preventDefault();
            const deleteUrl = deleteBtn.dataset.deleteUrl || '';
            if (!deleteUrl) return;

            const tipAdi = deleteBtn.dataset.tip || 'bu evrak';
            if (!window.confirm(`"${tipAdi}" evrakını silmek istediğinize emin misiniz?`)) {
                return;
            }

            deleteBtn.disabled = true;
            try {
                const { data } = await window.axios.delete(deleteUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const nextEvraklar = Array.isArray(data.evraklar) ? data.evraklar : pendingEvraklar;
                renderEvraklarModal(pendingEvrakKatilimci, nextEvraklar);
                showToast(data.message || 'Evrak silindi.', 'success');
                await reloadAfterAction();
            } catch (error) {
                showToast(validationMessage(error) || 'Evrak silinemedi.', 'error');
                deleteBtn.disabled = false;
            }
        });

        durumConfirm?.addEventListener('click', async () => {
            if (!pendingDurumUrl) return;

            const durumKod = durumSelect?.value || '';
            if (kesinKayitKilitliMi() && durumKod !== 'kesin_kayit') {
                showToast('Kesin kaydı iptal etmek için önce başarı durumunu güncelleyiniz.', 'error');
                return;
            }

            if (durumKod === 'iptal' && !durumGerekce?.value) {
                showToast('İptal gerekçesi seçilmelidir.', 'error');
                durumGerekce?.focus();
                return;
            }

            if (durumKod === 'kesin_kayit' && !durumBaslamaInput?.value) {
                showToast('Kursa başlama tarihi seçilmelidir.', 'error');
                durumBaslamaInput?.focus();
                return;
            }

            durumConfirm.disabled = true;
            try {
                const payload = { durum_kod: durumKod };
                if (durumKod === 'iptal') {
                    payload.iptal_gerekce_id = durumGerekce.value;
                }
                if (durumKod === 'kesin_kayit') {
                    payload.kursa_baslama_tarihi = durumBaslamaInput.value;
                }
                if (durumKod === 'kesin_kayit' || durumKod === 'iptal' || durumKod === 'yedek') {
                    payload.sms_gonder = durumSms?.checked ? '1' : '0';
                    payload.eposta_gonder = durumEposta?.checked ? '1' : '0';
                }
                const { data } = await postBasvuruUpdate(pendingDurumUrl, payload);
                setModalOpen(durumModal, false);
                showToast(data.message || 'Başvuru durumu güncellendi.', 'success');
                await reloadAfterAction();
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                durumConfirm.disabled = false;
            }
        });

        basariConfirm?.addEventListener('click', async () => {
            if (!pendingBasariUrl) return;

            if (!pendingBasariEditable) {
                showToast('Başarı durumu yalnızca başvuru durumu Kesin Kayıt olan kayıtlar için güncellenebilir.', 'error');
                return;
            }

            const selected = basariSelect?.selectedOptions[0];
            if (selected?.dataset.requiresBelge === '1' && !pendingBelgeAllowed) {
                showToast(
                    'Sertifika veya katılım belgesi hakkı yalnızca kesin kayıtlı başvurular ve Tamamlanan kurslarda seçilebilir.',
                    'error',
                );
                return;
            }

            basariConfirm.disabled = true;
            try {
                const { data } = await postBasvuruUpdate(pendingBasariUrl, {
                    basari_durumu_id: basariSelect?.value || '',
                });
                setModalOpen(basariModal, false);
                showToast(data.message || 'Başarı durumu güncellendi.', 'success');
                await reloadAfterAction();
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                basariConfirm.disabled = false;
            }
        });

        baslamaConfirm?.addEventListener('click', async () => {
            if (!pendingBaslamaUrl) return;

            const value = baslamaInput?.value || '';
            if (!value) {
                showToast('Kursa başlama tarihi seçilmelidir.', 'error');
                baslamaInput?.focus();
                return;
            }

            baslamaConfirm.disabled = true;
            try {
                const { data } = await postBasvuruUpdate(pendingBaslamaUrl, {
                    kursa_baslama_tarihi: value,
                });
                setModalOpen(baslamaModal, false);
                showToast(data.message || 'Kursa başlama tarihi güncellendi.', 'success');
                await reloadAfterAction();
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                baslamaConfirm.disabled = false;
            }
        });
    }

    bindBasvuruActions();

    function resetColumnPreferences() {
        const table = content.querySelector('#kurs-basvuru-table');
        if (!table) {
            showToast('Önce başvurular yüklenmeli', 'error');
            return;
        }

        const defaults = {
            order: JSON.parse(table.dataset.defaultOrder || '[]'),
            visible: JSON.parse(table.dataset.defaultVisible || '[]'),
        };

        clearColumnPrefs(BASVURU_COOKIE_KEY);
        applyColumnOrder(table, defaults.order);
        applyVisibility(table, defaults.visible, { dropdownRoot: panel, alwaysVisibleKeys: ['islemler'] });
        syncColumnDropdownOrder(columnDropdown, defaults.order.filter((key) => key !== 'islemler'));
        markClean(null, panel);
        showToast('Sütunlar varsayılana sıfırlandı');
    }

    function persistColumnPreferencesFromPicker() {
        const table = content.querySelector('#kurs-basvuru-table');
        if (!table) {
            showToast('Önce başvurular yüklenmeli', 'error');
            return;
        }

        saveColumnPrefs(BASVURU_COOKIE_KEY, pinIslemlerPrefs({
            order: currentOrder(table),
            visible: currentVisible(table),
        }));
        markClean(null, panel);
        showToast('Kolon düzenlemeleri kaydedildi');
    }

    async function loadBasvurular({ durum = currentDurum, page = 1, force = false } = {}) {
        if (loading && !force) return;

        const thisRequest = ++requestId;
        loading = true;
        content.classList.add('is-loading');
        setActiveFilter(durum);
        setColumnPickerOpen(false);

        try {
            const params = {
                basvuru_durum: durum,
                page,
            };
            if (currentSort) {
                params.sort = currentSort;
                params.direction = currentDirection;
            }

            const { data } = await window.axios.get(url, {
                params,
                headers: { Accept: 'application/json' },
            });

            if (thisRequest !== requestId) return;

            content.innerHTML = data.html;
            if (totalEl) {
                totalEl.textContent = 'Toplam Kayıt: ' + formatNumber(data.total);
            }

            currentSort = data.sort || '';
            currentDirection = data.direction || 'desc';
            loaded = true;
            updateUrl(data.durum || durum, page);
            updateExcelLink();
            bindTableInteractions();
        } catch (error) {
            if (thisRequest !== requestId) return;
            content.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-title">Yüklenemedi</div>
                    <p class="empty-state-text">Başvurular alınırken bir hata oluştu.</p>
                </div>
            `;
            showToast(validationMessage(error), 'error');
        } finally {
            if (thisRequest === requestId) {
                loading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    filters.forEach((btn) => {
        btn.addEventListener('click', () => {
            const durum = btn.dataset.basvuruFilter || 'tumu';
            if (durum === currentDurum && loaded) return;
            loadBasvurular({ durum, page: 1, force: true });
        });
    });

    columnToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = !columnDropdown?.classList.contains('open');
        setColumnPickerOpen(willOpen);
    });

    columnResetBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        resetColumnPreferences();
    });

    columnSaveBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        persistColumnPreferencesFromPicker();
    });

    columnDropdown?.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', (event) => {
        if (!columnDropdown?.classList.contains('open')) return;
        if (event.target.closest('[data-basvuru-column-picker]')) return;
        setColumnPickerOpen(false);
    });

    content.addEventListener('click', (event) => {
        const link = event.target.closest('[data-basvuru-pagination] a, .pagination a');
        if (!link || !content.contains(link)) return;

        event.preventDefault();
        const href = link.getAttribute('href');
        if (!href) return;

        const pageUrl = new URL(href, window.location.origin);
        const page = pageUrl.searchParams.get('page') || '1';
        loadBasvurular({ durum: currentDurum, page, force: true });
    });

    const pageParams = new URL(window.location.href).searchParams;
    if (pageParams.get('sort')) {
        currentSort = pageParams.get('sort') || '';
        currentDirection = pageParams.get('direction') === 'asc' ? 'asc' : 'desc';
    }

    panel._loadBasvurular = loadBasvurular;
    panel._basvuruLoaded = () => loaded;

    return panel;
}

function bindYoklamaDurumRadios(root) {
    root.querySelectorAll('.yoklama-durum-group').forEach((group) => {
        group.querySelectorAll('input[type="radio"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                group.querySelectorAll('.yoklama-durum-option').forEach((opt) => {
                    opt.classList.toggle('is-active', Boolean(opt.querySelector('input')?.checked));
                });
            });
        });
    });
}

function initYoklamaPanel() {
    const panel = document.querySelector('[data-yoklama-panel]');
    if (!panel) return null;

    const url = panel.dataset.yoklamaUrl;
    const content = panel.querySelector('[data-yoklama-content]');
    if (!url || !content) return null;

    let loadedList = false;
    let loading = false;
    let requestId = 0;
    let currentDersId = panel.dataset.yoklamaDers || '';

    function updateUrl(dersId = null) {
        const pageUrl = new URL(window.location.href);
        pageUrl.searchParams.set('tab', 'yoklamalar');
        pageUrl.searchParams.delete('basvuru_durum');
        pageUrl.searchParams.delete('page');
        if (dersId) {
            pageUrl.searchParams.set('ders', String(dersId));
        } else {
            pageUrl.searchParams.delete('ders');
        }
        history.replaceState({}, '', pageUrl.pathname + pageUrl.search);
        panel.dataset.yoklamaDers = dersId ? String(dersId) : '';
        currentDersId = dersId ? String(dersId) : '';
    }

    async function loadYoklamalar({ dersId = null, force = false } = {}) {
        if (loading && !force) return;

        const thisRequest = ++requestId;
        loading = true;
        content.classList.add('is-loading');

        try {
            const params = {};
            if (dersId) {
                params.ders = dersId;
            }

            const { data } = await window.axios.get(url, {
                params,
                headers: { Accept: 'application/json' },
            });

            if (thisRequest !== requestId) return;

            content.innerHTML = data.html;
            bindYoklamaDurumRadios(content);

            if (data.view === 'form') {
                loadedList = false;
                updateUrl(data.ders_id);
            } else {
                loadedList = true;
                updateUrl(null);
            }
        } catch (error) {
            if (thisRequest !== requestId) return;
            content.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-title">Yüklenemedi</div>
                    <p class="empty-state-text">Yoklamalar alınırken bir hata oluştu.</p>
                </div>
            `;
            showToast(validationMessage(error), 'error');
        } finally {
            if (thisRequest === requestId) {
                loading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    content.addEventListener('click', (event) => {
        const openBtn = event.target.closest('[data-yoklama-ders]');
        if (openBtn && content.contains(openBtn)) {
            event.preventDefault();
            loadYoklamalar({ dersId: openBtn.dataset.yoklamaDers, force: true });
            return;
        }

        const backBtn = event.target.closest('[data-yoklama-back]');
        if (backBtn && content.contains(backBtn)) {
            event.preventDefault();
            loadYoklamalar({ force: true });
            return;
        }

        const deleteBtn = event.target.closest('[data-yoklama-delete]');
        if (deleteBtn && content.contains(deleteBtn)) {
            event.preventDefault();
            openYoklamaDeleteModal(deleteBtn.dataset.yoklamaDeleteUrl, deleteBtn.dataset.yoklamaDeleteOzet);
        }
    });

    function openYoklamaDeleteModal(url, ozet) {
        const modal = document.getElementById('yoklama-delete-modal');
        if (!modal || !url) return;

        const bodyEl = modal.querySelector('[data-yoklama-delete-body]');
        const formEl = modal.querySelector('[data-yoklama-delete-form]');
        if (bodyEl) {
            bodyEl.textContent = `${ozet ? ozet + ' ' : ''}dersine ait yoklama kaydını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`;
        }
        if (formEl) {
            formEl.setAttribute('action', url);
        }

        openModal(modal);
    }

    const deleteModal = document.getElementById('yoklama-delete-modal');
    if (deleteModal && !deleteModal.dataset.yoklamaDeleteBound) {
        deleteModal.dataset.yoklamaDeleteBound = '1';

        deleteModal.querySelectorAll('[data-yoklama-delete-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(deleteModal));
        });

        const deleteForm = deleteModal.querySelector('[data-yoklama-delete-form]');
        const confirmBtn = deleteModal.querySelector('[data-yoklama-delete-confirm]');

        deleteForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const previousLabel = confirmBtn?.textContent;
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Siliniyor...';
            }

            try {
                const { data } = await window.axios.post(deleteForm.getAttribute('action'), new FormData(deleteForm), {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                closeModal(deleteModal);
                showToast(data.message || 'Yoklama silindi.', 'success');
                await loadYoklamalar({ force: true });
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = previousLabel || 'Sil';
                }
            }
        });
    }

    content.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-yoklama-form]');
        if (!form || !content.contains(form)) return;

        event.preventDefault();

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

            showToast(data.message || 'Yoklama kaydedildi.', 'success');
            await loadYoklamalar({ dersId: data.ders_id || currentDersId, force: true });
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = previousLabel || 'Yoklamayı Kaydet';
            }
        }
    });

    panel._loadYoklamalar = loadYoklamalar;
    panel._yoklamaLoaded = () => loadedList && !currentDersId;
    panel._currentYoklamaDers = () => currentDersId;

    return panel;
}

function initLessonTabs(basvuruPanel, yoklamaPanel) {
    const root = document.querySelector('.lesson-detail');
    if (!root) return;

    const tabs = root.querySelectorAll('[data-lesson-tab]');
    const panels = root.querySelectorAll('[data-lesson-panel]');
    const mesajlarPanel = root.querySelector('[data-lesson-panel="mesajlar"]');
    let mesajlarLoading = false;
    let mesajlarRequestId = 0;

    async function loadMesajlar({ force = false } = {}) {
        if (!mesajlarPanel) return;
        const url = mesajlarPanel.dataset.mesajlarUrl;
        const content = mesajlarPanel.querySelector('[data-mesajlar-content]');
        if (!url || !content) return;
        if (mesajlarLoading && !force) return;

        const thisRequest = ++mesajlarRequestId;
        mesajlarLoading = true;
        content.classList.add('is-loading');

        try {
            const kanal = new URL(window.location.href).searchParams.get('kanal') || 'sms';
            const { data } = await window.axios.get(url, {
                params: { kanal },
                headers: { Accept: 'application/json' },
            });

            if (thisRequest !== mesajlarRequestId) return;

            content.innerHTML = data.html || '';
        } catch (error) {
            if (thisRequest !== mesajlarRequestId) return;
            showToast(validationMessage(error), 'error');
        } finally {
            if (thisRequest === mesajlarRequestId) {
                mesajlarLoading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    function setTab(name) {
        const url = new URL(window.location.href);

        tabs.forEach((tab) => {
            const active = tab.getAttribute('data-lesson-tab') === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.getAttribute('data-lesson-panel') === name);
        });

        url.searchParams.set('tab', name);
        if (name !== 'basvurular') {
            url.searchParams.delete('basvuru_durum');
            url.searchParams.delete('page');
        }
        if (name !== 'yoklamalar') {
            url.searchParams.delete('ders');
        }
        if (name !== 'mesajlar') {
            url.searchParams.delete('kanal');
        }
        history.replaceState({}, '', url.pathname + url.search);

        if (name === 'basvurular' && basvuruPanel?._loadBasvurular) {
            const page = Number(new URL(window.location.href).searchParams.get('page') || 1);
            const durum = basvuruPanel.dataset.basvuruDurum || 'tumu';
            basvuruPanel._loadBasvurular({ durum, page, force: true });
        }

        if (name === 'yoklamalar' && yoklamaPanel?._loadYoklamalar) {
            const dersId = yoklamaPanel.dataset.yoklamaDers || null;
            yoklamaPanel._loadYoklamalar({ dersId: dersId || null, force: true });
        }

        if (name === 'mesajlar') {
            loadMesajlar({ force: true });
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            setTab(tab.getAttribute('data-lesson-tab'));
        });
    });

    const initialTab = new URL(window.location.href).searchParams.get('tab') || 'detay';
    if (initialTab === 'basvurular' && basvuruPanel?._loadBasvurular) {
        const page = Number(new URL(window.location.href).searchParams.get('page') || 1);
        const durum = basvuruPanel.dataset.basvuruDurum || 'tumu';
        basvuruPanel._loadBasvurular({ durum, page, force: true });
    }
    if (initialTab === 'yoklamalar' && yoklamaPanel?._loadYoklamalar) {
        const dersId = yoklamaPanel.dataset.yoklamaDers || null;
        yoklamaPanel._loadYoklamalar({ dersId: dersId || null, force: true });
    }
}

function reloadTakvimTab() {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', 'takvim');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function initTakvimIslemModallari() {
    const iptalModal = document.getElementById('ders-iptal-modal');
    const iptalGeriAlModal = document.getElementById('ders-iptal-geri-al-modal');
    const tarihModal = document.getElementById('ders-tarih-modal');
    if (!iptalModal && !iptalGeriAlModal && !tarihModal) return;

    if (iptalModal) {
        const ozetEl = iptalModal.querySelector('[data-ders-iptal-ozet]');
        const gerekceEl = iptalModal.querySelector('[data-ders-iptal-gerekce]');
        const gerekceInput = iptalModal.querySelector('[data-ders-iptal-gerekce-input]');
        const formEl = iptalModal.querySelector('[data-ders-iptal-form]');
        const confirmBtn = iptalModal.querySelector('[data-ders-iptal-confirm]');

        iptalModal.querySelectorAll('[data-ders-iptal-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(iptalModal));
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-takvim-iptal]');
            if (!trigger) return;
            event.preventDefault();

            if (trigger.dataset.takvimIptalYoklamaAlindi === '1') {
                showToast('Yoklaması alınmış bir ders iptal edilemez.', 'error');
                return;
            }

            if (ozetEl) {
                ozetEl.textContent = `${trigger.dataset.takvimIptalOzet || ''} dersini iptal etmek istediğinize emin misiniz? Bu işlem için gerekçe girmeniz gerekmektedir.`;
            }
            if (gerekceEl) gerekceEl.value = '';
            if (formEl) formEl.setAttribute('action', trigger.dataset.takvimIptalUrl || '');

            openModal(iptalModal);
            gerekceEl?.focus();
        });

        formEl?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const gerekce = (gerekceEl?.value || '').trim();
            if (!gerekce) {
                showToast('İptal gerekçesi girilmelidir.', 'error');
                gerekceEl?.focus();
                return;
            }
            if (gerekceInput) gerekceInput.value = gerekce;

            const previousLabel = confirmBtn?.textContent;
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'İşleniyor...';
            }

            try {
                await window.axios.post(formEl.getAttribute('action'), new FormData(formEl), {
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                reloadTakvimTab();
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = previousLabel || 'İptal Et';
                }
            }
        });
    }

    if (iptalGeriAlModal) {
        const bodyEl = iptalGeriAlModal.querySelector('[data-ders-iptal-geri-al-body]');
        const formEl = iptalGeriAlModal.querySelector('[data-ders-iptal-geri-al-form]');
        const confirmBtn = iptalGeriAlModal.querySelector('[data-ders-iptal-geri-al-confirm]');

        iptalGeriAlModal.querySelectorAll('[data-ders-iptal-geri-al-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(iptalGeriAlModal));
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-takvim-iptal-geri-al]');
            if (!trigger) return;
            event.preventDefault();

            if (bodyEl) {
                bodyEl.textContent = `${trigger.dataset.takvimIptalGeriAlOzet || ''} dersinin iptalini geri almak istediğinize emin misiniz?`;
            }
            if (formEl) formEl.setAttribute('action', trigger.dataset.takvimIptalGeriAlUrl || '');

            openModal(iptalGeriAlModal);
        });

        formEl?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const previousLabel = confirmBtn?.textContent;
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'İşleniyor...';
            }

            try {
                await window.axios.post(formEl.getAttribute('action'), new FormData(formEl), {
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                reloadTakvimTab();
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = previousLabel || 'İptali Geri Al';
                }
            }
        });
    }

    if (tarihModal) {
        const ozetEl = tarihModal.querySelector('[data-ders-tarih-ozet]');
        const tarihEl = tarihModal.querySelector('[data-ders-tarih-yeni]');
        const tarihInput = tarihModal.querySelector('[data-ders-tarih-yeni-input]');
        const formEl = tarihModal.querySelector('[data-ders-tarih-form]');
        const confirmBtn = tarihModal.querySelector('[data-ders-tarih-confirm]');

        tarihModal.querySelectorAll('[data-ders-tarih-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(tarihModal));
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-takvim-tarih]');
            if (!trigger) return;
            event.preventDefault();

            if (trigger.dataset.takvimTarihYoklamaAlindi === '1') {
                showToast('Yoklaması alınmış bir dersin tarihi değiştirilemez.', 'error');
                return;
            }

            if (ozetEl) {
                ozetEl.textContent = `${trigger.dataset.takvimTarihOzet || ''} dersi için yeni tarih seçiniz.`;
            }
            if (tarihEl) tarihEl.value = trigger.dataset.takvimTarihMevcut || '';
            if (formEl) formEl.setAttribute('action', trigger.dataset.takvimTarihUrl || '');

            openModal(tarihModal);
            tarihEl?.focus();
        });

        formEl?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const yeniTarih = tarihEl?.value || '';
            if (!yeniTarih) {
                showToast('Yeni tarih seçilmelidir.', 'error');
                tarihEl?.focus();
                return;
            }
            if (tarihInput) tarihInput.value = yeniTarih;

            const previousLabel = confirmBtn?.textContent;
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Kaydediliyor...';
            }

            try {
                await window.axios.post(formEl.getAttribute('action'), new FormData(formEl), {
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                reloadTakvimTab();
            } catch (error) {
                showToast(validationMessage(error), 'error');
            } finally {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = previousLabel || 'Kaydet';
                }
            }
        });
    }
}

function initTakvimPanel() {
    const panel = document.querySelector('[data-takvim-panel]');
    if (!panel) return;

    const listeView = panel.querySelector('[data-takvim-liste]');
    const aylikView = panel.querySelector('[data-takvim-aylik]');
    const viewButtons = panel.querySelectorAll('[data-takvim-view]');
    const grid = panel.querySelector('[data-takvim-grid]');
    const monthLabel = panel.querySelector('[data-takvim-month-label]');
    const dayDetail = panel.querySelector('[data-takvim-day-detail]');
    const dayTitle = panel.querySelector('[data-takvim-day-title]');
    const dayList = panel.querySelector('[data-takvim-day-list]');
    const pdfLink = panel.querySelector('[data-takvim-pdf]');
    const pdfBase = panel.dataset.takvimPdfBase || pdfLink?.getAttribute('href') || '';

    let dersler = [];
    try {
        dersler = JSON.parse(panel.dataset.takvimDersler || '[]');
    } catch {
        dersler = [];
    }

    const byDate = dersler.reduce((map, ders) => {
        if (!ders.tarih) return map;
        if (!map[ders.tarih]) map[ders.tarih] = [];
        map[ders.tarih].push(ders);
        return map;
    }, {});

    const firstDers = dersler.find((d) => d.tarih);
    let cursor = firstDers?.tarih
        ? new Date(`${firstDers.tarih}T00:00:00`)
        : new Date();
    cursor = new Date(cursor.getFullYear(), cursor.getMonth(), 1);

    const todayKey = new Date().toISOString().slice(0, 10);
    const monthNames = [
        'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
        'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık',
    ];
    const weekDays = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

    function formatKey(year, month, day) {
        return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    function syncPdfLink() {
        if (!pdfLink || !pdfBase) return;
        const ay = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}`;
        const url = new URL(pdfBase, window.location.origin);
        url.searchParams.set('ay', ay);
        pdfLink.href = url.pathname + url.search;
    }

    function setView(name) {
        viewButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.takvimView === name);
        });
        if (listeView) listeView.hidden = name !== 'liste';
        if (aylikView) aylikView.hidden = name !== 'aylik';
        if (name === 'aylik') {
            renderMonth();
        }
    }

    function showDayDetail(dateKey) {
        if (!dayDetail || !dayTitle || !dayList) return;
        const items = byDate[dateKey] || [];
        if (!items.length) {
            dayDetail.hidden = true;
            return;
        }

        const [y, m, d] = dateKey.split('-').map(Number);
        const date = new Date(y, m - 1, d);
        dayTitle.textContent = date.toLocaleDateString('tr-TR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
        dayList.innerHTML = items.map((ders) => {
            let status = ders.yoklama_alindi
                ? 'Alındı'
                : (dateKey <= todayKey ? 'Bekliyor' : 'Planlandı');
            let statusClass = ders.yoklama_alindi ? 'status-tamamlanan' : 'status-hazirlik';
            if (ders.iptal_edildi) {
                status = 'İptal Edildi';
                statusClass = 'status-iptal';
            }
            return `<li>
                <strong>${ders.baslangic} – ${ders.bitis}</strong>
                <span>${escapeHtml(ders.kurs_adi || '')}</span>
                <span>${ders.ders_saati} saat</span>
                <span>${ders.sinif || 'Sınıf yok'}</span>
                <span class="status ${statusClass}">${status}</span>
            </li>`;
        }).join('');
        dayDetail.hidden = false;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function renderMonth() {
        if (!grid || !monthLabel) return;

        const year = cursor.getFullYear();
        const month = cursor.getMonth();
        monthLabel.textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1);
        // Monday-first: Sun=0 -> 6, Mon=1 -> 0 ...
        let startOffset = firstDay.getDay() - 1;
        if (startOffset < 0) startOffset = 6;

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells = [];

        weekDays.forEach((label) => {
            cells.push(`<div class="takvim-cell takvim-cell-head">${label}</div>`);
        });

        for (let i = 0; i < startOffset; i++) {
            cells.push('<div class="takvim-cell is-empty"></div>');
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const key = formatKey(year, month, day);
            const lessons = byDate[key] || [];
            const hasDers = lessons.length > 0;
            const isToday = key === todayKey;
            const classes = [
                'takvim-cell',
                hasDers ? 'has-ders' : '',
                isToday ? 'is-today' : '',
            ].filter(Boolean).join(' ');

            const lessonHtml = hasDers
                ? `<span class="takvim-day-lessons">${lessons.map((ders) => `
                    <span class="takvim-day-lesson${ders.iptal_edildi ? ' is-cancelled' : ''}">
                        <span class="takvim-day-lesson-time">${ders.baslangic}–${ders.bitis}${ders.iptal_edildi ? ' · İptal' : ''}</span>
                        <span class="takvim-day-lesson-name">${escapeHtml(ders.kurs_adi || '')}</span>
                    </span>
                `).join('')}</span>`
                : '';

            cells.push(`
                <button type="button" class="${classes}" data-takvim-day="${key}" ${hasDers ? '' : 'disabled'}>
                    <span class="takvim-day-num">${day}</span>
                    ${lessonHtml}
                </button>
            `);
        }

        grid.innerHTML = cells.join('');
        syncPdfLink();
    }

    viewButtons.forEach((btn) => {
        btn.addEventListener('click', () => setView(btn.dataset.takvimView || 'liste'));
    });

    panel.querySelector('[data-takvim-prev]')?.addEventListener('click', () => {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
        renderMonth();
    });

    panel.querySelector('[data-takvim-next]')?.addEventListener('click', () => {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
        renderMonth();
    });

    grid?.addEventListener('click', (event) => {
        const cell = event.target.closest('[data-takvim-day]');
        if (!cell || cell.disabled) return;
        showDayDetail(cell.dataset.takvimDay);
        grid.querySelectorAll('.takvim-cell.is-selected').forEach((el) => el.classList.remove('is-selected'));
        cell.classList.add('is-selected');
    });

    setView('liste');
}

function initSmsModal() {
    const modal = document.getElementById('sms-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const mesajInput = modal.querySelector('[data-sms-mesaj]');
    const charCount = modal.querySelector('[data-sms-char-count]');
    const listEl = modal.querySelector('[data-sms-alicilar-list]');
    const countEl = modal.querySelector('[data-sms-alicilar-count]');
    const sendBtn = modal.querySelector('[data-sms-send]');
    const clearAllBtn = modal.querySelector('[data-sms-clear-all]');
    const searchInput = modal.querySelector('[data-sms-search]');
    const searchResults = modal.querySelector('[data-sms-search-results]');
    const filterButtons = modal.querySelectorAll('[data-sms-filter]');
    const alicilarUrl = modal.dataset.smsAlicilarUrl || '';

    let allAlicilar = [];
    let visibleIds = new Set();
    let currentFilter = 'tumu';
    let loading = false;

    function setModalOpen(open) {
        modal.hidden = !open;
        document.body.classList.toggle('modal-open', open);
        if (!open) {
            hideSearchResults();
            if (searchInput) searchInput.value = '';
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase('tr-TR')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function filteredAlicilar() {
        return allAlicilar.filter((item) => {
            if (!visibleIds.has(String(item.id))) return false;
            if (currentFilter === 'tumu') return true;
            return item.durum_kod === currentFilter;
        });
    }

    function renderAlicilar() {
        if (!listEl) return;
        const items = filteredAlicilar();

        if (countEl) {
            countEl.textContent = `${items.length} kişi`;
        }

        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Bu filtreye uygun alıcı yok. Arama ile ekleyebilirsiniz.</p>';
            return;
        }

        listEl.innerHTML = items.map((item) => `
            <span class="sms-alici-chip ${item.telefon_var ? '' : 'is-no-phone'}" data-sms-alici-id="${item.id}" title="${item.telefon_var ? '' : 'Telefon yok'}">
                <span class="sms-alici-chip-name">${escapeHtml(item.ad)}</span>
                <button
                    type="button"
                    class="sms-alici-chip-remove"
                    data-sms-alici-remove="${item.id}"
                    title="Listeden kaldır"
                    aria-label="${escapeHtml(item.ad)} kişisini kaldır"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </span>
        `).join('');
    }

    function setFilter(kod) {
        currentFilter = kod || 'tumu';
        filterButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.smsFilter === currentFilter);
        });
        renderAlicilar();
    }

    function hideSearchResults() {
        if (!searchResults) return;
        searchResults.hidden = true;
        searchResults.innerHTML = '';
    }

    function searchCandidates(query) {
        const q = normalizeSearch(query);
        const qDigits = digitsOnly(query);
        if (q.length < 2 && qDigits.length < 3) {
            return [];
        }

        return allAlicilar
            .filter((item) => !visibleIds.has(String(item.id)))
            .filter((item) => {
                const ad = normalizeSearch(item.ad);
                const tc = digitsOnly(item.tc);
                if (q && ad.includes(q)) return true;
                if (qDigits.length >= 3 && tc.includes(qDigits)) return true;
                return false;
            })
            .slice(0, 12);
    }

    function renderSearchResults(query) {
        if (!searchResults) return;
        const items = searchCandidates(query);

        if (!normalizeSearch(query) && digitsOnly(query).length < 3) {
            hideSearchResults();
            return;
        }

        if (!items.length) {
            searchResults.hidden = false;
            searchResults.innerHTML = '<p class="sms-alici-search-empty">Eklenebilir alıcı bulunamadı.</p>';
            return;
        }

        searchResults.hidden = false;
        searchResults.innerHTML = items.map((item) => `
            <button type="button" class="sms-alici-search-item" data-sms-add-id="${item.id}">
                <strong>${escapeHtml(item.ad)}</strong>
                <small>${escapeHtml(item.tc || 'TC yok')}${item.durum_ad ? ` · ${escapeHtml(item.durum_ad)}` : ''}${item.telefon_var ? '' : ' · Telefon yok'}</small>
            </button>
        `).join('');
    }

    function addAlici(id) {
        const key = String(id);
        const item = allAlicilar.find((row) => String(row.id) === key);
        if (!item) return;

        visibleIds.add(key);
        if (currentFilter !== 'tumu' && item.durum_kod !== currentFilter) {
            setFilter('tumu');
        } else {
            renderAlicilar();
        }

        if (searchInput) searchInput.value = '';
        hideSearchResults();
        showToast(`${item.ad} listeye eklendi.`, 'success');
    }

    async function loadAlicilar() {
        if (!alicilarUrl || !listEl) return;
        loading = true;
        listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yükleniyor...</p>';
        if (countEl) countEl.textContent = '…';
        hideSearchResults();
        if (searchInput) searchInput.value = '';

        try {
            const { data } = await window.axios.get(alicilarUrl, {
                headers: { Accept: 'application/json' },
            });
            allAlicilar = Array.isArray(data.alicilar) ? data.alicilar : [];
            visibleIds = new Set(allAlicilar.map((item) => String(item.id)));
            renderAlicilar();
        } catch (error) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yüklenemedi.</p>';
            showToast(validationMessage(error), 'error');
        } finally {
            loading = false;
        }
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-sms-modal-open]');
        if (!btn || btn.disabled) return;
        setFilter('tumu');
        if (mesajInput) mesajInput.value = '';
        if (charCount) charCount.textContent = '0';
        setModalOpen(true);
        loadAlicilar();
        searchInput?.focus();
    });

    modal.querySelectorAll('[data-sms-modal-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(false));
    });

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => setFilter(btn.dataset.smsFilter || 'tumu'));
    });

    clearAllBtn?.addEventListener('click', () => {
        visibleIds.clear();
        hideSearchResults();
        renderAlicilar();
        showToast('Tüm alıcılar temizlendi.', 'success');
        searchInput?.focus();
    });

    listEl?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-sms-alici-remove]');
        if (!removeBtn) return;
        visibleIds.delete(String(removeBtn.dataset.smsAliciRemove));
        renderAlicilar();
        renderSearchResults(searchInput?.value || '');
    });

    searchInput?.addEventListener('input', () => {
        renderSearchResults(searchInput.value);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideSearchResults();
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            const first = searchResults?.querySelector('[data-sms-add-id]');
            if (first) {
                addAlici(first.dataset.smsAddId);
            }
        }
    });

    searchResults?.addEventListener('click', (event) => {
        const itemBtn = event.target.closest('[data-sms-add-id]');
        if (!itemBtn) return;
        addAlici(itemBtn.dataset.smsAddId);
    });

    document.addEventListener('click', (event) => {
        if (modal.hidden) return;
        if (event.target.closest('[data-sms-search-wrap]')) return;
        hideSearchResults();
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) {
            charCount.textContent = String(mesajInput.value.length);
        }
    });

    modal.querySelectorAll('[data-sms-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!mesajInput) return;
            const token = btn.getAttribute('data-sms-insert') || '';
            if (!token) return;

            const start = mesajInput.selectionStart ?? mesajInput.value.length;
            const end = mesajInput.selectionEnd ?? start;
            const before = mesajInput.value.slice(0, start);
            const after = mesajInput.value.slice(end);
            const next = `${before}${token}${after}`.slice(0, Number(mesajInput.maxLength) || 480);
            mesajInput.value = next;
            const cursor = Math.min(start + token.length, next.length);
            mesajInput.focus();
            mesajInput.setSelectionRange(cursor, cursor);
            if (charCount) {
                charCount.textContent = String(mesajInput.value.length);
            }
        });
    });

    function personalizeSms(template, adSoyad) {
        return String(template || '').replaceAll(/\{ad_soyad\}/gi, adSoyad || '');
    }

    function openSmsOnizleme() {
        const sablon = mesajInput?.value?.trim() || '';
        if (!sablon) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            mesajInput?.focus();
            return;
        }

        const alicilar = filteredAlicilar();
        if (!alicilar.length) {
            showToast('Önizleme için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const ilkGonderilecek = alicilar.find((item) => item.telefon_var) || alicilar[0];
        const onizlemeModal = document.getElementById('sms-onizleme-modal');
        const aliciEl = onizlemeModal?.querySelector('[data-sms-onizleme-alici]');
        const mesajEl = onizlemeModal?.querySelector('[data-sms-onizleme-mesaj]');
        if (!onizlemeModal || !mesajEl) return;

        const kisisel = personalizeSms(sablon, ilkGonderilecek.ad || 'Ad Soyad');
        if (aliciEl) {
            const telefonNotu = ilkGonderilecek.telefon_var
                ? ''
                : ' <span>(telefon yok — gönderimde atlanır)</span>';
            aliciEl.innerHTML = `İlk gönderilecek: <strong>${escapeHtml(ilkGonderilecek.ad || 'İsimsiz')}</strong>${telefonNotu}`;
        }
        mesajEl.textContent = kisisel;
        openModal(onizlemeModal);
    }

    modal.querySelector('[data-sms-onizle]')?.addEventListener('click', openSmsOnizleme);

    const onizlemeModal = document.getElementById('sms-onizleme-modal');
    if (onizlemeModal && onizlemeModal.dataset.bound !== '1') {
        onizlemeModal.dataset.bound = '1';
        onizlemeModal.querySelectorAll('[data-sms-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(onizlemeModal));
        });
    }

    sendBtn?.addEventListener('click', async () => {
        const mesaj = mesajInput?.value?.trim() || '';
        const ids = filteredAlicilar().map((item) => item.id);

        if (!mesaj) {
            showToast('SMS metni zorunludur.', 'error');
            mesajInput?.focus();
            return;
        }

        if (!ids.length) {
            showToast('SMS göndermek için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const url = sendBtn.dataset.smsUrl;
        if (!url || loading) return;

        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesaj', mesaj);
            formData.append('basvuru_durum', currentFilter);
            ids.forEach((id) => formData.append('basvuru_ids[]', id));

            const { data } = await window.axios.post(url, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            setModalOpen(false);
            showToast(data.message || 'SMS gönderildi.', 'success');

            const pageUrl = new URL(window.location.href);
            pageUrl.searchParams.set('tab', 'mesajlar');
            pageUrl.searchParams.set('kanal', 'sms');
            setTimeout(() => {
                window.location.href = pageUrl.pathname + pageUrl.search;
            }, 700);
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            sendBtn.disabled = false;
        }
    });
}

function initSmsAlicilarDetayModal() {
    const modal = document.getElementById('sms-alicilar-detay-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const listEl = modal.querySelector('[data-sms-alicilar-detay-list]');

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function parseDetay(raw) {
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function renderDetay(items) {
        if (!listEl) return;
        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-detay-empty">Alıcı bilgisi bulunamadı.</p>';
            return;
        }

        listEl.innerHTML = items
            .map((item) => {
                const ad = escapeHtml(item?.ad || 'İsimsiz');
                const telefon = item?.telefon ? escapeHtml(item.telefon) : 'Telefon yok';
                const gonderildi = item?.durum === 'gonderildi';
                const statusClass = gonderildi ? 'status-aktif' : 'status-iptal';
                const statusText = gonderildi ? 'Gönderildi' : 'Atlandı';
                const hata = item?.hata && !gonderildi
                    ? `<div class="sms-alicilar-detay-hata">${escapeHtml(item.hata)}</div>`
                    : '';
                const mesaj = item?.mesaj
                    ? `<div class="sms-alicilar-detay-telefon">${escapeHtml(item.mesaj)}</div>`
                    : '';

                return `
                    <div class="sms-alicilar-detay-item">
                        <div class="sms-alicilar-detay-info">
                            <div class="sms-alicilar-detay-ad">${ad}</div>
                            <div class="sms-alicilar-detay-telefon">${telefon}</div>
                            ${mesaj}
                            ${hata}
                        </div>
                        <span class="status ${statusClass}">${statusText}</span>
                    </div>
                `;
            })
            .join('');
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-sms-alicilar-detay-ac]');
        if (!btn) return;
        renderDetay(parseDetay(btn.getAttribute('data-sms-alicilar-detay')));
        openModal(modal);
    });

    modal.querySelectorAll('[data-sms-alicilar-detay-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
}

function initMesajKanalTabs() {
    const panel = document.querySelector('[data-lesson-panel="mesajlar"]');
    if (!panel || panel.dataset.mesajKanalBound === '1') return;
    panel.dataset.mesajKanalBound = '1';

    function setKanal(kanal) {
        const next = kanal === 'eposta' ? 'eposta' : 'sms';
        const tabs = panel.querySelectorAll('[data-mesaj-kanal]');
        const panels = panel.querySelectorAll('[data-mesaj-kanal-panel]');

        tabs.forEach((tab) => {
            const active = tab.dataset.mesajKanal === next;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((el) => {
            el.hidden = el.dataset.mesajKanalPanel !== next;
        });

        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'mesajlar');
        url.searchParams.set('kanal', next);
        window.history.replaceState({}, '', url.pathname + url.search);
    }

    panel.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-mesaj-kanal]');
        if (!tab || !panel.contains(tab)) return;
        setKanal(tab.dataset.mesajKanal || 'sms');
    });
}

function initEpostaModal() {
    const modal = document.getElementById('eposta-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const konuInput = modal.querySelector('[data-eposta-konu]');
    const mesajInput = modal.querySelector('[data-eposta-mesaj]');
    const charCount = modal.querySelector('[data-eposta-char-count]');
    const listEl = modal.querySelector('[data-eposta-alicilar-list]');
    const countEl = modal.querySelector('[data-eposta-alicilar-count]');
    const sendBtn = modal.querySelector('[data-eposta-send]');
    const clearAllBtn = modal.querySelector('[data-eposta-clear-all]');
    const searchInput = modal.querySelector('[data-eposta-search]');
    const searchResults = modal.querySelector('[data-eposta-search-results]');
    const filterButtons = modal.querySelectorAll('[data-eposta-filter]');
    const alicilarUrl = modal.dataset.epostaAlicilarUrl || '';

    let allAlicilar = [];
    let visibleIds = new Set();
    let currentFilter = 'tumu';
    let loading = false;

    function setModalOpen(open) {
        modal.hidden = !open;
        document.body.classList.toggle('modal-open', open);
        if (!open) {
            hideSearchResults();
            if (searchInput) searchInput.value = '';
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase('tr-TR')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function filteredAlicilar() {
        return allAlicilar.filter((item) => {
            if (!visibleIds.has(String(item.id))) return false;
            if (currentFilter === 'tumu') return true;
            return item.durum_kod === currentFilter;
        });
    }

    function renderAlicilar() {
        if (!listEl) return;
        const items = filteredAlicilar();

        if (countEl) {
            countEl.textContent = `${items.length} kişi`;
        }

        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Bu filtreye uygun alıcı yok. Arama ile ekleyebilirsiniz.</p>';
            return;
        }

        listEl.innerHTML = items.map((item) => `
            <span class="sms-alici-chip ${item.email_var ? '' : 'is-no-email'}" data-eposta-alici-id="${item.id}" title="${item.email_var ? '' : 'E-posta yok'}">
                <span class="sms-alici-chip-name">${escapeHtml(item.ad)}</span>
                <button
                    type="button"
                    class="sms-alici-chip-remove"
                    data-eposta-alici-remove="${item.id}"
                    title="Listeden kaldır"
                    aria-label="${escapeHtml(item.ad)} kişisini kaldır"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </span>
        `).join('');
    }

    function setFilter(kod) {
        currentFilter = kod || 'tumu';
        filterButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.epostaFilter === currentFilter);
        });
        renderAlicilar();
    }

    function hideSearchResults() {
        if (!searchResults) return;
        searchResults.hidden = true;
        searchResults.innerHTML = '';
    }

    function searchCandidates(query) {
        const q = normalizeSearch(query);
        const qDigits = digitsOnly(query);
        if (q.length < 2 && qDigits.length < 3) {
            return [];
        }

        return allAlicilar
            .filter((item) => !visibleIds.has(String(item.id)))
            .filter((item) => {
                const ad = normalizeSearch(item.ad);
                const tc = digitsOnly(item.tc);
                if (q && ad.includes(q)) return true;
                if (qDigits.length >= 3 && tc.includes(qDigits)) return true;
                return false;
            })
            .slice(0, 12);
    }

    function renderSearchResults(query) {
        if (!searchResults) return;
        const items = searchCandidates(query);

        if (!normalizeSearch(query) && digitsOnly(query).length < 3) {
            hideSearchResults();
            return;
        }

        if (!items.length) {
            searchResults.hidden = false;
            searchResults.innerHTML = '<p class="sms-alici-search-empty">Eklenebilir alıcı bulunamadı.</p>';
            return;
        }

        searchResults.hidden = false;
        searchResults.innerHTML = items.map((item) => `
            <button type="button" class="sms-alici-search-item" data-eposta-add-id="${item.id}">
                <strong>${escapeHtml(item.ad)}</strong>
                <small>${escapeHtml(item.tc || 'TC yok')}${item.durum_ad ? ` · ${escapeHtml(item.durum_ad)}` : ''}${item.email_var ? '' : ' · E-posta yok'}</small>
            </button>
        `).join('');
    }

    function addAlici(id) {
        const key = String(id);
        const item = allAlicilar.find((row) => String(row.id) === key);
        if (!item) return;

        visibleIds.add(key);
        if (currentFilter !== 'tumu' && item.durum_kod !== currentFilter) {
            setFilter('tumu');
        } else {
            renderAlicilar();
        }

        if (searchInput) searchInput.value = '';
        hideSearchResults();
        showToast(`${item.ad} listeye eklendi.`, 'success');
    }

    async function loadAlicilar() {
        if (!alicilarUrl || !listEl) return;
        loading = true;
        listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yükleniyor...</p>';
        if (countEl) countEl.textContent = '…';
        hideSearchResults();
        if (searchInput) searchInput.value = '';

        try {
            const { data } = await window.axios.get(alicilarUrl, {
                headers: { Accept: 'application/json' },
            });
            allAlicilar = Array.isArray(data.alicilar) ? data.alicilar : [];
            visibleIds = new Set(allAlicilar.map((item) => String(item.id)));
            renderAlicilar();
        } catch (error) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yüklenemedi.</p>';
            showToast(validationMessage(error), 'error');
        } finally {
            loading = false;
        }
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-eposta-modal-open]');
        if (!btn || btn.disabled) return;
        setFilter('tumu');
        if (konuInput) konuInput.value = '';
        if (mesajInput) mesajInput.value = '';
        if (charCount) charCount.textContent = '0';
        setModalOpen(true);
        loadAlicilar();
        searchInput?.focus();
    });

    modal.querySelectorAll('[data-eposta-modal-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(false));
    });

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => setFilter(btn.dataset.epostaFilter || 'tumu'));
    });

    clearAllBtn?.addEventListener('click', () => {
        visibleIds.clear();
        hideSearchResults();
        renderAlicilar();
        showToast('Tüm alıcılar temizlendi.', 'success');
        searchInput?.focus();
    });

    listEl?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-eposta-alici-remove]');
        if (!removeBtn) return;
        visibleIds.delete(String(removeBtn.dataset.epostaAliciRemove));
        renderAlicilar();
        renderSearchResults(searchInput?.value || '');
    });

    searchInput?.addEventListener('input', () => {
        renderSearchResults(searchInput.value);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideSearchResults();
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            const first = searchResults?.querySelector('[data-eposta-add-id]');
            if (first) {
                addAlici(first.dataset.epostaAddId);
            }
        }
    });

    searchResults?.addEventListener('click', (event) => {
        const itemBtn = event.target.closest('[data-eposta-add-id]');
        if (!itemBtn) return;
        addAlici(itemBtn.dataset.epostaAddId);
    });

    document.addEventListener('click', (event) => {
        if (modal.hidden) return;
        if (event.target.closest('[data-eposta-search-wrap]')) return;
        hideSearchResults();
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) {
            charCount.textContent = String(mesajInput.value.length);
        }
    });

    function personalizeEposta(template, adSoyad) {
        return String(template || '').replaceAll(/\{ad_soyad\}/gi, adSoyad || '');
    }

    function insertEpostaToken(target, token) {
        const input = target === 'konu' ? konuInput : mesajInput;
        if (!input || !token) return;

        if (typeof input.selectionStart === 'number') {
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? start;
            const before = input.value.slice(0, start);
            const after = input.value.slice(end);
            const maxLen = Number(input.maxLength) || (target === 'konu' ? 200 : 5000);
            const next = `${before}${token}${after}`.slice(0, maxLen);
            input.value = next;
            const cursor = Math.min(start + token.length, next.length);
            input.focus();
            input.setSelectionRange(cursor, cursor);
        } else {
            input.value = `${input.value}${token}`.slice(0, Number(input.maxLength) || 200);
            input.focus();
        }

        if (target === 'mesaj' && charCount) {
            charCount.textContent = String(mesajInput.value.length);
        }
    }

    modal.querySelectorAll('[data-eposta-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            insertEpostaToken(
                btn.getAttribute('data-eposta-insert-target') || 'mesaj',
                btn.getAttribute('data-eposta-insert') || '',
            );
        });
    });

    function openEpostaOnizleme() {
        const konu = konuInput?.value?.trim() || '';
        const mesaj = mesajInput?.value?.trim() || '';

        if (!konu) {
            showToast('Önizleme için önce konu yazın.', 'error');
            konuInput?.focus();
            return;
        }

        if (!mesaj) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            mesajInput?.focus();
            return;
        }

        const alicilar = filteredAlicilar();
        if (!alicilar.length) {
            showToast('Önizleme için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const ilkGonderilecek = alicilar.find((item) => item.email_var) || alicilar[0];
        const onizlemeModal = document.getElementById('eposta-onizleme-modal');
        const aliciEl = onizlemeModal?.querySelector('[data-eposta-onizleme-alici]');
        const kimeEl = onizlemeModal?.querySelector('[data-eposta-onizleme-kime]');
        const konuEl = onizlemeModal?.querySelector('[data-eposta-onizleme-konu]');
        const mesajEl = onizlemeModal?.querySelector('[data-eposta-onizleme-mesaj]');
        if (!onizlemeModal || !konuEl || !mesajEl) return;

        if (aliciEl) {
            const emailNotu = ilkGonderilecek.email_var
                ? ''
                : ' <span>(e-posta yok — gönderimde atlanır)</span>';
            aliciEl.innerHTML = `İlk gönderilecek: <strong>${escapeHtml(ilkGonderilecek.ad || 'İsimsiz')}</strong>${emailNotu}`;
        }
        if (kimeEl) {
            kimeEl.textContent = ilkGonderilecek.email || 'E-posta yok';
        }
        konuEl.textContent = personalizeEposta(konu, ilkGonderilecek.ad || 'Ad Soyad');
        mesajEl.textContent = personalizeEposta(mesaj, ilkGonderilecek.ad || 'Ad Soyad');
        openModal(onizlemeModal);
    }

    modal.querySelector('[data-eposta-onizle]')?.addEventListener('click', openEpostaOnizleme);

    const epostaOnizlemeModal = document.getElementById('eposta-onizleme-modal');
    if (epostaOnizlemeModal && epostaOnizlemeModal.dataset.bound !== '1') {
        epostaOnizlemeModal.dataset.bound = '1';
        epostaOnizlemeModal.querySelectorAll('[data-eposta-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(epostaOnizlemeModal));
        });
    }

    sendBtn?.addEventListener('click', async () => {
        const konu = konuInput?.value?.trim() || '';
        const mesaj = mesajInput?.value?.trim() || '';
        const ids = filteredAlicilar().map((item) => item.id);

        if (!konu) {
            showToast('E-posta konusu zorunludur.', 'error');
            konuInput?.focus();
            return;
        }

        if (!mesaj) {
            showToast('E-posta metni zorunludur.', 'error');
            mesajInput?.focus();
            return;
        }

        if (!ids.length) {
            showToast('E-posta göndermek için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const url = sendBtn.dataset.epostaUrl;
        if (!url || loading) return;

        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('konu', konu);
            formData.append('mesaj', mesaj);
            formData.append('basvuru_durum', currentFilter);
            ids.forEach((id) => formData.append('basvuru_ids[]', id));

            const { data } = await window.axios.post(url, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            setModalOpen(false);
            showToast(data.message || 'E-posta gönderildi.', 'success');

            const pageUrl = new URL(window.location.href);
            pageUrl.searchParams.set('tab', 'mesajlar');
            pageUrl.searchParams.set('kanal', 'eposta');
            setTimeout(() => {
                window.location.href = pageUrl.pathname + pageUrl.search;
            }, 700);
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            sendBtn.disabled = false;
        }
    });
}

function initEpostaAlicilarDetayModal() {
    const modal = document.getElementById('eposta-alicilar-detay-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const listEl = modal.querySelector('[data-eposta-alicilar-detay-list]');

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function parseDetay(raw) {
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function renderDetay(items) {
        if (!listEl) return;
        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-detay-empty">Alıcı bilgisi bulunamadı.</p>';
            return;
        }

        listEl.innerHTML = items
            .map((item) => {
                const ad = escapeHtml(item?.ad || 'İsimsiz');
                const email = item?.email ? escapeHtml(item.email) : 'E-posta yok';
                const gonderildi = item?.durum === 'gonderildi';
                const statusClass = gonderildi ? 'status-aktif' : 'status-iptal';
                const statusText = gonderildi ? 'Gönderildi' : 'Atlandı';
                const hata = item?.hata && !gonderildi
                    ? `<div class="sms-alicilar-detay-hata">${escapeHtml(item.hata)}</div>`
                    : '';
                const konu = item?.konu
                    ? `<div class="sms-alicilar-detay-telefon"><strong>Konu:</strong> ${escapeHtml(item.konu)}</div>`
                    : '';
                const mesaj = item?.mesaj
                    ? `<div class="sms-alicilar-detay-telefon">${escapeHtml(item.mesaj)}</div>`
                    : '';

                return `
                    <div class="sms-alicilar-detay-item">
                        <div class="sms-alicilar-detay-info">
                            <div class="sms-alicilar-detay-ad">${ad}</div>
                            <div class="sms-alicilar-detay-telefon">${email}</div>
                            ${konu}
                            ${mesaj}
                            ${hata}
                        </div>
                        <span class="status ${statusClass}">${statusText}</span>
                    </div>
                `;
            })
            .join('');
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-eposta-alicilar-detay-ac]');
        if (!btn) return;
        renderDetay(parseDetay(btn.getAttribute('data-eposta-alicilar-detay')));
        openModal(modal);
    });

    modal.querySelectorAll('[data-eposta-alicilar-detay-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
}

export function initBasvuruMesajModallari() {
    const smsModal = document.getElementById('basvuru-sms-modal');
    const epostaModal = document.getElementById('basvuru-eposta-modal');
    if (!smsModal && !epostaModal) return;
    if (document.body.dataset.basvuruMesajModallariBound === '1') return;
    document.body.dataset.basvuruMesajModallariBound = '1';

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function personalize(template, adSoyad) {
        return String(template || '').replaceAll(/\{ad_soyad\}/gi, adSoyad || '');
    }

    function insertToken(input, token) {
        if (!input) return;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        const before = input.value.slice(0, start);
        const after = input.value.slice(end);
        const next = `${before}${token}${after}`.slice(0, Number(input.maxLength) || undefined);
        input.value = next;
        const cursor = Math.min(start + token.length, next.length);
        input.focus();
        input.setSelectionRange(cursor, cursor);
        input.dispatchEvent(new Event('input'));
    }

    const smsAliciEl = smsModal?.querySelector('[data-basvuru-sms-alici]');
    const smsNoTelefonEl = smsModal?.querySelector('[data-basvuru-sms-no-telefon]');
    const smsMesajInput = smsModal?.querySelector('[data-basvuru-sms-mesaj]');
    const smsCharCount = smsModal?.querySelector('[data-basvuru-sms-char-count]');
    const smsSendBtn = smsModal?.querySelector('[data-basvuru-sms-send]');

    let pendingSmsUrl = '';
    let pendingSmsId = '';
    let pendingSmsAd = '';
    let pendingSmsTelefonVar = true;
    let smsSending = false;

    smsMesajInput?.addEventListener('input', () => {
        if (smsCharCount) smsCharCount.textContent = String(smsMesajInput.value.length);
    });

    smsModal?.querySelector('[data-basvuru-sms-insert]')?.addEventListener('click', (event) => {
        const token = event.currentTarget.getAttribute('data-basvuru-sms-insert') || '';
        if (!token || !smsMesajInput) return;
        insertToken(smsMesajInput, token);
        if (smsCharCount) smsCharCount.textContent = String(smsMesajInput.value.length);
    });

    smsModal?.querySelectorAll('[data-basvuru-sms-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(smsModal));
    });

    smsModal?.querySelector('[data-basvuru-sms-onizle]')?.addEventListener('click', () => {
        const sablon = smsMesajInput?.value?.trim() || '';
        if (!sablon) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            smsMesajInput?.focus();
            return;
        }

        const onizlemeModal = document.getElementById('sms-onizleme-modal');
        const aliciEl = onizlemeModal?.querySelector('[data-sms-onizleme-alici]');
        const mesajEl = onizlemeModal?.querySelector('[data-sms-onizleme-mesaj]');
        if (!onizlemeModal || !mesajEl) return;

        const kisisel = personalize(sablon, pendingSmsAd || 'Ad Soyad');
        if (aliciEl) {
            const not = pendingSmsTelefonVar ? '' : ' <span>(telefon yok — gönderim engellenir)</span>';
            aliciEl.innerHTML = `Alıcı: <strong>${escapeHtml(pendingSmsAd || 'İsimsiz')}</strong>${not}`;
        }
        mesajEl.textContent = kisisel;
        openModal(onizlemeModal);
    });

    const smsOnizlemeModal = document.getElementById('sms-onizleme-modal');
    if (smsOnizlemeModal && smsOnizlemeModal.dataset.bound !== '1') {
        smsOnizlemeModal.dataset.bound = '1';
        smsOnizlemeModal.querySelectorAll('[data-sms-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(smsOnizlemeModal));
        });
    }

    smsSendBtn?.addEventListener('click', async () => {
        const mesaj = smsMesajInput?.value?.trim() || '';

        if (!mesaj) {
            showToast('SMS metni zorunludur.', 'error');
            smsMesajInput?.focus();
            return;
        }

        if (!pendingSmsTelefonVar) {
            showToast('Bu kişi için kayıtlı telefon numarası bulunamadı.', 'error');
            return;
        }

        if (!pendingSmsUrl || !pendingSmsId || smsSending) return;

        smsSending = true;
        smsSendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesaj', mesaj);
            formData.append('basvuru_ids[]', pendingSmsId);

            const { data } = await window.axios.post(pendingSmsUrl, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            closeModal(smsModal);
            showToast(data.message || 'SMS gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            smsSending = false;
            smsSendBtn.disabled = false;
        }
    });

    const epostaAliciEl = epostaModal?.querySelector('[data-basvuru-eposta-alici]');
    const epostaNoEmailEl = epostaModal?.querySelector('[data-basvuru-eposta-no-email]');
    const epostaKonuInput = epostaModal?.querySelector('[data-basvuru-eposta-konu]');
    const epostaMesajInput = epostaModal?.querySelector('[data-basvuru-eposta-mesaj]');
    const epostaCharCount = epostaModal?.querySelector('[data-basvuru-eposta-char-count]');
    const epostaSendBtn = epostaModal?.querySelector('[data-basvuru-eposta-send]');

    let pendingEpostaUrl = '';
    let pendingEpostaId = '';
    let pendingEpostaAd = '';
    let pendingEpostaEmail = '';
    let pendingEpostaEmailVar = true;
    let epostaSending = false;

    epostaMesajInput?.addEventListener('input', () => {
        if (epostaCharCount) epostaCharCount.textContent = String(epostaMesajInput.value.length);
    });

    epostaModal?.querySelectorAll('[data-basvuru-eposta-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const token = btn.getAttribute('data-basvuru-eposta-insert') || '';
            const target = btn.getAttribute('data-basvuru-eposta-insert-target') || 'mesaj';
            const input = target === 'konu' ? epostaKonuInput : epostaMesajInput;
            if (!token || !input) return;
            insertToken(input, token);
            if (target === 'mesaj' && epostaCharCount) {
                epostaCharCount.textContent = String(epostaMesajInput.value.length);
            }
        });
    });

    epostaModal?.querySelectorAll('[data-basvuru-eposta-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(epostaModal));
    });

    epostaModal?.querySelector('[data-basvuru-eposta-onizle]')?.addEventListener('click', () => {
        const konu = epostaKonuInput?.value?.trim() || '';
        const mesaj = epostaMesajInput?.value?.trim() || '';

        if (!konu) {
            showToast('Önizleme için önce konu yazın.', 'error');
            epostaKonuInput?.focus();
            return;
        }
        if (!mesaj) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            epostaMesajInput?.focus();
            return;
        }

        const onizlemeModal = document.getElementById('eposta-onizleme-modal');
        const aliciEl = onizlemeModal?.querySelector('[data-eposta-onizleme-alici]');
        const kimeEl = onizlemeModal?.querySelector('[data-eposta-onizleme-kime]');
        const konuEl = onizlemeModal?.querySelector('[data-eposta-onizleme-konu]');
        const mesajEl = onizlemeModal?.querySelector('[data-eposta-onizleme-mesaj]');
        if (!onizlemeModal || !konuEl || !mesajEl) return;

        if (aliciEl) {
            const not = pendingEpostaEmailVar ? '' : ' <span>(e-posta yok — gönderim engellenir)</span>';
            aliciEl.innerHTML = `Alıcı: <strong>${escapeHtml(pendingEpostaAd || 'İsimsiz')}</strong>${not}`;
        }
        if (kimeEl) {
            kimeEl.textContent = pendingEpostaEmail || 'E-posta yok';
        }
        konuEl.textContent = personalize(konu, pendingEpostaAd || 'Ad Soyad');
        mesajEl.textContent = personalize(mesaj, pendingEpostaAd || 'Ad Soyad');
        openModal(onizlemeModal);
    });

    const epostaOnizlemeModal = document.getElementById('eposta-onizleme-modal');
    if (epostaOnizlemeModal && epostaOnizlemeModal.dataset.bound !== '1') {
        epostaOnizlemeModal.dataset.bound = '1';
        epostaOnizlemeModal.querySelectorAll('[data-eposta-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(epostaOnizlemeModal));
        });
    }

    epostaSendBtn?.addEventListener('click', async () => {
        const konu = epostaKonuInput?.value?.trim() || '';
        const mesaj = epostaMesajInput?.value?.trim() || '';

        if (!konu) {
            showToast('E-posta konusu zorunludur.', 'error');
            epostaKonuInput?.focus();
            return;
        }
        if (!mesaj) {
            showToast('E-posta metni zorunludur.', 'error');
            epostaMesajInput?.focus();
            return;
        }
        if (!pendingEpostaEmailVar) {
            showToast('Bu kişi için kayıtlı e-posta adresi bulunamadı.', 'error');
            return;
        }

        if (!pendingEpostaUrl || !pendingEpostaId || epostaSending) return;

        epostaSending = true;
        epostaSendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('konu', konu);
            formData.append('mesaj', mesaj);
            formData.append('basvuru_ids[]', pendingEpostaId);

            const { data } = await window.axios.post(pendingEpostaUrl, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            closeModal(epostaModal);
            showToast(data.message || 'E-posta gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            epostaSending = false;
            epostaSendBtn.disabled = false;
        }
    });

    document.addEventListener('click', (event) => {
        const smsBtn = event.target.closest('[data-basvuru-sms-ac]');
        if (smsBtn) {
            event.preventDefault();
            pendingSmsUrl = smsBtn.dataset.sendUrl || '';
            pendingSmsId = smsBtn.dataset.basvuruId || '';
            pendingSmsAd = smsBtn.dataset.katilimci || '';
            pendingSmsTelefonVar = smsBtn.dataset.telefonVar === '1';

            if (smsAliciEl) {
                smsAliciEl.innerHTML = `<strong>${escapeHtml(pendingSmsAd || 'Bu kişi')}</strong> için SMS gönderilecek.`;
            }
            if (smsNoTelefonEl) smsNoTelefonEl.hidden = pendingSmsTelefonVar;
            if (smsMesajInput) smsMesajInput.value = '';
            if (smsCharCount) smsCharCount.textContent = '0';
            openModal(smsModal);
            return;
        }

        const epostaBtn = event.target.closest('[data-basvuru-eposta-ac]');
        if (epostaBtn) {
            event.preventDefault();
            pendingEpostaUrl = epostaBtn.dataset.sendUrl || '';
            pendingEpostaId = epostaBtn.dataset.basvuruId || '';
            pendingEpostaAd = epostaBtn.dataset.katilimci || '';
            pendingEpostaEmail = epostaBtn.dataset.email || '';
            pendingEpostaEmailVar = epostaBtn.dataset.emailVar === '1';

            if (epostaAliciEl) {
                epostaAliciEl.innerHTML = `<strong>${escapeHtml(pendingEpostaAd || 'Bu kişi')}</strong> için e-posta gönderilecek.`;
            }
            if (epostaNoEmailEl) epostaNoEmailEl.hidden = pendingEpostaEmailVar;
            if (epostaKonuInput) epostaKonuInput.value = '';
            if (epostaMesajInput) epostaMesajInput.value = '';
            if (epostaCharCount) epostaCharCount.textContent = '0';
            openModal(epostaModal);
        }
    });
}

function initMesajLogOnizleme() {
    const smsModal = document.getElementById('sms-onizleme-modal');
    const epostaModal = document.getElementById('eposta-onizleme-modal');
    if (!smsModal && !epostaModal) return;
    if (document.body.dataset.mesajLogOnizlemeBound === '1') return;
    document.body.dataset.mesajLogOnizlemeBound = '1';

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function parseAttrJson(raw, fallback = '') {
        if (raw == null || raw === '') return fallback;
        try {
            return JSON.parse(raw);
        } catch {
            return raw;
        }
    }

    function bindClose(modal, selector) {
        if (!modal || modal.dataset.logOnizlemeBound === '1') return;
        modal.dataset.logOnizlemeBound = '1';
        modal.querySelectorAll(selector).forEach((el) => {
            el.addEventListener('click', () => closeModal(modal));
        });
    }

    bindClose(smsModal, '[data-sms-onizleme-close]');
    bindClose(epostaModal, '[data-eposta-onizleme-close]');

    document.querySelectorAll('[data-mesaj-log-onizle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tip = btn.getAttribute('data-mesaj-log-onizle');
            const alici = btn.getAttribute('data-alici') || 'Alıcı';

            if (tip === 'sms' && smsModal) {
                const mesaj = parseAttrJson(btn.getAttribute('data-mesaj'), '');
                const telefon = btn.getAttribute('data-telefon') || '';
                const aliciEl = smsModal.querySelector('[data-sms-onizleme-alici]');
                const mesajEl = smsModal.querySelector('[data-sms-onizleme-mesaj]');
                if (aliciEl) {
                    aliciEl.innerHTML = telefon
                        ? `<strong>${escapeHtml(alici)}</strong> · ${escapeHtml(telefon)}`
                        : `<strong>${escapeHtml(alici)}</strong>`;
                }
                if (mesajEl) mesajEl.textContent = mesaj;
                openModal(smsModal);
                return;
            }

            if (tip === 'eposta' && epostaModal) {
                const konu = parseAttrJson(btn.getAttribute('data-konu'), '');
                const mesaj = parseAttrJson(btn.getAttribute('data-mesaj'), '');
                const email = btn.getAttribute('data-email') || '';
                const aliciEl = epostaModal.querySelector('[data-eposta-onizleme-alici]');
                const kimeEl = epostaModal.querySelector('[data-eposta-onizleme-kime]');
                const konuEl = epostaModal.querySelector('[data-eposta-onizleme-konu]');
                const mesajEl = epostaModal.querySelector('[data-eposta-onizleme-mesaj]');
                if (aliciEl) {
                    aliciEl.innerHTML = `<strong>${escapeHtml(alici)}</strong>`;
                }
                if (kimeEl) kimeEl.textContent = email || '—';
                if (konuEl) konuEl.textContent = konu;
                if (mesajEl) mesajEl.textContent = mesaj;
                openModal(epostaModal);
            }
        });
    });
}

export function initBasvuruActionModals(options = {}) {
    if (
        !document.getElementById('basvurular-table')
        && !document.getElementById('basvurular-results')
        && !document.querySelector('.basvuru-detail')
    ) {
        return;
    }

    if (document.body.dataset.basvuruActionModalsBound === '1') return;
    document.body.dataset.basvuruActionModalsBound = '1';

    const durumModal = document.getElementById('basvuru-durum-modal');
    const basariModal = document.getElementById('basvuru-basari-modal');
    const baslamaModal = document.getElementById('basvuru-baslama-modal');
    const iptalGerekceModal = document.getElementById('basvuru-iptal-gerekce-modal');
    const veliModal = document.getElementById('basvuru-veli-modal');
    if (!durumModal && !basariModal && !baslamaModal && !iptalGerekceModal && !veliModal) return;

    const durumText = durumModal?.querySelector('[data-basvuru-durum-text]');
    const durumSelect = durumModal?.querySelector('[data-basvuru-durum-select]');
    const durumGerekceWrap = durumModal?.querySelector('[data-basvuru-durum-gerekce-wrap]');
    const durumGerekce = durumModal?.querySelector('[data-basvuru-durum-gerekce]');
    const durumBaslamaWrap = durumModal?.querySelector('[data-basvuru-durum-baslama-wrap]');
    const durumBaslamaInput = durumModal?.querySelector('[data-basvuru-durum-baslama-input]');
    const durumKilitHint = durumModal?.querySelector('[data-basvuru-durum-kilit-hint]');
    const durumSmsWrap = durumModal?.querySelector('[data-basvuru-durum-sms-wrap]');
    const durumSms = durumModal?.querySelector('[data-basvuru-durum-sms]');
    const durumEpostaWrap = durumModal?.querySelector('[data-basvuru-durum-eposta-wrap]');
    const durumEposta = durumModal?.querySelector('[data-basvuru-durum-eposta]');
    const durumConfirm = durumModal?.querySelector('[data-basvuru-durum-confirm]');
    const basariText = basariModal?.querySelector('[data-basvuru-basari-text]');
    const basariHint = basariModal?.querySelector('[data-basvuru-basari-hint]');
    const basariSelect = basariModal?.querySelector('[data-basvuru-basari-select]');
    const basariConfirm = basariModal?.querySelector('[data-basvuru-basari-confirm]');
    const baslamaText = baslamaModal?.querySelector('[data-basvuru-baslama-text]');
    const baslamaInput = baslamaModal?.querySelector('[data-basvuru-baslama-input]');
    const baslamaConfirm = baslamaModal?.querySelector('[data-basvuru-baslama-confirm]');
    const iptalGerekceText = iptalGerekceModal?.querySelector('[data-basvuru-iptal-gerekce-text]');
    const iptalGerekceHint = iptalGerekceModal?.querySelector('[data-basvuru-iptal-gerekce-hint]');
    const iptalGerekceSelect = iptalGerekceModal?.querySelector('[data-basvuru-iptal-gerekce-select]');
    const iptalGerekceConfirm = iptalGerekceModal?.querySelector('[data-basvuru-iptal-gerekce-confirm]');
    const veliText = veliModal?.querySelector('[data-basvuru-veli-text]');
    const veliSelect = veliModal?.querySelector('[data-basvuru-veli-select]');
    const veliFields = veliModal?.querySelector('[data-basvuru-veli-fields]');
    const veliKucukHint = veliModal?.querySelector('[data-basvuru-veli-kucuk-hint]');
    const veliTc = veliModal?.querySelector('[data-basvuru-veli-tc]');
    const veliDogum = veliModal?.querySelector('[data-basvuru-veli-dogum]');
    const veliAd = veliModal?.querySelector('[data-basvuru-veli-ad]');
    const veliSoyad = veliModal?.querySelector('[data-basvuru-veli-soyad]');
    const veliTelefon = veliModal?.querySelector('[data-basvuru-veli-telefon]');
    const veliEmail = veliModal?.querySelector('[data-basvuru-veli-email]');
    const veliConfirm = veliModal?.querySelector('[data-basvuru-veli-confirm]');

    let pendingDurumUrl = '';
    let pendingBasariUrl = '';
    let pendingBaslamaUrl = '';
    let pendingIptalGerekceUrl = '';
    let pendingVeliUrl = '';
    let pendingBasariEditable = false;
    let pendingBelgeAllowed = false;
    let pendingMevcutDurumKod = '';
    let pendingBasariKod = '';
    let pendingKursBaslama = '';
    let pendingKursBitis = '';
    let pendingBaslamaTarihi = '';
    let pendingIptalEditable = false;
    let pendingKatilimciKucuk = false;
    let pendingHasExistingVeli = false;
    const olumluBasariKodlari = ['sertifika_hak_etti', 'katilim_belgesi_hak_etti'];

    function kesinKayitKilitliMi() {
        return pendingMevcutDurumKod === 'kesin_kayit'
            && olumluBasariKodlari.includes(pendingBasariKod);
    }

    function defaultBaslamaTarihi() {
        if (pendingMevcutDurumKod === 'kesin_kayit' && pendingBaslamaTarihi) {
            return pendingBaslamaTarihi;
        }

        return pendingKursBaslama || '';
    }

    function syncDurumFormVisibility() {
        const isIptal = durumSelect?.value === 'iptal';
        const isKesinKayit = durumSelect?.value === 'kesin_kayit';

        if (durumGerekceWrap) {
            durumGerekceWrap.hidden = !isIptal;
        }
        if (!isIptal && durumGerekce) {
            durumGerekce.value = '';
        }

        if (durumBaslamaWrap) {
            const wasHidden = durumBaslamaWrap.hidden;
            durumBaslamaWrap.hidden = !isKesinKayit;

            if (isKesinKayit && durumBaslamaInput) {
                durumBaslamaInput.min = pendingKursBaslama || '';
                durumBaslamaInput.max = pendingKursBitis || '';

                if (wasHidden || !durumBaslamaInput.value) {
                    durumBaslamaInput.value = defaultBaslamaTarihi();
                }
            }
        }

        syncDurumSmsOption(durumSelect?.value || '');
        syncDurumEpostaOption(durumSelect?.value || '');
    }

    function smsAyarForDurum(durumKod) {
        if (durumKod === 'kesin_kayit') {
            return durumModal?.dataset.smsOnayAyar || 'istege_bagli';
        }
        if (durumKod === 'iptal') {
            return durumModal?.dataset.smsIptalAyar || 'istege_bagli';
        }
        if (durumKod === 'yedek') {
            return durumModal?.dataset.smsYedekAyar || 'istege_bagli';
        }

        return null;
    }

    function epostaAyarForDurum(durumKod) {
        if (durumKod === 'kesin_kayit') {
            return durumModal?.dataset.epostaOnayAyar || 'istege_bagli';
        }
        if (durumKod === 'iptal') {
            return durumModal?.dataset.epostaIptalAyar || 'istege_bagli';
        }
        if (durumKod === 'yedek') {
            return durumModal?.dataset.epostaYedekAyar || 'istege_bagli';
        }

        return null;
    }

    function syncBildirimOption(ayar, wrap, checkbox) {
        const show = ayar !== null;

        if (wrap) {
            wrap.hidden = !show;
        }
        if (!checkbox) {
            return;
        }

        if (!show) {
            checkbox.checked = false;
            checkbox.disabled = false;
            return;
        }

        if (ayar === 'evet') {
            checkbox.checked = true;
            checkbox.disabled = true;
        } else if (ayar === 'hayir') {
            checkbox.checked = false;
            checkbox.disabled = true;
        } else {
            checkbox.disabled = false;
        }
    }

    function syncDurumSmsOption(durumKod = durumSelect?.value || '') {
        syncBildirimOption(smsAyarForDurum(durumKod), durumSmsWrap, durumSms);
    }

    function syncDurumEpostaOption(durumKod = durumSelect?.value || '') {
        syncBildirimOption(epostaAyarForDurum(durumKod), durumEpostaWrap, durumEposta);
    }

    function syncDurumKilitOptions() {
        const kilitli = kesinKayitKilitliMi();

        durumSelect?.querySelectorAll('option').forEach((option) => {
            const isKesinKayit = option.value === 'kesin_kayit';
            option.disabled = kilitli && !isKesinKayit;
        });

        if (durumSelect && kilitli) {
            durumSelect.value = 'kesin_kayit';
        }

        if (durumKilitHint) {
            durumKilitHint.hidden = !kilitli;
        }

        syncDurumFormVisibility();
    }

    function syncBasariBelgeOptions({ durumKod = '', basariId = '', kursTamamlanan = false } = {}) {
        const kesinKayit = durumKod === 'kesin_kayit';
        pendingBasariEditable = kesinKayit;
        pendingBelgeAllowed = kursTamamlanan && kesinKayit;

        basariSelect?.querySelectorAll('option').forEach((option) => {
            if (option.value === '') {
                option.disabled = !pendingBasariEditable;
                return;
            }

            const isCurrent = String(option.value) === String(basariId || '');
            const requiresBelge = option.dataset.requiresBelge === '1';
            option.disabled = !pendingBasariEditable || (requiresBelge && !pendingBelgeAllowed && !isCurrent);
        });

        if (basariHint) {
            basariHint.hidden = pendingBasariEditable && pendingBelgeAllowed;
        }

        if (basariConfirm) {
            basariConfirm.disabled = !pendingBasariEditable;
        }
    }

    async function postBasvuruUpdate(url, fields) {
        const formData = new FormData();
        Object.entries(fields).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                formData.append(key, value);
            }
        });
        formData.append('_method', 'PUT');

        return window.axios.post(url, formData, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });
    }

    async function reloadAfterAction() {
        if (options.onSuccess) {
            await options.onSuccess();
            return;
        }
        window.location.reload();
    }

    function selectValueForDurumKod(kod) {
        if (kod === 'kesin_kayit' || kod === 'iptal' || kod === 'yedek') {
            return kod;
        }
        return '';
    }

    document.addEventListener('click', (event) => {
        const durumBtn = event.target.closest('[data-basvuru-durum-ac]');
        if (durumBtn) {
            event.preventDefault();
            pendingDurumUrl = durumBtn.dataset.updateUrl || '';
            pendingMevcutDurumKod = durumBtn.dataset.durumKod || '';
            pendingBasariKod = durumBtn.dataset.basariKod || '';
            pendingKursBaslama = durumBtn.dataset.kursBaslama || '';
            pendingKursBitis = durumBtn.dataset.kursBitis || '';
            pendingBaslamaTarihi = durumBtn.dataset.baslamaTarihi || '';
            if (durumText) {
                durumText.textContent = `"${durumBtn.dataset.katilimci || 'Bu başvuru'}" için başvuru durumunu seçin.`;
            }
            if (durumSelect) {
                durumSelect.value = selectValueForDurumKod(durumBtn.dataset.durumKod || '');
            }
            if (durumGerekce) {
                durumGerekce.value = durumBtn.dataset.iptalGerekceId || '';
            }
            if (durumBaslamaInput) {
                durumBaslamaInput.value = '';
            }
            syncDurumKilitOptions();
            openModal(durumModal);
            return;
        }

        const basariBtn = event.target.closest('[data-basvuru-basari]');
        if (basariBtn) {
            event.preventDefault();
            pendingBasariUrl = basariBtn.dataset.updateUrl || '';
            if (basariText) {
                basariText.textContent = `"${basariBtn.dataset.katilimci || 'Bu başvuru'}" için başarı durumunu seçin.`;
            }
            if (basariSelect) {
                basariSelect.value = basariBtn.dataset.basariId || '';
            }
            syncBasariBelgeOptions({
                durumKod: basariBtn.dataset.durumKod || '',
                basariId: basariBtn.dataset.basariId || '',
                kursTamamlanan: basariBtn.dataset.kursDurum === 'tamamlanan',
            });
            openModal(basariModal);
            return;
        }

        const baslamaBtn = event.target.closest('[data-basvuru-baslama-ac]');
        if (baslamaBtn) {
            event.preventDefault();
            pendingBaslamaUrl = baslamaBtn.dataset.updateUrl || '';
            if (baslamaText) {
                baslamaText.textContent = `"${baslamaBtn.dataset.katilimci || 'Bu başvuru'}" için kursa başlama tarihini seçin.`;
            }
            if (baslamaInput) {
                baslamaInput.min = baslamaBtn.dataset.kursBaslama || '';
                baslamaInput.max = baslamaBtn.dataset.kursBitis || '';
                baslamaInput.value = baslamaBtn.dataset.baslamaTarihi || baslamaBtn.dataset.kursBaslama || '';
            }
            openModal(baslamaModal);
            return;
        }

        const iptalGerekceBtn = event.target.closest('[data-basvuru-iptal-gerekce-ac]');
        if (iptalGerekceBtn) {
            event.preventDefault();
            pendingIptalGerekceUrl = iptalGerekceBtn.dataset.updateUrl || '';
            pendingIptalEditable = iptalGerekceBtn.dataset.durumKod === 'iptal';
            if (iptalGerekceText) {
                iptalGerekceText.textContent = `"${iptalGerekceBtn.dataset.katilimci || 'Bu başvuru'}" için iptal gerekçesini seçin.`;
            }
            if (iptalGerekceSelect) {
                iptalGerekceSelect.value = iptalGerekceBtn.dataset.iptalGerekceId || '';
                iptalGerekceSelect.disabled = !pendingIptalEditable;
            }
            if (iptalGerekceHint) {
                iptalGerekceHint.hidden = pendingIptalEditable;
            }
            if (iptalGerekceConfirm) {
                iptalGerekceConfirm.disabled = !pendingIptalEditable;
            }
            openModal(iptalGerekceModal);
            return;
        }

        const veliBtn = event.target.closest('[data-basvuru-veli-ac]');
        if (veliBtn) {
            event.preventDefault();
            pendingVeliUrl = veliBtn.dataset.updateUrl || '';
            pendingKatilimciKucuk = veliBtn.dataset.katilimciKucuk === '1';
            pendingHasExistingVeli = veliBtn.dataset.veliBasvurusu === '1';
            if (veliText) {
                veliText.textContent = `"${veliBtn.dataset.katilimci || 'Bu başvuru'}" için veli başvurusunu güncelleyin.`;
            }
            if (veliSelect) {
                veliSelect.value = pendingHasExistingVeli ? '1' : '0';
                const hayirOption = veliSelect.querySelector('option[value="0"]');
                if (hayirOption) {
                    hayirOption.disabled = pendingKatilimciKucuk;
                }
            }
            if (veliTc) veliTc.value = veliBtn.dataset.veliTc || '';
            if (veliDogum) veliDogum.value = veliBtn.dataset.veliDogum || '';
            if (veliAd) veliAd.value = veliBtn.dataset.veliAd || '';
            if (veliSoyad) veliSoyad.value = veliBtn.dataset.veliSoyad || '';
            if (veliTelefon) veliTelefon.value = veliBtn.dataset.veliTelefon || '';
            if (veliEmail) veliEmail.value = veliBtn.dataset.veliEmail || '';
            if (veliKucukHint) {
                veliKucukHint.hidden = !pendingKatilimciKucuk;
            }
            syncVeliFieldsVisibility();
            openModal(veliModal);
        }
    });

    function syncVeliFieldsVisibility() {
        if (veliFields) {
            veliFields.hidden = veliSelect?.value !== '1';
        }
    }

    durumSelect?.addEventListener('change', syncDurumFormVisibility);
    veliSelect?.addEventListener('change', () => {
        if (veliSelect.value === '0' && pendingKatilimciKucuk) {
            veliSelect.value = '1';
            showToast('Katılımcı 18 yaşından küçük olduğu için veli başvurusu kaldırılamaz.', 'error');
            return;
        }
        syncVeliFieldsVisibility();
    });

    durumModal?.querySelectorAll('[data-basvuru-durum-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(durumModal));
    });
    basariModal?.querySelectorAll('[data-basvuru-basari-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(basariModal));
    });
    baslamaModal?.querySelectorAll('[data-basvuru-baslama-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(baslamaModal));
    });
    iptalGerekceModal?.querySelectorAll('[data-basvuru-iptal-gerekce-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(iptalGerekceModal));
    });
    veliModal?.querySelectorAll('[data-basvuru-veli-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(veliModal));
    });

    durumConfirm?.addEventListener('click', async () => {
        if (!pendingDurumUrl) return;

        const durumKod = durumSelect?.value || '';
        if (kesinKayitKilitliMi() && durumKod !== 'kesin_kayit') {
            showToast('Kesin kaydı iptal etmek için önce başarı durumunu güncelleyiniz.', 'error');
            return;
        }

        if (durumKod === 'iptal' && !durumGerekce?.value) {
            showToast('İptal gerekçesi seçilmelidir.', 'error');
            durumGerekce?.focus();
            return;
        }

        if (durumKod === 'kesin_kayit' && !durumBaslamaInput?.value) {
            showToast('Kursa başlama tarihi seçilmelidir.', 'error');
            durumBaslamaInput?.focus();
            return;
        }

        durumConfirm.disabled = true;
        try {
            const payload = { durum_kod: durumKod };
            if (durumKod === 'iptal') {
                payload.iptal_gerekce_id = durumGerekce.value;
            }
            if (durumKod === 'kesin_kayit') {
                payload.kursa_baslama_tarihi = durumBaslamaInput.value;
            }
            if (durumKod === 'kesin_kayit' || durumKod === 'iptal' || durumKod === 'yedek') {
                payload.sms_gonder = durumSms?.checked ? '1' : '0';
                payload.eposta_gonder = durumEposta?.checked ? '1' : '0';
            }
            const { data } = await postBasvuruUpdate(pendingDurumUrl, payload);
            closeModal(durumModal);
            showToast(data.message || 'Başvuru durumu güncellendi.', 'success');
            await reloadAfterAction();
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            durumConfirm.disabled = false;
        }
    });

    basariConfirm?.addEventListener('click', async () => {
        if (!pendingBasariUrl) return;

        if (!pendingBasariEditable) {
            showToast('Başarı durumu yalnızca başvuru durumu Kesin Kayıt olan kayıtlar için güncellenebilir.', 'error');
            return;
        }

        const selected = basariSelect?.selectedOptions[0];
        if (selected?.dataset.requiresBelge === '1' && !pendingBelgeAllowed) {
            showToast(
                'Sertifika veya katılım belgesi hakkı yalnızca kesin kayıtlı başvurular ve Tamamlanan kurslarda seçilebilir.',
                'error',
            );
            return;
        }

        basariConfirm.disabled = true;
        try {
            const { data } = await postBasvuruUpdate(pendingBasariUrl, {
                basari_durumu_id: basariSelect?.value || '',
            });
            closeModal(basariModal);
            showToast(data.message || 'Başarı durumu güncellendi.', 'success');
            await reloadAfterAction();
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            basariConfirm.disabled = false;
        }
    });

    baslamaConfirm?.addEventListener('click', async () => {
        if (!pendingBaslamaUrl) return;

        const value = baslamaInput?.value || '';
        if (!value) {
            showToast('Kursa başlama tarihi seçilmelidir.', 'error');
            baslamaInput?.focus();
            return;
        }

        baslamaConfirm.disabled = true;
        try {
            const { data } = await postBasvuruUpdate(pendingBaslamaUrl, {
                kursa_baslama_tarihi: value,
            });
            closeModal(baslamaModal);
            showToast(data.message || 'Kursa başlama tarihi güncellendi.', 'success');
            await reloadAfterAction();
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            baslamaConfirm.disabled = false;
        }
    });

    iptalGerekceConfirm?.addEventListener('click', async () => {
        if (!pendingIptalGerekceUrl) return;

        if (!pendingIptalEditable) {
            showToast('İptal gerekçesi yalnızca başvuru durumu İptal olan kayıtlarda güncellenebilir.', 'error');
            return;
        }

        const value = iptalGerekceSelect?.value || '';
        if (!value) {
            showToast('İptal gerekçesi seçilmelidir.', 'error');
            iptalGerekceSelect?.focus();
            return;
        }

        iptalGerekceConfirm.disabled = true;
        try {
            const { data } = await postBasvuruUpdate(pendingIptalGerekceUrl, {
                iptal_gerekce_id: value,
            });
            closeModal(iptalGerekceModal);
            showToast(data.message || 'İptal gerekçesi güncellendi.', 'success');
            await reloadAfterAction();
        } catch (error) {
            showToast(validationMessage(error), 'error');
            iptalGerekceConfirm.disabled = false;
        }
    });

    veliConfirm?.addEventListener('click', async () => {
        if (!pendingVeliUrl) return;

        const isEvet = veliSelect?.value === '1';
        if (!isEvet && pendingKatilimciKucuk) {
            showToast('Katılımcı 18 yaşından küçük olduğu için veli başvurusu kaldırılamaz.', 'error');
            return;
        }

        const payload = {
            veli_basvurusu: isEvet ? '1' : '0',
        };

        if (isEvet) {
            payload.veli_tc_kimlik_no = veliTc?.value || '';
            payload.veli_dogum_tarihi = veliDogum?.value || '';
            payload.veli_ad = veliAd?.value || '';
            payload.veli_soyad = veliSoyad?.value || '';
            payload.veli_telefon = veliTelefon?.value || '';
            payload.veli_email = veliEmail?.value || '';

            if (!pendingHasExistingVeli) {
                if (!payload.veli_tc_kimlik_no) {
                    showToast('Veli TC Kimlik No zorunludur.', 'error');
                    veliTc?.focus();
                    return;
                }
                if (!payload.veli_dogum_tarihi) {
                    showToast('Veli doğum tarihi zorunludur.', 'error');
                    veliDogum?.focus();
                    return;
                }
                if (!payload.veli_ad) {
                    showToast('Veli adı zorunludur.', 'error');
                    veliAd?.focus();
                    return;
                }
                if (!payload.veli_soyad) {
                    showToast('Veli soyadı zorunludur.', 'error');
                    veliSoyad?.focus();
                    return;
                }
            }
        }

        veliConfirm.disabled = true;
        try {
            const { data } = await postBasvuruUpdate(pendingVeliUrl, payload);
            closeModal(veliModal);
            showToast(data.message || 'Veli başvurusu güncellendi.', 'success');
            await reloadAfterAction();
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            veliConfirm.disabled = false;
        }
    });

    initEscapeClose();
}

function initKursYedekSiraModal() {
    const modal = document.getElementById('kurs-yedek-sira-modal');
    if (!modal) return;
    if (document.body.dataset.kursYedekSiraBound === '1') return;
    document.body.dataset.kursYedekSiraBound = '1';

    const listEl = modal.querySelector('[data-yedek-sira-list]');
    const descEl = modal.querySelector('[data-yedek-sira-desc]');
    const saveBtn = modal.querySelector('[data-yedek-sira-save]');
    let saveUrl = '';
    let dragId = null;
    let loading = false;
    let saving = false;

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function closeOpenActionMenus() {
        document.querySelectorAll('[data-row-actions].is-open').forEach((wrap) => {
            wrap.classList.remove('is-open');
            const toggle = wrap.querySelector('[data-action-toggle]');
            const dropdown = wrap.querySelector('[data-action-dropdown]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (dropdown) dropdown.hidden = true;
        });
    }

    function renumber() {
        listEl?.querySelectorAll('[data-yedek-sira-item]').forEach((item, index) => {
            const rank = item.querySelector('[data-yedek-sira-rank]');
            if (rank) rank.textContent = String(index + 1);
        });
    }

    function orderedIds() {
        return [...(listEl?.querySelectorAll('[data-yedek-sira-item]') || [])]
            .map((el) => Number(el.dataset.id))
            .filter((id) => Number.isFinite(id) && id > 0);
    }

    function bindItemDrag(item) {
        item.setAttribute('draggable', 'true');

        item.addEventListener('dragstart', (event) => {
            dragId = item.dataset.id;
            item.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragId || '');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('is-dragging');
            listEl?.querySelectorAll('.is-drag-over').forEach((el) => el.classList.remove('is-drag-over'));
            dragId = null;
            renumber();
        });

        item.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            const dragging = listEl?.querySelector(`[data-yedek-sira-item][data-id="${dragId}"]`);
            if (!dragging || dragging === item || !listEl) return;

            const rect = item.getBoundingClientRect();
            const before = event.clientY < rect.top + rect.height / 2;
            listEl.querySelectorAll('.is-drag-over').forEach((el) => el.classList.remove('is-drag-over'));
            item.classList.add('is-drag-over');
            if (before) {
                listEl.insertBefore(dragging, item);
            } else {
                listEl.insertBefore(dragging, item.nextSibling);
            }
            renumber();
        });

        item.addEventListener('dragleave', () => {
            item.classList.remove('is-drag-over');
        });

        item.addEventListener('drop', (event) => {
            event.preventDefault();
            item.classList.remove('is-drag-over');
            renumber();
        });
    }

    function renderItems(items) {
        if (!listEl) return;

        if (!items.length) {
            listEl.innerHTML = `
                <div class="empty-state" data-yedek-sira-empty style="padding:24px;">
                    <p class="empty-state-text">Yedek listesinde başvuru yok.</p>
                </div>
            `;
            if (saveBtn) saveBtn.disabled = true;
            return;
        }

        listEl.innerHTML = items.map((item, index) => {
            const subParts = [];
            if (item.kimlik) subParts.push(escapeHtml(item.kimlik));
            if (item.basvuru_tarihi) subParts.push(escapeHtml(item.basvuru_tarihi));
            const sub = subParts.length
                ? `<span class="yedek-sira-sub">${subParts.join(' · ')}</span>`
                : '';

            return `
                <div class="yedek-sira-item" data-yedek-sira-item data-id="${escapeHtml(item.id)}" role="listitem">
                    <span class="yedek-sira-handle" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                            <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                            <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                        </svg>
                    </span>
                    <span class="yedek-sira-rank" data-yedek-sira-rank>${index + 1}</span>
                    <span class="yedek-sira-meta">
                        <span class="yedek-sira-name">${escapeHtml(item.ad || 'İsimsiz')}</span>
                        ${sub}
                    </span>
                </div>
            `;
        }).join('');

        listEl.querySelectorAll('[data-yedek-sira-item]').forEach((el) => bindItemDrag(el));
        if (saveBtn) saveBtn.disabled = false;
    }

    async function openWithUrls(listUrl, nextSaveUrl) {
        if (!listUrl || !nextSaveUrl || loading) return;
        saveUrl = nextSaveUrl;
        closeOpenActionMenus();
        if (descEl) {
            descEl.textContent = 'Yedekteki başvuruları sürükleyerek sıralayın.';
        }
        if (listEl) {
            listEl.innerHTML = `
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Yükleniyor…</p>
                </div>
            `;
        }
        if (saveBtn) saveBtn.disabled = true;
        openModal(modal);
        loading = true;
        try {
            const { data } = await window.axios.get(listUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (descEl && data.kurs_ad) {
                descEl.textContent = `"${data.kurs_ad}" yedek listesini sürükleyerek sıralayın.`;
            }
            renderItems(Array.isArray(data.items) ? data.items : []);
        } catch (err) {
            closeModal(modal);
            showToast(validationMessage(err), 'error');
        } finally {
            loading = false;
        }
    }

    async function reloadAfterSave() {
        const panel = document.querySelector('[data-basvuru-panel]');
        if (panel?._loadBasvurular) {
            await panel._loadBasvurular({
                durum: panel.dataset.basvuruDurum || 'tumu',
                force: true,
            });
            return;
        }

        const filterForm = document.getElementById('basvurular-filter-form');
        if (filterForm) {
            filterForm.requestSubmit();
            return;
        }

        window.location.reload();
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-yedek-sira-open]');
        if (!btn || !document.getElementById('kurs-yedek-sira-modal')) return;
        // Kurs sayfalarında etkinlik butonları yok; URL kurs route'u olmalı.
        if (!String(btn.dataset.listUrl || '').includes('/kurslar/')) return;
        e.preventDefault();
        openWithUrls(btn.dataset.listUrl, btn.dataset.saveUrl);
    });

    modal.querySelectorAll('[data-yedek-sira-close]').forEach((el) => {
        el.addEventListener('click', () => {
            if (saving) return;
            closeModal(modal);
        });
    });

    saveBtn?.addEventListener('click', async () => {
        const ids = orderedIds();
        if (!saveUrl || saving) return;
        if (!ids.length) {
            showToast('Yedek listesinde başvuru yok.', 'error');
            return;
        }

        saving = true;
        saveBtn.disabled = true;
        try {
            const { data } = await window.axios.put(saveUrl, { basvuru_ids: ids }, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            showToast(data.message || 'Yedek sırası güncellendi.', 'success');
            closeModal(modal);
            await reloadAfterSave();
        } catch (err) {
            showToast(validationMessage(err), 'error');
            saveBtn.disabled = false;
        } finally {
            saving = false;
        }
    });
}

export { initKursYedekSiraModal };

export function initKursDetailActions() {
    if (document.querySelector('[data-etkinlik-detail]')) {
        return;
    }

    if (!document.querySelector('.lesson-detail')) {
        return;
    }

    const basvuruPanel = initBasvuruPanel();
    const yoklamaPanel = initYoklamaPanel();
    initLessonTabs(basvuruPanel, yoklamaPanel);
    initTakvimPanel();
    initTakvimIslemModallari();
    initMesajKanalTabs();
    initSmsModal();
    initSmsAlicilarDetayModal();
    initEpostaModal();
    initEpostaAlicilarDetayModal();
    initMesajLogOnizleme();
    initBasvuruMesajModallari();
    initKursYedekSiraModal();
    initOgretmenAssign();
    initYayinToggle();
    initEscapeClose();
}
