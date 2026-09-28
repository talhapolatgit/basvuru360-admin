@extends('layouts.admin')

@section('title', 'Güvenilir IP Adresleri')

@section('content')
@php
    $yonetebilir = auth()->user()?->hasYetki('guvenilir_ip.guncelle');
    $hataCantasi = $errors->hasBag('duzenle') ? 'duzenle' : ($errors->hasBag('olustur') ? 'olustur' : null);
    $hatalar = $hataCantasi ? $errors->getBag($hataCantasi) : null;
    $duzenlenenId = $hataCantasi === 'duzenle' ? (int) old('_kayit_id') : null;
@endphp

<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Sistem</p>
        <h1 class="page-title">Güvenilir IP Adresleri</h1>
        <p class="page-subtitle">Burada tanımlı IP adresleri ve aralıkları admin paneli ile başvuru portalındaki IP bazlı giriş denemesi sınırlarına hiçbir zaman takılmaz.</p>
    </div>
    @if ($yonetebilir)
        <div class="flex items-center gap-2">
            <x-cta-button type="button" icon="plus" data-guvenilir-ip-create>Yeni IP Adresi</x-cta-button>
        </div>
    @endif
</div>

<div class="guvenilir-ip-mevcut {{ $mevcutIpGuvenilir ? 'is-guvenilir' : '' }}">
    <div class="guvenilir-ip-mevcut-text">
        <span class="guvenilir-ip-mevcut-label">Şu anki IP adresiniz</span>
        <span class="guvenilir-ip-mevcut-ip mono-cell">{{ $mevcutIp ?? '—' }}</span>
        @if ($mevcutIpGuvenilir)
            <span class="status status-aktif">Güvenilir</span>
        @endif
    </div>
    @if ($yonetebilir && $mevcutIp && ! $mevcutIpGuvenilir)
        <button type="button" class="btn btn-secondary btn-sm" data-guvenilir-ip-create data-ip="{{ $mevcutIp }}">Bu IP'yi ekle</button>
    @endif
</div>

