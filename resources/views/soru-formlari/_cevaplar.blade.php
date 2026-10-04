@if ($cevaplar->isNotEmpty())
    <section class="card form-section-card basvuru-detail-section">
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Ek Bilgiler</h2>
        </div>

        <div class="lesson-info-grid">
            @foreach ($cevaplar as $cevap)
                <div class="lesson-info-card">
                    <div class="lesson-info-label">{{ $cevap->soru_baslik }}</div>
                    <div class="lesson-info-value">
                        @if ($cevap->dosyaMi())
                            <a href="{{ $cevap->dosyaUrl() }}" target="_blank" rel="noopener" class="table-link">{{ $cevap->gorunenDeger() }}</a>
                        @else
                            {{ $cevap->gorunenDeger() !== '' ? $cevap->gorunenDeger() : '—' }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
