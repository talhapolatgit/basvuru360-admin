@yetki('basvuru.yedek_sira_guncelle')
<div class="confirm-modal" id="kurs-yedek-sira-modal" hidden>
    <div class="confirm-modal-backdrop" data-yedek-sira-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="kurs-yedek-sira-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kurs-yedek-sira-modal-title" class="confirm-modal-title">Yedek Sıra Güncelle</h3>
            <button type="button" class="confirm-modal-x" data-yedek-sira-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p class="card-section-desc" style="margin-bottom:14px;" data-yedek-sira-desc>
                Yedekteki başvuruları sürükleyerek sıralayın.
            </p>
            <div class="yedek-sira-list" data-yedek-sira-list>
                <div class="empty-state" data-yedek-sira-empty style="padding:24px;">
                    <p class="empty-state-text">Yedek listesinde başvuru yok.</p>
                </div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-yedek-sira-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-yedek-sira-save>Kaydet</button>
        </div>
    </div>
</div>
@endyetki
