import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function validationMessage(error) {
    const data = error?.response?.data;
    if (!data) {
        return error?.message || 'İşlem başarısız.';
    }
    if (typeof data.message === 'string' && data.message) {
        return data.message;
    }
    const errors = data.errors;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat()[0];
        if (first) return String(first);
    }
    return 'İşlem başarısız.';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function formatFileSize(bytes) {
    const size = Number(bytes || 0);
    if (!size) return 'Boyut bilgisi yok';
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = size;
    let unitIndex = 0;
    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }
    const digits = value >= 10 || unitIndex === 0 ? 0 : 1;
    return `${value.toFixed(digits)} ${units[unitIndex]}`;
}

function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (![...document.querySelectorAll('.confirm-modal')].some((el) => !el.hidden)) {
        document.body.classList.remove('modal-open');
    }
}

function parseJsonArray(raw) {
    try {
        const parsed = JSON.parse(raw || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function renderEvraklarUi({ summaryEl, listEl, subtitleEl, katilimci, evraklar }) {
    const pendingEvraklar = Array.isArray(evraklar) ? evraklar : [];
    const pendingKatilimci = katilimci || 'Bu başvuru';

    if (!summaryEl || !listEl) {
        return pendingEvraklar;
    }

    const count = pendingEvraklar.length;
    if (subtitleEl) {
        subtitleEl.textContent = `"${pendingKatilimci}" için yüklenen evraklar`;
    }

    summaryEl.innerHTML = `
        <div class="basvuru-evraklar-summary-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.5a5.5 5.5 0 0 1-7.78-7.78l9.9-9.9a3.5 3.5 0 1 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 1 1-2.12-2.12l8.49-8.48"/></svg>
        </div>
        <div>
            <div class="basvuru-evraklar-summary-title">${count > 0 ? `${count} evrak yüklendi` : 'Yüklenmiş evrak bulunmuyor'}</div>
            <div class="basvuru-evraklar-summary-text">${count > 0 ? 'Evrak tipine göre dosyaları görüntüleyebilir veya yeni evrak ekleyebilirsiniz.' : 'Bu başvuruya henüz dosya eklenmemiş. Sağ üstteki + ile yükleyebilirsiniz.'}</div>
        </div>
    `;

    if (!count) {
        listEl.innerHTML = `
            <div class="empty-state basvuru-evraklar-empty">
                <div class="empty-state-title">Evrak yok</div>
                <p class="empty-state-text">Bu başvuru için yüklenmiş evrak kaydı bulunamadı.</p>
            </div>
        `;
        return pendingEvraklar;
    }

    listEl.innerHTML = pendingEvraklar.map((evrak) => {
        const type = escapeHtml(evrak?.tip || 'Evrak');
        const mime = escapeHtml(evrak?.mime || 'Tür bilgisi yok');
        const uploadedBy = escapeHtml(evrak?.yukleyen || 'Bilinmiyor');
        const uploadedAt = escapeHtml(evrak?.yuklenme_tarihi || 'Tarih yok');
        const description = evrak?.aciklama ? `<p class="basvuru-evrak-card-desc">${escapeHtml(evrak.aciklama)}</p>` : '';
        const url = evrak?.url ? escapeHtml(evrak.url) : '';
        const deleteUrl = evrak?.delete_url ? escapeHtml(evrak.delete_url) : '';
        const openAction = url
            ? `<a href="${url}" class="basvuru-evrak-card-link" target="_blank" rel="noopener noreferrer">
                    <span>Dosyayı Aç</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
               </a>`
            : `<span class="basvuru-evrak-card-link is-disabled">Dosya bağlantısı yok</span>`;
        const deleteAction = deleteUrl
            ? `<button
                    type="button"
                    class="basvuru-evrak-card-delete"
                    data-basvuru-evrak-sil
                    data-delete-url="${deleteUrl}"
                    data-tip="${type}"
                    title="Evrakı sil"
                    aria-label="Evrakı sil"
               >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                        <path d="M10 11v6"/>
                        <path d="M14 11v6"/>
                    </svg>
               </button>`
            : '';

        return `
            <article class="basvuru-evrak-card">
                <div class="basvuru-evrak-card-main">
                    <div class="basvuru-evrak-card-info">
                        <h4 class="basvuru-evrak-card-name">${type}</h4>
                        ${description}
                    </div>
                    <div class="basvuru-evrak-card-actions">
                        ${openAction}
                        ${deleteAction}
                    </div>
                </div>
                <div class="basvuru-evrak-meta-list">
                    <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Boyut</span><strong>${formatFileSize(evrak?.boyut)}</strong></span>
                    <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Biçim</span><strong>${mime}</strong></span>
                    <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Yükleyen</span><strong>${uploadedBy}</strong></span>
                    <span class="basvuru-evrak-meta-item"><span class="basvuru-evrak-meta-label">Yüklenme</span><strong>${uploadedAt}</strong></span>
                </div>
            </article>
        `;
    }).join('');

    return pendingEvraklar;
}

/**
 * Ortak evrak listesi / yükleme / silme bağlayıcısı.
 * @param {HTMLElement} root
 * @param {{
 *   uploadUrl?: string,
 *   tipOptions?: Array,
 *   katilimci?: string,
 *   initialEvraklar?: Array,
 *   onAfterChange?: () => Promise<void>|void,
 * }} options
 */
function bindBasvuruEvraklarRoot(root, options = {}) {
    if (!root || root.dataset.evraklarBound === '1') {
        return null;
    }
    root.dataset.evraklarBound = '1';

    const summaryEl = root.querySelector('[data-basvuru-evraklar-summary]');
    const listEl = root.querySelector('[data-basvuru-evraklar-list]');
    const subtitleEl = root.querySelector('[data-basvuru-evraklar-subtitle]');
    const uploadToggle = root.querySelector('[data-basvuru-evrak-upload-toggle]');
    const uploadPanel = root.querySelector('[data-basvuru-evrak-upload-panel]');
    const tipSelect = root.querySelector('[data-basvuru-evrak-tip-select]');
    const dosyaInput = root.querySelector('[data-basvuru-evrak-dosya]');
    const uploadCancel = root.querySelector('[data-basvuru-evrak-upload-cancel]');
    const uploadSubmit = root.querySelector('[data-basvuru-evrak-upload-submit]');

    let uploadUrl = options.uploadUrl || root.dataset.uploadUrl || '';
    let katilimci = options.katilimci || root.dataset.katilimci || 'Bu başvuru';
    let tipOptions = Array.isArray(options.tipOptions)
        ? options.tipOptions
        : parseJsonArray(root.dataset.evrakTipleri);
    let pendingEvraklar = Array.isArray(options.initialEvraklar)
        ? options.initialEvraklar
        : parseJsonArray(root.dataset.evraklar);

    function setUploadPanelOpen(open) {
        if (uploadPanel) {
            uploadPanel.hidden = !open;
        }
        uploadToggle?.classList.toggle('is-open', open);
        uploadToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function resetUploadForm() {
        if (tipSelect) tipSelect.value = '';
        if (dosyaInput) dosyaInput.value = '';
    }

    function fillTipSelect() {
        if (!tipSelect) return;
        tipSelect.innerHTML = [
            '<option value="">Seçiniz</option>',
            ...tipOptions.map((tip) => `<option value="${escapeHtml(String(tip.id))}">${escapeHtml(tip.ad || 'Evrak')}</option>`),
        ].join('');
    }

    function render(nextEvraklar = pendingEvraklar, nextKatilimci = katilimci) {
        katilimci = nextKatilimci || katilimci;
        pendingEvraklar = renderEvraklarUi({
            summaryEl,
            listEl,
            subtitleEl,
            katilimci,
            evraklar: nextEvraklar,
        });
        root.dataset.evraklar = JSON.stringify(pendingEvraklar);
        return pendingEvraklar;
    }

    async function afterChange() {
        if (options.onAfterChange) {
            await options.onAfterChange();
        }
    }

    uploadToggle?.addEventListener('click', () => {
        const willOpen = Boolean(uploadPanel?.hidden);
        if (willOpen && !uploadUrl) {
            showToast('Bu başvuru için evrak yükleme adresi bulunamadı.', 'error');
            return;
        }
        if (willOpen && tipOptions.length === 0) {
            showToast('Yüklenebilecek evrak tipi tanımlı değil.', 'error');
            return;
        }
        setUploadPanelOpen(willOpen);
        if (willOpen) {
            fillTipSelect();
            tipSelect?.focus();
        } else {
            resetUploadForm();
        }
    });

    uploadCancel?.addEventListener('click', () => {
        setUploadPanelOpen(false);
        resetUploadForm();
    });

    uploadSubmit?.addEventListener('click', async () => {
        if (!uploadUrl) {
            showToast('Bu başvuru için evrak yükleme adresi bulunamadı.', 'error');
            return;
        }

        const tipId = tipSelect?.value || '';
        const file = dosyaInput?.files?.[0] || null;

        if (!tipId) {
            showToast('Evrak tipi seçilmelidir.', 'error');
            tipSelect?.focus();
            return;
        }
        if (!file) {
            showToast('Dosya seçilmelidir.', 'error');
            dosyaInput?.focus();
            return;
        }

        const formData = new FormData();
        formData.append('evrak_tipi_id', tipId);
        formData.append('dosya', file);

        uploadSubmit.disabled = true;
        try {
            const { data } = await window.axios.post(uploadUrl, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            render(Array.isArray(data.evraklar) ? data.evraklar : pendingEvraklar);
            resetUploadForm();
            setUploadPanelOpen(false);
            showToast(data.message || 'Evrak yüklendi.', 'success');
            await afterChange();
        } catch (error) {
            showToast(validationMessage(error) || 'Evrak yüklenemedi.', 'error');
        } finally {
            uploadSubmit.disabled = false;
        }
    });

    listEl?.addEventListener('click', async (event) => {
        const deleteBtn = event.target.closest('[data-basvuru-evrak-sil]');
        if (!deleteBtn) return;

        event.preventDefault();
        const deleteUrl = deleteBtn.dataset.deleteUrl || '';
        if (!deleteUrl) return;

        const tipAdi = deleteBtn.dataset.tip || 'bu evrak';
        if (!window.confirm(`"${tipAdi}" evrakını silmek istediğinize emin misiniz?`)) {
            return;
        }

        deleteBtn.disabled = true;
        try {
            const { data } = await window.axios.delete(deleteUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            render(Array.isArray(data.evraklar) ? data.evraklar : pendingEvraklar);
            showToast(data.message || 'Evrak silindi.', 'success');
            await afterChange();
        } catch (error) {
            showToast(validationMessage(error) || 'Evrak silinemedi.', 'error');
            deleteBtn.disabled = false;
        }
    });

    render(pendingEvraklar, katilimci);

    return {
        render,
        setContext({ nextUploadUrl, nextKatilimci, nextEvraklar, nextTipOptions } = {}) {
            if (typeof nextUploadUrl === 'string') {
                uploadUrl = nextUploadUrl;
            }
            if (typeof nextKatilimci === 'string') {
                katilimci = nextKatilimci;
            }
            if (Array.isArray(nextTipOptions)) {
                tipOptions = nextTipOptions;
            }
            if (Array.isArray(nextEvraklar)) {
                render(nextEvraklar, katilimci);
            }
            resetUploadForm();
            setUploadPanelOpen(false);
        },
        setUploadPanelOpen,
        resetUploadForm,
        fillTipSelect,
    };
}

/**
 * Kurs Başvuruları listesi için Evraklar modalını bağlar.
 * @param {{ onReload?: () => Promise<void>|void }} options
 */
export function initBasvuruEvraklarModal(options = {}) {
    if (!document.getElementById('basvurular-table') && !document.getElementById('basvurular-results')) {
        return;
    }

    const evraklarModal = document.getElementById('basvuru-evraklar-modal');
    if (!evraklarModal || evraklarModal.dataset.listBound === '1') {
        return;
    }
    evraklarModal.dataset.listBound = '1';

    const controller = bindBasvuruEvraklarRoot(evraklarModal, {
        tipOptions: parseJsonArray(evraklarModal.dataset.evrakTipleri),
        initialEvraklar: [],
        onAfterChange: async () => {
            if (options.onReload) {
                await options.onReload();
                return;
            }
            window.location.reload();
        },
    });

    if (!controller) {
        return;
    }

    document.addEventListener('click', (event) => {
        const openBtn = event.target.closest('[data-basvuru-evraklar-ac]');
        if (!openBtn) return;

        event.preventDefault();
        if (typeof window.closeOtherMenus === 'function') {
            window.closeOtherMenus();
        }

        controller.setContext({
            nextUploadUrl: openBtn.dataset.uploadUrl || '',
            nextKatilimci: openBtn.dataset.katilimci || 'Bu başvuru',
            nextEvraklar: parseJsonArray(openBtn.dataset.evraklar),
        });
        openModal(evraklarModal);
    });

    evraklarModal.querySelectorAll('[data-basvuru-evraklar-close]').forEach((el) => {
        el.addEventListener('click', () => {
            controller.setUploadPanelOpen(false);
            controller.resetUploadForm();
            closeModal(evraklarModal);
        });
    });
}

/**
 * Kurs başvuru detay sayfasındaki inline Evraklar bölümünü bağlar.
 */
export function initBasvuruEvraklarPanel() {
    const panel = document.getElementById('basvuru-evraklar-panel');
    if (!panel) {
        return;
    }

    bindBasvuruEvraklarRoot(panel, {
        uploadUrl: panel.dataset.uploadUrl || '',
        tipOptions: parseJsonArray(panel.dataset.evrakTipleri),
        katilimci: panel.dataset.katilimci || 'Bu başvuru',
        initialEvraklar: parseJsonArray(panel.dataset.evraklar),
    });
}
