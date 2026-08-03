@extends('layouts.admin')

@section('title', $kullanici->tam_adi)

@section('content')
<div class="page-header">
    <div class="page-header-main">
        <div
            class="profil-avatar-wrap profil-avatar-wrap-sm"
            data-avatar-preview
            data-upload-url="{{ route('kullanicilar.foto.update', $kullanici) }}"
            data-delete-url="{{ route('kullanicilar.foto.delete', $kullanici) }}"
        >
            <label class="profil-avatar profil-avatar-lg profil-avatar-edit" title="Yeni fotoğraf yükle">
                @if ($kullanici->profil_foto_url)
                    <img src="{{ $kullanici->profil_foto_url }}" alt="Profil fotoğrafı" data-avatar-img>
                    <span class="profil-avatar-initials" data-avatar-initials hidden>{{ $kullanici->bas_harfler ?: '?' }}</span>
                @else
                    <img src="" alt="Profil fotoğrafı" data-avatar-img hidden>
                    <span class="profil-avatar-initials" data-avatar-initials>{{ $kullanici->bas_harfler ?: '?' }}</span>
                @endif
                <span class="profil-avatar-overlay" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                </span>
                <input type="file" accept="image/jpeg,image/png,image/webp" data-avatar-input hidden>
            </label>
            <button
                type="button"
                class="profil-avatar-remove-btn"
                data-avatar-remove-btn
                title="Profil fotoğrafını kaldır"
                aria-label="Profil fotoğrafını kaldır"
                @unless ($kullanici->profil_foto) hidden @endunless
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="page-header-text">
            <p class="page-eyebrow">Kullanıcılar</p>
            <h1 class="page-title">{{ $kullanici->tam_adi }}</h1>
            <p class="page-subtitle">Kullanıcı bilgilerini görüntüleyin ve ilişkili kayıtları inceleyin.</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('kullanicilar.index') }}">Kullanıcılara Dön</x-back-button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Roller</div>
        <div class="stat-value" style="font-size:16px;">
            <div class="rol-badge-list">
                @forelse ($kullanici->roller as $rol)
                    <span class="status {{ $rol->tum_yetkiler || $rol->kod === 'ogretmen' ? 'status-aktif' : 'status-hazirlik' }}">{{ $rol->ad }}</span>
                @empty
                    —
                @endforelse
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Durum</div>
        <div class="stat-value" style="font-size:16px;">
            @if ($kullanici->aktif)
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
        <div class="stat-label">Aktif Öğrenci Sayısı</div>
        <div class="stat-value">{{ number_format($aktifOgrenciSayisi) }}</div>
    </div>
</div>

