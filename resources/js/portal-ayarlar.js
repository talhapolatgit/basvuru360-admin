const KURS_SECIM = [
    { value: 'tum', label: 'Tüm kurslar' },
    { value: 'kurs', label: 'Kurs bazında' },
    { value: 'brans', label: 'Branş bazında' },
    { value: 'alan', label: 'Alan bazında' },
    { value: 'merkez', label: 'Merkez bazında' },
];

const ETKINLIK_SECIM = [
    { value: 'tum', label: 'Tüm etkinlikler' },
    { value: 'etkinlik', label: 'Etkinlik bazında' },
    { value: 'tur', label: 'Tür bazında' },
    { value: 'merkez', label: 'Merkez bazında' },
];

function hedefKey(kaynak, secim) {
    if (secim === 'tum' || secim === 'kurs' || secim === 'etkinlik') return null;
    if (kaynak === 'kurs') {
        if (secim === 'brans') return 'branslar';
        if (secim === 'alan') return 'alanlar';
        if (secim === 'merkez') return 'merkezler';
    }
    if (kaynak === 'etkinlik') {
        if (secim === 'tur') return 'etkinlik_tipleri';
        if (secim === 'merkez') return 'merkezler';
    }
    return null;
}

function isNoModu(secim) {
    return secim === 'kurs' || secim === 'etkinlik';
}

