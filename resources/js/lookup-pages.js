import { initEntityTable } from './entity-table';
import { initEntityModal } from './entity-modals';
import { initSearchModes } from './search-mode';

function normTr(value) {
    return String(value ?? '').trim().toLocaleLowerCase('tr');
}

function findSelectValue(select, desired) {
    if (!select || !desired) return '';
    const wanted = String(desired);
    const exact = [...select.options].find((o) => o.value === wanted);
    if (exact) return exact.value;
    const fuzzy = [...select.options].find((o) => normTr(o.value) === normTr(wanted));
    return fuzzy?.value ?? '';
}

function readIlceMap(form) {
    const el = form.querySelector('[data-ilce-map]');
    if (!el) return {};
    try {
        return JSON.parse(el.textContent || '{}');
    } catch {
        return {};
    }
}

function resolveIlceler(map, ilAd) {
    if (!ilAd) return [];
    if (Array.isArray(map[ilAd])) return map[ilAd];
    const key = Object.keys(map).find((k) => normTr(k) === normTr(ilAd));
    return key ? map[key] : [];
}

function populateMerkezIlceler(form, preferredIlce = '') {
    const ilSelect = form.querySelector('[data-merkez-il]');
    const ilceSelect = form.querySelector('[data-merkez-ilce]');
    if (!ilSelect || !ilceSelect) return;

    const list = resolveIlceler(readIlceMap(form), ilSelect.value);
    const current = preferredIlce || ilceSelect.value;
    ilceSelect.innerHTML = '<option value="">Seçiniz</option>' + list
        .map((ad) => `<option value="${String(ad).replaceAll('"', '&quot;')}">${String(ad)}</option>`)
        .join('');
    ilceSelect.disabled = !ilSelect.value;
    ilceSelect.value = findSelectValue(ilceSelect, current);
}

function syncMerkezIlValue(form, desiredIl) {
    const ilSelect = form.querySelector('[data-merkez-il]');
    if (!ilSelect) return;
    const matched = findSelectValue(ilSelect, desiredIl);
    if (matched) ilSelect.value = matched;
}

function wireMerkezIlIlce(form) {
    if (!form || form.dataset.ilIlceWired === '1') return;
    form.dataset.ilIlceWired = '1';
    form.querySelector('[data-merkez-il]')?.addEventListener('change', () => {
        populateMerkezIlceler(form, '');
    });
}

function initLookupPage({ tableId, tableConfig, modalId, modalConfig }) {
    const table = document.getElementById(tableId) ? initEntityTable(tableConfig) : null;

    if (document.getElementById(modalId)) {
        initEntityModal({
            ...modalConfig,
            onSuccess: () => {
                if (table) {
                    table.reload();
                } else {
                    window.location.reload();
                }
            },
        });
    }
}

export function initMerkezlerPage() {
    initLookupPage({
        tableId: 'merkezler-table',
        tableConfig: {
            tableId: 'merkezler-table',
            resultsId: 'merkezler-results',
            cardId: 'merkezler-table-card',
            filterFormId: 'merkezler-filter-form',
            clearBtnId: 'merkezler-filter-clear',
            cookieKey: 'merkezler_table_prefs',
            excelLinkId: 'merkezler-excel-link',
        },
        modalId: 'merkez-form-modal',
        modalConfig: {
            modalId: 'merkez-form-modal',
            formSelector: '.merkez-form',
            createTitle: 'Yeni Merkez',
            editTitle: 'Merkezi Düzenle',
            onCreate(form) {
                wireMerkezIlIlce(form);
                populateMerkezIlceler(form, '');
            },
            onEdit(form, btn) {
                wireMerkezIlIlce(form);
                syncMerkezIlValue(form, btn.dataset.il);
                populateMerkezIlceler(form, btn.dataset.ilce);
            },
        },
    });
}

export function initAlanlarPage() {
    initLookupPage({
        tableId: 'alanlar-table',
        tableConfig: {
            tableId: 'alanlar-table',
            resultsId: 'alanlar-results',
            cardId: 'alanlar-table-card',
            filterFormId: 'alanlar-filter-form',
            clearBtnId: 'alanlar-filter-clear',
            cookieKey: 'alanlar_table_prefs',
            excelLinkId: 'alanlar-excel-link',
        },
        modalId: 'alan-form-modal',
        modalConfig: {
            modalId: 'alan-form-modal',
            formSelector: '.alan-form',
            createTitle: 'Yeni Alan',
            editTitle: 'Alanı Düzenle',
        },
    });
}

