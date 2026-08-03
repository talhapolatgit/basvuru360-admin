<div class="card table-card lesson-table-card">
    <div class="table-wrapper">
        <table class="data-table" data-etkinlik-yoklama-table data-sort="" data-direction="asc">
            <thead>
                <tr>
                    <th data-column="katilimci" data-sortable="1">Katılımcı</th>
                    <th data-column="kimlik" data-sortable="1">Kimlik</th>
                    <th data-column="katilim" data-sortable="1">Katılım</th>
                </tr>
                <tr class="column-search-row">
                    <th data-column="katilimci">
                        <input type="text" class="form-control column-search" data-column-search="katilimci" placeholder="Ara..." autocomplete="off">
                    </th>
                    <th data-column="kimlik">
                        <input type="text" class="form-control column-search" data-column-search="kimlik" placeholder="Ara..." autocomplete="off">
                    </th>
                    <th data-column="katilim">
                        <input type="text" class="form-control column-search" data-column-search="katilim" placeholder="Ara..." autocomplete="off">
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($yoklamaListesi as $basvuru)
                    @php
                        $durum = $basvuru->katilim_durumu?->value;
                        $ad = $basvuru->kisi?->tam_adi ?? $basvuru->basvuran?->tam_adi ?? ('#'.$basvuru->id);
                        $kimlik = $basvuru->kisi?->tc_kimlik_no ?? '';
                    @endphp
                    <tr data-yoklama-row data-basvuru-id="{{ $basvuru->id }}" data-katilim-durumu="{{ $durum ?? '' }}">
                        <td data-column="katilimci" data-sort-value="{{ $ad }}">{{ $ad }}</td>
                        <td data-column="kimlik" class="mono-cell" data-sort-value="{{ $kimlik }}">{{ $kimlik !== '' ? $kimlik : '—' }}</td>
                        <td
                            data-column="katilim"
                            data-sort-value="{{ $durum ?? '' }}"
                            data-filter-value="{{ $basvuru->katilim_durumu?->label() ?? '' }}"
                        >
                            @yetki('etkinlik.yoklama')
                                <div class="yoklama-durum-group" role="group" aria-label="Katılım durumu">
                                    <button
                                        type="button"
                                        class="yoklama-durum-option yoklama-durum-var {{ $durum === 'katildi' ? 'is-active' : '' }}"
                                        data-yoklama-set="katildi"
                                    >Katıldı</button>
                                    <button
                                        type="button"
                                        class="yoklama-durum-option yoklama-durum-yok {{ $durum === 'katilmadi' ? 'is-active' : '' }}"
                                        data-yoklama-set="katilmadi"
                                    >Katılmadı</button>
                                </div>
                            @else
                                @if ($basvuru->katilim_durumu)
                                    <span class="status {{ $basvuru->katilim_durumu->statusClass() }}">{{ $basvuru->katilim_durumu->label() }}</span>
                                @else
                                    —
                                @endif
                            @endyetki
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="3">
                            <div class="empty-state">
                                <div class="empty-state-title">Kesin kayıtlı katılımcı yok</div>
                                <p class="empty-state-text">Yoklama almak için önce başvuruları kesin kayda alın.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
