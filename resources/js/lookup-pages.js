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
            onSuccess: (data) => {
                modalConfig.onSuccess?.(data);
                if (data?.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
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
    if (document.getElementById('merkezler-table')) {
        initSearchModes();
    }

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
    if (document.getElementById('alanlar-table')) {
        initSearchModes();
    }

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
    if (document.getElementById('branslar-table')) {
        initSearchModes();
    }

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
        initSearchModes();
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
        initSearchModes();
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
        initSearchModes();
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

export function initKresDonemlerPage() {
    if (document.getElementById('kres-donemler-table')) {
        initSearchModes();
    }

    initLookupPage({
        tableId: 'kres-donemler-table',
        tableConfig: {
            tableId: 'kres-donemler-table',
            resultsId: 'kres-donemler-results',
            cardId: 'kres-donemler-table-card',
            filterFormId: 'kres-donemler-filter-form',
            clearBtnId: 'kres-donemler-filter-clear',
            cookieKey: 'kres_donemler_table_prefs',
            excelLinkId: 'kres-donemler-excel-link',
        },
        modalId: 'kres-donem-form-modal',
        modalConfig: {
            modalId: 'kres-donem-form-modal',
            formSelector: '.kres-donem-form',
            createTitle: 'Yeni Dönem',
            editTitle: 'Dönemi Düzenle',
            onCreate(form) {
                syncKresDonemYayinla(form);
            },
            onEdit(form) {
                syncKresDonemYayinla(form);
            },
            beforeSubmit: confirmKresDonemAktifDegisimi,
            onSuccess(data) {
                const form = document.querySelector('.kres-donem-form');
                if (form && Array.isArray(data?.aktif_donemler)) {
                    form.dataset.aktifDonemler = JSON.stringify(data.aktif_donemler);
                }
            },
        },
    });

    const donemForm = document.querySelector('.kres-donem-form');
    donemForm?.querySelector('[data-field="aktif"]')?.addEventListener('change', () => {
        syncKresDonemYayinla(donemForm);
    });
}

function syncKresDonemYayinla(form) {
    const aktif = form.querySelector('[data-field="aktif"]');
    const yayinla = form.querySelector('[data-field="yayinla"]');
    if (!aktif || !yayinla) {
        return;
    }

    const aktifMi = !!aktif.checked;
    yayinla.disabled = !aktifMi;
    if (!aktifMi) {
        yayinla.checked = false;
    }
}

function parseAktifDonemler(form) {
    try {
        const parsed = JSON.parse(form.dataset.aktifDonemler || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function editingDonemId(form) {
    const match = String(form.action || '').match(/\/donemler\/(\d+)(?:\/|$|\?)/);
    return match ? Number(match[1]) : null;
}

function confirmKresDonemAktifDegisimi(form) {
    const aktif = form.querySelector('[data-field="aktif"]')?.checked;
    if (!aktif) {
        return true;
    }

    const editingId = editingDonemId(form);
    const others = parseAktifDonemler(form).filter((donem) => Number(donem.id) !== editingId);
    if (!others.length) {
        return true;
    }

    const names = others.map((donem) => `"${donem.ad}"`).join(', ');
    const message = others.length === 1
        ? `Yalnızca bir dönem aktif olabilir. ${names} pasife alınacak. Onaylıyor musunuz?`
        : `Yalnızca bir dönem aktif olabilir. Aktif dönemler pasife alınacak: ${names}. Onaylıyor musunuz?`;

    return askKresDonemAktifConfirm(message);
}

function askKresDonemAktifConfirm(message) {
    const modal = document.getElementById('kres-donem-aktif-confirm');
    const text = modal?.querySelector('[data-aktif-confirm-message]');
    if (!modal || !text) {
        return window.confirm(message);
    }

    text.textContent = message;
    modal.hidden = false;
    document.body.classList.add('modal-open');

    return new Promise((resolve) => {
        const finish = (ok) => {
            modal.hidden = true;
            modal.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKey);
            resolve(ok);
        };
        const onClick = (event) => {
            if (event.target.closest('[data-aktif-confirm-ok]')) {
                event.preventDefault();
                finish(true);
                return;
            }
            if (event.target.closest('[data-aktif-confirm-cancel]')) {
                event.preventDefault();
                finish(false);
            }
        };
        const onKey = (event) => {
            if (event.key === 'Escape') {
                finish(false);
            }
        };
        modal.addEventListener('click', onClick);
        document.addEventListener('keydown', onKey);
    });
}

export function initKresOkullarTanimPage() {
    if (document.getElementById('kres-okullar-table')) {
        initSearchModes();
    }

    initLookupPage({
        tableId: 'kres-okullar-table',
        tableConfig: {
            tableId: 'kres-okullar-table',
            resultsId: 'kres-okullar-results',
            cardId: 'kres-okullar-table-card',
            filterFormId: 'kres-okullar-filter-form',
            clearBtnId: 'kres-okullar-filter-clear',
            cookieKey: 'kres_okullar_table_prefs',
            excelLinkId: 'kres-okullar-excel-link',
        },
        modalId: 'kres-okul-form-modal',
        modalConfig: {
            modalId: 'kres-okul-form-modal',
            formSelector: '.kres-okul-form',
            createTitle: 'Yeni Okul',
            editTitle: 'Okulu Düzenle',
        },
    });
}

export function initKresGruplarTanimPage() {
    if (document.getElementById('kres-gruplar-table')) {
        initSearchModes();
    }

    initLookupPage({
        tableId: 'kres-gruplar-table',
        tableConfig: {
            tableId: 'kres-gruplar-table',
            resultsId: 'kres-gruplar-results',
            cardId: 'kres-gruplar-table-card',
            filterFormId: 'kres-gruplar-filter-form',
            clearBtnId: 'kres-gruplar-filter-clear',
            cookieKey: 'kres_gruplar_table_prefs',
            excelLinkId: 'kres-gruplar-excel-link',
        },
        modalId: 'kres-grup-form-modal',
        modalConfig: {
            modalId: 'kres-grup-form-modal',
            formSelector: '.kres-grup-form',
            createTitle: 'Yeni Grup',
            editTitle: 'Grubu Düzenle',
        },
    });
}

function parseFormluDonemler(form) {
    try {
        const parsed = JSON.parse(form.dataset.formluDonemler || '[]');
        return Array.isArray(parsed) ? parsed.map(Number) : [];
    } catch {
        return [];
    }
}

function syncSoruFormuDonemSecenekleri(form, currentId = null) {
    const used = parseFormluDonemler(form);
    const current = currentId == null ? null : Number(currentId);
    form.querySelectorAll('[data-field="donemId"] option').forEach((option) => {
        if (!option.value) {
            option.disabled = false;
            return;
        }
        const id = Number(option.value);
        option.disabled = used.includes(id) && id !== current;
    });
}

export function initKresSoruFormlariPage() {
    if (document.getElementById('kres-soru-formlari-table')) {
        initSearchModes();
    }

    initLookupPage({
        tableId: 'kres-soru-formlari-table',
        tableConfig: {
            tableId: 'kres-soru-formlari-table',
            resultsId: 'kres-soru-formlari-results',
            cardId: 'kres-soru-formlari-table-card',
            filterFormId: 'kres-soru-formlari-filter-form',
            clearBtnId: 'kres-soru-formlari-filter-clear',
            cookieKey: 'kres_soru_formlari_table_prefs',
            excelLinkId: 'kres-soru-formlari-excel-link',
        },
        modalId: 'kres-soru-formu-form-modal',
        modalConfig: {
            modalId: 'kres-soru-formu-form-modal',
            formSelector: '.kres-soru-formu-meta-form',
            createTitle: 'Yeni Form',
            editTitle: 'Formu Düzenle',
            onCreate(form) {
                syncSoruFormuDonemSecenekleri(form);
            },
            onEdit(form, btn) {
                syncSoruFormuDonemSecenekleri(form, btn.dataset.donemId);
            },
        },
    });
}
