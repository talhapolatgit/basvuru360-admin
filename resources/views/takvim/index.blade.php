@extends('layouts.admin')

@section('title', 'Takvim')

@php
    $egitmen = $egitmen ?? null;
    $merkez = $merkez ?? null;
    $etkinlikGorebilir = $etkinlikGorebilir ?? false;
    $ay = $ay ?? now()->format('Y-m');
    $pdfParams = $egitmen ? ['ogretmen' => $egitmen->id] : ($merkez ? ['merkez' => $merkez->id] : []);
    $dataParams = array_filter([
        'ogretmen' => $egitmen?->id,
        'merkez' => $merkez?->id,
    ]);
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Genel Bakış</p>
        <h1 class="page-title">Takvim</h1>
        @if ($egitmen)
            <p class="page-subtitle">{{ trim($egitmen->ad.' '.$egitmen->soyad) }} eğitmeninin aktif kurs ders takvimi.</p>
        @elseif ($merkez)
            <p class="page-subtitle">{{ $merkez->ad }} merkezinin aktif kurs ve etkinlik takvimi.</p>
        @else
            <p class="page-subtitle">Aktif kurs dersleri ve etkinlikleri tek ekrandan görüntüleyin.</p>
        @endif
    </div>
    @if ($egitmen)
        <div class="flex items-center gap-2">
            <x-back-button :href="route('egitmenler.show', $egitmen)" class="btn-back-sm">Eğitmene Dön</x-back-button>
        </div>
    @elseif ($merkez)
        <div class="flex items-center gap-2">
            <x-back-button :href="route('merkezler.show', $merkez)" class="btn-back-sm">Merkeze Dön</x-back-button>
        </div>
    @endif
</div>

<div
    class="lesson-tab-panel is-active"
    data-takvim-sayfa
    data-takvim-ay="{{ $ay }}"
    data-takvim-items='@json($takvimJson)'
    data-takvim-data-url="{{ route('takvim.data', $dataParams) }}"
    data-takvim-pdf-base="{{ route('takvim.pdf', $pdfParams) }}"
    data-takvim-etkinlik="{{ $etkinlikGorebilir ? '1' : '0' }}"
>
    <div class="takvim-toolbar">
        <div class="takvim-view-toggle" role="group" aria-label="Görünüm">
            <button type="button" class="takvim-view-btn" data-takvim-view="liste">Liste</button>
            <button type="button" class="takvim-view-btn is-active" data-takvim-view="aylik">Takvim</button>
        </div>
        <div class="takvim-aylik-header takvim-toolbar-month">
            <button type="button" class="takvim-nav-btn" data-takvim-prev aria-label="Önceki ay">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <h2 class="takvim-aylik-title" data-takvim-month-label></h2>
            <button type="button" class="takvim-nav-btn" data-takvim-next aria-label="Sonraki ay">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <div class="takvim-toolbar-meta" data-takvim-meta></div>
    </div>

    <div class="takvim-body" data-takvim-body>
        <div class="card table-card lesson-table-card" data-takvim-liste hidden>
            <div class="empty-state" data-takvim-liste-empty hidden>
                <div class="empty-state-title">Takvim boş</div>
                <p class="empty-state-text" data-takvim-liste-empty-text>
                    Gösterilecek kayıt bulunmuyor.
                </p>
            </div>
            <div class="table-wrapper" data-takvim-liste-table hidden>
                <table class="data-table" id="takvim-sayfa-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Gün</th>
                            <th>Saat</th>
                            <th>Tür</th>
                            <th>Ad</th>
                            <th>Merkez</th>
                            <th>Detay</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody data-takvim-liste-body></tbody>
                </table>
            </div>
        </div>

        <div class="card form-section-card takvim-aylik-card" data-takvim-aylik>
            <div class="takvim-aylik-grid" data-takvim-grid></div>
            <div class="takvim-aylik-legend">
                <span><i class="takvim-dot has-ders"></i> Kayıt var</span>
                <span><i class="takvim-dot is-today-dot"></i> Bugün</span>
            </div>
            <div class="takvim-gun-detay" data-takvim-day-detail hidden>
                <h3 class="takvim-gun-detay-title" data-takvim-day-title></h3>
                <ul class="takvim-gun-detay-list" data-takvim-day-list></ul>
            </div>
        </div>
    </div>

    <div class="table-footer lesson-table-footer takvim-pdf-footer">
        <div class="takvim-visibility" data-takvim-visibility role="group" aria-label="Takvim görünürlüğü">
            <label class="takvim-visibility-item">
                <input type="checkbox" data-takvim-toggle="kurs" checked>
                <span class="takvim-visibility-swatch" style="background:#3699ff" aria-hidden="true"></span>
                <span>Kurslar</span>
            </label>
            @if ($etkinlikGorebilir)
                <label class="takvim-visibility-item">
                    <input type="checkbox" data-takvim-toggle="etkinlik" checked>
                    <span class="takvim-visibility-swatch" style="background:#f1416c" aria-hidden="true"></span>
                    <span>Etkinlikler</span>
                </label>
            @endif
        </div>
        <div class="table-footer-right">
            <a
                href="{{ route('takvim.pdf', array_merge($pdfParams, ['ay' => $ay])) }}"
                class="btn-pdf btn-pdf-sm"
                data-takvim-pdf
                target="_blank"
                rel="noopener"
            >
                <span class="btn-pdf-mark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6"/>
                        <path d="M10 13h4"/>
                        <path d="M10 17h4"/>
                        <path d="M10 9h1"/>
                    </svg>
                </span>
                <span class="btn-pdf-text">PDF</span>
            </a>
        </div>
    </div>
</div>
@endsection
