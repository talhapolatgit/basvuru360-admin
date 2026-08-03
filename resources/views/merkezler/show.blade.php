@extends('layouts.admin')

@section('title', $merkez->ad)

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kurs Yönetimi</p>
        <h1 class="page-title">{{ $merkez->ad }}</h1>
        <p class="page-subtitle">Merkez bilgilerini görüntüleyin; bu merkeze bağlı aktif kurs ve etkinlikleri inceleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('merkezler.index') }}">Merkezlere Dön</x-back-button>
    </div>
</div>

<div class="stats-grid stats-grid-5">
    <div class="stat-card">
        <div class="stat-label">Durum</div>
        <div class="stat-value" style="font-size:16px;">
            @if ($merkez->aktif)
                <span class="status status-aktif">Aktif</span>
            @else
                <span class="status status-hazirlik">Pasif</span>
            @endif
        </div>
    </div>
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($aktifKursSayisi) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Aktif Etkinlik Sayısı</div>
        <div class="stat-value">{{ number_format($aktifEtkinlikSayisi) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Toplam Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($toplamKursSayisi) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Aktif Öğrenci Sayısı</div>
        <div class="stat-value">{{ number_format($aktifOgrenciSayisi) }}</div>
    </div>
</div>

<div class="lesson-actions-block">
    <div class="lesson-actions-card">
        <div class="lesson-actions-card-head">
            <h3 class="lesson-actions-title">İşlemler</h3>
            <p class="lesson-actions-subtitle">Merkez bilgilerini düzenleyin, takvimi görüntüleyin; kesin kayıtlı öğrencilere SMS veya e-posta gönderin</p>
        </div>
        <div class="lesson-actions-grid">
            <button
                type="button"
                class="lesson-action-btn lesson-action-btn-primary"
                data-entity-edit
                data-update-url="{{ route('merkezler.update', $merkez) }}"
                data-ad="{{ $merkez->ad }}"
                data-il="{{ $merkez->il }}"
                data-ilce="{{ $merkez->ilce }}"
                data-aktif="{{ $merkez->aktif ? '1' : '0' }}"
            >
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">Düzenle</span>
                    <span class="lesson-action-hint">Merkez bilgilerini güncelle</span>
                </span>
            </button>
            <a href="{{ route('takvim.index', ['merkez' => $merkez->id]) }}" class="lesson-action-btn lesson-action-btn-primary">
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">Takvim</span>
                    <span class="lesson-action-hint">Merkezin ders takvimini görüntüle</span>
                </span>
            </a>
            <button
                type="button"
                class="lesson-action-btn lesson-action-btn-primary"
                data-merkez-sms-open
                @disabled($aktifOgrenciSayisi < 1)
                title="{{ $aktifOgrenciSayisi > 0 ? 'Kesin kayıtlı öğrencilere SMS gönder' : 'Kesin kayıtlı öğrenci yok' }}"
            >
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">SMS Gönder</span>
                    <span class="lesson-action-hint">{{ number_format($aktifOgrenciSayisi) }} kesin kayıtlı öğrenci</span>
                </span>
            </button>
            <button
                type="button"
                class="lesson-action-btn lesson-action-btn-primary"
                data-merkez-eposta-open
                @disabled($aktifOgrenciSayisi < 1)
                title="{{ $aktifOgrenciSayisi > 0 ? 'Kesin kayıtlı öğrencilere e-posta gönder' : 'Kesin kayıtlı öğrenci yok' }}"
            >
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">E-posta Gönder</span>
                    <span class="lesson-action-hint">{{ number_format($aktifOgrenciSayisi) }} kesin kayıtlı öğrenci</span>
                </span>
            </button>
        </div>
    </div>
</div>

@php
    $merkezKurslarColumns = [
        'kurs_no' => 'Kurs No',
        'alan' => 'Alan',
        'brans' => 'Branş',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'kontenjan' => 'Kontenjan',
        'kayit' => 'Kayıt',
        'durum' => 'Durum',
    ];
    $merkezKurslarOrder = array_keys($merkezKurslarColumns);

    $merkezEtkinliklerColumns = [
        'etkinlik_no' => 'Etkinlik No',
        'ad' => 'Etkinlik',
        'tip' => 'Tip',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'kontenjan' => 'Kontenjan',
        'kayit' => 'Kayıt',
        'durum' => 'Durum',
    ];
    $merkezEtkinliklerOrder = array_keys($merkezEtkinliklerColumns);
@endphp

<div class="lesson-tabs" role="tablist" data-egitmen-tabs>
    <button type="button" class="lesson-tab is-active" data-egitmen-tab="kurslar" role="tab" aria-selected="true">
        Aktif Kurslar
        <span class="sabit-tab-count">{{ number_format($aktifKursSayisi) }}</span>
    </button>
    <button type="button" class="lesson-tab" data-egitmen-tab="etkinlikler" role="tab" aria-selected="false">
        Aktif Etkinlikler
        <span class="sabit-tab-count">{{ number_format($aktifEtkinlikSayisi) }}</span>
    </button>
</div>

<div class="lesson-tab-panel is-active" data-egitmen-panel="kurslar" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Aktif Kurslar</div>
                <p class="table-subtitle">Bu merkezde şu anda aktif durumda olan kurslar.</p>
            </div>
            <x-detail-table-tools :columns="$merkezKurslarColumns" :visible="$merkezKurslarOrder" excel-name="merkez-aktif-kurslar" />
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="merkez_kurslar_cols"
                data-default-order='@json($merkezKurslarOrder)'
                data-default-visible='@json($merkezKurslarOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($merkezKurslarColumns as $key => $label)
                            <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kurslar as $kurs)
                        <tr>
                            <td data-column="kurs_no" data-export-value="{{ $kurs->kurs_no }}"><a href="{{ route('kurslar.show', $kurs) }}" class="kurs-no">{{ $kurs->kurs_no }}</a></td>
                            <td data-column="alan">{{ $kurs->alan?->ad }}</td>
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
                                    <p class="empty-state-text">Bu merkeze bağlı aktif durumda bir kurs yok.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="lesson-tab-panel" data-egitmen-panel="etkinlikler" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Aktif Etkinlikler</div>
                <p class="table-subtitle">Bu merkezde şu anda aktif durumda olan etkinlikler.</p>
            </div>
            <x-detail-table-tools :columns="$merkezEtkinliklerColumns" :visible="$merkezEtkinliklerOrder" excel-name="merkez-aktif-etkinlikler" />
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="merkez_etkinlikler_cols"
                data-default-order='@json($merkezEtkinliklerOrder)'
                data-default-visible='@json($merkezEtkinliklerOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($merkezEtkinliklerColumns as $key => $label)
                            <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($etkinlikler as $etkinlik)
                        <tr>
                            <td data-column="etkinlik_no" data-export-value="{{ $etkinlik->etkinlik_no }}">
                                <a href="{{ route('etkinlikler.show', $etkinlik) }}" class="kurs-no">#{{ $etkinlik->etkinlik_no }}</a>
                            </td>
                            <td data-column="ad">{{ $etkinlik->ad }}</td>
                            <td data-column="tip">{{ $etkinlik->etkinlikTipi?->ad ?? '—' }}</td>
                            <td data-column="baslama" data-sort-value="{{ $etkinlik->baslangic_tarihi?->toDateString() }}">{{ $etkinlik->baslangic_tarihi?->format('d.m.Y') ?? '—' }}</td>
                            <td data-column="bitis" data-sort-value="{{ $etkinlik->bitis_tarihi?->toDateString() }}">{{ $etkinlik->bitis_tarihi?->format('d.m.Y') ?? '—' }}</td>
                            <td data-column="kontenjan">{{ $etkinlik->kontenjan }}</td>
                            <td data-column="kayit">{{ $etkinlik->kayit_sayisi }}</td>
                            <td data-column="durum" data-export-value="{{ $etkinlik->durum?->label() }}">
                                @if ($etkinlik->durum)
                                    <span class="status status-{{ $etkinlik->durum->value }}">{{ $etkinlik->durum->label() }}</span>
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
                                    <div class="empty-state-title">Aktif etkinlik bulunamadı</div>
                                    <p class="empty-state-text">Bu merkeze bağlı aktif durumda bir etkinlik yok.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('merkezler._form-modal')

{{-- SMS modal --}}
<div class="confirm-modal" id="merkez-sms-modal" hidden
    data-send-url="{{ route('merkezler.sms.send', $merkez) }}"
    data-ogrenci-sayisi="{{ $aktifOgrenciSayisi }}"
>
    <div class="confirm-modal-backdrop" data-merkez-sms-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="merkez-sms-modal-title">
        <div class="confirm-modal-header">
            <h3 id="merkez-sms-modal-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-merkez-sms-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-merkez-sms-alici style="margin-bottom:14px;"></p>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="merkez-sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-merkez-sms-insert="{ad_soyad}" title="İmleç konumuna ekler">{ad_soyad}</button>
                        <button type="button" class="sms-onizle-btn" data-merkez-sms-onizle>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="merkez-sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-merkez-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, kurs programınız güncellendi."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-merkez-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-merkez-sms-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-merkez-sms-send>Gönder</button>
        </div>
    </div>
</div>

{{-- E-posta modal --}}
<div class="confirm-modal" id="merkez-eposta-modal" hidden
    data-send-url="{{ route('merkezler.eposta.send', $merkez) }}"
    data-ogrenci-sayisi="{{ $aktifOgrenciSayisi }}"
>
    <div class="confirm-modal-backdrop" data-merkez-eposta-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="merkez-eposta-modal-title">
        <div class="confirm-modal-header">
            <h3 id="merkez-eposta-modal-title" class="confirm-modal-title">E-posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-merkez-eposta-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-merkez-eposta-alici style="margin-bottom:14px;"></p>
            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="merkez-eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-merkez-eposta-insert="{ad_soyad}" data-merkez-eposta-insert-target="konu" title="Konu alanına ekler">{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="merkez-eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-merkez-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="merkez-eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-merkez-eposta-insert="{ad_soyad}" data-merkez-eposta-insert-target="mesaj" title="Mesaj alanına ekler">{ad_soyad}</button>
                        <button type="button" class="sms-onizle-btn" data-merkez-eposta-onizle>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="merkez-eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-merkez-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, bilgilendirme mesajınız."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-merkez-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-merkez-eposta-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-merkez-eposta-send>Gönder</button>
        </div>
    </div>
</div>

{{-- SMS önizleme --}}
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

{{-- E-posta önizleme --}}
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
@endsection
