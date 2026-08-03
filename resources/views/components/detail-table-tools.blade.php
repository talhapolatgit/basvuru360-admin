@props([
    'columns' => [],
    'visible' => [],
    'excelName' => 'liste',
])

<div class="flex items-center gap-2.5">
    <div class="column-picker" data-col-picker>
        <button
            type="button"
            class="btn-columns"
            data-col-toggle
            aria-haspopup="true"
            aria-expanded="false"
        >
            <span class="btn-columns-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <path d="M9 3v18"/>
                    <path d="M15 3v18"/>
                </svg>
            </span>
            <span class="btn-columns-text">Sütunlar</span>
            <svg class="btn-columns-caret" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>
        <div class="column-dropdown" data-col-dropdown role="menu">
            <div class="column-dropdown-header">
                <span>Görünür sütunlar</span>
                <span class="column-dropdown-hint">Sürükleyerek sıralayın</span>
            </div>
            <div class="column-dropdown-list">
                @foreach ($columns as $key => $label)
                    <label class="column-option" data-column="{{ $key }}">
                        <input
                            type="checkbox"
                            class="column-toggle"
                            data-column="{{ $key }}"
                            @checked(in_array($key, $visible, true))
                        >
                        <span class="column-option-check" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </span>
                        <span class="column-option-label">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <div class="column-dropdown-footer">
                <button type="button" class="column-save-btn save-prefs-btn" data-col-save title="Kolon düzenlemelerini kaydet">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Kaydet
                </button>
                <button type="button" class="column-reset-btn" data-col-reset>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                    Sıfırla
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="btn-excel" data-excel-btn data-excel-name="{{ $excelName }}">
        <span class="btn-excel-mark" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h7v7H4z"/>
                <path d="M13 4h7v7h-7z"/>
                <path d="M4 13h7v7H4z"/>
                <path d="M13 13h7v7h-7z"/>
            </svg>
        </span>
        <span class="btn-excel-text">Excel</span>
    </button>
</div>
