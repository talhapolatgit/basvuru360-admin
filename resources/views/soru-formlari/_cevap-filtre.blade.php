@php
    $filtreSorulari = $soruFormu?->sorular ?? collect();
@endphp
@if ($filtreSorulari->isNotEmpty())
    <div class="cevap-filtre" data-cevap-filtre>
        <select class="form-control" data-cevap-soru aria-label="Ek soruya göre filtrele">
            <option value="">Ek soruya göre filtrele</option>
            @foreach ($filtreSorulari as $soru)
                <option
                    value="{{ $soru->id }}"
                    data-secmeli="{{ $soru->tip->secenekGerekli() ? '1' : '0' }}"
                    data-secenekler='@json($soru->secenekler->map(fn ($s) => ['id' => $s->id, 'etiket' => $s->etiket])->values())'
                >{{ \Illuminate\Support\Str::limit($soru->baslik, 60) }}</option>
            @endforeach
        </select>
        <select class="form-control" data-cevap-deger-secim hidden aria-label="Cevap"></select>
        <input type="text" class="form-control" data-cevap-deger-metin hidden placeholder="Cevap içerir..." aria-label="Cevap">
        <button type="button" class="cevap-filtre-temizle" data-cevap-filtre-temizle hidden title="Filtreyi temizle" aria-label="Filtreyi temizle">&times;</button>
    </div>
@endif
