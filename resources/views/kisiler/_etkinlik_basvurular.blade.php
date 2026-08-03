@php
    $etkinlikBasvuruColumns = [
        'etkinlik_no' => 'Etkinlik No',
        'etkinlik' => 'Etkinlik',
        'tip' => 'Tip',
        'merkez' => 'Merkez',
        'durum' => 'Başvuru Durumu',
        'tarih' => 'Başvuru Tarihi',
    ];
    $etkinlikBasvuruOrder = array_keys($etkinlikBasvuruColumns);
    $etkinlikBasvurulari = $etkinlikBasvurulari ?? collect();
    $etkinlikBasvuruDurumSecenekleri = $etkinlikBasvurulari->pluck('durum.ad')->filter()->unique()->sort()->values();
@endphp

<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Etkinlik Başvuruları</div>
            <p class="table-subtitle">Bu kişinin katılımcı olarak yer aldığı etkinlik başvuruları.</p>
        </div>
        <x-detail-table-tools :columns="$etkinlikBasvuruColumns" :visible="$etkinlikBasvuruOrder" excel-name="kisi-etkinlik-basvurular" />
    </div>

    <div class="detail-filter-bar" data-detail-filters>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Etkinlik No</span>
            <input type="text" class="form-control" data-filter-column="etkinlik_no" data-filter-type="text" placeholder="Ara...">
        </div>
        <div class="detail-filter-field">
            <span class="detail-filter-label">Etkinlik</span>
            <input type="text" class="form-control" data-filter-column="etkinlik" data-filter-type="text" placeholder="Ara...">
        </div>
        <div class="detail-filter-field">
            <span class="detail-filter-label">Merkez</span>
            <input type="text" class="form-control" data-filter-column="merkez" data-filter-type="text" placeholder="Ara...">
        </div>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Başvuru Durumu</span>
            <select class="form-control" data-filter-column="durum" data-filter-type="select">
                <option value="">Tümü</option>
                @foreach ($etkinlikBasvuruDurumSecenekleri as $ad)
                    <option value="{{ $ad }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Başlangıç</span>
            <input type="date" class="form-control" data-filter-column="tarih" data-filter-type="date-from">
        </div>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Bitiş</span>
            <input type="date" class="form-control" data-filter-column="tarih" data-filter-type="date-to">
        </div>
        <button type="button" class="detail-filter-clear" data-filter-clear>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            Temizle
        </button>
    </div>

    <div class="table-wrapper">
        <table
            class="data-table"
            data-detail-table="kisi_etkinlik_basvurular_cols"
            data-default-order='@json($etkinlikBasvuruOrder)'
            data-default-visible='@json($etkinlikBasvuruOrder)'
        >
            <thead>
                <tr>
                    @foreach ($etkinlikBasvuruColumns as $key => $label)
                        <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($etkinlikBasvurulari as $basvuru)
                    <tr>
                        <td data-column="etkinlik_no" data-export-value="{{ $basvuru->etkinlik?->etkinlik_no }}">
                            @if ($basvuru->etkinlik)
                                <a href="{{ route('etkinlikler.show', $basvuru->etkinlik) }}" class="kurs-no">{{ $basvuru->etkinlik->etkinlik_no }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-column="etkinlik">
                            @if ($basvuru->etkinlik)
                                <a href="{{ route('etkinlikler.basvurular.show', [$basvuru->etkinlik, $basvuru]) }}">{{ $basvuru->etkinlik->ad ?? '—' }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-column="tip">{{ $basvuru->etkinlik?->etkinlikTipi?->ad ?? '—' }}</td>
                        <td data-column="merkez">{{ $basvuru->etkinlik?->merkez?->ad ?? '—' }}</td>
                        <td data-column="durum" data-export-value="{{ $basvuru->durum?->ad }}">
                            @if ($basvuru->durum)
                                <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-column="tarih" data-sort-value="{{ $basvuru->created_at?->toDateString() }}">{{ $basvuru->created_at?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-title">Başvuru bulunamadı</div>
                                <p class="empty-state-text">Bu kişi henüz bir etkinliğe başvurmamış.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
