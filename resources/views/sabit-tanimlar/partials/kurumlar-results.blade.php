@php
    $allColumns = [
        'sira' => 'Sıra',
        'ad' => 'Ad',
        'durum' => 'Durum',
        'olusturma' => 'Oluşturma',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = array_keys($allColumns);
    $sortableColumns = ['sira', 'ad', 'olusturma'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="kurumlar-table"
        data-default-order='@json(array_keys($allColumns))'
        data-default-visible='@json($defaultVisible)'
        data-sort="{{ $sort ?? '' }}"
        data-direction="{{ $direction ?? 'asc' }}"
    >
        <thead>
            <tr>
                @foreach ($allColumns as $key => $label)
                    <th
                        data-column="{{ $key }}"
                        @if (in_array($key, $sortableColumns, true)) data-sortable="1" @endif
                    >{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td data-column="sira">{{ $item->sira }}</td>
                    <td data-column="ad">{{ $item->ad }}</td>
                    <td data-column="durum">
                        @if ($item->aktif)
                            <span class="status status-aktif">Aktif</span>
                        @else
                            <span class="status status-hazirlik">Pasif</span>
                        @endif
                    </td>
                    <td data-column="olusturma">{{ $item->created_at?->format('d.m.Y') }}</td>
                    <td data-column="islemler">
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                @yetki('sabit.guncelle')
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-kurumlar-edit
                                    data-update-url="{{ route('sabit-tanimlar.kurumlar.update', $item) }}"
                                    data-ad="{{ $item->ad }}"
                                    data-sira="{{ $item->sira }}"
                                    data-aktif="{{ $item->aktif ? '1' : '0' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    Düzenle
                                </button>
                                @endyetki
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}">
                        <div class="empty-state">
                            <div class="empty-state-title">Kurum bulunamadı</div>
                            <p class="empty-state-text">Filtreleri temizleyin veya yeni bir kayıt ekleyin.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="table-footer">
    <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#7e8299;">
        <span>{{ $items->count() }} kayıt</span>
    </div>
</div>