<div class="card table-card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>IP Adresi</th>
                    <th>Açıklama</th>
                    <th>Ekleyen</th>
                    <th>Son Güncelleme</th>
                    @if ($yonetebilir)
                        <th>İşlemler</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($adresler as $adres)
                    <tr>
                        <td class="mono-cell">
                            <strong>{{ $adres->ip_adresi }}</strong>
                            @if (str_contains($adres->ip_adresi, '/'))
                                <span class="status status-hazirlik" style="margin-left:6px; font-size:11px;">Aralık</span>
                            @endif
                        </td>
                        <td>{{ $adres->aciklama ?: '—' }}</td>
                        <td>{{ $adres->olusturan?->tam_adi ?? '—' }}</td>
                        <td>
                            {{ $adres->updated_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}
                            @if ($adres->guncelleyen)
                                <div class="yoklama-gun-adi">{{ $adres->guncelleyen->tam_adi }}</div>
                            @endif
                        </td>
                        @if ($yonetebilir)
                            <td>
                                <div class="row-actions" data-row-actions>
                                    <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                                    <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                        <button
                                            type="button"
                                            class="action-dropdown-item"
                                            role="menuitem"
                                            data-guvenilir-ip-edit
                                            data-id="{{ $adres->id }}"
                                            data-update-url="{{ route('guvenilir-ip-adresleri.update', $adres) }}"
                                            data-ip="{{ $adres->ip_adresi }}"
                                            data-aciklama="{{ $adres->aciklama }}"
                                        >Düzenle</button>
                                        <button
                                            type="button"
                                            class="action-dropdown-item action-dropdown-item-danger"
                                            role="menuitem"
                                            data-guvenilir-ip-delete
                                            data-delete-url="{{ route('guvenilir-ip-adresleri.destroy', $adres) }}"
                                            data-ip="{{ $adres->ip_adresi }}"
                                        >Sil</button>
                                    </div>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="{{ $yonetebilir ? 5 : 4 }}">
                            <div class="empty-state">
                                <div class="empty-state-title">Tanımlı güvenilir IP adresi yok</div>
                                <p class="empty-state-text">Kurumunuzun sabit IP adresini ekleyerek ortak ağdaki kullanıcıların geçici engellenmesini önleyebilirsiniz.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($yonetebilir)
    <div class="confirm-modal" id="guvenilir-ip-form-modal" hidden data-open-on-load="{{ $hataCantasi ? '1' : '0' }}">
        <div class="confirm-modal-backdrop" data-guvenilir-ip-close></div>
        <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="guvenilir-ip-form-title">
            <div class="confirm-modal-header">
                <h3 id="guvenilir-ip-form-title" class="confirm-modal-title" data-guvenilir-ip-title>
                    {{ $hataCantasi === 'duzenle' ? 'IP Adresini Düzenle' : 'Yeni Güvenilir IP Adresi' }}
                </h3>
                <button type="button" class="confirm-modal-x" data-guvenilir-ip-close aria-label="Kapat">&times;</button>
            </div>
            <form
                method="POST"
                action="{{ $duzenlenenId ? route('guvenilir-ip-adresleri.update', $duzenlenenId) : route('guvenilir-ip-adresleri.store') }}"
                data-guvenilir-ip-form
                data-create-url="{{ route('guvenilir-ip-adresleri.store') }}"
                novalidate
            >
                @csrf
                <input type="hidden" name="_method" value="PUT" data-guvenilir-ip-method @disabled(! $duzenlenenId)>
                <input type="hidden" name="_kayit_id" value="{{ $duzenlenenId }}" data-guvenilir-ip-kayit-id>
                <div class="confirm-modal-body">
                    <div class="form-group">
                        <label for="guvenilir-ip-adresi">IP adresi <span class="req">*</span></label>
                        <input
                            type="text"
                            id="guvenilir-ip-adresi"
                            name="ip_adresi"
                            class="form-control mono-cell @if ($hatalar?->has('ip_adresi')) is-invalid @endif"
                            value="{{ $hataCantasi ? old('ip_adresi') : '' }}"
                            maxlength="64"
                            placeholder="Örn. 85.105.10.20 veya 85.105.10.0/24"
                            autocomplete="off"
                            data-guvenilir-ip-input
                        >
                        <p class="form-error" data-guvenilir-ip-error @if (! $hatalar?->has('ip_adresi')) hidden @endif>{{ $hatalar?->first('ip_adresi') }}</p>
                        <p class="form-hint">Tek bir IPv4/IPv6 adresi veya CIDR aralığı girebilirsiniz.</p>
                    </div>
                    <div class="form-group">
                        <label for="guvenilir-ip-aciklama">Açıklama</label>
                        <input
                            type="text"
                            id="guvenilir-ip-aciklama"
                            name="aciklama"
                            class="form-control @if ($hatalar?->has('aciklama')) is-invalid @endif"
                            value="{{ $hataCantasi ? old('aciklama') : '' }}"
                            maxlength="255"
                            placeholder="Örn. Merkez bina sabit IP"
                            data-guvenilir-ip-aciklama
                        >
                        @if ($hatalar?->has('aciklama'))
                            <p class="form-error">{{ $hatalar->first('aciklama') }}</p>
                        @endif
                    </div>
                </div>
                <div class="confirm-modal-footer">
                    <button type="button" class="btn btn-secondary btn-wide" data-guvenilir-ip-close>Vazgeç</button>
                    <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <div class="confirm-modal" id="guvenilir-ip-delete-modal" hidden>
        <div class="confirm-modal-backdrop" data-guvenilir-ip-close></div>
        <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="guvenilir-ip-delete-title">
            <div class="confirm-modal-header">
                <h3 id="guvenilir-ip-delete-title" class="confirm-modal-title">IP Adresini Sil</h3>
                <button type="button" class="confirm-modal-x" data-guvenilir-ip-close aria-label="Kapat">&times;</button>
            </div>
            <form method="POST" action="" data-guvenilir-ip-delete-form>
                @csrf
                @method('DELETE')
                <div class="confirm-modal-body">
                    <p><strong class="mono-cell" data-guvenilir-ip-delete-ip></strong> güvenilir IP adreslerinden kaldırılacak. Bu adres tekrar IP bazlı giriş sınırlarına tabi olacak.</p>
                </div>
                <div class="confirm-modal-footer">
                    <button type="button" class="btn btn-secondary btn-wide" data-guvenilir-ip-close>Vazgeç</button>
                    <button type="submit" class="btn btn-danger btn-wide">Sil</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
