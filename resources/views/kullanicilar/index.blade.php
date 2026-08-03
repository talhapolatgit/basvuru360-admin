@extends('layouts.admin')

@section('title', 'Kullanıcılar')

@php
    $allColumns = [
        'ad' => 'Ad Soyad',
        'tc_kimlik_no' => 'TC Kimlik No',
        'dogum_tarihi' => 'Doğum Tarihi',
        'telefon' => 'Telefon',
        'email' => 'E-posta',
        'rol' => 'Rol',
        'aktif_kurs_sayisi' => 'Aktif Kurs Sayısı',
        'durum' => 'Durum',
        'olusturma' => 'Kayıt Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'tc_kimlik_no', 'telefon', 'rol', 'durum', 'islemler'];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kullanıcılar</p>
        <h1 class="page-title">Kullanıcılar</h1>
        <p class="page-subtitle">Kullanıcıları listeleyin, oluşturun ve düzenleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-cta-button :href="route('kullanicilar.create')" icon="plus">Yeni Kullanıcı</x-cta-button>
    </div>
</div>

{{-- Filtre Kartı --}}
<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Ad soyad, TC kimlik no, telefon, e-posta, rol ve duruma göre daraltın.</p>
        </div>
    </div>

    <form id="kullanicilar-filter-form" method="GET" action="{{ route('kullanicilar.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="ad_soyad">Ad Soyad</label>
                <input
                    type="text"
                    id="ad_soyad"
                    name="ad_soyad"
                    value="{{ $filters['ad_soyad'] ?? '' }}"
                    placeholder="Ara..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="tc_kimlik_no_filter">TC Kimlik No</label>
                <input
                    type="text"
                    id="tc_kimlik_no_filter"
                    name="tc_kimlik_no"
                    value="{{ $filters['tc_kimlik_no'] ?? '' }}"
                    placeholder="Ara..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="telefon_filter">Telefon</label>
                <input
                    type="text"
                    id="telefon_filter"
                    name="telefon"
                    value="{{ $filters['telefon'] ?? '' }}"
                    placeholder="Ara..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="email_filter">E-posta Adresi</label>
                <input
                    type="text"
                    id="email_filter"
                    name="email"
                    value="{{ $filters['email'] ?? '' }}"
                    placeholder="Ara..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="rol">Rol</label>
                <select id="rol" name="rol" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['rol'] ?? 'tumu') === 'tumu')>Tümü</option>
                    @foreach ($roller as $rol)
                        <option value="{{ $rol->kod }}" @selected(($filters['rol'] ?? 'tumu') === $rol->kod)>{{ $rol->ad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="durum">Durum</label>
                <select id="durum" name="durum" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['durum'] ?? 'tumu') === 'tumu')>Tümü</option>
                    <option value="aktif" @selected(($filters['durum'] ?? 'tumu') === 'aktif')>Aktif</option>
                    <option value="pasif" @selected(($filters['durum'] ?? 'tumu') === 'pasif')>Pasif</option>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <div class="filter-actions-right">
                <x-back-button id="kullanicilar-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

{{-- Tablo Kartı --}}
<div class="card table-card" id="kullanicilar-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kullanıcı Kayıtları</div>
            <p class="table-subtitle">Sütunları sürükleyerek sıralayabilir, görünürlüğü değiştirebilirsiniz.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="column-picker" data-column-picker>
                <button
                    type="button"
                    class="btn-columns"
                    id="columnPickerToggle"
                    onclick="toggleColumnDropdown(event)"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="columnDropdown"
                >
                    <span class="btn-columns-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2"/>
                            <path d="M9 3v18"/>
                            <path d="M15 3v18"/>
                        </svg>
                    </span>
                    <span class="btn-columns-text">Sütunlar</span>
                    <svg class="btn-columns-caret" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div class="column-dropdown" id="columnDropdown" role="menu" aria-labelledby="columnPickerToggle">
                    <div class="column-dropdown-header">
                        <span>Görünür sütunlar</span>
                        <span class="column-dropdown-hint">Sürükleyerek sıralayın</span>
                    </div>
                    <div class="column-dropdown-list">
                        @foreach ($allColumns as $key => $label)
                            @if ($key !== 'islemler')
                                <label class="column-option" data-column="{{ $key }}">
                                    <input
                                        type="checkbox"
                                        class="column-toggle"
                                        data-column="{{ $key }}"
                                        @checked(in_array($key, $defaultVisible, true))
                                    >
                                    <span class="column-option-check" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span>
                                    <span class="column-option-label">{{ $label }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <div class="column-dropdown-footer">
                        <button type="button" class="column-save-btn save-prefs-btn" id="dropdown-save-column-prefs" title="Kolon düzenlemelerini kaydet">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            Kaydet
                        </button>
                        <button type="button" class="column-reset-btn" id="reset-column-prefs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                <path d="M3 3v5h5"/>
                            </svg>
                            Sıfırla
                        </button>
                    </div>
                </div>
            </div>
            <x-excel-export
                :href="route('kullanicilar.export', request()->query())"
                id="kullanicilar-excel-link"
            />
        </div>
    </div>

    <div id="kullanicilar-results">
        @include('kullanicilar._results')
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleColumnDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('columnDropdown');
        const toggle = document.getElementById('columnPickerToggle');
        const picker = document.querySelector('[data-column-picker]');
        const willOpen = !dropdown?.classList.contains('open');
        dropdown?.classList.toggle('open', willOpen);
        picker?.classList.toggle('is-open', willOpen);
        toggle?.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    document.getElementById('columnDropdown')?.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        document.getElementById('columnDropdown')?.classList.remove('open');
        document.querySelector('[data-column-picker]')?.classList.remove('is-open');
        document.getElementById('columnPickerToggle')?.setAttribute('aria-expanded', 'false');
    });
</script>
@endpush
