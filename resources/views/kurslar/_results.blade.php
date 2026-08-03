@php
    $defaultVisible = $defaultVisible ?? ['no', 'brans', 'merkez', 'gunler', 'basvuru', 'kayit', 'durum', 'basvuru_durumu', 'islemler'];
    $allColumns = $allColumns ?? [
        'no' => 'No',
        'alan' => 'Alan',
        'brans' => 'Branş',
        'merkez' => 'Merkez',
        'kurum' => 'Kurum',
        'baslama' => 'Başlama',
        'bitis' => 'Bitiş',
        'gunler' => 'Günler',
        'basvuru' => 'Başvuru',
        'kayit' => 'Kayıt',
        'iptal' => 'İptal',
        'kontenjan' => 'Kontenjan',
        'yedek' => 'Yedek',
        'egitmen' => 'Eğitmen',
        'belge' => 'Belge Türü',
        'durum' => 'Durum',
        'basvuru_durumu' => 'Başvuru Durumu',
        'tarih' => 'Tarih',
        'islemler' => 'İşlemler',
    ];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = ['no', 'alan', 'brans', 'merkez', 'baslama', 'bitis', 'basvuru', 'kayit', 'iptal', 'kontenjan', 'yedek', 'egitmen', 'belge', 'durum', 'basvuru_durumu', 'tarih'];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="kurslar-table"
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
            @forelse ($kurslar as $kurs)
                <tr>
                    <td data-column="no" class="col-no {{ in_array('no', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        <a href="{{ route('kurslar.show', $kurs) }}" class="kurs-no">#{{ $kurs->kurs_no }}</a>
                    </td>
                    <td data-column="alan" class="col-alan {{ in_array('alan', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->alan?->ad }}</td>
                    <td data-column="brans" class="col-brans {{ in_array('brans', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->brans?->ad }}</td>
                    <td data-column="merkez" class="col-merkez {{ in_array('merkez', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->merkez?->ad }}</td>
                    <td data-column="kurum" class="col-kurum {{ in_array('kurum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        <div class="rol-badge-list">
                            @forelse ($kurs->kurumlar as $kurum)
                                <span class="status status-hazirlik">{{ $kurum->ad }}</span>
                            @empty
                                —
                            @endforelse
                        </div>
                    </td>
                    <td data-column="baslama" class="col-baslama {{ in_array('baslama', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->kurs_baslama_tarihi?->format('d.m.Y') }}</td>
                    <td data-column="bitis" class="col-bitis {{ in_array('bitis', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->kurs_bitis_tarihi?->format('d.m.Y') }}</td>
                    <td data-column="gunler" class="col-gunler gunler-cell {{ in_array('gunler', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->gunlerOzeti() }}</td>
                    <td data-column="basvuru" class="col-basvuru {{ in_array('basvuru', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->basvuru_sayisi }}</td>
                    <td data-column="kayit" class="col-kayit {{ in_array('kayit', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->kayit_sayisi }}</td>
                    <td data-column="iptal" class="col-iptal {{ in_array('iptal', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->iptal_sayisi }}</td>
                    <td data-column="kontenjan" class="col-kontenjan {{ in_array('kontenjan', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->kontenjan }}</td>
                    <td data-column="yedek" class="col-yedek {{ in_array('yedek', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->yedek_kontenjan }}</td>
                    <td data-column="egitmen" class="col-egitmen {{ in_array('egitmen', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->ogretmenAdlari() }}</td>
                    <td data-column="belge" class="col-belge {{ in_array('belge', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->kursTipi?->ad }}</td>
                    <td data-column="durum" class="col-durum {{ in_array('durum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($kurs->durum)
                            <span class="status status-{{ $kurs->durum->value }}">{{ $kurs->durum->label() }}</span>
                        @endif
                    </td>
                    <td data-column="basvuru_durumu" class="col-basvuru_durumu {{ in_array('basvuru_durumu', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        <span class="status {{ $kurs->basvuruDurumuStatusClass() }}">{{ $kurs->basvuruDurumuLabel() }}</span>
                    </td>
                    <td data-column="tarih" class="col-tarih {{ in_array('tarih', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $kurs->created_at?->format('d.m.Y') }}</td>
                    <td data-column="islemler" class="col-islemler">
                        <div class="row-actions" data-row-actions>
                            <button
                                type="button"
                                class="action-menu-btn"
                                data-action-toggle
                                aria-expanded="false"
                                aria-haspopup="menu"
                                title="İşlemler"
                            >•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                <a href="{{ route('kurslar.show', $kurs) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Kurs Detayları
                                </a>
                                <a href="{{ route('kurslar.edit', $kurs) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h6"/></svg>
                            </div>
                            <div class="empty-state-title">Kurs kaydı bulunamadı</div>
                            <p class="empty-state-text">Filtreleri temizleyin veya yeni bir kurs oluşturun.</p>
                            <a href="{{ route('kurslar.create') }}" class="btn btn-primary btn-sm">Yeni Kurs</a>
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
            @if ($kurslar->total())
                {{ $kurslar->firstItem() }}–{{ $kurslar->lastItem() }} / {{ $kurslar->total() }} kayıt
            @else
                0 kayıt
            @endif
        </span>
        <form id="per-page-form" method="GET" action="{{ route('kurslar.index') }}" style="display:flex; align-items:center; gap:8px;">
            @foreach ($filters as $key => $value)
                @if ($key !== 'per_page' && filled($value))
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endif
            @endforeach
            <select name="per_page" class="form-control" style="width:auto; height:32px;" data-per-page-select>
                @foreach ([20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected(($filters['per_page'] ?? 20) == $size)>{{ $size }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-footer-right">
        @if ($kurslar->hasPages())
            <div data-pagination>
                {{ $kurslar->links() }}
            </div>
        @endif

        <button type="button" id="save-column-prefs" class="save-prefs-btn" title="Kolon düzenlemelerini kaydet">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>
        </button>
    </div>
</div>
