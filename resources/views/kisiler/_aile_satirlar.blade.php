@forelse ($yakinlar as $kayit)
    @php
        $yakinKisi = $kayit->yakin;
    @endphp
    <tr>
        <td data-column="ad_soyad" data-sort-value="{{ $kayit->tam_adi }}">
            @if ($yakinKisi)
                <a href="{{ route('kisiler.show', $yakinKisi) }}" class="kurs-no" title="Kişi kaydına git">{{ $kayit->tam_adi ?: $yakinKisi->tam_adi }}</a>
            @else
                {{ $kayit->tam_adi ?: '—' }}
            @endif
        </td>
        <td data-column="tc">{{ $kayit->tc_kimlik_no ?: '—' }}</td>
        <td data-column="dogum" data-sort-value="{{ $kayit->dogum_tarihi?->toDateString() }}">
            {{ $kayit->dogum_tarihi?->format('d.m.Y') ?? '—' }}
        </td>
        <td data-column="yakinlik">{{ $kayit->yakinlikDerecesi?->ad ?? '—' }}</td>
        <td data-column="son_sorgu" data-sort-value="{{ $kayit->son_sorgu_at?->toDateTimeString() }}">
            {{ $kayit->son_sorgu_at?->format('d.m.Y H:i') ?? '—' }}
        </td>
        <td data-column="kaydeden" data-sort-value="{{ $kayit->created_at?->toDateTimeString() }}">
            {{ $kayit->kaydedenAdi() ?? '—' }}
            @if ($kayit->created_at)
                <div class="form-hint" style="margin:0;">{{ $kayit->created_at->format('d.m.Y H:i') }}</div>
            @endif
        </td>
        @if ($canEditKisi)
            <td data-column="islemler">
                <div class="row-actions" data-row-actions>
                    <button type="button" class="action-menu-btn" data-action-toggle aria-expanded="false" aria-haspopup="menu" title="İşlemler">•••</button>
                    <div class="action-dropdown" data-action-dropdown hidden role="menu">
                        @if ($yakinKisi)
                            <a href="{{ route('kisiler.show', $yakinKisi) }}" class="action-dropdown-item" role="menuitem">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Kişi Kaydını Aç
                            </a>
                        @endif
                        <button
                            type="button"
                            class="action-dropdown-item action-dropdown-item-danger"
                            role="menuitem"
                            data-kisi-yakin-sil="{{ route('kisiler.yakinlar.destroy', [$kisi, $kayit]) }}"
                            data-ad="{{ $kayit->tam_adi }}"
                            data-yakinlik="{{ $kayit->yakinlikDerecesi?->ad }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            Sil
                        </button>
                    </div>
                </div>
            </td>
        @endif
    </tr>
@empty
    <tr>
        <td colspan="{{ $kolonSayisi }}">
            <div class="empty-state">
                <div class="empty-state-title">Yakın bulunamadı</div>
                <p class="empty-state-text">Bu kişi için henüz aile kaydı eklenmemiş.</p>
            </div>
        </td>
    </tr>
@endforelse
