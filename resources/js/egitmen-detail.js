import { showToast } from './toast';
import { initDetailTable } from './detail-table';

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

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function personalize(template, adSoyad) {
    return String(template || '').replaceAll('{ad_soyad}', adSoyad || '');
}

function insertToken(input, token) {
    if (!input || !token) return;
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    const before = input.value.slice(0, start);
    const after = input.value.slice(end);
    input.value = before + token + after;
    const cursor = start + token.length;
    input.focus();
    input.setSelectionRange(cursor, cursor);
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function validationMessage(error) {
    const data = error?.response?.data;
    if (!data) {
        return 'İşlem sırasında bir hata oluştu.';
    }
    if (typeof data.message === 'string' && data.message && !data.errors) {
        return data.message;
    }
    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    return 'İşlem sırasında bir hata oluştu.';
}

export function initEgitmenDetailPage() {
    initEgitmenTabs();
    initKisiBasvuruTuruTabs();
    initKisiAileModal();

    const smsModal = document.getElementById('egitmen-sms-modal');
    const epostaModal = document.getElementById('egitmen-eposta-modal');
    const sifreModal = document.getElementById('egitmen-sifre-modal');

    if (!smsModal && !epostaModal && !sifreModal) {
        return;
    }

    initSmsModal(smsModal);
    initEpostaModal(epostaModal);
    initSifreModal(sifreModal);
}

function initKisiAileModal() {
    const modal = document.getElementById('kisi-aile-modal');
    const panel = document.querySelector('[data-egitmen-panel="aile"]');
    if (!modal || !panel) return;

    const araUrl = panel.dataset.kisiAraUrl || '';

    document.querySelectorAll('[data-kisi-aile-open]').forEach((btn) => {
        btn.addEventListener('click', () => openModal(modal));
    });

    modal.querySelectorAll('[data-kisi-aile-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    modal.querySelectorAll('[data-kisi-search]').forEach((input) => {
        wireKisiSearch(input, araUrl);
    });

    const form = document.getElementById('kisi-aile-form');
    form?.addEventListener('submit', (e) => {
        const hidden = document.getElementById('aile_yakin_kisi_id');
        if (!hidden?.value) {
            e.preventDefault();
            showToast('Yakın kişi seçmelisiniz.', 'error');
        }
    });

    if (modal.dataset.openOnLoad === '1') {
        openModal(modal);
    }
}

