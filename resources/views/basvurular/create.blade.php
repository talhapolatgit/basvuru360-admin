@extends('layouts.admin')

@section('title', 'Yeni Kurs Başvurusu')

@section('content')
@php
    use App\Enums\Cinsiyet;
@endphp

<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kurs Yönetimi</p>
        <h1 class="page-title">Yeni Kurs Başvurusu</h1>
        <p class="page-subtitle">Önce kursu seçin, ardından katılımcı ve gerekirse veli bilgilerini girerek başvuruyu kaydedin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('basvurular.index') }}">Başvurulara Dön</x-back-button>
    </div>
</div>

@if ($errors->any())
    <div class="card" style="margin-bottom:16px; border-color:#f1416c; background:#fff5f8;">
        <ul style="margin:0; padding-left:18px; color:#f1416c; font-size:13px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    method="POST"
    action="{{ route('basvurular.store') }}"
    enctype="multipart/form-data"
    class="basvuru-create"
    data-basvuru-create
    data-kurs-ozet-url-template="{{ url('/kurs-basvurulari/kurs/__ID__/ozet') }}"
    data-kimlik-sorgula-url="{{ route('kisiler.kimlik-sorgula') }}"
    data-adres-sorgula-url="{{ route('kisiler.adres-sorgula') }}"
