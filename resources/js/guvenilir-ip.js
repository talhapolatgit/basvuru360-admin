function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (![...document.querySelectorAll('.confirm-modal')].some((el) => !el.hidden)) {
        document.body.classList.remove('modal-open');
    }
}

function closeRowActions() {
    document.querySelectorAll('[data-row-actions].is-open').forEach((wrap) => {
        wrap.classList.remove('is-open');
        wrap.querySelector('[data-action-toggle]')?.setAttribute('aria-expanded', 'false');
        const dropdown = wrap.querySelector('[data-action-dropdown]');
        if (dropdown) dropdown.hidden = true;
    });
}

const IPV4 = /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/;

function ipHatasi(value) {
    if (value === '') return 'IP adresi zorunludur.';

    const [adres, onek, fazla] = value.split('/');
    if (fazla !== undefined) return 'Geçerli bir IP adresi veya CIDR aralığı girin.';

    const ipv4 = IPV4.test(adres);
    const ipv6 = !ipv4 && adres.includes(':') && /^[0-9a-fA-F:.]+$/.test(adres);
    if (!ipv4 && !ipv6) return 'Geçerli bir IP adresi veya CIDR aralığı girin.';

    if (onek === undefined) return null;
    if (!/^\d+$/.test(onek)) return 'CIDR önek uzunluğu sayı olmalıdır.';

    const n = Number(onek);
    if (ipv4 && (n < 16 || n > 32)) return 'IPv4 aralığı /16 ile /32 arasında olmalıdır.';
    if (ipv6 && (n < 48 || n > 128)) return 'IPv6 aralığı /48 ile /128 arasında olmalıdır.';

    return null;
}

export function initGuvenilirIpPage() {
    const formModal = document.getElementById('guvenilir-ip-form-modal');
    if (!formModal) return;

    const deleteModal = document.getElementById('guvenilir-ip-delete-modal');
    const form = formModal.querySelector('[data-guvenilir-ip-form]');
    const title = formModal.querySelector('[data-guvenilir-ip-title]');
    const methodInput = form.querySelector('[data-guvenilir-ip-method]');
    const kayitIdInput = form.querySelector('[data-guvenilir-ip-kayit-id]');
    const ipInput = form.querySelector('[data-guvenilir-ip-input]');
    const aciklamaInput = form.querySelector('[data-guvenilir-ip-aciklama]');
    const ipError = form.querySelector('[data-guvenilir-ip-error]');
    const deleteForm = deleteModal?.querySelector('[data-guvenilir-ip-delete-form]');
    const deleteIp = deleteModal?.querySelector('[data-guvenilir-ip-delete-ip]');

    const setIpError = (message) => {
        ipError.textContent = message ?? '';
        ipError.hidden = !message;
        ipInput.classList.toggle('is-invalid', Boolean(message));
    };

    const clearErrors = () => {
        setIpError(null);
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.form-error:not([data-guvenilir-ip-error])').forEach((el) => el.remove());
    };

    const openForm = ({ action, edit, id = '', ip = '', aciklama = '' }) => {
        clearErrors();
        form.action = action;
        methodInput.disabled = !edit;
        kayitIdInput.value = id;
        title.textContent = edit ? 'IP Adresini Düzenle' : 'Yeni Güvenilir IP Adresi';
        ipInput.value = ip;
        aciklamaInput.value = aciklama;
        openModal(formModal);
        (ip ? aciklamaInput : ipInput).focus();
    };

    document.querySelectorAll('[data-guvenilir-ip-create]').forEach((button) => {
        button.addEventListener('click', () => {
            openForm({ action: form.dataset.createUrl, edit: false, ip: button.dataset.ip ?? '' });
        });
    });

    document.querySelectorAll('[data-guvenilir-ip-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            closeRowActions();
            openForm({
                action: button.dataset.updateUrl,
                edit: true,
                id: button.dataset.id,
                ip: button.dataset.ip,
                aciklama: button.dataset.aciklama ?? '',
            });
        });
    });

    document.querySelectorAll('[data-guvenilir-ip-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            closeRowActions();
            if (!deleteForm) return;
            deleteForm.action = button.dataset.deleteUrl;
            deleteIp.textContent = button.dataset.ip;
            openModal(deleteModal);
        });
    });

    [formModal, deleteModal].forEach((modal) => {
        modal?.querySelectorAll('[data-guvenilir-ip-close]').forEach((el) => {
            el.addEventListener('click', () => closeModal(modal));
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeModal(formModal);
        closeModal(deleteModal);
    });

    ipInput.addEventListener('input', () => {
        if (!ipError.hidden) setIpError(null);
    });

    form.addEventListener('submit', (event) => {
        ipInput.value = ipInput.value.trim();
        const hata = ipHatasi(ipInput.value);
        if (hata) {
            event.preventDefault();
            setIpError(hata);
            ipInput.focus();
            return;
        }
        form.querySelector('button[type="submit"]').disabled = true;
    });

    deleteForm?.addEventListener('submit', () => {
        deleteForm.querySelector('button[type="submit"]').disabled = true;
    });

    if (formModal.dataset.openOnLoad === '1') {
        openModal(formModal);
        ipInput.focus();
    }
}