<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kullanıcı Bilgileri</div>
            <p class="table-subtitle">İletişim ve kimlik bilgileri.</p>
        </div>
    </div>

    <div class="lesson-info-grid" style="margin-bottom: 0;">
        <div class="lesson-info-card">
            <div class="lesson-info-label">TC Kimlik No</div>
            <div class="lesson-info-value">{{ $kullanici->tc_kimlik_no ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Doğum Tarihi</div>
            <div class="lesson-info-value">{{ $kullanici->dogum_tarihi?->format('d.m.Y') ?? '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Cinsiyet</div>
            <div class="lesson-info-value">{{ $kullanici->cinsiyet?->label() ?? '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Doğum Yeri</div>
            <div class="lesson-info-value">{{ $kullanici->dogum_yeri ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Medeni Durum</div>
            <div class="lesson-info-value">{{ $kullanici->medeni_durum ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Uyruk</div>
            <div class="lesson-info-value">{{ $kullanici->uyruk ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Anne Adı</div>
            <div class="lesson-info-value">{{ $kullanici->anne_adi ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Baba Adı</div>
            <div class="lesson-info-value">{{ $kullanici->baba_adi ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Kurum</div>
            <div class="lesson-info-value">
                @if ($kullanici->kurumlar->isNotEmpty())
                    <div class="rol-badge-list">
                        @foreach ($kullanici->kurumlar as $kurum)
                            <span class="status status-hazirlik">{{ $kurum->ad }}</span>
                        @endforeach
                    </div>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Telefon</div>
            <div class="lesson-info-value">{{ $kullanici->telefon ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">E-posta</div>
            <div class="lesson-info-value">{{ $kullanici->email }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">2 Aşamalı Güvenlik</div>
            <div class="lesson-info-value">{{ $kullanici->iki_asamali_guvenlik?->label() ?? 'Hayır' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">İl / İlçe</div>
            <div class="lesson-info-value">
                @if ($kullanici->il || $kullanici->ilce)
                    {{ trim(($kullanici->il ?? '').' / '.($kullanici->ilce ?? ''), ' /') }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Adres</div>
            <div class="lesson-info-value">{{ $kullanici->adres ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Kayıt Tarihi</div>
            <div class="lesson-info-value">{{ $kullanici->created_at?->format('d.m.Y') }}</div>
        </div>
    </div>
</div>

<div class="lesson-actions-block">
    <div class="lesson-actions-card">
        <div class="lesson-actions-card-head">
            <h3 class="lesson-actions-title">İşlemler</h3>
            <p class="lesson-actions-subtitle">Kullanıcı bilgilerini düzenleyin; SMS, e-posta veya şifre gönderin</p>
        </div>
        <div class="lesson-actions-grid">
            <a href="{{ route('kullanicilar.edit', $kullanici) }}" class="lesson-action-btn lesson-action-btn-primary">
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">Düzenle</span>
                    <span class="lesson-action-hint">Kullanıcı bilgilerini güncelle</span>
                </span>
            </a>
            @yetki('kullanici.guncelle')
                <a href="{{ route('kullanicilar.merkez-yetkileri.edit', $kullanici) }}" class="lesson-action-btn lesson-action-btn-primary">
                    <span class="lesson-action-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <span class="lesson-action-copy">
                        <span class="lesson-action-label">Merkez Yetkilendirme</span>
                        <span class="lesson-action-hint">Görüntüleyebileceği merkezleri seç</span>
                    </span>
                </a>
            @endyetki
            @if ($toplamKursSayisi > 0)
                <a href="{{ route('takvim.index', ['ogretmen' => $kullanici->id]) }}" class="lesson-action-btn lesson-action-btn-primary">
                    <span class="lesson-action-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                    </span>
                    <span class="lesson-action-copy">
                        <span class="lesson-action-label">Takvim</span>
                        <span class="lesson-action-hint">Kullanıcının ders takvimini görüntüle</span>
                    </span>
                </a>
            @endif
            <button
                type="button"
                class="lesson-action-btn lesson-action-btn-primary"
                data-egitmen-sms-open
                @disabled(! $telefonVar)
                title="{{ $telefonVar ? 'SMS gönder' : 'Telefon numarası yok' }}"
            >
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">SMS Gönder</span>
                    <span class="lesson-action-hint">{{ $telefonVar ? ($kullanici->telefon ?: 'Telefon') : 'Telefon yok' }}</span>
                </span>
            </button>
            <button
                type="button"
                class="lesson-action-btn lesson-action-btn-primary"
                data-egitmen-eposta-open
                @disabled(! $emailVar)
                title="{{ $emailVar ? 'E-posta gönder' : 'E-posta adresi yok' }}"
            >
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">E-posta Gönder</span>
                    <span class="lesson-action-hint">{{ $emailVar ? $kullanici->email : 'E-posta yok' }}</span>
                </span>
            </button>
            <button
                type="button"
                class="lesson-action-btn lesson-action-btn-primary"
                data-egitmen-sifre-open
                @disabled(! $telefonVar && ! $emailVar)
                title="Yeni şifre oluştur ve gönder"
            >
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">Şifre Gönder</span>
                    <span class="lesson-action-hint">Yeni geçici şifre oluştur</span>
                </span>
            </button>
        </div>
    </div>
</div>

@php
    use Illuminate\Support\Str;
@endphp

<div class="lesson-tabs" role="tablist" data-egitmen-tabs>
    <button type="button" class="lesson-tab is-active" data-egitmen-tab="kurslar" role="tab" aria-selected="true">Atanan Kurslar</button>
    <button type="button" class="lesson-tab" data-egitmen-tab="dersler" role="tab" aria-selected="false">Dersler</button>
    <button type="button" class="lesson-tab" data-egitmen-tab="yoklamalar" role="tab" aria-selected="false">Yoklamalar</button>
    <button type="button" class="lesson-tab" data-egitmen-tab="etkinlikler" role="tab" aria-selected="false">Atanan Etkinlikler</button>
    <button type="button" class="lesson-tab" data-egitmen-tab="mesajlar" role="tab" aria-selected="false">Mesajlar</button>
</div>

{{-- Atanan Kurslar --}}
@php
    $kurslarColumns = [
        'kurs_no' => 'Kurs No',
        'alan' => 'Alan',
        'brans' => 'Branş',
        'merkez' => 'Merkez',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'kontenjan' => 'Kontenjan',
        'kayit' => 'Kayıt',
        'durum' => 'Durum',
    ];
    $kurslarOrder = array_keys($kurslarColumns);
@endphp
<div class="lesson-tab-panel is-active" data-egitmen-panel="kurslar" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Atanan Kurslar</div>
                <p class="table-subtitle">Bu kullanıcının atandığı tüm kurslar.</p>
            </div>
            <x-detail-table-tools :columns="$kurslarColumns" :visible="$kurslarOrder" excel-name="atanan-kurslar" />
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="kullanici_kurslar_cols"
                data-default-order='@json($kurslarOrder)'
                data-default-visible='@json($kurslarOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($kurslarColumns as $key => $label)
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
                            <td data-column="merkez">{{ $kurs->merkez?->ad }}</td>
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
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
                                    </div>
                                    <div class="empty-state-title">Kurs bulunamadı</div>
                                    <p class="empty-state-text">Bu kullanıcıya henüz bir kurs atanmamış.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Dersler --}}
@php
    $derslerColumns = [
        'tarih' => 'Tarih',
        'gun' => 'Gün',
        'saat' => 'Saat',
        'sure' => 'Süre',
        'kurs' => 'Kurs',
        'sinif' => 'Sınıf',
        'durum' => 'Durum',
    ];
    $derslerOrder = array_keys($derslerColumns);
@endphp
<div class="lesson-tab-panel" data-egitmen-panel="dersler" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Dersler</div>
                <p class="table-subtitle">Kullanıcının atandığı kurslardaki tüm ders oturumları.</p>
            </div>
            <x-detail-table-tools :columns="$derslerColumns" :visible="$derslerOrder" excel-name="dersler" />
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="kullanici_dersler_cols"
                data-default-order='@json($derslerOrder)'
                data-default-visible='@json($derslerOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($derslerColumns as $key => $label)
                            <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dersler as $ders)
                        @php
                            $tarihStr = $ders->tarih?->toDateString();
                            $isCancelled = $ders->iptal_edildi;
                            $dersDurum = $isCancelled ? 'İptal Edildi' : ($ders->yoklama_alindi ? 'Alındı' : (($tarihStr && $tarihStr <= $bugun) ? 'Bekliyor' : 'Planlandı'));
                        @endphp
                        <tr class="{{ $isCancelled ? 'is-cancelled' : '' }}">
                            <td data-column="tarih" data-sort-value="{{ $tarihStr }}">{{ $ders->tarih?->format('d.m.Y') ?? '—' }}</td>
                            <td data-column="gun">{{ $ders->tarih?->locale('tr')->isoFormat('dddd') ?? '—' }}</td>
                            <td data-column="saat" data-sort-value="{{ substr((string) $ders->baslangic_saati, 0, 5) }}">{{ substr((string) $ders->baslangic_saati, 0, 5) }} – {{ substr((string) $ders->bitis_saati, 0, 5) }}</td>
                            <td data-column="sure">{{ rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.') }}</td>
                            <td data-column="kurs" data-sort-value="{{ $ders->kurs?->brans?->ad ?? ('Kurs #'.$ders->kurs?->kurs_no) }}" data-export-value="{{ ($ders->kurs?->brans?->ad ?? '') }} (Kurs #{{ $ders->kurs?->kurs_no }})">
                                <a href="{{ route('kurslar.show', ['kurs' => $ders->kurs_id, 'tab' => 'takvim']) }}" class="kurs-no">{{ $ders->kurs?->brans?->ad ?? ('Kurs #'.$ders->kurs?->kurs_no) }}</a>
                                <div class="takvim-kurs-no">Kurs #{{ $ders->kurs?->kurs_no }}</div>
                            </td>
                            <td data-column="sinif">{{ $ders->sinif ?: '—' }}</td>
                            <td data-column="durum" data-export-value="{{ $dersDurum }}">
                                @if ($ders->iptal_edildi)
                                    <span class="status status-iptal" title="{{ $ders->iptal_gerekcesi }}">İptal Edildi</span>
                                @elseif ($ders->yoklama_alindi)
                                    <span class="status status-tamamlanan">Alındı</span>
                                @elseif ($tarihStr && $tarihStr <= $bugun)
                                    <span class="status status-hazirlik">Bekliyor</span>
                                @else
                                    <span class="status status-hazirlik">Planlandı</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-title">Ders bulunamadı</div>
                                    <p class="empty-state-text">Bu kullanıcının kurslarında oluşturulmuş ders oturumu yok.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Yoklamalar --}}
@php
    $yoklamaColumns = [
        'tarih' => 'Tarih',
        'kurs' => 'Kurs',
        'saat' => 'Saat',
        'sinif' => 'Sınıf',
        'yoklama' => 'Yoklama',
        'katilim' => 'Katılım',
        'kaydeden' => 'Kaydeden',
    ];
    $yoklamaOrder = array_keys($yoklamaColumns);
@endphp
<div class="lesson-tab-panel" data-egitmen-panel="yoklamalar" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Yoklamalar</div>
                <p class="table-subtitle">Tarihi gelmiş ders oturumlarının yoklama durumu ve katılım özeti.</p>
            </div>
            <x-detail-table-tools :columns="$yoklamaColumns" :visible="$yoklamaOrder" excel-name="yoklamalar" />
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="kullanici_yoklama_cols"
                data-default-order='@json($yoklamaOrder)'
                data-default-visible='@json($yoklamaOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($yoklamaColumns as $key => $label)
                            <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($yoklamaDersleri as $ders)
                        @php
                            $yoklamaDurum = $ders->iptal_edildi ? 'İptal Edildi' : ($ders->yoklama_alindi ? 'Alındı' : 'Yoklama Alınmadı');
                            $katilimText = $ders->yoklama_alindi ? ((int) $ders->var_sayisi).' / '.((int) $ders->yoklamalar_count) : '—';
                        @endphp
                        <tr class="{{ $ders->iptal_edildi ? 'is-cancelled' : '' }}">
                            <td data-column="tarih" data-sort-value="{{ $ders->tarih?->toDateString() }}">{{ $ders->tarih?->format('d.m.Y') ?? '—' }}</td>
                            <td data-column="kurs" data-sort-value="{{ $ders->kurs?->brans?->ad ?? ('Kurs #'.$ders->kurs?->kurs_no) }}" data-export-value="{{ ($ders->kurs?->brans?->ad ?? '') }} (Kurs #{{ $ders->kurs?->kurs_no }})">
                                <a href="{{ route('kurslar.show', ['kurs' => $ders->kurs_id, 'tab' => 'yoklamalar']) }}" class="kurs-no">{{ $ders->kurs?->brans?->ad ?? ('Kurs #'.$ders->kurs?->kurs_no) }}</a>
                                <div class="takvim-kurs-no">Kurs #{{ $ders->kurs?->kurs_no }}</div>
                            </td>
                            <td data-column="saat" data-sort-value="{{ substr((string) $ders->baslangic_saati, 0, 5) }}">{{ substr((string) $ders->baslangic_saati, 0, 5) }} – {{ substr((string) $ders->bitis_saati, 0, 5) }}</td>
                            <td data-column="sinif">{{ $ders->sinif ?: '—' }}</td>
                            <td data-column="yoklama" data-export-value="{{ $yoklamaDurum }}">
                                @if ($ders->iptal_edildi)
                                    <span class="status status-iptal">İptal Edildi</span>
                                @elseif ($ders->yoklama_alindi)
                                    <span class="status status-tamamlanan">Alındı</span>
                                @else
                                    <span class="status status-hazirlik">Yoklama Alınmadı</span>
                                @endif
                            </td>
                            <td data-column="katilim" data-sort-value="{{ $ders->yoklama_alindi ? (int) $ders->var_sayisi : -1 }}" data-export-value="{{ $katilimText }}">
                                @if ($ders->yoklama_alindi)
                                    {{ (int) $ders->var_sayisi }} / {{ (int) $ders->yoklamalar_count }}
                                @else
                                    —
                                @endif
                            </td>
                            <td data-column="kaydeden">{{ $ders->yoklamaAlan?->tam_adi ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-title">Yoklama bulunamadı</div>
                                    <p class="empty-state-text">Tarihi gelmiş bir ders oturumu bulunmuyor.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Atanan Etkinlikler --}}
@php
    $etkinliklerColumns = [
        'etkinlik_no' => 'Etkinlik No',
        'ad' => 'Etkinlik',
        'tip' => 'Tip',
        'merkez' => 'Merkez',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'kontenjan' => 'Kontenjan',
        'kayit' => 'Kayıt',
        'durum' => 'Durum',
    ];
    $etkinliklerOrder = array_keys($etkinliklerColumns);
@endphp
<div class="lesson-tab-panel" data-egitmen-panel="etkinlikler" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Atanan Etkinlikler</div>
                <p class="table-subtitle">Bu kullanıcının sorumlu olarak atandığı tüm etkinlikler.</p>
            </div>
            <x-detail-table-tools :columns="$etkinliklerColumns" :visible="$etkinliklerOrder" excel-name="atanan-etkinlikler" />
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="kullanici_etkinlikler_cols"
                data-default-order='@json($etkinliklerOrder)'
                data-default-visible='@json($etkinliklerOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($etkinliklerColumns as $key => $label)
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
                            <td data-column="merkez">{{ $etkinlik->merkez?->ad ?? '—' }}</td>
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
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
                                    </div>
                                    <div class="empty-state-title">Etkinlik bulunamadı</div>
                                    <p class="empty-state-text">Bu kullanıcı henüz bir etkinliğe sorumlu olarak atanmamış.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Mesajlar --}}
<div class="lesson-tab-panel" data-egitmen-panel="mesajlar" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Mesajlar</div>
                <p class="table-subtitle">Bu kullanıcının gönderdiği tüm SMS ve e-posta kayıtları.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kanal</th>
                        <th>Kurs</th>
                        <th>İçerik</th>
                        <th>Sonuç</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mesajlar as $mesaj)
                        <tr>
                            <td>{{ $mesaj['created_at']?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($mesaj['kanal'] === 'sms')
                                    <span class="status status-aktif">SMS</span>
                                @else
                                    <span class="status status-hazirlik">E-posta</span>
                                @endif
                            </td>
                            <td>
                                @if ($mesaj['kurs_id'])
                                    <a href="{{ route('kurslar.show', ['kurs' => $mesaj['kurs_id'], 'tab' => 'mesajlar']) }}" class="kurs-no">{{ $mesaj['kurs_adi'] }}</a>
                                    <div class="takvim-kurs-no">Kurs #{{ $mesaj['kurs_no'] }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($mesaj['konu'])
                                    <div style="font-weight:600;">{{ $mesaj['konu'] }}</div>
                                @endif
                                <div class="takvim-kurs-no" style="margin-top:0;">{{ Str::limit($mesaj['mesaj'], 90) }}</div>
                            </td>
                            <td>{{ $mesaj['gonderilen'] }} / {{ $mesaj['toplam'] }}@if ($mesaj['atlanan'] > 0) <span class="takvim-kurs-no">({{ $mesaj['atlanan'] }} atlandı)</span>@endif</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-title">Mesaj bulunamadı</div>
                                    <p class="empty-state-text">Bu kullanıcı henüz SMS veya e-posta göndermemiş.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- SMS modal --}}
<div class="confirm-modal" id="egitmen-sms-modal" hidden
    data-send-url="{{ route('kullanicilar.sms.send', $kullanici) }}"
    data-ad="{{ $kullanici->tam_adi }}"
    data-telefon-var="{{ $telefonVar ? '1' : '0' }}"
>
    <div class="confirm-modal-backdrop" data-egitmen-sms-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="egitmen-sms-modal-title">
        <div class="confirm-modal-header">
            <h3 id="egitmen-sms-modal-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-egitmen-sms-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-egitmen-sms-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-egitmen-sms-no-telefon hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kullanıcı için kayıtlı telefon numarası bulunamadı.
            </p>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="egitmen-sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-egitmen-sms-insert="{ad_soyad}" title="İmleç konumuna ekler">{ad_soyad}</button>
                        <button type="button" class="sms-onizle-btn" data-egitmen-sms-onizle>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="egitmen-sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-egitmen-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, bilgilendirme mesajınız."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-egitmen-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-egitmen-sms-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-egitmen-sms-send>Gönder</button>
        </div>
    </div>
</div>

{{-- E-posta modal --}}
<div class="confirm-modal" id="egitmen-eposta-modal" hidden
    data-send-url="{{ route('kullanicilar.eposta.send', $kullanici) }}"
    data-ad="{{ $kullanici->tam_adi }}"
    data-email="{{ $kullanici->email }}"
    data-email-var="{{ $emailVar ? '1' : '0' }}"
>
    <div class="confirm-modal-backdrop" data-egitmen-eposta-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="egitmen-eposta-modal-title">
        <div class="confirm-modal-header">
            <h3 id="egitmen-eposta-modal-title" class="confirm-modal-title">E-posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-egitmen-eposta-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-egitmen-eposta-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-egitmen-eposta-no-email hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kullanıcı için kayıtlı e-posta adresi bulunamadı.
            </p>
            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="egitmen-eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-egitmen-eposta-insert="{ad_soyad}" data-egitmen-eposta-insert-target="konu" title="Konu alanına ekler">{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="egitmen-eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-egitmen-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="egitmen-eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-egitmen-eposta-insert="{ad_soyad}" data-egitmen-eposta-insert-target="mesaj" title="Mesaj alanına ekler">{ad_soyad}</button>
                        <button type="button" class="sms-onizle-btn" data-egitmen-eposta-onizle>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="egitmen-eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-egitmen-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, bilgilendirme mesajınız."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-egitmen-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-egitmen-eposta-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-egitmen-eposta-send>Gönder</button>
        </div>
    </div>
</div>

{{-- Şifre gönder modal --}}
<div class="confirm-modal" id="egitmen-sifre-modal" hidden
    data-send-url="{{ route('kullanicilar.sifre.send', $kullanici) }}"
    data-ad="{{ $kullanici->tam_adi }}"
    data-telefon-var="{{ $telefonVar ? '1' : '0' }}"
    data-email-var="{{ $emailVar ? '1' : '0' }}"
>
    <div class="confirm-modal-backdrop" data-egitmen-sifre-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="egitmen-sifre-modal-title">
        <div class="confirm-modal-header">
            <h3 id="egitmen-sifre-modal-title" class="confirm-modal-title">Şifre Gönder</h3>
            <button type="button" class="confirm-modal-x" data-egitmen-sifre-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p style="margin-bottom:14px;">
                <strong data-egitmen-sifre-ad></strong> için yeni bir geçici şifre oluşturulacak ve seçtiğiniz kanala gönderilecek.
                Mevcut şifre geçersiz hale gelir.
            </p>
            <div class="form-group" style="margin-bottom:0;">
                <label for="egitmen-sifre-kanal">Gönderim kanalı <span class="req">*</span></label>
                <select id="egitmen-sifre-kanal" class="form-control" data-egitmen-sifre-kanal>
                    @if ($telefonVar && $emailVar)
                        <option value="ikisi">SMS ve E-posta</option>
                    @endif
                    @if ($telefonVar)
                        <option value="sms">SMS</option>
                    @endif
                    @if ($emailVar)
                        <option value="eposta">E-posta</option>
                    @endif
                </select>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-egitmen-sifre-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-egitmen-sifre-send>Şifreyi Gönder</button>
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
