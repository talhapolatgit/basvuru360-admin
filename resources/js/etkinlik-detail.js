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
import {
    initEpostaAlicilarDetayModal,
    initEpostaModal,
    initSmsAlicilarDetayModal,
    initSmsModal,
} from './mesaj-gonder-modals';

const BASVURU_COOKIE_KEY = 'etkinlik_basvuru_table_prefs';

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

function errorMessage(error) {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    if (typeof data?.message === 'string' && data.message) return data.message;
    return 'İşlem sırasında bir hata oluştu.';
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
                    const raw = cell?.dataset.filterValue ?? cell?.textContent ?? '';
                    const text = raw.toLocaleLowerCase('tr-TR');
                    return text.includes(value);
                });
                row.style.display = match ? '' : 'none';
            });
        });
    });
}

function initTabs(root, basvuruPanel, yoklamaPanel) {
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
            showToast(errorMessage(error), 'error');
        } finally {
            if (thisRequest === mesajlarRequestId) {
                mesajlarLoading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const name = tab.getAttribute('data-lesson-tab');
            tabs.forEach((t) => {
                const active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach((p) => {
                p.classList.toggle('is-active', p.getAttribute('data-lesson-panel') === name);
            });
            const url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            if (name !== 'mesajlar') {
                url.searchParams.delete('kanal');
            }
            window.history.replaceState({}, '', url);
            if (name === 'basvurular') {
                basvuruPanel?.load?.({ force: true });
            }
            if (name === 'yoklama') {
                yoklamaPanel?.reload?.();
            }
            if (name === 'mesajlar') {
                loadMesajlar({ force: true });
            }
        });
    });
}

