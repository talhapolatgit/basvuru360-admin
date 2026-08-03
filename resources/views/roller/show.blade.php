@extends('layouts.admin')

@section('title', $rol->ad)

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Roller</p>
        <h1 class="page-title">{{ $rol->ad }}</h1>
        <p class="page-subtitle">Rol detayları ve atanmış yetkiler.</p>
    </div>
    <div class="flex items-center gap-2">
        @yetki('rol.yonet')
            <x-cta-button :href="route('roller.edit', $rol)" icon="edit">Düzenle</x-cta-button>
        @endyetki
        <x-back-button href="{{ route('roller.index') }}">Rollere Dön</x-back-button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Kod</div>
        <div class="stat-value" style="font-size:16px;">{{ $rol->kod }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Durum</div>
        <div class="stat-value" style="font-size:16px;">
            @if ($rol->aktif)
                <span class="status status-aktif">Aktif</span>
            @else
                <span class="status status-hazirlik">Pasif</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Yetki Sayısı</div>
        <div class="stat-value">{{ $rol->tum_yetkiler ? 'Tümü' : number_format($rol->yetkiler->count()) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Kullanıcı Sayısı</div>
        <div class="stat-value">{{ number_format($rol->kullanicilar->count()) }}</div>
    </div>
</div>

@if ($rol->aciklama)
    <div class="card" style="margin-bottom:16px; padding:16px 20px;">
        <div class="stat-label">Açıklama</div>
        <p style="margin:6px 0 0;">{{ $rol->aciklama }}</p>
    </div>
@endif

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Yetkiler</h2>
        </div>
    </div>
    @if ($rol->tum_yetkiler)
        <div class="empty-state" style="padding:24px;">
            <div class="empty-state-title">Sınırsız yetki</div>
            <p class="empty-state-text">Bu rol tüm işlemlere erişebilir.</p>
        </div>
    @elseif ($yetkilerByModul->isEmpty())
        <div class="empty-state" style="padding:24px;">
            <div class="empty-state-title">Yetki atanmamış</div>
        </div>
    @else
        <div class="yetki-matrix">
            @foreach ($yetkilerByModul as $modul => $yetkiler)
                <div class="yetki-modul">
                    <div class="yetki-modul-header">
                        <strong>{{ $modulAdlari[$modul] ?? $modul }}</strong>
                    </div>
                    <div class="yetki-modul-body">
                        @foreach ($yetkiler as $yetki)
                            <div class="yetki-item">{{ $yetki->ad }}</div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if ($rol->kullanicilar->isNotEmpty())
    <div class="card table-card" style="margin-top:16px;">
        <div class="card-section-header" style="padding:16px 20px 0;">
            <h2 class="card-section-title">Bu Role Atanan Kullanıcılar</h2>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rol->kullanicilar as $kullanici)
                        <tr>
                            <td>{{ $kullanici->tam_adi }}</td>
                            <td>{{ $kullanici->email }}</td>
                            <td>
                                @yetki('kullanici.goruntule')
                                    <a href="{{ route('kullanicilar.show', $kullanici) }}">Detay</a>
                                @endyetki
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
