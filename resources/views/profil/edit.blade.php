@extends('layouts.admin')

@section('title', 'Profilim')

@php
    $initials = $kullanici->bas_harfler;

    $val = function (string $key, mixed $default = null) use ($kullanici) {
        if (old($key) !== null) {
            return old($key);
        }

        return data_get($kullanici, $key) ?? $default;
    };

    $dogumTarihi = old('dogum_tarihi') ?? $kullanici->dogum_tarihi?->format('Y-m-d') ?? '';
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Hesap</p>
        <h1 class="page-title">Profilim</h1>
        <p class="page-subtitle">Kişisel bilgilerinizi görüntüleyin ve güncelleyin.</p>
    </div>
</div>

<form method="POST" action="{{ route('profil.update') }}" class="profil-form" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card form-section-card">
        <div class="card-section-header profil-header">
            <div
                class="profil-avatar-wrap"
                data-avatar-preview
                data-upload-url="{{ route('profil.foto.update') }}"
                data-delete-url="{{ route('profil.foto.delete') }}"
            >
                <label class="profil-avatar profil-avatar-lg profil-avatar-edit" title="Yeni fotoğraf yükle">
                    @if ($kullanici->profil_foto_url)
                        <img src="{{ $kullanici->profil_foto_url }}" alt="Profil fotoğrafı" data-avatar-img>
                        <span class="profil-avatar-initials" data-avatar-initials hidden>{{ $initials ?: '?' }}</span>
                    @else
                        <img src="" alt="Profil fotoğrafı" data-avatar-img hidden>
                        <span class="profil-avatar-initials" data-avatar-initials>{{ $initials ?: '?' }}</span>
                    @endif
                    <span class="profil-avatar-overlay" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" data-avatar-input hidden>
                </label>
                <button
                    type="button"
                    class="profil-avatar-remove-btn"
                    data-avatar-remove-btn
                    title="Profil fotoğrafını kaldır"
                    aria-label="Profil fotoğrafını kaldır"
                    @unless ($kullanici->profil_foto) hidden @endunless
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div>
                <h2 class="card-section-title">Kimlik ve İletişim Bilgileri</h2>
                <p class="card-section-desc">Ad, soyad, iletişim ve adres bilgileriniz. Fotoğrafın üzerine tıklayarak resminizi değiştirebilirsiniz.</p>
            </div>
        </div>

        @if ($errors->any() && ! session('sifre_hata'))
            <div class="alert alert-error mb-4">
                <strong>Formda hatalar var.</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label for="ad">Ad <span class="req">*</span></label>
                <input type="text" id="ad" name="ad" value="{{ $val('ad') }}" class="form-control @error('ad') is-invalid @enderror" required maxlength="100" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="soyad">Soyad <span class="req">*</span></label>
                <input type="text" id="soyad" name="soyad" value="{{ $val('soyad') }}" class="form-control @error('soyad') is-invalid @enderror" required maxlength="100" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="email">E-posta <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="{{ $val('email') }}" class="form-control @error('email') is-invalid @enderror" required maxlength="150" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="telefon">Telefon</label>
                <input type="text" id="telefon" name="telefon" value="{{ $val('telefon') }}" class="form-control @error('telefon') is-invalid @enderror" maxlength="20" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="tc_kimlik_no">TC Kimlik No</label>
                <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" value="{{ $val('tc_kimlik_no') }}" class="form-control @error('tc_kimlik_no') is-invalid @enderror" maxlength="11" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="dogum_tarihi">Doğum Tarihi</label>
                <input type="date" id="dogum_tarihi" name="dogum_tarihi" value="{{ $dogumTarihi }}" class="form-control @error('dogum_tarihi') is-invalid @enderror">
            </div>

            <div class="form-group">
                <label for="rol_display">Rol</label>
                <input type="text" id="rol_display" value="{{ $kullanici->roller_ozeti }}" class="form-control" disabled>
            </div>

            <div class="form-group">
                <label for="il">İl</label>
                <input type="text" id="il" name="il" value="{{ $val('il') }}" class="form-control @error('il') is-invalid @enderror" maxlength="100" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="ilce">İlçe</label>
                <input type="text" id="ilce" name="ilce" value="{{ $val('ilce') }}" class="form-control @error('ilce') is-invalid @enderror" maxlength="100" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="adres">Adres</label>
                <textarea id="adres" name="adres" class="form-control @error('adres') is-invalid @enderror" rows="2" maxlength="500">{{ $val('adres') }}</textarea>
            </div>
        </div>

        <div class="form-footer">
            <x-cta-button type="submit" icon="save">Bilgileri Kaydet</x-cta-button>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('profil.password') }}" class="profil-form">
    @csrf
    @method('PUT')

    <div class="card form-section-card">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Şifre Değiştir</h2>
                <p class="card-section-desc">Hesabınızın güvenliği için düzenli olarak şifrenizi değiştirin.</p>
            </div>
        </div>

        @if (session('sifre_hata') && $errors->any())
            <div class="alert alert-error mb-4">
                <strong>Şifre güncellenemedi.</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label for="mevcut_sifre">Mevcut Şifre <span class="req">*</span></label>
                <input type="password" id="mevcut_sifre" name="mevcut_sifre" class="form-control @error('mevcut_sifre') is-invalid @enderror" autocomplete="current-password">
            </div>

            <div class="form-group"></div>

            <div class="form-group">
                <label for="password">Yeni Şifre <span class="req">*</span></label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" placeholder="En az 6 karakter">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Yeni Şifre (Tekrar) <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
            </div>
        </div>

        <div class="form-footer">
            <x-cta-button type="submit" icon="save">Şifreyi Güncelle</x-cta-button>
        </div>
    </div>
</form>
@endsection
