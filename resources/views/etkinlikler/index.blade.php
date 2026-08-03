@extends('layouts.admin')

@section('title', 'Etkinlikler')

@php
    $defaultVisible = $defaultVisible ?? ['no', 'ad', 'merkez', 'tip', 'baslangic', 'bitis', 'basvuru', 'kayit', 'durum', 'basvuru_durumu', 'islemler'];
    $allColumns = $allColumns ?? [
        'no' => 'No',
        'ad' => 'Ad',
        'merkez' => 'Merkez',
        'kurum' => 'Kurum',
        'tip' => 'Tip',
        'baslangic' => 'Başlangıç',
        'bitis' => 'Bitiş',
        'basvuru' => 'Başvuru',
        'kayit' => 'Kayıt',
        'iptal' => 'İptal',
        'kontenjan' => 'Kontenjan',
        'yedek' => 'Yedek',
        'durum' => 'Durum',
        'basvuru_durumu' => 'Başvuru Durumu',
        'tarih' => 'Tarih',
        'islemler' => 'İşlemler',
    ];
    $ozet = $ozet ?? ['toplam' => 0, 'aktif' => 0, 'hazirlik' => 0, 'basvuruya_acik' => 0];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Etkinlik Yönetimi</p>
        <h1 class="page-title">Etkinlik Listesi</h1>
        <p class="page-subtitle">Etkinlikleri filtreleyin, sütunları düzenleyin ve kayıtları yönetin.</p>
    </div>
    <div class="flex items-center gap-2">
        @yetki('etkinlik.olustur')
            <x-cta-button :href="route('etkinlikler.create')" />
        @endyetki
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Toplam Etkinlik</div>
        <div class="stat-value">{{ number_format($ozet['toplam']) }}</div>
    </div>
    <a href="{{ route('etkinlikler.index', ['durum' => 'aktif']) }}" class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif</div>
        <div class="stat-value">{{ number_format($ozet['aktif']) }}</div>
    </a>
    <a href="{{ route('etkinlikler.index', ['durum' => 'hazirlik']) }}" class="stat-card stat-card-hazirlik">
        <div class="stat-label">Hazırlık</div>
        <div class="stat-value">{{ number_format($ozet['hazirlik']) }}</div>
    </a>
    <a href="{{ route('etkinlikler.index', ['basvuru_durumu' => 'acik']) }}" class="stat-card stat-card-yayinda">
        <div class="stat-label">Başvuruya Açık</div>
        <div class="stat-value">{{ number_format($ozet['basvuruya_acik']) }}</div>
    </a>
</div>

