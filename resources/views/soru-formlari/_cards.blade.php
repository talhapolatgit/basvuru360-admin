@foreach ($form->sorular as $soru)
    @include('soru-formlari._card', ['form' => $form, 'soru' => $soru, 'index' => $loop->iteration])
@endforeach
<div class="empty-state" data-soru-empty @if ($form->sorular->isNotEmpty()) hidden @endif>
    <div class="empty-state-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
    </div>
    <div class="empty-state-title">Henüz soru yok</div>
    <p class="empty-state-text">Başvuru formuna soru eklemek için Soru Ekle’ye tıklayın. Önce soru tipini seçin.</p>
</div>
