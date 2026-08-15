@php
    $allColumns = [
        'ad' => 'Ad',
        'okul' => 'Okul',
        'donem' => 'Dönem',
        'yas' => 'Yaş Aralığı',
        'kontenjan' => 'Kontenjan',
        'yedek' => 'Yedek K.',
        'cinsiyet' => 'Cinsiyet',
        'durum' => 'Durum',
        'olusturma' => 'Oluşturma Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'okul', 'donem', 'yas', 'kontenjan', 'cinsiyet', 'durum', 'olusturma', 'islemler'];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = ['ad', 'okul', 'donem', 'kontenjan', 'yedek', 'olusturma'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="kres-gruplar-table"
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
            @forelse ($gruplar as $grup)
                <tr>
                    <td data-column="ad">{{ $grup->ad }}</td>
                    <td data-column="okul">{{ $grup->okul?->ad ?? '—' }}</td>
                    <td data-column="donem">{{ $grup->donem?->ad ?? '—' }}</td>
                    <td data-column="yas">{{ $grup->yasAraligiLabel() }}</td>
                    <td data-column="kontenjan">{{ number_format($grup->kontenjan) }}</td>
                    <td data-column="yedek">{{ number_format($grup->yedek_kontenjan) }}</td>
                    <td data-column="cinsiyet">{{ $grup->cinsiyetSartiLabel() }}</td>
                    <td data-column="durum">
                        @if ($grup->aktif)
                            <span class="status status-aktif">Aktif</span>
                        @else
                            <span class="status status-hazirlik">Pasif</span>
                        @endif
                    </td>
                    <td data-column="olusturma">{{ $grup->created_at?->format('d.m.Y') }}</td>
                    <td data-column="islemler">
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-entity-edit
                                    data-update-url="{{ route('kres.gruplar.update', $grup) }}"
                                    data-ad="{{ $grup->ad }}"
                                    data-okul-id="{{ $grup->okul_id }}"
                                    data-donem-id="{{ $grup->donem_id }}"
                                    data-min-yas="{{ $grup->min_yas }}"
                                    data-max-yas="{{ $grup->max_yas }}"
                                    data-kontenjan="{{ $grup->kontenjan }}"
                                    data-yedek-kontenjan="{{ $grup->yedek_kontenjan }}"
                                    data-cinsiyet-sarti="{{ $grup->cinsiyet_sarti?->value }}"
                                    data-aktif="{{ $grup->aktif ? '1' : '0' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    Düzenle
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
                            </div>
                            <div class="empty-state-title">Grup kaydı bulunamadı</div>
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
            @if ($gruplar->total())
                {{ $gruplar->firstItem() }}–{{ $gruplar->lastItem() }} / {{ $gruplar->total() }} kayıt
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
        @if ($gruplar->hasPages())
            <div data-pagination>
                {{ $gruplar->links() }}
            </div>
        @endif
    </div>
</div>
