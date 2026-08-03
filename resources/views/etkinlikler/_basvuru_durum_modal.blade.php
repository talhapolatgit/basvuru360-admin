@yetki('etkinlik_basvuru.guncelle')
<div
    class="confirm-modal"
    id="etkinlik-basvuru-durum-modal"
    hidden
    data-sms-onay-ayar="{{ $smsBasvuruOnayAyar ?? 'istege_bagli' }}"
    data-sms-iptal-ayar="{{ $smsBasvuruIptalAyar ?? 'istege_bagli' }}"
    data-sms-yedek-ayar="{{ $smsBasvuruYedekAyar ?? 'istege_bagli' }}"
    data-eposta-onay-ayar="{{ $epostaBasvuruOnayAyar ?? 'istege_bagli' }}"
    data-eposta-iptal-ayar="{{ $epostaBasvuruIptalAyar ?? 'istege_bagli' }}"
    data-eposta-yedek-ayar="{{ $epostaBasvuruYedekAyar ?? 'istege_bagli' }}"
>
    <div class="confirm-modal-backdrop" data-etkinlik-durum-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="etkinlik-basvuru-durum-modal-title">
        <div class="confirm-modal-header">
            <h3 id="etkinlik-basvuru-durum-modal-title" class="confirm-modal-title">Başvuru Durumu</h3>
            <button type="button" class="confirm-modal-x" data-etkinlik-durum-close aria-label="Kapat">&times;</button>
        </div>
        <form data-etkinlik-durum-form>
            <div class="confirm-modal-body">
                <p data-etkinlik-durum-text style="margin-bottom:14px;">Başvuru durumunu seçin.</p>
                <div class="form-group">
                    <label for="etkinlik-basvuru-durum-select">Durum</label>
                    <select id="etkinlik-basvuru-durum-select" name="durum_kod" class="form-control" data-etkinlik-durum-select>
                        @foreach ($basvuruDurumlari as $filtreDurum)
                            <option value="{{ $filtreDurum->kod }}">{{ $filtreDurum->ad }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                        Listede yalnızca aktif etkinlik başvuru durumları görünür.
                    </p>
                </div>
                <div class="form-group is-hidden" data-etkinlik-durum-gerekce-wrap style="margin-bottom:0;">
                    <label for="etkinlik-basvuru-durum-gerekce">İptal gerekçesi <span class="req">*</span></label>
                    <select id="etkinlik-basvuru-durum-gerekce" name="iptal_gerekce_id" class="form-control" data-etkinlik-durum-gerekce>
                        <option value="">Seçiniz</option>
                        @foreach ($iptalGerekceleri as $gerekce)
                            <option value="{{ $gerekce->id }}">{{ $gerekce->ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form-group-switch is-hidden" data-etkinlik-durum-sms-wrap style="margin-bottom:0; margin-top:14px;">
                    <label class="switch-label" for="etkinlik-basvuru-durum-sms">
                        <input type="checkbox" id="etkinlik-basvuru-durum-sms" name="sms_gonder" data-etkinlik-durum-sms value="1">
                        <span>SMS Gönder</span>
                    </label>
                </div>
                <div class="form-group form-group-switch is-hidden" data-etkinlik-durum-eposta-wrap style="margin-bottom:0; margin-top:10px;">
                    <label class="switch-label" for="etkinlik-basvuru-durum-eposta">
                        <input type="checkbox" id="etkinlik-basvuru-durum-eposta" name="eposta_gonder" data-etkinlik-durum-eposta value="1">
                        <span>E-posta Gönder</span>
                    </label>
                </div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-etkinlik-durum-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endyetki
