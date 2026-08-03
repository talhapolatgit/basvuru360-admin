@php
    $basvuruEvrakTipOptions = \App\Models\EvrakTipi::query()
        ->where('aktif', true)
        ->orderBy('ad')
        ->get(['id', 'ad', 'aciklama'])
        ->map(fn ($tip) => [
            'id' => $tip->id,
            'ad' => $tip->ad,
            'aciklama' => $tip->aciklama,
        ])
        ->values();
@endphp
<div
    class="confirm-modal"
    id="basvuru-evraklar-modal"
    hidden
    data-evrak-tipleri='@json($basvuruEvrakTipOptions, JSON_UNESCAPED_UNICODE)'
    data-can-upload="{{ auth()->user()?->hasYetki('basvuru.evrak_yukle') ? '1' : '0' }}"
>
    <div class="confirm-modal-backdrop" data-basvuru-evraklar-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg basvuru-evraklar-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="basvuru-evraklar-modal-title">
        <div class="confirm-modal-header basvuru-evraklar-modal-header">
            <div>
                <h3 id="basvuru-evraklar-modal-title" class="confirm-modal-title">Başvuru Evrakları</h3>
                <p class="basvuru-evraklar-modal-subtitle" data-basvuru-evraklar-subtitle>Yüklenen evraklar listeleniyor.</p>
            </div>
            <div class="basvuru-evraklar-header-actions">
                @yetki('basvuru.evrak_yukle')
                    <button
                        type="button"
                        class="basvuru-evraklar-upload-toggle"
                        data-basvuru-evrak-upload-toggle
                        title="Yeni evrak yükle"
                        aria-label="Yeni evrak yükle"
                        aria-expanded="false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5v14"/>
                            <path d="M5 12h14"/>
                        </svg>
                    </button>
                @endyetki
                <button type="button" class="confirm-modal-x" data-basvuru-evraklar-close aria-label="Kapat">&times;</button>
            </div>
        </div>
        <div class="confirm-modal-body basvuru-evraklar-modal-body">
            <div class="basvuru-evrak-upload-panel" data-basvuru-evrak-upload-panel hidden>
                <div class="basvuru-evrak-upload-panel-head">
                    <strong>Yeni Evrak Yükle</strong>
                    <span>Evrak tipini seçip PDF veya JPG/PNG dosyası ekleyin (max 5 MB).</span>
                </div>
                <div class="basvuru-evrak-upload-grid">
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-evrak-tip-select">Evrak tipi <span class="req">*</span></label>
                        <select id="basvuru-evrak-tip-select" class="form-control" data-basvuru-evrak-tip-select>
                            <option value="">Seçiniz</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="basvuru-evrak-dosya">Dosya <span class="req">*</span></label>
                        <input
                            type="file"
                            id="basvuru-evrak-dosya"
                            class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                            data-basvuru-evrak-dosya
                        >
                    </div>
                </div>
                <div class="basvuru-evrak-upload-actions">
                    <button type="button" class="btn btn-secondary" data-basvuru-evrak-upload-cancel>Vazgeç</button>
                    <button type="button" class="btn btn-primary" data-basvuru-evrak-upload-submit>Yükle</button>
                </div>
            </div>
            <div class="basvuru-evraklar-summary" data-basvuru-evraklar-summary></div>
            <div class="basvuru-evraklar-list" data-basvuru-evraklar-list></div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-evraklar-close>Kapat</button>
        </div>
    </div>
</div>
