import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (![...document.querySelectorAll('.confirm-modal')].some((el) => !el.hidden)) {
        document.body.classList.remove('modal-open');
    }
}

function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
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
    return 'İşlem sırasında bir hata oluştu.';
}

function setReadonlyOnEdit(form, isEdit) {
    form.querySelectorAll('[data-readonly-on-edit]').forEach((field) => {
        field.readOnly = isEdit;
        field.classList.toggle('is-readonly', isEdit);
        if (isEdit) {
            field.setAttribute('aria-readonly', 'true');
        } else {
            field.removeAttribute('aria-readonly');
        }
    });
}

/**
 * Generic create/edit modal controller for simple lookup entities
 * (Merkez, Alan, Branş, Sabit Tanımlar). The modal must contain:
 * - a <form> (formSelector) whose fields carry [data-field="..."] attributes
 * - open buttons matching openSelector
 * - edit buttons matching editSelector with data-update-url + data-<field>
 * - [data-entity-modal-close] elements to close it
 */
export function initEntityModal({
    modalId,
    formSelector,
    titleSelector = '[data-entity-modal-title]',
    createTitle = 'Yeni Kayıt',
    editTitle = 'Kaydı Düzenle',
    openSelector = '[data-entity-modal-open]',
    editSelector = '[data-entity-edit]',
    onCreate,
    onEdit,
    onSuccess,
}) {
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector(formSelector);
    if (!modal || !form) return;

    const title = modal.querySelector(titleSelector);
    const createUrl = form.dataset.createUrl || form.getAttribute('action');

    function clearMethodField() {
        form.querySelector('[data-method-field]')?.remove();
    }

    function fillForm(dataset) {
        form.querySelectorAll('[data-field]').forEach((field) => {
            const key = field.dataset.field;
            if (!(key in dataset)) return;

            if (field.type === 'checkbox') {
                field.checked = dataset[key] === '1' || dataset[key] === 'true';
            } else {
                field.value = dataset[key] ?? '';
            }
        });
    }

    document.querySelectorAll(openSelector).forEach((btn) => {
        btn.addEventListener('click', () => {
            form.reset();
            clearMethodField();
            form.action = createUrl;
            setReadonlyOnEdit(form, false);
            if (title) title.textContent = createTitle;
            onCreate?.(form, btn);
            openModal(modal);
            form.querySelector('input:not([readonly]), select, textarea')?.focus();
        });
    });

    document.addEventListener('click', (event) => {
        const btn = event.target.closest(editSelector);
        if (!btn) return;

        // Ignore edit buttons belonging to another modal instance.
        if (editSelector === '[data-entity-edit]' && btn.closest('.confirm-modal') && btn.closest('.confirm-modal') !== modal) {
            return;
        }

        event.preventDefault();

        const rowActions = btn.closest('[data-row-actions]');
        if (rowActions) {
            rowActions.classList.remove('is-open');
            rowActions.querySelector('[data-action-toggle]')?.setAttribute('aria-expanded', 'false');
            const dropdown = rowActions.querySelector('[data-action-dropdown]');
            if (dropdown) {
                dropdown.hidden = true;
                dropdown.classList.remove('is-dropup');
                dropdown.style.top = '';
                dropdown.style.bottom = '';
                dropdown.style.left = '';
                dropdown.style.right = '';
                dropdown.style.position = '';
            }
        }

        form.reset();
        fillForm(btn.dataset);
        form.action = btn.dataset.updateUrl;
        setReadonlyOnEdit(form, true);

        let methodField = form.querySelector('[data-method-field]');
        if (!methodField) {
            methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.setAttribute('data-method-field', '');
            form.prepend(methodField);
        }
        methodField.value = 'PUT';

        if (title) title.textContent = editTitle;
        onEdit?.(form, btn);
        openModal(modal);
    });

    modal.querySelectorAll('[data-entity-modal-close]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const previousLabel = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Kaydediliyor...';
        }

        try {
            const formData = new FormData(form);
            const { data } = await window.axios.post(form.action, formData, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            closeModal(modal);
            showToast(data.message || 'Kayıt başarıyla kaydedildi.', 'success');
            onSuccess?.(data);
        } catch (error) {
            showToast(validationMessage(error), 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = previousLabel || 'Kaydet';
            }
        }
    });
}
