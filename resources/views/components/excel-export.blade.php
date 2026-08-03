@props(['href'])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'btn-excel']) }}
>
    <span class="btn-excel-mark" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h7v7H4z"/>
            <path d="M13 4h7v7h-7z"/>
            <path d="M4 13h7v7H4z"/>
            <path d="M13 13h7v7h-7z"/>
        </svg>
    </span>
    <span class="btn-excel-text">Excel</span>
</a>
