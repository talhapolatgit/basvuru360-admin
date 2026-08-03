@extends('layouts.admin')

@section('title', 'Merkez Yetkilendirme')

@php
    $allColumns = [
        'ad' => 'Ad Soyad',
        'email' => 'E-posta',
        'rol' => 'Roller',
        'merkezler' => 'Merkezler',
        'merkez_sayisi' => 'Merkez Adedi',
        'durum' => 'Durum',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'rol', 'merkezler', 'merkez_sayisi', 'durum', 'islemler'];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Yetkilendirme</p>
        <h1 class="page-title">Merkez Yetkilendirme</h1>
        <p class="page-subtitle">Kullanıcıların yetkili olduğu merkezleri listeleyin ve düzenleyin.</p>
    </div>
</div>

<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Kullanıcı, rol, merkez ve yetki durumuna göre daraltın.</p>
        </div>
    </div>

    <form id="merkez-yetkileri-filter-form" method="GET" action="{{ route('merkez-yetkileri.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="q">Kullanıcı</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Ad, soyad, e-posta..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label>Rol</label>
                <x-searchable-select
                    name="rol_id"
                    placeholder="Tüm roller"
                    :value="$filters['rol_id'] ?? ''"
                    :options="$roller->map(fn ($r) => ['value' => $r->id, 'label' => $r->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Merkez</label>
                <x-searchable-select
                    name="merkez_id"
                    placeholder="Tüm merkezler"
                    :value="$filters['merkez_id'] ?? ''"
                    :options="$merkezler->map(fn ($m) => ['value' => $m->id, 'label' => $m->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label for="durum">Yetki durumu</label>
                <select id="durum" name="durum" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['durum'] ?? 'tumu') === 'tumu')>Tümü</option>
                    <option value="yetkili" @selected(($filters['durum'] ?? '') === 'yetkili')>Merkez yetkili</option>
                    <option value="yetkisiz" @selected(($filters['durum'] ?? '') === 'yetkisiz')>Yetkisiz</option>
                </select>
            </div>

            <div class="form-group">
                <label for="aktif">Kullanıcı durumu</label>
                <select id="aktif" name="aktif" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['aktif'] ?? 'tumu') === 'tumu')>Tümü</option>
                    <option value="aktif" @selected(($filters['aktif'] ?? '') === 'aktif')>Aktif</option>
                    <option value="pasif" @selected(($filters['aktif'] ?? '') === 'pasif')>Pasif</option>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <div class="filter-actions-right">
                <x-back-button id="merkez-yetkileri-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

<div class="card table-card" id="merkez-yetkileri-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kullanıcı Merkez Yetkileri</div>
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
                :href="route('merkez-yetkileri.export', request()->query())"
                id="merkez-yetkileri-excel-link"
            />
        </div>
    </div>

    <div id="merkez-yetkileri-results">
        @include('merkez-yetkileri._results')
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
