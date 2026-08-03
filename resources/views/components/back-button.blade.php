@props([
    'href' => null,
    'type' => 'button',
    'icon' => 'back',
])

@php
    $mark = match ($icon) {
        'close' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'none' => null,
        default => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
    };
    $classes = 'btn-back'
        .($icon === 'none' ? ' btn-back-plain' : '')
        .($icon === 'plus' ? ' btn-back-add' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($mark)
            <span class="btn-back-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">{!! $mark !!}</svg>
            </span>
        @endif
        <span class="btn-back-text">{{ $slot->isEmpty() ? 'Listeye Dön' : $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($mark)
            <span class="btn-back-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">{!! $mark !!}</svg>
            </span>
        @endif
        <span class="btn-back-text">{{ $slot->isEmpty() ? 'Temizle' : $slot }}</span>
    </button>
@endif
