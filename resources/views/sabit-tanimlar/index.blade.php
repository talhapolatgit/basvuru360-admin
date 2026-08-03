@extends('layouts.admin')

@section('title', 'Sabit Tanımlar')

@php
    $indexRoute = $indexRoute ?? 'sabit-tanimlar.index';
    $pageEyebrow = $pageEyebrow ?? 'Kurs Yönetimi';
    $pageTitle = $pageTitle ?? 'Sabit Tanımlar';
    $pageSubtitle = $pageSubtitle ?? 'Evrak tipi, iptal gerekçesi ve kurum tanımlarını tek ekrandan yönetin.';
    $activeTab = $activeTab ?? 'evrak-tipleri';
    $tabs = $tabs ?? \App\Http\Controllers\SabitTanimController::visibleTabs();
    $counts = $counts ?? [];
    $statusSiniflari = $statusSiniflari ?? \App\Http\Controllers\SabitTanimController::STATUS_SINIFLARI;
    $formTabs = \App\Http\Controllers\SabitTanimController::FORM_TABS;
    $createLabels = [
        'kurs-tipleri' => 'Yeni Kurs Tipi',
        'etkinlik-tipleri' => 'Yeni Etkinlik Tipi',
        'evrak-tipleri' => 'Yeni Evrak Tipi',
        'basvuru-durumlari' => 'Yeni Başvuru Durumu',
        'etkinlik-basvuru-durumlari' => 'Yeni Etkinlik Başvuru Durumu',
        'basari-durumlari' => 'Yeni Başarı Durumu',
        'iptal-gerekceleri' => 'Yeni İptal Gerekçesi',
        'kurumlar' => 'Yeni Kurum',
    ];
    $openAttrs = [
        'kurs-tipleri' => 'data-kurs-tipleri-modal-open',
        'etkinlik-tipleri' => 'data-etkinlik-tipleri-modal-open',
        'evrak-tipleri' => 'data-evrak-tipleri-modal-open',
        'basvuru-durumlari' => 'data-basvuru-durumlari-modal-open',
        'etkinlik-basvuru-durumlari' => 'data-etkinlik-basvuru-durumlari-modal-open',
        'basari-durumlari' => 'data-basari-durumlari-modal-open',
        'iptal-gerekceleri' => 'data-iptal-gerekceleri-modal-open',
        'kurumlar' => 'data-kurumlar-modal-open',
    ];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">{{ $pageEyebrow }}</p>
        <h1 class="page-title">{{ $pageTitle }}</h1>
        <p class="page-subtitle">{{ $pageSubtitle }}</p>
    </div>
</div>