function initMesajKanalTabs(root) {
    const panel = root.querySelector('[data-lesson-panel="mesajlar"]');
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

function initBasvuruPanel(root) {
    const panel = root.querySelector('[data-basvuru-panel]');
    if (!panel) return null;

    const url = panel.dataset.basvuruUrl || root.dataset.basvurularUrl;
    const content = panel.querySelector('[data-basvuru-content]');
    const totalEl = panel.querySelector('[data-basvuru-total]');
    const filtersEl = panel.querySelector('[data-basvuru-filters]');
    const excelLink = panel.querySelector('[data-basvuru-excel]');
    const excelBaseHref = excelLink?.getAttribute('href') || '';
    const columnToggle = panel.querySelector('[data-basvuru-column-toggle]');
    const columnPicker = panel.querySelector('[data-basvuru-column-picker]');
    const columnDropdown = panel.querySelector('#basvuruColumnDropdown');
    const columnResetBtn = panel.querySelector('[data-basvuru-column-reset]');
    const columnSaveBtn = panel.querySelector('[data-basvuru-column-save]');
    if (!url || !content) return null;

    let currentDurum = panel.dataset.basvuruDurum || 'tumu';
    let currentSort = '';
    let currentDirection = 'desc';
    let currentPage = Number(new URL(window.location.href).searchParams.get('page') || '1') || 1;
    let loading = false;
    let requestId = 0;

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function setColumnPickerOpen(open) {
        columnDropdown?.classList.toggle('open', open);
        columnPicker?.classList.toggle('is-open', open);
        columnToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function setActiveFilter(durum) {
        currentDurum = durum;
        panel.dataset.basvuruDurum = durum;
        filtersEl?.querySelectorAll('[data-basvuru-filter]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.basvuruFilter === durum);
        });
    }

    function renderFilters(durumlar, activeDurum = currentDurum) {
        if (!filtersEl || !Array.isArray(durumlar)) return;

        const active = durumlar.some((d) => d.kod === activeDurum) ? activeDurum : 'tumu';
        filtersEl.innerHTML = [
            `<button type="button" class="basvuru-filter ${active === 'tumu' ? 'is-active' : ''}" data-basvuru-filter="tumu">Tümü</button>`,
            ...durumlar.map((d) => (
                `<button type="button" class="basvuru-filter ${active === d.kod ? 'is-active' : ''}" data-basvuru-filter="${escapeHtml(d.kod)}">${escapeHtml(d.ad)}</button>`
            )),
        ].join('');
        currentDurum = active;
        panel.dataset.basvuruDurum = active;
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
        const table = content.querySelector('#etkinlik-basvuru-table');
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

        const params = new URLSearchParams();
        params.set('basvuru_durum', durum || 'tumu');
        if (currentPage > 1) params.set('page', String(currentPage));
        if (currentSort) {
            params.set('sort', currentSort);
            params.set('direction', currentDirection);
        }

        try {
            const { data } = await window.axios.get(url + '?' + params.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (thisRequest !== requestId) return;

            if (Array.isArray(data.durumlar)) {
                renderFilters(data.durumlar, data.durum || durum);
            }

            content.innerHTML = data.html || '';
            if (totalEl) {
                totalEl.textContent = data.total != null
                    ? `Toplam Kayıt: ${new Intl.NumberFormat('tr-TR').format(data.total)}`
                    : 'Toplam Kayıt: —';
            }
            if (data.sort) {
                currentSort = data.sort;
                currentDirection = data.direction || 'desc';
            } else {
                currentSort = '';
                currentDirection = 'desc';
            }
            updateUrl(data.durum || currentDurum, currentPage);
            updateExcelLink();
            bindTableInteractions();
        } catch (e) {
            if (thisRequest !== requestId) return;
            content.innerHTML = '<div class="empty-state"><div class="empty-state-title">Başvurular yüklenemedi</div></div>';
            showToast(errorMessage(e), 'error');
        } finally {
            if (thisRequest === requestId) {
                loading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    filtersEl?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-basvuru-filter]');
        if (!btn || !filtersEl.contains(btn)) return;
        loadBasvurular({ durum: btn.dataset.basvuruFilter, page: 1, force: true });
    });

    columnToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        setColumnPickerOpen(!columnDropdown?.classList.contains('open'));
    });

    columnDropdown?.addEventListener('click', (event) => event.stopPropagation());

    document.addEventListener('click', () => setColumnPickerOpen(false));

    columnSaveBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const table = content.querySelector('#etkinlik-basvuru-table');
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
        const table = content.querySelector('#etkinlik-basvuru-table');
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

    return {
        load: loadBasvurular,
        reload: () => loadBasvurular({ durum: currentDurum, page: currentPage, force: true }),
        getPage: () => currentPage,
    };
}

function resetSorumluPicker(picker) {
    const valueInput = picker.querySelector('[data-select-value]');
    const label = picker.querySelector('[data-select-label]');
    const options = picker.querySelectorAll('.select-option');
    const emptyOption = picker.querySelector('.select-option[data-value=""]');
    const placeholder = emptyOption?.dataset.label || 'Kullanıcı seçin';

    if (valueInput) valueInput.value = '';
    if (label) label.textContent = placeholder;
    options.forEach((option) => {
        option.classList.toggle('selected', (option.dataset.value || '') === '');
    });
}

function syncSorumluPickerOptions(picker, list) {
    if (!picker || !list) return;
    const selected = new Set(
        [...list.querySelectorAll('[data-sorumlu-item]')].map((el) => String(el.dataset.id || '')),
    );
    picker.querySelectorAll('.select-option').forEach((option) => {
        const value = option.dataset.value || '';
        if (value === '') return;
        if (selected.has(value)) {
            option.classList.add('hidden');
            option.setAttribute('aria-disabled', 'true');
        } else {
            option.removeAttribute('aria-disabled');
            const searchInput = picker.querySelector('[data-select-search]');
            const query = (searchInput?.value || '').trim().toLocaleLowerCase('tr-TR');
            const text = (option.dataset.label || option.textContent || '').toLocaleLowerCase('tr-TR');
            option.classList.toggle('hidden', query !== '' && !text.includes(query));
        }
    });
}

