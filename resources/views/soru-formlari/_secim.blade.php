@php
    $seciliSoruFormuId = (string) ($seciliSoruFormuId ?? '');
    $soruFormuSecimId = $soruFormuSecimId ?? 'soru_formu_id';
@endphp
<div class="form-group">
    <label for="{{ $soruFormuSecimId }}">Başvuru Soru Formu</label>
    <select id="{{ $soruFormuSecimId }}" name="soru_formu_id" class="form-control" @isset($soruFormuDataField) data-field="{{ $soruFormuDataField }}" @endisset>
        <option value="">Ek soru sorulmasın</option>
        @foreach ($soruFormlari as $soruFormu)
            @php $secili = $seciliSoruFormuId === (string) $soruFormu->id; @endphp
            <option value="{{ $soruFormu->id }}" @selected($secili) @disabled(! $soruFormu->aktif && ! $secili)>
                {{ $soruFormu->ad }} ({{ $soruFormu->sorular_count }} soru){{ $soruFormu->aktif ? '' : ' · pasif' }}
            </option>
        @endforeach
    </select>
    <p class="field-hint">
        Başvuru sırasında kişiye sorulacak ek sorular (örn. "Kemanınız var mı?").
        @yetki('soru_formu.yonet')
            Formları <a href="{{ route('soru-formlari.index') }}" target="_blank" rel="noopener">Soru Formları</a> sayfasından yönetebilirsiniz.
        @endyetki
    </p>
</div>
