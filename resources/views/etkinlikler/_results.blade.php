@php
    $defaultVisible = $defaultVisible ?? ['no', 'ad', 'merkez', 'tip', 'baslangic', 'bitis', 'basvuru', 'kayit', 'durum', 'basvuru_durumu', 'islemler'];
    $allColumns = $allColumns ?? [
        'no' => 'No',
        'ad' => 'Ad',
        'merkez' => 'Merkez',
        'kurum' => 'Kurum',
        'tip' => 'Tip',
        'baslangic' => 'Başlangıç',
        'bitis' => 'Bitiş',
        'basvuru' => 'Başvuru',
        'kayit' => 'Kayıt',
        'iptal' => 'İptal',
        'kontenjan' => 'Kontenjan',
        'yedek' => 'Yedek',
        'durum' => 'Durum',
        'basvuru_durumu' => 'Başvuru Durumu',
        'tarih' => 'Tarih',
        'islemler' => 'İşlemler',
    ];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = ['no', 'ad', 'merkez', 'tip', 'baslangic', 'bitis', 'basvuru', 'kayit', 'iptal', 'kontenjan', 'yedek', 'durum', 'basvuru_durumu', 'tarih'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="etkinlikler-table"
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
            @forelse ($etkinlikler as $etkinlik)
                <tr>
                    <td data-column="no" class="col-no {{ in_array('no', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        <a href="{{ route('etkinlikler.show', $etkinlik) }}" class="kurs-no">#{{ $etkinlik->etkinlik_no }}</a>
                    </td>
                    <td data-column="ad" class="col-ad {{ in_array('ad', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->ad }}</td>
                    <td data-column="merkez" class="col-merkez {{ in_array('merkez', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->merkez?->ad }}</td>
                    <td data-column="kurum" class="col-kurum {{ in_array('kurum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        <div class="rol-badge-list">
                            @forelse ($etkinlik->kurumlar as $kurum)
                                <span class="status status-hazirlik">{{ $kurum->ad }}</span>
                            @empty
                                —
                            @endforelse
                        </div>
                    </td>
                    <td data-column="tip" class="col-tip {{ in_array('tip', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->etkinlikTipi?->ad }}</td>
                    <td data-column="baslangic" class="col-baslangic {{ in_array('baslangic', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->baslangic_tarihi?->format('d.m.Y') }}</td>
                    <td data-column="bitis" class="col-bitis {{ in_array('bitis', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->bitis_tarihi?->format('d.m.Y') }}</td>
                    <td data-column="basvuru" class="col-basvuru {{ in_array('basvuru', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->basvuru_sayisi }}</td>
                    <td data-column="kayit" class="col-kayit {{ in_array('kayit', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->kayit_sayisi }}</td>
                    <td data-column="iptal" class="col-iptal {{ in_array('iptal', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->iptal_sayisi }}</td>
                    <td data-column="kontenjan" class="col-kontenjan {{ in_array('kontenjan', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->kontenjan }}</td>
                    <td data-column="yedek" class="col-yedek {{ in_array('yedek', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->yedek_kontenjan }}</td>
                    <td data-column="durum" class="col-durum {{ in_array('durum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($etkinlik->durum)
                            <span class="status status-{{ $etkinlik->durum->value }}">{{ $etkinlik->durum->label() }}</span>
                        @endif
                    </td>
                    <td data-column="basvuru_durumu" class="col-basvuru_durumu {{ in_array('basvuru_durumu', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        <span class="status {{ $etkinlik->basvuruDurumuStatusClass() }}">{{ $etkinlik->basvuruDurumuLabel() }}</span>
                    </td>
                    <td data-column="tarih" class="col-tarih {{ in_array('tarih', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $etkinlik->created_at?->format('d.m.Y') }}</td>
                    <td data-column="islemler" class="col-islemler">
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                <a href="{{ route('etkinlikler.show', $etkinlik) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Etkinlik Detayları
                                </a>
                                @yetki('etkinlik.guncelle')
                                <a href="{{ route('etkinlikler.edit', $etkinlik) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    Düzenle
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
                            <div class="empty-state-title">Etkinlik bulunamadı</div>
                            <p class="empty-state-text">Filtreleri temizleyin veya yeni bir etkinlik oluşturun.</p>
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
            @if ($etkinlikler instanceof \Illuminate\Pagination\AbstractPaginator)
                @if ($etkinlikler->total())
                    {{ $etkinlikler->firstItem() }}–{{ $etkinlikler->lastItem() }} / {{ $etkinlikler->total() }} kayıt
                @else
                    0 kayıt
                @endif
            @else
                {{ $etkinlikler->count() }} kayıt
            @endif
        </span>
        <select name="per_page" class="form-control" style="width:auto; height:32px;" data-per-page-select>
            @foreach ([20, 50, 100] as $size)
                <option value="{{ $size }}" @selected(($filters['per_page'] ?? 20) == $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-footer-right">
        @if ($etkinlikler instanceof \Illuminate\Pagination\AbstractPaginator && $etkinlikler->hasPages())
            <div data-pagination>
                {{ $etkinlikler->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