function buildSorumluChip(id, label) {
    const item = document.createElement('div');
    item.className = 'evrak-item';
    item.setAttribute('data-sorumlu-item', '');
    item.setAttribute('data-id', String(id));
    item.innerHTML =
        '<input type="hidden" name="sorumlu_ids[]" value="' + String(id) + '">' +
        '<span class="evrak-item-label"></span>' +
        '<button type="button" class="evrak-item-remove" data-sorumlu-remove title="Kaldır" aria-label="Kaldır">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>' +
        '</button>';
    item.querySelector('.evrak-item-label').textContent = label || '';
    return item;
}

function updateSorumluUi(payload) {
    const openBtn = document.querySelector('[data-sorumlu-modal-open]');
    const nameEl = document.querySelector('[data-sorumlu-names]') || document.querySelector('.lesson-ogretmen-name');
    const modal = document.getElementById('sorumlu-modal');
    const titleEl = modal?.querySelector('#sorumlu-modal-title');
    const list = modal?.querySelector('[data-sorumlu-list]');
    const picker = modal?.querySelector('[data-searchable-select][data-name="sorumlu_picker"]');

    const people = Array.isArray(payload.sorumlular) ? payload.sorumlular : [];
    const hasPeople = people.length > 0;
    const names = hasPeople
        ? people.map((p) => p.tam_adi).filter(Boolean).join(', ')
        : '—';

    if (nameEl) nameEl.textContent = names;

    if (openBtn) {
        openBtn.textContent = hasPeople ? 'Düzenle' : 'Sorumlu Ata';
        openBtn.classList.toggle('is-change', hasPeople);
        openBtn.classList.toggle('is-publish', !hasPeople);
    }

    if (titleEl) {
        titleEl.textContent = hasPeople ? 'Sorumluları Düzenle' : 'Sorumlu Ata';
    }

    if (list) {
        list.innerHTML = '';
        if (!hasPeople) {
            const empty = document.createElement('p');
            empty.className = 'evrak-empty';
            empty.setAttribute('data-sorumlu-empty', '');
            empty.textContent = 'Henüz sorumlu atanmadı.';
            list.appendChild(empty);
        } else {
            people.forEach((person) => {
                list.appendChild(buildSorumluChip(person.id, person.tam_adi));
            });
        }
    }

    if (picker) {
        resetSorumluPicker(picker);
        syncSorumluPickerOptions(picker, list);
    }
}

function initSorumluModal(root) {
    const modal = document.getElementById('sorumlu-modal');
    const openBtn = document.querySelector('[data-sorumlu-modal-open]');
    const form = modal?.querySelector('[data-sorumlu-form]');
    if (!modal || !openBtn || !form) return;

    const list = form.querySelector('[data-sorumlu-list]');
    const picker = form.querySelector('[data-searchable-select][data-name="sorumlu_picker"]');
    const addBtn = form.querySelector('[data-sorumlu-add]');

    function ensureEmptyHint() {
        if (!list) return;
        const hasItems = list.querySelectorAll('[data-sorumlu-item]').length > 0;
        let empty = list.querySelector('[data-sorumlu-empty]');
        if (hasItems) {
            empty?.remove();
            return;
        }
        if (!empty) {
            empty = document.createElement('p');
            empty.className = 'evrak-empty';
            empty.setAttribute('data-sorumlu-empty', '');
            empty.textContent = 'Henüz sorumlu atanmadı.';
            list.appendChild(empty);
        }
    }

    openBtn.addEventListener('click', () => {
        syncSorumluPickerOptions(picker, list);
        openModal(modal);
    });

    modal.querySelectorAll('[data-sorumlu-modal-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    addBtn?.addEventListener('click', () => {
        const valueInput = picker?.querySelector('[data-select-value]');
        const id = valueInput?.value;
        if (!id || !list || !picker) return;
        if (list.querySelector(`[data-sorumlu-item][data-id="${id}"]`)) {
            resetSorumluPicker(picker);
            return;
        }
        const option = picker.querySelector(`.select-option[data-value="${CSS.escape(id)}"]`);
        const label = option?.dataset.label || option?.textContent || '';
        list.appendChild(buildSorumluChip(id, label.trim()));
        ensureEmptyHint();
        resetSorumluPicker(picker);
        syncSorumluPickerOptions(picker, list);
    });

    list?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-sorumlu-remove]');
        if (!btn) return;
        btn.closest('[data-sorumlu-item]')?.remove();
        ensureEmptyHint();
        syncSorumluPickerOptions(picker, list);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const assignUrl = root.dataset.sorumluUrl || form.action;
        const fd = new FormData(form);
        try {
            const { data } = await window.axios.post(assignUrl, fd, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-HTTP-Method-Override': 'PUT',
                },
            });
            showToast(data.message || 'Kaydedildi', 'success');
            updateSorumluUi(data);
            closeModal(modal);
        } catch (err) {
            showToast(errorMessage(err), 'error');
        }
    });
}

