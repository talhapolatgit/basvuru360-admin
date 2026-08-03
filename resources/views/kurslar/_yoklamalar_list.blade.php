<div class="card table-card lesson-table-card">
    @if ($dersler->isEmpty())
        <div class="empty-state">
            <div class="empty-state-title">Ders oturumu yok</div>
            <p class="empty-state-text">Bugün ve öncesine ait yoklama alınacak ders bulunmuyor.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table" id="kurs-ders-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Saat</th>
                        <th>Süre</th>
                        <th>Sınıf</th>
                        <th>Yoklama</th>
                        <th>Katılım</th>
                        <th>Kaydeden</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dersler as $ders)
                        @php
                            $gunAdi = $ders->tarih?->locale('tr')->isoFormat('dddd') ?? '';
                            $rowClasses = trim(($ders->yoklama_alindi ? 'is-done' : '').($ders->iptal_edildi ? ' is-cancelled' : ''));
                        @endphp
                        <tr class="{{ $rowClasses }}">
                            <td>
                                <div>{{ $ders->tarih?->format('d.m.Y') ?? '—' }}</div>
                                @if ($ders->orijinal_tarih)
                                    <div class="takvim-eski-tarih">Eski: {{ $ders->orijinal_tarih->format('d.m.Y') }}</div>
                                @elseif ($gunAdi !== '')
                                    <div class="yoklama-gun-adi">{{ $gunAdi }}</div>
                                @endif
                            </td>
                            <td>{{ substr((string) $ders->baslangic_saati, 0, 5) }} – {{ substr((string) $ders->bitis_saati, 0, 5) }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.') }}</td>
                            <td>{{ $ders->sinif ?: '—' }}</td>
                            <td>
                                @if ($ders->iptal_edildi)
                                    <span class="status status-iptal" title="{{ $ders->iptal_gerekcesi }}">İptal Edildi</span>
                                @elseif ($ders->yoklama_alindi)
                                    <span class="status status-tamamlanan">Alındı</span>
                                @else
                                    <span class="status status-hazirlik">Bekliyor</span>
                                @endif
                            </td>
                            <td>{{ (int) $ders->var_sayisi }} / {{ (int) $ders->yoklamalar_count }}</td>
                            <td>{{ $ders->yoklamaAlan?->tam_adi ?: '—' }}</td>
                            <td class="yoklama-action-cell">
                                @if ($ders->iptal_edildi)
                                    <span class="yoklama-action-none">—</span>
                                @else
                                    @php
                                        $yoklamaDuzenleyebilir = auth()->user()?->hasYetki('kurs.yoklama') ?? false;
                                        $yoklamaIslemVar = $ders->yoklama_alindi || $yoklamaDuzenleyebilir;
                                    @endphp
                                    @if ($yoklamaIslemVar)
                                        <div class="row-actions" data-row-actions>
                                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                                @if ($ders->yoklama_alindi)
                                                    <button
                                                        type="button"
                                                        class="action-dropdown-item"
                                                        role="menuitem"
                                                        data-yoklama-ders="{{ $ders->id }}"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        {{ $yoklamaDuzenleyebilir ? 'Düzenle' : 'Görüntüle' }}
                                                    </button>
                                                    @yetki('kurs.yoklama')
                                                    <button
                                                        type="button"
                                                        class="action-dropdown-item action-dropdown-item-danger"
                                                        role="menuitem"
                                                        data-yoklama-delete="{{ $ders->id }}"
                                                        data-yoklama-delete-ozet="{{ $ders->ozet() }}"
                                                        data-yoklama-delete-url="{{ route('kurslar.yoklama.delete', [$kurs, $ders]) }}"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                        Sil
                                                    </button>
                                                    @endyetki
                                                @else
                                                    <button
                                                        type="button"
                                                        class="action-dropdown-item"
                                                        role="menuitem"
                                                        data-yoklama-ders="{{ $ders->id }}"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                                        Yoklama Al
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="yoklama-action-none">—</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="table-footer lesson-table-footer">
            <div class="table-footer-right">
                <x-excel-export :href="route('kurslar.yoklamalar.export', $kurs)" class="btn-excel-sm" />
            </div>
        </div>
    @endif
</div>
