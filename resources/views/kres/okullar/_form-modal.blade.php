<div class="confirm-modal" id="kres-okul-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-okul-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kres-okul-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Okul</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('kres.okullar.store') }}" class="kres-okul-form" data-create-url="{{ route('kres.okullar.store') }}">
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="kres-okul-form-ad">Okul Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="kres-okul-form-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. Merkez Kreş"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kres-okul-form-adres">Adres</label>
                    <textarea id="kres-okul-form-adres" name="adres" class="form-control" rows="2" data-field="adres" maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="kres-okul-form-telefon">Telefon</label>
                    <input type="text" id="kres-okul-form-telefon" name="telefon" class="form-control" data-field="telefon" maxlength="30">
                </div>

                <div class="form-group form-group-switch">
                    <label class="switch-label" for="kres-okul-form-aktif">
                        <input type="checkbox" id="kres-okul-form-aktif" name="aktif" value="1" data-field="aktif" checked>
                        <span>Aktif</span>
                    </label>
                </div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-entity-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
            </div>
        </form>
    </div>
</div>
