@php
    $basvuruColumns = [
        'kurs_no' => 'Kurs No',
        'alan' => 'Alan',
        'brans' => 'Branş',
        'merkez' => 'Merkez',
        'durum' => 'Başvuru Durumu',
        'basari' => 'Başarı Durumu',
        'tarih' => 'Başvuru Tarihi',
    ];
    $basvuruOrder = array_keys($basvuruColumns);
    $basvuruDurumSecenekleri = $basvurular->pluck('durum.ad')->filter()->unique()->sort()->values();
    $basariDurumSecenekleri = $basvurular->pluck('basariDurum.ad')->filter()->unique()->sort()->values();
@endphp

<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kurs Başvuruları</div>
            <p class="table-subtitle">Bu kişinin katılımcı olarak yer aldığı kurs başvuruları.</p>
        </div>
        <x-detail-table-tools :columns="$basvuruColumns" :visible="$basvuruOrder" excel-name="kisi-kurs-basvurular" />
    </div>

    <div class="detail-filter-bar" data-detail-filters>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Kurs No</span>
            <input type="text" class="form-control" data-filter-column="kurs_no" data-filter-type="text" placeholder="Ara...">
        </div>
        <div class="detail-filter-field">
            <span class="detail-filter-label">Merkez</span>
            <input type="text" class="form-control" data-filter-column="merkez" data-filter-type="text" placeholder="Ara...">
        </div>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Başvuru Durumu</span>
            <select class="form-control" data-filter-column="durum" data-filter-type="select">
                <option value="">Tümü</option>
                @foreach ($basvuruDurumSecenekleri as $ad)
                    <option value="{{ $ad }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div class="detail-filter-field detail-filter-field-sm">
            <span class="detail-filter-label">Başarı Durumu</span>
            <select class="form-control" data-filter-column="basari" data-filter-type="select">
                <option value="">Tümü</option>
                @foreach ($basariDurumSecenekleri as $ad)
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
            data-detail-table="kisi_basvurular_cols"
            data-default-order='@json($basvuruOrder)'
            data-default-visible='@json($basvuruOrder)'
        >
            <thead>
                <tr>
                    @foreach ($basvuruColumns as $key => $label)
                        <th data-column="{{ $key }}" data-sortable="1">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($basvurular as $basvuru)
                    <tr>
                        <td data-column="kurs_no" data-export-value="{{ $basvuru->kurs?->kurs_no }}">
                            @if ($basvuru->kurs)
                                <a href="{{ route('kurslar.show', $basvuru->kurs) }}" class="kurs-no">{{ $basvuru->kurs->kurs_no }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-column="alan">{{ $basvuru->kurs?->alan?->ad ?? '—' }}</td>
                        <td data-column="brans">{{ $basvuru->kurs?->brans?->ad ?? '—' }}</td>
                        <td data-column="merkez">{{ $basvuru->kurs?->merkez?->ad ?? '—' }}</td>
                        <td data-column="durum" data-export-value="{{ $basvuru->durum?->ad }}">
                            @if ($basvuru->durum)
                                <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-column="basari" data-export-value="{{ $basvuru->basariDurum?->ad }}">
                            @if ($basvuru->basariDurum)
                                <span class="status {{ $basvuru->basariDurum->statusClass() }}">{{ $basvuru->basariDurum->ad }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-column="tarih" data-sort-value="{{ $basvuru->created_at?->toDateString() }}">{{ $basvuru->created_at?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-title">Başvuru bulunamadı</div>
                                <p class="empty-state-text">Bu kişi henüz bir kursa başvurmamış.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
