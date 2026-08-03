const SIDEBAR_THEMES = {
    default: {
        '--sidebar-bg': '#1e1e2d',
        '--sidebar-text-muted': '#9899ac',
        '--sidebar-border-color': '#2b2b40',
        '--sidebar-dropdown-content': 'rgba(0,0,0,0.2)',
        '--sidebar-menu-active': 'rgba(0,0,0,0.2)',
        '--sidebar-accent': '#3699ff',
        '--sidebar-text-light': '#ffffff',
        '--sidebar-hover-bg': '#2b2b40',
    },
    onyx: {
        '--sidebar-bg': '#000000',
        '--sidebar-text-muted': '#a0a0a0',
        '--sidebar-border-color': '#333333',
        '--sidebar-dropdown-content': 'rgba(0,0,0,0.2)',
        '--sidebar-menu-active': 'rgba(0,0,0,0.2)',
        '--sidebar-accent': '#ffffff',
        '--sidebar-text-light': '#ffffff',
        '--sidebar-hover-bg': '#1a1a1a',
    },
    light: {
        '--sidebar-bg': '#ffffff',
        '--sidebar-text-muted': '#5e6278',
        '--sidebar-border-color': '#eff2f5',
        '--sidebar-dropdown-content': '#f3f3f3',
        '--sidebar-menu-active': 'rgba(0,0,0,0.05)',
        '--sidebar-accent': '#3699ff',
        '--sidebar-text-light': '#3f4254',
        '--sidebar-hover-bg': '#f5f8fa',
    },
    bordo: {
        '--sidebar-bg': '#7d062b',
        '--sidebar-text-muted': '#d5d5d5',
        '--sidebar-border-color': '#630623',
        '--sidebar-dropdown-content': 'rgba(0,0,0,0.2)',
        '--sidebar-menu-active': 'rgba(0,0,0,0.2)',
        '--sidebar-accent': '#f9fdc7',
        '--sidebar-text-light': '#ffffff',
        '--sidebar-hover-bg': '#630623',
    },
    kucukcekmece: {
        '--sidebar-bg': '#23408f',
        '--sidebar-text-muted': '#b8c6e6',
        '--sidebar-border-color': '#1a3270',
        '--sidebar-dropdown-content': 'rgba(0,0,0,0.2)',
        '--sidebar-menu-active': 'rgba(0,0,0,0.25)',
        '--sidebar-accent': '#ffc72c',
        '--sidebar-text-light': '#ffffff',
        '--sidebar-hover-bg': '#1a3270',
    },
};

const STORAGE_KEYS = {
    theme: 'sidebar_theme',
    fontSize: 'sidebar_font_size',
    hidden: 'sidebar_hidden',
};

function applyTheme(themeName) {
    const theme = SIDEBAR_THEMES[themeName] || SIDEBAR_THEMES.default;
    const root = document.documentElement;

    Object.entries(theme).forEach(([key, value]) => {
        root.style.setProperty(key, value);
    });

    localStorage.setItem(STORAGE_KEYS.theme, themeName in SIDEBAR_THEMES ? themeName : 'default');

    document.querySelectorAll('[data-theme-btn]').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.themeBtn === themeName);
    });
}

function applyFontSize(size) {
    const value = Math.min(18, Math.max(12, Number(size) || 14));
    document.documentElement.style.setProperty('--menu-font-size', `${value}px`);
    localStorage.setItem(STORAGE_KEYS.fontSize, String(value));

    const display = document.getElementById('fontSizeDisplay');
    if (display) {
        display.textContent = `${value}px`;
    }

    const slider = document.getElementById('fontSlider');
    if (slider && slider.value !== String(value)) {
        slider.value = String(value);
    }
}

const MOBILE_QUERY = '(max-width: 1024px)';

function isMobileViewport() {
    return window.matchMedia(MOBILE_QUERY).matches;
}