{{-- Filtre Kartı --}}
<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Etkinlik no, ad, merkez ve duruma göre daraltın.</p>
        </div>
    </div>

    <form id="etkinlikler-filter-form" method="GET" action="{{ route('etkinlikler.index') }}" data-ajax-filter>
        <div class="filter-grid" data-filter-grid>
            <div class="form-group">
                <label for="etkinlik_no">Etkinlik No</label>
                <div
                    class="input-with-mode"
                    data-search-mode
                    data-mode-param="etkinlik_no_mode"
                    data-mode-storage="etkinlik_no_search_mode_v1"
                >
                    <input
                        type="text"
                        id="etkinlik_no"
                        name="etkinlik_no"
                        value="{{ $filters['etkinlik_no'] ?? '' }}"
                        placeholder="No girin..."
                        class="form-control input-with-mode-control"
                        autocomplete="off"
                    >
                    <input type="hidden" name="etkinlik_no_mode" value="{{ $filters['etkinlik_no_mode'] ?? 'exact' }}" data-mode-value>
                    <button type="button" class="mode-toggle" data-mode-toggle title="Arama yöntemi" aria-haspopup="listbox" aria-expanded="false">
                        <span data-mode-label>Eşit</span>
                        <svg class="mode-toggle-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="mode-dropdown" data-mode-dropdown hidden role="listbox">
                        <button type="button" class="mode-option" data-mode="contains" role="option">İçinde</button>
                        <button type="button" class="mode-option" data-mode="starts" role="option">Başında</button>
                        <button type="button" class="mode-option" data-mode="ends" role="option">Sonunda</button>
                        <button type="button" class="mode-option" data-mode="exact" role="option">Eşit</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="ad">Ad</label>
                <div
                    class="input-with-mode"
                    data-search-mode
                    data-mode-param="ad_mode"
                    data-mode-storage="etkinlik_ad_search_mode_v1"
                >
                    <input
                        type="text"
                        id="ad"
                        name="ad"
                        value="{{ $filters['ad'] ?? '' }}"
                        placeholder="Ad girin..."
                        class="form-control input-with-mode-control"
                        autocomplete="off"
                    >
                    <input type="hidden" name="ad_mode" value="{{ $filters['ad_mode'] ?? 'contains' }}" data-mode-value>
                    <button type="button" class="mode-toggle" data-mode-toggle title="Arama yöntemi" aria-haspopup="listbox" aria-expanded="false">
                        <span data-mode-label>İçinde</span>
                        <svg class="mode-toggle-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="mode-dropdown" data-mode-dropdown hidden role="listbox">
                        <button type="button" class="mode-option" data-mode="contains" role="option">İçinde</button>
                        <button type="button" class="mode-option" data-mode="starts" role="option">Başında</button>
                        <button type="button" class="mode-option" data-mode="ends" role="option">Sonunda</button>
                        <button type="button" class="mode-option" data-mode="exact" role="option">Eşit</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Merkez</label>
                <x-searchable-select
                    name="merkez_id"
                    placeholder="Tüm Merkezler"
                    :value="$filters['merkez_id'] ?? ''"
                    :options="$merkezler->map(fn ($m) => ['value' => $m->id, 'label' => $m->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Kurum</label>
                <x-searchable-select
                    name="kurum_id"
                    placeholder="Tüm Kurumlar"
                    :value="$filters['kurum_id'] ?? ''"
                    :options="$kurumlar->map(fn ($k) => ['value' => $k->id, 'label' => $k->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Tip</label>
                <x-searchable-select
                    name="etkinlik_tipi_id"
                    placeholder="Tüm Tipler"
                    :value="$filters['etkinlik_tipi_id'] ?? ''"
                    :options="$etkinlikTipleri->map(fn ($t) => ['value' => $t->id, 'label' => $t->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label for="durum">Durum</label>
                <select id="durum" name="durum" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['durum'] ?? 'tumu') === 'tumu')>Tümü</option>
                    @foreach ($durumlar as $durum)
                        <option value="{{ $durum->value }}" @selected(($filters['durum'] ?? 'tumu') === $durum->value)>{{ $durum->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="basvuru_durumu">Başvuru Durumu</label>
                <select id="basvuru_durumu" name="basvuru_durumu" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['basvuru_durumu'] ?? 'tumu') === 'tumu')>Tümü</option>
                    @foreach (($basvuruDurumlari ?? []) as $kod => $label)
                        <option value="{{ $kod }}" @selected(($filters['basvuru_durumu'] ?? 'tumu') === $kod)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <div class="filter-actions-right">
                <x-back-button id="etkinlikler-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

{{-- Tablo Kartı --}}
<div class="card table-card" id="etkinlikler-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Etkinlik Kayıtları</div>
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
                        <button
                            type="button"
                            class="column-save-btn save-prefs-btn"
                            id="dropdown-save-column-prefs"
                            data-column-save
                            title="Kolon düzenlemelerini kaydet"
                        >
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
            @yetki('etkinlik.export')
            <x-excel-export
                :href="route('etkinlikler.export', request()->query())"
                id="etkinlikler-excel-link"
            />
            @endyetki
        </div>
    </div>

    <div id="etkinlikler-results">
        @include('etkinlikler._results')
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
