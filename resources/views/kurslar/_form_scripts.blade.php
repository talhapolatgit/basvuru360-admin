<script>
(function () {
    const rows = document.getElementById('gun-rows');
    const template = document.getElementById('gun-row-template');
    const addBtn = document.getElementById('add-gun-row');
    if (rows && template && addBtn) {
        let index = rows.querySelectorAll('[data-gun-row]').length;

        addBtn.addEventListener('click', function () {
            const html = template.innerHTML.replaceAll('__INDEX__', String(index++));
            rows.insertAdjacentHTML('beforeend', html);
        });

        rows.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-remove-gun]');
            if (!btn) return;
            const row = btn.closest('[data-gun-row]');
            if (!row) return;
            if (rows.querySelectorAll('[data-gun-row]').length <= 1) {
                row.querySelectorAll('input, select').forEach(function (el) { el.value = ''; });
                return;
            }
            row.remove();
        });
    }

    const ikametSelect = document.querySelector('[data-ikamet-sarti]');
    const ikametDisiField = document.querySelector('[data-ikamet-disi-field]');
    const ikametDisiInput = document.querySelector('[data-ikamet-disi-input]');

    function syncIkametDisi() {
        if (!ikametSelect || !ikametDisiField) return;
        const show = ikametSelect.value === 'kismen';
        ikametDisiField.classList.toggle('is-hidden', !show);
        if (ikametDisiInput) {
            ikametDisiInput.required = show;
            if (!show) ikametDisiInput.value = '0';
        }
    }

    ikametSelect?.addEventListener('change', syncIkametDisi);
    syncIkametDisi();

    const evrakSelect = document.querySelector('[data-evrak-select]');
    const evrakAdd = document.querySelector('[data-evrak-add]');
    const evrakList = document.querySelector('[data-evrak-list]');

    function ensureEvrakEmptyHint() {
        if (!evrakList) return;
        const hasItems = evrakList.querySelectorAll('[data-evrak-item]').length > 0;
        let empty = evrakList.querySelector('[data-evrak-empty]');
        if (hasItems) {
            empty?.remove();
            return;
        }
        if (!empty) {
            empty = document.createElement('p');
            empty.className = 'evrak-empty';
            empty.setAttribute('data-evrak-empty', '');
            empty.textContent = 'Henüz evrak koşulu eklenmedi.';
            evrakList.appendChild(empty);
        }
    }

    function addEvrak() {
        if (!evrakSelect || !evrakList) return;
        const option = evrakSelect.selectedOptions[0];
        const id = option?.value;
        if (!id) return;
        if (evrakList.querySelector('[data-evrak-item][data-id="' + id + '"]')) {
            evrakSelect.value = '';
            return;
        }

        const item = document.createElement('div');
        item.className = 'evrak-item';
        item.setAttribute('data-evrak-item', '');
        item.setAttribute('data-id', id);
        item.innerHTML =
            '<input type="hidden" name="evrak_tipi_ids[]" value="' + id + '">' +
            '<span class="evrak-item-label"></span>' +
            '<button type="button" class="evrak-item-remove" data-evrak-remove title="Kaldır" aria-label="Kaldır">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>' +
            '</button>';
        item.querySelector('.evrak-item-label').textContent = option.getAttribute('data-label') || option.textContent;
        evrakList.querySelector('[data-evrak-empty]')?.remove();
        evrakList.appendChild(item);
        option.disabled = true;
        evrakSelect.value = '';
    }

    evrakAdd?.addEventListener('click', addEvrak);
    evrakSelect?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addEvrak();
        }
    });

    evrakList?.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-evrak-remove]');
        if (!btn) return;
        const item = btn.closest('[data-evrak-item]');
        if (!item) return;
        const id = item.getAttribute('data-id');
        const option = evrakSelect?.querySelector('option[value="' + id + '"]');
        if (option) option.disabled = false;
        item.remove();
        ensureEvrakEmptyHint();
    });

    evrakList?.querySelectorAll('[data-evrak-item]').forEach(function (item) {
        const option = evrakSelect?.querySelector('option[value="' + item.getAttribute('data-id') + '"]');
        if (option) option.disabled = true;
    });

    const gunIsoMap = {
        pazartesi: 1,
        sali: 2,
        carsamba: 3,
        persembe: 4,
        cuma: 5,
        cumartesi: 6,
        pazar: 7,
    };

    function toast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'error');
            return;
        }
        alert(message);
    }

    function formatDateInput(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function parseDateInput(value) {
        if (!value) return null;
        const parts = value.split('-').map(Number);
        if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return null;
        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        if (Number.isNaN(date.getTime())) return null;
        return date;
    }

    function collectWeeklyHoursByIso() {
        const byIso = {};
        document.querySelectorAll('#gun-rows [data-gun-row]').forEach(function (row) {
            const gun = row.querySelector('select[name*="[gun]"]')?.value || '';
            const saat = parseFloat(row.querySelector('input[name*="[ders_saati]"]')?.value || '');
            const baslangic = row.querySelector('input[name*="[baslangic_saati]"]')?.value || '';
            const bitis = row.querySelector('input[name*="[bitis_saati]"]')?.value || '';
            const iso = gunIsoMap[gun];
            if (!iso || !baslangic || !bitis || !(saat > 0)) return;
            byIso[iso] = (byIso[iso] || 0) + saat;
        });
        return byIso;
    }

    function validateWeeklyProgramRows(form) {
        if (!form) return true;

        const rows = form.querySelectorAll('#gun-rows [data-gun-row]');
        if (!rows.length) {
            toast('Haftalık programa en az bir gün ekleyin.', 'error');
            document.getElementById('gun-rows')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        let firstInvalid = null;
        const missing = [];

        rows.forEach(function (row, index) {
            const fields = [
                { el: row.querySelector('select[name*="[gun]"]'), label: 'Gün' },
                { el: row.querySelector('input[name*="[baslangic_saati]"]'), label: 'Başlangıç' },
                { el: row.querySelector('input[name*="[bitis_saati]"]'), label: 'Bitiş' },
                { el: row.querySelector('input[name*="[ders_saati]"]'), label: 'Ders Saati' },
            ];

            fields.forEach(function (field) {
                const empty = !field.el || String(field.el.value || '').trim() === ''
                    || (field.label === 'Ders Saati' && !(parseFloat(field.el.value) > 0));

                field.el?.classList.toggle('is-invalid', empty);

                if (empty) {
                    missing.push((index + 1) + '. satır ' + field.label);
                    if (!firstInvalid) firstInvalid = field.el;
                }
            });
        });

        if (missing.length === 0) return true;

        toast('Haftalık programda zorunlu alanlar eksik: ' + missing.slice(0, 3).join(', ') + (missing.length > 3 ? '…' : ''), 'error');
        firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid?.focus();
        return false;
    }

    function dateToIsoDay(date) {
        const jsDay = date.getDay();
        return jsDay === 0 ? 7 : jsDay;
    }

    const gunLabelByIso = {
        1: 'Pazartesi',
        2: 'Salı',
        3: 'Çarşamba',
        4: 'Perşembe',
        5: 'Cuma',
        6: 'Cumartesi',
        7: 'Pazar',
    };

    function validateBaslamaMatchesProgram() {
        const baslamaEl = document.getElementById('kurs_baslama_tarihi');
        if (!baslamaEl) return true;

        const baslama = parseDateInput(baslamaEl.value);
        if (!baslama) {
            toast('Kurs başlangıç tarihini girin.', 'error');
            baslamaEl.focus();
            return false;
        }

        const weeklyByIso = collectWeeklyHoursByIso();
        const weeklyTotal = Object.values(weeklyByIso).reduce(function (sum, h) { return sum + h; }, 0);
        if (!(weeklyTotal > 0)) {
            toast('Haftalık programda en az bir gün ve ders saati tanımlayın.', 'error');
            document.getElementById('gun-rows')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        const startIso = dateToIsoDay(baslama);
        if (!weeklyByIso[startIso]) {
            const programGunleri = Object.keys(weeklyByIso)
                .map(Number)
                .sort(function (a, b) { return a - b; })
                .map(function (iso) { return gunLabelByIso[iso]; })
                .join(', ');

            toast(
                'Kurs başlangıç tarihi ' + gunLabelByIso[startIso] + ' gününe denk geliyor; haftalık programda (' + programGunleri + ') yer almıyor.',
                'error'
            );
            baslamaEl.focus();
            return false;
        }

        return true;
    }

    function calculateKursBitis() {
        const baslamaEl = document.getElementById('kurs_baslama_tarihi');
        const bitisEl = document.getElementById('kurs_bitis_tarihi');
        const toplamEl = document.getElementById('toplam_kurs_saati');
        if (!baslamaEl || !bitisEl || !toplamEl) return;

        const form = document.getElementById('kurs-create-form') || document.getElementById('kurs-edit-form');
        if (!validateWeeklyProgramRows(form)) {
            return;
        }

        if (!validateBaslamaMatchesProgram()) {
            return;
        }

        const baslama = parseDateInput(baslamaEl.value);
        const toplam = parseFloat(toplamEl.value);
        if (!(toplam > 0)) {
            toast('Toplam kurs saatini girin.', 'error');
            toplamEl.focus();
            return;
        }

        const weeklyByIso = collectWeeklyHoursByIso();
        let accumulated = 0;
        let current = new Date(baslama.getTime());
        let lastLessonDate = null;
        const maxDays = 366 * 5;

        for (let i = 0; i < maxDays; i++) {
            const dayHours = weeklyByIso[dateToIsoDay(current)] || 0;

            if (dayHours > 0) {
                accumulated += dayHours;
                lastLessonDate = new Date(current.getTime());
                if (accumulated + 1e-9 >= toplam) {
                    bitisEl.value = formatDateInput(lastLessonDate);
                    toast('Kurs bitiş tarihi hesaplandı.', 'success');
                    bitisEl.focus();
                    return;
                }
            }

            current.setDate(current.getDate() + 1);
        }

        toast('Bitiş tarihi hesaplanamadı. Program ve toplam saati kontrol edin.', 'error');
    }

    function fieldLabel(selectEl) {
        const group = selectEl.closest('.form-group');
        const label = group?.querySelector('label');
        if (!label) return selectEl.dataset.name || 'Alan';
        return (label.childNodes[0]?.textContent || label.textContent || 'Alan').trim().replace(/\s*\*$/, '');
    }

    function validateRequiredSearchableSelects(form) {
        if (!form) return true;

        const requiredSelects = form.querySelectorAll('[data-searchable-select][data-required="1"]');
        let firstInvalid = null;
        const missing = [];

        requiredSelects.forEach(function (select) {
            const valueInput = select.querySelector('[data-select-value]');
            const display = select.querySelector('[data-select-toggle]');
            const empty = !valueInput || String(valueInput.value || '').trim() === '';

            select.classList.toggle('is-invalid', empty);
            display?.classList.toggle('is-invalid', empty);

            if (empty) {
                missing.push(fieldLabel(select));
                if (!firstInvalid) firstInvalid = select;
            }
        });

        if (missing.length === 0) return true;

        toast(missing.join(', ') + ' zorunludur.', 'error');
        firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid?.querySelector('[data-select-toggle]')?.focus();
        return false;
    }

    function parseDateTimeLocal(value) {
        if (!value) return null;
        // datetime-local: YYYY-MM-DDTHH:mm or date: YYYY-MM-DD
        if (value.length >= 10) {
            return parseDateInput(value.slice(0, 10));
        }
        return null;
    }

    function validateBasvuruTarihleriKursBitisindenSonraDegil() {
        const kursBitisEl = document.getElementById('kurs_bitis_tarihi');
        const basvuruBaslamaEl = document.getElementById('basvuru_baslama_tarihi');
        const basvuruBitisEl = document.getElementById('basvuru_bitis_tarihi');
        if (!kursBitisEl || !basvuruBaslamaEl || !basvuruBitisEl) return true;

        const kursBitis = parseDateInput(kursBitisEl.value);
        if (!kursBitis) return true;

        const basvuruBaslama = parseDateTimeLocal(basvuruBaslamaEl.value);
        const basvuruBitis = parseDateTimeLocal(basvuruBitisEl.value);

        if (basvuruBaslama && basvuruBaslama.getTime() > kursBitis.getTime()) {
            toast('Başvuru başlangıç tarihi kurs bitiş tarihinden sonra olamaz.', 'error');
            basvuruBaslamaEl.classList.add('is-invalid');
            basvuruBaslamaEl.focus();
            return false;
        }

        if (basvuruBitis && basvuruBitis.getTime() > kursBitis.getTime()) {
            toast('Başvuru bitiş tarihi kurs bitiş tarihinden sonra olamaz.', 'error');
            basvuruBitisEl.classList.add('is-invalid');
            basvuruBitisEl.focus();
            return false;
        }

        basvuruBaslamaEl.classList.remove('is-invalid');
        basvuruBitisEl.classList.remove('is-invalid');
        return true;
    }

    function validationMessage(error) {
        const data = error?.response?.data;
        if (!data) {
            return 'İşlem sırasında bir hata oluştu.';
        }
        if (typeof data.message === 'string' && data.message && !data.errors) {
            return data.message;
        }
        if (data.errors && typeof data.errors === 'object') {
            const first = Object.values(data.errors).flat()[0];
            if (first) return first;
        }
        if (typeof data.message === 'string' && data.message) {
            return data.message;
        }
        return 'İşlem sırasında bir hata oluştu.';
    }

    function markServerValidationErrors(form, errors) {
        if (!errors || typeof errors !== 'object') return;

        Object.keys(errors).forEach(function (name) {
            const htmlName = name.split('.').reduce(function (acc, part, index) {
                return index === 0 ? part : acc + '[' + part + ']';
            }, '');
            const candidates = [
                form.querySelector('[name="' + name + '"]'),
                form.querySelector('[name="' + htmlName + '"]'),
                form.querySelector('[name="' + name + '[]"]'),
            ].filter(Boolean);

            candidates.forEach(function (el) {
                el.classList.add('is-invalid');
                const selectRoot = el.closest('[data-searchable-select]');
                if (selectRoot) {
                    selectRoot.classList.add('is-invalid');
                    selectRoot.querySelector('[data-select-toggle]')?.classList.add('is-invalid');
                }
            });
        });
    }

    async function onKursFormSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;

        if (!validateRequiredSearchableSelects(form)) return;
        if (!validateWeeklyProgramRows(form)) return;
        if (!validateBasvuruTarihleriKursBitisindenSonraDegil()) return;
        if (!validateBaslamaMatchesProgram()) return;

        if (!window.axios) {
            form.submit();
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Kaydediliyor...';
        }

        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });

        try {
            const formData = new FormData(form);
            const { data } = await window.axios.post(form.action, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (data.redirect) {
                window.location.assign(data.redirect);
                return;
            }

            toast(data.message || 'Kurs kaydedildi.', 'success');
        } catch (error) {
            markServerValidationErrors(form, error?.response?.data?.errors);
            toast(validationMessage(error), 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = previousLabel || 'Kaydet';
            }
        }
    }

    document.getElementById('hesapla-kurs-bitis')?.addEventListener('click', calculateKursBitis);

    document.getElementById('kurs-create-form')?.addEventListener('submit', onKursFormSubmit);
    document.getElementById('kurs-edit-form')?.addEventListener('submit', onKursFormSubmit);
})();
</script>
