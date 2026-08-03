const MONTH_NAMES = [
    'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
    'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık',
];
const WEEK_DAYS = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
const STORAGE_KEY = 'takvim-gorunum';
const MAX_CACHE_MONTHS = 6;

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function loadVisibility(etkinlikEnabled) {
    const defaults = { kurs: true, etkinlik: etkinlikEnabled };
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return defaults;
        const parsed = JSON.parse(raw);
        return {
            kurs: parsed.kurs !== false,
            etkinlik: etkinlikEnabled ? parsed.etkinlik !== false : false,
        };
    } catch {
        return defaults;
    }
}

function saveVisibility(state) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
        // ignore
    }
}

function monthKeyFromDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function dateFromMonthKey(ay) {
    const [y, m] = String(ay).split('-').map(Number);
    return new Date(y, (m || 1) - 1, 1);
}

export function initTakvimSayfasi() {
    const panel = document.querySelector('[data-takvim-sayfa]');
    if (!panel) return;

    const listeView = panel.querySelector('[data-takvim-liste]');
    const listeEmpty = panel.querySelector('[data-takvim-liste-empty]');
    const listeEmptyText = panel.querySelector('[data-takvim-liste-empty-text]');
    const listeTable = panel.querySelector('[data-takvim-liste-table]');
    const listeBody = panel.querySelector('[data-takvim-liste-body]');
    const aylikView = panel.querySelector('[data-takvim-aylik]');
    const bodyEl = panel.querySelector('[data-takvim-body]');
    const viewButtons = panel.querySelectorAll('[data-takvim-view]');
    const grid = panel.querySelector('[data-takvim-grid]');
    const monthLabel = panel.querySelector('[data-takvim-month-label]');
    const dayDetail = panel.querySelector('[data-takvim-day-detail]');
    const dayTitle = panel.querySelector('[data-takvim-day-title]');
    const dayList = panel.querySelector('[data-takvim-day-list]');
    const metaEl = panel.querySelector('[data-takvim-meta]');
    const pdfLink = panel.querySelector('[data-takvim-pdf]');
    const prevBtn = panel.querySelector('[data-takvim-prev]');
    const nextBtn = panel.querySelector('[data-takvim-next]');
    const pdfBase = panel.dataset.takvimPdfBase || pdfLink?.getAttribute('href') || '';
    const dataUrl = panel.dataset.takvimDataUrl || '';
    const etkinlikEnabled = panel.dataset.takvimEtkinlik === '1';
    const toggleKurs = panel.querySelector('[data-takvim-toggle="kurs"]');
    const toggleEtkinlik = panel.querySelector('[data-takvim-toggle="etkinlik"]');

    const initialAy = panel.dataset.takvimAy || monthKeyFromDate(new Date());
    let items = [];
    try {
        items = JSON.parse(panel.dataset.takvimItems || '[]');
    } catch {
        items = [];
    }

    const monthCache = new Map([[initialAy, items]]);
    const cacheOrder = [initialAy];
    let abortController = null;
    let loadSeq = 0;

    const visibility = loadVisibility(etkinlikEnabled);
    if (toggleKurs) toggleKurs.checked = visibility.kurs;
    if (toggleEtkinlik) toggleEtkinlik.checked = visibility.etkinlik;

    const todayKey = new Date().toISOString().slice(0, 10);
    const urlParams = new URLSearchParams(window.location.search);
    const requestedView = urlParams.get('gorunum') === 'liste' ? 'liste' : 'aylik';
    const requestedGun = urlParams.get('gun');
    const initialGun = requestedGun && /^\d{4}-\d{2}-\d{2}$/.test(requestedGun) ? requestedGun : null;

    let currentView = requestedView;
    let selectedDay = initialGun;
    let cursor = dateFromMonthKey(initialAy);
    let loading = false;

    function visibleItems() {
        return items.filter((item) => {
            if (item.tip === 'etkinlik') return visibility.etkinlik;
            return visibility.kurs;
        });
    }

    function groupByDate(list) {
        return list.reduce((map, item) => {
            if (!item.tarih) return map;
            if (!map[item.tarih]) map[item.tarih] = [];
            map[item.tarih].push(item);
            return map;
        }, {});
    }

    function formatKey(year, month, day) {
        return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    function rememberMonth(ay, monthItems) {
        monthCache.set(ay, monthItems);
        const idx = cacheOrder.indexOf(ay);
        if (idx >= 0) {
            cacheOrder.splice(idx, 1);
        }
        cacheOrder.push(ay);
        while (cacheOrder.length > MAX_CACHE_MONTHS) {
            const old = cacheOrder.shift();
            if (old) {
                monthCache.delete(old);
            }
        }
    }

    function setLoading(isLoading) {
        loading = isLoading;
        bodyEl?.classList.toggle('is-loading', isLoading);
        if (prevBtn) prevBtn.disabled = isLoading;
        if (nextBtn) nextBtn.disabled = isLoading;
    }

    function syncPdfLink() {
        if (!pdfLink || !pdfBase) return;
        const ay = monthKeyFromDate(cursor);
        const url = new URL(pdfBase, window.location.origin);
        url.searchParams.set('ay', ay);
        pdfLink.href = url.pathname + url.search;
    }

    function updateMonthLabel() {
        if (!monthLabel) return;
        monthLabel.textContent = `${MONTH_NAMES[cursor.getMonth()]} ${cursor.getFullYear()}`;
    }

    function updateMeta(list) {
        if (!metaEl) return;
        const kursSayisi = new Set(list.filter((i) => i.tip === 'kurs').map((i) => i.kurs_id).filter(Boolean)).size;
        const etkinlikSayisi = new Set(list.filter((i) => i.tip === 'etkinlik').map((i) => i.etkinlik_id).filter(Boolean)).size;
        const parts = [`Kurs: ${kursSayisi.toLocaleString('tr-TR')}`];
        if (etkinlikEnabled) {
            parts.push(`Etkinlik: ${etkinlikSayisi.toLocaleString('tr-TR')}`);
        }
        parts.push(`Kayıt: ${list.length.toLocaleString('tr-TR')}`);
        metaEl.textContent = parts.join(' · ');
    }

    function statusFor(item, dateKey) {
        if (item.tip === 'etkinlik') {
            if (item.iptal_edildi) {
                return { text: 'İptal', cls: 'status-iptal' };
            }
            return {
                text: dateKey < todayKey ? 'Tamamlandı' : (dateKey === todayKey ? 'Bugün' : 'Planlandı'),
                cls: dateKey < todayKey ? 'status-tamamlanan' : 'status-hazirlik',
            };
        }
        if (item.iptal_edildi) {
            return { text: 'İptal Edildi', cls: 'status-iptal' };
        }
        if (item.yoklama_alindi) {
            return { text: 'Alındı', cls: 'status-tamamlanan' };
        }
        return {
            text: dateKey <= todayKey ? 'Bekliyor' : 'Planlandı',
            cls: 'status-hazirlik',
        };
    }

    function tipLabel(item) {
        return item.tip === 'etkinlik' ? 'Etkinlik' : 'Kurs';
    }

    function timeLabel(item) {
        if (item.tip === 'etkinlik') return 'Tüm gün';
        if (item.baslangic && item.bitis) return `${item.baslangic} – ${item.bitis}`;
        return '—';
    }

    function detailSecondary(item) {
        if (item.tip === 'etkinlik') {
            return item.sinif ? escapeHtml(item.sinif) : '—';
        }
        const parts = [];
        if (item.ders_saati) parts.push(`${escapeHtml(item.ders_saati)} saat`);
        parts.push(item.sinif ? escapeHtml(item.sinif) : 'Sınıf yok');
        return parts.join(' · ');
    }

    function showDayDetail(dateKey, byDate) {
        if (!dayDetail || !dayTitle || !dayList) return;
        const dayItems = byDate[dateKey] || [];
        if (!dayItems.length) {
            dayDetail.hidden = true;
            selectedDay = null;
            return;
        }

        selectedDay = dateKey;
        const [y, m, d] = dateKey.split('-').map(Number);
        const date = new Date(y, m - 1, d);
        dayTitle.textContent = date.toLocaleDateString('tr-TR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });

        dayList.innerHTML = dayItems.map((item) => {
            const status = statusFor(item, dateKey);
            const renk = item.renk || '#3699ff';
            const ad = escapeHtml(item.ad || item.kurs_adi || '');
            const link = item.url
                ? `<a href="${item.url}" class="kurs-no">${ad}</a>`
                : `<span>${ad}</span>`;
            return `<li>
                <span class="takvim-detay-renk" style="background:${escapeHtml(renk)}"></span>
                <strong>${escapeHtml(timeLabel(item))}</strong>
                <span class="takvim-detay-tip">${tipLabel(item)}</span>
                ${link}
                ${item.merkez ? `<span>${escapeHtml(item.merkez)}</span>` : ''}
                <span>${detailSecondary(item)}</span>
                <span class="status ${status.cls}">${status.text}</span>
            </li>`;
        }).join('');
        dayDetail.hidden = false;
    }

    function renderList(list) {
        if (!listeBody || !listeEmpty || !listeTable) return;

        if (!list.length) {
            listeTable.hidden = true;
            listeEmpty.hidden = false;
            if (listeEmptyText) {
                listeEmptyText.textContent = (!visibility.kurs && !visibility.etkinlik)
                    ? 'Görünüm filtrelerini açarak kayıtları görüntüleyebilirsiniz.'
                    : (selectedDay
                        ? 'Seçilen gün için gösterilecek kayıt bulunmuyor.'
                        : 'Bu ay için gösterilecek kayıt bulunmuyor.');
            }
            return;
        }

        listeEmpty.hidden = true;
        listeTable.hidden = false;

        listeBody.innerHTML = list.map((item) => {
            const isPast = item.tarih && item.tarih < todayKey;
            const isToday = item.tarih === todayKey;
            const rowClasses = [
                isToday ? 'is-today' : (isPast ? 'is-past' : ''),
                item.iptal_edildi ? 'is-cancelled' : '',
            ].filter(Boolean).join(' ');
            const status = statusFor(item, item.tarih || todayKey);
            const ad = escapeHtml(item.ad || item.kurs_adi || '—');
            const adHtml = item.url
                ? `<a href="${item.url}" class="kurs-no">${ad}</a>${item.kurs_no ? `<div class="takvim-kurs-no">${item.tip === 'etkinlik' ? 'Etkinlik' : 'Kurs'} #${escapeHtml(String(item.kurs_no))}</div>` : ''}`
                : ad;
            const tarih = item.tarih
                ? new Date(`${item.tarih}T00:00:00`).toLocaleDateString('tr-TR')
                : '—';

            return `<tr class="${rowClasses}" data-takvim-tip="${escapeHtml(item.tip || 'kurs')}">
                <td>${tarih}</td>
                <td>${escapeHtml(item.gun || '—')}</td>
                <td>${escapeHtml(timeLabel(item))}</td>
                <td><span class="takvim-tip-badge is-${escapeHtml(item.tip || 'kurs')}">${tipLabel(item)}</span></td>
                <td>${adHtml}</td>
                <td>${escapeHtml(item.merkez || '—')}</td>
                <td>${detailSecondary(item)}</td>
                <td><span class="status ${status.cls}">${status.text}</span></td>
            </tr>`;
        }).join('');
    }

    function renderMonth(byDate) {
        if (!grid) return;

        const year = cursor.getFullYear();
        const month = cursor.getMonth();

        const firstDay = new Date(year, month, 1);
        let startOffset = firstDay.getDay() - 1;
        if (startOffset < 0) startOffset = 6;

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells = [];

        WEEK_DAYS.forEach((label) => {
            cells.push(`<div class="takvim-cell takvim-cell-head">${label}</div>`);
        });

        for (let i = 0; i < startOffset; i++) {
            cells.push('<div class="takvim-cell is-empty"></div>');
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const key = formatKey(year, month, day);
            const dayItems = byDate[key] || [];
            const hasItems = dayItems.length > 0;
            const isToday = key === todayKey;
            const classes = [
                'takvim-cell',
                hasItems ? 'has-ders' : '',
                isToday ? 'is-today' : '',
                selectedDay === key ? 'is-selected' : '',
            ].filter(Boolean).join(' ');

            const lessonHtml = hasItems
                ? `<span class="takvim-day-lessons">${dayItems.map((item) => `
                    <span class="takvim-day-lesson${item.iptal_edildi ? ' is-cancelled' : ''}${item.tip === 'etkinlik' ? ' is-etkinlik' : ''}" style="border-left:3px solid ${escapeHtml(item.renk || '#3699ff')};padding-left:5px;">
                        <span class="takvim-day-lesson-time">${escapeHtml(item.tip === 'etkinlik' ? 'Etkinlik' : `${item.baslangic}–${item.bitis}`)}${item.iptal_edildi ? ' · İptal' : ''}</span>
                        <span class="takvim-day-lesson-name">${escapeHtml(item.ad || item.kurs_adi || '')}</span>
                    </span>
                `).join('')}</span>`
                : '';

            cells.push(`
                <button type="button" class="${classes}" data-takvim-day="${key}" ${hasItems ? '' : 'disabled'}>
                    <span class="takvim-day-num">${day}</span>
                    ${lessonHtml}
                </button>
            `);
        }

        grid.innerHTML = cells.join('');

        if (selectedDay) {
            if (byDate[selectedDay]?.length) {
                showDayDetail(selectedDay, byDate);
            } else {
                dayDetail.hidden = true;
                selectedDay = null;
            }
        }
    }

    function refresh() {
        updateMonthLabel();
        syncPdfLink();
        const list = visibleItems();
        const byDate = groupByDate(list);
        updateMeta(list);
        if (currentView === 'liste') {
            const dayFiltered = selectedDay
                ? list.filter((item) => item.tarih === selectedDay)
                : list;
            renderList(dayFiltered);
        } else {
            renderMonth(byDate);
        }
    }

    async function ensureMonth(ay) {
        if (monthCache.has(ay)) {
            items = monthCache.get(ay) || [];
            refresh();
            return;
        }

        if (!dataUrl || !window.axios) {
            items = [];
            refresh();
            return;
        }

        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();
        const seq = ++loadSeq;
        setLoading(true);

        try {
            const url = new URL(dataUrl, window.location.origin);
            url.searchParams.set('ay', ay);
            const { data } = await window.axios.get(url.pathname + url.search, {
                signal: abortController.signal,
                headers: { Accept: 'application/json' },
            });

            if (seq !== loadSeq) return;

            const monthItems = Array.isArray(data?.items) ? data.items : [];
            rememberMonth(ay, monthItems);
            items = monthItems;
            refresh();
        } catch (error) {
            if (error?.code === 'ERR_CANCELED' || error?.name === 'CanceledError' || error?.name === 'AbortError') {
                return;
            }
            if (seq !== loadSeq) return;
            items = [];
            refresh();
            if (typeof window.showToast === 'function') {
                window.showToast('Takvim verisi yüklenemedi.', 'error');
            }
        } finally {
            if (seq === loadSeq) {
                setLoading(false);
            }
        }
    }

    async function goToMonth(date) {
        cursor = new Date(date.getFullYear(), date.getMonth(), 1);
        selectedDay = null;
        if (dayDetail) dayDetail.hidden = true;
        updateMonthLabel();
        syncPdfLink();
        await ensureMonth(monthKeyFromDate(cursor));
    }

    function setView(name) {
        currentView = name;
        viewButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.takvimView === name);
        });
        if (listeView) listeView.hidden = name !== 'liste';
        if (aylikView) aylikView.hidden = name !== 'aylik';
        refresh();
    }

    viewButtons.forEach((btn) => {
        btn.addEventListener('click', () => setView(btn.dataset.takvimView || 'aylik'));
    });

    prevBtn?.addEventListener('click', () => {
        if (loading) return;
        goToMonth(new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1));
    });

    nextBtn?.addEventListener('click', () => {
        if (loading) return;
        goToMonth(new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1));
    });

    grid?.addEventListener('click', (event) => {
        const cell = event.target.closest('[data-takvim-day]');
        if (!cell || cell.disabled) return;
        const byDate = groupByDate(visibleItems());
        showDayDetail(cell.dataset.takvimDay, byDate);
        grid.querySelectorAll('.takvim-cell.is-selected').forEach((el) => el.classList.remove('is-selected'));
        cell.classList.add('is-selected');
    });

    function onToggleChange() {
        visibility.kurs = toggleKurs ? toggleKurs.checked : true;
        visibility.etkinlik = toggleEtkinlik ? toggleEtkinlik.checked : false;
        saveVisibility(visibility);
        refresh();
    }

    toggleKurs?.addEventListener('change', onToggleChange);
    toggleEtkinlik?.addEventListener('change', onToggleChange);

    setView(requestedView);
}
