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

    function openDurumModal(btn) {
        if (!durumModal) return;
        const form = durumModal.querySelector('[data-durum-form]');
        if (!form) return;
        form.action = btn.dataset.url;
        form.durum_id.value = btn.dataset.durumId || '';
        form.yedek_sira.value = btn.dataset.yedekSira || '';
        durumModal.querySelector('[data-durum-kisi]').textContent = btn.dataset.kisi || '';
        syncYedek(form);
        durumModal.hidden = false;
        document.body.classList.add('modal-open');
    }

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

        content.querySelectorAll('[data-kres-durum-open]').forEach((btn) => {
            btn.addEventListener('click', () => openDurumModal(btn));
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
    document.addEventListener('click', () => setColumnPickerOpen(false));

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
