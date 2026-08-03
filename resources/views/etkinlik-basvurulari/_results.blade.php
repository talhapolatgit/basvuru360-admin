@php
    $defaultVisible = $defaultVisible ?? ['etkinlik_no', 'etkinlik', 'merkez', 'katilimci', 'kimlik', 'telefon', 'durum', 'basvuru_tarihi', 'islemler'];
    $allColumns = $allColumns ?? [
        'etkinlik_no' => 'Etkinlik No',
        'etkinlik' => 'Etkinlik',
        'merkez' => 'Merkez',
        'basvuran' => 'Başvuran',
        'katilimci' => 'Katılımcı',
        'veli' => 'Veli',
        'kimlik' => 'Kimlik No',
        'dogum' => 'Doğum T.',
        'telefon' => 'Telefon',
        'ikamet' => 'İkamet',
        'durum' => 'Durum',
        'katilim' => 'Katılım',
        'onay' => 'Onay Tarihi',
        'iptal' => 'İptal Tarihi',
        'iptal_gerekce' => 'İptal Gerekçesi',
        'kaydeden' => 'Kaydeden',
        'basvuru_tarihi' => 'Başvuru Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = $sortableColumns ?? [
        'etkinlik_no', 'etkinlik', 'merkez', 'basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet',
        'durum', 'onay', 'iptal', 'iptal_gerekce', 'kaydeden', 'basvuru_tarihi',
    ];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="etkinlik-basvurulari-table"
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
            @forelse ($basvurular as $basvuru)
                <tr>
                    <td data-column="etkinlik_no" class="col-etkinlik_no {{ in_array('etkinlik_no', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($basvuru->etkinlik)
                            <a href="{{ route('etkinlikler.show', $basvuru->etkinlik) }}" class="kurs-no">#{{ $basvuru->etkinlik->etkinlik_no }}</a>
                        @endif
                    </td>
                    <td data-column="etkinlik" class="col-etkinlik {{ in_array('etkinlik', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->etkinlik?->ad }}</td>
                    <td data-column="merkez" class="col-merkez {{ in_array('merkez', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->etkinlik?->merkez?->ad }}</td>
                    <td data-column="basvuran" class="col-basvuran {{ in_array('basvuran', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->basvuran?->tam_adi ?? ($basvuru->kisi?->tam_adi ?? '—') }}</td>
                    <td data-column="katilimci" class="col-katilimci {{ in_array('katilimci', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kisi?->tam_adi ?? '—' }}</td>
                    <td data-column="veli" class="col-veli {{ in_array('veli', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->veli?->tam_adi ?? '—' }}</td>
                    <td data-column="kimlik" class="col-kimlik {{ in_array('kimlik', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kisi?->tc_kimlik_no ?? '—' }}</td>
                    <td data-column="dogum" class="col-dogum {{ in_array('dogum', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '—' }}</td>
                    <td data-column="telefon" class="col-telefon {{ in_array('telefon', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kisi?->telefon ?? '—' }}</td>
                    <td data-column="ikamet" class="col-ikamet {{ in_array('ikamet', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kisi?->ilce ?? '—' }}</td>
                    <td data-column="durum" class="col-durum {{ in_array('durum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($basvuru->durum)
                            <span class="status {{ $basvuru->durum->status_sinifi ?? 'status-hazirlik' }}">{{ $basvuru->durum->ad }}</span>
                        @endif
                    </td>
                    <td data-column="yedek_sira" class="col-yedek_sira {{ in_array('yedek_sira', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->durum?->kod === 'yedek' && $basvuru->yedek_sira ? $basvuru->yedek_sira : '—' }}
                    </td>
                    <td data-column="katilim" class="col-katilim {{ in_array('katilim', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->katilim_durumu?->label() ?? '—' }}</td>
                    <td data-column="onay" class="col-onay {{ in_array('onay', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->onay_tarihi?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td data-column="iptal" class="col-iptal {{ in_array('iptal', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->iptal_tarihi?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td data-column="iptal_gerekce" class="col-iptal_gerekce {{ in_array('iptal_gerekce', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->iptalGerekce?->ad ?? '—' }}</td>
                    <td data-column="kaydeden" class="col-kaydeden {{ in_array('kaydeden', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->olusturan?->tam_adi ?? '—' }}</td>
                    <td data-column="basvuru_tarihi" class="col-basvuru_tarihi {{ in_array('basvuru_tarihi', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->created_at?->format('d.m.Y H:i') }}</td>
                    <td data-column="islemler" class="col-islemler">
                        @php
                            $katilimciAdi = $basvuru->kisi?->tam_adi ?? ($basvuru->basvuran?->tam_adi ?? 'Başvuru');
                        @endphp
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                @if ($basvuru->etkinlik)
                                    <a href="{{ route('etkinlikler.basvurular.show', [$basvuru->etkinlik, $basvuru]) }}" class="action-dropdown-item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Detaylar
                                    </a>
                                    @yetki('etkinlik_basvuru.guncelle')
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-etkinlik-durum-open
                                        data-url="{{ route('etkinlikler.basvurular.durum', [$basvuru->etkinlik, $basvuru]) }}"
                                        data-ad="{{ $katilimciAdi }}"
                                        data-durum="{{ $basvuru->durum?->kod }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        Durum Güncelle
                                    </button>
                                    @endyetki
                                    @yetki('etkinlik_basvuru.yedek_sira_guncelle')
                                    @if ($basvuru->durum?->kod === 'yedek')
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-yedek-sira-open
                                        data-list-url="{{ route('etkinlikler.yedek-sirasi', $basvuru->etkinlik) }}"
                                        data-save-url="{{ route('etkinlikler.yedek-sirasi.update', $basvuru->etkinlik) }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                                        Yedek Sıra Güncelle
                                    </button>
                                    @endif
                                    @endyetki
                                    @yetki('etkinlik.sms')
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-basvuru-sms-ac
                                        data-send-url="{{ route('etkinlikler.sms.send', $basvuru->etkinlik) }}"
                                        data-basvuru-id="{{ $basvuru->id }}"
                                        data-katilimci="{{ $katilimciAdi }}"
                                        data-telefon-var="{{ ($basvuru->kisi?->telefon ?: $basvuru->basvuran?->telefon ?: $basvuru->veli?->telefon) ? '1' : '0' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        SMS Gönder
                                    </button>
                                    @endyetki
                                    @yetki('etkinlik.eposta')
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-basvuru-eposta-ac
                                        data-send-url="{{ route('etkinlikler.eposta.send', $basvuru->etkinlik) }}"
                                        data-basvuru-id="{{ $basvuru->id }}"
                                        data-katilimci="{{ $katilimciAdi }}"
                                        data-email="{{ $basvuru->kisi?->email ?: ($basvuru->basvuran?->email ?: ($basvuru->veli?->email ?: '')) }}"
                                        data-email-var="{{ ($basvuru->kisi?->email ?: $basvuru->basvuran?->email ?: $basvuru->veli?->email) ? '1' : '0' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        E-Posta Gönder
                                    </button>
                                    @endyetki
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}">
                        <div class="empty-state">
                            <div class="empty-state-title">Başvuru bulunamadı</div>
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
            @if ($basvurular instanceof \Illuminate\Pagination\AbstractPaginator)
                @if ($basvurular->total())
                    {{ $basvurular->firstItem() }}–{{ $basvurular->lastItem() }} / {{ $basvurular->total() }} kayıt
                @else
                    0 kayıt
                @endif
            @else
                {{ $basvurular->count() }} kayıt
            @endif
        </span>
        <select name="per_page" class="form-control" style="width:auto; height:32px;" data-per-page-select>
            @foreach ([20, 50, 100] as $size)
                <option value="{{ $size }}" @selected(($filters['per_page'] ?? 20) == $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-footer-right">
        @if ($basvurular instanceof \Illuminate\Pagination\AbstractPaginator && $basvurular->hasPages())
            <div data-pagination>
                {{ $basvurular->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
