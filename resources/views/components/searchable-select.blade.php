@props([
    'name',
    'options' => [],
    'value' => '',
    'placeholder' => 'Seçin',
    'required' => false,
    'emptyValue' => '',
])

@php
    $selectedValue = (string) ($value ?? '');
    $selectedLabel = $placeholder;
    foreach ($options as $option) {
        if ((string) $option['value'] === $selectedValue) {
            $selectedLabel = $option['label'];
            break;
        }
    }
@endphp

<div
    class="custom-select searchable-select"
    data-searchable-select
    data-name="{{ $name }}"
    @if ($required) data-required="1" @endif
    {{ $attributes }}
>
    <input
        type="hidden"
        name="{{ $name }}"
        value="{{ $selectedValue }}"
        data-select-value
        @if ($required) data-required-field="1" @endif
    >
    <div
        class="select-display {{ $required && $selectedValue === '' ? 'is-empty' : '' }}"
        data-select-toggle
        tabindex="0"
        role="combobox"
        aria-haspopup="listbox"
        aria-expanded="false"
        @if ($required) aria-required="true" @endif
    >
        <span data-select-label class="select-label-text">{{ $selectedLabel }}</span>
    </div>
    <div class="select-dropdown" data-select-dropdown>
        <input type="text" class="select-search" placeholder="Ara..." data-select-search autocomplete="off">
        <div data-select-options>
            <div class="select-option {{ $selectedValue === (string) $emptyValue ? 'selected' : '' }}" data-value="{{ $emptyValue }}" data-label="{{ $placeholder }}">
                {{ $placeholder }}
            </div>
            @foreach ($options as $option)
                <div
                    class="select-option {{ (string) $option['value'] === $selectedValue ? 'selected' : '' }}"
                    data-value="{{ $option['value'] }}"
                    data-label="{{ $option['label'] }}"
                >
                    {{ $option['label'] }}
                </div>
            @endforeach
        </div>
    </div>
</div>
