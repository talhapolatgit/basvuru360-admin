@php
    $allColumns = [
        'ad' => 'Ad Soyad',
        'tc_kimlik_no' => 'TC Kimlik No',
        'dogum_tarihi' => 'Doğum Tarihi',
        'telefon' => 'Telefon',
        'email' => 'E-posta',
        'cinsiyet' => 'Cinsiyet',
        'basvuru_sayisi' => 'Başvuru Sayısı',
        'durum' => 'Durum',
        'olusturma' => 'Kayıt Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'tc_kimlik_no', 'telefon', 'basvuru_sayisi', 'durum', 'islemler'];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = ['ad', 'tc_kimlik_no', 'dogum_tarihi', 'telefon', 'cinsiyet', 'basvuru_sayisi', 'olusturma'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="kisiler-table"
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
            @forelse ($kisiler as $kisi)
                <tr>
                    <td data-column="ad">
                        <a href="{{ route('kisiler.show', $kisi) }}">{{ $kisi->tam_adi }}</a>
                    </td>
                    <td data-column="tc_kimlik_no">{{ $kisi->tc_kimlik_no ?: '—' }}</td>
                    <td data-column="dogum_tarihi">{{ $kisi->dogum_tarihi?->format('d.m.Y') ?? '—' }}</td>
                    <td data-column="telefon">{{ $kisi->telefon ?: '—' }}</td>
                    <td data-column="email">{{ $kisi->email ?: '—' }}</td>
                    <td data-column="cinsiyet">{{ $kisi->cinsiyet?->label() ?? '—' }}</td>
                    <td data-column="basvuru_sayisi">{{ number_format($kisi->basvuru_sayisi) }}</td>
                    <td data-column="durum">
                        @if ($kisi->aktif)
                            <span class="status status-aktif">Aktif</span>
                        @else
                            <span class="status status-hazirlik">Pasif</span>
                        @endif
                    </td>
                    <td data-column="olusturma">{{ $kisi->created_at?->format('d.m.Y') }}</td>
                    <td data-column="islemler">
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                <a href="{{ route('kisiler.show', $kisi) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Detaylar
                                </a>
                                <a href="{{ route('kisiler.edit', $kisi) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    Düzenle
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <div class="empty-state-title">Kişi kaydı bulunamadı</div>
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
            @if ($kisiler->total())
                {{ $kisiler->firstItem() }}–{{ $kisiler->lastItem() }} / {{ $kisiler->total() }} kayıt
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
        @if ($kisiler->hasPages())
            <div data-pagination>
                {{ $kisiler->links() }}
            </div>
        @endif
    </div>
</div>
