@extends('layouts.admin')

@section('title', 'Anasayfa')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Genel Bakış</p>
        <h1 class="page-title">Merhaba, {{ auth()->user()?->ad ?: 'Hoş geldiniz' }}</h1>
        <p class="page-subtitle">{{ now()->locale('tr')->isoFormat('D MMMM YYYY, dddd') }} · Bugünkü özet ve son hareketler.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('dashboard') }}" class="btn-cta">
            <span class="btn-cta-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
            </span>
            <span class="btn-cta-text">Dashboard</span>
        </a>
    </div>
</div>

<p class="dash-section-label">Kurs</p>
<div class="stats-grid">
    @yetki('kurs.goruntule')
    <a href="{{ route('kurslar.index', ['durum' => 'aktif']) }}" class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Kurs</div>
        <div class="stat-value">{{ number_format($aktifKursSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Kurs</div>
        <div class="stat-value">{{ number_format($aktifKursSayisi) }}</div>
    </div>
    @endyetki

    @yetki('takvim.goruntule')
    <a href="{{ route('takvim.index', ['ay' => now()->format('Y-m'), 'gorunum' => 'liste', 'gun' => now()->toDateString()]) }}" class="stat-card">
        <div class="stat-label">Bugünkü Ders</div>
        <div class="stat-value">{{ number_format($bugunkuDersSayisi) }}</div>
    </a>
    @else
    <div class="stat-card">
        <div class="stat-label">Bugünkü Ders</div>
        <div class="stat-value">{{ number_format($bugunkuDersSayisi) }}</div>
    </div>
    @endyetki

    @yetki('basvuru.goruntule')
    <a href="{{ route('basvurular.index', ['basvuru_durum' => 'onay_bekliyor']) }}" class="stat-card stat-card-hazirlik">
        <div class="stat-label">Onay Bekleyen Başvuru</div>
        <div class="stat-value">{{ number_format($onayBekleyenSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-hazirlik">
        <div class="stat-label">Onay Bekleyen Başvuru</div>
        <div class="stat-value">{{ number_format($onayBekleyenSayisi) }}</div>
    </div>
    @endyetki

    @yetki('basvuru.goruntule')
    <a href="{{ route('basvurular.index', ['basvuru_durum' => 'kesin_kayit']) }}" class="stat-card stat-card-kesin">
        <div class="stat-label">Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($kesinKayitSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-kesin">
        <div class="stat-label">Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($kesinKayitSayisi) }}</div>
    </div>
    @endyetki
</div>

<p class="dash-section-label">Etkinlik</p>
<div class="stats-grid">
    @yetki('etkinlik.goruntule')
    <a href="{{ route('etkinlikler.index', ['durum' => 'aktif']) }}" class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Etkinlik</div>
        <div class="stat-value">{{ number_format($aktifEtkinlikSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Aktif Etkinlik</div>
        <div class="stat-value">{{ number_format($aktifEtkinlikSayisi) }}</div>
    </div>
    @endyetki

    @yetki('etkinlik.goruntule')
    <a href="{{ route('etkinlikler.index', ['basvuru_durumu' => 'acik']) }}" class="stat-card">
        <div class="stat-label">Başvuruya Açık</div>
        <div class="stat-value">{{ number_format($basvuruyaAcikEtkinlikSayisi) }}</div>
    </a>
    @else
    <div class="stat-card">
        <div class="stat-label">Başvuruya Açık</div>
        <div class="stat-value">{{ number_format($basvuruyaAcikEtkinlikSayisi) }}</div>
    </div>
    @endyetki

    @yetki('etkinlik_basvuru.goruntule')
    <a href="{{ route('etkinlik-basvurulari.index', ['basvuru_durum' => 'onay_bekliyor']) }}" class="stat-card stat-card-hazirlik">
        <div class="stat-label">Onay Bekleyen Etkinlik Başvurusu</div>
        <div class="stat-value">{{ number_format($etkinlikOnayBekleyenSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-hazirlik">
        <div class="stat-label">Onay Bekleyen Etkinlik Başvurusu</div>
        <div class="stat-value">{{ number_format($etkinlikOnayBekleyenSayisi) }}</div>
    </div>
    @endyetki

    @yetki('etkinlik_basvuru.goruntule')
    <a href="{{ route('etkinlik-basvurulari.index', ['basvuru_durum' => 'kesin_kayit']) }}" class="stat-card stat-card-kesin">
        <div class="stat-label">Etkinlik Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($etkinlikKesinKayitSayisi) }}</div>
    </a>
    @else
    <div class="stat-card stat-card-kesin">
        <div class="stat-label">Etkinlik Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($etkinlikKesinKayitSayisi) }}</div>
    </div>
    @endyetki
</div>

<div class="dash-cols">
    {{-- Yaklaşan Dersler --}}
    <div class="card table-card" style="margin-bottom:0;">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Yaklaşan Dersler</div>
                <p class="table-subtitle">Önümüzdeki 7 gün içindeki aktif kurs oturumları.</p>
            </div>
            <a href="{{ route('takvim.index') }}" class="btn-cta">
                <span class="btn-cta-mark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                </span>
                <span class="btn-cta-text">Takvim</span>
            </a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Saat</th>
                        <th>Kurs</th>
                        <th>Merkez</th>
                        <th>Sınıf</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($yaklasanDersler as $ders)
                        <tr>
                            <td>{{ $ders->tarih?->locale('tr')->isoFormat('D MMM, ddd') ?? '—' }}</td>
                            <td>{{ substr((string) $ders->baslangic_saati, 0, 5) }} – {{ substr((string) $ders->bitis_saati, 0, 5) }}</td>
                            <td>
                                <a href="{{ route('kurslar.show', ['kurs' => $ders->kurs_id, 'tab' => 'takvim']) }}" class="kurs-no">{{ $ders->kurs?->brans?->ad ?? ('Kurs #'.$ders->kurs?->kurs_no) }}</a>
                                <div class="takvim-kurs-no">Kurs #{{ $ders->kurs?->kurs_no }}</div>
                            </td>
                            <td>{{ $ders->kurs?->merkez?->ad ?? '—' }}</td>
                            <td>{{ $ders->sinif ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-title">Yaklaşan ders yok</div>
                                    <p class="empty-state-text">Önümüzdeki 7 gün içinde planlanmış aktif kurs oturumu bulunmuyor.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hızlı Erişim --}}
    @anyYetki(['kurs.olustur', 'etkinlik.olustur', 'kullanici.olustur', 'kurs.goruntule', 'etkinlik.goruntule', 'basvuru.goruntule', 'etkinlik_basvuru.goruntule', 'kullanici.goruntule', 'merkez.goruntule'])
    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Hızlı Erişim</h2>
                <p class="card-section-desc">Sık kullanılan sayfalar.</p>
            </div>
        </div>

        <div class="quick-links">
            @yetki('kurs.olustur')
            <a href="{{ route('kurslar.create') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Yeni Kurs</span>
                    <span class="quick-link-hint">Kurs oluştur</span>
                </span>
            </a>
            @endyetki
            @yetki('etkinlik.olustur')
            <a href="{{ route('etkinlikler.create') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Yeni Etkinlik</span>
                    <span class="quick-link-hint">Etkinlik oluştur</span>
                </span>
            </a>
            @endyetki
            @yetki('kullanici.olustur')
            <a href="{{ route('kullanicilar.create') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Yeni Kullanıcı</span>
                    <span class="quick-link-hint">Kullanıcı ekle</span>
                </span>
            </a>
            @endyetki
            @yetki('kurs.goruntule')
            <a href="{{ route('kurslar.index') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Kurslar</span>
                    <span class="quick-link-hint">Tüm kurslar</span>
                </span>
            </a>
            @endyetki
            @yetki('etkinlik.goruntule')
            <a href="{{ route('etkinlikler.index') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Etkinlikler</span>
                    <span class="quick-link-hint">Tüm etkinlikler</span>
                </span>
            </a>
            @endyetki
            @yetki('basvuru.goruntule')
            <a href="{{ route('basvurular.index') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h2"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Kurs Başvuruları</span>
                    <span class="quick-link-hint">Kurs başvuruları</span>
                </span>
            </a>
            @endyetki
            @yetki('etkinlik_basvuru.goruntule')
            <a href="{{ route('etkinlik-basvurulari.index') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h6"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Etkinlik Başvuruları</span>
                    <span class="quick-link-hint">Etkinlik başvuruları</span>
                </span>
            </a>
            @endyetki
            @yetki('kullanici.goruntule')
            <a href="{{ route('kullanicilar.index') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Kullanıcılar</span>
                    <span class="quick-link-hint">Kullanıcı yönetimi</span>
                </span>
            </a>
            @endyetki
            @yetki('merkez.goruntule')
            <a href="{{ route('merkezler.index') }}" class="quick-link">
                <span class="quick-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                </span>
                <span>
                    <span class="quick-link-label">Merkezler</span>
                    <span class="quick-link-hint">Merkez yönetimi</span>
                </span>
            </a>
            @endyetki
        </div>
    </div>
    @endanyYetki
</div>

{{-- Yaklaşan Etkinlikler --}}
<div class="card table-card" style="margin-top:24px;">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Yaklaşan Etkinlikler</div>
            <p class="table-subtitle">Önümüzdeki 7 gün içinde başlayan aktif etkinlikler.</p>
        </div>
        @yetki('etkinlik.goruntule')
        <a href="{{ route('etkinlikler.index', ['durum' => 'aktif']) }}" class="btn-cta">
            <span class="btn-cta-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </span>
            <span class="btn-cta-text">Tümünü Gör</span>
        </a>
        @endyetki
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Etkinlik</th>
                    <th>Tip</th>
                    <th>Merkez</th>
                    <th>Başvuru Durumu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($yaklasanEtkinlikler as $etkinlik)
                    <tr>
                        <td>
                            {{ $etkinlik->baslangic_tarihi?->locale('tr')->isoFormat('D MMM, ddd') ?? '—' }}
                            @if ($etkinlik->bitis_tarihi && $etkinlik->bitis_tarihi->toDateString() !== $etkinlik->baslangic_tarihi?->toDateString())
                                <div class="takvim-kurs-no">– {{ $etkinlik->bitis_tarihi->locale('tr')->isoFormat('D MMM') }}</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('etkinlikler.show', $etkinlik) }}" class="kurs-no">{{ $etkinlik->ad ?: ('Etkinlik #'.$etkinlik->etkinlik_no) }}</a>
                            <div class="takvim-kurs-no">#{{ $etkinlik->etkinlik_no }}</div>
                        </td>
                        <td>{{ $etkinlik->etkinlikTipi?->ad ?? '—' }}</td>
                        <td>{{ $etkinlik->merkez?->ad ?? '—' }}</td>
                        <td>
                            <span class="status {{ $etkinlik->basvuruDurumuStatusClass() }}">{{ $etkinlik->basvuruDurumuLabel() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-title">Yaklaşan etkinlik yok</div>
                                <p class="empty-state-text">Önümüzdeki 7 gün içinde başlayan aktif etkinlik bulunmuyor.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Son Başvurular: Kurs + Etkinlik --}}
<div class="dash-charts" style="margin-top:24px;">
    <div class="card table-card" style="margin-bottom:0;">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Son Kurs Başvuruları</div>
                <p class="table-subtitle">En son yapılan kurs başvuruları.</p>
            </div>
            @yetki('basvuru.goruntule')
            <a href="{{ route('basvurular.index') }}" class="btn-cta">
                <span class="btn-cta-mark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                <span class="btn-cta-text">Tümünü Gör</span>
            </a>
            @endyetki
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Katılımcı</th>
                        <th>Kurs</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sonBasvurular as $basvuru)
                        <tr>
                            <td>{{ $basvuru->kisi?->tam_adi ?? $basvuru->basvuran?->tam_adi ?? '—' }}</td>
                            <td>
                                @if ($basvuru->kurs)
                                    <a href="{{ route('kurslar.show', $basvuru->kurs) }}" class="kurs-no">{{ $basvuru->kurs?->brans?->ad ?? ('Kurs #'.$basvuru->kurs?->kurs_no) }}</a>
                                    <div class="takvim-kurs-no">#{{ $basvuru->kurs?->kurs_no }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($basvuru->durum)
                                    <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $basvuru->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-state-title">Başvuru bulunamadı</div>
                                    <p class="empty-state-text">Henüz kurs başvurusu yapılmamış.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card table-card" style="margin-bottom:0;">
        <div class="table-toolbar">
            <div>
                <div class="table-title">Son Etkinlik Başvuruları</div>
                <p class="table-subtitle">En son yapılan etkinlik başvuruları.</p>
            </div>
            @yetki('etkinlik_basvuru.goruntule')
            <a href="{{ route('etkinlik-basvurulari.index') }}" class="btn-cta">
                <span class="btn-cta-mark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                <span class="btn-cta-text">Tümünü Gör</span>
            </a>
            @endyetki
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Katılımcı</th>
                        <th>Etkinlik</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sonEtkinlikBasvurulari as $basvuru)
                        <tr>
                            <td>{{ $basvuru->kisi?->tam_adi ?? $basvuru->basvuran?->tam_adi ?? '—' }}</td>
                            <td>
                                @if ($basvuru->etkinlik)
                                    <a href="{{ route('etkinlikler.show', $basvuru->etkinlik) }}" class="kurs-no">{{ $basvuru->etkinlik?->ad ?? ('Etkinlik #'.$basvuru->etkinlik?->etkinlik_no) }}</a>
                                    <div class="takvim-kurs-no">#{{ $basvuru->etkinlik?->etkinlik_no }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($basvuru->durum)
                                    <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $basvuru->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-state-title">Başvuru bulunamadı</div>
                                    <p class="empty-state-text">Henüz etkinlik başvurusu yapılmamış.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