function updateYayinUi(payload) {
    const statusWrap = document.querySelector('[data-yayin-status]');
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
        openBtn.classList.toggle('is-unpublish', !!payload.onlinede_yayinlansin);
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
                    '<p>Bu etkinlik yayına alınacaktır fakat başvuru tarih aralığında online kanalda görünür hale gelecektir. Şu an başvuruya açmak için başvuru tarih aralığını güncellemeniz gerekmektedir.</p>' +
                    '<p style="margin-top:0.75rem;">Bu etkinliği yayına almak istediğinize emin misiniz?</p>';
            } else {
                bodyEl.textContent = 'Bu etkinliği yayına almak istediğinize emin misiniz? Online başvurular görünür hale gelecektir.';
            }
        } else {
            titleEl.textContent = 'Yayından Kaldır';
            bodyEl.textContent = 'Bu etkinliği yayından kaldırmak istediğinize emin misiniz? Online başvurular kapanacaktır.';
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
            showToast(errorMessage(error), 'error');
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = previousLabel || 'Onayla';
            }
        }
    });
}

function yoklamaCellValue(row, key) {
    const cell = row.querySelector(`[data-column="${key}"]`);
    if (!cell) return '';
    if (cell.dataset.sortValue !== undefined) return cell.dataset.sortValue;
    return (cell.textContent || '').trim().replace(/\s+/g, ' ');
}

function sortYoklamaRows(table, key, direction) {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const rows = [...tbody.querySelectorAll('tr[data-yoklama-row]')];
    if (rows.length < 2) return;

    const factor = direction === 'asc' ? 1 : -1;
    rows.sort((a, b) => {
        const av = yoklamaCellValue(a, key);
        const bv = yoklamaCellValue(b, key);
        return String(av).localeCompare(String(bv), 'tr', { numeric: true, sensitivity: 'base' }) * factor;
    });
    rows.forEach((row) => tbody.appendChild(row));
}

