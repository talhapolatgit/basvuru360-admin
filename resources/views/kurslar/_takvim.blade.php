@php
    $dersTakvim = $dersTakvim ?? collect();
    $bugun = now()->toDateString();
    $kursAdi = $kurs->brans?->ad ?? ('Kurs #'.$kurs->kurs_no);
    $takvimJson = $dersTakvim->map(fn ($ders) => [
        'id' => $ders->id,
        'tarih' => $ders->tarih?->format('Y-m-d'),
        'gun' => $ders->tarih?->locale('tr')->isoFormat('dddd'),
        'baslangic' => substr((string) $ders->baslangic_saati, 0, 5),
        'bitis' => substr((string) $ders->bitis_saati, 0, 5),
        'ders_saati' => rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.'),
        'sinif' => $ders->sinif ?: '',
        'kurs_adi' => $kursAdi,
        'yoklama_alindi' => (bool) $ders->yoklama_alindi,
        'iptal_edildi' => (bool) $ders->iptal_edildi,
    ])->values();
@endphp

<div
    class="lesson-tab-panel {{ $activeTab === 'takvim' ? 'is-active' : '' }}"
    data-lesson-panel="takvim"
    data-takvim-panel
    data-takvim-dersler='@json($takvimJson)'
    data-takvim-pdf-base="{{ route('kurslar.takvim.pdf', $kurs) }}"
    role="tabpanel"
