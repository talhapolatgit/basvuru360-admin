/**
 * Tıklanabilir profil avatarı: fotoğraf seçilince anında (AJAX) yükler,
 * sağ alttaki "x" ile onay sorup siler.
 *
 * Kullanım:
 * <div data-avatar-preview data-upload-url="..." data-delete-url="...">
 *   <label class="profil-avatar profil-avatar-edit">
 *     <img data-avatar-img [hidden]>
 *     <span data-avatar-initials>AB</span>
 *     <input type="file" data-avatar-input hidden>
 *   </label>
 *   <button type="button" data-avatar-remove-btn [hidden]>x</button>
 * </div>
 */
import { showToast } from './toast';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function errorMessage(error) {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    if (typeof data?.message === 'string' && data.message) return data.message;
    return 'İşlem sırasında bir hata oluştu.';
}

function initOne(wrap) {
    if (wrap.dataset.avatarInitialized === '1') return;
    wrap.dataset.avatarInitialized = '1';

    const input = wrap.querySelector('[data-avatar-input]');
    const img = wrap.querySelector('[data-avatar-img]');
    const initials = wrap.querySelector('[data-avatar-initials]');
    const removeBtn = wrap.querySelector('[data-avatar-remove-btn]');
    const uploadUrl = wrap.dataset.uploadUrl;
    const deleteUrl = wrap.dataset.deleteUrl;

    let busy = false;

    input?.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        if (!file || busy || !uploadUrl) return;

        busy = true;
        wrap.classList.add('is-busy');
        try {
            const formData = new FormData();
            formData.append('profil_foto', file);
            const { data } = await window.axios.post(uploadUrl, formData, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (img && data.url) {
                img.src = data.url;
                img.hidden = false;
            }
            if (initials) initials.hidden = true;
            if (removeBtn) removeBtn.hidden = false;
            showToast(data.message || 'Profil fotoğrafı güncellendi.', 'success');
        } catch (error) {
            showToast(errorMessage(error), 'error');
        } finally {
            input.value = '';
            busy = false;
            wrap.classList.remove('is-busy');
        }
    });

    removeBtn?.addEventListener('click', async () => {
        if (busy || !deleteUrl) return;
        if (!window.confirm('Profil fotoğrafı kaldırılacak. Onaylıyor musunuz?')) return;

        busy = true;
        wrap.classList.add('is-busy');
        try {
            const { data } = await window.axios.delete(deleteUrl, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (img) {
                img.hidden = true;
                img.src = '';
            }
            if (initials) initials.hidden = false;
            removeBtn.hidden = true;
            showToast(data.message || 'Profil fotoğrafı kaldırıldı.', 'success');
        } catch (error) {
            showToast(errorMessage(error), 'error');
        } finally {
            busy = false;
            wrap.classList.remove('is-busy');
        }
    });
}

export function initAvatarUploaders() {
    document.querySelectorAll('[data-avatar-preview]').forEach(initOne);
}
