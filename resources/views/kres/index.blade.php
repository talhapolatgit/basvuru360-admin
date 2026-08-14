@extends('layouts.admin')

@section('title', 'Başvurular')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kreş Yönetimi</p>
        <h1 class="page-title">Başvurular</h1>
        <p class="page-subtitle">Okula girin ve yaş gruplarıyla başvuruları takip edin.</p>
    </div>
    <div class="kres-page-actions">
        @include('kres.partials.donem-switcher')
    </div>
</div>

@include('kres.partials.chrome')

@unless ($aktifDonem)
    <div class="kres-empty">
        <h2>Aktif dönem yok</h2>
        <p>Grupları ve başvuruları görmek için önce bir eğitim öğretim dönemi tanımlayın.</p>
        @yetki('kres.donem_yonet')
        <a href="{{ route('kres.donemler.index') }}" class="btn-cta btn-cta-sm">
            <span class="btn-cta-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            </span>
            <span class="btn-cta-text">Dönemler</span>
        </a>
        @endyetki
    </div>
@endunless

<div class="kres-school-grid">
    @forelse ($okullar as $row)
        @php $okul = $row['model']; @endphp
        <a href="{{ route('kres.okullar.show', $okul) }}" class="kres-school-card {{ $okul->aktif ? '' : 'is-passive' }}">
            <div class="kres-school-card__top">
                <h2 class="kres-school-card__title">{{ $okul->ad }}</h2>
                @if ($okul->aktif)
                    <span class="status status-aktif">Aktif</span>
                @else
                    <span class="status status-hazirlik">Pasif</span>
                @endif
            </div>
            @if ($okul->adres)
                <p class="kres-school-card__meta">{{ $okul->adres }}</p>
            @endif
            <div class="kres-school-card__stats">
                <div>
                    <span class="kres-stat-label">Grup</span>
                    <strong>{{ $row['grup_sayisi'] }}</strong>
                </div>
                <div>
                    <span class="kres-stat-label">Kesin kayıt</span>
                    <strong>{{ $row['kesin_kayit'] }}</strong>
                </div>
                <div>
                    <span class="kres-stat-label">Yedek</span>
                    <strong>{{ $row['yedek'] }}</strong>
                </div>
            </div>
            <div class="kres-fill">
                <div class="kres-fill__bar" style="--fill: {{ $row['doluluk'] }}%"></div>
                <span class="kres-fill__label">Kontenjan doluluk {{ $row['doluluk'] }}% @if ($row['kontenjan']) ({{ $row['kesin_kayit'] }}/{{ $row['kontenjan'] }}) @endif</span>
            </div>
        </a>
    @empty
        <div class="kres-empty kres-empty--inline">
            <h2>Henüz okul yok</h2>
            <p>Başvuru takibi için önce Okullar tanım ekranından okul ekleyin.</p>
            @yetki('kres.okul_yonet')
            <a href="{{ route('kres.okullar.index') }}" class="btn-cta btn-cta-sm">
                <span class="btn-cta-mark" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                </span>
                <span class="btn-cta-text">Okullar</span>
            </a>
            @endyetki
        </div>
    @endforelse
</div>
@endsection
