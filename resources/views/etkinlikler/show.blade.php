@extends('layouts.admin')

@section('title', $etkinlik->ad)

@php
    $user = auth()->user();
    $basvuruGorebilir = $user?->hasYetki('etkinlik_basvuru.goruntule') ?? false;
    $yoklamaGorebilir = $user?->hasYetki('etkinlik.yoklama_goruntule') ?? false;
    $mesajGorebilir = $user?->hasYetki('etkinlik.mesaj_goruntule') ?? false;
    $allowedTabs = ['detay'];
    if ($basvuruGorebilir) {
        $allowedTabs[] = 'basvurular';
    }
    if ($yoklamaGorebilir) {
        $allowedTabs[] = 'yoklama';
    }
    if ($mesajGorebilir) {
        $allowedTabs[] = 'mesajlar';
    }
    $activeTab = in_array($activeTab ?? 'detay', $allowedTabs, true) ? $activeTab : 'detay';
    $basvuruDurum = $basvuruDurum ?? 'tumu';
    $activeMesajKanal = in_array($activeMesajKanal ?? 'sms', ['sms', 'eposta'], true) ? $activeMesajKanal : 'sms';
@endphp

@section('content')
<div
    class="lesson-detail"
    data-etkinlik-detail
    @yetki('etkinlik_basvuru.goruntule')
    data-basvurular-url="{{ route('etkinlikler.basvurular', $etkinlik) }}"
    @endyetki
    @yetki('etkinlik.yoklama')
    data-yoklama-url="{{ route('etkinlikler.yoklama.save', $etkinlik) }}"
    @endyetki
    @yetki('etkinlik.yoklama_goruntule')
    data-yoklama-list-url="{{ route('etkinlikler.yoklama', $etkinlik) }}"
    @endyetki
    data-sorumlu-url="{{ route('etkinlikler.sorumlu.assign', $etkinlik) }}"
    @yetki('etkinlik.sms')
    data-sms-url="{{ route('etkinlikler.sms.send', $etkinlik) }}"
    data-sms-alicilar-url="{{ route('etkinlikler.sms.alicilar', $etkinlik) }}"
    @endyetki
    @yetki('etkinlik.eposta')
    data-eposta-url="{{ route('etkinlikler.eposta.send', $etkinlik) }}"
    data-eposta-alicilar-url="{{ route('etkinlikler.eposta.alicilar', $etkinlik) }}"
    @endyetki
