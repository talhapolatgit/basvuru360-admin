import './bootstrap';
import { initKursTable } from './kurs-table';
import { initBasvuruTable } from './basvuru-table';
import { initSidebar } from './sidebar';
import { consumeFlashToasts, showToast } from './toast';
import { initKursDetailActions, initBasvuruActionModals, initBasvuruMesajModallari, initKursYedekSiraModal } from './kurs-detail';
import { initBasvuruEvraklarModal, initBasvuruEvraklarPanel } from './basvuru-evraklar-modal';
import { initMerkezlerPage, initAlanlarPage, initBranslarPage, initEgitmenlerPage, initKullanicilarPage, initKisilerPage, initMerkezYetkileriPage, initEtkinliklerPage, initEtkinlikBasvurulariPage, initKresDonemlerPage, initKresOkullarTanimPage, initKresGruplarTanimPage } from './lookup-pages';
import { initEtkinlikDetailPage, initBasvuruMesajModallari as initEtkinlikBasvuruMesajModallari, initEtkinlikBasvuruDurumModal, initEtkinlikYedekSiraModal } from './etkinlik-detail';
import { initSabitTanimlarPage } from './sabit-tanimlar';
import { initRichTextEditors } from './rich-text-editor';
import { initEntegrasyonlarPage } from './entegrasyonlar';
import { initGenelAyarlarPage } from './genel-ayarlar';
import { initPortalAyarlarPage } from './portal-ayarlar';
import { initEgitmenDetailPage } from './egitmen-detail';
import { initMerkezDetailPage } from './merkez-detail';
import { initTakvimSayfasi } from './takvim-page';
import { initDetailTables } from './detail-table';
import { initLoginPage } from './login';
import { initAvatarUploaders } from './avatar-uploader';
import { initKisiForm } from './kisi-form';
import { initBasvuruCreatePage } from './basvuru-create';
import { initEtkinlikBasvuruCreatePage } from './etkinlik-basvuru-create';
import { initKresGrupDetailPage } from './kres-grup-detail';

window.showToast = showToast;

function closeAllSearchableSelects(except = null) {
    document.querySelectorAll('[data-searchable-select]').forEach((select) => {
        if (except && select === except) {
            return;
        }

        select.classList.remove('open');
        select.querySelector('[data-select-dropdown]')?.classList.remove('open');
    });

    document.querySelectorAll('[data-chip-select]').forEach((select) => {
        if (except && select === except) {
            return;
        }

        select.classList.remove('open');
        select.querySelector('[data-chip-select-dropdown]')?.classList.remove('open');
        select.querySelector('[data-chip-select-toggle]')?.setAttribute('aria-expanded', 'false');
    });
}

function initSearchableSelects() {
    document.querySelectorAll('[data-searchable-select]').forEach((select) => {
        if (select.dataset.initialized === '1') {
            return;
        }

        select.dataset.initialized = '1';

        const toggle = select.querySelector('[data-select-toggle]');
        const dropdown = select.querySelector('[data-select-dropdown]');
        const searchInput = select.querySelector('[data-select-search]');
        const valueInput = select.querySelector('[data-select-value]');
        const label = select.querySelector('[data-select-label]');
        const options = select.querySelectorAll('.select-option');

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = !dropdown.classList.contains('open');

            closeAllSearchableSelects();
            document.getElementById('columnDropdown')?.classList.remove('open');
            document.getElementById('gunlerDropdown')?.classList.remove('open');

            if (willOpen) {
                select.classList.add('open');
                dropdown.classList.add('open');
                if (searchInput) {
                    searchInput.value = '';
                    options.forEach((option) => option.classList.remove('hidden'));
                    setTimeout(() => searchInput.focus(), 0);
                }
            }
        });

        searchInput?.addEventListener('click', (event) => event.stopPropagation());
        searchInput?.addEventListener('input', () => {
            const query = searchInput.value.trim().toLocaleLowerCase('tr-TR');

            options.forEach((option) => {
                const text = (option.dataset.label || option.textContent || '').toLocaleLowerCase('tr-TR');
                option.classList.toggle('hidden', query !== '' && !text.includes(query));
            });
        });

        options.forEach((option) => {
            option.addEventListener('click', (event) => {
                event.stopPropagation();

                const value = option.dataset.value ?? '';
                const text = option.dataset.label ?? option.textContent.trim();

                if (valueInput) {
                    valueInput.value = value;
                    valueInput.dispatchEvent(new Event('change', { bubbles: true }));
                    valueInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (label) {
                    label.textContent = text;
                }

                options.forEach((item) => item.classList.remove('selected'));
                option.classList.add('selected');

                const isEmpty = String(value).trim() === '';
                select.classList.toggle('is-invalid', select.dataset.required === '1' && isEmpty);
                toggle?.classList.toggle('is-invalid', select.dataset.required === '1' && isEmpty);
                toggle?.classList.toggle('is-empty', isEmpty);

                select.classList.remove('open');
                dropdown.classList.remove('open');
            });
        });
    });
}

