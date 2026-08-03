/**
 * Reusable column order / visibility / drag helpers for data tables.
 */
function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

export function clearColumnPrefs(cookieKey) {
    document.cookie = `${cookieKey}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax`;
}

function headerCells(table) {
    return Array.from(table.querySelectorAll('thead tr:first-child th[data-column]'));
}

export function loadColumnPrefs(cookieKey, defaults) {
    try {
        const raw = getCookie(cookieKey);
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

        const visible = Array.isArray(parsed.visible)
            ? parsed.visible.filter((key) => known.has(key))
            : [...defaults.visible];

        return { order, visible };
    } catch {
        return structuredClone(defaults);
    }
}

export function saveColumnPrefs(cookieKey, prefs, days = 365) {
    setCookie(cookieKey, JSON.stringify(prefs), days);
}

export function applyColumnOrder(table, order) {
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

export function applyVisibility(table, visible, { dropdownRoot = document, alwaysVisibleKeys = [] } = {}) {
    const keys = headerCells(table).map((th) => th.dataset.column);
    const forced = new Set(alwaysVisibleKeys);

    keys.forEach((key) => {
        const show = forced.has(key) || visible.includes(key);
        table.querySelectorAll(`[data-column="${key}"]`).forEach((el) => {
            el.classList.toggle('col-hidden', !show);
        });

        const checkbox = dropdownRoot.querySelector(`.column-toggle[data-column="${key}"]`);
        if (checkbox) {
            checkbox.checked = show;
        }
    });
}

export function syncColumnDropdownOrder(dropdown, order) {
    if (!dropdown) return;

    const list = dropdown.querySelector('.column-dropdown-list') || dropdown;

    order.forEach((key) => {
        const option = dropdown.querySelector(`.column-option[data-column="${key}"]`);
        if (option) {
            list.appendChild(option);
        }
    });
}

export function currentOrder(table) {
    return headerCells(table).map((th) => th.dataset.column);
}

export function currentVisible(table) {
    return headerCells(table)
        .filter((th) => !th.classList.contains('col-hidden'))
        .map((th) => th.dataset.column);
}

export function markDirty(saveBtn, root = document) {
    const buttons = root.querySelectorAll('.save-prefs-btn');
    if (!buttons.length && saveBtn) {
        saveBtn.classList.add('is-dirty');
        saveBtn.classList.remove('is-saved');
        saveBtn.disabled = false;
        saveBtn.title = 'Kolon düzenlemelerini kaydet';
        return;
    }

    buttons.forEach((btn) => {
        btn.classList.add('is-dirty');
        btn.classList.remove('is-saved');
        btn.disabled = false;
        btn.title = 'Kolon düzenlemelerini kaydet';
    });
}

export function markClean(saveBtn, root = document) {
    const buttons = root.querySelectorAll('.save-prefs-btn');
    const targets = buttons.length ? buttons : (saveBtn ? [saveBtn] : []);

    targets.forEach((btn) => {
        btn.classList.remove('is-dirty');
        btn.classList.add('is-saved');
        btn.disabled = false;
        btn.title = 'Kolon düzenlemeleri kaydedildi';
        setTimeout(() => btn.classList.remove('is-saved'), 1500);
    });
}

export function initColumnDrag(table, { onChange, pinnedKeys = [] } = {}) {
    let dragKey = null;
    let didDrag = false;
    const pinned = new Set(pinnedKeys);

    headerCells(table).forEach((th) => {
        const key = th.dataset.column;
        if (pinned.has(key)) {
            th.removeAttribute('draggable');
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
            if (!dragKey || targetKey === dragKey || pinned.has(targetKey)) return;
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
            if (!fromKey || !toKey || fromKey === toKey || pinned.has(fromKey) || pinned.has(toKey)) return;

            const order = currentOrder(table);
            const fromIndex = order.indexOf(fromKey);
            const toIndex = order.indexOf(toKey);
            if (fromIndex < 0 || toIndex < 0) return;

            order.splice(fromIndex, 1);
            order.splice(toIndex, 0, fromKey);

            pinned.forEach((pinKey) => {
                if (order.includes(pinKey)) {
                    order.splice(order.indexOf(pinKey), 1);
                    order.push(pinKey);
                }
            });

            applyColumnOrder(table, order);
            didDrag = true;
            onChange?.(order);
        });
    });

    table._didDragRef = () => didDrag;
}

export function initVisibilityToggles(table, toggles, { onChange } = {}) {
    toggles.forEach((checkbox) => {
        checkbox.onchange = () => {
            const key = checkbox.dataset.column;
            table.querySelectorAll(`[data-column="${key}"]`).forEach((el) => {
                el.classList.toggle('col-hidden', !checkbox.checked);
            });
            onChange?.(currentVisible(table));
        };
    });
}

export function initSorting(table, { onSort } = {}) {
    const currentSort = table.dataset.sort || '';
    const currentDirection = table.dataset.direction || 'desc';

    table.querySelectorAll('thead tr:first-child th[data-sortable="1"]').forEach((th) => {
        const key = th.dataset.column;
        th.classList.add('sortable');
        th.classList.remove('sort-asc', 'sort-desc');

        if (currentSort === key) {
            th.classList.add(currentDirection === 'asc' ? 'sort-asc' : 'sort-desc');
        }

        th.onclick = () => {
            if (table._didDragRef?.()) return;
            const nextDirection = currentSort === key && currentDirection === 'asc' ? 'desc' : 'asc';
            onSort?.(key, nextDirection);
        };
    });
}

export function applyTablePreferences(table, cookieKey, dropdownRoot = document, { pinnedKeys = [] } = {}) {
    if (!table) return null;

    const defaults = {
        order: JSON.parse(table.dataset.defaultOrder || '[]'),
        visible: JSON.parse(table.dataset.defaultVisible || '[]'),
    };

    const prefs = loadColumnPrefs(cookieKey, defaults);

    pinnedKeys.forEach((key) => {
        if (prefs.order.includes(key)) {
            prefs.order.splice(prefs.order.indexOf(key), 1);
        }
        prefs.order.push(key);
        if (!prefs.visible.includes(key)) {
            prefs.visible.push(key);
        }
    });

    applyColumnOrder(table, prefs.order);
    applyVisibility(table, prefs.visible, { dropdownRoot, alwaysVisibleKeys: pinnedKeys });
    syncColumnDropdownOrder(dropdownRoot.querySelector('.column-dropdown'), prefs.order.filter((key) => !pinnedKeys.includes(key)));

    return prefs;
}