function setSidebarHidden(hidden, { persist = true } = {}) {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.getElementById('adminMain');
    const openBtn = document.getElementById('sidebarOpenBtn');
    const backdrop = document.getElementById('sidebarBackdrop');
    const mobile = isMobileViewport();

    if (!sidebar || !main) {
        return;
    }

    if (persist && !mobile) {
        localStorage.setItem(STORAGE_KEYS.hidden, hidden ? '1' : '0');
    }

    document.documentElement.classList.toggle('sidebar-pref-hidden', !mobile && hidden);
    document.body.classList.toggle('sidebar-hidden', hidden);
    document.body.classList.toggle('sidebar-drawer-open', mobile && !hidden);
    sidebar.classList.toggle('is-hidden', hidden);
    main.classList.toggle('sidebar-is-hidden', hidden || mobile);
    openBtn?.classList.toggle('is-visible', hidden || mobile);

    if (backdrop) {
        backdrop.hidden = !(mobile && !hidden);
    }
}

function syncSidebarForViewport() {
    if (isMobileViewport()) {
        setSidebarHidden(true, { persist: false });
        return;
    }

    const savedHidden = localStorage.getItem(STORAGE_KEYS.hidden) === '1';
    setSidebarHidden(savedHidden, { persist: false });
}

function toggleSettingsPanel(forceClose = false) {
    const panel = document.getElementById('settingsPanel');
    if (!panel) {
        return;
    }

    const isOpen = panel.classList.contains('show');

    if (forceClose || isOpen) {
        panel.classList.remove('show');
        window.setTimeout(() => {
            if (!panel.classList.contains('show')) {
                panel.hidden = true;
            }
        }, 200);
        return;
    }

    panel.hidden = false;
    window.requestAnimationFrame(() => panel.classList.add('show'));
}

function resetAppearance() {
    applyTheme('default');
    applyFontSize(14);
}

function initMenuDropdowns() {
    document.querySelectorAll('[data-menu-dropdown]').forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-menu-dropdown-toggle]');
        const content = dropdown.querySelector('[data-menu-dropdown-content]');

        if (!toggle || !content) {
            return;
        }

        toggle.addEventListener('click', () => {
            const willOpen = !content.classList.contains('open');

            content.classList.toggle('open', willOpen);
            toggle.classList.toggle('dropdown-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });
}

export function initSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (!sidebar) {
        return;
    }

    const savedTheme = localStorage.getItem(STORAGE_KEYS.theme) || 'default';
    const savedFont = localStorage.getItem(STORAGE_KEYS.fontSize) || '14';

    applyTheme(savedTheme);
    applyFontSize(savedFont);
    syncSidebarForViewport();
    initMenuDropdowns();

    document.getElementById('sidebarHideBtn')?.addEventListener('click', () => {
        toggleSettingsPanel(true);
        setSidebarHidden(true);
    });

    document.getElementById('sidebarOpenBtn')?.addEventListener('click', () => {
        setSidebarHidden(false);
    });

    document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
        toggleSettingsPanel(true);
        setSidebarHidden(true);
    });

    window.matchMedia(MOBILE_QUERY).addEventListener('change', () => {
        syncSidebarForViewport();
    });

    document.getElementById('appearanceToggleBtn')?.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleSettingsPanel();
    });

    document.querySelectorAll('[data-theme-btn]').forEach((btn) => {
        btn.addEventListener('click', () => applyTheme(btn.dataset.themeBtn));
    });

    document.getElementById('fontSlider')?.addEventListener('input', (event) => {
        applyFontSize(event.target.value);
    });

    document.getElementById('appearanceResetBtn')?.addEventListener('click', resetAppearance);

    document.getElementById('settingsPanelCloseBtn')?.addEventListener('click', () => {
        toggleSettingsPanel(true);
    });

    document.addEventListener('click', (event) => {
        const panel = document.getElementById('settingsPanel');
        const toggle = document.getElementById('appearanceToggleBtn');

        if (!panel?.classList.contains('show')) {
            return;
        }

        if (panel.contains(event.target) || toggle?.contains(event.target)) {
            return;
        }

        toggleSettingsPanel(true);
    });
}