>
    <div class="takvim-toolbar">
        <div class="takvim-view-toggle" role="group" aria-label="Görünüm">
            <button type="button" class="takvim-view-btn is-active" data-takvim-view="liste">Liste</button>
            <button type="button" class="takvim-view-btn" data-takvim-view="aylik">Takvim</button>
        </div>
        <div class="takvim-toolbar-meta" data-takvim-count>
            Toplam ders günü: {{ $dersTakvim->count() }}
        </div>
    </div>

    <div class="card table-card lesson-table-card" data-takvim-liste>
        @if ($dersTakvim->isEmpty())
            <div class="empty-state">
                <div class="empty-state-title">Takvim boş</div>
                <p class="empty-state-text">Bu kurs için oluşturulmuş ders oturumu bulunmuyor.</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table" id="kurs-takvim-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Gün</th>
                            <th>Saat</th>
                            <th>Süre</th>
                            <th>Sınıf</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dersTakvim as $ders)
                            @php
                                $tarihStr = $ders->tarih?->toDateString();
                                $isPast = $tarihStr && $tarihStr < $bugun;
                                $isToday = $tarihStr === $bugun;
                                $rowClasses = trim(($isToday ? 'is-today' : ($isPast ? 'is-past' : '')).($ders->iptal_edildi ? ' is-cancelled' : ''));
                            @endphp
                            <tr class="{{ $rowClasses }}">
                                <td>
                                    <div>{{ $ders->tarih?->format('d.m.Y') ?? '—' }}</div>
                                    @if ($ders->orijinal_tarih)
                                        <div class="takvim-eski-tarih">Eski: {{ $ders->orijinal_tarih->format('d.m.Y') }}</div>
                                    @endif
                                </td>
                                <td>{{ $ders->tarih?->locale('tr')->isoFormat('dddd') ?? '—' }}</td>
                                <td>
                                    {{ substr((string) $ders->baslangic_saati, 0, 5) }}
                                    –
                                    {{ substr((string) $ders->bitis_saati, 0, 5) }}
                                </td>
                                <td>{{ rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.') }}</td>
                                <td>{{ $ders->sinif ?: '—' }}</td>
                                <td>
                                    @if ($ders->iptal_edildi)
                                        <span class="status status-iptal" title="{{ $ders->iptal_gerekcesi }}">İptal Edildi</span>
                                    @elseif ($ders->yoklama_alindi)
                                        <span class="status status-tamamlanan">Alındı</span>
                                    @elseif ($tarihStr && $tarihStr <= $bugun)
                                        <span class="status status-hazirlik">Bekliyor</span>
                                    @else
                                        <span class="status status-hazirlik">Planlandı</span>
                                    @endif
                                </td>
                                <td class="yoklama-action-cell">
                                    <div class="row-actions" data-row-actions>
                                        <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                                        <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                            @if ($ders->iptal_edildi)
                                                <button
                                                    type="button"
                                                    class="action-dropdown-item"
                                                    role="menuitem"
                                                    data-takvim-iptal-geri-al="{{ $ders->id }}"
                                                    data-takvim-iptal-geri-al-ozet="{{ $ders->ozet() }}"
                                                    data-takvim-iptal-geri-al-url="{{ route('kurslar.dersler.iptal-geri-al', [$kurs, $ders]) }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                    İptali Geri Al
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="action-dropdown-item action-dropdown-item-danger"
                                                    role="menuitem"
                                                    data-takvim-iptal="{{ $ders->id }}"
                                                    data-takvim-iptal-ozet="{{ $ders->ozet() }}"
                                                    data-takvim-iptal-url="{{ route('kurslar.dersler.iptal', [$kurs, $ders]) }}"
                                                    data-takvim-iptal-yoklama-alindi="{{ $ders->yoklama_alindi ? '1' : '0' }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m14.5 9.5-5 5"/><path d="m9.5 9.5 5 5"/></svg>
                                                    Ders İptal
                                                </button>
                                                <button
                                                    type="button"
                                                    class="action-dropdown-item"
                                                    role="menuitem"
                                                    data-takvim-tarih="{{ $ders->id }}"
                                                    data-takvim-tarih-ozet="{{ $ders->ozet() }}"
                                                    data-takvim-tarih-mevcut="{{ $tarihStr }}"
                                                    data-takvim-tarih-url="{{ route('kurslar.dersler.tarih-degistir', [$kurs, $ders]) }}"
                                                    data-takvim-tarih-yoklama-alindi="{{ $ders->yoklama_alindi ? '1' : '0' }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                                                    Tarih Değiştir
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer lesson-table-footer">
                <div class="table-footer-right">
                    <x-excel-export :href="route('kurslar.takvim.export', $kurs)" class="btn-excel-sm" />
                </div>
            </div>
        @endif
    </div>

    <div class="card form-section-card takvim-aylik-card" data-takvim-aylik hidden>
        <div class="takvim-aylik-header">
            <button type="button" class="takvim-nav-btn" data-takvim-prev aria-label="Önceki ay">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <h2 class="takvim-aylik-title" data-takvim-month-label></h2>
            <button type="button" class="takvim-nav-btn" data-takvim-next aria-label="Sonraki ay">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <div class="takvim-aylik-grid" data-takvim-grid></div>
        <div class="takvim-aylik-legend">
            <span><i class="takvim-dot has-ders"></i> Ders var</span>
            <span><i class="takvim-dot is-today-dot"></i> Bugün</span>
        </div>
        <div class="takvim-gun-detay" data-takvim-day-detail hidden>
            <h3 class="takvim-gun-detay-title" data-takvim-day-title></h3>
            <ul class="takvim-gun-detay-list" data-takvim-day-list></ul>
        </div>
        <div class="table-footer lesson-table-footer takvim-pdf-footer">
            <div class="table-footer-right">
                <a
                    href="{{ route('kurslar.takvim.pdf', $kurs) }}"
                    class="btn-pdf btn-pdf-sm"
                    data-takvim-pdf
                    target="_blank"
                    rel="noopener"
                >
                    <span class="btn-pdf-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6"/>
                            <path d="M10 13h4"/>
                            <path d="M10 17h4"/>
                            <path d="M10 9h1"/>
                        </svg>
                    </span>
                    <span class="btn-pdf-text">PDF</span>
                </a>
            </div>
        </div>
    </div>
</div>
