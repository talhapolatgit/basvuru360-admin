@php
    $allColumns = $basvuruColumns ?? [];
    $defaultVisible = $basvuruDefaultVisible ?? array_keys($allColumns);
    $sortableColumns = $basvuruSortable ?? [];
    $defaultOrder = array_keys($allColumns);
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $canDurum = auth()->user()?->hasYetki('kres.basvuru_durum_guncelle') ?? false;
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="kres-basvuru-table"
        data-default-order='@json($defaultOrder)'
        data-default-visible='@json($defaultVisible)'
        data-sort="{{ $sort }}"
        data-direction="{{ $direction }}"
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
            <tr class="column-search-row">
                @foreach ($allColumns as $key => $label)
                    <th
                        data-column="{{ $key }}"
                        class="col-{{ $key }} {{ in_array($key, $defaultVisible, true) || $key === 'islemler' ? '' : 'col-hidden' }}"
                    >
                        @if ($key !== 'islemler')
                            <input
                                type="text"
                                class="form-control column-search"
                                data-column-search="{{ $key }}"
                                placeholder="Ara..."
                                autocomplete="off"
                            >
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($basvurular as $basvuru)
                <tr>
                    <td data-column="ogrenci" class="col-ogrenci {{ in_array('ogrenci', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->tam_adi ?? '—' }}
                    </td>
                    <td data-column="kimlik" class="col-kimlik mono-cell {{ in_array('kimlik', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->tc_kimlik_no ?? '—' }}
                    </td>
                    <td data-column="dogum" class="col-dogum {{ in_array('dogum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td data-column="telefon" class="col-telefon {{ in_array('telefon', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->telefon ?? '—' }}
                    </td>
                    <td data-column="basvuran" class="col-basvuran {{ in_array('basvuran', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->basvuran?->tam_adi ?? '—' }}
                    </td>
                    <td data-column="durum" class="col-durum {{ in_array('durum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($basvuru->durum)
                            <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td data-column="yedek_sira" class="col-yedek_sira {{ in_array('yedek_sira', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->durum?->kod === 'yedek' && $basvuru->yedek_sira ? $basvuru->yedek_sira : '—' }}
                    </td>
                    <td data-column="notlar" class="col-notlar {{ in_array('notlar', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->notlar ? \Illuminate\Support\Str::limit($basvuru->notlar, 60) : '—' }}
                    </td>
                    <td data-column="kaydeden" class="col-kaydeden {{ in_array('kaydeden', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->olusturan?->tam_adi ?: '—' }}
                    </td>
                    <td data-column="basvuru_tarihi" class="col-basvuru_tarihi {{ in_array('basvuru_tarihi', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->created_at?->format('d.m.Y H:i') }}
                    </td>
                    <td data-column="islemler" class="col-islemler">
                        @if ($canDurum)
                            <button
                                type="button"
                                class="btn-columns btn-columns-sm"
                                data-kres-durum-open
                                data-url="{{ route('kres.basvurular.durum', [$okul, $grup, $basvuru]) }}"
                                data-durum-id="{{ $basvuru->durum_id }}"
                                data-yedek-sira="{{ $basvuru->yedek_sira }}"
                                data-kisi="{{ $basvuru->kisi?->tam_adi }}"
                                title="Durum güncelle"
                            >
                                <span class="btn-columns-text">Durum</span>
                            </button>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}" class="text-center text-muted">Bu filtrede başvuru bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($basvurular instanceof \Illuminate\Pagination\AbstractPaginator && $basvurular->hasPages())
    <div class="table-pagination" data-pagination>
        {{ $basvurular->links() }}
    </div>
@endif
