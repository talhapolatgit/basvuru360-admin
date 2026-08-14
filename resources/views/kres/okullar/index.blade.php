@extends('layouts.admin')

@section('title', 'Okullar')

@php
    $allColumns = [
        'ad' => 'Ad',
        'adres' => 'Adres',
        'telefon' => 'Telefon',
        'grup_sayisi' => 'Grup Sayısı',
        'durum' => 'Durum',
        'olusturma' => 'Oluşturma Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'adres', 'telefon', 'grup_sayisi', 'durum', 'olusturma', 'islemler'];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kreş Yönetimi</p>
        <h1 class="page-title">Okullar</h1>
        <p class="page-subtitle">Kreş okullarını listeleyin, oluşturun ve düzenleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-cta-button type="button" icon="plus" data-entity-modal-open>Yeni Okul</x-cta-button>
    </div>
</div>

<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Okul adına, adrese ve durumuna göre daraltın.</p>
        </div>
    </div>

    <form id="kres-okullar-filter-form" method="GET" action="{{ route('kres.okullar.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="q">Okul Adı</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Ara..."
                    class="form-control"
                    autocomplete="off"
                >
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
                <x-back-button id="kres-okullar-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

<div class="card table-card" id="kres-okullar-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Okul Kayıtları</div>
            <p class="table-subtitle">Sütunları sürükleyerek sıralayabilir, görünürlüğü değiştirebilirsiniz.</p>
        </div>
        <div class="flex items-center gap-2.5">
            @include('kres.tanim._column-picker')
            <x-excel-export
                :href="route('kres.okullar.export', request()->query())"
                id="kres-okullar-excel-link"
            />
        </div>
    </div>

    <div id="kres-okullar-results">
        @include('kres.okullar._results')
    </div>
</div>

@include('kres.okullar._form-modal')
@endsection

@include('kres.tanim._column-script')
