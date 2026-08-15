<div class="confirm-modal" id="kres-soru-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-soru-onizleme-close></div>
    <div class="confirm-modal-dialog kres-soru-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-soru-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="kres-soru-onizleme-title" class="confirm-modal-title" data-soru-onizleme-title>{{ isset($form) ? $form->ad.' — Önizleme' : 'Form önizleme' }}</h3>
            <button type="button" class="confirm-modal-x" data-soru-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body" data-soru-onizleme-govde>
            @isset($form)
                @include('kres.soru-formlari._preview')
            @endisset
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-soru-onizleme-close>Kapat</button>
        </div>
    </div>
</div>
