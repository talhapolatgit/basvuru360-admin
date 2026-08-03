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

const ORNEK_AD = 'Ahmet Yılmaz';

export function initMerkezDetailPage() {
    const smsModal = document.getElementById('merkez-sms-modal');
    const epostaModal = document.getElementById('merkez-eposta-modal');

    if (!smsModal && !epostaModal) {
        return;
    }

    initSmsModal(smsModal);
    initEpostaModal(epostaModal);
}

function initSmsModal(modal) {
    if (!modal) return;

    const sendUrl = modal.dataset.sendUrl || '';
    const ogrenciSayisi = Number(modal.dataset.ogrenciSayisi || '0');
    const aliciEl = modal.querySelector('[data-merkez-sms-alici]');
    const mesajInput = modal.querySelector('[data-merkez-sms-mesaj]');
    const charCount = modal.querySelector('[data-merkez-sms-char-count]');
    const sendBtn = modal.querySelector('[data-merkez-sms-send]');
    let sending = false;

    document.querySelectorAll('[data-merkez-sms-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (aliciEl) {
                aliciEl.innerHTML = `Merkezin aktif kurslarındaki <strong>${ogrenciSayisi}</strong> kesin kayıtlı öğrenciye SMS gönderilecek.`;
            }
            if (mesajInput) mesajInput.value = '';
            if (charCount) charCount.textContent = '0';
            openModal(modal);
            mesajInput?.focus();
        });
    });

    modal.querySelectorAll('[data-merkez-sms-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) charCount.textContent = String(mesajInput.value.length);
    });

    modal.querySelector('[data-merkez-sms-insert]')?.addEventListener('click', (event) => {
        const token = event.currentTarget.getAttribute('data-merkez-sms-insert') || '';
        insertToken(mesajInput, token);
    });

    modal.querySelector('[data-merkez-sms-onizle]')?.addEventListener('click', () => {
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
            alici.innerHTML = `Örnek alıcı: <strong>${escapeHtml(ORNEK_AD)}</strong>`;
        }
        bubble.textContent = personalize(mesaj, ORNEK_AD);
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

    const sendUrl = modal.dataset.sendUrl || '';
    const ogrenciSayisi = Number(modal.dataset.ogrenciSayisi || '0');
    const aliciEl = modal.querySelector('[data-merkez-eposta-alici]');
    const konuInput = modal.querySelector('[data-merkez-eposta-konu]');
    const mesajInput = modal.querySelector('[data-merkez-eposta-mesaj]');
    const charCount = modal.querySelector('[data-merkez-eposta-char-count]');
    const sendBtn = modal.querySelector('[data-merkez-eposta-send]');
    let sending = false;

    document.querySelectorAll('[data-merkez-eposta-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (aliciEl) {
                aliciEl.innerHTML = `Merkezin aktif kurslarındaki <strong>${ogrenciSayisi}</strong> kesin kayıtlı öğrenciye e-posta gönderilecek.`;
            }
            if (konuInput) konuInput.value = '';
            if (mesajInput) mesajInput.value = '';
            if (charCount) charCount.textContent = '0';
            openModal(modal);
            konuInput?.focus();
        });
    });

    modal.querySelectorAll('[data-merkez-eposta-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    mesajInput?.addEventListener('input', () => {
        if (charCount) charCount.textContent = String(mesajInput.value.length);
    });

    modal.querySelectorAll('[data-merkez-eposta-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const token = btn.getAttribute('data-merkez-eposta-insert') || '';
            const target = btn.getAttribute('data-merkez-eposta-insert-target') || 'mesaj';
            const input = target === 'konu' ? konuInput : mesajInput;
            insertToken(input, token);
        });
    });

    modal.querySelector('[data-merkez-eposta-onizle]')?.addEventListener('click', () => {
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
            alici.innerHTML = `Örnek alıcı: <strong>${escapeHtml(ORNEK_AD)}</strong>`;
        }
        if (kime) kime.textContent = 'ornek@ogrenci.com';
        konuEl.textContent = personalize(konu, ORNEK_AD);
        mesajEl.textContent = personalize(mesaj, ORNEK_AD);
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
