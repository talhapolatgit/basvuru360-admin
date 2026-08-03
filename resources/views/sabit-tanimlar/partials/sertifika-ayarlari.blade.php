<div class="sertifika-ayarlar" data-sertifika-ayarlar>
    <form
        method="POST"
        action="{{ route('sabit-tanimlar.sertifika-ayarlari.update') }}"
        enctype="multipart/form-data"
        class="sertifika-ayarlar-form"
        data-sertifika-ayarlar-form
    >
        @csrf
        @method('PUT')

        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Genel</h3>
                <p class="sertifika-ayarlar-desc">Belge üzerinde görünecek kurum / kuruluş adı.</p>
            </div>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="sertifika-kurum-adi">Kurum adı</label>
                    <input
                        type="text"
                        id="sertifika-kurum-adi"
                        name="kurum_adi"
                        class="form-control"
                        value="{{ old('kurum_adi', $form['kurum_adi'] ?? '') }}"
                        maxlength="200"
                        placeholder="Boş bırakılırsa basılmaz"
                    >
                    <p class="form-hint">Boş bırakılırsa sertifikaya yazılmaz.</p>
                </div>
            </div>
        </div>

        @foreach (($form['sablonlar'] ?? []) as $kod => $sablon)
            <div class="sertifika-ayarlar-block" data-sablon-kod="{{ $kod }}">
                <div class="sertifika-ayarlar-block-head">
                    <h3 class="sertifika-ayarlar-title">{{ $sablon['etiket'] }}</h3>
                    <p class="sertifika-ayarlar-desc">
                        Belge metni ve alt metinde yer tutucu kullanabilirsiniz.
                    </p>
                    @if (! empty($form['yer_tutucular']))
                        <div class="sertifika-yer-tutucular" aria-label="Yer tutucular">
                            @foreach ($form['yer_tutucular'] as $token => $aciklama)
                                <span class="sertifika-yer-tutucu" title="{{ $aciklama }}">
                                    <code>{{ $token }}</code>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="sertifika-ayarlar-layout">
                    <div class="sertifika-ayarlar-fields">
                        <div class="form-grid form-grid-2">
                            <div class="form-group">
                                <label for="sablon-{{ $kod }}-kod">Belge kodu</label>
                                <input
                                    type="text"
                                    id="sablon-{{ $kod }}-kod"
                                    name="sablonlar[{{ $kod }}][kod]"
                                    class="form-control"
                                    value="{{ old('sablonlar.'.$kod.'.kod', $sablon['kod']) }}"
                                    maxlength="20"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="sablon-{{ $kod }}-baslik">Başlık</label>
                                <input
                                    type="text"
                                    id="sablon-{{ $kod }}-baslik"
                                    name="sablonlar[{{ $kod }}][baslik]"
                                    class="form-control"
                                    value="{{ old('sablonlar.'.$kod.'.baslik', $sablon['baslik']) }}"
                                    maxlength="120"
                                    placeholder="Boş bırakılırsa basılmaz"
                                >
                            </div>
                            <div class="form-group">
                                <label for="sablon-{{ $kod }}-alt-baslik">Alt başlık</label>
                                <input
                                    type="text"
                                    id="sablon-{{ $kod }}-alt-baslik"
                                    name="sablonlar[{{ $kod }}][alt_baslik]"
                                    class="form-control"
                                    value="{{ old('sablonlar.'.$kod.'.alt_baslik', $sablon['alt_baslik']) }}"
                                    maxlength="120"
                                    placeholder="Boş bırakılırsa basılmaz"
                                >
                            </div>
                            <div class="form-group">
                                <label for="sablon-{{ $kod }}-kenarlik">Kenarlık ölçüsü (mm)</label>
                                <input
                                    type="number"
                                    id="sablon-{{ $kod }}-kenarlik"
                                    name="sablonlar[{{ $kod }}][kenarlik_olcusu]"
                                    class="form-control"
                                    value="{{ old('sablonlar.'.$kod.'.kenarlik_olcusu', $sablon['kenarlik_olcusu'] ?? 12) }}"
                                    min="0"
                                    max="40"
                                    step="0.5"
                                    required
                                >
                                <p class="form-hint">Üst başlık ve alt imza alanları bu kenarlığın içine girmez.</p>
                            </div>
                            <div class="form-group sertifika-color-pair">
                                <div>
                                    <label for="sablon-{{ $kod }}-renk">Ana renk</label>
                                    <input
                                        type="color"
                                        id="sablon-{{ $kod }}-renk"
                                        name="sablonlar[{{ $kod }}][renk]"
                                        class="form-control form-control-color"
                                        value="{{ old('sablonlar.'.$kod.'.renk', $sablon['renk']) }}"
                                    >
                                </div>
                                <div>
                                    <label for="sablon-{{ $kod }}-vurgu">Vurgu rengi</label>
                                    <input
                                        type="color"
                                        id="sablon-{{ $kod }}-vurgu"
                                        name="sablonlar[{{ $kod }}][vurgu]"
                                        class="form-control form-control-color"
                                        value="{{ old('sablonlar.'.$kod.'.vurgu', $sablon['vurgu']) }}"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sablon-{{ $kod }}-metin">Belge metni</label>
                            <textarea
                                id="sablon-{{ $kod }}-metin"
                                name="sablonlar[{{ $kod }}][metin]"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                                required
                            >{{ old('sablonlar.'.$kod.'.metin', $sablon['metin']) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="sablon-{{ $kod }}-alt-metin">Alt metin</label>
                            <textarea
                                id="sablon-{{ $kod }}-alt-metin"
                                name="sablonlar[{{ $kod }}][alt_metin]"
                                class="form-control"
                                rows="2"
                                maxlength="1000"
                                placeholder="Örn. Alan: :alan · Merkez: :merkez · Kurs dönemi: :donem"
                            >{{ old('sablonlar.'.$kod.'.alt_metin', $sablon['alt_metin']) }}</textarea>
                            <p class="form-hint">Belge metninin altında görünür. Boş bırakırsanız alt metin yazılmaz.</p>
                        </div>

                        <div class="sertifika-imza-ayarlar">
                            <div class="form-group">
                                <label class="switch-label" for="sablon-{{ $kod }}-belge-no">
                                    <input
                                        type="checkbox"
                                        id="sablon-{{ $kod }}-belge-no"
                                        name="sablonlar[{{ $kod }}][belge_no_yazdir]"
                                        value="1"
                                        @checked(old('sablonlar.'.$kod.'.belge_no_yazdir', $sablon['belge_no_yazdir'] ?? true))
                                    >
                                    Belge no yazdır
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="switch-label" for="sablon-{{ $kod }}-tarih">
                                    <input
                                        type="checkbox"
                                        id="sablon-{{ $kod }}-tarih"
                                        name="sablonlar[{{ $kod }}][tarih_yazdir]"
                                        value="1"
                                        @checked(old('sablonlar.'.$kod.'.tarih_yazdir', $sablon['tarih_yazdir'] ?? true))
                                    >
                                    Tarih yazdır
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="switch-label" for="sablon-{{ $kod }}-egitmen-imza">
                                    <input
                                        type="checkbox"
                                        id="sablon-{{ $kod }}-egitmen-imza"
                                        name="sablonlar[{{ $kod }}][egitmen_imzasi]"
                                        value="1"
                                        @checked(old('sablonlar.'.$kod.'.egitmen_imzasi', $sablon['egitmen_imzasi'] ?? true))
                                    >
                                    Eğitmen imzası
                                </label>
                                <p class="form-hint">İşaretlenirse solda ad soyad + “Eğitmen” unvanı görünür.</p>
                            </div>

                            <div class="form-group">
                                <label class="switch-label" for="sablon-{{ $kod }}-diger-imza">
                                    <input
                                        type="checkbox"
                                        id="sablon-{{ $kod }}-diger-imza"
                                        name="sablonlar[{{ $kod }}][diger_imzaci]"
                                        value="1"
                                        @checked(old('sablonlar.'.$kod.'.diger_imzaci', $sablon['diger_imzaci'] ?? false))
                                        data-diger-imzaci-toggle
                                    >
                                    Diğer imzacı
                                </label>
                                <p class="form-hint">İşaretlenirse sağ imza alanı bu kişiye göre yazılır.</p>
                            </div>

                            <div
                                class="sertifika-diger-imzaci-fields form-grid form-grid-2"
                                data-diger-imzaci-fields
                                @if (! old('sablonlar.'.$kod.'.diger_imzaci', $sablon['diger_imzaci'] ?? false)) hidden @endif
                            >
                                <div class="form-group">
                                    <label for="sablon-{{ $kod }}-diger-unvan">Unvan</label>
                                    <input
                                        type="text"
                                        id="sablon-{{ $kod }}-diger-unvan"
                                        name="sablonlar[{{ $kod }}][diger_imzaci_unvan]"
                                        class="form-control"
                                        value="{{ old('sablonlar.'.$kod.'.diger_imzaci_unvan', $sablon['diger_imzaci_unvan'] ?? '') }}"
                                        maxlength="120"
                                        placeholder="Örn. Merkez Müdürü"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="sablon-{{ $kod }}-diger-ad">Ad soyad</label>
                                    <input
                                        type="text"
                                        id="sablon-{{ $kod }}-diger-ad"
                                        name="sablonlar[{{ $kod }}][diger_imzaci_ad_soyad]"
                                        class="form-control"
                                        value="{{ old('sablonlar.'.$kod.'.diger_imzaci_ad_soyad', $sablon['diger_imzaci_ad_soyad'] ?? '') }}"
                                        maxlength="150"
                                        placeholder="Örn. Ayşe Yılmaz"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sablon-{{ $kod }}-arka-plan">Şablon arka planı</label>
                            <input
                                type="file"
                                id="sablon-{{ $kod }}-arka-plan"
                                name="sablonlar[{{ $kod }}][arka_plan]"
                                class="form-control"
                                accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                                data-sertifika-preview-input
                            >
                            <p class="form-hint">PNG, JPG, SVG veya WEBP. Yatay A4 önerilir. Boş bırakırsanız mevcut şablon korunur.</p>
                            @if ($sablon['arka_plan_ozel'])
                                <label class="sertifika-reset-check">
                                    <input type="checkbox" name="arka_plan_kaldir[]" value="{{ $kod }}">
                                    Yüklenen şablonu kaldır, varsayılana dön
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="sertifika-ayarlar-preview">
                        <div class="sertifika-preview-frame">
                            @if (! empty($sablon['arka_plan_url']))
                                <img
                                    src="{{ $sablon['arka_plan_url'] }}"
                                    alt="{{ $sablon['etiket'] }} şablon önizleme"
                                    data-sertifika-preview-img
                                >
                            @else
                                <div class="sertifika-preview-empty" data-sertifika-preview-empty>Önizleme yok</div>
                                <img src="" alt="" hidden data-sertifika-preview-img>
                            @endif
                        </div>
                        <p class="sertifika-preview-caption">{{ $sablon['arka_plan_adi'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach

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
