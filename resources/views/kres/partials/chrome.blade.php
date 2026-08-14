{{-- Beklenen: $step (1|2|3|null), opsiyonel $okul, $grup --}}
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.1rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if ($step)
<div class="kres-chrome">
    <nav class="kres-stepper" aria-label="Kreş adımları">
        <a
            href="{{ route('kres.index') }}"
            class="kres-step {{ $step === 1 ? 'is-active' : ($step > 1 ? 'is-done' : '') }}"
        >
            <span class="kres-step__num">1</span>
            <span class="kres-step__body">
                <span class="kres-step__label">Okullar</span>
                <span class="kres-step__hint">Kurum seçimi</span>
            </span>
        </a>
        <span class="kres-stepper__sep" aria-hidden="true"></span>
        <a
            href="{{ isset($okul) ? route('kres.okullar.show', $okul) : '#' }}"
            class="kres-step {{ $step === 2 ? 'is-active' : ($step > 2 ? 'is-done' : 'is-disabled') }}"
            @if (! isset($okul)) aria-disabled="true" tabindex="-1" @endif
        >
            <span class="kres-step__num">2</span>
            <span class="kres-step__body">
                <span class="kres-step__label">Gruplar</span>
                <span class="kres-step__hint">{{ isset($okul) ? $okul->ad : 'Okul seçin' }}</span>
            </span>
        </a>
        <span class="kres-stepper__sep" aria-hidden="true"></span>
        <a
            href="{{ isset($okul, $grup) ? route('kres.gruplar.show', [$okul, $grup]) : '#' }}"
            class="kres-step {{ $step === 3 ? 'is-active' : 'is-disabled' }}"
            @if (! isset($okul, $grup)) aria-disabled="true" tabindex="-1" @endif
        >
            <span class="kres-step__num">3</span>
            <span class="kres-step__body">
                <span class="kres-step__label">Başvurular</span>
                <span class="kres-step__hint">{{ isset($grup) ? $grup->ad : 'Grup seçin' }}</span>
            </span>
        </a>
    </nav>
</div>
@endif
