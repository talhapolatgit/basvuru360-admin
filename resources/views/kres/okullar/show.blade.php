@extends('layouts.admin')

@section('title', $okul->ad)

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kreş Yönetimi</p>
        <h1 class="page-title">{{ $okul->ad }}</h1>
        <p class="page-subtitle">
            @if ($aktifDonem)
                {{ $aktifDonem->ad }} dönemindeki yaş grupları
            @else
                Grupları görmek için dönem seçin
            @endif
        </p>
    </div>
    <div class="kres-page-actions">
        <div class="kres-page-actions__btns">
            <x-back-button href="{{ route('kres.index') }}">Okullara Dön</x-back-button>
        </div>
        @include('kres.partials.donem-switcher')
    </div>
</div>

@include('kres.partials.chrome')

@if ($okul->telefon || $okul->adres)
    <div class="kres-info-strip">
        @if ($okul->telefon)<span>Tel: {{ $okul->telefon }}</span>@endif
        @if ($okul->adres)<span>{{ $okul->adres }}</span>@endif
    </div>
@endif

<div class="kres-group-grid">
    @forelse ($gruplar as $row)
        @php $grup = $row['model']; @endphp
        <a href="{{ route('kres.gruplar.show', [$okul, $grup]) }}" class="kres-group-card {{ $grup->aktif ? '' : 'is-passive' }}">
            <div class="kres-group-card__top">
                <h2>{{ $grup->ad }}</h2>
                <span class="kres-group-card__ages">{{ $grup->yasAraligiLabel() }}</span>
            </div>
            <div class="kres-school-card__stats">
                <div>
                    <span class="kres-stat-label">Kontenjan</span>
                    <strong>{{ $grup->kontenjan }}</strong>
                </div>
                <div>
                    <span class="kres-stat-label">Kesin kayıt</span>
                    <strong>{{ $row['kesin_kayit'] }}</strong>
                </div>
                <div>
                    <span class="kres-stat-label">Başvuru</span>
                    <strong>{{ $row['basvuru_sayisi'] }}</strong>
                </div>
            </div>
            <div class="kres-fill">
                <div class="kres-fill__bar" style="--fill: {{ $row['doluluk'] }}%"></div>
                <span class="kres-fill__label">Doluluk {{ $row['doluluk'] }}%</span>
            </div>
        </a>
    @empty
        <div class="kres-empty kres-empty--inline">
            <h2>{{ $aktifDonem ? 'Bu dönemde grup yok' : 'Dönem seçilmedi' }}</h2>
            <p>
                @if ($aktifDonem)
                    Yaş gruplarını Gruplar tanım ekranından ekleyin.
                @else
                    Üstten bir dönem seçin veya Dönemler ekranından dönem oluşturun.
                @endif
            </p>
            @if ($aktifDonem)
                @yetki('kres.grup_yonet')
                <a href="{{ route('kres.gruplar.index', ['okul_id' => $okul->id, 'donem_id' => $aktifDonem->id]) }}" class="btn-cta btn-cta-sm">
                    <span class="btn-cta-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    </span>
                    <span class="btn-cta-text">Gruplar</span>
                </a>
                @endyetki
            @endif
        </div>
    @endforelse
</div>
@endsection
