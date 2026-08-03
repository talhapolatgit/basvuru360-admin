@php
    $iptalGerekceleri = $iptalGerekceleri ?? collect();
@endphp

{{-- İptal gerekçesi modalı (etkinlik başvuru detay) --}}
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

{{-- Veli başvurusu modalı (etkinlik başvuru detay) --}}
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
