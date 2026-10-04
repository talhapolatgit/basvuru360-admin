@php
    $cevapHaritasi = $basvuru->relationLoaded('cevaplar') ? $basvuru->cevaplar->whereNotNull('soru_id')->keyBy('soru_id') : collect();
@endphp
@foreach ($soruKolonlari ?? [] as $kolon)
    @php $cevap = $cevapHaritasi->get($kolon['soru']->id); @endphp
    <td data-column="{{ $kolon['key'] }}" class="col-{{ $kolon['key'] }} {{ in_array($kolon['key'], $defaultVisible, true) ? '' : 'col-hidden' }}">
        @if (! $cevap)
            —
        @elseif ($cevap->dosyaMi())
            <a href="{{ $cevap->dosyaUrl() }}" target="_blank" rel="noopener" class="table-link">{{ $cevap->gorunenDeger() }}</a>
        @else
            {{ \Illuminate\Support\Str::limit($cevap->gorunenDeger(), 120) ?: '—' }}
        @endif
    </td>
@endforeach
