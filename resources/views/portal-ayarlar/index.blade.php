@extends('layouts.admin')

@section('title', 'Portal Ayarları')

@section('content')
@php
    $guncelleyebilir = auth()->user()?->hasYetki('portal_ayar.guncelle');
@endphp

<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Sistem</p>
        <h1 class="page-title">Portal Ayarları</h1>
        <p class="page-subtitle">Vatandaş portalındaki yönetilebilir sayfaları, menü sırasını ve içerik kurallarını yönetin.</p>
    </div>
    @if ($guncelleyebilir)
    <div class="flex items-center gap-2">
        <x-cta-button :href="route('portal-ayarlar.create')" icon="plus">Yeni Sayfa</x-cta-button>
    </div>
    @endif
</div>

<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Portal Sayfaları</div>
            <p class="table-subtitle">Ana Sayfa sabittir. Kurslar, Etkinlikler, Başvurularım, Profil ve özel sayfalar buradan yönetilir.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4rem">Sıra</th>
                    <th>Başlık</th>
                    <th>URL</th>
                    <th>İçerik</th>
                    <th>Menü</th>
                    <th>Tür</th>
                    <th class="text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sayfalar as $index => $sayfa)
                    @php
                        if ($sayfa->isAuthSayfa()) {
                            $icerik = match ($sayfa->kod) {
                                'basvurularim' => 'Başvurular',
                                'profil' => 'Profil',
                                default => '—',
                            };
                        } else {
                            $hasKurs = $sayfa->hasKurs();
                            $hasEtkinlik = $sayfa->hasEtkinlik();
                            $icerik = collect([
                                $hasKurs ? 'Kurs' : null,
                                $hasEtkinlik ? 'Etkinlik' : null,
                            ])->filter()->implode(' + ') ?: '—';
                        }
                    @endphp
                    <tr>
                        <td>
                            @if ($guncelleyebilir)
                            <div class="flex items-center gap-1">
                                <form method="POST" action="{{ route('portal-ayarlar.move', $sayfa) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="yon" value="yukari">
                                    <button type="submit" class="btn btn-sm" @disabled($index === 0) title="Yukarı">↑</button>
                                </form>
                                <form method="POST" action="{{ route('portal-ayarlar.move', $sayfa) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="yon" value="asagi">
                                    <button type="submit" class="btn btn-sm" @disabled($index === $sayfalar->count() - 1) title="Aşağı">↓</button>
                                </form>
                            </div>
                            @else
                                {{ $sayfa->sira }}
                            @endif
                        </td>
                        <td>
                            <strong>{{ $sayfa->baslik }}</strong>
                        </td>
                        <td>
                            <code>/{{ $sayfa->sistem ? $sayfa->slug : 'sayfa/'.$sayfa->slug }}</code>
                        </td>
                        <td>{{ $icerik }}</td>
                        <td>
                            @if ($guncelleyebilir)
                            <form method="POST" action="{{ route('portal-ayarlar.menude-toggle', $sayfa) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="menude_goster" value="{{ $sayfa->menude_goster ? 0 : 1 }}">
                                <button type="submit" class="log-badge {{ $sayfa->menude_goster ? 'log-badge-success' : 'log-badge-muted' }}">
                                    {{ $sayfa->menude_goster ? 'Görünür' : 'Gizli' }}
                                </button>
                            </form>
                            @else
                                <span class="log-badge {{ $sayfa->menude_goster ? 'log-badge-success' : 'log-badge-muted' }}">
                                    {{ $sayfa->menude_goster ? 'Görünür' : 'Gizli' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="log-badge {{ $sayfa->sistem ? 'log-badge-info' : 'log-badge-warning' }}">
                                {{ $sayfa->sistem ? 'Sistem' : 'Özel' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('portal-ayarlar.edit', $sayfa) }}" class="btn btn-sm">Düzenle</a>
                                @if ($guncelleyebilir && ! $sayfa->sistem)
                                <form method="POST" action="{{ route('portal-ayarlar.destroy', $sayfa) }}" onsubmit="return confirm('Bu sayfayı silmek istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Henüz sayfa tanımlanmamış.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
