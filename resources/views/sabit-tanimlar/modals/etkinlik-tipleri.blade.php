<div class="confirm-modal" id="etkinlik-tipleri-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="etkinlik-tipleri-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="etkinlik-tipleri-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Etkinlik Tipi</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('sabit-tanimlar.etkinlik-tipleri.store') }}" class="etkinlik-tipleri-form" data-create-url="{{ route('sabit-tanimlar.etkinlik-tipleri.store') }}">
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="etkinlik-tipleri-form-ad">Ad <span class="req">*</span></label>
                    <input type="text" id="etkinlik-tipleri-form-ad" name="ad" class="form-control" data-field="ad" placeholder="Örn. Seminer" required maxlength="150">
                </div>
                <div class="form-group form-group-switch">
                    <label class="switch-label" for="etkinlik-tipleri-form-aktif">
                        <input type="checkbox" id="etkinlik-tipleri-form-aktif" name="aktif" value="1" data-field="aktif" checked>
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
