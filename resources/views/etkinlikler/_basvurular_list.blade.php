@php
    $allColumns = $basvuruColumns ?? [];
    $defaultVisible = $basvuruDefaultVisible ?? array_keys($allColumns);
    $sortableColumns = $basvuruSortable ?? [];
    $defaultOrder = array_keys($allColumns);
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="etkinlik-basvuru-table"
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
                    <td data-column="basvuran" class="col-basvuran {{ in_array('basvuran', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->basvuran?->tam_adi ?? ($basvuru->kisi?->tam_adi ?? '—') }}
                    </td>
                    <td data-column="katilimci" class="col-katilimci {{ in_array('katilimci', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->tam_adi ?? '—' }}
                        @if ($basvuru->veliBasvurusuMu())
                            <div class="yoklama-gun-adi">Veli başvurusu</div>
                        @endif
                    </td>
                    <td data-column="veli" class="col-veli {{ in_array('veli', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->veli?->tam_adi ?? '—' }}
                    </td>
                    <td data-column="kimlik" class="col-kimlik mono-cell {{ in_array('kimlik', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->tc_kimlik_no ?? '—' }}
                    </td>
                    <td data-column="dogum" class="col-dogum {{ in_array('dogum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td data-column="telefon" class="col-telefon {{ in_array('telefon', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->telefon ?? ($basvuru->basvuran?->telefon ?? '—') }}
                    </td>
                    <td data-column="ikamet" class="col-ikamet {{ in_array('ikamet', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->ilce ?? ($basvuru->kisi?->il ?? '—') }}
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
                    <td data-column="katilim" class="col-katilim {{ in_array('katilim', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($basvuru->katilim_durumu)
                            <span class="status {{ $basvuru->katilim_durumu->statusClass() }}">{{ $basvuru->katilim_durumu->label() }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td data-column="onay" class="col-onay {{ in_array('onay', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->onay_tarihi?->format('d.m.Y H:i') ?? '—' }}
                    </td>
                    <td data-column="iptal" class="col-iptal {{ in_array('iptal', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->iptal_tarihi?->format('d.m.Y H:i') ?? '—' }}
                    </td>
                    <td data-column="iptal_gerekce" class="col-iptal_gerekce {{ in_array('iptal_gerekce', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->iptalGerekce?->ad ?? '—' }}
                    </td>
                    <td data-column="kaydeden" class="col-kaydeden {{ in_array('kaydeden', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->olusturan?->tam_adi ?: '—' }}
                    </td>
                    <td data-column="basvuru_tarihi" class="col-basvuru_tarihi {{ in_array('basvuru_tarihi', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->created_at?->format('d.m.Y H:i') }}
                    </td>
                    <td data-column="islemler" class="col-islemler">
                        @php
                            $katilimciAdi = $basvuru->kisi?->tam_adi ?? ($basvuru->basvuran?->tam_adi ?? 'Başvuru');
                        @endphp
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
                                <a href="{{ route('etkinlikler.basvurular.show', [$etkinlik, $basvuru]) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Detaylar
                                </a>
                                @yetki('etkinlik_basvuru.guncelle')
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-etkinlik-durum-open
                                    data-url="{{ route('etkinlikler.basvurular.durum', [$etkinlik, $basvuru]) }}"
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
                                    data-list-url="{{ route('etkinlikler.yedek-sirasi', $etkinlik) }}"
                                    data-save-url="{{ route('etkinlikler.yedek-sirasi.update', $etkinlik) }}"
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
                                    data-send-url="{{ route('etkinlikler.sms.send', $etkinlik) }}"
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
                                    data-send-url="{{ route('etkinlikler.eposta.send', $etkinlik) }}"
                                    data-basvuru-id="{{ $basvuru->id }}"
                                    data-katilimci="{{ $katilimciAdi }}"
                                    data-email="{{ $basvuru->kisi?->email ?: ($basvuru->basvuran?->email ?: ($basvuru->veli?->email ?: '')) }}"
                                    data-email-var="{{ ($basvuru->kisi?->email ?: $basvuru->basvuran?->email ?: $basvuru->veli?->email) ? '1' : '0' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    E-Posta Gönder
                                </button>
                                @endyetki
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ count($allColumns) }}">
                        <div class="empty-state">
                            <div class="empty-state-title">Başvuru bulunamadı</div>
                            <p class="empty-state-text">Seçili filtreye uygun başvuru kaydı yok.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="table-footer lesson-table-footer" data-basvuru-pagination>
    <div style="font-size:13px; color:#7e8299;">
        @if ($basvurular instanceof \Illuminate\Pagination\AbstractPaginator && $basvurular->total() > 0)
            {{ $basvurular->firstItem() }}–{{ $basvurular->lastItem() }} / {{ $basvurular->total() }}
        @else
            0 kayıt
        @endif
    </div>
    <div class="table-footer-right">
        @if ($basvurular instanceof \Illuminate\Pagination\AbstractPaginator && $basvurular->hasPages())
            <div data-pagination>
                {{ $basvurular->withQueryString()->links() }}
            </div>
        @endif
        <button type="button" id="basvuru-save-column-prefs" class="save-prefs-btn" title="Kolon düzenlemelerini kaydet">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>
        </button>
    </div>
</div>
