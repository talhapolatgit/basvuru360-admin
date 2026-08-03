@extends('layouts.admin')

@section('title', $kurs->brans?->ad ?? 'Kurs #'.$kurs->kurs_no)

@php
    $user = auth()->user();
    $basvuruGorebilir = $user?->hasYetki('basvuru.goruntule') ?? false;
    $yoklamaGorebilir = $user?->hasYetki('kurs.yoklama_goruntule') ?? false;
    $mesajGorebilir = $user?->hasYetki('kurs.mesaj_goruntule') ?? false;
    $allowedTabs = ['detay', 'program', 'takvim'];
    if ($basvuruGorebilir) {
        $allowedTabs[] = 'basvurular';
    }
    if ($yoklamaGorebilir) {
        $allowedTabs[] = 'yoklamalar';
    }
    if ($mesajGorebilir) {
        $allowedTabs[] = 'mesajlar';
    }
    $activeTab = in_array($activeTab ?? 'detay', $allowedTabs, true)
        ? $activeTab
        : 'detay';
    $basvuruDurum = $basvuruDurum ?? 'tumu';
@endphp

@section('content')
<div class="lesson-detail">
    <div class="lesson-detail-header">
        <h1 class="lesson-detail-title">{{ $kurs->brans?->ad ?? 'Kurs Detayı' }}</h1>
        @if ($activeTab === 'basvurular')
            <button type="button" class="btn-excel" disabled title="Yakında">
                <span class="btn-excel-mark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h7v7H4z"/>
                        <path d="M13 4h7v7h-7z"/>
                        <path d="M4 13h7v7H4z"/>
                        <path d="M13 13h7v7h-7z"/>
                    </svg>
                </span>
                <span class="btn-excel-text">Excel</span>
            </button>
        @endif
    </div>

    <div class="lesson-tabs" role="tablist">
        <button type="button" class="lesson-tab {{ $activeTab === 'detay' ? 'is-active' : '' }}" data-lesson-tab="detay" role="tab" aria-selected="{{ $activeTab === 'detay' ? 'true' : 'false' }}">Kurs Detayları</button>
        @yetki('basvuru.goruntule')
        <button type="button" class="lesson-tab {{ $activeTab === 'basvurular' ? 'is-active' : '' }}" data-lesson-tab="basvurular" role="tab" aria-selected="{{ $activeTab === 'basvurular' ? 'true' : 'false' }}">Başvurular</button>
        @endyetki
        <button type="button" class="lesson-tab {{ $activeTab === 'program' ? 'is-active' : '' }}" data-lesson-tab="program" role="tab" aria-selected="{{ $activeTab === 'program' ? 'true' : 'false' }}">Ders Programı</button>
        <button type="button" class="lesson-tab {{ $activeTab === 'takvim' ? 'is-active' : '' }}" data-lesson-tab="takvim" role="tab" aria-selected="{{ $activeTab === 'takvim' ? 'true' : 'false' }}">Takvim</button>
        @yetki('kurs.yoklama_goruntule')
        <button type="button" class="lesson-tab {{ $activeTab === 'yoklamalar' ? 'is-active' : '' }}" data-lesson-tab="yoklamalar" role="tab" aria-selected="{{ $activeTab === 'yoklamalar' ? 'true' : 'false' }}">Yoklamalar</button>
        @endyetki
        @yetki('kurs.mesaj_goruntule')
        <button type="button" class="lesson-tab {{ $activeTab === 'mesajlar' ? 'is-active' : '' }}" data-lesson-tab="mesajlar" role="tab" aria-selected="{{ $activeTab === 'mesajlar' ? 'true' : 'false' }}">Mesajlar</button>
        @endyetki
    </div>

    {{-- Kurs Detayları --}}
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
                <div class="lesson-stat-value">{{ number_format($kurs->kontenjan) }}</div>
            </div>
            <div class="lesson-stat-card">
                <h3 class="lesson-stat-label">Ek Kontenjan</h3>
                <div class="lesson-stat-value">{{ number_format($kurs->yedek_kontenjan) }}</div>
            </div>
        </div>

        <div class="lesson-info-grid">
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kurs No</div>
                <div class="lesson-info-value">{{ $kurs->kurs_no }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Branş</div>
                <div class="lesson-info-value">{{ $kurs->brans?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Alan</div>
                <div class="lesson-info-value">{{ $kurs->alan?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kurs Merkezi</div>
                <div class="lesson-info-value">{{ $kurs->merkez?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kurum</div>
                <div class="lesson-info-value">
                    @if ($kurs->kurumlar->isNotEmpty())
                        <div class="rol-badge-list">
                            @foreach ($kurs->kurumlar as $kurum)
                                <span class="status status-hazirlik">{{ $kurum->ad }}</span>
                            @endforeach
                        </div>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kurs Tipi</div>
                <div class="lesson-info-value">{{ $kurs->kursTipi?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Tarihler</div>
                <div class="lesson-info-value">
                    {{ $kurs->kurs_baslama_tarihi?->format('d.m.Y') ?? '—' }}
                    -
                    {{ $kurs->kurs_bitis_tarihi?->format('d.m.Y') ?? '—' }}
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Başvuru Tarihleri</div>
                <div class="lesson-info-value">
                    {{ $kurs->basvuru_baslama_tarihi?->format('d.m.Y') ?? '—' }}
                    -
                    {{ $kurs->basvuru_bitis_tarihi?->format('d.m.Y') ?? '—' }}
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Toplam Saat</div>
                <div class="lesson-info-value">{{ $kurs->toplam_kurs_saati }} saat</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Öğretmen</div>
                <div class="lesson-info-value lesson-info-value-with-action">
                    <div class="lesson-ogretmen-name">{{ $kurs->ogretmenAdlari() }}</div>
                    @yetki('kurs.ogretmen_ata')
                    <button
                        type="button"
                        class="lesson-yayin-btn {{ $kurs->ogretmenler->isNotEmpty() ? 'is-change' : 'is-publish' }}"
                        data-ogretmen-modal-open
                    >
                        {{ $kurs->ogretmenler->isNotEmpty() ? 'Düzenle' : 'Öğretmen Ata' }}
                    </button>
                    @endyetki
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">MEB No</div>
                <div class="lesson-info-value">{{ $kurs->meb_numarasi ?: '—' }}</div>
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
                    @if ($kurs->evrakTipleri->isEmpty())
                        —
                    @else
                        {{ $kurs->evrakTipleri->pluck('ad')->implode(', ') }}
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <svg class="lesson-info-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Durum
                </div>
                <div class="lesson-info-value">
                    @if ($kurs->durum)
                        <span class="status status-{{ $kurs->durum->value }}">{{ $kurs->durum->label() }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Başvuru Durumu</div>
                <div class="lesson-info-value lesson-info-value-with-action">
                    <div class="lesson-yayin-status">
                        <span class="status {{ $kurs->basvuruDurumuStatusClass() }}">{{ $kurs->basvuruDurumuLabel() }}</span>
                    </div>
                    <button
                        type="button"
                        class="lesson-yayin-btn {{ $kurs->onlinede_yayinlansin ? 'is-unpublish' : 'is-publish' }}"
                        data-yayin-modal-open
                        data-action="{{ $kurs->onlinede_yayinlansin ? 'unpublish' : 'publish' }}"
                        data-can-publish="{{ ($yayinlanabilir ?? false) ? '1' : '0' }}"
                        data-baslangic="{{ optional($kurs->basvuru_baslama_tarihi)->format('d.m.Y H:i') ?? '—' }}"
                        data-bitis="{{ optional($kurs->basvuru_bitis_tarihi)->format('d.m.Y H:i') ?? '—' }}"
                    >
                        @if ($kurs->onlinede_yayinlansin)
                            Yayından Kaldır
                        @else
                            Yayına Al
                        @endif
                    </button>
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">İkamet Dışı Kontenjan</div>
                <div class="lesson-info-value">{{ number_format($kurs->ikamet_disi_kontenjan) }}</div>
            </div>
        </div>

        <div class="lesson-actions-block">
            <div class="lesson-actions-card">
                <div class="lesson-actions-card-head">
                    <h3 class="lesson-actions-title">İşlemler</h3>
                    <p class="lesson-actions-subtitle">Kurs ile ilgili hızlı işlemler</p>
                </div>
                <div class="lesson-actions-grid">
                    @yetki('kurs.guncelle')
                        <a href="{{ route('kurslar.edit', $kurs) }}" class="lesson-action-btn lesson-action-btn-primary">
                            <span class="lesson-action-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <span class="lesson-action-copy">
                                <span class="lesson-action-label">Düzenle</span>
                                <span class="lesson-action-hint">Kurs bilgilerini güncelle</span>
                            </span>
                        </a>
                    @else
                        <button type="button" class="lesson-action-btn" disabled title="Bu işlem için yetkiniz yok">
                            <span class="lesson-action-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <span class="lesson-action-copy">
                                <span class="lesson-action-label">Düzenle</span>
                                <span class="lesson-action-hint">Kurs bilgilerini güncelle</span>
                            </span>
                        </button>
                    @endyetki
                    <button
                        type="button"
                        class="lesson-action-btn {{ auth()->user()?->hasYetki('kurs.sms') ? 'lesson-action-btn-primary' : '' }}"
                        @yetki('kurs.sms') data-sms-modal-open @else disabled title="Bu işlem için yetkiniz yok" @endyetki
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
                        class="lesson-action-btn {{ auth()->user()?->hasYetki('kurs.eposta') ? 'lesson-action-btn-primary' : '' }}"
                        @yetki('kurs.eposta') data-eposta-modal-open @else disabled title="Bu işlem için yetkiniz yok" @endyetki
                    >
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">E-posta Gönder</span>
                            <span class="lesson-action-hint">Başvuranlara toplu gönder</span>
                        </span>
                    </button>
                    @yetki('kurs.export')
                        <a href="{{ route('kurslar.sertifikalar.pdf', $kurs) }}" class="lesson-action-btn lesson-action-btn-primary">
                            <span class="lesson-action-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                            </span>
                            <span class="lesson-action-copy">
                                <span class="lesson-action-label">Sertifika Oluştur</span>
                                <span class="lesson-action-hint">Hak edenler için PDF</span>
                            </span>
                        </a>
                    @else
                        <button type="button" class="lesson-action-btn" disabled title="Bu işlem için yetkiniz yok">
                            <span class="lesson-action-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                            </span>
                            <span class="lesson-action-copy">
                                <span class="lesson-action-label">Sertifika Oluştur</span>
                                <span class="lesson-action-hint">Yetkiniz yok</span>
                            </span>
                        </button>
                    @endyetki
                    <button type="button" class="lesson-action-btn" disabled title="Yakında">
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">Belge Yükle</span>
                            <span class="lesson-action-hint">Yakında</span>
                        </span>
                    </button>
                    <button type="button" class="lesson-action-btn" disabled title="Yakında">
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 3 4 4-4 4"/><path d="M20 7H4"/><path d="m8 21-4-4 4-4"/><path d="M4 17h16"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">Kursiyer Taşıma</span>
                            <span class="lesson-action-hint">Yakında</span>
                        </span>
                    </button>
                    <a href="{{ route('kurslar.index') }}" class="lesson-action-btn lesson-action-btn-muted">
                        <span class="lesson-action-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        </span>
                        <span class="lesson-action-copy">
                            <span class="lesson-action-label">Listeye Dön</span>
                            <span class="lesson-action-hint">Kurs listesine git</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Başvurular --}}
    @yetki('basvuru.goruntule')
    <div
        class="lesson-tab-panel {{ $activeTab === 'basvurular' ? 'is-active' : '' }}"
        data-lesson-panel="basvurular"
        data-basvuru-panel
        data-basvuru-url="{{ route('kurslar.basvurular', $kurs) }}"
        data-basvuru-durum="{{ $basvuruDurum }}"
        data-kurs-durum="{{ $kurs->durum?->value }}"
        data-kurs-baslama="{{ $kurs->kurs_baslama_tarihi?->format('Y-m-d') }}"
        data-kurs-bitis="{{ $kurs->kurs_bitis_tarihi?->format('Y-m-d') }}"
        role="tabpanel"
    >
        <div class="basvuru-toolbar">
            <div class="basvuru-filters">
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
                <x-excel-export
                    :href="route('kurslar.basvurular.export', $kurs)"
                    id="basvuru-excel-link"
                    class="btn-excel-sm"
                    data-basvuru-excel
                />
                @yetki('basvuru.olustur')
                <x-cta-button
                    :href="route('basvurular.create', ['kurs_id' => $kurs->id])"
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

    {{-- Ders Programı --}}
    <div class="lesson-tab-panel {{ $activeTab === 'program' ? 'is-active' : '' }}" data-lesson-panel="program" role="tabpanel">
        <div class="card table-card lesson-table-card">
            @if ($kurs->gunler->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-title">Ders programı yok</div>
                    <p class="empty-state-text">Bu kurs için haftalık program tanımlanmamış.</p>
                </div>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Gün</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Ders Saati</th>
                                <th>Sınıf</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kurs->gunler->sortBy(fn ($g) => $g->gun?->sira() ?? 99) as $gun)
                                <tr>
                                    <td>{{ $gun->gun?->label() ?? '—' }}</td>
                                    <td>{{ substr((string) $gun->baslangic_saati, 0, 5) }}</td>
                                    <td>{{ substr((string) $gun->bitis_saati, 0, 5) }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $gun->ders_saati, 1, '.', ''), '0'), '.') }}</td>
                                    <td>{{ $gun->sinif ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-footer lesson-table-footer">
                    <div class="table-footer-right">
                        <x-excel-export :href="route('kurslar.program.export', $kurs)" class="btn-excel-sm" />
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('kurslar._takvim')

    {{-- Yoklamalar --}}
    @yetki('kurs.yoklama_goruntule')
    <div
        class="lesson-tab-panel {{ $activeTab === 'yoklamalar' ? 'is-active' : '' }}"
        data-lesson-panel="yoklamalar"
        data-yoklama-panel
        data-yoklama-url="{{ route('kurslar.yoklamalar', $kurs) }}"
        data-yoklama-ders="{{ request('ders') }}"
        role="tabpanel"
    >
        <div data-yoklama-content>
            <div class="card table-card lesson-table-card">
                <div class="empty-state">
                    <div class="empty-state-title">Yoklamalar</div>
                    <p class="empty-state-text">Bu sekme açıldığında ders listesi yüklenecek.</p>
                </div>
            </div>
        </div>
    </div>
    @endyetki

    {{-- Mesajlar --}}
    @yetki('kurs.mesaj_goruntule')
    <div
        class="lesson-tab-panel {{ $activeTab === 'mesajlar' ? 'is-active' : '' }}"
        data-lesson-panel="mesajlar"
        data-mesajlar-url="{{ route('kurslar.mesajlar', $kurs) }}"
        role="tabpanel"
    >
        <div data-mesajlar-content>
            @include('kurslar._mesajlar_panel', [
                'mesajKanal' => $activeMesajKanal ?? 'sms',
            ])
        </div>
    </div>
    @endyetki
</div>

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
            <form method="POST" action="{{ route('kurslar.toggle-yayin', $kurs) }}" data-yayin-modal-form>
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-wide" data-yayin-modal-confirm>Onayla</button>
            </form>
        </div>
    </div>
</div>

{{-- Yoklama silme onay modalı --}}
<div class="confirm-modal" id="yoklama-delete-modal" hidden>
    <div class="confirm-modal-backdrop" data-yoklama-delete-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="yoklama-delete-modal-title">
        <div class="confirm-modal-header">
            <h3 id="yoklama-delete-modal-title" class="confirm-modal-title">Yoklamayı Sil</h3>
            <button type="button" class="confirm-modal-x" data-yoklama-delete-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body" data-yoklama-delete-body></div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-yoklama-delete-close data-yoklama-delete-cancel>Vazgeç</button>
            <form method="POST" action="" data-yoklama-delete-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-wide" data-yoklama-delete-confirm>Sil</button>
            </form>
        </div>
    </div>
</div>

{{-- Ders iptal modalı --}}
<div class="confirm-modal" id="ders-iptal-modal" hidden>
    <div class="confirm-modal-backdrop" data-ders-iptal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ders-iptal-modal-title">
        <div class="confirm-modal-header">
            <h3 id="ders-iptal-modal-title" class="confirm-modal-title">Ders İptal</h3>
            <button type="button" class="confirm-modal-x" data-ders-iptal-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p class="confirm-modal-text" data-ders-iptal-ozet></p>
            <div class="form-group" style="margin-bottom:0;">
                <label for="ders-iptal-gerekce">İptal gerekçesi <span class="req">*</span></label>
                <textarea
                    id="ders-iptal-gerekce"
                    class="form-control"
                    rows="3"
                    maxlength="1000"
                    placeholder="Bu dersin neden iptal edildiğini yazınız"
                    data-ders-iptal-gerekce
                ></textarea>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-ders-iptal-close data-ders-iptal-cancel>Vazgeç</button>
            <form method="POST" action="" data-ders-iptal-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="gerekce" data-ders-iptal-gerekce-input>
                <button type="submit" class="btn btn-danger btn-wide" data-ders-iptal-confirm>İptal Et</button>
            </form>
        </div>
    </div>
</div>

{{-- Ders iptalini geri alma modalı --}}
<div class="confirm-modal" id="ders-iptal-geri-al-modal" hidden>
    <div class="confirm-modal-backdrop" data-ders-iptal-geri-al-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ders-iptal-geri-al-modal-title">
        <div class="confirm-modal-header">
            <h3 id="ders-iptal-geri-al-modal-title" class="confirm-modal-title">İptali Geri Al</h3>
            <button type="button" class="confirm-modal-x" data-ders-iptal-geri-al-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body" data-ders-iptal-geri-al-body></div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-ders-iptal-geri-al-close data-ders-iptal-geri-al-cancel>Vazgeç</button>
            <form method="POST" action="" data-ders-iptal-geri-al-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary btn-wide" data-ders-iptal-geri-al-confirm>İptali Geri Al</button>
            </form>
        </div>
    </div>
</div>

{{-- Ders tarih değiştirme modalı --}}
<div class="confirm-modal" id="ders-tarih-modal" hidden>
    <div class="confirm-modal-backdrop" data-ders-tarih-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ders-tarih-modal-title">
        <div class="confirm-modal-header">
            <h3 id="ders-tarih-modal-title" class="confirm-modal-title">Tarih Değiştir</h3>
            <button type="button" class="confirm-modal-x" data-ders-tarih-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p class="confirm-modal-text" data-ders-tarih-ozet></p>
            <div class="form-group" style="margin-bottom:0;">
                <label for="ders-tarih-yeni">Yeni tarih <span class="req">*</span></label>
                <input type="date" id="ders-tarih-yeni" class="form-control" data-ders-tarih-yeni>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-ders-tarih-close data-ders-tarih-cancel>Vazgeç</button>
            <form method="POST" action="" data-ders-tarih-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="tarih" data-ders-tarih-yeni-input>
                <button type="submit" class="btn btn-primary btn-wide" data-ders-tarih-confirm>Kaydet</button>
            </form>
        </div>
    </div>
</div>

@include('kurslar._basvuru_action_modals')
@include('kurslar._yedek_sira_modal')

{{-- Başvuru evrakları modalı --}}
@yetki('basvuru.evrak_goruntule')
@include('partials.basvuru-evraklar-modal')
@endyetki

{{-- Başvuruya tekli SMS gönder modalı --}}
<div class="confirm-modal" id="basvuru-sms-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-sms-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="basvuru-sms-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-sms-modal-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-sms-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-sms-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-basvuru-sms-no-telefon hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı telefon numarası bulunamadı.
            </p>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="basvuru-sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-sms-insert="{ad_soyad}"
                            title="İmleç konumuna ekler"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-basvuru-sms-onizle
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
                    id="basvuru-sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-basvuru-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, kurs kaydınız onaylandı."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-basvuru-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-sms-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-sms-send>Gönder</button>
        </div>
    </div>
</div>

{{-- Başvuruya tekli e-posta gönder modalı --}}
<div class="confirm-modal" id="basvuru-eposta-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-eposta-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="basvuru-eposta-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-eposta-modal-title" class="confirm-modal-title">E-Posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-eposta-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-eposta-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-basvuru-eposta-no-email hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı e-posta adresi bulunamadı.
            </p>
            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="basvuru-eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-eposta-insert="{ad_soyad}"
                            data-basvuru-eposta-insert-target="konu"
                            title="Konu alanına ekler"
                        >{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="basvuru-eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-basvuru-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="basvuru-eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-eposta-insert="{ad_soyad}"
                            data-basvuru-eposta-insert-target="mesaj"
                            title="Mesaj alanına ekler"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-basvuru-eposta-onizle
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
                    id="basvuru-eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-basvuru-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, başvuru durumunuz güncellendi."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-basvuru-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-eposta-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-eposta-send>Gönder</button>
        </div>
    </div>
</div>

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
    data-sms-alicilar-url="{{ route('kurslar.sms.alicilar', $kurs) }}"
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
                data-sms-url="{{ route('kurslar.sms.send', $kurs) }}"
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
    data-eposta-alicilar-url="{{ route('kurslar.eposta.alicilar', $kurs) }}"
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
                data-eposta-url="{{ route('kurslar.eposta.send', $kurs) }}"
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

{{-- Öğretmen ata / düzenle modalı --}}
@yetki('kurs.ogretmen_ata')
<div class="confirm-modal" id="ogretmen-modal" hidden>
    <div class="confirm-modal-backdrop" data-ogretmen-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ogretmen-modal-title">
        <div class="confirm-modal-header">
            <h3 id="ogretmen-modal-title" class="confirm-modal-title">
                {{ $kurs->ogretmenler->isNotEmpty() ? 'Öğretmenleri Düzenle' : 'Öğretmen Ata' }}
            </h3>
            <button type="button" class="confirm-modal-x" data-ogretmen-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('kurslar.ogretmen.assign', $kurs) }}" class="ogretmen-modal-form">
            @csrf
            @method('PUT')
            <div class="confirm-modal-body">
                <div class="evrak-picker" data-ogretmen-picker>
                    <div class="evrak-add-row">
                        <div class="form-group grow" style="margin:0;">
                            <label>Öğretmen ekle</label>
                            <x-searchable-select
                                name="ogretmen_picker"
                                placeholder="Öğretmen seçin"
                                value=""
                                :options="$ogretmenler->map(fn ($o) => ['value' => $o->id, 'label' => $o->tam_adi])->all()"
                            />
                        </div>
                        <x-back-button type="button" icon="plus" data-ogretmen-add>Ekle</x-back-button>
                    </div>

                    <div class="evrak-list" data-ogretmen-list>
                        @forelse ($kurs->ogretmenler->sortBy('id') as $ogretmen)
                            <div class="evrak-item" data-ogretmen-item data-id="{{ $ogretmen->id }}">
                                <input type="hidden" name="ogretmen_ids[]" value="{{ $ogretmen->id }}">
                                <span class="evrak-item-label">{{ $ogretmen->tam_adi }}</span>
                                <button type="button" class="evrak-item-remove" data-ogretmen-remove title="Kaldır" aria-label="Kaldır">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @empty
                            <p class="evrak-empty" data-ogretmen-empty>Henüz öğretmen atanmadı.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-ogretmen-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endyetki
@endsection