function initYoklama(root) {
    const panel = root.querySelector('[data-lesson-panel="yoklama"]');
    if (!panel) return null;

    const content = panel.querySelector('[data-yoklama-content]') || panel;
    const listUrl = root.dataset.yoklamaListUrl || '';
    const totalEl = panel.querySelector('[data-yoklama-total]');
    const excelLink = panel.querySelector('[data-yoklama-excel]');
    const excelBaseHref = excelLink?.getAttribute('href') || '';
    const filterBtns = panel.querySelectorAll('[data-yoklama-filter]');

    let loading = false;
    let savingId = null;
    let currentFiltre = panel.dataset.yoklamaFiltre || 'tumu';

    function setActiveFilter(filtre) {
        currentFiltre = filtre || 'tumu';
        panel.dataset.yoklamaFiltre = currentFiltre;
        filterBtns.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.yoklamaFilter === currentFiltre);
        });
    }

    function updateExcelLink() {
        if (!excelLink || !excelBaseHref) return;
        const exportUrl = new URL(excelBaseHref, window.location.origin);
        if (currentFiltre && currentFiltre !== 'tumu') {
            exportUrl.searchParams.set('filtre', currentFiltre);
        } else {
            exportUrl.searchParams.delete('filtre');
        }
        excelLink.href = exportUrl.pathname + exportUrl.search;
    }

    function updateTotal(visibleCount) {
        if (!totalEl) return;
        const n = visibleCount != null
            ? visibleCount
            : content.querySelectorAll('tr[data-yoklama-row]:not([style*="display: none"])').length;
        totalEl.textContent = `Toplam Kayıt: ${new Intl.NumberFormat('tr-TR').format(n)}`;
    }

    function applyRowVisibility(table) {
        if (!table) return;
        const inputs = table.querySelectorAll('.column-search[data-column-search]');
        const filters = [...inputs].map((el) => ({
            key: el.dataset.columnSearch,
            value: el.value.trim().toLocaleLowerCase('tr-TR'),
        }));

        let visible = 0;
        table.querySelectorAll('tbody tr[data-yoklama-row]').forEach((row) => {
            const durum = row.dataset.katilimDurumu || '';
            let statusMatch = true;
            if (currentFiltre === 'katildi') statusMatch = durum === 'katildi';
            else if (currentFiltre === 'katilmadi') statusMatch = durum === 'katilmadi';
            else if (currentFiltre === 'alinmayan') statusMatch = !durum;

            const searchMatch = filters.every(({ key, value }) => {
                if (!value) return true;
                const cell = row.querySelector(`[data-column="${key}"]`);
                const raw = cell?.dataset.filterValue ?? cell?.textContent ?? '';
                return raw.toLocaleLowerCase('tr-TR').includes(value);
            });

            const show = statusMatch && searchMatch;
            row.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });
        updateTotal(visible);
    }

    function bindYoklamaTable(table) {
        if (!table) return;
        table.dataset.yoklamaBound = '1';

        table.querySelectorAll('.column-search[data-column-search]').forEach((input) => {
            input.addEventListener('input', () => applyRowVisibility(table));
        });

        const refreshSort = () => {
            initSorting(table, {
                onSort: (key, direction) => {
                    sortYoklamaRows(table, key, direction);
                    table.dataset.sort = key;
                    table.dataset.direction = direction;
                    refreshSort();
                    applyRowVisibility(table);
                },
            });
        };
        refreshSort();
        applyRowVisibility(table);
    }

    filterBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            setActiveFilter(btn.dataset.yoklamaFilter);
            updateExcelLink();
            applyRowVisibility(content.querySelector('[data-etkinlik-yoklama-table]'));
        });
    });

    setActiveFilter(currentFiltre);
    updateExcelLink();
    bindYoklamaTable(content.querySelector('[data-etkinlik-yoklama-table]'));

    panel.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-yoklama-set]');
        if (!btn || !panel.contains(btn)) return;

        const row = btn.closest('[data-yoklama-row]');
        const url = root.dataset.yoklamaUrl;
        const durum = btn.dataset.yoklamaSet;
        if (!row || !url || !durum) return;

        const basvuruId = Number(row.dataset.basvuruId);
        if (!basvuruId || savingId === basvuruId) return;

        const group = row.querySelector('.yoklama-durum-group');
        const prevActive = group ? [...group.querySelectorAll('.yoklama-durum-option.is-active')] : [];
        const prevDurum = row.dataset.katilimDurumu || '';
        group?.querySelectorAll('.yoklama-durum-option').forEach((el) => {
            el.classList.toggle('is-active', el === btn);
            el.disabled = true;
        });

        row.dataset.katilimDurumu = durum;
        const katilimCell = row.querySelector('[data-column="katilim"]');
        if (katilimCell) {
            katilimCell.dataset.sortValue = durum;
            katilimCell.dataset.filterValue = durum === 'katildi' ? 'Katıldı' : 'Katılmadı';
        }

        savingId = basvuruId;
        try {
            const { data } = await window.axios.put(url, {
                yoklamalar: [{
                    basvuru_id: basvuruId,
                    katilim_durumu: durum,
                }],
            }, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            showToast(data.message || 'Yoklama güncellendi.', 'success');
            applyRowVisibility(content.querySelector('[data-etkinlik-yoklama-table]'));
        } catch (err) {
            row.dataset.katilimDurumu = prevDurum;
            group?.querySelectorAll('.yoklama-durum-option').forEach((el) => {
                el.classList.toggle('is-active', prevActive.includes(el));
            });
            if (katilimCell) {
                katilimCell.dataset.sortValue = prevDurum;
                katilimCell.dataset.filterValue = prevDurum === 'katildi' ? 'Katıldı' : (prevDurum === 'katilmadi' ? 'Katılmadı' : '');
            }
            showToast(errorMessage(err), 'error');
        } finally {
            group?.querySelectorAll('.yoklama-durum-option').forEach((el) => {
                el.disabled = false;
            });
            savingId = null;
        }
    });

    async function reload() {
        if (!listUrl || loading) return;
        loading = true;
        content.classList.add('is-loading');
        try {
            const { data } = await window.axios.get(listUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (typeof data.html === 'string') {
                content.innerHTML = data.html;
                bindYoklamaTable(content.querySelector('[data-etkinlik-yoklama-table]'));
            }
        } catch (err) {
            showToast(errorMessage(err), 'error');
        } finally {
            loading = false;
            content.classList.remove('is-loading');
        }
    }

    return { reload };
}

