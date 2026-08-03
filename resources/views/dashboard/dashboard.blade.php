@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $kursDurumMax = max(1, $kursDurumDagilim->max('count') ?? 0);
    $etkinlikDurumMax = max(1, $etkinlikDurumDagilim->max('count') ?? 0);
    $basvuruDurumMax = max(1, $basvuruDurumDagilim->max('count') ?? 0);
    $etkinlikBasvuruDurumMax = max(1, $etkinlikBasvuruDurumDagilim->max('count') ?? 0);
    $aylikMax = max(1, $aylikBasvuru->max(fn ($i) => max($i['kurs'], $i['etkinlik'])) ?? 0);
    $merkezMax = max(1, $merkezDagilim->max('count') ?? 0);
    $merkezEtkinlikMax = max(1, $merkezEtkinlikDagilim->max('count') ?? 0);
    $bransMax = max(1, $bransDagilim->max('count') ?? 0);
    $etkinlikTipiMax = max(1, $etkinlikTipiDagilim->max('count') ?? 0);
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Genel Bakış</p>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Kurs, etkinlik, başvuru ve kullanıcı istatistiklerinin genel görünümü.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('anasayfa') }}" class="btn-cta">
            <span class="btn-cta-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
            </span>
            <span class="btn-cta-text">Anasayfa</span>
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Toplam Kurs</div>
        <div class="stat-value">{{ number_format($toplamKurs) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">{{ number_format($aktifKurs) }} aktif</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Kurs Başvurusu</div>
        <div class="stat-value">{{ number_format($toplamBasvuru) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">{{ number_format($kesinKayit) }} kesin kayıt</div>
    </div>
    <div class="stat-card stat-card-aktif">
        <div class="stat-label">Toplam Etkinlik</div>
        <div class="stat-value">{{ number_format($toplamEtkinlik) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">{{ number_format($aktifEtkinlik) }} aktif</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Etkinlik Başvurusu</div>
        <div class="stat-value">{{ number_format($toplamEtkinlikBasvuru) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">{{ number_format($etkinlikKesinKayit) }} kesin kayıt</div>
    </div>
</div>

<div class="stats-grid" style="margin-top:16px;">
    <div class="stat-card stat-card-kesin">
        <div class="stat-label">Toplam Kullanıcı</div>
        <div class="stat-value">{{ number_format($toplamKullanici) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">{{ number_format($ogretmenSayisi) }} öğretmen · {{ number_format($personelSayisi) }} personel</div>
    </div>
    <div class="stat-card stat-card-hazirlik">
        <div class="stat-label">Kurs Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($kesinKayit) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">Tüm zamanlar</div>
    </div>
    <div class="stat-card stat-card-hazirlik">
        <div class="stat-label">Etkinlik Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($etkinlikKesinKayit) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">Tüm zamanlar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Merkez</div>
        <div class="stat-value">{{ number_format($merkezSayisi) }}</div>
        <div class="table-subtitle" style="margin-top:6px;">{{ number_format($etkinlikTipiSayisi) }} etkinlik tipi</div>
    </div>
</div>

<p class="dash-section-label" style="margin-top:28px;">Durum dağılımları</p>
<div class="dash-charts">
    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Kurs Durum Dağılımı</h2>
                <p class="card-section-desc">Kursların durumlarına göre sayıları.</p>
            </div>
        </div>
        <div class="dash-bars">
            @foreach ($kursDurumDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-{{ $item['kod'] }}" style="width: {{ round(($item['count'] / $kursDurumMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Etkinlik Durum Dağılımı</h2>
                <p class="card-section-desc">Etkinliklerin durumlarına göre sayıları.</p>
            </div>
        </div>
        <div class="dash-bars">
            @foreach ($etkinlikDurumDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-{{ $item['kod'] }}" style="width: {{ round(($item['count'] / $etkinlikDurumMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="dash-charts" style="margin-top:24px;">
    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Kurs Başvuru Durumları</h2>
                <p class="card-section-desc">Kurs başvurularının durumlarına göre sayıları.</p>
            </div>
        </div>
        <div class="dash-bars">
            @forelse ($basvuruDurumDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ round(($item['count'] / $basvuruDurumMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Başvuru durumu tanımlı değil.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Etkinlik Başvuru Durumları</h2>
                <p class="card-section-desc">Etkinlik başvurularının durumlarına göre sayıları.</p>
            </div>
        </div>
        <div class="dash-bars">
            @forelse ($etkinlikBasvuruDurumDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-tamamlanan" style="width: {{ round(($item['count'] / $etkinlikBasvuruDurumMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Etkinlik başvuru durumu tanımlı değil.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Aylık Başvuru Trendi --}}
<div class="card" style="margin-top:24px;">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Son 6 Ay Başvuru Trendi</h2>
            <p class="card-section-desc">Aylara göre alınan kurs ve etkinlik başvuruları.</p>
        </div>
        <div class="vchart-legend">
            <span class="vchart-legend-item"><span class="vchart-legend-swatch is-kurs"></span> Kurs</span>
            <span class="vchart-legend-item"><span class="vchart-legend-swatch is-etkinlik"></span> Etkinlik</span>
        </div>
    </div>
    <div class="vchart vchart-dual">
        @foreach ($aylikBasvuru as $item)
            <div class="vchart-col">
                <div class="vchart-count">{{ number_format($item['kurs'] + $item['etkinlik']) }}</div>
                <div class="vchart-bar-wrap vchart-bar-wrap-dual">
                    <div class="vchart-bar is-kurs" style="height: {{ max(3, round(($item['kurs'] / $aylikMax) * 100)) }}%;" title="Kurs: {{ $item['kurs'] }}"></div>
                    <div class="vchart-bar is-etkinlik" style="height: {{ max(3, round(($item['etkinlik'] / $aylikMax) * 100)) }}%;" title="Etkinlik: {{ $item['etkinlik'] }}"></div>
                </div>
                <div class="vchart-label">{{ $item['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

<p class="dash-section-label" style="margin-top:28px;">Merkez ve kategori</p>
<div class="dash-charts">
    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Merkez Bazlı Aktif Kurs</h2>
                <p class="card-section-desc">En çok aktif kursa sahip merkezler.</p>
            </div>
        </div>
        <div class="dash-bars">
            @forelse ($merkezDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-aktif" style="width: {{ round(($item['count'] / $merkezMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Merkez kaydı bulunamadı.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Merkez Bazlı Aktif Etkinlik</h2>
                <p class="card-section-desc">En çok aktif etkinliğe sahip merkezler.</p>
            </div>
        </div>
        <div class="dash-bars">
            @forelse ($merkezEtkinlikDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-aktif" style="width: {{ round(($item['count'] / $merkezEtkinlikMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Merkez kaydı bulunamadı.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="dash-charts" style="margin-top:24px;">
    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Branş Bazlı Kurs</h2>
                <p class="card-section-desc">En çok kursa sahip branşlar.</p>
            </div>
        </div>
        <div class="dash-bars">
            @forelse ($bransDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-tamamlanan" style="width: {{ round(($item['count'] / $bransMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Branş kaydı bulunamadı.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Tip Bazlı Etkinlik</h2>
                <p class="card-section-desc">En çok etkinliğe sahip tipler.</p>
            </div>
        </div>
        <div class="dash-bars">
            @forelse ($etkinlikTipiDagilim as $item)
                <div class="bar-row">
                    <div class="bar-label">{{ $item['label'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill is-tamamlanan" style="width: {{ round(($item['count'] / $etkinlikTipiMax) * 100) }}%;"></div>
                    </div>
                    <div class="bar-value">{{ number_format($item['count']) }}</div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px;">
                    <p class="empty-state-text">Etkinlik tipi kaydı bulunamadı.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Genel Özet --}}
<div class="card" style="margin-top:24px;">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Genel Özet</h2>
            <p class="card-section-desc">Tanım kayıtlarının toplamları.</p>
        </div>
    </div>
    <div class="dash-mini-grid">
        <a href="{{ route('merkezler.index') }}" class="quick-link">
            <span class="quick-link-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
            </span>
            <span>
                <span class="quick-link-label">{{ number_format($merkezSayisi) }} Merkez</span>
                <span class="quick-link-hint">Merkez yönetimi</span>
            </span>
        </a>
        <a href="{{ route('alanlar.index') }}" class="quick-link">
            <span class="quick-link-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M4 9h16"/><path d="M9 4v16"/></svg>
            </span>
            <span>
                <span class="quick-link-label">{{ number_format($alanSayisi) }} Alan</span>
                <span class="quick-link-hint">Alan yönetimi</span>
            </span>
        </a>
        <a href="{{ route('branslar.index') }}" class="quick-link">
            <span class="quick-link-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
            </span>
            <span>
                <span class="quick-link-label">{{ number_format($bransSayisi) }} Branş</span>
                <span class="quick-link-hint">Branş yönetimi</span>
            </span>
        </a>
        @yetki('etkinlik.goruntule')
        <a href="{{ route('etkinlikler.index') }}" class="quick-link">
            <span class="quick-link-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
            </span>
            <span>
                <span class="quick-link-label">{{ number_format($etkinlikTipiSayisi) }} Etkinlik Tipi</span>
                <span class="quick-link-hint">Etkinlik yönetimi</span>
            </span>
        </a>
        @else
        <div class="quick-link">
            <span class="quick-link-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
            </span>
            <span>
                <span class="quick-link-label">{{ number_format($etkinlikTipiSayisi) }} Etkinlik Tipi</span>
                <span class="quick-link-hint">Tanım kayıtları</span>
            </span>
        </div>
        @endyetki
    </div>
</div>
@endsection