function wireKisiSearch(input, araUrl) {
    const targetId = input.dataset.kisiTarget;
    const labelKey = input.dataset.kisiLabel;
    const wrap = input.closest('.form-group');
    const hidden = document.getElementById(targetId);
    const picked = wrap?.querySelector(`[data-kisi-picked="${labelKey}"]`);
    const results = wrap?.querySelector('[data-kisi-results]');
    if (!hidden || !results) return;

    let timer = null;
    const clearPick = () => {
        hidden.value = '';
        if (picked) picked.textContent = 'Kişi seçilmedi';
    };

    input.addEventListener('input', () => {
        clearPick();
        const q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 2) {
            results.hidden = true;
            results.innerHTML = '';
            return;
        }
        timer = setTimeout(async () => {
            try {
                const res = await fetch(`${araUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                results.innerHTML = '';
                (data.items || []).forEach((item) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'kres-kisi-result';
                    btn.textContent = item.label;
                    btn.addEventListener('click', () => {
                        hidden.value = item.id;
                        if (picked) picked.textContent = item.label;
                        input.value = item.tam_adi;
                        results.hidden = true;
                    });
                    results.appendChild(btn);
                });
                results.hidden = !(data.items || []).length;
            } catch {
                results.hidden = true;
            }
        }, 250);
    });
}

function initKisiBasvuruTuruTabs() {
    const panelRoot = document.querySelector('[data-egitmen-panel="basvurular"][data-kisi-basvurular-url]');
    const root = panelRoot?.querySelector('[data-kisi-basvuru-tur-tabs]');
    if (!panelRoot || !root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';

    const url = panelRoot.dataset.kisiBasvurularUrl || '';
    let loading = false;
    let requestId = 0;

    function updateCounts(counts = {}) {
        const kursEl = root.querySelector('[data-kisi-basvuru-count="kurs"]');
        const etkinlikEl = root.querySelector('[data-kisi-basvuru-count="etkinlik"]');
        if (kursEl && counts.kurs !== undefined) {
            kursEl.textContent = Number(counts.kurs).toLocaleString('tr-TR');
        }
        if (etkinlikEl && counts.etkinlik !== undefined) {
            etkinlikEl.textContent = Number(counts.etkinlik).toLocaleString('tr-TR');
        }
    }

    async function loadTur(tur, { force = false } = {}) {
        const next = tur === 'etkinlik' ? 'etkinlik' : 'kurs';
        const panel = panelRoot.querySelector(`[data-kisi-basvuru-panel="${next}"]`);
        const content = panel?.querySelector('[data-kisi-basvuru-content]');
        if (!url || !panel || !content) return;
        if (loading && !force) return;

        const thisRequest = ++requestId;
        loading = true;
        content.classList.add('is-loading');

        try {
            const { data } = await window.axios.get(url, {
                params: { tur: next },
                headers: { Accept: 'application/json' },
            });

            if (thisRequest !== requestId) return;

            content.innerHTML = data.html || '';
            updateCounts(data.counts || {});
            content.querySelectorAll('[data-detail-table]').forEach((table) => {
                initDetailTable(table);
            });
        } catch (error) {
            if (thisRequest !== requestId) return;
            showToast(validationMessage(error), 'error');
        } finally {
            if (thisRequest === requestId) {
                loading = false;
                content.classList.remove('is-loading');
            }
        }
    }

    function setTur(tur, { reload = true } = {}) {
        const next = tur === 'etkinlik' ? 'etkinlik' : 'kurs';
        root.querySelectorAll('[data-kisi-basvuru-tur]').forEach((tab) => {
            const active = tab.getAttribute('data-kisi-basvuru-tur') === next;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panelRoot.querySelectorAll('[data-kisi-basvuru-panel]').forEach((panel) => {
            panel.hidden = panel.getAttribute('data-kisi-basvuru-panel') !== next;
        });

        const pageUrl = new URL(window.location.href);
        pageUrl.searchParams.set('tab', 'basvurular');
        pageUrl.searchParams.set('basvuru_tur', next);
        history.replaceState({}, '', pageUrl.pathname + pageUrl.search);

        if (reload) {
            loadTur(next, { force: true });
        }
    }

    root.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-kisi-basvuru-tur]');
        if (!tab || !root.contains(tab)) return;
        setTur(tab.getAttribute('data-kisi-basvuru-tur') || 'kurs', { reload: true });
    });

    const initial = new URL(window.location.href).searchParams.get('basvuru_tur');
    if (initial === 'etkinlik' || initial === 'kurs') {
        setTur(initial, { reload: false });
    }
}

function initEgitmenTabs() {
    const tabBar = document.querySelector('[data-egitmen-tabs]');
    if (!tabBar) return;

    const tabs = document.querySelectorAll('[data-egitmen-tab]');
    const panels = document.querySelectorAll('[data-egitmen-panel]');
    const valid = [...tabs].map((t) => t.getAttribute('data-egitmen-tab'));

    function setTab(name) {
        if (!valid.includes(name)) name = valid[0];

        tabs.forEach((tab) => {
            const active = tab.getAttribute('data-egitmen-tab') === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.getAttribute('data-egitmen-panel') === name);
        });

        const url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        history.replaceState({}, '', url.pathname + url.search);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => setTab(tab.getAttribute('data-egitmen-tab')));
    });

    const initial = new URL(window.location.href).searchParams.get('tab');
    if (initial && valid.includes(initial)) {
        setTab(initial);
    }
}

function initSmsModal(modal) {
    if (!modal) return;

    const ad = modal.dataset.ad || '';
    const telefonVar = modal.dataset.telefonVar === '1';
    const sendUrl = modal.dataset.sendUrl || '';
    const aliciEl = modal.querySelector('[data-egitmen-sms-alici]');
    const noTelefonEl = modal.querySelector('[data-egitmen-sms-no-telefon]');
    const mesajInput = modal.querySelector('[data-egitmen-sms-mesaj]');
    const charCount = modal.querySelector('[data-egitmen-sms-char-count]');
    const sendBtn = modal.querySelector('[data-egitmen-sms-send]');
    let sending = false;

    document.querySelectorAll('[data-egitmen-sms-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (aliciEl) {
                aliciEl.innerHTML = `<strong>${escapeHtml(ad || 'Bu eğitmen')}</strong> için SMS gönderilecek.`;
            }
            if (noTelefonEl) noTelefonEl.hidden = telefonVar;
            if (mesajInput) mesajInput.value = '';
            if (charCount) charCount.textContent = '0';
            openModal(modal);
            mesajInput?.focus();
        });
    });

    modal.querySelectorAll('[data-egitmen-sms-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) charCount.textContent = String(mesajInput.value.length);
    });

    modal.querySelector('[data-egitmen-sms-insert]')?.addEventListener('click', (event) => {
        const token = event.currentTarget.getAttribute('data-egitmen-sms-insert') || '';
        insertToken(mesajInput, token);
    });

    modal.querySelector('[data-egitmen-sms-onizle]')?.addEventListener('click', () => {
        const mesaj = mesajInput?.value?.trim() || '';
        if (!mesaj) {
            showToast('Önizleme için önce mesaj yazın.', 'error');
            mesajInput?.focus();
            return;
        }

        const onizlemeModal = document.getElementById('sms-onizleme-modal');
        const alici = onizlemeModal?.querySelector('[data-sms-onizleme-alici]');
        const bubble = onizlemeModal?.querySelector('[data-sms-onizleme-mesaj]');
        if (!onizlemeModal || !bubble) return;

        if (alici) {
            alici.innerHTML = `<strong>${escapeHtml(ad || 'İsimsiz')}</strong>`;
        }
        bubble.textContent = personalize(mesaj, ad || 'Ad Soyad');
        openModal(onizlemeModal);
    });

    const onizlemeModal = document.getElementById('sms-onizleme-modal');
    if (onizlemeModal && onizlemeModal.dataset.bound !== '1') {
        onizlemeModal.dataset.bound = '1';
        onizlemeModal.querySelectorAll('[data-sms-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(onizlemeModal));
        });
    }

    sendBtn?.addEventListener('click', async () => {
        const mesaj = mesajInput?.value?.trim() || '';
        if (!mesaj) {
            showToast('SMS metni zorunludur.', 'error');
            mesajInput?.focus();
            return;
        }
        if (!telefonVar) {
            showToast('Bu eğitmen için kayıtlı telefon numarası bulunamadı.', 'error');
            return;
        }
        if (!sendUrl || sending) return;

        sending = true;
        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('mesaj', mesaj);
            const { data } = await window.axios.post(sendUrl, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            closeModal(modal);
            showToast(data.message || 'SMS gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            sending = false;
            sendBtn.disabled = false;
        }
    });
}

function initEpostaModal(modal) {
    if (!modal) return;

    const ad = modal.dataset.ad || '';
    const email = modal.dataset.email || '';
    const emailVar = modal.dataset.emailVar === '1';
    const sendUrl = modal.dataset.sendUrl || '';
    const aliciEl = modal.querySelector('[data-egitmen-eposta-alici]');
    const noEmailEl = modal.querySelector('[data-egitmen-eposta-no-email]');
    const konuInput = modal.querySelector('[data-egitmen-eposta-konu]');
    const mesajInput = modal.querySelector('[data-egitmen-eposta-mesaj]');
    const charCount = modal.querySelector('[data-egitmen-eposta-char-count]');
    const sendBtn = modal.querySelector('[data-egitmen-eposta-send]');
    let sending = false;

    document.querySelectorAll('[data-egitmen-eposta-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (aliciEl) {
                aliciEl.innerHTML = `<strong>${escapeHtml(ad || 'Bu eğitmen')}</strong> için e-posta gönderilecek.`;
            }
            if (noEmailEl) noEmailEl.hidden = emailVar;
            if (konuInput) konuInput.value = '';
            if (mesajInput) mesajInput.value = '';
            if (charCount) charCount.textContent = '0';
            openModal(modal);
            konuInput?.focus();
        });
    });

    modal.querySelectorAll('[data-egitmen-eposta-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) charCount.textContent = String(mesajInput.value.length);
    });

    modal.querySelectorAll('[data-egitmen-eposta-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const token = btn.getAttribute('data-egitmen-eposta-insert') || '';
            const target = btn.getAttribute('data-egitmen-eposta-insert-target') || 'mesaj';
            const input = target === 'konu' ? konuInput : mesajInput;
            insertToken(input, token);
        });
    });

    modal.querySelector('[data-egitmen-eposta-onizle]')?.addEventListener('click', () => {
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

        const onizlemeModal = document.getElementById('eposta-onizleme-modal');
        const alici = onizlemeModal?.querySelector('[data-eposta-onizleme-alici]');
        const kime = onizlemeModal?.querySelector('[data-eposta-onizleme-kime]');
        const konuEl = onizlemeModal?.querySelector('[data-eposta-onizleme-konu]');
        const mesajEl = onizlemeModal?.querySelector('[data-eposta-onizleme-mesaj]');
        if (!onizlemeModal || !konuEl || !mesajEl) return;

        if (alici) {
            alici.innerHTML = `Alıcı: <strong>${escapeHtml(ad || 'İsimsiz')}</strong>`;
        }
        if (kime) kime.textContent = email || 'E-posta yok';
        konuEl.textContent = personalize(konu, ad || 'Ad Soyad');
        mesajEl.textContent = personalize(mesaj, ad || 'Ad Soyad');
        openModal(onizlemeModal);
    });

    const onizlemeModal = document.getElementById('eposta-onizleme-modal');
    if (onizlemeModal && onizlemeModal.dataset.bound !== '1') {
        onizlemeModal.dataset.bound = '1';
        onizlemeModal.querySelectorAll('[data-eposta-onizleme-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(onizlemeModal));
        });
    }

    sendBtn?.addEventListener('click', async () => {
        const konu = konuInput?.value?.trim() || '';
        const mesaj = mesajInput?.value?.trim() || '';
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
        if (!emailVar) {
            showToast('Bu eğitmen için kayıtlı e-posta adresi bulunamadı.', 'error');
            return;
        }
        if (!sendUrl || sending) return;

        sending = true;
        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('konu', konu);
            formData.append('mesaj', mesaj);
            const { data } = await window.axios.post(sendUrl, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            closeModal(modal);
            showToast(data.message || 'E-posta gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            sending = false;
            sendBtn.disabled = false;
        }
    });
}

function initSifreModal(modal) {
    if (!modal) return;

    const ad = modal.dataset.ad || '';
    const sendUrl = modal.dataset.sendUrl || '';
    const adEl = modal.querySelector('[data-egitmen-sifre-ad]');
    const kanalSelect = modal.querySelector('[data-egitmen-sifre-kanal]');
    const sendBtn = modal.querySelector('[data-egitmen-sifre-send]');
    let sending = false;

    document.querySelectorAll('[data-egitmen-sifre-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (adEl) adEl.textContent = ad || 'Bu eğitmen';
            if (kanalSelect && kanalSelect.options.length) {
                kanalSelect.selectedIndex = 0;
            }
            openModal(modal);
        });
    });

    modal.querySelectorAll('[data-egitmen-sifre-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    sendBtn?.addEventListener('click', async () => {
        const kanal = kanalSelect?.value || '';
        if (!kanal) {
            showToast('Gönderim kanalı seçilmelidir.', 'error');
            return;
        }
        if (!sendUrl || sending) return;

        sending = true;
        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('kanal', kanal);
            const { data } = await window.axios.post(sendUrl, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            closeModal(modal);
            showToast(data.message || 'Şifre gönderildi.', 'success');
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            sending = false;
            sendBtn.disabled = false;
        }
    });
}