function initDurumModal(options = {}) {
    const modal = document.getElementById('etkinlik-basvuru-durum-modal');
    if (!modal) return;
    if (document.body.dataset.etkinlikBasvuruDurumBound === '1') return;
    document.body.dataset.etkinlikBasvuruDurumBound = '1';

    const basvuruPanel = options.basvuruPanel || null;
    const yoklamaPanel = options.yoklamaPanel || null;
    const form = modal.querySelector('[data-etkinlik-durum-form]');
    const select = modal.querySelector('[data-etkinlik-durum-select]');
    const gerekceWrap = modal.querySelector('[data-etkinlik-durum-gerekce-wrap]');
    const smsWrap = modal.querySelector('[data-etkinlik-durum-sms-wrap]');
    const smsCheckbox = modal.querySelector('[data-etkinlik-durum-sms]');
    const epostaWrap = modal.querySelector('[data-etkinlik-durum-eposta-wrap]');
    const epostaCheckbox = modal.querySelector('[data-etkinlik-durum-eposta]');
    let currentUrl = null;

    function selectValueForDurumKod(kod) {
        if (!select || !kod) return select?.options?.[0]?.value || '';
        const exists = [...select.options].some((opt) => opt.value === kod);
        return exists ? kod : (select.options?.[0]?.value || '');
    }

    function smsAyarForDurum(durumKod) {
        if (durumKod === 'kesin_kayit') {
            return modal.dataset.smsOnayAyar || 'istege_bagli';
        }
        if (durumKod === 'iptal') {
            return modal.dataset.smsIptalAyar || 'istege_bagli';
        }
        if (durumKod === 'yedek') {
            return modal.dataset.smsYedekAyar || 'istege_bagli';
        }

        return null;
    }

    function epostaAyarForDurum(durumKod) {
        if (durumKod === 'kesin_kayit') {
            return modal.dataset.epostaOnayAyar || 'istege_bagli';
        }
        if (durumKod === 'iptal') {
            return modal.dataset.epostaIptalAyar || 'istege_bagli';
        }
        if (durumKod === 'yedek') {
            return modal.dataset.epostaYedekAyar || 'istege_bagli';
        }

        return null;
    }

    function syncBildirimOption(ayar, wrap, checkbox) {
        const show = ayar !== null;
        wrap?.classList.toggle('is-hidden', !show);
        if (!checkbox) return;

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

    function syncSmsOption() {
        syncBildirimOption(smsAyarForDurum(select?.value || ''), smsWrap, smsCheckbox);
    }

    function syncEpostaOption() {
        syncBildirimOption(epostaAyarForDurum(select?.value || ''), epostaWrap, epostaCheckbox);
    }

    function syncGerekce() {
        const show = select?.value === 'iptal';
        gerekceWrap?.classList.toggle('is-hidden', !show);
        if (!show) {
            const gerekce = modal.querySelector('[data-etkinlik-durum-gerekce]');
            if (gerekce) gerekce.value = '';
        }
        syncSmsOption();
        syncEpostaOption();
    }
    select?.addEventListener('change', syncGerekce);

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-etkinlik-durum-open]');
        if (!btn) return;
        e.preventDefault();
        currentUrl = btn.dataset.url;
        const text = modal.querySelector('[data-etkinlik-durum-text]');
        if (text) {
            text.textContent = `"${btn.dataset.ad || 'Bu başvuru'}" için başvuru durumunu seçin.`;
        }
        if (select) {
            select.value = selectValueForDurumKod(btn.dataset.durum || '');
        }
        syncGerekce();
        const gerekce = modal.querySelector('[data-etkinlik-durum-gerekce]');
        if (gerekce && select?.value === 'iptal') {
            gerekce.value = btn.dataset.iptalGerekceId || '';
        }
        openModal(modal);
    });

    modal.querySelectorAll('[data-etkinlik-durum-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentUrl) return;
        const fd = new FormData(form);
        const durumKod = String(fd.get('durum_kod') || '').trim();
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!durumKod) {
            showToast('Başvuru durumu seçilmelidir.', 'error');
            return;
        }
        if (durumKod === 'kesin_kayit' || durumKod === 'iptal' || durumKod === 'yedek') {
            fd.set('sms_gonder', smsCheckbox?.checked ? '1' : '0');
            fd.set('eposta_gonder', epostaCheckbox?.checked ? '1' : '0');
        } else {
            fd.delete('sms_gonder');
            fd.delete('eposta_gonder');
        }
        if (submitBtn) submitBtn.disabled = true;
        try {
            const { data } = await window.axios.post(currentUrl, fd, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-HTTP-Method-Override': 'PUT',
                },
            });
            showToast(data.message || 'Başvuru durumu güncellendi.', 'success');
            closeModal(modal);
            if (basvuruPanel?.reload || yoklamaPanel?.reload) {
                await basvuruPanel?.reload?.();
                await yoklamaPanel?.reload?.();
            } else {
                const filterForm = document.getElementById('etkinlik-basvurulari-filter-form');
                if (filterForm) {
                    filterForm.requestSubmit();
                } else {
                    window.location.reload();
                }
            }
        } catch (err) {
            showToast(errorMessage(err), 'error');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}

