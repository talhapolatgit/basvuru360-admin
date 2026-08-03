<script>
(function () {
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
        if (hasItems) { empty?.remove(); return; }
        if (!empty) {
            empty = document.createElement('p');
            empty.className = 'evrak-empty';
            empty.setAttribute('data-evrak-empty', '');
            empty.textContent = 'Henüz evrak koşulu eklenmedi.';
            evrakList.appendChild(empty);
        }
    }

    evrakAdd?.addEventListener('click', function () {
        const option = evrakSelect?.selectedOptions[0];
        const id = option?.value;
        if (!id || !evrakList) return;
        if (evrakList.querySelector('[data-evrak-item][data-id="' + id + '"]')) {
            evrakSelect.value = '';
            return;
        }
        const item = document.createElement('div');
        item.className = 'evrak-item';
        item.setAttribute('data-evrak-item', '');
        item.setAttribute('data-id', id);
        item.innerHTML = '<input type="hidden" name="evrak_tipi_ids[]" value="' + id + '">' +
            '<span class="evrak-item-label">' + (option.dataset.label || option.textContent) + '</span>' +
            '<button type="button" class="evrak-item-remove" data-evrak-remove title="Kaldır">&times;</button>';
        evrakList.appendChild(item);
        evrakSelect.value = '';
        ensureEvrakEmptyHint();
    });

    evrakList?.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-evrak-remove]');
        if (!btn) return;
        btn.closest('[data-evrak-item]')?.remove();
        ensureEvrakEmptyHint();
    });
})();
</script>
