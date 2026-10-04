<div class="confirm-modal" id="soru-formu-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="soru-formu-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="soru-formu-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Form</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form
            method="POST"
            action="{{ route('soru-formlari.store') }}"
            class="soru-formu-meta-form"
            data-create-url="{{ route('soru-formlari.store') }}"
        >
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="soru-formu-ad">Form Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="soru-formu-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. Müzik Kursları Ön Bilgi Formu"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="soru-formu-aciklama">Açıklama</label>
                    <textarea
                        id="soru-formu-aciklama"
                        name="aciklama"
                        class="form-control"
                        data-field="aciklama"
                        rows="3"
                        placeholder="Başvuru sahibine gösterilecek kısa açıklama"
                    ></textarea>
                </div>

                <div class="form-group form-group-switch">
                    <label class="switch-label" for="soru-formu-aktif">
                        <input type="checkbox" id="soru-formu-aktif" name="aktif" value="1" data-field="aktif" checked>
                        <span>Aktif</span>
                    </label>
                    <p class="form-hint">Pasif formlar seçildikleri kurs, etkinlik veya dönemde portalda gösterilmez.</p>
                </div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-entity-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
            </div>
        </form>
    </div>
</div>
