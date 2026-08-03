@php
    $allColumns = [
        'ad' => 'Ad Soyad',
        'tc_kimlik_no' => 'TC Kimlik No',
        'dogum_tarihi' => 'Doğum Tarihi',
        'telefon' => 'Telefon',
        'email' => 'E-posta',
        'aktif_kurs_sayisi' => 'Aktif Kurs Sayısı',
        'durum' => 'Durum',
        'olusturma' => 'Kayıt Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultVisible = ['ad', 'tc_kimlik_no', 'telefon', 'aktif_kurs_sayisi', 'durum', 'islemler'];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = ['ad', 'tc_kimlik_no', 'dogum_tarihi', 'telefon', 'aktif_kurs_sayisi', 'olusturma'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="egitmenler-table"
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
            @forelse ($egitmenler as $egitmen)
                <tr>
                    <td data-column="ad">
                        <a href="{{ route('egitmenler.show', $egitmen) }}">{{ $egitmen->tam_adi }}</a>
                    </td>
                    <td data-column="tc_kimlik_no">{{ $egitmen->tc_kimlik_no ?: '—' }}</td>
                    <td data-column="dogum_tarihi">{{ $egitmen->dogum_tarihi?->format('d.m.Y') ?? '—' }}</td>
                    <td data-column="telefon">{{ $egitmen->telefon ?: '—' }}</td>
                    <td data-column="email">{{ $egitmen->email }}</td>
                    <td data-column="aktif_kurs_sayisi">{{ number_format($egitmen->aktif_kurs_sayisi) }}</td>
                    <td data-column="durum">
                        @if ($egitmen->aktif)
                            <span class="status status-aktif">Aktif</span>
                        @else
                            <span class="status status-hazirlik">Pasif</span>
                        @endif
                    </td>
                    <td data-column="olusturma">{{ $egitmen->created_at?->format('d.m.Y') }}</td>
                    <td data-column="islemler">
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                <a href="{{ route('egitmenler.show', $egitmen) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Detaylar
                                </a>
                                <a href="{{ route('egitmenler.edit', $egitmen) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    Düzenle
                                </a>
                                <a href="{{ route('takvim.index', ['ogretmen' => $egitmen->id]) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                                    Takvim
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
                            </div>
                            <div class="empty-state-title">Eğitmen kaydı bulunamadı</div>
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
            @if ($egitmenler->total())
                {{ $egitmenler->firstItem() }}–{{ $egitmenler->lastItem() }} / {{ $egitmenler->total() }} kayıt
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
        @if ($egitmenler->hasPages())
            <div data-pagination>
                {{ $egitmenler->links() }}
            </div>
        @endif
    </div>
</div>
