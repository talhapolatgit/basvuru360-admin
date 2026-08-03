import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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

function errorMessage(error) {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    if (typeof data?.message === 'string' && data.message) return data.message;
    return 'İşlem sırasında bir hata oluştu.';
}

export function initSmsModal() {
    const modal = document.getElementById('sms-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const mesajInput = modal.querySelector('[data-sms-mesaj]');
    const charCount = modal.querySelector('[data-sms-char-count]');
    const listEl = modal.querySelector('[data-sms-alicilar-list]');
    const countEl = modal.querySelector('[data-sms-alicilar-count]');
    const sendBtn = modal.querySelector('[data-sms-send]');
    const clearAllBtn = modal.querySelector('[data-sms-clear-all]');
    const searchInput = modal.querySelector('[data-sms-search]');
    const searchResults = modal.querySelector('[data-sms-search-results]');
    const filterButtons = modal.querySelectorAll('[data-sms-filter]');
    const alicilarUrl = modal.dataset.smsAlicilarUrl || '';

    let allAlicilar = [];
    let visibleIds = new Set();
    let currentFilter = 'tumu';
    let loading = false;

    function setModalOpen(open) {
        modal.hidden = !open;
        document.body.classList.toggle('modal-open', open);
        if (!open) {
            hideSearchResults();
            if (searchInput) searchInput.value = '';
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase('tr-TR')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function filteredAlicilar() {
        return allAlicilar.filter((item) => {
            if (!visibleIds.has(String(item.id))) return false;
            if (currentFilter === 'tumu') return true;
            return item.durum_kod === currentFilter;
        });
    }

    function renderAlicilar() {
        if (!listEl) return;
        const items = filteredAlicilar();

        if (countEl) {
            countEl.textContent = `${items.length} kişi`;
        }

        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Bu filtreye uygun alıcı yok. Arama ile ekleyebilirsiniz.</p>';
            return;
        }

        listEl.innerHTML = items.map((item) => `
            <span class="sms-alici-chip ${item.telefon_var ? '' : 'is-no-phone'}" data-sms-alici-id="${item.id}" title="${item.telefon_var ? '' : 'Telefon yok'}">
                <span class="sms-alici-chip-name">${escapeHtml(item.ad)}</span>
                <button
                    type="button"
                    class="sms-alici-chip-remove"
                    data-sms-alici-remove="${item.id}"
                    title="Listeden kaldır"
                    aria-label="${escapeHtml(item.ad)} kişisini kaldır"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </span>
        `).join('');
    }

    function setFilter(kod) {
        currentFilter = kod || 'tumu';
        filterButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.smsFilter === currentFilter);
        });
        renderAlicilar();
    }

    function hideSearchResults() {
        if (!searchResults) return;
        searchResults.hidden = true;
        searchResults.innerHTML = '';
    }

    function searchCandidates(query) {
        const q = normalizeSearch(query);
        const qDigits = digitsOnly(query);
        if (q.length < 2 && qDigits.length < 3) {
            return [];
        }

        return allAlicilar
            .filter((item) => !visibleIds.has(String(item.id)))
            .filter((item) => {
                const ad = normalizeSearch(item.ad);
                const tc = digitsOnly(item.tc);
                if (q && ad.includes(q)) return true;
                if (qDigits.length >= 3 && tc.includes(qDigits)) return true;
                return false;
            })
            .slice(0, 12);
    }

    function renderSearchResults(query) {
        if (!searchResults) return;
        const items = searchCandidates(query);

        if (!normalizeSearch(query) && digitsOnly(query).length < 3) {
            hideSearchResults();
            return;
        }

        if (!items.length) {
            searchResults.hidden = false;
            searchResults.innerHTML = '<p class="sms-alici-search-empty">Eklenebilir alıcı bulunamadı.</p>';
            return;
        }

        searchResults.hidden = false;
        searchResults.innerHTML = items.map((item) => `
            <button type="button" class="sms-alici-search-item" data-sms-add-id="${item.id}">
                <strong>${escapeHtml(item.ad)}</strong>
                <small>${escapeHtml(item.tc || 'TC yok')}${item.durum_ad ? ` · ${escapeHtml(item.durum_ad)}` : ''}${item.telefon_var ? '' : ' · Telefon yok'}</small>
            </button>
        `).join('');
    }

    function addAlici(id) {
        const key = String(id);
        const item = allAlicilar.find((row) => String(row.id) === key);
        if (!item) return;

        visibleIds.add(key);
        if (currentFilter !== 'tumu' && item.durum_kod !== currentFilter) {
            setFilter('tumu');
        } else {
            renderAlicilar();
        }

        if (searchInput) searchInput.value = '';
        hideSearchResults();
        showToast(`${item.ad} listeye eklendi.`, 'success');
    }

    async function loadAlicilar() {
        if (!alicilarUrl || !listEl) return;
        loading = true;
        listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yükleniyor...</p>';
        if (countEl) countEl.textContent = '…';
        hideSearchResults();
        if (searchInput) searchInput.value = '';

        try {
            const { data } = await window.axios.get(alicilarUrl, {
                headers: { Accept: 'application/json' },
            });
            allAlicilar = Array.isArray(data.alicilar) ? data.alicilar : [];
            visibleIds = new Set(allAlicilar.map((item) => String(item.id)));
            renderAlicilar();
        } catch (error) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yüklenemedi.</p>';
            showToast(errorMessage(error), 'error');
        } finally {
            loading = false;
        }
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-sms-modal-open]');
        if (!btn || btn.disabled) return;
        setFilter('tumu');
        if (mesajInput) mesajInput.value = '';
        if (charCount) charCount.textContent = '0';
        setModalOpen(true);
        loadAlicilar();
        searchInput?.focus();
    });

    modal.querySelectorAll('[data-sms-modal-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(false));
    });

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => setFilter(btn.dataset.smsFilter || 'tumu'));
    });

    clearAllBtn?.addEventListener('click', () => {
        visibleIds.clear();
        hideSearchResults();
        renderAlicilar();
        showToast('Tüm alıcılar temizlendi.', 'success');
        searchInput?.focus();
    });

    listEl?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-sms-alici-remove]');
        if (!removeBtn) return;
        visibleIds.delete(String(removeBtn.dataset.smsAliciRemove));
        renderAlicilar();
        renderSearchResults(searchInput?.value || '');
    });

    searchInput?.addEventListener('input', () => {
        renderSearchResults(searchInput.value);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideSearchResults();
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            const first = searchResults?.querySelector('[data-sms-add-id]');
            if (first) {
                addAlici(first.dataset.smsAddId);
            }
        }
    });

    searchResults?.addEventListener('click', (event) => {
        const itemBtn = event.target.closest('[data-sms-add-id]');
        if (!itemBtn) return;
        addAlici(itemBtn.dataset.smsAddId);
    });

    document.addEventListener('click', (event) => {
        if (modal.hidden) return;
        if (event.target.closest('[data-sms-search-wrap]')) return;
        hideSearchResults();
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) {
            charCount.textContent = String(mesajInput.value.length);
        }
    });

    modal.querySelectorAll('[data-sms-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!mesajInput) return;
            const token = btn.getAttribute('data-sms-insert') || '';
            if (!token) return;

            const start = mesajInput.selectionStart ?? mesajInput.value.length;
            const end = mesajInput.selectionEnd ?? start;
            const before = mesajInput.value.slice(0, start);
            const after = mesajInput.value.slice(end);
            const next = `${before}${token}${after}`.slice(0, Number(mesajInput.maxLength) || 480);
            mesajInput.value = next;
            const cursor = Math.min(start + token.length, next.length);
            mesajInput.focus();
            mesajInput.setSelectionRange(cursor, cursor);
            if (charCount) {
                charCount.textContent = String(mesajInput.value.length);
            }
        });
    });

    function personalizeSms(template, adSoyad) {
        return String(template || '').replaceAll(/\{ad_soyad\}/gi, adSoyad || '');
    }

    function openSmsOnizleme() {
        const sablon = mesajInput?.value?.trim() || '';
        if (!sablon) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            mesajInput?.focus();
            return;
        }

        const alicilar = filteredAlicilar();
        if (!alicilar.length) {
            showToast('Önizleme için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const ilkGonderilecek = alicilar.find((item) => item.telefon_var) || alicilar[0];
        const onizlemeModal = document.getElementById('sms-onizleme-modal');
        const aliciEl = onizlemeModal?.querySelector('[data-sms-onizleme-alici]');
        const mesajEl = onizlemeModal?.querySelector('[data-sms-onizleme-mesaj]');
        if (!onizlemeModal || !mesajEl) return;

        const kisisel = personalizeSms(sablon, ilkGonderilecek.ad || 'Ad Soyad');
        if (aliciEl) {
            const telefonNotu = ilkGonderilecek.telefon_var
                ? ''
                : ' <span>(telefon yok — gönderimde atlanır)</span>';
            aliciEl.innerHTML = `İlk gönderilecek: <strong>${escapeHtml(ilkGonderilecek.ad || 'İsimsiz')}</strong>${telefonNotu}`;
        }
        mesajEl.textContent = kisisel;
        openModal(onizlemeModal);
    }

    modal.querySelector('[data-sms-onizle]')?.addEventListener('click', openSmsOnizleme);

    const onizlemeModal = document.getElementById('sms-onizleme-modal');
    if (onizlemeModal && onizlemeModal.dataset.bound !== '1') {
        onizlemeModal.dataset.bound = '1';
        onizlemeModal.querySelectorAll('[data-sms-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(onizlemeModal));
        });
    }

    sendBtn?.addEventListener('click', async () => {
        const mesaj = mesajInput?.value?.trim() || '';
        const ids = filteredAlicilar().map((item) => item.id);

        if (!mesaj) {
            showToast('SMS metni zorunludur.', 'error');
            mesajInput?.focus();
            return;
        }

        if (!ids.length) {
            showToast('SMS göndermek için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const url = sendBtn.dataset.smsUrl;
        if (!url || loading) return;

        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesaj', mesaj);
            formData.append('basvuru_durum', currentFilter);
            ids.forEach((id) => formData.append('basvuru_ids[]', id));

            const { data } = await window.axios.post(url, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            setModalOpen(false);
            showToast(data.message || 'SMS gönderildi.', 'success');

            const pageUrl = new URL(window.location.href);
            pageUrl.searchParams.set('tab', 'mesajlar');
            pageUrl.searchParams.set('kanal', 'sms');
            setTimeout(() => {
                window.location.href = pageUrl.pathname + pageUrl.search;
            }, 700);
        } catch (error) {
            showToast(errorMessage(error), 'error');
        } finally {
            sendBtn.disabled = false;
        }
    });
}

export function initSmsAlicilarDetayModal() {
    const modal = document.getElementById('sms-alicilar-detay-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const listEl = modal.querySelector('[data-sms-alicilar-detay-list]');

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function parseDetay(raw) {
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function renderDetay(items) {
        if (!listEl) return;
        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-detay-empty">Alıcı bilgisi bulunamadı.</p>';
            return;
        }

        listEl.innerHTML = items
            .map((item) => {
                const ad = escapeHtml(item?.ad || 'İsimsiz');
                const telefon = item?.telefon ? escapeHtml(item.telefon) : 'Telefon yok';
                const gonderildi = item?.durum === 'gonderildi';
                const statusClass = gonderildi ? 'status-aktif' : 'status-iptal';
                const statusText = gonderildi ? 'Gönderildi' : 'Atlandı';
                const hata = item?.hata && !gonderildi
                    ? `<div class="sms-alicilar-detay-hata">${escapeHtml(item.hata)}</div>`
                    : '';
                const mesaj = item?.mesaj
                    ? `<div class="sms-alicilar-detay-telefon">${escapeHtml(item.mesaj)}</div>`
                    : '';

                return `
                    <div class="sms-alicilar-detay-item">
                        <div class="sms-alicilar-detay-info">
                            <div class="sms-alicilar-detay-ad">${ad}</div>
                            <div class="sms-alicilar-detay-telefon">${telefon}</div>
                            ${mesaj}
                            ${hata}
                        </div>
                        <span class="status ${statusClass}">${statusText}</span>
                    </div>
                `;
            })
            .join('');
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-sms-alicilar-detay-ac]');
        if (!btn) return;
        renderDetay(parseDetay(btn.getAttribute('data-sms-alicilar-detay')));
        openModal(modal);
    });

    modal.querySelectorAll('[data-sms-alicilar-detay-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
}

export function initEpostaModal() {
    const modal = document.getElementById('eposta-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const konuInput = modal.querySelector('[data-eposta-konu]');
    const mesajInput = modal.querySelector('[data-eposta-mesaj]');
    const charCount = modal.querySelector('[data-eposta-char-count]');
    const listEl = modal.querySelector('[data-eposta-alicilar-list]');
    const countEl = modal.querySelector('[data-eposta-alicilar-count]');
    const sendBtn = modal.querySelector('[data-eposta-send]');
    const clearAllBtn = modal.querySelector('[data-eposta-clear-all]');
    const searchInput = modal.querySelector('[data-eposta-search]');
    const searchResults = modal.querySelector('[data-eposta-search-results]');
    const filterButtons = modal.querySelectorAll('[data-eposta-filter]');
    const alicilarUrl = modal.dataset.epostaAlicilarUrl || '';

    let allAlicilar = [];
    let visibleIds = new Set();
    let currentFilter = 'tumu';
    let loading = false;

    function setModalOpen(open) {
        modal.hidden = !open;
        document.body.classList.toggle('modal-open', open);
        if (!open) {
            hideSearchResults();
            if (searchInput) searchInput.value = '';
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function normalizeSearch(value) {
        return String(value || '')
            .toLocaleLowerCase('tr-TR')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function filteredAlicilar() {
        return allAlicilar.filter((item) => {
            if (!visibleIds.has(String(item.id))) return false;
            if (currentFilter === 'tumu') return true;
            return item.durum_kod === currentFilter;
        });
    }

    function renderAlicilar() {
        if (!listEl) return;
        const items = filteredAlicilar();

        if (countEl) {
            countEl.textContent = `${items.length} kişi`;
        }

        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Bu filtreye uygun alıcı yok. Arama ile ekleyebilirsiniz.</p>';
            return;
        }

        listEl.innerHTML = items.map((item) => `
            <span class="sms-alici-chip ${item.email_var ? '' : 'is-no-email'}" data-eposta-alici-id="${item.id}" title="${item.email_var ? '' : 'E-posta yok'}">
                <span class="sms-alici-chip-name">${escapeHtml(item.ad)}</span>
                <button
                    type="button"
                    class="sms-alici-chip-remove"
                    data-eposta-alici-remove="${item.id}"
                    title="Listeden kaldır"
                    aria-label="${escapeHtml(item.ad)} kişisini kaldır"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </span>
        `).join('');
    }

    function setFilter(kod) {
        currentFilter = kod || 'tumu';
        filterButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.epostaFilter === currentFilter);
        });
        renderAlicilar();
    }

    function hideSearchResults() {
        if (!searchResults) return;
        searchResults.hidden = true;
        searchResults.innerHTML = '';
    }

    function searchCandidates(query) {
        const q = normalizeSearch(query);
        const qDigits = digitsOnly(query);
        if (q.length < 2 && qDigits.length < 3) {
            return [];
        }

        return allAlicilar
            .filter((item) => !visibleIds.has(String(item.id)))
            .filter((item) => {
                const ad = normalizeSearch(item.ad);
                const tc = digitsOnly(item.tc);
                if (q && ad.includes(q)) return true;
                if (qDigits.length >= 3 && tc.includes(qDigits)) return true;
                return false;
            })
            .slice(0, 12);
    }

    function renderSearchResults(query) {
        if (!searchResults) return;
        const items = searchCandidates(query);

        if (!normalizeSearch(query) && digitsOnly(query).length < 3) {
            hideSearchResults();
            return;
        }

        if (!items.length) {
            searchResults.hidden = false;
            searchResults.innerHTML = '<p class="sms-alici-search-empty">Eklenebilir alıcı bulunamadı.</p>';
            return;
        }

        searchResults.hidden = false;
        searchResults.innerHTML = items.map((item) => `
            <button type="button" class="sms-alici-search-item" data-eposta-add-id="${item.id}">
                <strong>${escapeHtml(item.ad)}</strong>
                <small>${escapeHtml(item.tc || 'TC yok')}${item.durum_ad ? ` · ${escapeHtml(item.durum_ad)}` : ''}${item.email_var ? '' : ' · E-posta yok'}</small>
            </button>
        `).join('');
    }

    function addAlici(id) {
        const key = String(id);
        const item = allAlicilar.find((row) => String(row.id) === key);
        if (!item) return;

        visibleIds.add(key);
        if (currentFilter !== 'tumu' && item.durum_kod !== currentFilter) {
            setFilter('tumu');
        } else {
            renderAlicilar();
        }

        if (searchInput) searchInput.value = '';
        hideSearchResults();
        showToast(`${item.ad} listeye eklendi.`, 'success');
    }

    async function loadAlicilar() {
        if (!alicilarUrl || !listEl) return;
        loading = true;
        listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yükleniyor...</p>';
        if (countEl) countEl.textContent = '…';
        hideSearchResults();
        if (searchInput) searchInput.value = '';

        try {
            const { data } = await window.axios.get(alicilarUrl, {
                headers: { Accept: 'application/json' },
            });
            allAlicilar = Array.isArray(data.alicilar) ? data.alicilar : [];
            visibleIds = new Set(allAlicilar.map((item) => String(item.id)));
            renderAlicilar();
        } catch (error) {
            listEl.innerHTML = '<p class="sms-alicilar-empty">Alıcılar yüklenemedi.</p>';
            showToast(errorMessage(error), 'error');
        } finally {
            loading = false;
        }
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-eposta-modal-open]');
        if (!btn || btn.disabled) return;
        setFilter('tumu');
        if (konuInput) konuInput.value = '';
        if (mesajInput) mesajInput.value = '';
        if (charCount) charCount.textContent = '0';
        setModalOpen(true);
        loadAlicilar();
        searchInput?.focus();
    });

    modal.querySelectorAll('[data-eposta-modal-close]').forEach((el) => {
        el.addEventListener('click', () => setModalOpen(false));
    });

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => setFilter(btn.dataset.epostaFilter || 'tumu'));
    });

    clearAllBtn?.addEventListener('click', () => {
        visibleIds.clear();
        hideSearchResults();
        renderAlicilar();
        showToast('Tüm alıcılar temizlendi.', 'success');
        searchInput?.focus();
    });

    listEl?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-eposta-alici-remove]');
        if (!removeBtn) return;
        visibleIds.delete(String(removeBtn.dataset.epostaAliciRemove));
        renderAlicilar();
        renderSearchResults(searchInput?.value || '');
    });

    searchInput?.addEventListener('input', () => {
        renderSearchResults(searchInput.value);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideSearchResults();
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            const first = searchResults?.querySelector('[data-eposta-add-id]');
            if (first) {
                addAlici(first.dataset.epostaAddId);
            }
        }
    });

    searchResults?.addEventListener('click', (event) => {
        const itemBtn = event.target.closest('[data-eposta-add-id]');
        if (!itemBtn) return;
        addAlici(itemBtn.dataset.epostaAddId);
    });

    document.addEventListener('click', (event) => {
        if (modal.hidden) return;
        if (event.target.closest('[data-eposta-search-wrap]')) return;
        hideSearchResults();
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) {
            charCount.textContent = String(mesajInput.value.length);
        }
    });

    function personalizeEposta(template, adSoyad) {
        return String(template || '').replaceAll(/\{ad_soyad\}/gi, adSoyad || '');
    }

    function insertEpostaToken(target, token) {
        const input = target === 'konu' ? konuInput : mesajInput;
        if (!input || !token) return;

        if (typeof input.selectionStart === 'number') {
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? start;
            const before = input.value.slice(0, start);
            const after = input.value.slice(end);
            const maxLen = Number(input.maxLength) || (target === 'konu' ? 200 : 5000);
            const next = `${before}${token}${after}`.slice(0, maxLen);
            input.value = next;
            const cursor = Math.min(start + token.length, next.length);
            input.focus();
            input.setSelectionRange(cursor, cursor);
        } else {
            input.value = `${input.value}${token}`.slice(0, Number(input.maxLength) || 200);
            input.focus();
        }

        if (target === 'mesaj' && charCount) {
            charCount.textContent = String(mesajInput.value.length);
        }
    }

    modal.querySelectorAll('[data-eposta-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            insertEpostaToken(
                btn.getAttribute('data-eposta-insert-target') || 'mesaj',
                btn.getAttribute('data-eposta-insert') || '',
            );
        });
    });

    function openEpostaOnizleme() {
        const konu = konuInput?.value?.trim() || '';
        const mesaj = mesajInput?.value?.trim() || '';

        if (!konu) {
            showToast('Önizleme için önce konu yazın.', 'error');
            konuInput?.focus();
            return;
        }

        if (!mesaj) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            mesajInput?.focus();
            return;
        }

        const alicilar = filteredAlicilar();
        if (!alicilar.length) {
            showToast('Önizleme için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const ilkGonderilecek = alicilar.find((item) => item.email_var) || alicilar[0];
        const onizlemeModal = document.getElementById('eposta-onizleme-modal');
        const aliciEl = onizlemeModal?.querySelector('[data-eposta-onizleme-alici]');
        const kimeEl = onizlemeModal?.querySelector('[data-eposta-onizleme-kime]');
        const konuEl = onizlemeModal?.querySelector('[data-eposta-onizleme-konu]');
        const mesajEl = onizlemeModal?.querySelector('[data-eposta-onizleme-mesaj]');
        if (!onizlemeModal || !konuEl || !mesajEl) return;

        if (aliciEl) {
            const emailNotu = ilkGonderilecek.email_var
                ? ''
                : ' <span>(e-posta yok — gönderimde atlanır)</span>';
            aliciEl.innerHTML = `İlk gönderilecek: <strong>${escapeHtml(ilkGonderilecek.ad || 'İsimsiz')}</strong>${emailNotu}`;
        }
        if (kimeEl) {
            kimeEl.textContent = ilkGonderilecek.email || 'E-posta yok';
        }
        konuEl.textContent = personalizeEposta(konu, ilkGonderilecek.ad || 'Ad Soyad');
        mesajEl.textContent = personalizeEposta(mesaj, ilkGonderilecek.ad || 'Ad Soyad');
        openModal(onizlemeModal);
    }

    modal.querySelector('[data-eposta-onizle]')?.addEventListener('click', openEpostaOnizleme);

    const epostaOnizlemeModal = document.getElementById('eposta-onizleme-modal');
    if (epostaOnizlemeModal && epostaOnizlemeModal.dataset.bound !== '1') {
        epostaOnizlemeModal.dataset.bound = '1';
        epostaOnizlemeModal.querySelectorAll('[data-eposta-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(epostaOnizlemeModal));
        });
    }

    sendBtn?.addEventListener('click', async () => {
        const konu = konuInput?.value?.trim() || '';
        const mesaj = mesajInput?.value?.trim() || '';
        const ids = filteredAlicilar().map((item) => item.id);

        if (!konu) {
            showToast('E-posta konusu zorunludur.', 'error');
            konuInput?.focus();
            return;
        }

        if (!mesaj) {
            showToast('E-posta metni zorunludur.', 'error');
            mesajInput?.focus();
            return;
        }

        if (!ids.length) {
            showToast('E-posta göndermek için en az bir alıcı olmalıdır.', 'error');
            return;
        }

        const url = sendBtn.dataset.epostaUrl;
        if (!url || loading) return;

        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('konu', konu);
            formData.append('mesaj', mesaj);
            formData.append('basvuru_durum', currentFilter);
            ids.forEach((id) => formData.append('basvuru_ids[]', id));

            const { data } = await window.axios.post(url, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            setModalOpen(false);
            showToast(data.message || 'E-posta gönderildi.', 'success');

            const pageUrl = new URL(window.location.href);
            pageUrl.searchParams.set('tab', 'mesajlar');
            pageUrl.searchParams.set('kanal', 'eposta');
            setTimeout(() => {
                window.location.href = pageUrl.pathname + pageUrl.search;
            }, 700);
        } catch (error) {
            showToast(errorMessage(error), 'error');
        } finally {
            sendBtn.disabled = false;
        }
    });
}

