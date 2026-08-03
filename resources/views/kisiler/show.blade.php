@extends('layouts.admin')

@section('title', $kisi->tam_adi)

@section('content')
<div class="page-header">
    <div class="page-header-main">
        <div
            class="profil-avatar-wrap profil-avatar-wrap-sm"
            data-avatar-preview
            data-upload-url="{{ route('kisiler.foto.update', $kisi) }}"
            data-delete-url="{{ route('kisiler.foto.delete', $kisi) }}"
        >
            <label class="profil-avatar profil-avatar-lg profil-avatar-edit" title="Yeni fotoğraf yükle">
                @if ($kisi->profil_foto_url)
                    <img src="{{ $kisi->profil_foto_url }}" alt="Profil fotoğrafı" data-avatar-img>
                    <span class="profil-avatar-initials" data-avatar-initials hidden>{{ $kisi->bas_harfler ?: '?' }}</span>
                @else
                    <img src="" alt="Profil fotoğrafı" data-avatar-img hidden>
                    <span class="profil-avatar-initials" data-avatar-initials>{{ $kisi->bas_harfler ?: '?' }}</span>
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
                @unless ($kisi->profil_foto) hidden @endunless
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="page-header-text">
            <p class="page-eyebrow">Kişiler</p>
            <h1 class="page-title">{{ $kisi->tam_adi }}</h1>
            <p class="page-subtitle">Kişi bilgilerini görüntüleyin ve ilişkili kayıtları inceleyin.</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('kisiler.index') }}">Kişilere Dön</x-back-button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Cinsiyet</div>
        <div class="stat-value" style="font-size:16px;">
            <span class="status status-hazirlik">{{ $kisi->cinsiyet?->label() ?? '—' }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Durum</div>
        <div class="stat-value" style="font-size:16px;">
            @if ($kisi->aktif)
                <span class="status status-aktif">Aktif</span>
            @else
                <span class="status status-hazirlik">Pasif</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Toplam Başvuru</div>
        <div class="stat-value">{{ number_format($toplamBasvuru) }}</div>
    </div>
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Kurs Sayısı</div>
        <div class="stat-value">{{ number_format($aktifKursSayisi) }}</div>
    </div>
</div>

<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kişi Bilgileri</div>
            <p class="table-subtitle">İletişim ve kimlik bilgileri.</p>
        </div>
    </div>

    <div class="lesson-info-grid" style="margin-bottom: 0;">
        <div class="lesson-info-card">
            <div class="lesson-info-label">TC Kimlik No</div>
            <div class="lesson-info-value">{{ $kisi->tc_kimlik_no ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Doğum Tarihi</div>
            <div class="lesson-info-value">{{ $kisi->dogum_tarihi?->format('d.m.Y') ?? '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Doğum Yeri</div>
            <div class="lesson-info-value">{{ $kisi->dogum_yeri ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Medeni Durum</div>
            <div class="lesson-info-value">{{ $kisi->medeni_durum ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Uyruk</div>
            <div class="lesson-info-value">{{ $kisi->uyruk ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Anne Adı</div>
            <div class="lesson-info-value">{{ $kisi->anne_adi ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Baba Adı</div>
            <div class="lesson-info-value">{{ $kisi->baba_adi ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Telefon</div>
            <div class="lesson-info-value">{{ $kisi->telefon ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">E-posta</div>
            <div class="lesson-info-value">{{ $kisi->email ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">İl / İlçe</div>
            <div class="lesson-info-value">
                @if ($kisi->il || $kisi->ilce)
                    {{ trim(($kisi->il ?? '').' / '.($kisi->ilce ?? ''), ' /') }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Adres</div>
            <div class="lesson-info-value">{{ $kisi->adres ?: '—' }}</div>
        </div>
        <div class="lesson-info-card">
            <div class="lesson-info-label">Kayıt Tarihi</div>
            <div class="lesson-info-value">{{ $kisi->created_at?->format('d.m.Y') }}</div>
        </div>
    </div>
</div>

<div class="lesson-actions-block">
    <div class="lesson-actions-card">
        <div class="lesson-actions-card-head">
            <h3 class="lesson-actions-title">İşlemler</h3>
            <p class="lesson-actions-subtitle">Kişi bilgilerini düzenleyin; SMS veya e-posta gönderin</p>
        </div>
        <div class="lesson-actions-grid">
            <a href="{{ route('kisiler.edit', $kisi) }}" class="lesson-action-btn lesson-action-btn-primary">
                <span class="lesson-action-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </span>
                <span class="lesson-action-copy">
                    <span class="lesson-action-label">Düzenle</span>
                    <span class="lesson-action-hint">Kişi bilgilerini güncelle</span>
                </span>
            </a>
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
                    <span class="lesson-action-hint">{{ $telefonVar ? ($kisi->telefon ?: 'Telefon') : 'Telefon yok' }}</span>
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
                    <span class="lesson-action-hint">{{ $emailVar ? $kisi->email : 'E-posta yok' }}</span>
                </span>
            </button>
        </div>
    </div>
</div>

<div class="lesson-tabs" role="tablist" data-egitmen-tabs>
    <button type="button" class="lesson-tab is-active" data-egitmen-tab="basvurular" role="tab" aria-selected="true">Başvurular</button>
    <button type="button" class="lesson-tab" data-egitmen-tab="yoklamalar" role="tab" aria-selected="false">Yoklamalar</button>
    <button type="button" class="lesson-tab" data-egitmen-tab="mesajlar" role="tab" aria-selected="false">Mesajlar</button>
</div>

{{-- Başvurular --}}
@php
    $kursBasvuruSayisi = $basvurular->count();
    $etkinlikBasvuruSayisi = ($etkinlikBasvurulari ?? collect())->count();
    $aktifBasvuruTur = in_array(request('basvuru_tur'), ['kurs', 'etkinlik'], true)
        ? request('basvuru_tur')
        : 'kurs';
@endphp
<div
    class="lesson-tab-panel is-active"
    data-egitmen-panel="basvurular"
    data-kisi-basvurular-url="{{ route('kisiler.basvurular', $kisi) }}"
    role="tabpanel"
>
    <div class="mesaj-kanal-tabs" role="tablist" aria-label="Başvuru türü" data-kisi-basvuru-tur-tabs style="margin-bottom:16px;">
        <button
            type="button"
            class="mesaj-kanal-tab {{ $aktifBasvuruTur === 'kurs' ? 'is-active' : '' }}"
            data-kisi-basvuru-tur="kurs"
            role="tab"
            aria-selected="{{ $aktifBasvuruTur === 'kurs' ? 'true' : 'false' }}"
        >
            <span>Kurs Başvuruları</span>
            <span class="mesaj-kanal-tab-count" data-kisi-basvuru-count="kurs">{{ number_format($kursBasvuruSayisi) }}</span>
        </button>
        <button
            type="button"
            class="mesaj-kanal-tab {{ $aktifBasvuruTur === 'etkinlik' ? 'is-active' : '' }}"
            data-kisi-basvuru-tur="etkinlik"
            role="tab"
            aria-selected="{{ $aktifBasvuruTur === 'etkinlik' ? 'true' : 'false' }}"
        >
            <span>Etkinlik Başvuruları</span>
            <span class="mesaj-kanal-tab-count" data-kisi-basvuru-count="etkinlik">{{ number_format($etkinlikBasvuruSayisi) }}</span>
        </button>
    </div>

    <div data-kisi-basvuru-panel="kurs" @if ($aktifBasvuruTur !== 'kurs') hidden @endif>
        <div data-kisi-basvuru-content>
            @include('kisiler._kurs_basvurular', ['basvurular' => $basvurular])
        </div>
    </div>

    <div data-kisi-basvuru-panel="etkinlik" @if ($aktifBasvuruTur !== 'etkinlik') hidden @endif>
        <div data-kisi-basvuru-content>
            @include('kisiler._etkinlik_basvurular', ['etkinlikBasvurulari' => $etkinlikBasvurulari ?? collect()])
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
        'durum' => 'Durum',
        'aciklama' => 'Açıklama',
    ];
    $yoklamaOrder = array_keys($yoklamaColumns);
@endphp
<div class="lesson-tab-panel" data-egitmen-panel="yoklamalar" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Yoklamalar</div>
                <p class="table-subtitle">Bu kişinin ders bazında yoklama kayıtları.</p>
            </div>
            <x-detail-table-tools :columns="$yoklamaColumns" :visible="$yoklamaOrder" excel-name="kisi-yoklamalar" />
        </div>

        <div class="detail-filter-bar" data-detail-filters>
            <div class="detail-filter-field">
                <span class="detail-filter-label">Kurs</span>
                <input type="text" class="form-control" data-filter-column="kurs" data-filter-type="text" placeholder="Ara...">
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Sınıf</span>
                <input type="text" class="form-control" data-filter-column="sinif" data-filter-type="text" placeholder="Ara...">
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Durum</span>
                <select class="form-control" data-filter-column="durum" data-filter-type="select">
                    <option value="">Tümü</option>
                    <option value="Var">Var</option>
                    <option value="Yok">Yok</option>
                    <option value="İzinli">İzinli</option>
                    <option value="Ders İptal">Ders İptal</option>
                </select>
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Başlangıç</span>
                <input type="date" class="form-control" data-filter-column="tarih" data-filter-type="date-from">
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Bitiş</span>
                <input type="date" class="form-control" data-filter-column="tarih" data-filter-type="date-to">
            </div>
            <button type="button" class="detail-filter-clear" data-filter-clear>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                Temizle
            </button>
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="kisi_yoklama_cols"
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
                    @forelse ($yoklamalar as $yoklama)
                        @php $ders = $yoklama->ders; @endphp
                        <tr class="{{ $ders?->iptal_edildi ? 'is-cancelled' : '' }}">
                            <td data-column="tarih" data-sort-value="{{ $ders?->tarih?->toDateString() }}">{{ $ders?->tarih?->format('d.m.Y') ?? '—' }}</td>
                            <td data-column="kurs" data-sort-value="{{ $ders?->kurs?->brans?->ad ?? ('Kurs #'.$ders?->kurs?->kurs_no) }}" data-export-value="{{ ($ders?->kurs?->brans?->ad ?? '') }} (Kurs #{{ $ders?->kurs?->kurs_no }})">
                                @if ($ders?->kurs)
                                    <a href="{{ route('kurslar.show', ['kurs' => $ders->kurs_id, 'tab' => 'yoklamalar']) }}" class="kurs-no">{{ $ders->kurs->brans?->ad ?? ('Kurs #'.$ders->kurs->kurs_no) }}</a>
                                    <div class="takvim-kurs-no">Kurs #{{ $ders->kurs->kurs_no }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-column="saat" data-sort-value="{{ substr((string) $ders?->baslangic_saati, 0, 5) }}">
                                @if ($ders)
                                    {{ substr((string) $ders->baslangic_saati, 0, 5) }} – {{ substr((string) $ders->bitis_saati, 0, 5) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td data-column="sinif">{{ $ders?->sinif ?: '—' }}</td>
                            <td data-column="durum" data-export-value="{{ $ders?->iptal_edildi ? 'Ders İptal' : ($yoklama->durum?->label() ?? '—') }}">
                                @if ($ders?->iptal_edildi)
                                    <span class="status status-iptal">Ders İptal</span>
                                @elseif ($yoklama->durum)
                                    <span class="status {{ $yoklama->durum->statusClass() }}">{{ $yoklama->durum->label() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-column="aciklama">{{ $yoklama->aciklama ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-title">Yoklama bulunamadı</div>
                                    <p class="empty-state-text">Bu kişi için kayıtlı yoklama bilgisi yok.</p>
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
@php
    $mesajColumns = [
        'tarih' => 'Tarih',
        'kanal' => 'Kanal',
        'kurs' => 'Kurs',
        'hedef' => 'Hedef',
        'durum' => 'Durum',
        'icerik' => 'İçerik',
    ];
    $mesajOrder = array_keys($mesajColumns);
@endphp
<div class="lesson-tab-panel" data-egitmen-panel="mesajlar" role="tabpanel">
    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Mesajlar</div>
                <p class="table-subtitle">Bu kişiye gönderilen SMS ve e-posta kayıtları.</p>
            </div>
            <x-detail-table-tools :columns="$mesajColumns" :visible="$mesajOrder" excel-name="kisi-mesajlar" />
        </div>

        <div class="detail-filter-bar" data-detail-filters>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Kanal</span>
                <select class="form-control" data-filter-column="kanal" data-filter-type="select">
                    <option value="">Tümü</option>
                    <option value="SMS">SMS</option>
                    <option value="E-posta">E-posta</option>
                </select>
            </div>
            <div class="detail-filter-field">
                <span class="detail-filter-label">Kurs</span>
                <input type="text" class="form-control" data-filter-column="kurs" data-filter-type="text" placeholder="Ara...">
            </div>
            <div class="detail-filter-field">
                <span class="detail-filter-label">İçerik</span>
                <input type="text" class="form-control" data-filter-column="icerik" data-filter-type="text" placeholder="Ara...">
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Durum</span>
                <select class="form-control" data-filter-column="durum" data-filter-type="select">
                    <option value="">Tümü</option>
                    <option value="Gönderildi">Gönderildi</option>
                    <option value="Başarısız">Başarısız</option>
                </select>
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Başlangıç</span>
                <input type="date" class="form-control" data-filter-column="tarih" data-filter-type="date-from">
            </div>
            <div class="detail-filter-field detail-filter-field-sm">
                <span class="detail-filter-label">Bitiş</span>
                <input type="date" class="form-control" data-filter-column="tarih" data-filter-type="date-to">
            </div>
            <button type="button" class="detail-filter-clear" data-filter-clear>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                Temizle
            </button>
        </div>

        <div class="table-wrapper">
            <table
                class="data-table"
                data-detail-table="kisi_mesajlar_cols"
                data-default-order='@json($mesajOrder)'
                data-default-visible='@json($mesajOrder)'
            >
                <thead>
                    <tr>
                        @foreach ($mesajColumns as $key => $label)
                            <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mesajlar as $mesaj)
                        @php
                            $durumEtiket = $mesaj['durum'] === 'gonderildi' ? 'Gönderildi' : 'Başarısız';
                            $icerik = trim(($mesaj['konu'] ? $mesaj['konu'].' — ' : '').$mesaj['mesaj']);
                        @endphp
                        <tr>
                            <td data-column="tarih" data-sort-value="{{ $mesaj['created_at']?->toDateString() }}">{{ $mesaj['created_at']?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td data-column="kanal" data-export-value="{{ $mesaj['kanal'] === 'sms' ? 'SMS' : 'E-posta' }}">
                                @if ($mesaj['kanal'] === 'sms')
                                    <span class="status status-aktif">SMS</span>
                                @else
                                    <span class="status status-hazirlik">E-posta</span>
                                @endif
                            </td>
                            <td data-column="kurs" data-sort-value="{{ $mesaj['kurs_adi'] }}" data-export-value="{{ $mesaj['kurs_adi'] ? $mesaj['kurs_adi'].' (Kurs #'.$mesaj['kurs_no'].')' : '' }}">
                                @if ($mesaj['kurs_id'])
                                    <a href="{{ route('kurslar.show', ['kurs' => $mesaj['kurs_id'], 'tab' => 'mesajlar']) }}" class="kurs-no">{{ $mesaj['kurs_adi'] }}</a>
                                    <div class="takvim-kurs-no">Kurs #{{ $mesaj['kurs_no'] }}</div>
                                @else
                                    <span style="color:#b5b5c3;">Doğrudan</span>
                                @endif
                            </td>
                            <td data-column="hedef">{{ $mesaj['hedef'] ?: '—' }}</td>
                            <td data-column="durum" data-export-value="{{ $durumEtiket }}">
                                @if ($mesaj['durum'] === 'gonderildi')
                                    <span class="status status-tamamlanan">Gönderildi</span>
                                @else
                                    <span class="status status-iptal">Başarısız</span>
                                @endif
                            </td>
                            <td data-column="icerik" data-export-value="{{ $icerik }}">
                                @if ($mesaj['konu'])
                                    <div style="font-weight:600;">{{ $mesaj['konu'] }}</div>
                                @endif
                                <div class="takvim-kurs-no" style="margin-top:0;">{{ \Illuminate\Support\Str::limit($mesaj['mesaj'], 90) }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-title">Mesaj bulunamadı</div>
                                    <p class="empty-state-text">Bu kişiye henüz SMS veya e-posta gönderilmemiş.</p>
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
    data-send-url="{{ route('kisiler.sms.send', $kisi) }}"
    data-ad="{{ $kisi->tam_adi }}"
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
                Bu kişi için kayıtlı telefon numarası bulunamadı.
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
    data-send-url="{{ route('kisiler.eposta.send', $kisi) }}"
    data-ad="{{ $kisi->tam_adi }}"
    data-email="{{ $kisi->email }}"
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
                Bu kişi için kayıtlı e-posta adresi bulunamadı.
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
