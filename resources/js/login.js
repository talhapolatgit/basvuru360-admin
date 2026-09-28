function retryAfterSaniye(error) {
    const header = Number.parseInt(String(error?.response?.headers?.['retry-after'] ?? ''), 10);
    if (header > 0) return header;

    const match = String(error?.response?.data?.message ?? '').match(/(\d+)\s*(saniye|dakika)/i);
    if (!match) return 60;

    return Number(match[1]) * (match[2].toLowerCase() === 'dakika' ? 60 : 1);
}

/**
 * 429 sonrası gönder butonunu bekleme süresi boyunca kapatır ve üzerinde geri sayım gösterir.
 * Bitiş zamanı sessionStorage'da tutulur; sayfa yenilense de geri sayım sürer.
 */
function createSubmitCooldown(button, storageKey) {
    const label = button?.querySelector('.login-submit-text');
    const defaultText = label?.textContent ?? '';
    let timer = null;
    let untilMs = 0;

    function kalanSaniye() {
        return Math.max(0, Math.ceil((untilMs - Date.now()) / 1000));
    }

    function format(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m}:${String(s).padStart(2, '0')}`;
    }

    function stop() {
        if (timer) window.clearInterval(timer);
        timer = null;
        untilMs = 0;
        sessionStorage.removeItem(storageKey);
        button?.classList.remove('is-cooldown');
        if (button) button.disabled = false;
        if (label) label.textContent = defaultText;
    }

    function tick() {
        const kalan = kalanSaniye();
        if (kalan <= 0) {
            stop();
            return;
        }
        if (label) label.textContent = `Tekrar deneyin (${format(kalan)})`;
    }

    function startUntil(ms) {
        untilMs = ms;
        sessionStorage.setItem(storageKey, String(ms));
        button?.classList.add('is-cooldown');
        if (button) button.disabled = true;
        if (timer) window.clearInterval(timer);
        tick();
        timer = window.setInterval(tick, 1000);
    }

    const kayitli = Number(sessionStorage.getItem(storageKey) || 0);
    if (kayitli > Date.now()) {
        startUntil(kayitli);
    } else {
        sessionStorage.removeItem(storageKey);
    }

    return {
        start: (seconds) => startUntil(Date.now() + Math.max(1, seconds) * 1000),
        active: () => kalanSaniye() > 0,
    };
}

/**
 * Giriş sayfası — sayfa yenilenmeden (AJAX) kimlik doğrulama.
 */
export function initLoginPage() {
    const form = document.getElementById('loginForm');
    const otpForm = document.querySelector('[data-otp-form]');

    if (otpForm) {
        initOtpPage(otpForm);
        return;
    }

    if (!form) return;

    const submitBtn = document.getElementById('loginSubmit');
    const alertBox = document.getElementById('loginAlert');
    const alertText = alertBox?.querySelector('[data-login-alert-text]');
    const wrap = form.closest('.login-form-wrap');

    const fields = {
        email: form.querySelector('[data-login-field="email"]'),
        password: form.querySelector('[data-login-field="password"]'),
    };

    const errorEls = {
        email: form.querySelector('[data-login-error="email"]'),
        password: form.querySelector('[data-login-error="password"]'),
    };

    let submitting = false;
    const cooldown = createSubmitCooldown(submitBtn, 'admin_login_cooldown_until');

    function hideAlert() {
        if (!alertBox) return;
        alertBox.hidden = true;
    }

    function showAlert(message) {
        if (!alertBox || !alertText) return;
        alertText.textContent = message;
        alertBox.hidden = false;
    }

    function clearFieldError(key) {
        fields[key]?.classList.remove('is-invalid');
        if (errorEls[key]) errorEls[key].textContent = '';
    }

    function clearAllErrors() {
        hideAlert();
        Object.keys(fields).forEach(clearFieldError);
        wrap?.classList.remove('is-shaking');
    }

    function setFieldError(key, message) {
        fields[key]?.classList.add('is-invalid');
        if (errorEls[key]) errorEls[key].textContent = message;
    }

    function triggerShake() {
        if (!wrap) return;
        wrap.classList.remove('is-shaking');
        // eslint-disable-next-line no-unused-expressions
        void wrap.offsetWidth;
        wrap.classList.add('is-shaking');
    }

    function setLoading(loading) {
        submitting = loading;
        submitBtn?.classList.toggle('is-loading', loading);
        if (submitBtn) submitBtn.disabled = loading || cooldown.active();
    }

    Object.entries(fields).forEach(([key, input]) => {
        input?.addEventListener('input', () => clearFieldError(key));
    });

    form.querySelector('[data-login-toggle-password]')?.addEventListener('click', () => {
        const input = fields.password;
        if (!input) return;
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        form.querySelector('[data-login-eye-open]')?.toggleAttribute('hidden', !showing);
        form.querySelector('[data-login-eye-closed]')?.toggleAttribute('hidden', showing);
    });

    function validate() {
        const errors = {};
        const email = (fields.email?.value ?? '').trim();

        if (email === '') {
            errors.email = 'E-posta adresi zorunludur.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Geçerli bir e-posta adresi girin.';
        }

        if ((fields.password?.value ?? '') === '') {
            errors.password = 'Şifre zorunludur.';
        }

        return errors;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting || cooldown.active()) return;

        clearAllErrors();

        const clientErrors = validate();
        const errorKeys = Object.keys(clientErrors);
        if (errorKeys.length > 0) {
            errorKeys.forEach((key) => setFieldError(key, clientErrors[key]));
            showAlert(clientErrors[errorKeys[0]]);
            triggerShake();
            fields[errorKeys[0]]?.focus();
            return;
        }

        setLoading(true);

        try {
            const { data } = await window.axios.post(form.action, {
                email: fields.email?.value ?? '',
                password: fields.password?.value ?? '',
                remember: form.querySelector('#remember')?.checked ?? false,
            }, {
                headers: { Accept: 'application/json' },
            });

            if (data?.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.reload();
            }
        } catch (error) {
            setLoading(false);

            const status = error?.response?.status;
            const responseData = error?.response?.data;

            if (status === 422 && responseData?.errors) {
                const errors = responseData.errors;
                Object.entries(errors).forEach(([key, messages]) => {
                    if (fields[key]) {
                        setFieldError(key, messages[0]);
                    }
                });

                if (errors.email) {
                    showAlert(errors.email[0]);
                } else if (errors.password) {
                    showAlert(errors.password[0]);
                }

                triggerShake();
                fields.password?.focus();
                return;
            }

            if (status === 429) {
                showAlert(responseData?.message || 'Çok fazla deneme yapıldı. Lütfen biraz bekleyin.');
                cooldown.start(retryAfterSaniye(error));
                triggerShake();
                return;
            }

            showAlert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
            triggerShake();
        }
    });
}

function initOtpPage(form) {
    const submitBtn = form.querySelector('[data-otp-submit]');
    const alertBox = document.querySelector('[data-login-alert]');
    const alertText = alertBox?.querySelector('[data-login-alert-text]');
    const wrap = form.closest('.login-form-wrap');
    const kodInput = form.querySelector('[data-otp-field="kod"]');
    const kodError = form.querySelector('[data-otp-error="kod"]');
    const resendBtn = form.querySelector('[data-otp-resend]');
    const resendLabel = form.querySelector('[data-otp-resend-label]') || resendBtn;
    const resendUrl = form.dataset.resendUrl || '';
    let submitting = false;
    let resending = false;
    let cooldownTimer = null;
    let cooldownLeft = Number.parseInt(String(form.dataset.resendWait || '0'), 10) || 0;
    const submitCooldown = createSubmitCooldown(submitBtn, 'admin_otp_cooldown_until');

    function showAlert(message, isError = true) {
        if (!alertBox || !alertText) return;
        alertText.textContent = message;
        alertBox.hidden = false;
        alertBox.style.display = 'flex';
        alertBox.classList.toggle('is-success', !isError);
    }

    function setLoading(loading) {
        submitting = loading;
        submitBtn?.classList.toggle('is-loading', loading);
        if (submitBtn) submitBtn.disabled = loading || submitCooldown.active();
    }

    function triggerShake() {
        if (!wrap) return;
        wrap.classList.remove('is-shaking');
        // eslint-disable-next-line no-unused-expressions
        void wrap.offsetWidth;
        wrap.classList.add('is-shaking');
    }

    function cooldownLabel(seconds) {
        const total = Math.max(0, Number(seconds) || 0);
        const m = Math.floor(total / 60);
        const s = total % 60;
        if (m > 0) {
            return `${m}:${String(s).padStart(2, '0')}`;
        }
        return `${s} sn`;
    }

    function syncResendButton() {
        if (!resendBtn || !resendLabel) return;

        if (cooldownLeft > 0) {
            resendBtn.disabled = true;
            resendLabel.textContent = `Tekrar gönder (${cooldownLabel(cooldownLeft)})`;
            return;
        }

        resendBtn.disabled = Boolean(resending || submitting);
        resendLabel.textContent = 'Kodu tekrar gönder';
    }

    function startCooldown(seconds) {
        cooldownLeft = Math.max(0, Number(seconds) || 0);
        if (cooldownTimer) {
            window.clearInterval(cooldownTimer);
            cooldownTimer = null;
        }

        syncResendButton();
        if (cooldownLeft <= 0) return;

        cooldownTimer = window.setInterval(() => {
            cooldownLeft -= 1;
            if (cooldownLeft <= 0) {
                window.clearInterval(cooldownTimer);
                cooldownTimer = null;
                cooldownLeft = 0;
            }
            syncResendButton();
        }, 1000);
    }

    startCooldown(cooldownLeft);

    kodInput?.addEventListener('input', () => {
        kodInput.classList.remove('is-invalid');
        if (kodError) kodError.textContent = '';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting || submitCooldown.active()) return;

        if (kodError) kodError.textContent = '';
        kodInput?.classList.remove('is-invalid');

        const kodRakamlar = (kodInput?.value ?? '').replace(/\D+/g, '');
        const kodHatasi = kodRakamlar === ''
            ? 'Doğrulama kodu zorunludur.'
            : (kodRakamlar.length !== 6 ? 'Doğrulama kodu 6 haneli olmalıdır.' : null);
        if (kodHatasi) {
            if (kodError) kodError.textContent = kodHatasi;
            kodInput?.classList.add('is-invalid');
            showAlert(kodHatasi);
            triggerShake();
            kodInput?.focus();
            return;
        }

        setLoading(true);
        syncResendButton();

        try {
            const { data } = await window.axios.post(form.action, {
                kod: kodInput?.value ?? '',
            }, {
                headers: { Accept: 'application/json' },
            });

            if (data?.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = '/anasayfa';
            }
        } catch (error) {
            setLoading(false);
            syncResendButton();
            if (error?.response?.status === 429) {
                submitCooldown.start(retryAfterSaniye(error));
            }
            const errors = error?.response?.data?.errors;
            const message = errors?.kod?.[0]
                || error?.response?.data?.message
                || 'Doğrulama başarısız. Lütfen tekrar deneyin.';

            if (kodError) kodError.textContent = message;
            kodInput?.classList.add('is-invalid');
            showAlert(message);
            triggerShake();
            kodInput?.focus();
        }
    });

    resendBtn?.addEventListener('click', async () => {
        if (!resendUrl || resending || submitting || cooldownLeft > 0) return;
        resending = true;
        syncResendButton();

        try {
            const { data } = await window.axios.post(resendUrl, {}, {
                headers: { Accept: 'application/json' },
            });
            showAlert(data?.message || 'Yeni kod gönderildi.', false);
            startCooldown(data?.yeniden_gonderim_saniye ?? 120);
        } catch (error) {
            const errors = error?.response?.data?.errors;
            const message = errors?.kod?.[0]
                || errors?.email?.[0]
                || error?.response?.data?.message
                || 'Kod gönderilemedi.';
            showAlert(message);

            const match = String(message).match(/(\d+)\s*dk\s*(\d+)\s*sn|(\d+)\s*sn/i);
            if (match) {
                if (match[1] != null) {
                    startCooldown((Number(match[1]) * 60) + Number(match[2] || 0));
                } else if (match[3] != null) {
                    startCooldown(Number(match[3]));
                }
            }
        } finally {
            resending = false;
            syncResendButton();
        }
    });
}
