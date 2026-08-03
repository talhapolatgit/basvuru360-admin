import { showToast } from './toast';

const COOKIE_KEY = 'basvurular_table_prefs';
const COOKIE_DAYS = 365;
const MORE_FILTERS_KEY = 'basvurular_more_filters';

let isLoading = false;

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

function clearCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax`;
}

function loadPrefs(defaults) {
    try {
        const raw = getCookie(COOKIE_KEY);
        if (!raw) {
            return structuredClone(defaults);
        }

        const parsed = JSON.parse(raw);
        const known = new Set(defaults.order);
        const order = Array.isArray(parsed.order)
            ? parsed.order.filter((key) => known.has(key))
            : [...defaults.order];

        defaults.order.forEach((key) => {
            if (!order.includes(key)) {
                order.push(key);
            }
        });

        if (order.includes('islemler')) {
            order.splice(order.indexOf('islemler'), 1);
        }
        order.push('islemler');

        const visible = Array.isArray(parsed.visible)
            ? parsed.visible.filter((key) => known.has(key) && key !== 'islemler')
            : [...defaults.visible];

        return { order, visible };
    } catch {
        return structuredClone(defaults);
    }
}

function savePrefs(prefs) {
    setCookie(COOKIE_KEY, JSON.stringify(prefs), COOKIE_DAYS);
}

function applyColumnOrder(table, order) {
    table.querySelectorAll('tr').forEach((row) => {
        const map = new Map();
        Array.from(row.children).forEach((cell) => {
            if (cell.dataset.column) {
                map.set(cell.dataset.column, cell);
            }
        });

        order.forEach((key) => {
            const cell = map.get(key);
            if (cell) {
                row.appendChild(cell);
            }
        });
    });
}

function applyVisibility(table, visible) {
    const allKeys = Array.from(table.querySelectorAll('thead th[data-column]')).map((th) => th.dataset.column);

    allKeys.forEach((key) => {
        const show = key === 'islemler' || visible.includes(key);
        table.querySelectorAll(`[data-column="${key}"]`).forEach((el) => {
            el.classList.toggle('col-hidden', !show);
        });

        const checkbox = document.querySelector(`.column-toggle[data-column="${key}"]`);
        if (checkbox) {
            checkbox.checked = show;
        }
    });
}

function syncColumnDropdownOrder(order) {
    const dropdown = document.getElementById('columnDropdown');
    if (!dropdown) {
        return;
    }

    const list = dropdown.querySelector('.column-dropdown-list') || dropdown;

    order.forEach((key) => {
        if (key === 'islemler') {
            return;
        }

        const option = dropdown.querySelector(`.column-option[data-column="${key}"]`);
        if (option) {
            list.appendChild(option);
        }
    });
}

function thIsHidden(table, key) {
    const th = table.querySelector(`thead th[data-column="${key}"]`);
    return !th || th.classList.contains('col-hidden');
}

function currentVisible(table) {
    return Array.from(table.querySelectorAll('thead th[data-column]'))
        .map((th) => th.dataset.column)
        .filter((key) => key !== 'islemler' && !thIsHidden(table, key));
}

function currentOrder(table) {
    return Array.from(table.querySelectorAll('thead th[data-column]')).map((th) => th.dataset.column);
}

function markDirty() {
    document.querySelectorAll('.save-prefs-btn').forEach((saveBtn) => {
        saveBtn.classList.add('is-dirty');
        saveBtn.classList.remove('is-saved');
        saveBtn.disabled = false;
        saveBtn.title = 'Kolon düzenlemelerini kaydet';
    });
}

function markClean() {
    document.querySelectorAll('.save-prefs-btn').forEach((saveBtn) => {
        saveBtn.classList.remove('is-dirty');
        saveBtn.classList.add('is-saved');
        saveBtn.disabled = false;
        saveBtn.title = 'Kolon düzenlemeleri kaydedildi';

        setTimeout(() => {
            saveBtn.classList.remove('is-saved');
        }, 1500);
    });
}

function persistColumnPreferences() {
    const table = document.getElementById('basvurular-table');
    if (!table) {
        return false;
    }

    savePrefs({
        order: currentOrder(table),
        visible: currentVisible(table),
    });
    markClean();
    showToast('Kolon düzenlemeleri kaydedildi');
    return true;
}

function initColumnDrag(table, saveBtn) {
    const headers = table.querySelectorAll('thead th[data-column]');
    let dragKey = null;
    let didDrag = false;

    headers.forEach((th) => {
        if (th.dataset.column === 'islemler') {
            return;
        }

        th.setAttribute('draggable', 'true');

        th.addEventListener('dragstart', (event) => {
            dragKey = th.dataset.column;
            didDrag = false;
            th.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragKey);
        });

        th.addEventListener('drag', () => {
            didDrag = true;
        });

        th.addEventListener('dragend', () => {
            th.classList.remove('dragging');
            table.querySelectorAll('thead th').forEach((item) => item.classList.remove('drag-over'));
            dragKey = null;
            setTimeout(() => {
                didDrag = false;
            }, 50);
        });

        th.addEventListener('dragover', (event) => {
            event.preventDefault();
            const targetKey = th.dataset.column;
            if (!dragKey || targetKey === 'islemler' || targetKey === dragKey) {
                return;
            }
            th.classList.add('drag-over');
            event.dataTransfer.dropEffect = 'move';
        });

        th.addEventListener('dragleave', () => {
            th.classList.remove('drag-over');
        });

        th.addEventListener('drop', (event) => {
            event.preventDefault();
            th.classList.remove('drag-over');

            const fromKey = event.dataTransfer.getData('text/plain') || dragKey;
            const toKey = th.dataset.column;

            if (!fromKey || !toKey || fromKey === toKey || toKey === 'islemler') {
                return;
            }

            const order = currentOrder(table);
            const fromIndex = order.indexOf(fromKey);
            const toIndex = order.indexOf(toKey);

            if (fromIndex < 0 || toIndex < 0) {
                return;
            }

            order.splice(fromIndex, 1);
            order.splice(toIndex, 0, fromKey);

            if (order.includes('islemler')) {
                order.splice(order.indexOf('islemler'), 1);
                order.push('islemler');
            }

            applyColumnOrder(table, order);
            syncColumnDropdownOrder(order);
            markDirty(saveBtn);
            didDrag = true;
        });
    });

    table._didDragRef = () => didDrag;
}

function initVisibilityToggles(table, saveBtn) {
    document.querySelectorAll('.column-toggle').forEach((checkbox) => {
        checkbox.onchange = () => {
            const key = checkbox.dataset.column;
            table.querySelectorAll(`[data-column="${key}"]`).forEach((el) => {
                el.classList.toggle('col-hidden', !checkbox.checked);
            });
            markDirty(saveBtn);
        };
    });
}

function initSorting(table) {
    const currentSort = table.dataset.sort || '';
    const currentDirection = table.dataset.direction || 'desc';

    table.querySelectorAll('thead th[data-sortable="1"]').forEach((th) => {
        const key = th.dataset.column;
        th.classList.add('sortable');
        th.classList.remove('sort-asc', 'sort-desc');

        if (currentSort === key) {
            th.classList.add(currentDirection === 'asc' ? 'sort-asc' : 'sort-desc');
        }

        th.onclick = () => {
            if (table._didDragRef && table._didDragRef()) {
                return;
            }

            const url = new URL(window.location.href);
            const nextDirection = currentSort === key && currentDirection === 'asc' ? 'desc' : 'asc';
            url.searchParams.set('sort', key);
            url.searchParams.set('direction', nextDirection);
            url.searchParams.delete('page');
            loadBasvurular(url.toString());
        };
    });
}

function updateExcelLink(url) {
    const excel = document.getElementById('basvurular-excel-link');
    if (!excel) {
        return;
    }

    const current = new URL(url, window.location.origin);
    const exportUrl = new URL(excel.href, window.location.origin);
    exportUrl.search = current.search;
    excel.href = exportUrl.pathname + exportUrl.search;
}

function setLoading(loading) {
    isLoading = loading;
    document.getElementById('basvurular-table-card')?.classList.toggle('is-loading', loading);
}

async function loadBasvurular(url, { push = true } = {}) {
    if (isLoading) {
        return;
    }

    const target = new URL(url, window.location.origin);
    target.searchParams.set('ajax', '1');

    setLoading(true);

    try {
        const response = await fetch(target.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
        });

        if (!response.ok) {
            throw new Error('İstek başarısız');
        }

        const html = await response.text();
        const results = document.getElementById('basvurular-results');
        if (results) {
            results.innerHTML = html;
        }

        const cleanUrl = new URL(target.toString());
        cleanUrl.searchParams.delete('ajax');

        if (push) {
            history.pushState({}, '', cleanUrl.pathname + cleanUrl.search);
        }

        updateExcelLink(cleanUrl.toString());
        bindResultsInteractions();
        applyTablePreferences();
    } catch (error) {
        console.error(error);
        showToast('Veriler yüklenirken bir hata oluştu', 'error');
    } finally {
        setLoading(false);
    }
}

function formToUrl(form) {
    const url = new URL(form.action, window.location.origin);
    const data = new FormData(form);

    // Clear previous params then append form fields
    url.search = '';
    for (const [key, value] of data.entries()) {
        if (value !== null && String(value).trim() !== '') {
            url.searchParams.append(key, value);
        }
    }

    // Keep current sort if form doesn't include it
    const current = new URL(window.location.href);
    if (!url.searchParams.has('sort') && current.searchParams.has('sort')) {
        url.searchParams.set('sort', current.searchParams.get('sort'));
        url.searchParams.set('direction', current.searchParams.get('direction') || 'desc');
    }

    url.searchParams.delete('page');

    return url.toString();
}

function resetFilterForm(form) {
    const modeValue = form.querySelector('[data-mode-value]')?.value || 'exact';

    // form.reset() HTML'deki başlangıç value'ya döner; AJAX sonrası eski filtre geri gelebilir.
    form.querySelectorAll('input').forEach((input) => {
        if (input.hasAttribute('data-mode-value') || input.hasAttribute('data-select-value')) {
            return;
        }

        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
            input.defaultChecked = false;
            return;
        }

        if (input.type === 'hidden') {
            return;
        }

        input.value = '';
        input.defaultValue = '';
    });

    form.querySelectorAll('select').forEach((select) => {
        const fallback = ['basvuru_durum', 'kurs_durum'].includes(select.id) ? 'tumu' : '';
        select.value = fallback;
        [...select.options].forEach((option) => {
            option.defaultSelected = option.value === fallback;
        });
    });

    form.querySelectorAll('[data-select-value]').forEach((input) => {
        input.value = '';
        const select = input.closest('[data-searchable-select]');
        const placeholder = select?.querySelector('.select-option[data-value=""]')?.dataset.label
            || select?.querySelector('[data-select-label]')?.textContent
            || '';
        const label = select?.querySelector('[data-select-label]');
        if (label && placeholder) {
            label.textContent = placeholder;
        }
        select?.querySelectorAll('.select-option').forEach((option) => {
            option.classList.toggle('selected', option.dataset.value === '');
        });
    });

    const wrapper = form.querySelector('[data-kurs-no-mode]');
    if (wrapper) {
        setKursNoMode(wrapper, modeValue);
    }
}

const KURS_NO_MODE_LABELS = {
    contains: 'İçinde',
    starts: 'Başında',
    ends: 'Sonunda',
    exact: 'Eşit',
};

const KURS_NO_MODE_KEY = 'basvurular_kurs_no_search_mode_v2';

function setKursNoMode(wrapper, mode) {
    const safeMode = KURS_NO_MODE_LABELS[mode] ? mode : 'exact';
    const valueInput = wrapper.querySelector('[data-mode-value]');
    const label = wrapper.querySelector('[data-mode-label]');
    const toggle = wrapper.querySelector('[data-mode-toggle]');
    const dropdown = wrapper.querySelector('[data-mode-dropdown]');

    if (valueInput) {
        valueInput.value = safeMode;
    }
    if (label) {
        label.textContent = KURS_NO_MODE_LABELS[safeMode];
    }

    wrapper.querySelectorAll('[data-mode]').forEach((option) => {
        option.classList.toggle('is-selected', option.dataset.mode === safeMode);
    });

    localStorage.setItem(KURS_NO_MODE_KEY, safeMode);
    dropdown.hidden = true;
    toggle?.classList.remove('is-open');
    toggle?.setAttribute('aria-expanded', 'false');
}

function initKursNoMode() {
    const wrapper = document.querySelector('[data-kurs-no-mode]');
    if (!wrapper) {
        return;
    }

    const toggle = wrapper.querySelector('[data-mode-toggle]');
    const dropdown = wrapper.querySelector('[data-mode-dropdown]');
    const valueInput = wrapper.querySelector('[data-mode-value]');
    const urlMode = new URLSearchParams(window.location.search).get('kurs_no_mode');
    const savedMode = urlMode || localStorage.getItem(KURS_NO_MODE_KEY) || valueInput?.value || 'exact';

    setKursNoMode(wrapper, savedMode);

    toggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = dropdown.hidden;
        document.querySelectorAll('[data-mode-dropdown]').forEach((el) => {
            el.hidden = true;
        });
        document.querySelectorAll('[data-mode-toggle]').forEach((el) => {
            el.classList.remove('is-open');
            el.setAttribute('aria-expanded', 'false');
        });

        if (willOpen) {
            dropdown.hidden = false;
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        }
    });

    wrapper.querySelectorAll('[data-mode]').forEach((option) => {
        option.addEventListener('click', (event) => {
            event.stopPropagation();
            setKursNoMode(wrapper, option.dataset.mode);
        });
    });

    document.addEventListener('click', () => {
        dropdown.hidden = true;
        toggle?.classList.remove('is-open');
        toggle?.setAttribute('aria-expanded', 'false');
    });
}

function bindResultsInteractions() {
    const results = document.getElementById('basvurular-results');
    if (!results) {
        return;
    }

    results.querySelectorAll('[data-pagination] a').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            loadBasvurular(link.href);
        });
    });

    const perPage = results.querySelector('[data-per-page-select]');
    perPage?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage.value);
        url.searchParams.delete('page');
        loadBasvurular(url.toString());
    });

    const saveBtn = document.getElementById('save-column-prefs');
    const table = document.getElementById('basvurular-table');
    if (table) {
        initColumnDrag(table, saveBtn);
        initSorting(table);
        initVisibilityToggles(table, saveBtn);
    }

    saveBtn?.addEventListener('click', () => {
        persistColumnPreferences();
    });
}

function applyTablePreferences() {
    const table = document.getElementById('basvurular-table');
    if (!table) {
        return;
    }

    const defaults = {
        order: JSON.parse(table.dataset.defaultOrder || '[]'),
        visible: JSON.parse(table.dataset.defaultVisible || '[]'),
    };

    const prefs = loadPrefs(defaults);
    applyColumnOrder(table, prefs.order);
    applyVisibility(table, prefs.visible);
    syncColumnDropdownOrder(prefs.order);
}

function resetColumnPreferences() {
    const table = document.getElementById('basvurular-table');
    if (!table) {
        return;
    }

    const defaults = {
        order: JSON.parse(table.dataset.defaultOrder || '[]'),
        visible: JSON.parse(table.dataset.defaultVisible || '[]').filter((key) => key !== 'islemler'),
    };

    if (defaults.order.includes('islemler')) {
        defaults.order = defaults.order.filter((key) => key !== 'islemler');
        defaults.order.push('islemler');
    }

    clearCookie(COOKIE_KEY);
    applyColumnOrder(table, defaults.order);
    applyVisibility(table, defaults.visible);
    syncColumnDropdownOrder(defaults.order);
    markClean();
    showToast('Sütunlar varsayılana sıfırlandı');
}

function initColumnPicker() {
    const resetBtn = document.getElementById('reset-column-prefs');
    const saveBtn = document.getElementById('dropdown-save-column-prefs');
    if (resetBtn && resetBtn.dataset.bound !== '1') {
        resetBtn.dataset.bound = '1';
        resetBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            resetColumnPreferences();
        });
    }

    if (saveBtn && saveBtn.dataset.bound !== '1') {
        saveBtn.dataset.bound = '1';
        saveBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            persistColumnPreferences();
        });
    }
}

function getFilterColumnCount(grid) {
    const styles = getComputedStyle(grid);
    const columns = styles.gridTemplateColumns.trim();

    if (!columns || columns === 'none') {
        return 1;
    }

    return columns.split(/\s+/).length;
}

function fieldHasValue(field) {
    if (field.type === 'checkbox' || field.type === 'radio') {
        return field.checked;
    }

    if (field.hasAttribute('data-mode-value')) {
        return false;
    }

    const value = String(field.value || '').trim();
    if (value === '') {
        return false;
    }

    // "Tümü" gibi varsayılan seçimler filtre uygulanmış sayılmaz
    const resetValue = field.dataset.resetValue;
    if (resetValue !== undefined && value === resetValue) {
        return false;
    }

    if (value === 'tumu') {
        return false;
    }

    return true;
}

function initMoreFilters() {
    const form = document.getElementById('basvurular-filter-form');
    const grid = form?.querySelector('[data-filter-grid]');
    const toggle = form?.querySelector('[data-more-filters]');
    const label = form?.querySelector('[data-more-filters-label]');

    if (!form || !grid || !toggle) {
        return;
    }

    let isOpen = false;

    const groups = () => [...grid.querySelectorAll(':scope > .form-group')];

    const applyVisibility = () => {
        const items = groups();

        // Önce hepsini göster ki kolon sayısı doğru ölçülsün
        items.forEach((item) => item.classList.remove('is-filter-collapsed'));

        if (isOpen) {
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            if (label) {
                label.textContent = 'Daha az filtre';
            }
            return;
        }

        const columns = Math.max(1, getFilterColumnCount(grid));
        // Kapalıyken iki satır görünsün
        const visibleCount = columns * 2;

        items.forEach((item, index) => {
            item.classList.toggle('is-filter-collapsed', index >= visibleCount);
        });

        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (label) {
            label.textContent = 'Daha fazla filtre';
        }
    };

    const hasCollapsedValues = () => {
        const columns = Math.max(1, getFilterColumnCount(grid));
        const visibleCount = columns * 2;

        return groups().slice(visibleCount).some((group) => (
            [...group.querySelectorAll('input, select, textarea')].some(fieldHasValue)
        ));
    };

    const setOpen = (open) => {
        isOpen = open;
        localStorage.setItem(MORE_FILTERS_KEY, open ? '1' : '0');
        applyVisibility();
    };

    // Varsayılan kapalı; yalnızca gizli satırlarda gerçek bir filtre değeri varsa aç
    isOpen = false;
    applyVisibility();
    if (hasCollapsedValues()) {
        setOpen(true);
    } else {
        localStorage.setItem(MORE_FILTERS_KEY, '0');
    }

    toggle.addEventListener('click', () => {
        setOpen(!isOpen);
    });

    window.addEventListener('resize', () => {
        applyVisibility();
    });
}

function initAjaxFilters() {
    const form = document.getElementById('basvurular-filter-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadBasvurular(formToUrl(form));
    });

    document.getElementById('basvurular-filter-clear')?.addEventListener('click', () => {
        resetFilterForm(form);
        const url = new URL(form.action, window.location.origin);
        url.searchParams.set('basvuru_durum', 'tumu');
        url.searchParams.set('kurs_durum', 'tumu');
        loadBasvurular(url.toString());
    });

    window.addEventListener('popstate', () => {
        loadBasvurular(window.location.href, { push: false });
    });
}

export function initBasvuruTable() {
    if (!document.getElementById('basvurular-table')) {
        return;
    }

    initKursNoMode();
    initMoreFilters();
    initAjaxFilters();
    initColumnPicker();
    bindResultsInteractions();
    applyTablePreferences();
}
