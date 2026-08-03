const MODE_LABELS = {
    contains: 'İçinde',
    starts: 'Başında',
    ends: 'Sonunda',
    exact: 'Eşit',
};

function setSearchMode(wrapper, mode) {
    const safeMode = MODE_LABELS[mode] ? mode : 'exact';
    const valueInput = wrapper.querySelector('[data-mode-value]');
    const label = wrapper.querySelector('[data-mode-label]');
    const toggle = wrapper.querySelector('[data-mode-toggle]');
    const dropdown = wrapper.querySelector('[data-mode-dropdown]');
    const storageKey = wrapper.dataset.modeStorage;

    if (valueInput) {
        valueInput.value = safeMode;
    }
    if (label) {
        label.textContent = MODE_LABELS[safeMode];
    }

    wrapper.querySelectorAll('[data-mode]').forEach((option) => {
        option.classList.toggle('is-selected', option.dataset.mode === safeMode);
    });

    if (storageKey) {
        localStorage.setItem(storageKey, safeMode);
    }

    if (dropdown) {
        dropdown.hidden = true;
    }
    toggle?.classList.remove('is-open');
    toggle?.setAttribute('aria-expanded', 'false');
}

function closeAllModeDropdowns(except = null) {
    document.querySelectorAll('[data-search-mode]').forEach((wrapper) => {
        if (wrapper === except) {
            return;
        }
        const dropdown = wrapper.querySelector('[data-mode-dropdown]');
        const toggle = wrapper.querySelector('[data-mode-toggle]');
        if (dropdown) {
            dropdown.hidden = true;
        }
        toggle?.classList.remove('is-open');
        toggle?.setAttribute('aria-expanded', 'false');
    });
}

/**
 * Initialize İçinde / Başında / Sonunda / Eşit mode toggles.
 * Expects wrappers with [data-search-mode], optional data-mode-param and data-mode-storage.
 */
export function initSearchModes(root = document) {
    const wrappers = root.querySelectorAll('[data-search-mode]');
    if (!wrappers.length) {
        return;
    }

    wrappers.forEach((wrapper) => {
        if (wrapper.dataset.modeBound === '1') {
            return;
        }
        wrapper.dataset.modeBound = '1';

        const toggle = wrapper.querySelector('[data-mode-toggle]');
        const dropdown = wrapper.querySelector('[data-mode-dropdown]');
        const valueInput = wrapper.querySelector('[data-mode-value]');
        const param = wrapper.dataset.modeParam;
        const storageKey = wrapper.dataset.modeStorage;
        const urlMode = param
            ? new URLSearchParams(window.location.search).get(param)
            : null;
        const savedMode = urlMode
            || (storageKey ? localStorage.getItem(storageKey) : null)
            || valueInput?.value
            || 'exact';

        setSearchMode(wrapper, savedMode);

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = dropdown?.hidden;
            closeAllModeDropdowns();
            if (willOpen && dropdown) {
                dropdown.hidden = false;
                toggle.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });

        wrapper.querySelectorAll('[data-mode]').forEach((option) => {
            option.addEventListener('click', (event) => {
                event.stopPropagation();
                setSearchMode(wrapper, option.dataset.mode);
            });
        });
    });

    if (document.documentElement.dataset.searchModeDocBound !== '1') {
        document.documentElement.dataset.searchModeDocBound = '1';
        document.addEventListener('click', () => closeAllModeDropdowns());
    }
}
