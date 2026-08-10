function selectionInside(surface) {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) {
        return null;
    }

    const node = sel.anchorNode;
    if (!node || !surface.contains(node)) {
        return null;
    }

    return sel;
}

function applyFontSize(surface, pt) {
    const sel = selectionInside(surface);
    if (!sel) {
        return;
    }

    const range = sel.getRangeAt(0);
    const span = document.createElement('span');
    span.style.fontSize = `${pt}pt`;

    if (range.collapsed) {
        span.appendChild(document.createTextNode('\u200b'));
        range.insertNode(span);
        const next = document.createRange();
        next.setStart(span.firstChild, 1);
        next.collapse(true);
        sel.removeAllRanges();
        sel.addRange(next);
        return;
    }

    try {
        range.surroundContents(span);
    } catch {
        const contents = range.extractContents();
        span.appendChild(contents);
        range.insertNode(span);
    }

    const next = document.createRange();
    next.selectNodeContents(span);
    sel.removeAllRanges();
    sel.addRange(next);
}

function normalizeUrl(raw) {
    const value = String(raw ?? '').trim();
    if (!value) {
        return '';
    }
    if (/^(https?:|mailto:|tel:|\/|#)/i.test(value)) {
        return value;
    }

    return `https://${value}`;
}

function closestAnchor(node, surface) {
    let current = node?.nodeType === Node.TEXT_NODE ? node.parentElement : node;
    while (current && current !== surface) {
        if (current.tagName === 'A') {
            return current;
        }
        current = current.parentElement;
    }

    return null;
}

function ensureLinkModal() {
    let modal = document.getElementById('rich-editor-link-modal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'rich-editor-link-modal';
    modal.className = 'confirm-modal';
    modal.hidden = true;
    modal.innerHTML = `
        <div class="confirm-modal-backdrop" data-rich-link-close></div>
        <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="rich-editor-link-title">
            <div class="confirm-modal-header">
                <h3 id="rich-editor-link-title" class="confirm-modal-title">Bağlantı ekle</h3>
                <button type="button" class="confirm-modal-x" data-rich-link-close aria-label="Kapat">&times;</button>
            </div>
            <form data-rich-link-form>
                <div class="confirm-modal-body">
                    <div class="form-group">
                        <label for="rich-editor-link-text">Başlık</label>
                        <input id="rich-editor-link-text" type="text" class="form-control" name="title" required maxlength="255" autocomplete="off">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="rich-editor-link-url">URL</label>
                        <input id="rich-editor-link-url" type="text" class="form-control" name="url" required maxlength="2000" placeholder="https://" inputmode="url" autocomplete="off">
                    </div>
                </div>
                <div class="confirm-modal-footer">
                    <button type="button" class="btn-back" data-rich-link-close>Vazgeç</button>
                    <button type="submit" class="btn-cta">
                        <span class="btn-cta-text">Ekle</span>
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    return modal;
}

function openLinkModal({ title = '', url = '' } = {}) {
    const modal = ensureLinkModal();
    const form = modal.querySelector('[data-rich-link-form]');
    const titleInput = modal.querySelector('#rich-editor-link-text');
    const urlInput = modal.querySelector('#rich-editor-link-url');

    titleInput.value = title;
    urlInput.value = url;
    modal.hidden = false;
    document.body.classList.add('modal-open');

    window.setTimeout(() => {
        (title.trim() ? urlInput : titleInput).focus();
        (title.trim() ? urlInput : titleInput).select?.();
    }, 0);

    return new Promise((resolve) => {
        const close = (result = null) => {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            form.removeEventListener('submit', onSubmit);
            modal.querySelectorAll('[data-rich-link-close]').forEach((el) => {
                el.removeEventListener('click', onCancel);
            });
            document.removeEventListener('keydown', onKeyDown);
            resolve(result);
        };

        const onCancel = () => close(null);
        const onSubmit = (event) => {
            event.preventDefault();
            const nextTitle = titleInput.value.trim();
            const nextUrl = normalizeUrl(urlInput.value);
            if (!nextTitle || !nextUrl) {
                return;
            }
            close({ title: nextTitle, url: nextUrl });
        };
        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close(null);
            }
        };

        form.addEventListener('submit', onSubmit);
        modal.querySelectorAll('[data-rich-link-close]').forEach((el) => {
            el.addEventListener('click', onCancel);
        });
        document.addEventListener('keydown', onKeyDown);
    });
}

function insertLink(surface, { title, url }, savedRange, existingAnchor) {
    surface.focus();

    const sel = window.getSelection();
    if (savedRange && sel) {
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }

    if (existingAnchor && surface.contains(existingAnchor)) {
        existingAnchor.href = url;
        existingAnchor.textContent = title;
        existingAnchor.target = '_blank';
        existingAnchor.rel = 'noopener noreferrer';
        return;
    }

    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.target = '_blank';
    anchor.rel = 'noopener noreferrer';
    anchor.textContent = title;

    if (savedRange && !savedRange.collapsed) {
        savedRange.deleteContents();
        savedRange.insertNode(anchor);
    } else if (savedRange) {
        savedRange.insertNode(anchor);
    } else {
        surface.appendChild(anchor);
    }

    if (sel) {
        const next = document.createRange();
        next.setStartAfter(anchor);
        next.collapse(true);
        sel.removeAllRanges();
        sel.addRange(next);
    }
}

export function initRichTextEditors(scope = document) {
    scope.querySelectorAll('[data-rich-editor]').forEach((root) => {
        if (root.dataset.initialized === '1') {
            return;
        }
        root.dataset.initialized = '1';

        const surface = root.querySelector('[data-rich-surface]');
        const input = root.querySelector('[data-rich-input]');
        const toolbar = root.querySelector('[data-rich-toolbar]');
        const disabled = root.hasAttribute('data-rich-editor-disabled');

        if (!surface || !input) {
            return;
        }

        const sync = () => {
            input.value = surface.innerHTML;
        };

        sync();

        if (disabled) {
            surface.setAttribute('contenteditable', 'false');
            return;
        }

        surface.addEventListener('input', sync);
        surface.addEventListener('blur', sync);

        toolbar?.querySelectorAll('[data-cmd]').forEach((btn) => {
            btn.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const cmd = btn.getAttribute('data-cmd');
                if (!cmd) return;
                surface.focus();
                document.execCommand(cmd, false);
                sync();
            });
        });

        const linkBtn = toolbar?.querySelector('[data-rich-link]');
        linkBtn?.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });
        linkBtn?.addEventListener('click', async (event) => {
            event.preventDefault();
            const sel = selectionInside(surface);
            const range = sel?.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
            const existingAnchor = closestAnchor(sel?.anchorNode, surface);
            const selectedText = range && !range.collapsed ? range.toString().trim() : '';

            const result = await openLinkModal({
                title: existingAnchor?.textContent?.trim() || selectedText,
                url: existingAnchor?.getAttribute('href') || '',
            });

            if (!result) {
                surface.focus();
                return;
            }

            insertLink(surface, result, range, existingAnchor);
            sync();
            surface.focus();
        });

        const fontSizeSelect = toolbar?.querySelector('[data-font-size]');
        const defaultFontSize = fontSizeSelect?.dataset.fontSizeDefault || '11';
        if (fontSizeSelect && !fontSizeSelect.value) {
            fontSizeSelect.value = defaultFontSize;
        }
        fontSizeSelect?.addEventListener('mousedown', (event) => {
            // Keep editor selection when opening the dropdown.
            event.stopPropagation();
        });
        fontSizeSelect?.addEventListener('change', () => {
            const value = fontSizeSelect.value || defaultFontSize;
            if (!value) {
                return;
            }
            surface.focus();
            applyFontSize(surface, value);
            sync();
            fontSizeSelect.value = value;
        });

        root.closest('form')?.addEventListener('submit', sync);
    });
}
