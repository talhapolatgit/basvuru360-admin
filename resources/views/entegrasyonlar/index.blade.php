@extends('layouts.admin')

@section('title', 'Entegrasyonlar')

@section('content')
@php
    $guncelleyebilir = auth()->user()?->hasYetki('entegrasyon.guncelle');
@endphp

<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Sistem</p>
        <h1 class="page-title">Entegrasyonlar</h1>
        <p class="page-subtitle">SMS, e-posta, kimlik ve adres sorgulama sağlayıcılarını tek ekrandan yönetin. Her türü aktif/pasif yapabilir; aktifken yalnızca bir sağlayıcı seçebilirsiniz.</p>
    </div>
</div>

<div class="card entegrasyonlar-card" data-entegrasyonlar>
    <form
        method="POST"
        action="{{ route('entegrasyonlar.update') }}"
        class="entegrasyonlar-form"
        data-entegrasyonlar-form
    >
        @csrf
        @method('PUT')

        <div class="entegrasyon-tur-grid">
            @foreach ($turler as $tur)
                @php
                    $turAktif = (bool) old('tur_aktif.'.$tur['tur'], $tur['tur_aktif']);
                    $seciliSaglayici = old('aktif.'.$tur['tur'], $tur['aktif']);
                    $aktifSaglayici = collect($tur['saglayicilar'])->firstWhere('kod', $tur['aktif']);
                @endphp
                <section
                    class="entegrasyon-tur-card {{ $turAktif ? '' : 'is-pasif' }}"
                    data-entegrasyon-tur="{{ $tur['tur'] }}"
                    data-tur-aktif="{{ $turAktif ? '1' : '0' }}"
                >
                    <div class="entegrasyon-tur-head">
                        <div>
                            <h2 class="entegrasyon-tur-title">{{ $tur['ad'] }}</h2>
                            <p class="entegrasyon-tur-desc">{{ $tur['aciklama'] }}</p>
                        </div>
                        <div class="entegrasyon-tur-meta">
                            <span class="status {{ $turAktif ? 'status-aktif' : 'status-hazirlik' }}" data-entegrasyon-durum>
                                @if ($turAktif)
                                    Aktif{{ $aktifSaglayici ? ': '.$aktifSaglayici['ad'] : '' }}
                                @else
                                    Pasif
                                @endif
                            </span>
                            <label class="switch-label entegrasyon-tur-switch" for="tur-aktif-{{ $tur['tur'] }}">
                                <input type="hidden" name="tur_aktif[{{ $tur['tur'] }}]" value="0">
                                <input
                                    type="checkbox"
                                    id="tur-aktif-{{ $tur['tur'] }}"
                                    name="tur_aktif[{{ $tur['tur'] }}]"
                                    value="1"
                                    data-entegrasyon-tur-aktif
                                    @checked($turAktif)
                                    @disabled(! $guncelleyebilir)
                                >
                                <span>{{ $turAktif ? 'Aktif' : 'Pasif' }}</span>
                            </label>
                        </div>
                    </div>

                    <div
                        class="entegrasyon-saglayici-list"
                        role="radiogroup"
                        aria-label="{{ $tur['ad'] }} sağlayıcıları"
                        data-entegrasyon-saglayici-list
                    >
                        @forelse ($tur['saglayicilar'] as $saglayici)
                            @php
                                $secili = $seciliSaglayici === $saglayici['kod'];
                                $ayarli = ! empty($saglayici['alanlar']);
                            @endphp
                            <div
                                class="entegrasyon-saglayici {{ ($turAktif && $secili) ? 'is-active' : '' }}"
                                data-entegrasyon-saglayici-wrap
                            >
                                <label class="entegrasyon-saglayici-main">
                                    <input
                                        type="radio"
                                        name="aktif[{{ $tur['tur'] }}]"
                                        value="{{ $saglayici['kod'] }}"
                                        data-entegrasyon-saglayici
                                        @checked($secili)
                                        @disabled(! $turAktif || ! $guncelleyebilir)
                                        @required($turAktif)
                                    >
                                    <span class="entegrasyon-saglayici-body">
                                        <span class="entegrasyon-saglayici-ad">{{ $saglayici['ad'] }}</span>
                                        <span class="entegrasyon-saglayici-kod">{{ $saglayici['kod'] }}</span>
                                        @if ($saglayici['aciklama'] !== '')
                                            <span class="entegrasyon-saglayici-desc">{{ $saglayici['aciklama'] }}</span>
                                        @endif
                                    </span>
                                </label>

                                @if ($ayarli)
                                    <button
                                        type="button"
                                        class="entegrasyon-ayar-btn"
                                        data-entegrasyon-ayar-open
                                        data-tur="{{ $tur['tur'] }}"
                                        data-saglayici="{{ $saglayici['kod'] }}"
                                        title="{{ $saglayici['ad'] }} ayarları"
                                        aria-label="{{ $saglayici['ad'] }} ayarları"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="entegrasyon-empty">Bu tür için henüz sağlayıcı tanımlı değil.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <div class="entegrasyonlar-actions">
            @yetki('entegrasyon.guncelle')
                <button type="submit" class="btn-cta">
                    <span class="btn-cta-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </span>
                    <span class="btn-cta-text">Ayarları Kaydet</span>
                </button>
            @else
                <button type="button" class="btn-cta" disabled title="Bu işlem için yetkiniz yok">
                    <span class="btn-cta-text">Ayarları Kaydet</span>
                </button>
            @endyetki
        </div>
    </form>

    @foreach ($turler as $tur)
        @foreach ($tur['saglayicilar'] as $saglayici)
            @continue(empty($saglayici['alanlar']))
            <div
                class="confirm-modal"
                id="entegrasyon-ayar-modal-{{ $tur['tur'] }}-{{ $saglayici['kod'] }}"
                data-entegrasyon-ayar-modal
                data-tur="{{ $tur['tur'] }}"
                data-saglayici="{{ $saglayici['kod'] }}"
                hidden
            >
                <div class="confirm-modal-backdrop" data-entegrasyon-ayar-close></div>
                <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="entegrasyon-ayar-title-{{ $tur['tur'] }}-{{ $saglayici['kod'] }}">
                    <div class="confirm-modal-header">
                        <h3 id="entegrasyon-ayar-title-{{ $tur['tur'] }}-{{ $saglayici['kod'] }}" class="confirm-modal-title">
                            {{ $saglayici['ad'] }} Ayarları
                        </h3>
                        <button type="button" class="confirm-modal-x" data-entegrasyon-ayar-close aria-label="Kapat">&times;</button>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('entegrasyonlar.ayarlar.update', ['tur' => $tur['tur'], 'saglayici' => $saglayici['kod']]) }}"
                        class="entegrasyon-ayar-form"
                        data-entegrasyon-ayar-form
                    >
                        @csrf
                        @method('PUT')
                        <div class="confirm-modal-body">
                            @if ($saglayici['aciklama'] !== '')
                                <p class="entegrasyon-ayar-modal-desc">{{ $saglayici['aciklama'] }}</p>
                            @endif
                            <div class="entegrasyon-ayarlar-grid">
                                @foreach ($saglayici['alanlar'] as $alan)
                                    @php
                                        $alanAdi = 'ayarlar['.$alan['kod'].']';
                                        $alanId = 'modal-ayar-'.$tur['tur'].'-'.$saglayici['kod'].'-'.$alan['kod'];
                                    @endphp
                                    <div class="form-group {{ in_array($alan['kod'], ['host', 'from_address', 'client_id', 'refresh_token'], true) ? 'entegrasyon-ayar-span-2' : '' }}">
                                        <label for="{{ $alanId }}">
                                            {{ $alan['etiket'] }}
                                            @if ($alan['zorunlu'] && ! $alan['gizli'])
                                                <span class="required">*</span>
                                            @elseif ($alan['zorunlu'] && $alan['gizli'] && ! $alan['dolu'])
                                                <span class="required">*</span>
                                            @endif
                                        </label>

                                        @if ($alan['tip'] === 'select')
                                            <select
                                                id="{{ $alanId }}"
                                                name="{{ $alanAdi }}"
                                                class="form-control"
                                                data-alan-kod="{{ $alan['kod'] }}"
                                                @disabled(! $guncelleyebilir)
                                            >
                                                @foreach ($alan['secenekler'] as $optDeger => $optEtiket)
                                                    <option value="{{ $optDeger }}" @selected((string) $alan['deger'] === (string) $optDeger)>
                                                        {{ $optEtiket }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input
                                                id="{{ $alanId }}"
                                                type="{{ $alan['tip'] === 'password' ? 'password' : ($alan['tip'] === 'number' ? 'number' : ($alan['tip'] === 'email' ? 'email' : 'text')) }}"
                                                name="{{ $alanAdi }}"
                                                class="form-control"
                                                value="{{ $alan['gizli'] ? '' : $alan['deger'] }}"
                                                placeholder="{{ $alan['gizli'] && $alan['dolu'] ? 'Kayıtlı değer korunacak (değiştirmek için yeni değer girin)' : ($alan['placeholder'] ?: '') }}"
                                                autocomplete="{{ $alan['gizli'] ? 'new-password' : 'off' }}"
                                                data-alan-kod="{{ $alan['kod'] }}"
                                                data-gizli="{{ $alan['gizli'] ? '1' : '0' }}"
                                                @disabled(! $guncelleyebilir)
                                            >
                                        @endif

                                        @if ($alan['gizli'] && $alan['dolu'])
                                            <p class="field-hint" data-gizli-hint>Kayıtlı bir değer var. Boş bırakırsanız mevcut değer korunur.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="confirm-modal-footer">
                            <button type="button" class="btn btn-secondary btn-wide" data-entegrasyon-ayar-close>Vazgeç</button>
                            @if ($guncelleyebilir)
                                <button type="submit" class="btn btn-primary btn-wide" data-entegrasyon-ayar-save>
                                    Kaydet
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endforeach
</div>
@endsection
