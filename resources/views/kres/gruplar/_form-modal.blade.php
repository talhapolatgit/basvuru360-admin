<div class="confirm-modal" id="kres-grup-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-grup-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kres-grup-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Grup</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('kres.gruplar.store') }}" class="kres-grup-form" data-create-url="{{ route('kres.gruplar.store') }}">
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="kres-grup-form-ad">Grup Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="kres-grup-form-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. 3-4 Yaş"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kres-grup-form-okul-id">Okul <span class="req">*</span></label>
                    <select id="kres-grup-form-okul-id" name="okul_id" class="form-control" data-field="okulId" required>
                        <option value="">Okul seçin</option>
                        @foreach ($okullar as $okul)
                            <option value="{{ $okul->id }}">{{ $okul->ad }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="kres-grup-form-donem-id">Dönem <span class="req">*</span></label>
                    <select id="kres-grup-form-donem-id" name="donem_id" class="form-control" data-field="donemId" required>
                        <option value="">Dönem seçin</option>
                        @foreach ($donemler as $donem)
                            <option value="{{ $donem->id }}">{{ $donem->ad }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="kres-grup-form-min-yas">Min. yaş</label>
                    <input type="number" id="kres-grup-form-min-yas" name="min_yas" class="form-control" data-field="minYas" min="0" max="18">
                </div>

                <div class="form-group">
                    <label for="kres-grup-form-max-yas">Maks. yaş</label>
                    <input type="number" id="kres-grup-form-max-yas" name="max_yas" class="form-control" data-field="maxYas" min="0" max="18">
                </div>

                <div class="form-group">
                    <label for="kres-grup-form-kontenjan">Kontenjan <span class="req">*</span></label>
                    <input type="number" id="kres-grup-form-kontenjan" name="kontenjan" class="form-control" data-field="kontenjan" min="0" max="500" value="20" required>
                </div>

                <div class="form-group form-group-switch">
                    <label class="switch-label" for="kres-grup-form-aktif">
                        <input type="checkbox" id="kres-grup-form-aktif" name="aktif" value="1" data-field="aktif" checked>
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
