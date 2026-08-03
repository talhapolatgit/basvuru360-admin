{{-- Ortak başvuru durum / başarı / başlama (+ detay ekleri) modalları --}}
@php
    $iptalGerekceleri = $iptalGerekceleri ?? collect();
    $basariDurumlari = $basariDurumlari ?? collect();
@endphp

{{-- Başvuru durum modalı --}}
<div
    class="confirm-modal"
    id="basvuru-durum-modal"
    hidden
    data-sms-onay-ayar="{{ $smsBasvuruOnayAyar ?? 'istege_bagli' }}"
    data-sms-iptal-ayar="{{ $smsBasvuruIptalAyar ?? 'istege_bagli' }}"
    data-sms-yedek-ayar="{{ $smsBasvuruYedekAyar ?? 'istege_bagli' }}"
    data-eposta-onay-ayar="{{ $epostaBasvuruOnayAyar ?? 'istege_bagli' }}"
    data-eposta-iptal-ayar="{{ $epostaBasvuruIptalAyar ?? 'istege_bagli' }}"
    data-eposta-yedek-ayar="{{ $epostaBasvuruYedekAyar ?? 'istege_bagli' }}"
>
    <div class="confirm-modal-backdrop" data-basvuru-durum-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="basvuru-durum-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-durum-modal-title" class="confirm-modal-title">Başvuru Durumu</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-durum-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-durum-text style="margin-bottom:14px;">Başvuru durumunu seçin.</p>
            <div class="form-group">
                <label for="basvuru-durum-select">Durum</label>
                <select id="basvuru-durum-select" class="form-control" data-basvuru-durum-select>
                    <option value="">—</option>
                    <option value="kesin_kayit">Onayla</option>
                    <option value="iptal">İptal Et</option>
                    <option value="yedek">Yedeğe Al</option>
                </select>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    Seçim yapmadan kaydederseniz durum Onay Bekliyor olarak güncellenir.
                </p>
                <p class="form-hint" data-basvuru-durum-kilit-hint hidden style="margin-top:8px; font-size:12px; color:#f64e60;">
                    Kesin kaydı iptal etmek için önce başarı durumunu güncelleyiniz.
                </p>
            </div>
            <div class="form-group" data-basvuru-durum-gerekce-wrap hidden style="margin-bottom:0;">
                <label for="basvuru-durum-gerekce">İptal gerekçesi <span class="req">*</span></label>
                <select id="basvuru-durum-gerekce" class="form-control" data-basvuru-durum-gerekce>
                    <option value="">Seçiniz</option>
                    @foreach ($iptalGerekceleri as $gerekce)
                        <option value="{{ $gerekce->id }}">{{ $gerekce->ad }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" data-basvuru-durum-baslama-wrap hidden style="margin-bottom:0;">
                <label for="basvuru-durum-baslama-input">Kursa başlama tarihi <span class="req">*</span></label>
                <input type="date" id="basvuru-durum-baslama-input" class="form-control" data-basvuru-durum-baslama-input>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    Kursa geç başlayacaksa kursa başlama tarihini değiştiriniz. Yoklama alırken bu tarih dikkate alınır.
                </p>
            </div>
            <div class="form-group form-group-switch" data-basvuru-durum-sms-wrap hidden style="margin-bottom:0; margin-top:14px;">
                <label class="switch-label" for="basvuru-durum-sms">
                    <input type="checkbox" id="basvuru-durum-sms" data-basvuru-durum-sms value="1">
                    <span>SMS Gönder</span>
                </label>
            </div>
            <div class="form-group form-group-switch" data-basvuru-durum-eposta-wrap hidden style="margin-bottom:0; margin-top:10px;">
                <label class="switch-label" for="basvuru-durum-eposta">
                    <input type="checkbox" id="basvuru-durum-eposta" data-basvuru-durum-eposta value="1">
                    <span>E-posta Gönder</span>
                </label>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-durum-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-durum-confirm>Kaydet</button>
        </div>
    </div>
</div>

{{-- Başarı durumu modalı --}}
<div class="confirm-modal" id="basvuru-basari-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-basari-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="basvuru-basari-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-basari-modal-title" class="confirm-modal-title">Başarı Durumu</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-basari-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-basari-text style="margin-bottom:14px;">Başarı durumunu seçin.</p>
            <div class="form-group" style="margin:0;">
                <label for="basvuru-basari-select">Başarı durumu</label>
                <select id="basvuru-basari-select" class="form-control" data-basvuru-basari-select>
                    <option value="">Belirtilmedi</option>
                    @foreach ($basariDurumlari as $basariDurum)
                        <option
                            value="{{ $basariDurum->id }}"
                            data-kod="{{ $basariDurum->kod }}"
                            @if (in_array($basariDurum->kod, ['sertifika_hak_etti', 'katilim_belgesi_hak_etti'], true))
                                data-requires-belge="1"
                            @endif
                        >{{ $basariDurum->ad }}</option>
                    @endforeach
                </select>
                <p class="form-hint" data-basvuru-basari-hint hidden style="margin-top:8px; font-size:12px; color:#7e8299;">
                    Başarı durumu yalnızca başvuru durumu Kesin Kayıt olan kayıtlar için güncellenebilir. Sertifika / katılım belgesi için kurs durumu ayrıca Tamamlanan olmalıdır.
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-basari-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-basari-confirm>Kaydet</button>
        </div>
    </div>
</div>

{{-- Kursa başlama tarihi modalı --}}
<div class="confirm-modal" id="basvuru-baslama-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-baslama-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="basvuru-baslama-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-baslama-modal-title" class="confirm-modal-title">Kursa Başlama Tarihi</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-baslama-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-baslama-text style="margin-bottom:14px;">Kursa başlama tarihini seçin.</p>
            <div class="form-group" style="margin:0;">
                <label for="basvuru-baslama-input">Kursa başlama tarihi <span class="req">*</span></label>
                <input
                    type="date"
                    id="basvuru-baslama-input"
                    class="form-control"
                    data-basvuru-baslama-input
                    @if (isset($kurs))
                        min="{{ $kurs->kurs_baslama_tarihi?->format('Y-m-d') }}"
                        max="{{ $kurs->kurs_bitis_tarihi?->format('Y-m-d') }}"
                    @endif
                >
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-baslama-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-baslama-confirm>Kaydet</button>
        </div>
    </div>
</div>

{{-- İptal gerekçesi modalı (başvuru detay) --}}
<div class="confirm-modal" id="basvuru-iptal-gerekce-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-iptal-gerekce-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="basvuru-iptal-gerekce-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-iptal-gerekce-modal-title" class="confirm-modal-title">İptal Gerekçesi</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-iptal-gerekce-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-iptal-gerekce-text style="margin-bottom:14px;">İptal gerekçesini seçin.</p>
            <p class="form-hint" data-basvuru-iptal-gerekce-hint hidden style="margin-bottom:14px; color:#f64e60;">
                İptal gerekçesi yalnızca başvuru durumu İptal olan kayıtlarda güncellenebilir.
            </p>
            <div class="form-group" style="margin:0;">
                <label for="basvuru-iptal-gerekce-select">İptal gerekçesi <span class="req">*</span></label>
                <select id="basvuru-iptal-gerekce-select" class="form-control" data-basvuru-iptal-gerekce-select>
                    <option value="">Seçiniz</option>
                    @foreach ($iptalGerekceleri as $gerekce)
                        <option value="{{ $gerekce->id }}">{{ $gerekce->ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-iptal-gerekce-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-iptal-gerekce-confirm>Kaydet</button>
        </div>
    </div>
</div>

{{-- Veli başvurusu modalı (başvuru detay) --}}
<div class="confirm-modal" id="basvuru-veli-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-veli-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="basvuru-veli-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-veli-modal-title" class="confirm-modal-title">Veli Başvurusu</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-veli-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-veli-text style="margin-bottom:14px;">Bu başvurunun veli üzerinden yapılıp yapılmadığını seçin.</p>
            <div class="form-group">
                <label for="basvuru-veli-select">Veli başvurusu <span class="req">*</span></label>
                <select id="basvuru-veli-select" class="form-control" data-basvuru-veli-select>
                    <option value="0">Hayır</option>
                    <option value="1">Evet</option>
                </select>
                <p class="form-hint" data-basvuru-veli-kucuk-hint hidden style="margin-top:8px; font-size:12px; color:#f64e60;">
                    Katılımcı 18 yaşından küçük olduğu için veli başvurusu kaldırılamaz.
                </p>
            </div>
            <div class="basvuru-veli-fields" data-basvuru-veli-fields hidden>
                <div class="basvuru-evrak-upload-panel-head" style="margin-bottom:12px;">
                    <strong>Veli bilgileri</strong>
                    <span>Veli başvurusu için kişi bilgilerini girin. Mevcut TC ile eşleşen kayıt varsa güncellenir.</span>
                </div>
                <div class="basvuru-evrak-upload-grid">
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-veli-tc">TC Kimlik No <span class="req">*</span></label>
                        <input type="text" id="basvuru-veli-tc" class="form-control" maxlength="11" inputmode="numeric" data-basvuru-veli-tc>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-veli-dogum">Doğum Tarihi <span class="req">*</span></label>
                        <input type="date" id="basvuru-veli-dogum" class="form-control" data-basvuru-veli-dogum>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-veli-ad">Ad <span class="req">*</span></label>
                        <input type="text" id="basvuru-veli-ad" class="form-control" data-basvuru-veli-ad>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-veli-soyad">Soyad <span class="req">*</span></label>
                        <input type="text" id="basvuru-veli-soyad" class="form-control" data-basvuru-veli-soyad>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-veli-telefon">Telefon</label>
                        <input type="text" id="basvuru-veli-telefon" class="form-control" data-basvuru-veli-telefon>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-veli-email">E-posta</label>
                        <input type="email" id="basvuru-veli-email" class="form-control" data-basvuru-veli-email>
                    </div>
                </div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-veli-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-veli-confirm>Kaydet</button>
        </div>
    </div>
</div>
