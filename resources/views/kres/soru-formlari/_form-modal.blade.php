<div class="confirm-modal" id="kres-soru-formu-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-soru-formu-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kres-soru-formu-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Form</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form
            method="POST"
            action="{{ route('kres.soru-formlari.store') }}"
            class="kres-soru-formu-meta-form"
            data-create-url="{{ route('kres.soru-formlari.store') }}"
            data-formlu-donemler='@json($formluDonemIds ?? [])'
        >
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="kres-soru-formu-donem-id">Dönem <span class="req">*</span></label>
                    <select id="kres-soru-formu-donem-id" name="donem_id" class="form-control" data-field="donemId" required>
                        <option value="">Dönem seçin</option>
                        @foreach ($donemler as $donem)
                            <option value="{{ $donem->id }}">{{ $donem->ad }}{{ $donem->aktif ? ' (aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="kres-soru-formu-ad">Form Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="kres-soru-formu-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. 2025-2026 Başvuru Formu"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kres-soru-formu-aciklama">Açıklama</label>
                    <textarea
                        id="kres-soru-formu-aciklama"
                        name="aciklama"
                        class="form-control"
                        data-field="aciklama"
                        rows="3"
                        placeholder="Başvuru sahibine gösterilecek kısa açıklama"
                    ></textarea>
                </div>

                <div class="form-group form-group-switch">
                    <label class="switch-label" for="kres-soru-formu-aktif">
                        <input type="checkbox" id="kres-soru-formu-aktif" name="aktif" value="1" data-field="aktif" checked>
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
