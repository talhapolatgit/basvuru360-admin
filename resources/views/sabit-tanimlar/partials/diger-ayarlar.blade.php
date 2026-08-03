@php
    $form = $form ?? [];
    $module = $module ?? 'kurs';
    $isEtkinlik = $module === 'etkinlik';
    $secenekler = $form['secenekler'] ?? [];
    $alanlar = [
        'sms_basvuru_onay' => 'Başvuru onaylandığında SMS gönder',
        'sms_basvuru_iptal' => 'Başvuru iptal edildiğinde SMS gönder',
        'sms_basvuru_yedek' => 'Başvuru yedeğe alındığında SMS gönder',
    ];
    $metinler = [
        'sms_metin_onay' => [
            'label' => 'Onay SMS metni',
            'hint' => 'Başvuru onaylandığında gönderilir.',
        ],
        'sms_metin_iptal' => [
            'label' => 'İptal SMS metni',
            'hint' => 'Başvuru iptal edildiğinde gönderilir.',
        ],
        'sms_metin_yedek' => [
            'label' => 'Yedek SMS metni',
            'hint' => 'Başvuru yedeğe alındığında gönderilir.',
        ],
    ];
    $epostaAlanlar = [
        'eposta_basvuru_onay' => 'Başvuru onaylandığında e-posta gönder',
        'eposta_basvuru_iptal' => 'Başvuru iptal edildiğinde e-posta gönder',
        'eposta_basvuru_yedek' => 'Başvuru yedeğe alındığında e-posta gönder',
    ];
    $epostaSablonlar = [
        'onay' => [
            'konu' => 'eposta_konu_onay',
            'metin' => 'eposta_metin_onay',
            'label' => 'Onay',
            'hint' => 'Başvuru onaylandığında gönderilir.',
        ],
        'iptal' => [
            'konu' => 'eposta_konu_iptal',
            'metin' => 'eposta_metin_iptal',
            'label' => 'İptal',
            'hint' => 'Başvuru iptal edildiğinde gönderilir.',
        ],
        'yedek' => [
            'konu' => 'eposta_konu_yedek',
            'metin' => 'eposta_metin_yedek',
            'label' => 'Yedek',
            'hint' => 'Başvuru yedeğe alındığında gönderilir.',
        ],
    ];
    $updateRoute = $isEtkinlik
        ? route('sabit-tanimlar.etkinlik-diger-ayarlar.update')
        : route('sabit-tanimlar.diger-ayarlar.update');
    $idPrefix = $isEtkinlik ? 'etkinlik-' : '';
@endphp