<div class="card sabit-tanimlar-card" data-sabit-tanimlar>
    <div class="lesson-tabs sabit-tanimlar-tabs" data-sabit-tabs role="tablist">
        @foreach ($tabs as $tabKey => $tabLabel)
            <button
                type="button"
                class="lesson-tab {{ $activeTab === $tabKey ? 'is-active' : '' }}"
                data-sabit-tab="{{ $tabKey }}"
                role="tab"
                aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
            >
                <span class="sabit-tab-label">{{ $tabLabel }}</span>
                <span class="sabit-tab-count">{{ number_format($counts[$tabKey] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    @foreach ($tabs as $tabKey => $tabLabel)
        @php
            $isFormTab = in_array($tabKey, $formTabs, true);
            $tabData = $tabsData[$tabKey] ?? ['items' => collect(), 'sort' => '', 'direction' => 'asc', 'filters' => ['durum' => 'tumu']];
            $items = $tabData['items'] ?? collect();
            $sort = $tabData['sort'] ?? '';
            $direction = $tabData['direction'] ?? 'asc';
            $filters = $tabData['filters'] ?? ['durum' => 'tumu'];
        @endphp
        <div
            class="lesson-tab-panel sabit-tanimlar-panel {{ $activeTab === $tabKey ? 'is-active' : '' }}"
            data-sabit-panel="{{ $tabKey }}"
            role="tabpanel"
            @if ($activeTab !== $tabKey) hidden @endif
        >
            @if ($isFormTab)
                <div id="{{ $tabKey }}-results" data-sabit-results="{{ $tabKey }}">
                    @include('sabit-tanimlar.partials.'.$tabKey, [
                        'form' => ($formTabForms[$tabKey] ?? []),
                        'module' => $module ?? 'kurs',
                    ])
                </div>
            @else
                <div class="sabit-panel-toolbar">
                    <form
                        id="{{ $tabKey }}-filter-form"
                        method="GET"
                        action="{{ route($indexRoute) }}"
                        class="sabit-filter-form"
                        data-sabit-filter-form
                        data-tab="{{ $tabKey }}"
                    >
                        <input type="hidden" name="tab" value="{{ $tabKey }}">
                        <div class="sabit-filter-grid">
                            <div class="form-group">
                                <label for="{{ $tabKey }}-q">Ara</label>
                                <input
                                    type="text"
                                    id="{{ $tabKey }}-q"
                                    name="q"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Ad veya açıklama..."
                                    class="form-control"
                                    autocomplete="off"
                                >
                            </div>
                            <div class="form-group">
                                <label for="{{ $tabKey }}-durum">Durum</label>
                                <select id="{{ $tabKey }}-durum" name="durum" class="form-control" data-reset-value="tumu">
                                    <option value="tumu" @selected(($filters['durum'] ?? 'tumu') === 'tumu')>Tümü</option>
                                    <option value="aktif" @selected(($filters['durum'] ?? 'tumu') === 'aktif')>Aktif</option>
                                    <option value="pasif" @selected(($filters['durum'] ?? 'tumu') === 'pasif')>Pasif</option>
                                </select>
                            </div>
                            <div class="sabit-filter-actions">
                                <x-back-button type="button" icon="close" data-sabit-filter-clear>Temizle</x-back-button>
                                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
                            </div>
                        </div>
                    </form>

                    @yetki('sabit.olustur')
                    <button type="button" class="btn-cta" {{ $openAttrs[$tabKey] }}>
                        <span class="btn-cta-mark" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        </span>
                        <span class="btn-cta-text">{{ $createLabels[$tabKey] }}</span>
                    </button>
                    @endyetki
                </div>

                <div class="card table-card sabit-table-card" id="{{ $tabKey }}-table-card">
                    <div id="{{ $tabKey }}-results" data-sabit-results="{{ $tabKey }}">
                        @include('sabit-tanimlar.partials.'.$tabKey.'-results', [
                            'items' => $items,
                            'sort' => $sort,
                            'direction' => $direction,
                            'filters' => $filters,
                        ])
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>

@if (isset($tabs['kurs-tipleri']))
    @include('sabit-tanimlar.modals.kurs-tipleri')
@endif
@if (isset($tabs['etkinlik-tipleri']))
    @include('sabit-tanimlar.modals.etkinlik-tipleri')
@endif
@if (isset($tabs['evrak-tipleri']))
    @include('sabit-tanimlar.modals.evrak-tipleri')
@endif
@if (isset($tabs['basvuru-durumlari']))
    @include('sabit-tanimlar.modals.basvuru-durumlari')
@endif
@if (isset($tabs['etkinlik-basvuru-durumlari']))
    @include('sabit-tanimlar.modals.etkinlik-basvuru-durumlari')
@endif
@if (isset($tabs['basari-durumlari']))
    @include('sabit-tanimlar.modals.basari-durumlari')
@endif
@if (isset($tabs['iptal-gerekceleri']))
    @include('sabit-tanimlar.modals.iptal-gerekceleri')
@endif
@if (isset($tabs['kurumlar']))
    @include('sabit-tanimlar.modals.kurumlar')
@endif
@endsection
