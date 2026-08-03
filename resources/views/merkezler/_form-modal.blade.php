<div class="confirm-modal" id="merkez-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="merkez-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="merkez-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Merkez</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('merkezler.store') }}" class="merkez-form" data-create-url="{{ route('merkezler.store') }}">
            @csrf
            <script type="application/json" data-ilce-map>@json($ilcelerByIl ?? [])</script>
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="merkez-form-ad">Merkez Adı <span class="req">*</span></label>
                    <input
                        type="text"
                        id="merkez-form-ad"
                        name="ad"
                        class="form-control"
                        data-field="ad"
                        placeholder="Örn. Halk Eğitim Merkezi"
                        required
                    >
                </div>

                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label for="merkez-form-il">İl</label>
                        <select
                            id="merkez-form-il"
                            name="il"
                            class="form-control"
                            data-field="il"
                            data-merkez-il
                        >
                            <option value="">Seçiniz</option>
                            @foreach (($iller ?? []) as $il)
                                <option value="{{ $il->ad }}">{{ $il->ad }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="merkez-form-ilce">İlçe</label>
                        <select
                            id="merkez-form-ilce"
                            name="ilce"
                            class="form-control"
                            data-field="ilce"
                            data-merkez-ilce
                            disabled
                        >
                            <option value="">Seçiniz</option>
                        </select>
                        <p class="field-hint">İkamet koşulu bu ilçeye göre kontrol edilir.</p>
                    </div>
                </div>

                <div class="form-group form-group-switch">
                    <label class="switch-label" for="merkez-form-aktif">
                        <input type="checkbox" id="merkez-form-aktif" name="aktif" value="1" data-field="aktif" checked>
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
