@php
    $statusSiniflari = $statusSiniflari ?? \App\Http\Controllers\SabitTanimController::STATUS_SINIFLARI;
@endphp
<div class="confirm-modal" id="etkinlik-basvuru-durumlari-form-modal" hidden>
    <div class="confirm-modal-backdrop" data-entity-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="etkinlik-basvuru-durumlari-form-modal-title">
        <div class="confirm-modal-header">
            <h3 id="etkinlik-basvuru-durumlari-form-modal-title" class="confirm-modal-title" data-entity-modal-title>Yeni Etkinlik Başvuru Durumu</h3>
            <button type="button" class="confirm-modal-x" data-entity-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('sabit-tanimlar.etkinlik-basvuru-durumlari.store') }}" class="etkinlik-basvuru-durumlari-form" data-create-url="{{ route('sabit-tanimlar.etkinlik-basvuru-durumlari.store') }}">
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="etkinlik-basvuru-durumlari-form-kod">Kod <span class="req">*</span></label>
                    <input type="text" id="etkinlik-basvuru-durumlari-form-kod" name="kod" class="form-control" data-field="kod" data-readonly-on-edit placeholder="örn. kesin_kayit" required maxlength="50" pattern="[a-z0-9_]+">
                    <p class="field-hint">Küçük harf, rakam ve alt çizgi. Düzenlemede değiştirilemez.</p>
                </div>
                <div class="form-group">
                    <label for="etkinlik-basvuru-durumlari-form-ad">Ad <span class="req">*</span></label>
                    <input type="text" id="etkinlik-basvuru-durumlari-form-ad" name="ad" class="form-control" data-field="ad" required maxlength="150">
                </div>
                <div class="form-group">
                    <label for="etkinlik-basvuru-durumlari-form-aciklama">Açıklama</label>
                    <textarea id="etkinlik-basvuru-durumlari-form-aciklama" name="aciklama" class="form-control" data-field="aciklama" rows="2" maxlength="500"></textarea>
                </div>
                <div class="form-group">
                    <label for="etkinlik-basvuru-durumlari-form-status">Görünüm Sınıfı <span class="req">*</span></label>
                    <select id="etkinlik-basvuru-durumlari-form-status" name="status_sinifi" class="form-control" data-field="statusSinifi" required>
                        @foreach ($statusSiniflari as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="etkinlik-basvuru-durumlari-form-sira">Sıra</label>
                    <input type="number" id="etkinlik-basvuru-durumlari-form-sira" name="sira" class="form-control" data-field="sira" min="0" max="9999" value="0">
                </div>
                <div class="form-group form-group-switch">
                    <label class="switch-label" for="etkinlik-basvuru-durumlari-form-aktif">
                        <input type="checkbox" id="etkinlik-basvuru-durumlari-form-aktif" name="aktif" value="1" data-field="aktif" checked>
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
