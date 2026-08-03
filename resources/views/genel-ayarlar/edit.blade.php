@extends('layouts.admin')

@section('title', 'Genel Ayarlar')

@section('content')
@php
    $guncelleyebilir = auth()->user()?->hasYetki('genel_ayar.guncelle');
    $form = $form ?? [];
@endphp

<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Sistem</p>
        <h1 class="page-title">Genel Ayarlar</h1>
        <p class="page-subtitle">Kurum bilgileri ve iletişim ayarlarını yönetin. Bu bilgiler vatandaş portalında kullanılacaktır.</p>
    </div>
</div>

<div class="card genel-ayarlar-card" data-genel-ayarlar>
    <form
        method="POST"
        action="{{ route('genel-ayarlar.update') }}"
        enctype="multipart/form-data"
        class="genel-ayarlar-form"
        data-genel-ayarlar-form
    >
        @csrf
        @method('PUT')

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Kurum Bilgileri</h3>
                <p class="sertifika-ayarlar-desc">Vatandaş portalında görünecek kurum adı, logo, favicon ve arama açıklaması.</p>
            </div>

            <div class="genel-ayarlar-layout">
                <div class="genel-ayarlar-fields">
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="genel-kurum-adi">Kurum adı</label>
                            <input
                                type="text"
                                id="genel-kurum-adi"
                                name="kurum_adi"
                                class="form-control @error('kurum_adi') is-invalid @enderror"
                                value="{{ old('kurum_adi', $form['kurum_adi'] ?? '') }}"
                                maxlength="200"
                                placeholder="Örn. Belediye Kültür Müdürlüğü"
                                @disabled(! $guncelleyebilir)
                            >
                            @error('kurum_adi')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="genel-web-sitesi">Web sitesi</label>
                            <input
                                type="text"
                                id="genel-web-sitesi"
                                name="web_sitesi"
                                class="form-control @error('web_sitesi') is-invalid @enderror"
                                value="{{ old('web_sitesi', $form['web_sitesi'] ?? '') }}"
                                maxlength="255"
                                placeholder="https://ornek.gov.tr"
                                @disabled(! $guncelleyebilir)
                            >
                            @error('web_sitesi')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="genel-site-aciklama">Google arama açıklaması</label>
                        <textarea
                            id="genel-site-aciklama"
                            name="site_aciklama"
                            class="form-control @error('site_aciklama') is-invalid @enderror"
                            rows="3"
                            maxlength="320"
                            placeholder="Google’da site arandığında görünecek kısa açıklama"
                            @disabled(! $guncelleyebilir)
                        >{{ old('site_aciklama', $form['site_aciklama'] ?? '') }}</textarea>
                        <p class="form-hint">Önerilen uzunluk 150–160 karakter. En fazla 320 karakter.</p>
                        @error('site_aciklama')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="genel-logo">Logo</label>
                        <input
                            type="file"
                            id="genel-logo"
                            name="logo"
                            class="form-control @error('logo') is-invalid @enderror"
                            accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                            data-genel-logo-input
                            @disabled(! $guncelleyebilir)
                        >
                        <p class="form-hint">PNG, JPG, SVG veya WEBP. En fazla 5 MB. Boş bırakırsanız mevcut logo korunur.</p>
                        @error('logo')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                        @if (! empty($form['logo']))
                            <label class="sertifika-reset-check">
                                <input
                                    type="checkbox"
                                    name="logo_kaldir"
                                    value="1"
                                    @checked(old('logo_kaldir'))
                                    @disabled(! $guncelleyebilir)
                                >
                                Yüklenen logoyu kaldır
                            </label>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="genel-favicon">Favicon</label>
                        <input
                            type="file"
                            id="genel-favicon"
                            name="favicon"
                            class="form-control @error('favicon') is-invalid @enderror"
                            accept=".ico,.png,.jpg,.jpeg,.svg,.webp,image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/svg+xml,image/webp"
                            data-genel-favicon-input
                            @disabled(! $guncelleyebilir)
                        >
                        <p class="form-hint">ICO, PNG, JPG, SVG veya WEBP. En fazla 2 MB. Önerilen ölçü: 32×32 veya 48×48 px.</p>
                        @error('favicon')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                        @if (! empty($form['favicon']))
                            <label class="sertifika-reset-check">
                                <input
                                    type="checkbox"
                                    name="favicon_kaldir"
                                    value="1"
                                    @checked(old('favicon_kaldir'))
                                    @disabled(! $guncelleyebilir)
                                >
                                Yüklenen favicon’u kaldır
                            </label>
                        @endif
                    </div>
                </div>

                <div class="genel-ayarlar-logo-preview">
                    <div class="genel-logo-frame">
                        @if (! empty($form['logo_url']))
                            <img
                                src="{{ $form['logo_url'] }}"
                                alt="Kurum logosu"
                                data-genel-logo-img
                            >
                        @else
                            <div class="genel-logo-empty" data-genel-logo-empty>Logo yok</div>
                            <img src="" alt="" hidden data-genel-logo-img>
                        @endif
                    </div>
                    <p class="sertifika-preview-caption" data-genel-logo-caption>
                        {{ $form['logo_adi'] ?? 'Önizleme' }}
                    </p>

                    <div class="genel-logo-frame genel-favicon-frame" style="margin-top:1rem;max-width:72px;max-height:72px;min-height:72px">
                        @if (! empty($form['favicon_url']))
                            <img
                                src="{{ $form['favicon_url'] }}"
                                alt="Favicon"
                                data-genel-favicon-img
                                style="max-width:48px;max-height:48px;object-fit:contain"
                            >
                        @else
                            <div class="genel-logo-empty" data-genel-favicon-empty>Favicon yok</div>
                            <img src="" alt="" hidden data-genel-favicon-img style="max-width:48px;max-height:48px;object-fit:contain">
                        @endif
                    </div>
                    <p class="sertifika-preview-caption" data-genel-favicon-caption>
                        {{ $form['favicon_adi'] ?? 'Favicon önizleme' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Portal Sidebar</h3>
                <p class="sertifika-ayarlar-desc">Vatandaş portalının sol menüsünde görünen logo, başlık ve alt başlık.</p>
            </div>

            <div class="genel-ayarlar-layout">
                <div class="genel-ayarlar-fields">
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="genel-sidebar-baslik">Sidebar başlık</label>
                            <input
                                type="text"
                                id="genel-sidebar-baslik"
                                name="sidebar_baslik"
                                class="form-control @error('sidebar_baslik') is-invalid @enderror"
                                value="{{ old('sidebar_baslik', $form['sidebar_baslik'] ?? '') }}"
                                maxlength="200"
                                placeholder="Portal sol menüde görünen başlık"
                                @disabled(! $guncelleyebilir)
                            >
                            <p class="form-hint">Boş bırakılırsa sidebar’da başlık gösterilmez. Kurum adından bağımsızdır.</p>
                            @error('sidebar_baslik')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="genel-sidebar-alt-baslik">Sidebar alt başlık</label>
                            <input
                                type="text"
                                id="genel-sidebar-alt-baslik"
                                name="sidebar_alt_baslik"
                                class="form-control @error('sidebar_alt_baslik') is-invalid @enderror"
                                value="{{ old('sidebar_alt_baslik', $form['sidebar_alt_baslik'] ?? '') }}"
                                maxlength="200"
                                placeholder="Örn. Beyoğlu Halk Eğitim"
                                @disabled(! $guncelleyebilir)
                            >
                            @error('sidebar_alt_baslik')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="genel-sidebar-logo">Sidebar logo</label>
                        <input
                            type="file"
                            id="genel-sidebar-logo"
                            name="sidebar_logo"
                            class="form-control @error('sidebar_logo') is-invalid @enderror"
                            accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                            data-genel-sidebar-logo-input
                            @disabled(! $guncelleyebilir)
                        >
                        <p class="form-hint">PNG, JPG, SVG veya WEBP. En fazla 5 MB. Portal sol menüsünde kullanılır.</p>
                        @error('sidebar_logo')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                        @if (! empty($form['sidebar_logo']))
                            <label class="sertifika-reset-check">
                                <input
                                    type="checkbox"
                                    name="sidebar_logo_kaldir"
                                    value="1"
                                    @checked(old('sidebar_logo_kaldir'))
                                    @disabled(! $guncelleyebilir)
                                >
                                Yüklenen sidebar logosunu kaldır
                            </label>
                        @endif
                    </div>

                    @php
                        $sidebarArkaplan = old(
                            'sidebar_logo_arkaplan',
                            $form['sidebar_logo_arkaplan'] ?? \App\Services\GenelAyarServisi::DEFAULT_SIDEBAR_LOGO_ARKAPLAN
                        );
                        $sidebarArkaplanSeffaf = old(
                            'sidebar_logo_arkaplan_seffaf',
                            ($form['sidebar_logo_arkaplan'] ?? '') === 'transparent'
                        );
                        $sidebarArkaplanColor = $sidebarArkaplan === 'transparent'
                            ? \App\Services\GenelAyarServisi::DEFAULT_SIDEBAR_LOGO_ARKAPLAN
                            : $sidebarArkaplan;
                        $sidebarBg = old(
                            'sidebar_arkaplan',
                            $form['sidebar_arkaplan'] ?? \App\Services\GenelAyarServisi::DEFAULT_SIDEBAR_ARKAPLAN
                        );
                        $sidebarBgTip = old(
                            'sidebar_arkaplan_tip',
                            $form['sidebar_arkaplan_tip'] ?? \App\Services\GenelAyarServisi::DEFAULT_SIDEBAR_ARKAPLAN_TIP
                        );
                    @endphp
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="genel-sidebar-arkaplan-tip">Sidebar arka plan tipi</label>
                            <select
                                id="genel-sidebar-arkaplan-tip"
                                name="sidebar_arkaplan_tip"
                                class="form-control @error('sidebar_arkaplan_tip') is-invalid @enderror"
                                @disabled(! $guncelleyebilir)
                                required
                            >
                                <option value="duz" @selected($sidebarBgTip === 'duz')>Düz renk</option>
                                <option value="gradient" @selected($sidebarBgTip === 'gradient')>Gradient</option>
                            </select>
                            @error('sidebar_arkaplan_tip')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="genel-sidebar-arkaplan">Sidebar arka plan rengi</label>
                            <div class="sertifika-color-pair">
                                <input
                                    type="color"
                                    id="genel-sidebar-arkaplan"
                                    name="sidebar_arkaplan"
                                    class="form-control form-control-color"
                                    value="{{ $sidebarBg }}"
                                    @disabled(! $guncelleyebilir)
                                >
                            </div>
                            <p class="form-hint">Gradient seçilirse bu renk ana ton olarak kullanılır.</p>
                            @error('sidebar_arkaplan')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="genel-sidebar-logo-arkaplan">Logo arka plan rengi</label>
                            <div class="sertifika-color-pair">
                                <input
                                    type="color"
                                    id="genel-sidebar-logo-arkaplan"
                                    name="sidebar_logo_arkaplan"
                                    class="form-control form-control-color"
                                    value="{{ $sidebarArkaplanColor }}"
                                    data-genel-sidebar-logo-arkaplan
                                    @disabled(! $guncelleyebilir || $sidebarArkaplanSeffaf)
                                >
                            </div>
                            <label class="sertifika-reset-check" style="margin-top:0.65rem;">
                                <input
                                    type="checkbox"
                                    name="sidebar_logo_arkaplan_seffaf"
                                    value="1"
                                    data-genel-sidebar-logo-arkaplan-seffaf
                                    @checked($sidebarArkaplanSeffaf)
                                    @disabled(! $guncelleyebilir)
                                >
                                Şeffaf logo arka planı (renk yok)
                            </label>
                            <p class="form-hint">Portal sidebar’daki logo kutusunun arka planı.</p>
                            @error('sidebar_logo_arkaplan')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="genel-ayarlar-logo-preview">
                    <div
                        class="genel-logo-frame"
                        data-genel-sidebar-logo-frame
                        style="background: {{ $sidebarArkaplanSeffaf ? 'transparent' : $sidebarArkaplanColor }};"
                    >
                        @if (! empty($form['sidebar_logo_url']))
                            <img
                                src="{{ $form['sidebar_logo_url'] }}"
                                alt="Sidebar logosu"
                                data-genel-sidebar-logo-img
                            >
                        @else
                            <div class="genel-logo-empty" data-genel-sidebar-logo-empty>Logo yok</div>
                            <img src="" alt="" hidden data-genel-sidebar-logo-img>
                        @endif
                    </div>
                    <p class="sertifika-preview-caption" data-genel-sidebar-logo-caption>
                        {{ $form['sidebar_logo_adi'] ?? 'Önizleme' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Portal Header</h3>
                <p class="sertifika-ayarlar-desc">Mobil üst barda görünen logo. Sidebar logosundan bağımsızdır.</p>
            </div>

            <div class="genel-ayarlar-layout">
                <div class="genel-ayarlar-fields">
                    <div class="form-group">
                        <label for="genel-header-logo">Header logo</label>
                        <input
                            type="file"
                            id="genel-header-logo"
                            name="header_logo"
                            class="form-control @error('header_logo') is-invalid @enderror"
                            accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                            data-genel-header-logo-input
                            @disabled(! $guncelleyebilir)
                        >
                        <p class="form-hint">PNG, JPG, SVG veya WEBP. En fazla 5 MB. Mobil üst barda kullanılır.</p>
                        @error('header_logo')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                        @if (! empty($form['header_logo']))
                            <label class="sertifika-reset-check">
                                <input
                                    type="checkbox"
                                    name="header_logo_kaldir"
                                    value="1"
                                    @checked(old('header_logo_kaldir'))
                                    @disabled(! $guncelleyebilir)
                                >
                                Yüklenen header logosunu kaldır
                            </label>
                        @endif
                    </div>
                </div>

                <div class="genel-ayarlar-logo-preview">
                    <div class="genel-logo-frame">
                        @if (! empty($form['header_logo_url']))
                            <img
                                src="{{ $form['header_logo_url'] }}"
                                alt="Header logosu"
                                data-genel-header-logo-img
                            >
                        @else
                            <div class="genel-logo-empty" data-genel-header-logo-empty>Logo yok</div>
                            <img src="" alt="" hidden data-genel-header-logo-img>
                        @endif
                    </div>
                    <p class="sertifika-preview-caption" data-genel-header-logo-caption>
                        {{ $form['header_logo_adi'] ?? 'Önizleme' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">İletişim</h3>
                <p class="sertifika-ayarlar-desc">Vatandaşların ulaşabileceği telefon ve e-posta bilgileri.</p>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="genel-telefon">Telefon numarası</label>
                    <input
                        type="text"
                        id="genel-telefon"
                        name="telefon"
                        class="form-control @error('telefon') is-invalid @enderror"
                        value="{{ old('telefon', $form['telefon'] ?? '') }}"
                        maxlength="20"
                        placeholder="Örn. 0212 000 00 00"
                        @disabled(! $guncelleyebilir)
                    >
                    @error('telefon')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="genel-eposta">E-posta adresi</label>
                    <input
                        type="email"
                        id="genel-eposta"
                        name="eposta"
                        class="form-control @error('eposta') is-invalid @enderror"
                        value="{{ old('eposta', $form['eposta'] ?? '') }}"
                        maxlength="255"
                        placeholder="ornek@kurum.gov.tr"
                        @disabled(! $guncelleyebilir)
                    >
                    @error('eposta')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Portal Giriş</h3>
                <p class="sertifika-ayarlar-desc">Vatandaşların (kişilerin) portala hangi bilgilerle giriş yapacağını belirleyin.</p>
            </div>

            <div class="form-group">
                <label for="genel-kisi-giris-yontemi">Kişilerin giriş yöntemi</label>
                <select
                    id="genel-kisi-giris-yontemi"
                    name="kisi_giris_yontemi"
                    class="form-control @error('kisi_giris_yontemi') is-invalid @enderror"
                    @disabled(! $guncelleyebilir)
                    required
                >
                    @foreach ($form['kisi_giris_yontemi_secenekler'] ?? [] as $secenek)
                        <option
                            value="{{ $secenek['value'] }}"
                            @selected(old('kisi_giris_yontemi', $form['kisi_giris_yontemi'] ?? 'tc_sifre') === $secenek['value'])
                        >
                            {{ $secenek['label'] }}
                        </option>
                    @endforeach
                </select>
                <p class="form-hint">Seçilen yöntem portal ve mobil uygulamada tek giriş şekli olarak kullanılır.</p>
                @error('kisi_giris_yontemi')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Adres</h3>
                <p class="sertifika-ayarlar-desc">Kurumun il, ilçe ve açık adresi.</p>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="genel-il">İl</label>
                    <input
                        type="text"
                        id="genel-il"
                        name="il"
                        class="form-control @error('il') is-invalid @enderror"
                        value="{{ old('il', $form['il'] ?? '') }}"
                        maxlength="100"
                        @disabled(! $guncelleyebilir)
                    >
                    @error('il')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="genel-ilce">İlçe</label>
                    <input
                        type="text"
                        id="genel-ilce"
                        name="ilce"
                        class="form-control @error('ilce') is-invalid @enderror"
                        value="{{ old('ilce', $form['ilce'] ?? '') }}"
                        maxlength="100"
                        @disabled(! $guncelleyebilir)
                    >
                    @error('ilce')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="genel-adres">Adres</label>
                <textarea
                    id="genel-adres"
                    name="adres"
                    class="form-control @error('adres') is-invalid @enderror"
                    rows="3"
                    maxlength="500"
                    placeholder="Açık adres"
                    @disabled(! $guncelleyebilir)
                >{{ old('adres', $form['adres'] ?? '') }}</textarea>
                @error('adres')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        </div>

        <div class="sertifika-ayarlar-actions">
            @if ($guncelleyebilir)
                <button type="submit" class="btn-cta">
                    <span class="btn-cta-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </span>
                    <span class="btn-cta-text">Ayarları Kaydet</span>
                </button>
            @else
                <button type="button" class="btn-cta" disabled title="Bu işlem için yetkiniz yok">
                    <span class="btn-cta-text">Ayarları Kaydet</span>
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
