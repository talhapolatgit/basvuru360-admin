<div class="confirm-modal" id="kurumlar-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kurumlar-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kurumlar-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Kurum</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('sabit-tanimlar.kurumlar.store') }}" class="kurumlar-form" data-create-url="{{ route('sabit-tanimlar.kurumlar.store') }}">
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="kurumlar-form-ad">Ad <span class="req">*</span></label>
                    <input type="text" id="kurumlar-form-ad" name="ad" class="form-control" data-field="ad" placeholder="Örn. İSMEK" required maxlength="150">
                </div>
                <div class="form-group">
                    <label for="kurumlar-form-sira">Sıra</label>
                    <input type="number" id="kurumlar-form-sira" name="sira" class="form-control" data-field="sira" min="0" max="9999" value="0">
                </div>
                <div class="form-group form-group-switch">
                    <label class="switch-label" for="kurumlar-form-aktif">
                        <input type="checkbox" id="kurumlar-form-aktif" name="aktif" value="1" data-field="aktif" checked>
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
