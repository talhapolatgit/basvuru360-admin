<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Doğrulama — {{ config('app.name', 'Başvuru 360') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body font-sans antialiased">
    <div class="login-shell">
        <div class="login-brand-panel">
            <div class="login-brand-glow login-brand-glow-1"></div>
            <div class="login-brand-glow login-brand-glow-2"></div>

            <div class="login-brand-inner">
                <div class="login-brand-mark">
                    <x-brand-logo :size="22" class="login-brand-logo" />
                    <span>Başvuru 360</span>
                </div>

                <h1 class="login-brand-title">İki aşamalı<br>güvenlik doğrulaması.</h1>
                <p class="login-brand-desc">
                    Hesabınızı korumak için size gönderilen doğrulama kodunu girin.
                </p>
            </div>

            <p class="login-brand-footer">&copy; {{ now()->year }} Başvuru 360 · Tüm hakları saklıdır.</p>
        </div>

        <div class="login-form-panel">
            <div class="login-form-wrap">
                <div class="login-form-mobile-brand">
                    <div class="login-brand-mark login-brand-mark-dark">
                        <x-brand-logo :size="22" class="login-brand-logo" />
                        <span>Başvuru 360</span>
                    </div>
                </div>

                <div class="login-form-head">
                    <h2 class="login-form-title">Doğrulama kodu</h2>
                    <p class="login-form-subtitle" data-otp-subtitle>
                        @if ($kanal === 'sms')
                            Kod SMS ile <strong>{{ $hedef }}</strong> numarasına gönderildi.
                        @else
                            Kod e-posta ile <strong>{{ $hedef }}</strong> adresine gönderildi.
                        @endif
                    </p>
                </div>

                @if (session('success'))
                    <div class="login-alert" data-login-alert style="display:flex;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                        <span data-login-alert-text>{{ session('success') }}</span>
                    </div>
                @else
                    <div class="login-alert" id="loginAlert" data-login-alert hidden>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span data-login-alert-text></span>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('login.dogrulama.submit') }}"
                    class="login-form"
                    id="otpForm"
                    data-otp-form
                    data-resend-url="{{ route('login.dogrulama.yenile') }}"
                    data-resend-wait="{{ (int) ($yenidenGonderimKalan ?? 0) }}"
                    novalidate
                >
                    @csrf

                    <div class="login-field">
                        <label for="kod" class="login-label">Doğrulama kodu</label>
                        <div class="login-input-group">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input
                                type="text"
                                id="kod"
                                name="kod"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="12"
                                required
                                autofocus
                                placeholder="6 haneli kod"
                                class="login-input @error('kod') is-invalid @enderror"
                                data-otp-field="kod"
                            >
                        </div>
                        <p class="login-error" data-otp-error="kod">{{ $errors->first('kod') }}</p>
                    </div>

                    <button type="submit" class="login-submit" id="otpSubmit" data-otp-submit>
                        <span class="login-submit-text">Doğrula</span>
                        <svg class="login-submit-spinner" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-9-9"/></svg>
                    </button>

                    <div class="login-field-inline" style="justify-content:space-between; margin-top:16px;">
                        <button type="button" class="btn btn-link" data-otp-resend style="padding:0; border:0; background:none; color:#3699ff; cursor:pointer; font-size:13px;">
                            <span data-otp-resend-label>Kodu tekrar gönder</span>
                        </button>
                        <a href="{{ route('login') }}" style="font-size:13px; color:#7e8299;">Girişe dön</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
