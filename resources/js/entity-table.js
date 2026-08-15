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
} from './table-columns';

/**
 * Generic AJAX list-table controller (filter form + sortable/draggable columns +
 * column visibility preferences + pagination) used by the simple lookup pages
 * (Merkezler, Alanlar, Branşlar). Mirrors the pattern used by basvuru-table.js
 * but is parameterized so it can be reused across several pages.
 */
export function initEntityTable(options) {
    const {
        tableId,
        resultsId,
        cardId,
        filterFormId,
        clearBtnId,
        cookieKey,
        columnDropdownId = 'columnDropdown',
        saveColumnBtnId = 'dropdown-save-column-prefs',
        resetColumnBtnId = 'reset-column-prefs',
        pinnedKeys = ['islemler'],
        excelLinkId,
    } = options;

    if (!document.getElementById(tableId)) {
        return;
    }

    let isLoading = false;

    function getTable() {
        return document.getElementById(tableId);
    }

    function getDropdown() {
        return document.getElementById(columnDropdownId);
    }

    function setLoading(loading) {
        isLoading = loading;
        if (cardId) {
            document.getElementById(cardId)?.classList.toggle('is-loading', loading);
        }
    }

    function applyPrefs() {
        const table = getTable();
        if (!table) return null;
        return applyTablePreferences(table, cookieKey, getDropdown() || document, { pinnedKeys });
    }

    function persistPrefs() {
        const table = getTable();
        if (!table) return;
        saveColumnPrefs(cookieKey, {
            order: currentOrder(table),
            visible: currentVisible(table),
        });
        markClean();
        showToast('Kolon düzenlemeleri kaydedildi');
    }

    function resetPrefs() {
        const table = getTable();
        if (!table) return;
        const defaults = {
            order: JSON.parse(table.dataset.defaultOrder || '[]'),
            visible: JSON.parse(table.dataset.defaultVisible || '[]'),
        };
        clearColumnPrefs(cookieKey);
        applyColumnOrder(table, defaults.order);
        applyVisibility(table, defaults.visible, { dropdownRoot: getDropdown() || document, alwaysVisibleKeys: pinnedKeys });
        markClean();
        showToast('Sütunlar varsayılana sıfırlandı');
    }

    function updateExcelLink(url) {
        if (!excelLinkId) return;

        const excel = document.getElementById(excelLinkId);
        if (!excel) return;

        const current = new URL(url, window.location.origin);
        const exportUrl = new URL(excel.href, window.location.origin);
        exportUrl.search = current.search;
        excel.href = exportUrl.pathname + exportUrl.search;
    }

    function bindPagination() {
        const results = document.getElementById(resultsId);
        if (!results) return;

        results.querySelectorAll('[data-pagination] a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                load(link.href);
            });
        });

        const perPage = results.querySelector('[data-per-page-select]');
        perPage?.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage.value);
            url.searchParams.delete('page');
            load(url.toString());
        });
    }

    function bindResultsInteractions() {
        bindPagination();

        const table = getTable();
        if (!table) return;

        initColumnDrag(table, {
            pinnedKeys,
            onChange: () => markDirty(null),
        });

        initSorting(table, {
            onSort: (key, direction) => {
                const url = new URL(window.location.href);
                url.searchParams.set('sort', key);
                url.searchParams.set('direction', direction);
                url.searchParams.delete('page');
                load(url.toString());
            },
        });

        const dropdown = getDropdown();
        if (dropdown) {
            initVisibilityToggles(table, dropdown.querySelectorAll('.column-toggle'), {
                onChange: () => markDirty(null),
            });
        }
    }

    async function load(url, { push = true } = {}) {
        if (isLoading) return;

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
            const results = document.getElementById(resultsId);
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
            applyPrefs();
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

        url.search = '';
        for (const [key, value] of data.entries()) {
            if (value !== null && String(value).trim() !== '') {
                url.searchParams.append(key, value);
            }
        }

        const current = new URL(window.location.href);
        if (!url.searchParams.has('sort') && current.searchParams.has('sort')) {
            url.searchParams.set('sort', current.searchParams.get('sort'));
            url.searchParams.set('direction', current.searchParams.get('direction') || 'desc');
        }

        url.searchParams.delete('page');

        return url.toString();
    }

    function initFilters() {
        const form = document.getElementById(filterFormId);
        if (!form) return;

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            load(formToUrl(form));
        });

        document.getElementById(clearBtnId)?.addEventListener('click', () => {
            form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
                if (input.closest('[data-searchable-select]')) {
                    return;
                }
                input.value = '';
            });
            form.querySelectorAll('select').forEach((select) => {
                const fallback = select.dataset.resetValue ?? '';
                select.value = fallback;
            });
            form.querySelectorAll('[data-searchable-select]').forEach((select) => {
                const valueInput = select.querySelector('[data-select-value]');
                const label = select.querySelector('[data-select-label]');
                const resetValue = select.getAttribute('data-reset-value') ?? '';
                const resetOption = select.querySelector(
                    `[data-select-options] .select-option[data-value="${CSS.escape(resetValue)}"]`
                );
                const emptyOption = select.querySelector('[data-select-options] .select-option[data-value=""]');
                const option = resetOption || emptyOption;
                const value = option?.getAttribute('data-value') ?? '';
                const placeholder = option?.getAttribute('data-label') || option?.textContent?.trim() || 'Seçin';

                if (valueInput) {
                    valueInput.value = value;
                }
                if (label) {
                    label.textContent = placeholder;
                }
                select.querySelectorAll('.select-option').forEach((opt) => {
                    opt.classList.toggle('selected', opt.getAttribute('data-value') === value);
                });
            });
            load(form.getAttribute('action'));
        });

        window.addEventListener('popstate', () => {
            load(window.location.href, { push: false });
        });
    }

    function initColumnPicker() {
        const resetBtn = document.getElementById(resetColumnBtnId);
        if (resetBtn && resetBtn.dataset.bound !== '1') {
            resetBtn.dataset.bound = '1';
            resetBtn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                resetPrefs();
            });
        }

        const saveBtn = document.getElementById(saveColumnBtnId);
        if (saveBtn && saveBtn.dataset.bound !== '1') {
            saveBtn.dataset.bound = '1';
            saveBtn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                persistPrefs();
            });
        }
    }

    initFilters();
    initColumnPicker();
    bindResultsInteractions();
    applyPrefs();

    return { reload: () => load(window.location.href, { push: false }) };
}
