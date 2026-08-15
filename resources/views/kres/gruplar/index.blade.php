@extends('layouts.admin')

@section('title', 'Gruplar')

@php
    $allColumns = [
        'ad' => 'Ad',
        'okul' => 'Okul',
        'donem' => 'Dönem',
        'yas' => 'Yaş Aralığı',
        'kontenjan' => 'Kontenjan',
        'yedek' => 'Yedek K.',
        'cinsiyet' => 'Cinsiyet',
        'durum' => 'Durum',
        'olusturma' => 'Oluşturma Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'okul', 'donem', 'yas', 'kontenjan', 'cinsiyet', 'durum', 'olusturma', 'islemler'];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kreş Yönetimi</p>
        <h1 class="page-title">Gruplar</h1>
        <p class="page-subtitle">Okul ve döneme bağlı yaş gruplarını listeleyin, oluşturun ve düzenleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-cta-button type="button" icon="plus" data-entity-modal-open>Yeni Grup</x-cta-button>
    </div>
</div>

<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Grup adına, okula, döneme ve durumuna göre daraltın.</p>
        </div>
    </div>

    <form id="kres-gruplar-filter-form" method="GET" action="{{ route('kres.gruplar.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="q">Grup Adı</label>
                <div
                    class="input-with-mode"
                    data-search-mode
                    data-mode-param="q_mode"
                    data-mode-storage="kres_grup_ad_search_mode_v2"
                >
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Ara..."
                        class="form-control input-with-mode-control"
                        autocomplete="off"
                    >
                    <input type="hidden" name="q_mode" value="{{ $filters['q_mode'] ?? 'contains' }}" data-mode-value>
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
                <label>Okul</label>
                <x-searchable-select
                    name="okul_id"
                    placeholder="Tüm Okullar"
                    :value="$filters['okul_id'] ?? ''"
                    :options="$okullar->map(fn ($okul) => ['value' => $okul->id, 'label' => $okul->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Dönem</label>
                <x-searchable-select
                    name="donem_id"
                    placeholder="Tüm Dönemler"
                    empty-value="tumu"
                    :value="$filters['donem_id'] ?? ''"
                    :options="$donemler->map(fn ($donem) => ['value' => $donem->id, 'label' => $donem->ad])->all()"
                    data-reset-value="{{ $varsayilanDonemId ?? 'tumu' }}"
                />
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
                <x-back-button id="kres-gruplar-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

<div class="card table-card" id="kres-gruplar-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Grup Kayıtları</div>
            <p class="table-subtitle">Sütunları sürükleyerek sıralayabilir, görünürlüğü değiştirebilirsiniz.</p>
        </div>
        <div class="flex items-center gap-2.5">
            @include('kres.tanim._column-picker')
            <x-excel-export
                :href="route('kres.gruplar.export', request()->query())"
                id="kres-gruplar-excel-link"
            />
        </div>
    </div>

    <div id="kres-gruplar-results">
        @include('kres.gruplar._results')
    </div>
</div>

@include('kres.gruplar._form-modal')
@endsection

@include('kres.tanim._column-script')
