@extends('layouts.admin')

@section('title', $form->ad)

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kreş Yönetimi · Soru Formları</p>
        <h1 class="page-title">{{ $form->ad }}</h1>
        <p class="page-subtitle">
            {{ $form->donem?->ad ?? 'Dönem yok' }}
            @if ($form->aciklama)
                · {{ $form->aciklama }}
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('kres.soru-formlari.index') }}">Formlara Dön</x-back-button>
        <button type="button" class="btn-back" data-soru-onizle data-onizleme-url="{{ route('kres.soru-formlari.onizleme', $form) }}" data-ad="{{ $form->ad }}">
            <span class="btn-back-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
            <span class="btn-back-text">Önizle</span>
        </button>
        <x-cta-button type="button" icon="plus" data-soru-ekle>Soru Ekle</x-cta-button>
    </div>
</div>

<div
    class="kres-soru-builder"
    data-kres-soru-builder
    data-store-url="{{ route('kres.soru-formlari.sorular.store', $form) }}"
    data-sira-url="{{ route('kres.soru-formlari.sorular.sira', $form) }}"
    data-secenek-tipleri='@json(collect($soruTipleri)->filter->secenekGerekli()->map->value->values())'
>
    <div class="kres-soru-list" data-soru-list>
        @foreach ($form->sorular as $soru)
            @include('kres.soru-formlari._card', ['form' => $form, 'soru' => $soru, 'index' => $loop->iteration])
        @endforeach
        <div class="empty-state" data-soru-empty @if ($form->sorular->isNotEmpty()) hidden @endif>
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </div>
            <div class="empty-state-title">Henüz soru yok</div>
            <p class="empty-state-text">Başvuru formuna soru eklemek için Soru Ekle’ye tıklayın. Önce soru tipini seçin.</p>
        </div>
    </div>
</div>

<div class="confirm-modal" id="kres-soru-modal" hidden>
    <div class="confirm-modal-backdrop" data-soru-modal-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="kres-soru-modal-title">
        <div class="confirm-modal-header">
            <h3 id="kres-soru-modal-title" class="confirm-modal-title" data-soru-modal-title>Yeni Soru</h3>
            <button type="button" class="confirm-modal-x" data-soru-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('kres.soru-formlari.sorular.store', $form) }}" data-soru-form>
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="kres-soru-tip">Soru tipi <span class="req">*</span></label>
                    <select id="kres-soru-tip" name="tip" class="form-control" required data-soru-tip>
                        <option value="">Soru tipi seçin</option>
                        @foreach ($soruTipleri as $tip)
                            <option value="{{ $tip->value }}" data-secenek="{{ $tip->secenekGerekli() ? '1' : '0' }}">{{ $tip->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div data-soru-alanlari hidden>
                    <div class="form-group">
                        <label for="kres-soru-baslik">Soru <span class="req">*</span></label>
                        <input type="text" id="kres-soru-baslik" name="baslik" class="form-control" data-soru-baslik placeholder="Soruyu yazın">
                    </div>

                    <div class="form-group">
                        <label for="kres-soru-aciklama">Yardım metni</label>
                        <input type="text" id="kres-soru-aciklama" name="aciklama" class="form-control" data-soru-aciklama placeholder="İsteğe bağlı açıklama">
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group-switch">
                            <label class="switch-label" for="kres-soru-zorunlu">
                                <input type="checkbox" id="kres-soru-zorunlu" name="zorunlu" value="1" data-soru-zorunlu>
                                <span>Zorunlu</span>
                            </label>
                        </div>
                        <div class="form-group form-group-switch" data-tam-sayi-alani hidden>
                            <label class="switch-label" for="kres-soru-tam-sayi">
                                <input type="checkbox" id="kres-soru-tam-sayi" name="tam_sayi" value="1" data-soru-tam-sayi>
                                <span>Tam sayı</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row" data-sayi-alani hidden>
                        <div class="form-group">
                            <label for="kres-soru-min-deger" data-min-label>Minimum</label>
                            <input type="number" id="kres-soru-min-deger" name="min_deger" class="form-control" data-soru-min-deger step="any" placeholder="İsteğe bağlı">
                        </div>
                        <div class="form-group">
                            <label for="kres-soru-max-deger" data-max-label>Maksimum</label>
                            <input type="number" id="kres-soru-max-deger" name="max_deger" class="form-control" data-soru-max-deger step="any" placeholder="İsteğe bağlı">
                        </div>
                    </div>
                    <p class="form-hint" data-secim-adet-hint hidden>İşaretlenebilecek seçenek adedini belirtir. Boş bırakılırsa sınır uygulanmaz.</p>

                    <div class="form-group" data-secenek-alani hidden>
                        <label>Seçenekler <span class="req">*</span></label>
                        <p class="form-hint">Sırayı oklarla değiştirin. Boş satırlar kaydedilmez.</p>
                        <div class="kres-soru-secenek-list" data-secenek-list></div>
                        <x-back-button type="button" icon="plus" data-secenek-ekle>Seçenek ekle</x-back-button>
                    </div>
                </div>
            </div>
            <div class="confirm-modal-footer">
                <button type="button" class="btn btn-secondary btn-wide" data-soru-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary btn-wide" data-soru-kaydet disabled>Kaydet</button>
            </div>
        </form>
    </div>
</div>

@include('kres.soru-formlari._preview-modal')
@endsection