<div class="kurs-diger-ayarlar" data-diger-ayarlar>
    <form
        method="POST"
        action="{{ $updateRoute }}"
        class="kurs-diger-ayarlar-form"
        data-diger-ayarlar-form
    >
        @csrf
        @method('PUT')

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">SMS Bildirimleri</h3>
                <p class="sertifika-ayarlar-desc">Başvuru durumu değişimlerinde SMS gönderim davranışını belirleyin.</p>
            </div>

            <div class="form-grid form-grid-2">
                @foreach ($alanlar as $name => $label)
                    <div class="form-group">
                        <label for="{{ $idPrefix }}{{ $name }}">{{ $label }}</label>
                        <select
                            id="{{ $idPrefix }}{{ $name }}"
                            name="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                            required
                        >
                            @foreach ($secenekler as $secenek)
                                <option
                                    value="{{ $secenek['value'] }}"
                                    @selected(old($name, $form[$name] ?? 'istege_bagli') === $secenek['value'])
                                >{{ $secenek['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">SMS Metinleri</h3>
                <p class="sertifika-ayarlar-desc">
                    Yer tutucu: <code>{ad_soyad}</code>
                </p>
            </div>

            <div class="form-grid">
                @foreach ($metinler as $name => $meta)
                    <div class="form-group">
                        <label for="{{ $idPrefix }}{{ $name }}">{{ $meta['label'] }}</label>
                        <textarea
                            id="{{ $idPrefix }}{{ $name }}"
                            name="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            rows="3"
                            maxlength="480"
                            @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                            required
                        >{{ old($name, $form[$name] ?? '') }}</textarea>
                        <p class="form-hint">{{ $meta['hint'] }} En fazla 480 karakter.</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">E-posta Bildirimleri</h3>
                <p class="sertifika-ayarlar-desc">Başvuru durumu değişimlerinde e-posta gönderim davranışını belirleyin.</p>
            </div>

            <div class="form-grid form-grid-2">
                @foreach ($epostaAlanlar as $name => $label)
                    <div class="form-group">
                        <label for="{{ $idPrefix }}{{ $name }}">{{ $label }}</label>
                        <select
                            id="{{ $idPrefix }}{{ $name }}"
                            name="{{ $name }}"
                            class="form-control @error($name) is-invalid @enderror"
                            @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                            required
                        >
                            @foreach ($secenekler as $secenek)
                                <option
                                    value="{{ $secenek['value'] }}"
                                    @selected(old($name, $form[$name] ?? 'istege_bagli') === $secenek['value'])
                                >{{ $secenek['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">E-posta Metinleri</h3>
                <p class="sertifika-ayarlar-desc">
                    Yer tutucu: <code>{ad_soyad}</code>
                </p>
            </div>

            <div class="form-grid">
                @foreach ($epostaSablonlar as $meta)
                    @php
                        $konuName = $meta['konu'];
                        $metinName = $meta['metin'];
                    @endphp
                    <div class="form-group">
                        <label for="{{ $idPrefix }}{{ $konuName }}">{{ $meta['label'] }} e-posta konusu</label>
                        <input
                            type="text"
                            id="{{ $idPrefix }}{{ $konuName }}"
                            name="{{ $konuName }}"
                            class="form-control @error($konuName) is-invalid @enderror"
                            maxlength="200"
                            value="{{ old($konuName, $form[$konuName] ?? '') }}"
                            @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                            required
                        >
                        <p class="form-hint">En fazla 200 karakter.</p>
                    </div>
                    <div class="form-group">
                        <label for="{{ $idPrefix }}{{ $metinName }}">{{ $meta['label'] }} e-posta metni</label>
                        <textarea
                            id="{{ $idPrefix }}{{ $metinName }}"
                            name="{{ $metinName }}"
                            class="form-control @error($metinName) is-invalid @enderror"
                            rows="4"
                            maxlength="5000"
                            @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                            required
                        >{{ old($metinName, $form[$metinName] ?? '') }}</textarea>
                        <p class="form-hint">{{ $meta['hint'] }} En fazla 5000 karakter.</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Başvuru İptali</h3>
                <p class="sertifika-ayarlar-desc">Portal üzerinden kişilerin onaylanmış başvurularını iptal edebilmesini yönetin.</p>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="{{ $idPrefix }}onaylanmis_basvuru_kisi_iptal">Onaylanmış başvuruları kişiler iptal edebilsin mi?</label>
                    <select
                        id="{{ $idPrefix }}onaylanmis_basvuru_kisi_iptal"
                        name="onaylanmis_basvuru_kisi_iptal"
                        class="form-control @error('onaylanmis_basvuru_kisi_iptal') is-invalid @enderror"
                        @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                        required
                    >
                        <option value="evet" @selected(old('onaylanmis_basvuru_kisi_iptal', $form['onaylanmis_basvuru_kisi_iptal'] ?? 'evet') === 'evet')>Evet</option>
                        <option value="hayir" @selected(old('onaylanmis_basvuru_kisi_iptal', $form['onaylanmis_basvuru_kisi_iptal'] ?? 'evet') === 'hayir')>Hayır</option>
                    </select>
                    @error('onaylanmis_basvuru_kisi_iptal')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <p class="form-hint">Hayır seçilirse kesin kayıtlı başvurular portalda iptal edilemez; onay bekleyen ve yedek başvurular iptal edilebilir.</p>
                </div>
            </div>
        </div>

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">KVKK ve Aydınlatma</h3>
                <p class="sertifika-ayarlar-desc">
                    Portal başvuru formunda gösterilir. Yalnızca metni dolu olanlar görünür; boş bırakılanlar formda çıkmaz.
                </p>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="{{ $idPrefix }}kvkk_baslik">KVKK başlığı</label>
                    <input
                        type="text"
                        id="{{ $idPrefix }}kvkk_baslik"
                        name="kvkk_baslik"
                        class="form-control @error('kvkk_baslik') is-invalid @enderror"
                        maxlength="200"
                        value="{{ old('kvkk_baslik', $form['kvkk_baslik'] ?? '') }}"
                        placeholder="KVKK Metni"
                        @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                    >
                    @error('kvkk_baslik')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="{{ $idPrefix }}aydinlatma_baslik">Aydınlatma başlığı</label>
                    <input
                        type="text"
                        id="{{ $idPrefix }}aydinlatma_baslik"
                        name="aydinlatma_baslik"
                        class="form-control @error('aydinlatma_baslik') is-invalid @enderror"
                        maxlength="200"
                        value="{{ old('aydinlatma_baslik', $form['aydinlatma_baslik'] ?? '') }}"
                        placeholder="Aydınlatma Metni"
                        @disabled(! auth()->user()?->hasYetki('sabit.guncelle'))
                    >
                    @error('aydinlatma_baslik')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="{{ $idPrefix }}kvkk_metni">KVKK metni</label>
                <div
                    class="rich-editor @error('kvkk_metni') is-invalid @enderror"
                    data-rich-editor
                    @if (! auth()->user()?->hasYetki('sabit.guncelle')) data-rich-editor-disabled @endif
                >
                    @include('sabit-tanimlar.partials.rich-editor-toolbar')
                    <div
                        class="rich-editor__surface"
                        data-rich-surface
                        contenteditable="{{ auth()->user()?->hasYetki('sabit.guncelle') ? 'true' : 'false' }}"
                        role="textbox"
                        aria-multiline="true"
                        aria-label="KVKK metni"
                    >{!! old('kvkk_metni', $form['kvkk_metni'] ?? '') !!}</div>
                    <textarea
                        id="{{ $idPrefix }}kvkk_metni"
                        name="kvkk_metni"
                        class="rich-editor__input"
                        hidden
                        data-rich-input
                    >{{ old('kvkk_metni', $form['kvkk_metni'] ?? '') }}</textarea>
                </div>
                @error('kvkk_metni')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="{{ $idPrefix }}aydinlatma_metni">Aydınlatma metni</label>
                <div
                    class="rich-editor @error('aydinlatma_metni') is-invalid @enderror"
                    data-rich-editor
                    @if (! auth()->user()?->hasYetki('sabit.guncelle')) data-rich-editor-disabled @endif
                >
                    @include('sabit-tanimlar.partials.rich-editor-toolbar')
                    <div
                        class="rich-editor__surface"
                        data-rich-surface
                        contenteditable="{{ auth()->user()?->hasYetki('sabit.guncelle') ? 'true' : 'false' }}"
                        role="textbox"
                        aria-multiline="true"
                        aria-label="Aydınlatma metni"
                    >{!! old('aydinlatma_metni', $form['aydinlatma_metni'] ?? '') !!}</div>
                    <textarea
                        id="{{ $idPrefix }}aydinlatma_metni"
                        name="aydinlatma_metni"
                        class="rich-editor__input"
                        hidden
                        data-rich-input
                    >{{ old('aydinlatma_metni', $form['aydinlatma_metni'] ?? '') }}</textarea>
                </div>
                @error('aydinlatma_metni')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sertifika-ayarlar-actions">
            @yetki('sabit.guncelle')
                <button type="submit" class="btn-cta">
                    <span class="btn-cta-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </span>
                    <span class="btn-cta-text">Ayarları Kaydet</span>
                </button>
            @else
                <button type="button" class="btn-cta" disabled title="Bu işlem için yetkiniz yok">
                    <span class="btn-cta-text">Ayarları Kaydet</span>
                </button>
            @endyetki
        </div>
    </form>
</div>
