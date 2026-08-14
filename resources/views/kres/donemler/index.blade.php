@extends('layouts.admin')

@section('title', 'Dönemler')

@php
    $allColumns = [
        'ad' => 'Ad',
        'baslangic' => 'Başlangıç',
        'bitis' => 'Bitiş',
        'grup_sayisi' => 'Grup Sayısı',
        'durum' => 'Durum',
        'olusturma' => 'Oluşturma Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'baslangic', 'bitis', 'grup_sayisi', 'durum', 'olusturma', 'islemler'];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kreş Yönetimi</p>
        <h1 class="page-title">Dönemler</h1>
        <p class="page-subtitle">Eğitim öğretim dönemlerini listeleyin, oluşturun ve düzenleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-cta-button type="button" icon="plus" data-entity-modal-open>Yeni Dönem</x-cta-button>
    </div>
</div>

<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Dönem adına ve durumuna göre daraltın.</p>
        </div>
    </div>

    <form id="kres-donemler-filter-form" method="GET" action="{{ route('kres.donemler.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="q">Dönem Adı</label>
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
                <x-back-button id="kres-donemler-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

<div class="card table-card" id="kres-donemler-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Dönem Kayıtları</div>
            <p class="table-subtitle">Sütunları sürükleyerek sıralayabilir, görünürlüğü değiştirebilirsiniz.</p>
        </div>
        <div class="flex items-center gap-2.5">
            @include('kres.tanim._column-picker')
            <x-excel-export
                :href="route('kres.donemler.export', request()->query())"
                id="kres-donemler-excel-link"
            />
        </div>
    </div>

    <div id="kres-donemler-results">
        @include('kres.donemler._results')
    </div>
</div>

@include('kres.donemler._form-modal')
@endsection

@include('kres.tanim._column-script')
