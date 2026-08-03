@props([
    'size' => 20,
    'class' => '',
])

{{-- Başvuru 360: online başvuru formu + onay + 360 halkası --}}
<svg
    {{ $attributes->merge(['class' => $class]) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 32 32"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    {{-- 360 halkası --}}
    <circle cx="16" cy="16" r="14.5" stroke="currentColor" stroke-width="1.6" opacity="0.28"/>
    <path
        d="M26 12.2A11.4 11.4 0 0 0 9.6 7.8"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        opacity="0.7"
    />
    <path
        d="M6.2 19.2A11.4 11.4 0 0 0 22.8 24.4"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        opacity="0.45"
    />

    {{-- Başvuru formu --}}
    <rect
        x="9.25"
        y="7.75"
        width="13.5"
        height="16.5"
        rx="2.4"
        stroke="currentColor"
        stroke-width="1.65"
    />
    <path
        d="M12.5 12h7M12.5 15.6h7M12.5 19.2h4.2"
        stroke="currentColor"
        stroke-width="1.55"
        stroke-linecap="round"
    />

    {{-- Onay rozeti --}}
    <circle cx="22.6" cy="22.6" r="5.4" fill="currentColor"/>
    <path
        d="M20.35 22.65 21.9 24.15 25.05 20.85"
        stroke="var(--brand-logo-check, #0f172a)"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
    />
</svg>
