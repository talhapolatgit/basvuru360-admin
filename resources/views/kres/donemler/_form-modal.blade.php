<div class="confirm-modal" id="kres-donem-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-donem-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kres-donem-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Dönem</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form
            method="POST"
            action="{{ route('kres.donemler.store') }}"
            class="kres-donem-form"
            data-create-url="{{ route('kres.donemler.store') }}"
            data-aktif-donemler='@json($aktifDonemler ?? [])'
        >
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="kres-donem-form-ad">Dönem Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="kres-donem-form-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. 2025-2026"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kres-donem-form-baslangic">Başlangıç</label>
                    <input type="date" id="kres-donem-form-baslangic" name="baslangic" class="form-control" data-field="baslangic">
                </div>

                <div class="form-group">
                    <label for="kres-donem-form-bitis">Bitiş</label>
                    <input type="date" id="kres-donem-form-bitis" name="bitis" class="form-control" data-field="bitis">
                </div>

                <div class="form-group form-group-switch kres-donem-switchler">
                    <label class="switch-label" for="kres-donem-form-aktif">
                        <input type="checkbox" id="kres-donem-form-aktif" name="aktif" value="1" data-field="aktif" checked>
                        <span>Aktif</span>
                    </label>
                    <label class="switch-label" for="kres-donem-form-yayinla">
                        <input type="checkbox" id="kres-donem-form-yayinla" name="yayinla" value="1" data-field="yayinla">
                        <span>Yayınla</span>
                    </label>
                </div>
                <p class="form-hint">Portalda yayınlayıp başvuru almak için dönem aktif olmalı ve Yayınla seçilmelidir.</p>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-entity-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div class="confirm-modal" id="kres-donem-aktif-confirm" hidden>
    <div class="confirm-modal-backdrop" data-aktif-confirm-cancel></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-donem-aktif-confirm-title">
        <div class="confirm-modal-header">
            <h3 id="kres-donem-aktif-confirm-title" class="confirm-modal-title">Aktif dönem değişecek</h3>
            <button type="button" class="confirm-modal-x" data-aktif-confirm-cancel aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-aktif-confirm-message style="margin:0;">Yalnızca bir dönem aktif olabilir.</p>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-aktif-confirm-cancel>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-aktif-confirm-ok>Onayla</button>
        </div>
    </div>
</div>
