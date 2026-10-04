@extends('layouts.admin')

@section('title', $form->ad)

@php
    $kullanimlar = collect()
        ->merge($form->kurslar->map(fn ($kurs) => ['etiket' => 'Kurs: '.($kurs->brans?->ad ?? 'Kurs').' ('.$kurs->kurs_no.')', 'url' => route('kurslar.show', $kurs)]))
        ->merge($form->etkinlikler->map(fn ($etkinlik) => ['etiket' => 'Etkinlik: '.$etkinlik->ad, 'url' => route('etkinlikler.show', $etkinlik)]))
        ->merge($form->kresDonemleri->map(fn ($donem) => ['etiket' => 'Kreş dönemi: '.$donem->ad, 'url' => null]));
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Tanımlar · Soru Formları</p>
        <h1 class="page-title">{{ $form->ad }}</h1>
        <p class="page-subtitle">
            {{ $form->aktif ? 'Aktif' : 'Pasif' }}
            @if ($form->aciklama)
                · {{ $form->aciklama }}
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('soru-formlari.index') }}">Formlara Dön</x-back-button>
        <button type="button" class="btn-back" data-soru-onizle data-onizleme-url="{{ route('soru-formlari.onizleme', $form) }}" data-ad="{{ $form->ad }}">
            <span class="btn-back-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
            <span class="btn-back-text">Önizle</span>
        </button>
        <x-cta-button type="button" icon="plus" data-soru-ekle>Soru Ekle</x-cta-button>
    </div>
</div>

<div class="card soru-formu-kullanim">
    <div class="soru-formu-kullanim__baslik">Kullanıldığı yerler</div>
    @if ($kullanimlar->isEmpty())
        <p class="form-hint" style="margin:0;">Bu form henüz bir kursa, etkinliğe veya kreş dönemine bağlanmadı. Kurs veya etkinlik düzenleme ekranındaki "Başvuru Soru Formu" alanından seçebilirsiniz.</p>
    @else
        <div class="soru-formu-kullanim__liste">
            @foreach ($kullanimlar as $kullanim)
                @if ($kullanim['url'])
                    <a href="{{ $kullanim['url'] }}" class="soru-formu-kullanim__etiket">{{ $kullanim['etiket'] }}</a>
                @else
                    <span class="soru-formu-kullanim__etiket">{{ $kullanim['etiket'] }}</span>
                @endif
            @endforeach
        </div>
    @endif
</div>

<div
    class="kres-soru-builder"
    data-soru-builder
    data-store-url="{{ route('soru-formlari.sorular.store', $form) }}"
    data-sira-url="{{ route('soru-formlari.sorular.sira', $form) }}"
    data-secenek-tipleri='@json(collect($soruTipleri)->filter->secenekGerekli()->map->value->values())'
>
    <div class="kres-soru-list" data-soru-list>
        @include('soru-formlari._cards', ['form' => $form])
    </div>
</div>

<div class="confirm-modal" id="soru-modal" hidden>
    <div class="confirm-modal-backdrop" data-soru-modal-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="soru-modal-title">
        <div class="confirm-modal-header">
            <h3 id="soru-modal-title" class="confirm-modal-title" data-soru-modal-title>Yeni Soru</h3>
            <button type="button" class="confirm-modal-x" data-soru-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('soru-formlari.sorular.store', $form) }}" data-soru-form>
            @csrf
            <div class="confirm-modal-body">
                <div class="form-group">
                    <label for="soru-tip">Soru tipi <span class="req">*</span></label>
                    <select id="soru-tip" name="tip" class="form-control" required data-soru-tip>
                        <option value="">Soru tipi seçin</option>
                        @foreach ($soruTipleri as $tip)
                            <option value="{{ $tip->value }}" data-secenek="{{ $tip->secenekGerekli() ? '1' : '0' }}">{{ $tip->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div data-soru-alanlari hidden>
                    <div class="form-group">
                        <label for="soru-baslik">Soru <span class="req">*</span></label>
                        <input type="text" id="soru-baslik" name="baslik" class="form-control" data-soru-baslik maxlength="500" placeholder="Örn. Kemanınız var mı?">
                    </div>

                    <div class="form-group">
                        <label for="soru-aciklama">Yardım metni</label>
                        <input type="text" id="soru-aciklama" name="aciklama" class="form-control" data-soru-aciklama placeholder="İsteğe bağlı açıklama">
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group-switch">
                            <label class="switch-label" for="soru-zorunlu">
                                <input type="checkbox" id="soru-zorunlu" name="zorunlu" value="1" data-soru-zorunlu>
                                <span>Zorunlu</span>
                            </label>
                        </div>
                        <div class="form-group form-group-switch" data-tam-sayi-alani hidden>
                            <label class="switch-label" for="soru-tam-sayi">
                                <input type="checkbox" id="soru-tam-sayi" name="tam_sayi" value="1" data-soru-tam-sayi>
                                <span>Tam sayı</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row" data-sayi-alani hidden>
                        <div class="form-group">
                            <label for="soru-min-deger" data-min-label>Minimum</label>
                            <input type="number" id="soru-min-deger" name="min_deger" class="form-control" data-soru-min-deger step="any" placeholder="İsteğe bağlı">
                        </div>
                        <div class="form-group">
                            <label for="soru-max-deger" data-max-label>Maksimum</label>
                            <input type="number" id="soru-max-deger" name="max_deger" class="form-control" data-soru-max-deger step="any" placeholder="İsteğe bağlı">
                        </div>
                    </div>
                    <p class="form-hint" data-secim-adet-hint hidden>İşaretlenebilecek seçenek adedini belirtir. Boş bırakılırsa sınır uygulanmaz.</p>

                    <div class="form-group" data-secenek-alani hidden>
                        <label>Seçenekler <span class="req">*</span></label>
                        <p class="form-hint">Sırayı oklarla değiştirin. Boş satırlar kaydedilmez.</p>
                        <div class="kres-soru-secenek-list" data-secenek-list></div>
                        <x-back-button type="button" icon="plus" data-secenek-ekle>Seçenek ekle</x-back-button>
                    </div>

                    <div class="soru-kosul" data-kosul-alani>
                        <div class="form-group form-group-switch">
                            <label class="switch-label" for="soru-kosullu">
                                <input type="checkbox" id="soru-kosullu" data-kosul-toggle>
                                <span>Bu soruyu yalnızca belirli bir cevap verildiğinde göster</span>
                            </label>
                            <p class="form-hint" data-kosul-bos-hint hidden>Koşul için bu sorudan önce gelen seçmeli (liste, radio veya checkbox) bir soru gerekir.</p>
                        </div>
                        <div data-kosul-detay hidden>
                            <div class="form-group">
                                <label for="soru-kosul-soru">Hangi soruya göre?</label>
                                <select id="soru-kosul-soru" name="kosul_soru_id" class="form-control" data-kosul-soru>
                                    <option value="">Soru seçin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hangi cevap(lar) verildiğinde gösterilsin?</label>
                                <div class="kres-soru-onizleme-secenekler" data-kosul-secenekler></div>
                            </div>
                        </div>
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

@include('soru-formlari._preview-modal')
@endsection