function initChipSelects() {
    document.querySelectorAll('[data-chip-select]').forEach((select) => {
        if (select.dataset.initialized === '1') {
            return;
        }

        select.dataset.initialized = '1';

        const toggle = select.querySelector('[data-chip-select-toggle]');
        const dropdown = select.querySelector('[data-chip-select-dropdown]');
        const searchInput = select.querySelector('[data-chip-select-search]');
        const chipsWrap = select.querySelector('[data-chip-select-chips]');
        const placeholder = select.querySelector('[data-chip-select-placeholder]');
        const optionsRoot = select.querySelector('[data-chip-select-options]');
        const inputName = select.dataset.inputName || 'roller[]';
        const placeholderText = select.dataset.placeholder || 'Seçin';

        const syncPlaceholder = () => {
            const hasChips = chipsWrap?.querySelectorAll('[data-chip-id]').length > 0;
            if (placeholder) {
                placeholder.hidden = hasChips;
            }
        };

        const hideOption = (value) => {
            const option = optionsRoot?.querySelector(`.select-option[data-value="${CSS.escape(String(value))}"]`);
            if (option) {
                option.hidden = true;
                option.classList.add('is-selected');
            }
        };

        const showOption = (value) => {
            const option = optionsRoot?.querySelector(`.select-option[data-value="${CSS.escape(String(value))}"]`);
            if (option) {
                option.hidden = false;
                option.classList.remove('is-selected');
            }
        };

        const addChip = (value, label) => {
            if (!chipsWrap || chipsWrap.querySelector(`[data-chip-id="${CSS.escape(String(value))}"]`)) {
                return;
            }

            const chip = document.createElement('span');
            chip.className = 'chip-select-chip';
            chip.dataset.chipId = String(value);
            chip.innerHTML =
                `<input type="hidden" name="${inputName.replace(/"/g, '&quot;')}" value="${String(value).replace(/"/g, '&quot;')}">` +
                `<span class="chip-select-chip-label"></span>` +
                `<button type="button" class="chip-select-chip-remove" data-chip-remove aria-label="Kaldır">&times;</button>`;
            chip.querySelector('.chip-select-chip-label').textContent = label;
            chipsWrap.appendChild(chip);
            hideOption(value);
            syncPlaceholder();
        };

        const removeChip = (chip) => {
            const value = chip?.dataset.chipId;
            chip?.remove();
            if (value !== undefined) {
                showOption(value);
            }
            syncPlaceholder();
        };

        toggle?.addEventListener('click', (event) => {
            if (event.target.closest('[data-chip-remove]')) {
                return;
            }

            event.stopPropagation();
            const willOpen = !dropdown?.classList.contains('open');

            closeAllSearchableSelects();
            document.getElementById('columnDropdown')?.classList.remove('open');
            document.getElementById('gunlerDropdown')?.classList.remove('open');

            if (willOpen && dropdown) {
                select.classList.add('open');
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

        select.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-chip-remove]');
            if (!removeBtn) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            removeChip(removeBtn.closest('[data-chip-id]'));
        });

        searchInput?.addEventListener('click', (event) => event.stopPropagation());
        searchInput?.addEventListener('input', () => {
            const query = searchInput.value.trim().toLocaleLowerCase('tr-TR');

            optionsRoot?.querySelectorAll('.select-option').forEach((option) => {
                if (option.classList.contains('is-selected') || option.hidden) {
                    return;
                }
                const text = (option.dataset.label || option.textContent || '').toLocaleLowerCase('tr-TR');
                option.classList.toggle('hidden', query !== '' && !text.includes(query));
            });
        });

        optionsRoot?.querySelectorAll('.select-option').forEach((option) => {
            option.addEventListener('click', (event) => {
                event.stopPropagation();
                if (option.classList.contains('is-selected') || option.hidden) {
                    return;
                }

                const value = option.dataset.value ?? '';
                const label = option.dataset.label ?? option.textContent.trim();
                if (value === '') {
                    return;
                }

                addChip(value, label);
                if (searchInput) {
                    searchInput.value = '';
                    optionsRoot.querySelectorAll('.select-option').forEach((item) => item.classList.remove('hidden'));
                }
            });
        });

        syncPlaceholder();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initSearchableSelects();
    initChipSelects();
    initKursTable();
    initBasvuruTable();
    initKursDetailActions();
    initBasvuruActionModals();
    initKursYedekSiraModal();
    initBasvuruEvraklarModal();
    initBasvuruEvraklarPanel();
    initBasvuruMesajModallari();
    initEtkinlikBasvuruMesajModallari();
    initMerkezlerPage();
    initAlanlarPage();
    initBranslarPage();
    initEgitmenlerPage();
    initKullanicilarPage();
    initKisilerPage();
    initMerkezYetkileriPage();
    initEtkinliklerPage();
    initEtkinlikBasvurulariPage();
    initKresDonemlerPage();
    initKresOkullarTanimPage();
    initKresGruplarTanimPage();
    initEtkinlikDetailPage();
    initEtkinlikBasvuruDurumModal();
    initEtkinlikYedekSiraModal();
    initSabitTanimlarPage();
    initRichTextEditors();
    initEntegrasyonlarPage();
    initGenelAyarlarPage();
    initPortalAyarlarPage();
    initEgitmenDetailPage();
    initMerkezDetailPage();
    initDetailTables();
    initTakvimSayfasi();
    initLoginPage();
    initAvatarUploaders();
    initKisiForm();
    initBasvuruCreatePage();
    initEtkinlikBasvuruCreatePage();
    initKresGrupDetailPage();
    consumeFlashToasts();

    document.addEventListener('click', () => {
        closeAllSearchableSelects();
    });
});
