<article
    class="kres-soru-card"
    data-soru-card
    data-soru-id="{{ $soru->id }}"
    data-update-url="{{ route('kres.soru-formlari.sorular.update', [$form, $soru]) }}"
    data-delete-url="{{ route('kres.soru-formlari.sorular.destroy', [$form, $soru]) }}"
    data-tip="{{ $soru->tip->value }}"
    data-baslik="{{ $soru->baslik }}"
    data-aciklama="{{ $soru->aciklama }}"
    data-zorunlu="{{ $soru->zorunlu ? '1' : '0' }}"
    data-tam-sayi="{{ $soru->tam_sayi ? '1' : '0' }}"
    data-min-deger="{{ $soru->min_deger }}"
    data-max-deger="{{ $soru->max_deger }}"
    data-secenekler='@json($soru->secenekler->pluck('etiket')->values())'
>
    <span class="kres-soru-drag" data-soru-drag title="Sıralamak için sürükleyin" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="9" cy="6" r="1.4"/><circle cx="15" cy="6" r="1.4"/>
            <circle cx="9" cy="12" r="1.4"/><circle cx="15" cy="12" r="1.4"/>
            <circle cx="9" cy="18" r="1.4"/><circle cx="15" cy="18" r="1.4"/>
        </svg>
    </span>
    <div class="kres-soru-card__index" data-soru-index>{{ $index }}</div>
    <div class="kres-soru-card__body">
        <div class="kres-soru-card__main">
            <h2 class="kres-soru-card__title">{{ $soru->baslik }}</h2>
            <span class="kres-soru-tip">{{ $soru->tip->label() }}</span>
            @if ($soru->zorunlu)
                <span class="kres-soru-zorunlu">Zorunlu</span>
            @endif
        </div>
        @php
            $ozet = $soru->sayiOzeti()
                ?? ($soru->secenekler->isNotEmpty() ? $soru->secenekler->pluck('etiket')->implode(' · ') : $soru->aciklama);
        @endphp
        @if ($ozet)
            <p class="kres-soru-card__opts">{{ $ozet }}</p>
        @endif
    </div>
    <div class="kres-soru-card__actions">
        <button type="button" class="kres-soru-icon-btn" data-soru-duzenle title="Düzenle" aria-label="Düzenle">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
        </button>
        <button type="button" class="kres-soru-icon-btn kres-soru-icon-btn--danger" data-soru-sil title="Sil" aria-label="Sil">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        </button>
    </div>
</article>
