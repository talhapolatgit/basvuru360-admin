import { initEntityModal } from './entity-modals';
import { initSorting } from './table-columns';
import { initRichTextEditors } from './rich-text-editor';

const TAB_CONFIG = [
    {
        key: 'kurs-tipleri',
        modalId: 'kurs-tipleri-form-modal',
        formSelector: '.kurs-tipleri-form',
        createTitle: 'Yeni Kurs Tipi',
        editTitle: 'Kurs Tipini Düzenle',
        openSelector: '[data-kurs-tipleri-modal-open]',
        editSelector: '[data-kurs-tipleri-edit]',
    },
    {
        key: 'etkinlik-tipleri',
        modalId: 'etkinlik-tipleri-form-modal',
        formSelector: '.etkinlik-tipleri-form',
        createTitle: 'Yeni Etkinlik Tipi',
        editTitle: 'Etkinlik Tipini Düzenle',
        openSelector: '[data-etkinlik-tipleri-modal-open]',
        editSelector: '[data-etkinlik-tipleri-edit]',
    },
    {
        key: 'evrak-tipleri',
        modalId: 'evrak-tipleri-form-modal',
        formSelector: '.evrak-tipleri-form',
        createTitle: 'Yeni Evrak Tipi',
        editTitle: 'Evrak Tipini Düzenle',
        openSelector: '[data-evrak-tipleri-modal-open]',
        editSelector: '[data-evrak-tipleri-edit]',
    },
    {
        key: 'basvuru-durumlari',
        modalId: 'basvuru-durumlari-form-modal',
        formSelector: '.basvuru-durumlari-form',
        createTitle: 'Yeni Başvuru Durumu',
        editTitle: 'Başvuru Durumunu Düzenle',
        openSelector: '[data-basvuru-durumlari-modal-open]',
        editSelector: '[data-basvuru-durumlari-edit]',
    },
    {
        key: 'etkinlik-basvuru-durumlari',
        modalId: 'etkinlik-basvuru-durumlari-form-modal',
        formSelector: '.etkinlik-basvuru-durumlari-form',
        createTitle: 'Yeni Etkinlik Başvuru Durumu',
        editTitle: 'Etkinlik Başvuru Durumunu Düzenle',
        openSelector: '[data-etkinlik-basvuru-durumlari-modal-open]',
        editSelector: '[data-etkinlik-basvuru-durumlari-edit]',
    },
    {
        key: 'basari-durumlari',
        modalId: 'basari-durumlari-form-modal',
        formSelector: '.basari-durumlari-form',
        createTitle: 'Yeni Başarı Durumu',
        editTitle: 'Başarı Durumunu Düzenle',
        openSelector: '[data-basari-durumlari-modal-open]',
        editSelector: '[data-basari-durumlari-edit]',
    },
    {
        key: 'iptal-gerekceleri',
        modalId: 'iptal-gerekceleri-form-modal',
        formSelector: '.iptal-gerekceleri-form',
        createTitle: 'Yeni İptal Gerekçesi',
        editTitle: 'İptal Gerekçesini Düzenle',
        openSelector: '[data-iptal-gerekceleri-modal-open]',
        editSelector: '[data-iptal-gerekceleri-edit]',
    },
    {
        key: 'kurumlar',
        modalId: 'kurumlar-form-modal',
        formSelector: '.kurumlar-form',
        createTitle: 'Yeni Kurum',
        editTitle: 'Kurumu Düzenle',
        openSelector: '[data-kurumlar-modal-open]',
        editSelector: '[data-kurumlar-edit]',
    },
];