>
    <div class="lesson-detail-header">
        <h1 class="lesson-detail-title">{{ $etkinlik->ad }}</h1>
    </div>

    <div class="lesson-tabs" role="tablist">
        <button type="button" class="lesson-tab {{ $activeTab === 'detay' ? 'is-active' : '' }}" data-lesson-tab="detay" role="tab" aria-selected="{{ $activeTab === 'detay' ? 'true' : 'false' }}">Etkinlik Detayları</button>
        @yetki('etkinlik_basvuru.goruntule')
        <button type="button" class="lesson-tab {{ $activeTab === 'basvurular' ? 'is-active' : '' }}" data-lesson-tab="basvurular" role="tab" aria-selected="{{ $activeTab === 'basvurular' ? 'true' : 'false' }}">Başvurular</button>
        @endyetki
        @yetki('etkinlik.yoklama_goruntule')
        <button type="button" class="lesson-tab {{ $activeTab === 'yoklama' ? 'is-active' : '' }}" data-lesson-tab="yoklama" role="tab" aria-selected="{{ $activeTab === 'yoklama' ? 'true' : 'false' }}">Yoklama</button>
        @endyetki
        @yetki('etkinlik.mesaj_goruntule')
        <button type="button" class="lesson-tab {{ $activeTab === 'mesajlar' ? 'is-active' : '' }}" data-lesson-tab="mesajlar" role="tab" aria-selected="{{ $activeTab === 'mesajlar' ? 'true' : 'false' }}">Mesajlar</button>
        @endyetki
    </div>

    {{-- Detay --}}
    <div class="lesson-tab-panel {{ $activeTab === 'detay' ? 'is-active' : '' }}" data-lesson-panel="detay" role="tabpanel">
        <div class="lesson-stat-grid">
            <div class="lesson-stat-card">
                <h3 class="lesson-stat-label">Aktif Başvurular</h3>
                <div class="lesson-stat-value">{{ number_format($basvuruOzet['aktif']) }}</div>
            </div>
            <div class="lesson-stat-card">
                <h3 class="lesson-stat-label">Kesin Kayıtlar</h3>
                <div class="lesson-stat-value">{{ number_format($basvuruOzet['kayit']) }}</div>
            </div>
            <div class="lesson-stat-card">
                <h3 class="lesson-stat-label">Kontenjan</h3>
                <div class="lesson-stat-value">{{ number_format($etkinlik->kontenjan) }}</div>
            </div>
            <div class="lesson-stat-card">
                <h3 class="lesson-stat-label">Yedek</h3>
                <div class="lesson-stat-value">{{ number_format($etkinlik->yedek_kontenjan) }}</div>
            </div>
        </div>

        <div class="lesson-info-grid">
            <div class="lesson-info-card">
                <div class="lesson-info-label">Etkinlik No</div>
                <div class="lesson-info-value">{{ $etkinlik->etkinlik_no }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Tip</div>
                <div class="lesson-info-value">{{ $etkinlik->etkinlikTipi?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Merkez</div>
                <div class="lesson-info-value">{{ $etkinlik->merkez?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kurum</div>
                <div class="lesson-info-value">
                    @if ($etkinlik->kurumlar->isNotEmpty())
                        <div class="rol-badge-list">
                            @foreach ($etkinlik->kurumlar as $kurum)
                                <span class="status status-hazirlik">{{ $kurum->ad }}</span>
                            @endforeach
                        </div>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Tarihler</div>
                <div class="lesson-info-value">
                    {{ $etkinlik->baslangic_tarihi?->format('d.m.Y') ?? '—' }}
                    -
                    {{ $etkinlik->bitis_tarihi?->format('d.m.Y') ?? '—' }}
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Başvuru Dönemi</div>
                <div class="lesson-info-value">
                    {{ $etkinlik->basvuru_baslama_tarihi?->format('d.m.Y H:i') ?? '—' }}
                    -
                    {{ $etkinlik->basvuru_bitis_tarihi?->format('d.m.Y H:i') ?? '—' }}
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Sorumlular</div>
                <div class="lesson-info-value lesson-info-value-with-action">
                    <div class="lesson-ogretmen-name" data-sorumlu-names>{{ $etkinlik->sorumluAdlari() }}</div>
                    @yetki('etkinlik.sorumlu_ata')
                    <button
                        type="button"
                        class="lesson-yayin-btn {{ $etkinlik->sorumlular->isNotEmpty() ? 'is-change' : 'is-publish' }}"
                        data-sorumlu-modal-open
                    >
                        {{ $etkinlik->sorumlular->isNotEmpty() ? 'Düzenle' : 'Sorumlu Ata' }}
                    </button>
                    @endyetki
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <svg class="lesson-info-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    Koşullar
                </div>
                <div class="lesson-info-value">{{ $kosullarOzeti }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Gerekli Evraklar</div>
                <div class="lesson-info-value">
                    @if ($etkinlik->evrakTipleri->isEmpty())
                        —
                    @else
                        {{ $etkinlik->evrakTipleri->pluck('ad')->implode(', ') }}
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <svg class="lesson-info-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Durum
                </div>
                <div class="lesson-info-value">
                    @if ($etkinlik->durum)
                        <span class="status status-{{ $etkinlik->durum->value }}">{{ $etkinlik->durum->label() }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Başvuru Durumu</div>
                <div class="lesson-info-value lesson-info-value-with-action">
                    <div class="lesson-yayin-status" data-yayin-status>
                        <span class="status {{ $etkinlik->basvuruDurumuStatusClass() }}">{{ $etkinlik->basvuruDurumuLabel() }}</span>
                    </div>
                    @yetki('etkinlik.yayinla')
                    <button
                        type="button"
                        class="lesson-yayin-btn {{ $etkinlik->onlinede_yayinlansin ? 'is-unpublish' : 'is-publish' }}"
                        data-yayin-modal-open
                        data-action="{{ $etkinlik->onlinede_yayinlansin ? 'unpublish' : 'publish' }}"
                        data-can-publish="{{ ($yayinlanabilir ?? false) ? '1' : '0' }}"
                    >
                        @if ($etkinlik->onlinede_yayinlansin)
                            Yayından Kaldır
                        @else
                            Yayına Al
                        @endif
                    </button>
                    @endyetki
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">İkamet Dışı Kontenjan</div>
                <div class="lesson-info-value">{{ number_format($etkinlik->ikamet_disi_kontenjan) }}</div>
            </div>
            <div class="lesson-info-card" style="grid-column: 1 / -1;">
                <div class="lesson-info-label">Açıklama</div>
                <div class="lesson-info-value">{{ $etkinlik->aciklama ?: '—' }}</div>
            </div>
        </div>

        <div class="lesson-actions-block">
            <div class="lesson-actions-card">
                <div class="lesson-actions-card-head">
                    <h3 class="lesson-actions-title">İşlemler</h3>
                    <p class="lesson-actions-subtitle">Etkinlik ile ilgili hızlı işlemler</p>
                </div>
                <div class="lesson-actions-grid">
                    @yetki('etkinlik.guncelle')
                        <a href="{{ route('etkinlikler.edit', $etkinlik) }}" class="lesson-action-btn lesson-action-btn-primary">
                            <span class="lesson-action-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </span>
                            <span class="lesson-action-copy">
                                <span class="lesson-action-label">Düzenle</span>
                                <span class="lesson-action-hint">Etkinlik bilgilerini güncelle</span>
                            </span>
                        </a>
                    @else
                        <button type="button" class="lesson-action-btn" disabled title="Bu işlem için yetkiniz yok">
                            <span class="lesson-action-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </span>
                            <span class="lesson-action-copy">
                                <span class="lesson-action-label">Düzenle</span>
                                <span class="lesson-action-hint">Etkinlik bilgilerini güncelle</span>
                            </span>
                        </button>
                    @endyetki
                    <button
                        type="button"
                        class="lesson-action-btn {{ auth()->user()?->hasYetki('etkinlik.sms') ? 'lesson-action-btn-primary' : '' }}"
                        @yetki('etkinlik.sms') data-sms-modal-open @else disabled title="Bu işlem için yetkiniz yok" @endyetki
                    >
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">SMS Gönder</span>
                            <span class="lesson-action-hint">Başvuranlara toplu gönder</span>
                        </span>
                    </button>
                    <button
                        type="button"
                        class="lesson-action-btn {{ auth()->user()?->hasYetki('etkinlik.eposta') ? 'lesson-action-btn-primary' : '' }}"
                        @yetki('etkinlik.eposta') data-eposta-modal-open @else disabled title="Bu işlem için yetkiniz yok" @endyetki
                    >
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">E-Posta Gönder</span>
                            <span class="lesson-action-hint">Başvuranlara toplu gönder</span>
                        </span>
                    </button>
                    <a href="{{ route('etkinlikler.index') }}" class="lesson-action-btn lesson-action-btn-muted">
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">Listeye Dön</span>
                            <span class="lesson-action-hint">Etkinlik listesine git</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Başvurular --}}
    @yetki('etkinlik_basvuru.goruntule')
    <div
        class="lesson-tab-panel {{ $activeTab === 'basvurular' ? 'is-active' : '' }}"
        data-lesson-panel="basvurular"
        data-basvuru-panel
        data-basvuru-url="{{ route('etkinlikler.basvurular', $etkinlik) }}"
        data-basvuru-durum="{{ $basvuruDurum }}"
        role="tabpanel"
    >
        <div class="basvuru-toolbar">
            <div class="basvuru-filters" data-basvuru-filters>
                <button
                    type="button"
                    class="basvuru-filter {{ $basvuruDurum === 'tumu' ? 'is-active' : '' }}"
                    data-basvuru-filter="tumu"
                >Tümü</button>
                @foreach ($basvuruDurumlari as $filtreDurum)
                    <button
                        type="button"
                        class="basvuru-filter {{ $basvuruDurum === $filtreDurum->kod ? 'is-active' : '' }}"
                        data-basvuru-filter="{{ $filtreDurum->kod }}"
                    >{{ $filtreDurum->ad }}</button>
                @endforeach
            </div>
            <div class="basvuru-toolbar-actions">
                <div class="basvuru-toolbar-meta" data-basvuru-total>Toplam Kayıt: —</div>
                <div class="column-picker" data-basvuru-column-picker>
                    <button
                        type="button"
                        class="btn-columns btn-columns-sm"
                        id="basvuruColumnPickerToggle"
                        data-basvuru-column-toggle
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="basvuruColumnDropdown"
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
                    <div class="column-dropdown" id="basvuruColumnDropdown" role="menu" aria-labelledby="basvuruColumnPickerToggle">
                        <div class="column-dropdown-header">
                            <span>Görünür sütunlar</span>
                            <span class="column-dropdown-hint">Sürükleyerek sıralayın</span>
                        </div>
                        <div class="column-dropdown-list">
                            @foreach ($basvuruColumns as $key => $label)
                                @if ($key === 'islemler')
                                    @continue
                                @endif
                                <label class="column-option" data-column="{{ $key }}">
                                    <input
                                        type="checkbox"
                                        class="column-toggle"
                                        data-column="{{ $key }}"
                                        @checked(in_array($key, $basvuruDefaultVisible, true))
                                    >
                                    <span class="column-option-check" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span>
                                    <span class="column-option-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="column-dropdown-footer">
                            <button
                                type="button"
                                class="column-save-btn save-prefs-btn"
                                id="basvuru-dropdown-save-column-prefs"
                                data-basvuru-column-save
                                title="Kolon düzenlemelerini kaydet"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                Kaydet
                            </button>
                            <button type="button" class="column-reset-btn" id="basvuru-reset-column-prefs" data-basvuru-column-reset>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                </svg>
                                Sıfırla
                            </button>
                        </div>
                    </div>
                </div>
                @yetki('etkinlik_basvuru.export')
                <x-excel-export
                    :href="route('etkinlikler.basvurular.export', $etkinlik)"
                    id="basvuru-excel-link"
                    class="btn-excel-sm"
                    data-basvuru-excel
                />
                @endyetki
                @yetki('etkinlik_basvuru.olustur')
                <x-cta-button
                    :href="route('etkinlik-basvurulari.create', ['etkinlik_id' => $etkinlik->id])"
                    class="btn-cta-sm"
                >Yeni Başvuru</x-cta-button>
                @endyetki
            </div>
        </div>

        <div class="card table-card lesson-table-card" data-basvuru-content>
            <div class="empty-state basvuru-placeholder">
                <div class="empty-state-title">Başvurular</div>
                <p class="empty-state-text">Bu sekme açıldığında başvurular yüklenecek.</p>
            </div>
        </div>
    </div>
    @endyetki

    {{-- Yoklama --}}
    @yetki('etkinlik.yoklama_goruntule')
    <div
        class="lesson-tab-panel {{ $activeTab === 'yoklama' ? 'is-active' : '' }}"
        data-lesson-panel="yoklama"
        data-yoklama-panel
        data-yoklama-filtre="tumu"
        role="tabpanel"
    >
        <div class="basvuru-toolbar">
            <div class="basvuru-filters" role="group" aria-label="Yoklama filtresi">
                <button type="button" class="basvuru-filter is-active" data-yoklama-filter="tumu">Tümü</button>
                <button type="button" class="basvuru-filter" data-yoklama-filter="katildi">Katılanlar</button>
                <button type="button" class="basvuru-filter" data-yoklama-filter="katilmadi">Katılmayanlar</button>
                <button type="button" class="basvuru-filter" data-yoklama-filter="alinmayan">Yoklama Alınmayanlar</button>
            </div>
            <div class="basvuru-toolbar-actions">
                <div class="basvuru-toolbar-meta" data-yoklama-total>Toplam Kayıt: —</div>
                @yetki('etkinlik.export')
                <x-excel-export
                    :href="route('etkinlikler.yoklama.export', $etkinlik)"
                    id="yoklama-excel-link"
                    class="btn-excel-sm"
                    data-yoklama-excel
                />
                @endyetki
            </div>
        </div>

        <div data-yoklama-content>
            @include('etkinlikler._yoklama')
        </div>
    </div>
    @endyetki

    {{-- Mesajlar --}}
    @yetki('etkinlik.mesaj_goruntule')
    <div
        class="lesson-tab-panel {{ $activeTab === 'mesajlar' ? 'is-active' : '' }}"
        data-lesson-panel="mesajlar"
        data-mesajlar-url="{{ route('etkinlikler.mesajlar', $etkinlik) }}"
        role="tabpanel"
    >
        <div data-mesajlar-content>
            @include('etkinlikler._mesajlar_panel', [
                'mesajKanal' => $activeMesajKanal,
            ])
        </div>
    </div>
    @endyetki
</div>

{{-- Sorumlu modal --}}
@yetki('etkinlik.sorumlu_ata')
<div class="confirm-modal" id="sorumlu-modal" hidden>
    <div class="confirm-modal-backdrop" data-sorumlu-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sorumlu-modal-title">
        <div class="confirm-modal-header">
            <h3 id="sorumlu-modal-title" class="confirm-modal-title">
                {{ $etkinlik->sorumlular->isNotEmpty() ? 'Sorumluları Düzenle' : 'Sorumlu Ata' }}
            </h3>
            <button type="button" class="confirm-modal-x" data-sorumlu-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('etkinlikler.sorumlu.assign', $etkinlik) }}" class="ogretmen-modal-form" data-sorumlu-form>
            @csrf
            @method('PUT')
            <div class="confirm-modal-body">
                <div class="evrak-picker" data-sorumlu-picker>
                    <div class="evrak-add-row">
                        <div class="form-group grow" style="margin:0;">
                            <label>Sorumlu ekle</label>
                            <x-searchable-select
                                name="sorumlu_picker"
                                placeholder="Kullanıcı seçin"
                                value=""
                                :options="$kullanicilar->map(fn ($u) => ['value' => $u->id, 'label' => $u->tam_adi])->all()"
                            />
                        </div>
                        <x-back-button type="button" icon="plus" data-sorumlu-add>Ekle</x-back-button>
                    </div>
                    <div class="evrak-list" data-sorumlu-list>
                        @forelse ($etkinlik->sorumlular->sortBy('id') as $sorumlu)
                            <div class="evrak-item" data-sorumlu-item data-id="{{ $sorumlu->id }}">
                                <input type="hidden" name="sorumlu_ids[]" value="{{ $sorumlu->id }}">
                                <span class="evrak-item-label">{{ $sorumlu->tam_adi }}</span>
                                <button type="button" class="evrak-item-remove" data-sorumlu-remove title="Kaldır" aria-label="Kaldır">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @empty
                            <p class="evrak-empty" data-sorumlu-empty>Henüz sorumlu atanmadı.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-sorumlu-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endyetki

{{-- SMS alıcı detay modalı --}}
<div class="confirm-modal" id="sms-alicilar-detay-modal" hidden>
    <div class="confirm-modal-backdrop" data-sms-alicilar-detay-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sms-alicilar-detay-title">
        <div class="confirm-modal-header">
            <h3 id="sms-alicilar-detay-title" class="confirm-modal-title">SMS Alıcıları</h3>
            <button type="button" class="confirm-modal-x" data-sms-alicilar-detay-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <div class="sms-alicilar-detay-list" data-sms-alicilar-detay-list></div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-sms-alicilar-detay-close>Kapat</button>
        </div>
    </div>
</div>

{{-- SMS gönder modalı --}}
<div
    class="confirm-modal"
    id="sms-modal"
    hidden
    data-sms-alicilar-url="{{ route('etkinlikler.sms.alicilar', $etkinlik) }}"
>
    <div class="confirm-modal-backdrop" data-sms-modal-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="sms-modal-title">
        <div class="confirm-modal-header">
            <h3 id="sms-modal-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-sms-modal-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <div class="sms-filter-row" role="group" aria-label="Başvuru durumu filtresi">
                <button type="button" class="basvuru-filter is-active" data-sms-filter="tumu">Tümü</button>
                @foreach ($basvuruDurumlari as $filtreDurum)
                    <button
                        type="button"
                        class="basvuru-filter"
                        data-sms-filter="{{ $filtreDurum->kod }}"
                    >{{ $filtreDurum->ad }}</button>
                @endforeach
            </div>

            <div class="form-group">
                <div class="sms-alicilar-head">
                    <label>Alıcılar</label>
                    <div class="sms-alicilar-head-actions">
                        <span class="sms-alicilar-count" data-sms-alicilar-count>0 kişi</span>
                        <button type="button" class="sms-clear-all-btn" data-sms-clear-all title="Tümünü Temizle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                <path d="M10 11v6"/>
                                <path d="M14 11v6"/>
                            </svg>
                            Tümünü Temizle
                        </button>
                    </div>
                </div>
                <div class="sms-alici-search-wrap" data-sms-search-wrap>
                    <input
                        type="search"
                        class="form-control"
                        data-sms-search
                        placeholder="Ad soyad veya T.C. kimlik no ile ara ve ekle..."
                        autocomplete="off"
                    >
                    <div class="sms-alici-search-results" data-sms-search-results hidden></div>
                </div>
                <div class="sms-alicilar-list" data-sms-alicilar-list>
                    <p class="sms-alicilar-empty" data-sms-alicilar-empty>Alıcılar yükleniyor...</p>
                </div>
            </div>

            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-sms-insert="{ad_soyad}"
                            title="İmleç konumuna ekler"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-sms-onizle
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, kurs kaydınız onaylandı."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-sms-modal-close>Vazgeç</button>
            <button
                type="button"
                class="btn btn-primary btn-wide"
                data-sms-send
                data-sms-url="{{ route('etkinlikler.sms.send', $etkinlik) }}"
            >Gönder</button>
        </div>
    </div>
</div>

{{-- SMS önizleme modalı --}}
<div class="confirm-modal" id="sms-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-sms-onizleme-close></div>
    <div class="confirm-modal-dialog sms-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="sms-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="sms-onizleme-title" class="confirm-modal-title">SMS Önizleme</h3>
            <button type="button" class="confirm-modal-x" data-sms-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body sms-onizleme-body">
            <p class="sms-onizleme-alici" data-sms-onizleme-alici></p>
            <div class="sms-phone" aria-hidden="true">
                <div class="sms-phone-frame">
                    <div class="sms-phone-notch"></div>
                    <div class="sms-phone-screen">
                        <div class="sms-phone-status">
                            <span>9:41</span>
                            <span>SMS</span>
                        </div>
                        <div class="sms-phone-thread">
                            <div class="sms-phone-bubble" data-sms-onizleme-mesaj></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-sms-onizleme-close>Kapat</button>
        </div>
    </div>
</div>

{{-- E-posta alıcı detay modalı --}}
<div class="confirm-modal" id="eposta-alicilar-detay-modal" hidden>
    <div class="confirm-modal-backdrop" data-eposta-alicilar-detay-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="eposta-alicilar-detay-title">
        <div class="confirm-modal-header">
            <h3 id="eposta-alicilar-detay-title" class="confirm-modal-title">E-Posta Alıcıları</h3>
            <button type="button" class="confirm-modal-x" data-eposta-alicilar-detay-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <div class="sms-alicilar-detay-list" data-eposta-alicilar-detay-list></div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-eposta-alicilar-detay-close>Kapat</button>
        </div>
    </div>
</div>

{{-- E-posta gönder modalı --}}
<div
    class="confirm-modal"
    id="eposta-modal"
    hidden
    data-eposta-alicilar-url="{{ route('etkinlikler.eposta.alicilar', $etkinlik) }}"
>
    <div class="confirm-modal-backdrop" data-eposta-modal-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="eposta-modal-title">
        <div class="confirm-modal-header">
            <h3 id="eposta-modal-title" class="confirm-modal-title">E-Posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-eposta-modal-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <div class="sms-filter-row" role="group" aria-label="Başvuru durumu filtresi">
                <button type="button" class="basvuru-filter is-active" data-eposta-filter="tumu">Tümü</button>
                @foreach ($basvuruDurumlari as $filtreDurum)
                    <button
                        type="button"
                        class="basvuru-filter"
                        data-eposta-filter="{{ $filtreDurum->kod }}"
                    >{{ $filtreDurum->ad }}</button>
                @endforeach
            </div>

            <div class="form-group">
                <div class="sms-alicilar-head">
                    <label>Alıcılar</label>
                    <div class="sms-alicilar-head-actions">
                        <span class="sms-alicilar-count" data-eposta-alicilar-count>0 kişi</span>
                        <button type="button" class="sms-clear-all-btn" data-eposta-clear-all title="Tümünü Temizle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                <path d="M10 11v6"/>
                                <path d="M14 11v6"/>
                            </svg>
                            Tümünü Temizle
                        </button>
                    </div>
                </div>
                <div class="sms-alici-search-wrap" data-eposta-search-wrap>
                    <input
                        type="search"
                        class="form-control"
                        data-eposta-search
                        placeholder="Ad soyad veya T.C. kimlik no ile ara ve ekle..."
                        autocomplete="off"
                    >
                    <div class="sms-alici-search-results" data-eposta-search-results hidden></div>
                </div>
                <div class="sms-alicilar-list" data-eposta-alicilar-list>
                    <p class="sms-alicilar-empty" data-eposta-alicilar-empty>Alıcılar yükleniyor...</p>
                </div>
            </div>

            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-eposta-insert="{ad_soyad}"
                            data-eposta-insert-target="konu"
                            title="Konu alanına ekler"
                        >{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>

            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-eposta-insert="{ad_soyad}"
                            data-eposta-insert-target="mesaj"
                            title="Mesaj alanına ekler"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-eposta-onizle
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, başvuru durumunuz güncellendi."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-eposta-modal-close>Vazgeç</button>
            <button
                type="button"
                class="btn btn-primary btn-wide"
                data-eposta-send
                data-eposta-url="{{ route('etkinlikler.eposta.send', $etkinlik) }}"
            >Gönder</button>
        </div>
    </div>
</div>

{{-- E-posta önizleme modalı --}}
<div class="confirm-modal" id="eposta-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-eposta-onizleme-close></div>
    <div class="confirm-modal-dialog eposta-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="eposta-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="eposta-onizleme-title" class="confirm-modal-title">E-Posta Önizleme</h3>
            <button type="button" class="confirm-modal-x" data-eposta-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body eposta-onizleme-body">
            <p class="sms-onizleme-alici" data-eposta-onizleme-alici></p>
            <div class="eposta-preview" aria-hidden="true">
                <div class="eposta-preview-chrome">
                    <span class="eposta-preview-dot"></span>
                    <span class="eposta-preview-dot"></span>
                    <span class="eposta-preview-dot"></span>
                    <span class="eposta-preview-chrome-title">E-posta</span>
                </div>
                <div class="eposta-preview-meta">
                    <div class="eposta-preview-row">
                        <span>Kime</span>
                        <strong data-eposta-onizleme-kime>—</strong>
                    </div>
                    <div class="eposta-preview-row">
                        <span>Konu</span>
                        <strong data-eposta-onizleme-konu></strong>
                    </div>
                </div>
                <div class="eposta-preview-body" data-eposta-onizleme-mesaj></div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-eposta-onizleme-close>Kapat</button>
        </div>
    </div>
</div>

@include('etkinlikler._basvuru_action_modals')

{{-- Yayın onay modalı --}}
<div class="confirm-modal" id="yayin-modal" hidden>
    <div class="confirm-modal-backdrop" data-yayin-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="yayin-modal-title">
        <div class="confirm-modal-header">
            <h3 id="yayin-modal-title" class="confirm-modal-title" data-yayin-modal-title>Onay</h3>
            <button type="button" class="confirm-modal-x" data-yayin-modal-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body" data-yayin-modal-body></div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-yayin-modal-close data-yayin-modal-cancel>Vazgeç</button>
            <form method="POST" action="{{ route('etkinlikler.toggle-yayin', $etkinlik) }}" data-yayin-modal-form>
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-wide" data-yayin-modal-confirm>Onayla</button>
            </form>
        </div>
    </div>
</div>

@include('etkinlikler._basvuru_durum_modal')
@include('etkinlikler._yedek_sira_modal')
@endsection
