@php
    $defaultVisible = $defaultVisible ?? ['kurs_no', 'brans', 'merkez', 'katilimci', 'kimlik', 'telefon', 'durum', 'basari', 'basvuru_tarihi', 'islemler'];
    $allColumns = $allColumns ?? [
        'kurs_no' => 'Kurs No',
        'brans' => 'Branş',
        'merkez' => 'Merkez',
        'basvuran' => 'Başvuran',
        'katilimci' => 'Katılımcı',
        'veli' => 'Veli',
        'kimlik' => 'Kimlik No',
        'dogum' => 'Doğum T.',
        'telefon' => 'Telefon',
        'ikamet' => 'İkamet',
        'durum' => 'Durum',
        'basari' => 'Başarı',
        'kursa_baslama' => 'Kursa Başlama',
        'onay' => 'Onay Tarihi',
        'iptal' => 'İptal Tarihi',
        'iptal_gerekce' => 'İptal Gerekçesi',
        'kaydeden' => 'Kaydeden',
        'basvuru_tarihi' => 'Başvuru Tarihi',
        'islemler' => 'İşlemler',
    ];
    $defaultOrder = array_keys($allColumns);
    $sortableColumns = [
        'kurs_no', 'brans', 'merkez', 'basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet',
        'durum', 'basari', 'kursa_baslama', 'onay', 'iptal', 'iptal_gerekce', 'kaydeden', 'basvuru_tarihi',
    ];
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="basvurular-table"
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
                    <td data-column="kurs_no" class="col-kurs_no {{ in_array('kurs_no', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($basvuru->kurs)
                            <a href="{{ route('kurslar.show', ['kurs' => $basvuru->kurs, 'tab' => 'basvurular']) }}" class="kurs-no">#{{ $basvuru->kurs->kurs_no }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td data-column="brans" class="col-brans {{ in_array('brans', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kurs?->brans?->ad ?? '—' }}</td>
                    <td data-column="merkez" class="col-merkez {{ in_array('merkez', $defaultVisible, true) ? '' : 'col-hidden' }}">{{ $basvuru->kurs?->merkez?->ad ?? '—' }}</td>
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
                    <td data-column="basari" class="col-basari {{ in_array('basari', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        @if ($basvuru->basariDurum)
                            <span class="status {{ $basvuru->basariDurum->statusClass() }}">{{ $basvuru->basariDurum->ad }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td data-column="kursa_baslama" class="col-kursa_baslama {{ in_array('kursa_baslama', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kursa_baslama_tarihi?->format('d.m.Y') ?? '—' }}
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
                            $canViewEvrak = auth()->user()?->hasYetki('basvuru.evrak_goruntule');
                            $canUploadEvrak = auth()->user()?->hasYetki('basvuru.evrak_yukle');
                            $canDeleteEvrak = auth()->user()?->hasYetki('basvuru.evrak_sil');
                            $evraklarPayload = ($basvuru->evraklar ?? collect())->map(fn ($evrak) => [
                                'id' => $evrak->id,
                                'tip' => $evrak->evrakTipi?->ad ?? 'Evrak',
                                'aciklama' => $evrak->evrakTipi?->aciklama,
                                'mime' => $evrak->mime,
                                'boyut' => (int) ($evrak->boyut ?? 0),
                                'url' => $canViewEvrak && $basvuru->kurs
                                    ? route('kurslar.basvurular.evrak.show', [$basvuru->kurs, $basvuru, $evrak])
                                    : null,
                                'delete_url' => $canDeleteEvrak && $basvuru->kurs
                                    ? route('kurslar.basvurular.evrak.destroy', [$basvuru->kurs, $basvuru, $evrak])
                                    : null,
                                'yukleyen' => $evrak->olusturan?->tam_adi,
                                'yuklenme_tarihi' => $evrak->created_at?->format('d.m.Y H:i'),
                            ])->values();
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
                                <a href="{{ route('kurslar.basvurular.show', [$basvuru->kurs, $basvuru]) }}" class="action-dropdown-item" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Başvuru Detayları
                                </a>
                                @if ($basvuru->kurs)
                                    @yetki('kurs.goruntule')
                                    <a href="{{ route('kurslar.show', $basvuru->kurs) }}" class="action-dropdown-item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Kurs Detayları
                                    </a>
                                    @endyetki
                                @endif
                                @yetki('basvuru.durum_guncelle')
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-basvuru-durum-ac
                                    data-update-url="{{ route('kurslar.basvurular.durum', [$basvuru->kurs, $basvuru]) }}"
                                    data-katilimci="{{ $katilimciAdi }}"
                                    data-durum-kod="{{ $basvuru->durum?->kod ?? '' }}"
                                    data-basari-kod="{{ $basvuru->basariDurum?->kod ?? '' }}"
                                    data-iptal-gerekce-id="{{ $basvuru->iptal_gerekce_id ?? '' }}"
                                    data-baslama-tarihi="{{ $basvuru->kursa_baslama_tarihi?->format('Y-m-d') ?? '' }}"
                                    data-kurs-durum="{{ $basvuru->kurs?->durum?->value }}"
                                    data-kurs-baslama="{{ $basvuru->kurs?->kurs_baslama_tarihi?->format('Y-m-d') }}"
                                    data-kurs-bitis="{{ $basvuru->kurs?->kurs_bitis_tarihi?->format('Y-m-d') }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    Durum Güncelle
                                </button>
                                @endyetki
                                @yetki('basvuru.yedek_sira_guncelle')
                                @if (($basvuru->durum?->kod ?? '') === 'yedek' && $basvuru->kurs)
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-yedek-sira-open
                                    data-list-url="{{ route('kurslar.yedek-sirasi', $basvuru->kurs) }}"
                                    data-save-url="{{ route('kurslar.yedek-sirasi.update', $basvuru->kurs) }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                                    Yedek Sıra Güncelle
                                </button>
                                @endif
                                @endyetki
                                @yetki('basvuru.basari_guncelle')
                                @if (($basvuru->durum?->kod ?? '') === 'kesin_kayit')
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-basvuru-basari
                                        data-update-url="{{ route('kurslar.basvurular.basari', [$basvuru->kurs, $basvuru]) }}"
                                        data-katilimci="{{ $katilimciAdi }}"
                                        data-basari-id="{{ $basvuru->basari_durumu_id ?? '' }}"
                                        data-durum-kod="{{ $basvuru->durum?->kod ?? '' }}"
                                        data-kurs-durum="{{ $basvuru->kurs?->durum?->value }}"
                                        data-kurs-baslama="{{ $basvuru->kurs?->kurs_baslama_tarihi?->format('Y-m-d') }}"
                                        data-kurs-bitis="{{ $basvuru->kurs?->kurs_bitis_tarihi?->format('Y-m-d') }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        Başarı Güncelle
                                    </button>
                                @endif
                                @endyetki
                                @yetki('basvuru.kursa_baslama_guncelle')
                                    @if (($basvuru->durum?->kod ?? '') === 'kesin_kayit')
                                        <button
                                            type="button"
                                            class="action-dropdown-item"
                                            role="menuitem"
                                            data-basvuru-baslama-ac
                                            data-update-url="{{ route('kurslar.basvurular.kursa-baslama', [$basvuru->kurs, $basvuru]) }}"
                                            data-katilimci="{{ $katilimciAdi }}"
                                            data-baslama-tarihi="{{ $basvuru->kursa_baslama_tarihi?->format('Y-m-d') ?? '' }}"
                                            data-kurs-durum="{{ $basvuru->kurs?->durum?->value }}"
                                            data-kurs-baslama="{{ $basvuru->kurs?->kurs_baslama_tarihi?->format('Y-m-d') }}"
                                            data-kurs-bitis="{{ $basvuru->kurs?->kurs_bitis_tarihi?->format('Y-m-d') }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                                            Kursa Başlama Tarihi
                                        </button>
                                    @endif
                                @endyetki
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-basvuru-sms-ac
                                    data-send-url="{{ route('kurslar.sms.send', $basvuru->kurs) }}"
                                    data-basvuru-id="{{ $basvuru->id }}"
                                    data-katilimci="{{ $katilimciAdi }}"
                                    data-telefon-var="{{ ($basvuru->kisi?->telefon ?: $basvuru->basvuran?->telefon ?: $basvuru->veli?->telefon) ? '1' : '0' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    SMS Gönder
                                </button>
                                <button
                                    type="button"
                                    class="action-dropdown-item"
                                    role="menuitem"
                                    data-basvuru-eposta-ac
                                    data-send-url="{{ route('kurslar.eposta.send', $basvuru->kurs) }}"
                                    data-basvuru-id="{{ $basvuru->id }}"
                                    data-katilimci="{{ $katilimciAdi }}"
                                    data-email="{{ $basvuru->kisi?->email ?: ($basvuru->basvuran?->email ?: ($basvuru->veli?->email ?: '')) }}"
                                    data-email-var="{{ ($basvuru->kisi?->email ?: $basvuru->basvuran?->email ?: $basvuru->veli?->email) ? '1' : '0' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    E-posta Gönder
                                </button>
                                @yetki('basvuru.evrak_goruntule')
                                @if ($basvuru->kurs)
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-basvuru-evraklar-ac
                                        data-katilimci="{{ $katilimciAdi }}"
                                        @if ($canUploadEvrak)
                                        data-upload-url="{{ route('kurslar.basvurular.evrak.store', [$basvuru->kurs, $basvuru]) }}"
                                        @endif
                                        data-evraklar='@json($evraklarPayload, JSON_UNESCAPED_UNICODE)'
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.5a5.5 5.5 0 0 1-7.78-7.78l9.9-9.9a3.5 3.5 0 1 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 1 1-2.12-2.12l8.49-8.48"/></svg>
                                        <span class="action-dropdown-item-label">Evraklar</span>
                                        <span class="action-dropdown-count" aria-label="{{ $evraklarPayload->count() }} evrak">{{ $evraklarPayload->count() }}</span>
                                    </button>
                                @endif
                                @endyetki
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
                            <div class="empty-state-title">Başvuru kaydı bulunamadı</div>
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
            @if ($basvurular->total())
                {{ $basvurular->firstItem() }}–{{ $basvurular->lastItem() }} / {{ $basvurular->total() }} kayıt
            @else
                0 kayıt
            @endif
        </span>
        <form id="per-page-form" method="GET" action="{{ route('basvurular.index') }}" style="display:flex; align-items:center; gap:8px;">
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
        @if ($basvurular->hasPages())
            <div data-pagination>
                {{ $basvurular->links() }}
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
