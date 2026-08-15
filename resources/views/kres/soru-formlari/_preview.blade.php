<div class="kres-soru-onizleme">
    <div class="kres-soru-onizleme-banner">Bu bir önizlemedir. Cevaplar kaydedilmez.</div>

    <div class="kres-soru-onizleme-head">
        <h2 class="kres-soru-onizleme-ad">{{ $form->ad }}</h2>
        @if ($form->donem)
            <p class="kres-soru-onizleme-donem">{{ $form->donem->ad }}{{ $form->donem->aktif ? ' (aktif)' : '' }}</p>
        @endif
        @if ($form->aciklama)
            <p class="kres-soru-onizleme-aciklama">{{ $form->aciklama }}</p>
        @endif
    </div>

    <form class="kres-soru-onizleme-form" onsubmit="return false;" novalidate>
        @forelse ($form->sorular as $soru)
            @php $tip = $soru->tip; @endphp
            <div class="form-group">
                <label>
                    {{ $soru->baslik }}
                    @if ($soru->zorunlu)
                        <span class="req">*</span>
                    @endif
                </label>
                @if ($soru->aciklama)
                    <p class="form-hint">{{ $soru->aciklama }}</p>
                @endif

                @switch ($tip)
                    @case(\App\Enums\KresSoruTipi::UzunMetin)
                        <textarea class="form-control" rows="4" placeholder="{{ $tip->placeholder() }}"></textarea>
                        @break

                    @case(\App\Enums\KresSoruTipi::Sayi)
                        <input
                            type="number"
                            class="form-control"
                            placeholder="{{ $tip->placeholder() }}"
                            @if ($soru->min_deger !== null) min="{{ $soru->min_deger }}" @endif
                            @if ($soru->max_deger !== null) max="{{ $soru->max_deger }}" @endif
                            step="{{ $soru->tam_sayi ? '1' : 'any' }}"
                        >
                        @break

                    @case(\App\Enums\KresSoruTipi::Liste)
                        <select class="form-control">
                            <option value="">Seçiniz</option>
                            @foreach ($soru->secenekler as $secenek)
                                <option value="{{ $secenek->id }}">{{ $secenek->etiket }}</option>
                            @endforeach
                        </select>
                        @break

                    @case(\App\Enums\KresSoruTipi::Checkbox)
                        @if ($soru->min_deger !== null || $soru->max_deger !== null)
                            <p class="form-hint">
                                @if ($soru->min_deger !== null && $soru->max_deger !== null)
                                    En az {{ (int) $soru->min_deger }}, en fazla {{ (int) $soru->max_deger }} seçim yapın.
                                @elseif ($soru->min_deger !== null)
                                    En az {{ (int) $soru->min_deger }} seçim yapın.
                                @else
                                    En fazla {{ (int) $soru->max_deger }} seçim yapın.
                                @endif
                            </p>
                        @endif
                        <div
                            class="kres-soru-onizleme-secenekler"
                            data-checkbox-secim
                            @if ($soru->min_deger !== null) data-min-secim="{{ (int) $soru->min_deger }}" @endif
                            @if ($soru->max_deger !== null) data-max-secim="{{ (int) $soru->max_deger }}" @endif
                        >
                            @foreach ($soru->secenekler as $secenek)
                                <label class="checkbox-label">
                                    <input type="checkbox" value="{{ $secenek->id }}">
                                    <span>{{ $secenek->etiket }}</span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case(\App\Enums\KresSoruTipi::Radio)
                        <div class="kres-soru-onizleme-secenekler">
                            @foreach ($soru->secenekler as $secenek)
                                <label class="checkbox-label">
                                    <input type="radio" name="onizleme_{{ $soru->id }}" value="{{ $secenek->id }}">
                                    <span>{{ $secenek->etiket }}</span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case(\App\Enums\KresSoruTipi::Tarih)
                        <input type="date" class="form-control">
                        @break

                    @case(\App\Enums\KresSoruTipi::Dosya)
                        <input type="file" class="form-control">
                        @break

                    @case(\App\Enums\KresSoruTipi::Resim)
                        <input type="file" class="form-control" accept="image/*">
                        @break

                    @case(\App\Enums\KresSoruTipi::TcKimlik)
                        <input type="text" class="form-control" inputmode="numeric" maxlength="11" placeholder="{{ $tip->placeholder() }}">
                        @break

                    @case(\App\Enums\KresSoruTipi::CepTelefonu)
                        <input
                            type="tel"
                            class="form-control"
                            inputmode="numeric"
                            autocomplete="tel"
                            maxlength="14"
                            placeholder="{{ $tip->placeholder() }}"
                            data-cep-telefonu
                        >
                        @break

                    @case(\App\Enums\KresSoruTipi::Eposta)
                        <input type="email" class="form-control" placeholder="{{ $tip->placeholder() }}">
                        @break

                    @default
                        <input type="text" class="form-control" placeholder="{{ $tip->placeholder() }}">
                @endswitch
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-title">Bu formda henüz soru yok</div>
                <p class="empty-state-text">Önizleme için önce soru ekleyin.</p>
            </div>
        @endforelse
    </form>
</div>
