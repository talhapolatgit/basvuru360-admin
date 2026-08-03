<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Giriş — {{ config('app.name', 'Başvuru 360') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body font-sans antialiased">
    <div class="login-shell">
        {{-- Sol marka paneli --}}
        <div class="login-brand-panel">
            <div class="login-brand-glow login-brand-glow-1"></div>
            <div class="login-brand-glow login-brand-glow-2"></div>

            <div class="login-brand-inner">
                <div class="login-brand-mark">
                    <x-brand-logo :size="22" class="login-brand-logo" />
                    <span>Başvuru 360</span>
                </div>

                <h1 class="login-brand-title">Kurs ve başvuru<br>yönetimini tek panelden yönetin.</h1>
                <p class="login-brand-desc">
                    Kurslar, kayıtlar, eğitmenler ve merkezler — tüm operasyonunuz güvenli ve tek bir yönetim panelinde.
                </p>

                <ul class="login-brand-list">
                    <li>
                        <span class="login-brand-list-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        Kurs, başvuru ve yoklama takibi
                    </li>
                    <li>
                        <span class="login-brand-list-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        SMS ve e-posta ile toplu iletişim
                    </li>
                    <li>
                        <span class="login-brand-list-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        Anlık istatistikler ve raporlama
                    </li>
                </ul>
            </div>

            <p class="login-brand-footer">&copy; {{ now()->year }} Başvuru 360 · Tüm hakları saklıdır.</p>
        </div>

        {{-- Sağ form paneli --}}
        <div class="login-form-panel">
            <div class="login-form-wrap">
                <div class="login-form-mobile-brand">
                    <div class="login-brand-mark login-brand-mark-dark">
                        <x-brand-logo :size="22" class="login-brand-logo" />
                        <span>Başvuru 360</span>
                    </div>
                </div>

                <div class="login-form-head">
                    <h2 class="login-form-title">Tekrar hoş geldiniz</h2>
                    <p class="login-form-subtitle">Devam etmek için hesap bilgilerinizle giriş yapın.</p>
                </div>

                <div class="login-alert" id="loginAlert" data-login-alert hidden>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span data-login-alert-text></span>
                </div>

                <form method="POST" action="{{ route('login') }}" class="login-form" id="loginForm" novalidate>
                    @csrf

                    <div class="login-field">
                        <label for="email" class="login-label">E-posta</label>
                        <div class="login-input-group">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="ornek@basvuru360.com"
                                class="login-input"
                                data-login-field="email"
                            >
                        </div>
                        <p class="login-error" data-login-error="email">{{ $errors->first('email') }}</p>
                    </div>

                    <div class="login-field">
                        <div class="login-label-row">
                            <label for="password" class="login-label">Şifre</label>
                        </div>
                        <div class="login-input-group">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="login-input"
                                data-login-field="password"
                            >
                            <button type="button" class="login-input-toggle" data-login-toggle-password title="Şifreyi göster/gizle" aria-label="Şifreyi göster/gizle">
                                <svg data-login-eye-open xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg data-login-eye-closed hidden xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                            </button>
                        </div>
                        <p class="login-error" data-login-error="password"></p>
                    </div>

                    <div class="login-field-inline">
                        <label class="switch-label" for="remember">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Beni hatırla</span>
                        </label>
                    </div>

                    <button type="submit" class="login-submit" id="loginSubmit">
                        <span class="login-submit-text">Giriş Yap</span>
                        <svg class="login-submit-spinner" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-9-9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
