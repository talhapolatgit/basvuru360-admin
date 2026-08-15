@php
    $allColumns = $basvuruColumns ?? [];
    $defaultVisible = $basvuruDefaultVisible ?? array_keys($allColumns);
    $sortableColumns = $basvuruSortable ?? [];
    $defaultOrder = array_keys($allColumns);
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $canDurum = auth()->user()?->hasYetki('kres.basvuru_durum_guncelle') ?? false;
    $canKisiGoruntule = auth()->user()?->hasYetki('kisi.goruntule') ?? false;
    $canSms = auth()->user()?->hasYetki('kisi.sms') ?? false;
    $canEposta = auth()->user()?->hasYetki('kisi.eposta') ?? false;
@endphp

<div class="table-wrapper">
    <table
        class="data-table"
        id="kres-basvuru-table"
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
                    <td data-column="ogrenci" class="col-ogrenci {{ in_array('ogrenci', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->tam_adi ?? '—' }}
                    </td>
                    <td data-column="kimlik" class="col-kimlik mono-cell {{ in_array('kimlik', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->tc_kimlik_no ?? '—' }}
                    </td>
                    <td data-column="dogum" class="col-dogum {{ in_array('dogum', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td data-column="telefon" class="col-telefon {{ in_array('telefon', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->kisi?->telefon ?? '—' }}
                    </td>
                    <td data-column="basvuran" class="col-basvuran {{ in_array('basvuran', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->basvuran?->tam_adi ?? '—' }}
                    </td>
                    <td data-column="veli" class="col-veli {{ in_array('veli', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->basvuran?->tam_adi ?? '—' }}
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
                    <td data-column="notlar" class="col-notlar {{ in_array('notlar', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->notlar ? \Illuminate\Support\Str::limit($basvuru->notlar, 60) : '—' }}
                    </td>
                    <td data-column="kaydeden" class="col-kaydeden {{ in_array('kaydeden', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->olusturan?->tam_adi ?: '—' }}
                    </td>
                    <td data-column="basvuru_tarihi" class="col-basvuru_tarihi {{ in_array('basvuru_tarihi', $defaultVisible, true) ? '' : 'col-hidden' }}">
                        {{ $basvuru->created_at?->format('d.m.Y H:i') }}
                    </td>
                    <td data-column="islemler" class="col-islemler">
                        @php
                            $ogrenci = $basvuru->kisi;
                            $veli = $basvuru->basvuran;
                            $mesajKisi = $veli ?: $ogrenci;
                            $telefonVar = filled($mesajKisi?->telefon);
                            $emailVar = filled($mesajKisi?->email);
                        @endphp
                        <div class="row-actions" data-row-actions>
                            <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                            <div class="action-dropdown" data-action-dropdown hidden role="menu">
                                @if ($canDurum)
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-kres-durum-open
                                        data-url="{{ route('kres.basvurular.durum', [$okul, $grup, $basvuru]) }}"
                                        data-durum-id="{{ $basvuru->durum_id }}"
                                        data-yedek-sira="{{ $basvuru->yedek_sira }}"
                                        data-kisi="{{ $ogrenci?->tam_adi }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        Durum Güncelle
                                    </button>
                                @else
                                    <button type="button" class="action-dropdown-item is-disabled" role="menuitem" disabled aria-disabled="true" title="Bu işlem için yetkiniz yok">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        Durum Güncelle
                                    </button>
                                @endif

                                @if ($canKisiGoruntule && $ogrenci)
                                    <a href="{{ route('kisiler.show', $ogrenci) }}" class="action-dropdown-item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        Öğrenci Profili
                                    </a>
                                @else
                                    <button type="button" class="action-dropdown-item is-disabled" role="menuitem" disabled aria-disabled="true" title="{{ $ogrenci ? 'Bu işlem için yetkiniz yok' : 'Öğrenci kaydı yok' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        Öğrenci Profili
                                    </button>
                                @endif

                                @if ($canKisiGoruntule && $veli)
                                    <a href="{{ route('kisiler.show', $veli) }}" class="action-dropdown-item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        Veli Profili
                                    </a>
                                @else
                                    <button type="button" class="action-dropdown-item is-disabled" role="menuitem" disabled aria-disabled="true" title="{{ $veli ? 'Bu işlem için yetkiniz yok' : 'Veli kaydı yok' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        Veli Profili
                                    </button>
                                @endif

                                @if ($canSms && $mesajKisi)
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-kres-sms-ac
                                        data-send-url="{{ route('kisiler.sms.send', $mesajKisi) }}"
                                        data-ad="{{ $mesajKisi->tam_adi }}"
                                        data-rol="{{ $veli ? 'veli' : 'öğrenci' }}"
                                        data-telefon-var="{{ $telefonVar ? '1' : '0' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        SMS Gönder
                                    </button>
                                @else
                                    <button type="button" class="action-dropdown-item is-disabled" role="menuitem" disabled aria-disabled="true" title="Bu işlem için yetkiniz yok">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        SMS Gönder
                                    </button>
                                @endif

                                @if ($canEposta && $mesajKisi)
                                    <button
                                        type="button"
                                        class="action-dropdown-item"
                                        role="menuitem"
                                        data-kres-eposta-ac
                                        data-send-url="{{ route('kisiler.eposta.send', $mesajKisi) }}"
                                        data-ad="{{ $mesajKisi->tam_adi }}"
                                        data-rol="{{ $veli ? 'veli' : 'öğrenci' }}"
                                        data-email="{{ $mesajKisi->email }}"
                                        data-email-var="{{ $emailVar ? '1' : '0' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        E-posta Gönder
                                    </button>
                                @else
                                    <button type="button" class="action-dropdown-item is-disabled" role="menuitem" disabled aria-disabled="true" title="Bu işlem için yetkiniz yok">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        E-posta Gönder
                                    </button>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($allColumns) }}" class="text-center text-muted">Bu filtrede başvuru bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($basvurular instanceof \Illuminate\Pagination\AbstractPaginator && $basvurular->hasPages())
    <div class="table-pagination" data-pagination>
        {{ $basvurular->links() }}
    </div>
@endif
