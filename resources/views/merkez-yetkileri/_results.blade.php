@php
    $allColumns = [
        'ad' => 'Ad Soyad',
        'email' => 'E-posta',
        'rol' => 'Roller',
        'merkezler' => 'Merkezler',
        'merkez_sayisi' => 'Merkez Adedi',
        'durum' => 'Durum',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'rol', 'merkezler', 'merkez_sayisi', 'durum', 'islemler'];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = ['ad', 'email', 'merkez_sayisi'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="merkez-yetkileri-table"
        data-default-order='@json($defaultOrder)'
        data-default-visible='@json($defaultVisible)'
        data-sort="{{ $sort ?? '' }}"
        data-direction="{{ $direction ?? 'desc' }}"
    >
        <thead>
            <tr>
                @foreach ($allColumns as $key => $label)
                    <th
                        data-column="{{ $key }}"
                        @if (in_array($key, $sortableColumns, true)) data-sortable="1" @endif
                        class="col-{{ $key }} {{ in_array($key, $defaultVisible, true) || $key === 'islemler' ? '' : 'col-hidden' }}"
                    >{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($kullanicilar as $kullanici)
                <tr>
                    <td data-column="ad">
                        <a href="{{ route('kullanicilar.show', $kullanici) }}">{{ $kullanici->tam_adi }}</a>
                    </td>
                    <td data-column="email">{{ $kullanici->email }}</td>
                    <td data-column="rol">
                        <div class="rol-badge-list">
                            @forelse ($kullanici->roller as $rol)
                                <span class="status {{ $rol->tum_yetkiler ? 'status-aktif' : 'status-hazirlik' }}">{{ $rol->ad }}</span>
                            @empty
                                —
                            @endforelse
                        </div>
                    </td>
                    <td data-column="merkezler">
                        @if ($kullanici->atananMerkezler->isEmpty())
                            <span style="color:#a1a5b7;">Yetkilendirme yok</span>
                        @else
                            <div class="rol-badge-list">
                                @foreach ($kullanici->atananMerkezler->take(4) as $merkez)
                                    <span class="status status-hazirlik">{{ $merkez->ad }}</span>
                                @endforeach
                                @if ($kullanici->atananMerkezler->count() > 4)
                                    <span class="status status-hazirlik">+{{ $kullanici->atananMerkezler->count() - 4 }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td data-column="merkez_sayisi">{{ number_format($kullanici->merkez_sayisi) }}</td>
                    <td data-column="durum">
                        @if ($kullanici->aktif)
                            <span class="status status-aktif">Aktif</span>
                        @else
                            <span class="status status-hazirlik">Pasif</span>
                        @endif
                    </td>
                    <td data-column="islemler">
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                <a href="{{ route('kullanicilar.show', $kullanici) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Kullanıcı Detayı
                                </a>
                                @yetki('kullanici.guncelle')
                                    <a
                                        href="{{ route('kullanicilar.merkez-yetkileri.edit', ['kullanici' => $kullanici, 'return' => 'liste']) }}"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                        Merkezleri Düzenle
                                    </a>
                                @endyetki
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div class="empty-state-title">Kayıt bulunamadı</div>
                            <p class="empty-state-text">Filtreleri temizleyerek yeniden deneyin.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="table-footer">
    <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#7e8299;">
        <span>
            @if ($kullanicilar->total())
                {{ $kullanicilar->firstItem() }}–{{ $kullanicilar->lastItem() }} / {{ $kullanicilar->total() }} kayıt
            @else
                0 kayıt
            @endif
        </span>
        <select name="per_page" class="form-control" style="width:auto; height:32px;" data-per-page-select>
            @foreach ([20, 50, 100] as $size)
                <option value="{{ $size }}" @selected(($filters['per_page'] ?? 20) == $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-footer-right">
        @if ($kullanicilar->hasPages())
            <div data-pagination>
                {{ $kullanicilar->links() }}
            </div>
        @endif
    </div>
</div>