export function initEtkinlikBasvuruDurumModal(options = {}) {
    initDurumModal(options);
}

function initYedekSiraModal(options = {}) {
    const modal = document.getElementById('etkinlik-yedek-sira-modal');
    if (!modal) return;
    if (document.body.dataset.etkinlikYedekSiraBound === '1') return;
    document.body.dataset.etkinlikYedekSiraBound = '1';

    const basvuruPanel = options.basvuruPanel || null;
    const yoklamaPanel = options.yoklamaPanel || null;
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

    async function openWithUrls(listUrl, nextSaveUrl, etkinlikAd) {
        if (!listUrl || !nextSaveUrl || loading) return;
        saveUrl = nextSaveUrl;
        closeOpenActionMenus();
        if (descEl) {
            descEl.textContent = etkinlikAd
                ? `"${etkinlikAd}" yedek listesini sürükleyerek sıralayın.`
                : 'Yedekteki başvuruları sürükleyerek sıralayın.';
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
            if (descEl && data.etkinlik_ad) {
                descEl.textContent = `"${data.etkinlik_ad}" yedek listesini sürükleyerek sıralayın.`;
            }
            renderItems(Array.isArray(data.items) ? data.items : []);
        } catch (err) {
            closeModal(modal);
            showToast(errorMessage(err), 'error');
        } finally {
            loading = false;
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-yedek-sira-open]');
        if (!btn || !document.getElementById('etkinlik-yedek-sira-modal')) return;
        if (!String(btn.dataset.listUrl || '').includes('/etkinlikler/')) return;
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
            if (basvuruPanel?.reload || yoklamaPanel?.reload) {
                await basvuruPanel?.reload?.();
                await yoklamaPanel?.reload?.();
            } else {
                const filterForm = document.getElementById('etkinlik-basvurulari-filter-form');
                if (filterForm) {
                    filterForm.requestSubmit();
                } else {
                    window.location.reload();
                }
            }
        } catch (err) {
            showToast(errorMessage(err), 'error');
            saveBtn.disabled = false;
        } finally {
            saving = false;
        }
    });
}