function fillSelect(select, options, selected = '') {
    select.innerHTML = '';
    options.forEach((opt) => {
        const option = document.createElement('option');
        option.value = String(opt.value);
        option.textContent = opt.label;
        if (String(opt.value) === String(selected)) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

function closeHedefSelects(except = null) {
    document.querySelectorAll('[data-kural-hedef-select]').forEach((select) => {
        if (except && select === except) return;
        select.classList.remove('open');
        select.querySelector('[data-select-dropdown]')?.classList.remove('open');
        select.querySelector('[data-select-toggle]')?.setAttribute('aria-expanded', 'false');
    });
}

function setHedefValue(selectRoot, value, label, required = true) {
    const valueInput = selectRoot.querySelector('[data-kural-hedef]');
    const labelEl = selectRoot.querySelector('[data-select-label]');
    const toggle = selectRoot.querySelector('[data-select-toggle]');
    const isEmpty = String(value ?? '').trim() === '';

    if (valueInput) valueInput.value = String(value ?? '');
    if (labelEl) labelEl.textContent = label || 'Seçin';
    toggle?.classList.toggle('is-empty', isEmpty);
    selectRoot.classList.toggle('is-invalid', required && isEmpty);
    toggle?.classList.toggle('is-invalid', required && isEmpty);

    selectRoot.querySelectorAll('.select-option').forEach((option) => {
        option.classList.toggle('selected', String(option.dataset.value ?? '') === String(value ?? ''));
    });
}

function fillHedefSearchable(selectRoot, options, selected = '', placeholder = 'Seçin') {
    const optionsRoot = selectRoot.querySelector('[data-select-options]');
    if (!optionsRoot) return;

    const items = [{ value: '', label: placeholder }, ...options];
    optionsRoot.innerHTML = '';

    items.forEach((opt) => {
        const el = document.createElement('div');
        el.className = 'select-option';
        el.dataset.value = String(opt.value);
        el.dataset.label = opt.label;
        el.textContent = opt.label;
        if (String(opt.value) === String(selected)) {
            el.classList.add('selected');
        }
        optionsRoot.appendChild(el);
    });

    const selectedOpt = items.find((opt) => String(opt.value) === String(selected));
    setHedefValue(
        selectRoot,
        selectedOpt ? selectedOpt.value : '',
        selectedOpt ? selectedOpt.label : placeholder,
        selectRoot.dataset.required === '1',
    );
}

function bindHedefSearchable(selectRoot) {
    if (selectRoot.dataset.bound === '1') return;
    selectRoot.dataset.bound = '1';

    const toggle = selectRoot.querySelector('[data-select-toggle]');
    const dropdown = selectRoot.querySelector('[data-select-dropdown]');
    const searchInput = selectRoot.querySelector('[data-select-search]');
    const optionsRoot = selectRoot.querySelector('[data-select-options]');

    toggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        if (selectRoot.hidden || selectRoot.dataset.disabled === '1') return;

        const willOpen = !dropdown.classList.contains('open');
        closeHedefSelects();
        document.getElementById('columnDropdown')?.classList.remove('open');

        if (willOpen) {
            selectRoot.classList.add('open');
            dropdown.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
            if (searchInput) {
                searchInput.value = '';
                optionsRoot?.querySelectorAll('.select-option').forEach((option) => {
                    option.classList.remove('hidden');
                });
                setTimeout(() => searchInput.focus(), 0);
            }
        }
    });

    searchInput?.addEventListener('click', (event) => event.stopPropagation());
    searchInput?.addEventListener('input', () => {
        const query = searchInput.value.trim().toLocaleLowerCase('tr-TR');
        optionsRoot?.querySelectorAll('.select-option').forEach((option) => {
            const text = (option.dataset.label || option.textContent || '').toLocaleLowerCase('tr-TR');
            option.classList.toggle('hidden', query !== '' && !text.includes(query));
        });
    });

    optionsRoot?.addEventListener('click', (event) => {
        const option = event.target.closest('.select-option');
        if (!option || !optionsRoot.contains(option)) return;
        event.stopPropagation();

        setHedefValue(
            selectRoot,
            option.dataset.value ?? '',
            option.dataset.label ?? option.textContent.trim(),
            selectRoot.dataset.required === '1',
        );
        closeHedefSelects();
    });
}

function setHedefSelectEnabled(selectRoot, enabled) {
    selectRoot.hidden = !enabled;
    selectRoot.dataset.disabled = enabled ? '0' : '1';
    const valueInput = selectRoot.querySelector('[data-kural-hedef]');
    if (valueInput) {
        valueInput.disabled = !enabled;
        if (!enabled) valueInput.value = '';
    }
}

function reindexRows(root) {
    root.querySelectorAll('[data-kural-row]').forEach((row, index) => {
        const kaynak = row.querySelector('[data-kural-kaynak]');
        const secim = row.querySelector('[data-kural-secim]');
        const hedefSelect = row.querySelector('[data-kural-hedef]');
        const hedefNos = row.querySelector('[data-kural-hedef-nos]');
        if (kaynak) kaynak.name = `kurallar[${index}][kaynak]`;
        if (secim) secim.name = `kurallar[${index}][secim_tipi]`;
        if (hedefSelect) {
            hedefSelect.name = hedefSelect.disabled ? '' : `kurallar[${index}][hedef_id]`;
        }
        if (hedefNos) {
            hedefNos.name = hedefNos.disabled ? '' : `kurallar[${index}][hedef_nos]`;
        }
    });
}

function syncRow(row, hedefler) {
    const kaynakSelect = row.querySelector('[data-kural-kaynak]');
    const secimSelect = row.querySelector('[data-kural-secim]');
    const hedefSelectRoot = row.querySelector('[data-kural-hedef-select]');
    const hedefValue = row.querySelector('[data-kural-hedef]');
    const hedefNos = row.querySelector('[data-kural-hedef-nos]');
    const hedefWrap = row.querySelector('[data-hedef-wrap]');
    const hedefHint = row.querySelector('[data-hedef-hint]');

    const kaynak = kaynakSelect.value;
    const secimOptions = kaynak === 'etkinlik' ? ETKINLIK_SECIM : KURS_SECIM;
    const currentSecim = secimSelect.value;
    fillSelect(secimSelect, secimOptions, currentSecim || secimOptions[0].value);

    const secim = secimSelect.value;
    bindHedefSearchable(hedefSelectRoot);

    if (secim === 'tum') {
        hedefWrap.style.visibility = 'hidden';
        setHedefSelectEnabled(hedefSelectRoot, false);
        hedefNos.disabled = true;
        hedefNos.required = false;
        hedefNos.hidden = true;
        hedefNos.value = '';
        if (hedefHint) {
            hedefHint.textContent = '';
            hedefHint.hidden = true;
        }
        return;
    }

    hedefWrap.style.visibility = 'visible';

    if (isNoModu(secim)) {
        setHedefSelectEnabled(hedefSelectRoot, false);
        hedefNos.hidden = false;
        hedefNos.disabled = false;
        hedefNos.required = true;
        hedefNos.placeholder =
            secim === 'kurs'
                ? 'Örn. K-1001, K-1002'
                : 'Örn. E-2001, E-2002';
        if (hedefHint) {
            hedefHint.hidden = false;
            hedefHint.textContent =
                secim === 'kurs'
                    ? 'Kurs numaralarını virgülle ayırarak yazın.'
                    : 'Etkinlik numaralarını virgülle ayırarak yazın.';
        }
        if (hedefNos.dataset.initial) {
            hedefNos.value = hedefNos.dataset.initial;
            delete hedefNos.dataset.initial;
        }
        return;
    }

    hedefNos.hidden = true;
    hedefNos.disabled = true;
    hedefNos.required = false;
    hedefNos.value = '';
    setHedefSelectEnabled(hedefSelectRoot, true);
    if (hedefHint) {
        hedefHint.hidden = false;
        hedefHint.textContent = 'Listeden arayarak seçin.';
    }

    const key = hedefKey(kaynak, secim);
    const selected = hedefSelectRoot.dataset.selected || hedefValue?.value || '';
    fillHedefSearchable(hedefSelectRoot, hedefler[key] || [], selected, 'Seçin');
    delete hedefSelectRoot.dataset.selected;
}

function syncKuralEmpty(root) {
    const empty = root.querySelector('[data-kural-empty]');
    if (!empty) return;
    const hasRows = root.querySelector('[data-kural-row]') != null;
    empty.hidden = hasRows;
}

function addRow(root, template, hedefler, initial = null) {
    const node = template.content.firstElementChild.cloneNode(true);
    const kaynakSelect = node.querySelector('[data-kural-kaynak]');
    const secimSelect = node.querySelector('[data-kural-secim]');
    const hedefSelectRoot = node.querySelector('[data-kural-hedef-select]');
    const hedefNos = node.querySelector('[data-kural-hedef-nos]');

    if (initial) {
        kaynakSelect.value = initial.kaynak || 'kurs';
        fillSelect(
            secimSelect,
            kaynakSelect.value === 'etkinlik' ? ETKINLIK_SECIM : KURS_SECIM,
            initial.secim_tipi || 'tum',
        );
        if (initial.hedef_id != null) {
            hedefSelectRoot.dataset.selected = String(initial.hedef_id);
        }
        if (initial.hedef_nos) {
            hedefNos.dataset.initial = String(initial.hedef_nos);
        }
    } else {
        fillSelect(secimSelect, KURS_SECIM, 'tum');
    }

    kaynakSelect.addEventListener('change', () => {
        secimSelect.value = '';
        hedefNos.dataset.initial = '';
        hedefSelectRoot.dataset.selected = '';
        syncRow(node, hedefler);
        reindexRows(root);
    });
    secimSelect.addEventListener('change', () => {
        hedefSelectRoot.dataset.selected = '';
        if (!isNoModu(secimSelect.value)) {
            hedefNos.dataset.initial = '';
            hedefNos.value = '';
        }
        syncRow(node, hedefler);
        reindexRows(root);
    });
    node.querySelector('[data-kural-sil]')?.addEventListener('click', () => {
        node.remove();
        reindexRows(root);
        syncKuralEmpty(root);
    });

    root.appendChild(node);
    syncRow(node, hedefler);
    reindexRows(root);
    syncKuralEmpty(root);
}

export function initPortalAyarlarPage() {
    const formRoot = document.querySelector('[data-portal-sayfa-form]');
    if (!formRoot) return;

    const list = formRoot.querySelector('[data-kural-list]');
    const template = formRoot.querySelector('[data-kural-template]');
    const addBtn = formRoot.querySelector('[data-kural-ekle]');
    let hedefler = {};
    try {
        hedefler = JSON.parse(formRoot.dataset.hedefler || '{}');
    } catch {
        hedefler = {};
    }

    let initial = [];
    const initialNode = document.querySelector('[data-initial-kurallar]');
    if (initialNode) {
        try {
            initial = JSON.parse(initialNode.textContent || '[]');
        } catch {
            initial = [];
        }
    }

    if (Array.isArray(initial) && initial.length > 0) {
        initial.forEach((row) => addRow(list, template, hedefler, row));
    } else {
        syncKuralEmpty(list);
    }

    addBtn?.addEventListener('click', () => {
        addRow(list, template, hedefler);
        list.querySelector('[data-kural-row]:last-child')?.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
        });
    });

    document.addEventListener('click', () => closeHedefSelects());
}
