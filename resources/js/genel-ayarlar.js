function bindLogoPreview(root, inputSel, imgSel, emptySel, captionSel) {
    const input = root.querySelector(inputSel);
    const img = root.querySelector(imgSel);
    const empty = root.querySelector(emptySel);
    const caption = root.querySelector(captionSel);

    if (!input || !img) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) {
            return;
        }

        const url = URL.createObjectURL(file);
        img.src = url;
        img.hidden = false;
        if (empty) {
            empty.hidden = true;
        }
        if (caption) {
            caption.textContent = file.name;
        }
    });
}

export function initGenelAyarlarPage() {
    const root = document.querySelector('[data-genel-ayarlar]');
    if (!root) {
        return;
    }

    bindLogoPreview(
        root,
        '[data-genel-logo-input]',
        '[data-genel-logo-img]',
        '[data-genel-logo-empty]',
        '[data-genel-logo-caption]',
    );

    bindLogoPreview(
        root,
        '[data-genel-sidebar-logo-input]',
        '[data-genel-sidebar-logo-img]',
        '[data-genel-sidebar-logo-empty]',
        '[data-genel-sidebar-logo-caption]',
    );

    bindLogoPreview(
        root,
        '[data-genel-header-logo-input]',
        '[data-genel-header-logo-img]',
        '[data-genel-header-logo-empty]',
        '[data-genel-header-logo-caption]',
    );

    bindLogoPreview(
        root,
        '[data-genel-favicon-input]',
        '[data-genel-favicon-img]',
        '[data-genel-favicon-empty]',
        '[data-genel-favicon-caption]',
    );

    const arkaplanInput = root.querySelector('[data-genel-sidebar-logo-arkaplan]');
    const arkaplanSeffaf = root.querySelector('[data-genel-sidebar-logo-arkaplan-seffaf]');
    const arkaplanFrame = root.querySelector('[data-genel-sidebar-logo-frame]');

    const syncArkaplanPreview = () => {
        if (!arkaplanFrame) {
            return;
        }

        const seffaf = Boolean(arkaplanSeffaf?.checked);
        if (arkaplanInput && arkaplanSeffaf && !arkaplanSeffaf.disabled) {
            arkaplanInput.disabled = seffaf;
        }

        arkaplanFrame.style.background = seffaf
            ? 'transparent'
            : (arkaplanInput?.value || '#ffffff');
    };

    arkaplanInput?.addEventListener('input', syncArkaplanPreview);
    arkaplanSeffaf?.addEventListener('change', syncArkaplanPreview);
    syncArkaplanPreview();
}
