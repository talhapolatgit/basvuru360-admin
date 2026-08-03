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
        if (submitBtn) submitBtn.disabled = loading;
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

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting) return;

        clearAllErrors();
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
        if (submitBtn) submitBtn.disabled = loading;
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
        if (submitting) return;

        if (kodError) kodError.textContent = '';
        kodInput?.classList.remove('is-invalid');
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
