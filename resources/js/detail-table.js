/**
 * Detay sayfalarındaki statik tablolara (sekmeler) sütun gizleme/gösterme,
 * sürükleyerek sıralama, satır sıralaması (client-side) ve Excel (CSV) dışa
 * aktarma özellikleri ekler.
 *
 * Kullanım: <table data-detail-table="cookie-anahtari"
 *                  data-default-order='[...]' data-default-visible='[...]'>
 * Başlıklar: <th data-column="key" data-sortable="1">
 * Hücreler:  <td data-column="key" [data-sort-value] [data-export-value]>
 */
import {
    applyTablePreferences,
    saveColumnPrefs,
    clearColumnPrefs,
    applyColumnOrder,
    applyVisibility,
    syncColumnDropdownOrder,
    currentOrder,
    currentVisible,
    initColumnDrag,
    initVisibilityToggles,
    initSorting,
    markDirty,
    markClean,
} from './table-columns';
import { showToast } from './toast';

function cellValue(row, key) {
    const cell = row.querySelector(`[data-column="${key}"]`);
    if (!cell) return '';
    if (cell.dataset.sortValue !== undefined) return cell.dataset.sortValue;
    return cell.textContent.trim().replace(/\s+/g, ' ');
}

function isNumeric(value) {
    const str = String(value).trim();
    return str !== '' && /^-?[\d.,]+$/.test(str);
}

function compareCells(a, b) {
    if (isNumeric(a) && isNumeric(b)) {
        return parseFloat(String(a).replace(',', '.')) - parseFloat(String(b).replace(',', '.'));
    }
    return String(a).localeCompare(String(b), 'tr', { numeric: true, sensitivity: 'base' });
}

function sortRows(table, key, direction) {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.children).filter((tr) => tr.querySelector(`[data-column="${key}"]`));
    if (rows.length < 2) return;

    const factor = direction === 'asc' ? 1 : -1;
    rows.sort((a, b) => compareCells(cellValue(a, key), cellValue(b, key)) * factor);
    rows.forEach((row) => tbody.appendChild(row));
}

function bindSorting(table) {
    const refresh = () => {
        initSorting(table, {
            onSort: (key, direction) => {
                sortRows(table, key, direction);
                table.dataset.sort = key;
                table.dataset.direction = direction;
                refresh();
            },
        });
    };
    refresh();
}