>
    @csrf

    <nav class="basvuru-stepper" data-basvuru-stepper aria-label="Başvuru adımları">
        <button type="button" class="basvuru-step is-active" data-basvuru-step-btn="1">
            <span class="basvuru-step-num">1</span>
            <span class="basvuru-step-label">Kurs</span>
        </button>
        <button type="button" class="basvuru-step" data-basvuru-step-btn="2" disabled>
            <span class="basvuru-step-num">2</span>
            <span class="basvuru-step-label">Katılımcı</span>
        </button>
        <button type="button" class="basvuru-step" data-basvuru-step-btn="3" disabled>
            <span class="basvuru-step-num">3</span>
            <span class="basvuru-step-label">Veli</span>
        </button>
        <button type="button" class="basvuru-step" data-basvuru-step-btn="4" disabled data-basvuru-evrak-step hidden>
            <span class="basvuru-step-num">4</span>
            <span class="basvuru-step-label">Evrak</span>
        </button>
    </nav>

    {{-- Adım 1: Kurs --}}
    <section class="card form-section-card basvuru-step-panel is-active" data-basvuru-step-panel="1">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Kurs Seçimi</h2>
                <p class="card-section-desc">Başvurunun yapılacağı kursu seçin. Hazırlık ve aktif kurslar listelenir.</p>
            </div>
        </div>

        <div class="form-group" style="max-width:520px;">
            <label>Kurs <span class="req">*</span></label>
            <x-searchable-select
                name="kurs_id"
                placeholder="Kurs seçin..."
                :value="old('kurs_id', $seciliKursId ?? '')"
                :options="$kursOptions"
                :required="true"
                data-basvuru-kurs-select
            />
        </div>

        <div class="basvuru-kurs-ozet is-empty" data-basvuru-kurs-ozet>
            <p class="basvuru-kurs-ozet-empty">Kurs seçildiğinde özet bilgiler burada görünür.</p>
            <div class="basvuru-kurs-ozet-body" hidden data-basvuru-kurs-ozet-body>
                <div class="lesson-info-grid">
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Kurs No</div>
                        <div class="lesson-info-value" data-ozet="kurs_no">—</div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Branş</div>
                        <div class="lesson-info-value" data-ozet="brans">—</div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Merkez</div>
                        <div class="lesson-info-value" data-ozet="merkez">—</div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Durum</div>
                        <div class="lesson-info-value" data-ozet="durum">—</div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Kurs Tarihi</div>
                        <div class="lesson-info-value"><span data-ozet="baslama">—</span> – <span data-ozet="bitis">—</span></div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Ana Kontenjan</div>
                        <div class="lesson-info-value"><span data-ozet="ana_sayisi">0</span> / <span data-ozet="kontenjan">0</span></div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Yedek Kontenjan</div>
                        <div class="lesson-info-value"><span data-ozet="yedek_sayisi">0</span> / <span data-ozet="yedek_kontenjan">0</span></div>
                    </div>
                    <div class="lesson-info-card">
                        <div class="lesson-info-label">Koşullar</div>
                        <div class="lesson-info-value" data-ozet="kosullar">—</div>
                    </div>
                </div>
                <div class="basvuru-kurs-evrak-note is-hidden" data-basvuru-kurs-evrak-note>
                    <strong>Evrak şartı var:</strong>
                    <span data-ozet="evrak_listesi"></span>
                </div>
            </div>
        </div>
    </section>

    {{-- Adım 2: Katılımcı --}}
    <section class="card form-section-card basvuru-step-panel" data-basvuru-step-panel="2" hidden>
        <div
            data-kisi-form
            data-kimlik-sorgula-url="{{ route('kisiler.kimlik-sorgula') }}"
            data-adres-sorgula-url="{{ route('kisiler.adres-sorgula') }}"
        >
            <div class="card-section-header">
                <div>
                    <h2 class="card-section-title">Katılımcı Bilgileri</h2>
                    <p class="card-section-desc">TC ve doğum tarihi ile kimlik sorgulayarak formu doldurun.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tc_kimlik_no">TC Kimlik No <span class="req">*</span></label>
                    <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" value="{{ old('tc_kimlik_no') }}" class="form-control @error('tc_kimlik_no') is-invalid @enderror" maxlength="11" inputmode="numeric" autocomplete="off" data-kimlik-tc>
                </div>
                <div class="form-group">
                    <label for="dogum_tarihi">Doğum Tarihi <span class="req">*</span></label>
                    <div class="input-with-action">
                        <input type="date" id="dogum_tarihi" name="dogum_tarihi" value="{{ old('dogum_tarihi') }}" class="form-control @error('dogum_tarihi') is-invalid @enderror" data-kimlik-dogum data-basvuru-dogum>
                        <button type="button" class="btn-kimlik-sorgula" data-kimlik-sorgula title="Kimlik Sorgula" aria-label="Kimlik Sorgula">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ad">Ad <span class="req">*</span></label>
                    <input type="text" id="ad" name="ad" value="{{ old('ad') }}" class="form-control @error('ad') is-invalid @enderror" data-kimlik-alan="ad">
                </div>
                <div class="form-group">
                    <label for="soyad">Soyad <span class="req">*</span></label>
                    <input type="text" id="soyad" name="soyad" value="{{ old('soyad') }}" class="form-control @error('soyad') is-invalid @enderror" data-kimlik-alan="soyad">
                </div>
                <div class="form-group">
                    <label for="cinsiyet">Cinsiyet</label>
                    <select id="cinsiyet" name="cinsiyet" class="form-control" data-kimlik-alan="cinsiyet">
                        <option value="">Seçiniz</option>
                        @foreach (Cinsiyet::cases() as $cinsiyet)
                            <option value="{{ $cinsiyet->value }}" @selected(old('cinsiyet') === $cinsiyet->value)>{{ $cinsiyet->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="telefon">Telefon</label>
                    <input type="text" id="telefon" name="telefon" value="{{ old('telefon') }}" class="form-control" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" maxlength="150">
                </div>
            </div>

            <div class="card-section-header" style="margin-top:22px;">
                <div>
                    <h2 class="card-section-title">Adres Bilgileri</h2>
                    <p class="card-section-desc">TC ve doğum tarihi ile adres sorgulayabilirsiniz.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="il">İl <span class="req">*</span></label>
                    <div class="input-with-action">
                        <input type="text" id="il" name="il" value="{{ old('il') }}" class="form-control @error('il') is-invalid @enderror" data-adres-alan="il">
                        <button type="button" class="btn-kimlik-sorgula" data-adres-sorgula title="Adres Sorgula" aria-label="Adres Sorgula">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ilce">İlçe <span class="req">*</span></label>
                    <input type="text" id="ilce" name="ilce" value="{{ old('ilce') }}" class="form-control @error('ilce') is-invalid @enderror" data-adres-alan="ilce">
                </div>
                <div class="form-group" style="grid-column:1 / -1;">
                    <label for="adres">Adres <span class="req">*</span></label>
                    <textarea id="adres" name="adres" rows="3" class="form-control @error('adres') is-invalid @enderror" data-adres-alan="adres">{{ old('adres') }}</textarea>
                </div>
            </div>
        </div>
    </section>

    {{-- Adım 3: Veli --}}
    <section class="card form-section-card basvuru-step-panel" data-basvuru-step-panel="3" hidden>
        <div
            data-kisi-form
            data-kimlik-sorgula-url="{{ route('kisiler.kimlik-sorgula') }}"
        >
            <div class="card-section-header">
                <div>
                    <h2 class="card-section-title">Veli Bilgileri</h2>
                    <p class="card-section-desc">
                        18 yaşından küçük katılımcılarda veli bilgisi zorunludur.
                        <span class="basvuru-veli-badge is-hidden" data-basvuru-veli-badge>18 yaş altı — zorunlu</span>
                    </p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="veli_tc_kimlik_no">TC Kimlik No <span class="req is-hidden" data-veli-req>*</span></label>
                    <input type="text" id="veli_tc_kimlik_no" name="veli_tc_kimlik_no" value="{{ old('veli_tc_kimlik_no') }}" class="form-control @error('veli_tc_kimlik_no') is-invalid @enderror" maxlength="11" inputmode="numeric" autocomplete="off" data-kimlik-tc>
                </div>
                <div class="form-group">
                    <label for="veli_dogum_tarihi">Doğum Tarihi <span class="req is-hidden" data-veli-req>*</span></label>
                    <div class="input-with-action">
                        <input type="date" id="veli_dogum_tarihi" name="veli_dogum_tarihi" value="{{ old('veli_dogum_tarihi') }}" class="form-control @error('veli_dogum_tarihi') is-invalid @enderror" data-kimlik-dogum>
                        <button type="button" class="btn-kimlik-sorgula" data-kimlik-sorgula title="Kimlik Sorgula" aria-label="Kimlik Sorgula">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="veli_ad">Ad <span class="req is-hidden" data-veli-req>*</span></label>
                    <input type="text" id="veli_ad" name="veli_ad" value="{{ old('veli_ad') }}" class="form-control @error('veli_ad') is-invalid @enderror" data-kimlik-alan="ad">
                </div>
                <div class="form-group">
                    <label for="veli_soyad">Soyad <span class="req is-hidden" data-veli-req>*</span></label>
                    <input type="text" id="veli_soyad" name="veli_soyad" value="{{ old('veli_soyad') }}" class="form-control @error('veli_soyad') is-invalid @enderror" data-kimlik-alan="soyad">
                </div>
                <div class="form-group">
                    <label for="veli_telefon">Telefon</label>
                    <input type="text" id="veli_telefon" name="veli_telefon" value="{{ old('veli_telefon') }}" class="form-control" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="veli_email">E-posta</label>
                    <input type="email" id="veli_email" name="veli_email" value="{{ old('veli_email') }}" class="form-control" maxlength="150">
                </div>
            </div>
        </div>
    </section>

    {{-- Adım 4: Evrak --}}
    <section class="card form-section-card basvuru-step-panel" data-basvuru-step-panel="4" hidden data-basvuru-evrak-panel>
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Evrak Yükleme</h2>
                <p class="card-section-desc">Bu kurs için zorunlu evrakları yükleyin. PDF veya JPG/PNG, en fazla 5 MB.</p>
            </div>
        </div>
        <div class="basvuru-evrak-list" data-basvuru-evrak-list></div>
    </section>

    <div class="form-footer basvuru-create-footer">
        <button type="button" class="btn btn-secondary" data-basvuru-prev hidden>Geri</button>
        <div class="basvuru-create-footer-right">
            <x-back-button href="{{ route('basvurular.index') }}">Vazgeç</x-back-button>
            <button type="button" class="btn-cta" data-basvuru-next>
                <span class="btn-cta-text">Devam Et</span>
            </button>
            <x-cta-button type="submit" icon="save" data-basvuru-submit hidden>Başvuruyu Kaydet</x-cta-button>
        </div>
    </div>
</form>
@endsection