export function initEpostaAlicilarDetayModal() {
    const modal = document.getElementById('eposta-alicilar-detay-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const listEl = modal.querySelector('[data-eposta-alicilar-detay-list]');

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function parseDetay(raw) {
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function renderDetay(items) {
        if (!listEl) return;
        if (!items.length) {
            listEl.innerHTML = '<p class="sms-alicilar-detay-empty">Alıcı bilgisi bulunamadı.</p>';
            return;
        }

        listEl.innerHTML = items
            .map((item) => {
                const ad = escapeHtml(item?.ad || 'İsimsiz');
                const email = item?.email ? escapeHtml(item.email) : 'E-posta yok';
                const gonderildi = item?.durum === 'gonderildi';
                const statusClass = gonderildi ? 'status-aktif' : 'status-iptal';
                const statusText = gonderildi ? 'Gönderildi' : 'Atlandı';
                const hata = item?.hata && !gonderildi
                    ? `<div class="sms-alicilar-detay-hata">${escapeHtml(item.hata)}</div>`
                    : '';
                const konu = item?.konu
                    ? `<div class="sms-alicilar-detay-telefon"><strong>Konu:</strong> ${escapeHtml(item.konu)}</div>`
                    : '';
                const mesaj = item?.mesaj
                    ? `<div class="sms-alicilar-detay-telefon">${escapeHtml(item.mesaj)}</div>`
                    : '';

                return `
                    <div class="sms-alicilar-detay-item">
                        <div class="sms-alicilar-detay-info">
                            <div class="sms-alicilar-detay-ad">${ad}</div>
                            <div class="sms-alicilar-detay-telefon">${email}</div>
                            ${konu}
                            ${mesaj}
                            ${hata}
                        </div>
                        <span class="status ${statusClass}">${statusText}</span>
                    </div>
                `;
            })
            .join('');
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-eposta-alicilar-detay-ac]');
        if (!btn) return;
        renderDetay(parseDetay(btn.getAttribute('data-eposta-alicilar-detay')));
        openModal(modal);
    });

    modal.querySelectorAll('[data-eposta-alicilar-detay-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
}