export function initBranslarPage() {
    initLookupPage({
        tableId: 'branslar-table',
        tableConfig: {
            tableId: 'branslar-table',
            resultsId: 'branslar-results',
            cardId: 'branslar-table-card',
            filterFormId: 'branslar-filter-form',
            clearBtnId: 'branslar-filter-clear',
            cookieKey: 'branslar_table_prefs',
            excelLinkId: 'branslar-excel-link',
        },
        modalId: 'brans-form-modal',
        modalConfig: {
            modalId: 'brans-form-modal',
            formSelector: '.brans-form',
            createTitle: 'Yeni Branş',
            editTitle: 'Branşı Düzenle',
        },
    });
}

export function initEgitmenlerPage() {
    if (document.getElementById('egitmenler-table')) {
        initEntityTable({
            tableId: 'egitmenler-table',
            resultsId: 'egitmenler-results',
            cardId: 'egitmenler-table-card',
            filterFormId: 'egitmenler-filter-form',
            clearBtnId: 'egitmenler-filter-clear',
            cookieKey: 'egitmenler_table_prefs',
            excelLinkId: 'egitmenler-excel-link',
        });
    }
}

export function initKullanicilarPage() {
    if (document.getElementById('kullanicilar-table')) {
        initEntityTable({
            tableId: 'kullanicilar-table',
            resultsId: 'kullanicilar-results',
            cardId: 'kullanicilar-table-card',
            filterFormId: 'kullanicilar-filter-form',
            clearBtnId: 'kullanicilar-filter-clear',
            cookieKey: 'kullanicilar_table_prefs',
            excelLinkId: 'kullanicilar-excel-link',
        });
    }
}

export function initKisilerPage() {
    if (document.getElementById('kisiler-table')) {
        initEntityTable({
            tableId: 'kisiler-table',
            resultsId: 'kisiler-results',
            cardId: 'kisiler-table-card',
            filterFormId: 'kisiler-filter-form',
            clearBtnId: 'kisiler-filter-clear',
            cookieKey: 'kisiler_table_prefs',
            excelLinkId: 'kisiler-excel-link',
        });
    }
}

export function initMerkezYetkileriPage() {
    if (document.getElementById('merkez-yetkileri-table')) {
        initEntityTable({
            tableId: 'merkez-yetkileri-table',
            resultsId: 'merkez-yetkileri-results',
            cardId: 'merkez-yetkileri-table-card',
            filterFormId: 'merkez-yetkileri-filter-form',
            clearBtnId: 'merkez-yetkileri-filter-clear',
            cookieKey: 'merkez_yetkileri_table_prefs',
            excelLinkId: 'merkez-yetkileri-excel-link',
        });
    }
}

export function initEtkinliklerPage() {
    if (document.getElementById('etkinlikler-table')) {
        initSearchModes();
        initEntityTable({
            tableId: 'etkinlikler-table',
            resultsId: 'etkinlikler-results',
            cardId: 'etkinlikler-table-card',
            filterFormId: 'etkinlikler-filter-form',
            clearBtnId: 'etkinlikler-filter-clear',
            cookieKey: 'etkinlikler_table_prefs',
            excelLinkId: 'etkinlikler-excel-link',
        });
    }
}

export function initEtkinlikBasvurulariPage() {
    if (document.getElementById('etkinlik-basvurulari-table')) {
        initSearchModes();
        initEntityTable({
            tableId: 'etkinlik-basvurulari-table',
            resultsId: 'etkinlik-basvurulari-results',
            cardId: 'etkinlik-basvurulari-table-card',
            filterFormId: 'etkinlik-basvurulari-filter-form',
            clearBtnId: 'etkinlik-basvurulari-filter-clear',
            cookieKey: 'etkinlik_basvurulari_table_prefs',
            excelLinkId: 'etkinlik-basvurulari-excel-link',
        });
    }
}