function csvCell(value) {
    const str = String(value ?? '');
    if (/[";\n]/.test(str)) {
        return '"' + str.replace(/"/g, '""') + '"';
    }
    return str;
}

function exportCsv(table, name) {
    const headerCells = Array.from(table.querySelectorAll('thead tr:first-child th[data-column]'))
        .filter((th) => !th.classList.contains('col-hidden'));
    const keys = headerCells.map((th) => th.dataset.column);
    const headers = headerCells.map((th) => th.textContent.trim());

    const rows = Array.from(table.querySelectorAll('tbody tr'))
        .filter((tr) => tr.querySelector('[data-column]') && !tr.querySelector('.empty-state') && !tr.classList.contains('row-filtered'));

    const lines = [headers.map(csvCell).join(';')];
    rows.forEach((tr) => {
        const cols = keys.map((key) => {
            const cell = tr.querySelector(`[data-column="${key}"]`);
            if (!cell) return '';
            const raw = cell.dataset.exportValue ?? cell.textContent;
            return csvCell(String(raw).trim().replace(/\s+/g, ' '));
        });
        lines.push(cols.join(';'));
    });

    const csv = '\ufeff' + lines.join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${name}-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

function trLower(value) {
    return String(value ?? '').toLocaleLowerCase('tr-TR');
}

function filterValue(cell) {
    if (!cell) return '';
    if (cell.dataset.filterValue !== undefined) return cell.dataset.filterValue;
    if (cell.dataset.exportValue !== undefined) return cell.dataset.exportValue;
    return cell.textContent.trim().replace(/\s+/g, ' ');
}

function rowMatchesFilter(row, input) {
    const col = input.dataset.filterColumn;
    const type = input.dataset.filterType || 'text';
    const raw = (input.value || '').trim();
    if (raw === '') return true;

    const cell = row.querySelector(`[data-column="${col}"]`);

    if (type === 'date-from') {
        return (cell?.dataset.sortValue || '') !== '' && (cell?.dataset.sortValue || '') >= raw;
    }
    if (type === 'date-to') {
        return (cell?.dataset.sortValue || '') !== '' && (cell?.dataset.sortValue || '') <= raw;
    }
    if (type === 'select') {
        return trLower(filterValue(cell)) === trLower(raw);
    }
    return trLower(filterValue(cell)).includes(trLower(raw));
}

function initFilters(table, card) {
    const bar = card.querySelector('[data-detail-filters]');
    if (!bar) return;

    const inputs = Array.from(bar.querySelectorAll('[data-filter-column]'));
    if (!inputs.length) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const colCount = table.querySelectorAll('thead tr:first-child th').length || 1;
    let emptyRow = null;

    const ensureEmptyRow = () => {
        if (emptyRow) return emptyRow;
        emptyRow = document.createElement('tr');
        emptyRow.className = 'detail-filter-empty-row';
        emptyRow.hidden = true;
        const td = document.createElement('td');
        td.colSpan = colCount;
        td.innerHTML = '<div class="empty-state"><div class="empty-state-title">Sonuç bulunamadı</div><p class="empty-state-text">Filtreleri değiştirerek yeniden deneyin.</p></div>';
        emptyRow.appendChild(td);
        tbody.appendChild(emptyRow);
        return emptyRow;
    };

    const apply = () => {
        const rows = Array.from(tbody.children).filter(
            (tr) => tr.querySelector('[data-column]')
                && !tr.querySelector('.empty-state')
                && !tr.classList.contains('detail-filter-empty-row')
        );

        let visible = 0;
        rows.forEach((tr) => {
            const show = inputs.every((input) => rowMatchesFilter(tr, input));
            tr.classList.toggle('row-filtered', !show);
            if (show) visible += 1;
        });

        if (rows.length > 0 && visible === 0) {
            ensureEmptyRow().hidden = false;
        } else if (emptyRow) {
            emptyRow.hidden = true;
        }
    };

    inputs.forEach((input) => {
        input.addEventListener('input', apply);
        input.addEventListener('change', apply);
    });

    bar.querySelector('[data-filter-clear]')?.addEventListener('click', () => {
        inputs.forEach((input) => {
            input.value = '';
        });
        apply();
    });
}

function initTable(table) {
    const cookieKey = table.dataset.detailTable;
    if (!cookieKey || table.dataset.detailBound === '1') return;
    table.dataset.detailBound = '1';

    const card = table.closest('.card') || table.closest('[data-detail-table-scope]') || document;
    const picker = card.querySelector('[data-col-picker]');
    const dropdown = picker?.querySelector('[data-col-dropdown]');
    const toggle = picker?.querySelector('[data-col-toggle]');

    const dropdownRoot = picker || card;
    const saveBtn = picker?.querySelector('[data-col-save]') || null;

    applyTablePreferences(table, cookieKey, dropdownRoot);

    const flagDirty = () => markDirty(saveBtn, picker || document);

    initColumnDrag(table, { onChange: flagDirty });

    const toggles = dropdownRoot.querySelectorAll('.column-toggle');
    initVisibilityToggles(table, toggles, { onChange: flagDirty });

    saveBtn?.addEventListener('click', () => {
        saveColumnPrefs(cookieKey, {
            order: currentOrder(table),
            visible: currentVisible(table),
        });
        markClean(saveBtn, picker || document);
        showToast('Kolon düzenlemeleri kaydedildi');
    });

    picker?.querySelector('[data-col-reset]')?.addEventListener('click', () => {
        const defaults = {
            order: JSON.parse(table.dataset.defaultOrder || '[]'),
            visible: JSON.parse(table.dataset.defaultVisible || '[]'),
        };
        clearColumnPrefs(cookieKey);
        applyColumnOrder(table, defaults.order);
        applyVisibility(table, defaults.visible, { dropdownRoot });
        syncColumnDropdownOrder(dropdown, defaults.order);
        markClean(saveBtn, picker || document);
        showToast('Sütunlar varsayılana sıfırlandı');
    });

    bindSorting(table);
    initFilters(table, card);

    if (dropdown && toggle) {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = !dropdown.classList.contains('open');
            document.querySelectorAll('[data-col-dropdown].open').forEach((el) => {
                el.classList.remove('open');
                el.closest('[data-col-picker]')?.classList.remove('is-open');
            });
            dropdown.classList.toggle('open', willOpen);
            picker.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        dropdown.addEventListener('click', (event) => event.stopPropagation());
    }

    card.querySelector('[data-excel-btn]')?.addEventListener('click', (event) => {
        const btn = event.currentTarget;
        exportCsv(table, btn.dataset.excelName || 'liste');
    });
}

export function initDetailTable(table) {
    if (!table) return;
    initTable(table);
}

export function initDetailTables() {
    const tables = document.querySelectorAll('[data-detail-table]');
    if (!tables.length) return;

    tables.forEach(initTable);

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-col-dropdown].open').forEach((el) => {
            el.classList.remove('open');
            el.closest('[data-col-picker]')?.classList.remove('is-open');
            el.closest('[data-col-picker]')?.querySelector('[data-col-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    });
}
