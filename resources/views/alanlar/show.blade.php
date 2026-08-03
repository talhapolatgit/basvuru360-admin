@extends('layouts.admin')

@section('title', $alan->ad)

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kurs Yönetimi</p>
        <h1 class="page-title">{{ $alan->ad }}</h1>
        <p class="page-subtitle">Alan bilgilerini görüntüleyin ve bu alana bağlı aktif kursları inceleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('alanlar.index') }}">Alanlara Dön</x-back-button>
        <button
            type="button"
            class="btn-cta"
            data-entity-edit
            data-update-url="{{ route('alanlar.update', $alan) }}"
            data-ad="{{ $alan->ad }}"
            data-aktif="{{ $alan->aktif ? '1' : '0' }}"
        >
            <span class="btn-cta-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </span>
            <span class="btn-cta-text">Düzenle</span>
        </button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Durum</div>
        <div class="stat-value" style="font-size:16px;">
            @if ($alan->aktif)
                <span class="status status-aktif">Aktif</span>
            @else
                <span class="status status-hazirlik">Pasif</span>
            @endif
        </div>
    </div>
    @yetki('kurs.goruntule')
    <a href="{{ route('kurslar.index', ['alan_id' => $alan->id, 'durum' => 'aktif']) }}" class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($aktifKursSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($aktifKursSayisi) }}</div>
    </div>
    @endyetki
    @yetki('kurs.goruntule')
    <a href="{{ route('kurslar.index', ['alan_id' => $alan->id, 'durum' => 'tumu']) }}" class="stat-card">
        <div class="stat-label">Toplam Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($toplamKursSayisi) }}</div>
    </a>
    @else
    <div class="stat-card">
        <div class="stat-label">Toplam Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($toplamKursSayisi) }}</div>
    </div>
    @endyetki
    @yetki('brans.goruntule')
    <a href="{{ route('branslar.index', ['alan_id' => $alan->id, 'durum' => 'tumu']) }}" class="stat-card">
        <div class="stat-label">Branş Sayısı</div>
        <div class="stat-value">{{ number_format($bransSayisi) }}</div>
    </a>
    @else
    <div class="stat-card">
        <div class="stat-label">Branş Sayısı</div>
        <div class="stat-value">{{ number_format($bransSayisi) }}</div>
    </div>
    @endyetki
</div>

@php
    $alanKurslarColumns = [
        'kurs_no' => 'Kurs No',
        'merkez' => 'Merkez',
        'brans' => 'Branş',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'kontenjan' => 'Kontenjan',
        'kayit' => 'Kayıt',
        'durum' => 'Durum',
    ];
    $alanKurslarOrder = array_keys($alanKurslarColumns);
@endphp
<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Aktif Kurslar</div>
            <p class="table-subtitle">Bu alana bağlı, şu anda aktif durumda olan kurslar.</p>
        </div>
        <x-detail-table-tools :columns="$alanKurslarColumns" :visible="$alanKurslarOrder" excel-name="alan-aktif-kurslar" />
    </div>

    <div class="table-wrapper">
        <table
            class="data-table"
            data-detail-table="alan_kurslar_cols"
            data-default-order='@json($alanKurslarOrder)'
            data-default-visible='@json($alanKurslarOrder)'
        >
            <thead>
                <tr>
                    @foreach ($alanKurslarColumns as $key => $label)
                        <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($kurslar as $kurs)
                    <tr>
                        <td data-column="kurs_no" data-export-value="{{ $kurs->kurs_no }}"><a href="{{ route('kurslar.show', $kurs) }}" class="kurs-no">{{ $kurs->kurs_no }}</a></td>
                        <td data-column="merkez">{{ $kurs->merkez?->ad }}</td>
                        <td data-column="brans">{{ $kurs->brans?->ad }}</td>
                        <td data-column="baslama" data-sort-value="{{ $kurs->kurs_baslama_tarihi?->toDateString() }}">{{ $kurs->kurs_baslama_tarihi?->format('d.m.Y') }}</td>
                        <td data-column="bitis" data-sort-value="{{ $kurs->kurs_bitis_tarihi?->toDateString() }}">{{ $kurs->kurs_bitis_tarihi?->format('d.m.Y') }}</td>
                        <td data-column="kontenjan">{{ $kurs->kontenjan }}</td>
                        <td data-column="kayit">{{ $kurs->kayit_sayisi }}</td>
                        <td data-column="durum" data-export-value="{{ $kurs->durum?->label() }}">
                            @if ($kurs->durum)
                                <span class="status status-{{ $kurs->durum->value }}">{{ $kurs->durum->label() }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
                                </div>
                                <div class="empty-state-title">Aktif kurs bulunamadı</div>
                                <p class="empty-state-text">Bu alana bağlı aktif durumda bir kurs yok.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('alanlar._form-modal')
@endsection
