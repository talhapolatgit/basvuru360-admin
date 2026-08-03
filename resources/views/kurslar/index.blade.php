@extends('layouts.admin')

@section('title', 'Kurs Yönetimi')

@php
    $defaultVisible = ['no', 'brans', 'merkez', 'gunler', 'basvuru', 'kayit', 'durum', 'basvuru_durumu', 'islemler'];
    $allColumns = [
        'no' => 'No',
        'alan' => 'Alan',
        'brans' => 'Branş',
        'merkez' => 'Merkez',
        'kurum' => 'Kurum',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'gunler' => 'Günler',
        'basvuru' => 'Başvuru',
        'kayit' => 'Kayıt',
        'iptal' => 'İptal',
        'kontenjan' => 'Kontenjan',
        'yedek' => 'Yedek',
        'egitmen' => 'Eğitmen',
        'belge' => 'Belge Türü',
        'durum' => 'Durum',
        'basvuru_durumu' => 'Başvuru Durumu',
        'tarih' => 'Tarih',
        'islemler' => 'İşlemler',
    ];
    $gunlerList = ['pazartesi' => 'Pazartesi', 'sali' => 'Salı', 'carsamba' => 'Çarşamba', 'persembe' => 'Perşembe', 'cuma' => 'Cuma', 'cumartesi' => 'Cumartesi', 'pazar' => 'Pazar'];
    $selectedGunler = (array) ($filters['gunler'] ?? []);
    $ozet = $ozet ?? ['toplam' => 0, 'aktif' => 0, 'hazirlik' => 0, 'basvuruya_acik' => 0];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kurs Yönetimi</p>
        <h1 class="page-title">Kurs Listesi</h1>
        <p class="page-subtitle">Kursları filtreleyin, sütunları düzenleyin ve kayıtları yönetin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-cta-button :href="route('kurslar.create')" />
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Toplam Kurs</div>
        <div class="stat-value">{{ number_format($ozet['toplam']) }}</div>
    </div>
    <a href="{{ route('kurslar.index', ['durum' => 'aktif']) }}" class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif</div>
        <div class="stat-value">{{ number_format($ozet['aktif']) }}</div>
    </a>
    <a href="{{ route('kurslar.index', ['durum' => 'hazirlik']) }}" class="stat-card stat-card-hazirlik">
        <div class="stat-label">Hazırlık</div>
        <div class="stat-value">{{ number_format($ozet['hazirlik']) }}</div>
    </a>
    <a href="{{ route('kurslar.index', ['basvuru_durumu' => 'acik']) }}" class="stat-card stat-card-yayinda">
        <div class="stat-label">Başvuruya Açık</div>
        <div class="stat-value">{{ number_format($ozet['basvuruya_acik']) }}</div>
    </a>
</div>