export function initEtkinlikYedekSiraModal(options = {}) {
    initYedekSiraModal(options);
}

export function initBasvuruMesajModallari() {
    const smsModal = document.getElementById('etkinlik-basvuru-sms-modal');
    const epostaModal = document.getElementById('etkinlik-basvuru-eposta-modal');
    if (!smsModal && !epostaModal) return;
    if (document.body.dataset.etkinlikBasvuruMesajBound === '1') return;
    document.body.dataset.etkinlikBasvuruMesajBound = '1';

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
        const max = Number(input.maxLength);
        const next = `${before}${token}${after}`.slice(0, Number.isFinite(max) && max > 0 ? max : undefined);
        input.value = next;
        const cursor = Math.min(start + token.length, next.length);
        input.focus();
        input.setSelectionRange(cursor, cursor);
        input.dispatchEvent(new Event('input'));
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
    if (smsOnizlemeModal && smsOnizlemeModal.dataset.etkinlikBasvuruOnizlemeBound !== '1') {
        smsOnizlemeModal.dataset.etkinlikBasvuruOnizlemeBound = '1';
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
            showToast(errorMessage(error), 'error');
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

        const ad = pendingEpostaAd || 'Ad Soyad';
        if (aliciEl) {
            const not = pendingEpostaEmailVar ? '' : ' <span>(e-posta yok — gönderim engellenir)</span>';
            aliciEl.innerHTML = `Alıcı: <strong>${escapeHtml(ad)}</strong>${not}`;
        }
        if (kimeEl) {
            kimeEl.textContent = pendingEpostaEmailVar
                ? (pendingEpostaEmail || '—')
                : 'E-posta adresi yok';
        }
        konuEl.textContent = personalize(konu, ad);
        mesajEl.textContent = personalize(mesaj, ad);
        openModal(onizlemeModal);
    });

    const epostaOnizlemeModal = document.getElementById('eposta-onizleme-modal');
    if (epostaOnizlemeModal && epostaOnizlemeModal.dataset.etkinlikBasvuruOnizlemeBound !== '1') {
        epostaOnizlemeModal.dataset.etkinlikBasvuruOnizlemeBound = '1';
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
            showToast(errorMessage(error), 'error');
        } finally {
            epostaSending = false;
            epostaSendBtn.disabled = false;
        }
    });

    document.addEventListener('click', (event) => {
        const smsBtn = event.target.closest('[data-basvuru-sms-ac]');
        if (smsBtn) {
            event.preventDefault();
            closeOpenActionMenus();
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
            closeOpenActionMenus();
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

export function initEtkinlikDetailPage() {
    const root = document.querySelector('[data-etkinlik-detail]');
    if (!root) return;

    const basvuruPanel = initBasvuruPanel(root);
    const yoklamaPanel = initYoklama(root);
    initTabs(root, basvuruPanel, yoklamaPanel);
    initMesajKanalTabs(root);
    initSorumluModal(root);
    initYayinToggle();
    initSmsModal();
    initSmsAlicilarDetayModal();
    initEpostaModal();
    initEpostaAlicilarDetayModal();
    initBasvuruMesajModallari();
    initDurumModal({ basvuruPanel, yoklamaPanel });
    initYedekSiraModal({ basvuruPanel, yoklamaPanel });

    if (root.querySelector('[data-lesson-panel="basvurular"].is-active')) {
        basvuruPanel?.load?.({ force: true });
    }
    if (root.querySelector('[data-lesson-panel="yoklama"].is-active')) {
        yoklamaPanel?.reload?.();
    }
}