export function initSabitTanimlarPage() {
    const root = document.querySelector('[data-sabit-tanimlar]');
    if (!root) return;

    initTabs(root);
    initSertifikaAyarlari(root);
    initDigerAyarlar(root);

    const reloaders = {};

    TAB_CONFIG.forEach((config) => {
        // Gizli sekmelerin paneli DOM'da yoksa atla.
        if (!document.getElementById(`${config.key}-filter-form`) && !document.getElementById(config.modalId)) {
            return;
        }

        reloaders[config.key] = initTabTable(config.key);

        initEntityModal({
            modalId: config.modalId,
            formSelector: config.formSelector,
            createTitle: config.createTitle,
            editTitle: config.editTitle,
            openSelector: config.openSelector,
            editSelector: config.editSelector,
            onSuccess: () => {
                reloaders[config.key]?.();
                refreshTabCount(config.key);
            },
        });
    });
}

function initSertifikaAyarlari(root) {
    root.querySelectorAll('[data-sertifika-preview-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const block = input.closest('[data-sablon-kod]');
            const img = block?.querySelector('[data-sertifika-preview-img]');
            const empty = block?.querySelector('[data-sertifika-preview-empty]');
            const caption = block?.querySelector('.sertifika-preview-caption');
            const file = input.files?.[0];

            if (!img || !file) return;

            const url = URL.createObjectURL(file);
            img.src = url;
            img.hidden = false;
            if (empty) empty.hidden = true;
            if (caption) caption.textContent = file.name;
        });
    });

    root.querySelectorAll('[data-diger-imzaci-toggle]').forEach((checkbox) => {
        const block = checkbox.closest('[data-sablon-kod]');
        const fields = block?.querySelector('[data-diger-imzaci-fields]');
        if (!fields) return;

        const sync = () => {
            fields.hidden = !checkbox.checked;
        };
        checkbox.addEventListener('change', sync);
        sync();
    });

    const form = root.querySelector('[data-sertifika-ayarlar-form]');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.querySelector('.btn-cta-text')?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            const label = submitBtn.querySelector('.btn-cta-text');
            if (label) label.textContent = 'Kaydediliyor...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data?.errors
                    ? Object.values(data.errors).flat()[0]
                    : data?.message;
                throw new Error(firstError || 'Kayıt başarısız');
            }

            if (typeof window.showToast === 'function') {
                window.showToast(data.message || 'Sertifika ayarları kaydedildi.', 'success');
            }

            // Şablon önizlemelerini güncellemek için paneli yenile.
            const results = document.getElementById('sertifika-ayarlari-results');
            if (results) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'sertifika-ayarlari');
                url.searchParams.set('ajax', '1');
                const htmlResponse = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'text/html',
                    },
                });
                if (htmlResponse.ok) {
                    results.innerHTML = await htmlResponse.text();
                    initSertifikaAyarlari(root);
                }
            }
        } catch (error) {
            if (typeof window.showToast === 'function') {
                window.showToast(error.message || 'Kayıt başarısız', 'error');
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                const label = submitBtn.querySelector('.btn-cta-text');
                if (label) label.textContent = previousLabel || 'Ayarları Kaydet';
            }
        }
    });
}

function initDigerAyarlar(root) {
    const form = root.querySelector('[data-diger-ayarlar-form]');
    if (!form || form.dataset.initialized === '1') return;
    form.dataset.initialized = '1';

    // Editörleri bu sekme için yeniden bağla
    initRichTextEditors(form);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.querySelector('.btn-cta-text')?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            const label = submitBtn.querySelector('.btn-cta-text');
            if (label) label.textContent = 'Kaydediliyor...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data?.errors
                    ? Object.values(data.errors).flat()[0]
                    : data?.message;
                throw new Error(firstError || 'Kayıt başarısız');
            }

            if (typeof window.showToast === 'function') {
                window.showToast(data.message || 'Diğer ayarlar kaydedildi.', 'success');
            }
        } catch (error) {
            if (typeof window.showToast === 'function') {
                window.showToast(error.message || 'Kayıt başarısız', 'error');
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                const label = submitBtn.querySelector('.btn-cta-text');
                if (label) label.textContent = previousLabel || 'Ayarları Kaydet';
            }
        }
    });
}

