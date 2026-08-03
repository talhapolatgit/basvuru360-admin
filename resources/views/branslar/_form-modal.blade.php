<div class="confirm-modal" id="brans-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="brans-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="brans-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Branş</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('branslar.store') }}" class="brans-form" data-create-url="{{ route('branslar.store') }}">
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="brans-form-ad">Branş Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="brans-form-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. Bilgisayar İşletmenliği"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="brans-form-alan-id">Alan <span class="req">*</span></label>
                    <select id="brans-form-alan-id" name="alan_id" class="form-control" data-field="alanId" required>
                        <option value="">Alan seçin</option>
                        @foreach ($alanlar as $alan)
                            <option value="{{ $alan->id }}">{{ $alan->ad }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group form-group-switch">
                    <label class="switch-label" for="brans-form-aktif">
                        <input type="checkbox" id="brans-form-aktif" name="aktif" value="1" data-field="aktif" checked>
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