{{-- Filtre Kartı --}}
<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Kurs no, merkez, branş ve tarih aralıklarına göre daraltın.</p>
        </div>
    </div>

    <form id="kurslar-filter-form" method="GET" action="{{ route('kurslar.index') }}" data-ajax-filter>
        <div class="filter-grid" data-filter-grid>
            <div class="form-group">
                <label for="kurs_no">Kurs No</label>
                <div class="input-with-mode" data-kurs-no-mode>
                    <input
                        type="text"
                        id="kurs_no"
                        name="kurs_no"
                        value="{{ $filters['kurs_no'] ?? '' }}"
                        placeholder="No girin..."
                        class="form-control input-with-mode-control"
                        autocomplete="off"
                    >
                    <input type="hidden" name="kurs_no_mode" value="{{ $filters['kurs_no_mode'] ?? 'exact' }}" data-mode-value>
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
                <label>Alan</label>
                <x-searchable-select
                    name="alan_id"
                    placeholder="Tüm Alanlar"
                    :value="$filters['alan_id'] ?? ''"
                    :options="$alanlar->map(fn ($a) => ['value' => $a->id, 'label' => $a->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Branş</label>
                <x-searchable-select
                    name="brans_id"
                    placeholder="Tüm Branşlar"
                    :value="$filters['brans_id'] ?? ''"
                    :options="$branslar->map(fn ($b) => ['value' => $b->id, 'label' => $b->ad])->all()"
                />
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
                <label>Öğretmen</label>
                <x-searchable-select
                    name="ogretmen_id"
                    placeholder="Tüm Öğretmenler"
                    :value="$filters['ogretmen_id'] ?? ''"
                    :options="$ogretmenler->map(fn ($o) => ['value' => $o->id, 'label' => $o->tam_adi])->all()"
                />
            </div>

            <div class="form-group">
                <label for="durum">Durum</label>
                <select id="durum" name="durum" class="form-control">
                    <option value="hazirlik" @selected(($filters['durum'] ?? 'tumu') === 'hazirlik')>Hazırlık</option>
                    <option value="aktif" @selected(($filters['durum'] ?? 'tumu') === 'aktif')>Aktif</option>
                    <option value="tamamlanan" @selected(($filters['durum'] ?? 'tumu') === 'tamamlanan')>Tamamlanan</option>
                    <option value="iptal" @selected(($filters['durum'] ?? 'tumu') === 'iptal')>İptal Edilen</option>
                    <option value="tumu" @selected(($filters['durum'] ?? 'tumu') === 'tumu')>Tüm Kurslar</option>
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

            <div class="form-group">
                <label>Günler</label>
                <div class="custom-select" id="gunlerSelect">
                    <div class="select-display" onclick="toggleGunlerDropdown(event)">
                        <span id="gunlerDisplay">{{ count($selectedGunler) ? count($selectedGunler).' gün seçili' : 'Tümü' }}</span>
                    </div>
                    <div class="select-dropdown" id="gunlerDropdown">
                        @foreach ($gunlerList as $value => $label)
                            <label class="multi-select-option">
                                <input type="checkbox" name="gunler[]" value="{{ $value }}" @checked(in_array($value, $selectedGunler, true)) onchange="updateGunlerDisplay()">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="kurs_tipi_id">Belge Tipi</label>
                <select id="kurs_tipi_id" name="kurs_tipi_id" class="form-control">
                    <option value="">Hepsi</option>
                    @foreach ($kursTipleri as $tip)
                        <option value="{{ $tip->id }}" @selected(($filters['kurs_tipi_id'] ?? '') == $tip->id)>{{ $tip->ad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="ilk_kayit">İlk Kayıt</label>
                <input type="date" id="ilk_kayit" name="ilk_kayit" value="{{ $filters['ilk_kayit'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="son_kayit">Son Kayıt</label>
                <input type="date" id="son_kayit" name="son_kayit" value="{{ $filters['son_kayit'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="kurs_baslama_ilk">Kurs Başlama Tarihi (İlk)</label>
                <input type="date" id="kurs_baslama_ilk" name="kurs_baslama_ilk" value="{{ $filters['kurs_baslama_ilk'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="kurs_baslama_son">Kurs Başlama Tarihi (Son)</label>
                <input type="date" id="kurs_baslama_son" name="kurs_baslama_son" value="{{ $filters['kurs_baslama_son'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="kurs_bitis_ilk">Kurs Bitiş Tarihi (İlk)</label>
                <input type="date" id="kurs_bitis_ilk" name="kurs_bitis_ilk" value="{{ $filters['kurs_bitis_ilk'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="kurs_bitis_son">Kurs Bitiş Tarihi (Son)</label>
                <input type="date" id="kurs_bitis_son" name="kurs_bitis_son" value="{{ $filters['kurs_bitis_son'] ?? '' }}" class="form-control">
            </div>
        </div>

        <div class="filter-actions">
            <button type="button" class="more-filters-btn" data-more-filters aria-expanded="false">
                <span data-more-filters-label>Daha fazla filtre</span>
                <svg class="more-filters-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="filter-actions-right">
                <x-back-button id="kurslar-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

{{-- Tablo Kartı --}}
<div class="card table-card" id="kurslar-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kurs Kayıtları</div>
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
            <x-excel-export
                :href="route('kurslar.export', request()->query())"
                id="kurslar-excel-link"
            />
        </div>
    </div>

    <div id="kurslar-results">
        @include('kurslar._results')
    </div>
</div>
@endsection

@push('scripts')
<script>
    function closeOtherMenus() {
        document.querySelectorAll('[data-searchable-select]').forEach(function (select) {
            select.classList.remove('open');
            select.querySelector('[data-select-dropdown]')?.classList.remove('open');
        });
        document.querySelectorAll('[data-row-actions].is-open').forEach(function (wrap) {
            wrap.classList.remove('is-open');
            wrap.querySelector('[data-action-toggle]')?.setAttribute('aria-expanded', 'false');
            const dropdown = wrap.querySelector('[data-action-dropdown]');
            if (dropdown) {
                dropdown.hidden = true;
                dropdown.classList.remove('is-dropup');
                dropdown.style.top = '';
                dropdown.style.bottom = '';
                dropdown.style.left = '';
                dropdown.style.right = '';
                dropdown.style.position = '';
            }
        });
        document.querySelectorAll('.table-wrapper.has-open-action-menu').forEach(function (el) {
            el.classList.remove('has-open-action-menu');
        });
    }

    function toggleColumnDropdown(event) {
        event.stopPropagation();
        closeOtherMenus();
        document.getElementById('gunlerDropdown')?.classList.remove('open');
        const dropdown = document.getElementById('columnDropdown');
        const toggle = document.getElementById('columnPickerToggle');
        const picker = document.querySelector('[data-column-picker]');
        const willOpen = !dropdown?.classList.contains('open');
        dropdown?.classList.toggle('open', willOpen);
        picker?.classList.toggle('is-open', willOpen);
        toggle?.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    function toggleGunlerDropdown(event) {
        event.stopPropagation();
        closeOtherMenus();
        document.getElementById('columnDropdown')?.classList.remove('open');
        document.getElementById('gunlerDropdown').classList.toggle('open');
    }

    function updateGunlerDisplay() {
        const checked = document.querySelectorAll('#gunlerDropdown input:checked').length;
        document.getElementById('gunlerDisplay').textContent = checked ? (checked + ' gün seçili') : 'Tümü';
    }

    document.getElementById('columnDropdown')?.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.getElementById('gunlerSelect')?.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        document.getElementById('columnDropdown')?.classList.remove('open');
        document.querySelector('[data-column-picker]')?.classList.remove('is-open');
        document.getElementById('columnPickerToggle')?.setAttribute('aria-expanded', 'false');
        document.getElementById('gunlerDropdown')?.classList.remove('open');
    });
</script>
@endpush
