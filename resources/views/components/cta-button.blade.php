@props([
    'href' => null,
    'type' => 'submit',
    'icon' => 'plus',
])

@php
    $iconSvg = match ($icon) {
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        default => '<path d="M5 12h14"/><path d="M12 5v14"/>',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn-cta']) }}>
        <span class="btn-cta-mark" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">{!! $iconSvg !!}</svg>
        </span>
        <span class="btn-cta-text">{{ $slot->isEmpty() ? 'Yeni Kurs' : $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'btn-cta']) }}>
        <span class="btn-cta-mark" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">{!! $iconSvg !!}</svg>
        </span>
        <span class="btn-cta-text">{{ $slot->isEmpty() ? 'Kaydet' : $slot }}</span>
    </button>
@endif
