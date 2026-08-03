{{-- Başvuruya tekli SMS gönder modalı --}}
<div class="confirm-modal" id="etkinlik-basvuru-sms-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-sms-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="etkinlik-basvuru-sms-modal-title">
        <div class="confirm-modal-header">
            <h3 id="etkinlik-basvuru-sms-modal-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-sms-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-sms-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-basvuru-sms-no-telefon hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı telefon numarası bulunamadı.
            </p>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="etkinlik-basvuru-sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-sms-insert="{ad_soyad}"
                            title="İsim değişkeni ekle"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-basvuru-sms-onizle
                            title="Önizleme"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="etkinlik-basvuru-sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-basvuru-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, etkinlik kaydınız onaylandı."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-basvuru-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-sms-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-sms-send>Gönder</button>
        </div>
    </div>
</div>

{{-- Başvuruya tekli e-posta gönder modalı --}}
<div class="confirm-modal" id="etkinlik-basvuru-eposta-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-eposta-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="etkinlik-basvuru-eposta-modal-title">
        <div class="confirm-modal-header">
            <h3 id="etkinlik-basvuru-eposta-modal-title" class="confirm-modal-title">E-Posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-eposta-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-eposta-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-basvuru-eposta-no-email hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı e-posta adresi bulunamadı.
            </p>
            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="etkinlik-basvuru-eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-eposta-insert="{ad_soyad}"
                            data-basvuru-eposta-insert-target="konu"
                            title="İsim değişkeni ekle"
                        >{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="etkinlik-basvuru-eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-basvuru-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="etkinlik-basvuru-eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-eposta-insert="{ad_soyad}"
                            data-basvuru-eposta-insert-target="mesaj"
                            title="İsim değişkeni ekle"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-basvuru-eposta-onizle
                            title="Önizleme"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="etkinlik-basvuru-eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-basvuru-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, başvuru durumunuz güncellendi."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-basvuru-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-eposta-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-eposta-send>Gönder</button>
        </div>
    </div>
</div>
