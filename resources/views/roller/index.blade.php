@extends('layouts.admin')

@section('title', 'Roller')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Yetkilendirme</p>
        <h1 class="page-title">Roller</h1>
        <p class="page-subtitle">Rolleri yönetin ve yetki atayın. Bir kullanıcıya birden fazla rol verilebilir.</p>
    </div>
    <div class="flex items-center gap-2">
        @yetki('rol.yonet')
            <x-cta-button :href="route('roller.create')" icon="plus">Yeni Rol</x-cta-button>
        @endyetki
    </div>
</div>

<div class="card table-card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Kod</th>
                    <th>Yetki</th>
                    <th>Kullanıcı</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roller as $rol)
                    <tr>
                        <td>
                            <strong>{{ $rol->ad }}</strong>
                            @if ($rol->sistem)
                                <span class="status status-hazirlik" style="margin-left:6px; font-size:11px;">Sistem</span>
                            @endif
                            @if ($rol->tum_yetkiler)
                                <span class="status status-aktif" style="margin-left:4px; font-size:11px;">Tam yetki</span>
                            @endif
                            @if ($rol->aciklama)
                                <div class="yoklama-gun-adi">{{ $rol->aciklama }}</div>
                            @endif
                        </td>
                        <td class="mono-cell">{{ $rol->kod }}</td>
                        <td>
                            @if ($rol->tum_yetkiler)
                                Tümü
                            @else
                                {{ number_format($rol->yetkiler_count) }}
                            @endif
                        </td>
                        <td>{{ number_format($rol->kullanicilar_count) }}</td>
                        <td>
                            @if ($rol->aktif)
                                <span class="status status-aktif">Aktif</span>
                            @else
                                <span class="status status-hazirlik">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions" data-row-actions>
                                <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                                <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                    <a href="{{ route('roller.show', $rol) }}" class="action-dropdown-item" role="menuitem">Detay</a>
                                    @yetki('rol.yonet')
                                        <a href="{{ route('roller.edit', $rol) }}" class="action-dropdown-item" role="menuitem">Düzenle</a>
                                        @if ($rol->silinebilirMi())
                                            <form method="POST" action="{{ route('roller.destroy', $rol) }}" onsubmit="return confirm('Bu rolü silmek istediğinize emin misiniz?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-dropdown-item action-dropdown-item-danger" role="menuitem">Sil</button>
                                            </form>
                                        @endif
                                    @endyetki
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-title">Rol bulunamadı</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
