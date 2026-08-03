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

        const fontSizeSelect = toolbar?.querySelector('[data-font-size]');
        fontSizeSelect?.addEventListener('mousedown', (event) => {
            // Keep editor selection when opening the dropdown.
            event.stopPropagation();
        });
        fontSizeSelect?.addEventListener('change', () => {
            const value = fontSizeSelect.value;
            if (!value) {
                return;
            }
            surface.focus();
            applyFontSize(surface, value);
            sync();
            fontSizeSelect.value = '';
        });

        root.closest('form')?.addEventListener('submit', sync);
    });
}