function initTabs(root) {
    const tabs = root.querySelectorAll('[data-sabit-tab]');
    const panels = root.querySelectorAll('[data-sabit-panel]');
    const valid = [...tabs].map((t) => t.getAttribute('data-sabit-tab'));

    function setTab(name) {
        if (!valid.includes(name)) name = valid[0];

        tabs.forEach((tab) => {
            const active = tab.getAttribute('data-sabit-tab') === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            const active = panel.getAttribute('data-sabit-panel') === name;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });

        const url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        // Drop other-tab filter noise from the shared URL.
        ['q', 'durum', 'sort', 'direction', 'ajax'].forEach((key) => url.searchParams.delete(key));
        history.replaceState({}, '', url.pathname + url.search);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => setTab(tab.getAttribute('data-sabit-tab')));
    });

    const initial = new URL(window.location.href).searchParams.get('tab');
    if (initial && valid.includes(initial)) {
        setTab(initial);
    }
}

function initTabTable(tabKey) {
    const form = document.getElementById(`${tabKey}-filter-form`);
    const resultsId = `${tabKey}-results`;
    const cardId = `${tabKey}-table-card`;
    let isLoading = false;

    async function load(url) {
        if (isLoading) return;

        const target = new URL(url, window.location.origin);
        target.searchParams.set('tab', tabKey);
        target.searchParams.set('ajax', '1');

        isLoading = true;
        document.getElementById(cardId)?.classList.add('is-loading');

        try {
            const response = await fetch(target.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
            });

            if (!response.ok) throw new Error('İstek başarısız');

            const html = await response.text();
            const results = document.getElementById(resultsId);
            if (results) results.innerHTML = html;

            const clean = new URL(target.toString());
            clean.searchParams.delete('ajax');
            history.replaceState({}, '', clean.pathname + clean.search);

            bindTable();
        } catch (error) {
            console.error(error);
        } finally {
            isLoading = false;
            document.getElementById(cardId)?.classList.remove('is-loading');
        }
    }

    function formToUrl() {
        if (!form) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabKey);
            return url.toString();
        }

        const url = new URL(form.action, window.location.origin);
        const data = new FormData(form);
        for (const [key, value] of data.entries()) {
            if (value !== null && String(value).trim() !== '') {
                url.searchParams.append(key, value);
            }
        }
        url.searchParams.set('tab', tabKey);
        return url.toString();
    }

    function bindTable() {
        const table = document.getElementById(`${tabKey}-table`);
        if (!table) return;

        initSorting(table, {
            onSort: (key, direction) => {
                const url = new URL(formToUrl(), window.location.origin);
                url.searchParams.set('sort', key);
                url.searchParams.set('direction', direction);
                load(url.toString());
            },
        });
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        load(formToUrl());
    });

    form?.querySelector('[data-sabit-filter-clear]')?.addEventListener('click', () => {
        form.querySelectorAll('input[type="text"]').forEach((input) => {
            input.value = '';
        });
        form.querySelectorAll('select').forEach((select) => {
            select.value = select.dataset.resetValue ?? 'tumu';
        });
        load(formToUrl());
    });

    bindTable();

    return () => load(formToUrl());
}

async function refreshTabCount(tabKey) {
    const tab = document.querySelector(`[data-sabit-tab="${tabKey}"]`);
    const countEl = tab?.querySelector('.sabit-tab-count');
    if (!countEl) return;

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        url.searchParams.set('ajax', '1');
        url.searchParams.delete('q');
        url.searchParams.set('durum', 'tumu');
        url.searchParams.delete('sort');
        url.searchParams.delete('direction');

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
        });
        if (!response.ok) return;

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const footer = doc.querySelector('.table-footer span');
        const match = footer?.textContent?.match(/(\d+)/);
        if (match) {
            countEl.textContent = Number(match[1]).toLocaleString('tr-TR');
        }
    } catch {
        // ignore count refresh errors
    }
}
