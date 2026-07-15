document.addEventListener('DOMContentLoaded', () => {
    const closeHiddenEditorRows = (table) => {
        table.querySelectorAll('.student-editor-row.open').forEach((row) => {
            const previous = row.previousElementSibling;
            if (!previous || previous.hidden) {
                row.classList.remove('open');
            }
        });
    };

    const updateStudentCount = (table) => {
        const countEl = document.getElementById('alumnes-count');
        if (!countEl) return;

        const visible = table.querySelectorAll('tbody tr[data-class]:not([hidden])').length;
        countEl.textContent = visible + ' usuaris';
    };

    const normalizeFilterValue = (value) => {
        return (value || '').trim().replace(/\s+/g, ' ').toLocaleLowerCase('ca');
    };

    const geoMapEl = document.querySelector('[data-geo-map]');
    let geoMap = null;

    const initGeoMap = () => {
        if (!geoMapEl || geoMap || !window.L) return;

        let points = [];
        try {
            points = JSON.parse(geoMapEl.getAttribute('data-geo-points') || '[]');
        } catch (error) {
            points = [];
        }

        geoMap = window.L.map(geoMapEl, {
            scrollWheelZoom: false,
        });

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(geoMap);

        const bounds = [];
        points.forEach((point) => {
            const lat = Number(point.lat);
            const lng = Number(point.lng);
            const total = Number(point.total) || 0;
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const radius = Math.max(8, Math.min(22, 8 + Math.sqrt(total) * 2));
            const marker = window.L.circleMarker([lat, lng], {
                color: '#2f5d3a',
                fillColor: '#5f8e69',
                fillOpacity: 0.55,
                radius,
                weight: 2,
            }).addTo(geoMap);

            marker.bindPopup(`<strong>${point.country_code || ''}</strong><br>${point.region || 'Desconegut'}<br>${total} visites`);
            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            geoMap.fitBounds(bounds, { padding: [32, 32], maxZoom: 4 });
        } else {
            geoMap.setView([20, 0], 2);
        }
    };

    const refreshGeoMap = () => {
        if (!geoMapEl) return;
        initGeoMap();
        if (!geoMap) return;

        window.requestAnimationFrame(() => {
            geoMap.invalidateSize();
        });
    };

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Confirmes aquesta acció?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-target]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const targetId = button.getAttribute('data-target');
            if (!targetId) return;
            let row = document.getElementById(targetId);
            if (!row) row = button.closest('tr')?.nextElementSibling;
            if (!row) return;
            const parentRow = button.closest('tr');
            if (parentRow?.hidden) return;

            const isOpen = row.classList.contains('open');
            document.querySelectorAll('.student-editor-row.open').forEach((r) => r.classList.remove('open'));
            if (!isOpen) row.classList.add('open');
        });
    });

    document.querySelectorAll('[data-nav-group-toggle]').forEach((toggle) => {
        const targetId = toggle.getAttribute('data-nav-group-toggle');
        const submenu = targetId ? document.getElementById(targetId) : null;
        if (!submenu) return;

        toggle.addEventListener('click', () => {
            const isHidden = submenu.hasAttribute('hidden');
            if (isHidden) {
                submenu.removeAttribute('hidden');
            } else {
                submenu.setAttribute('hidden', '');
            }
            toggle.setAttribute('aria-expanded', String(isHidden));
        });
    });

    document.querySelectorAll('.collapse-toggle').forEach((collapseBtn) => {
        const targetId = collapseBtn.getAttribute('data-collapse');
        const collapsibleContent = targetId ? document.getElementById(targetId) : null;
        const collapsibleCard = collapseBtn.closest('.admin-collapsible, .collapsible-card');
        if (!collapsibleCard || !collapsibleContent) return;

        const syncButtonLabel = () => {
            collapseBtn.textContent = collapsibleCard.classList.contains('is-collapsed') ? 'Mostrar' : 'Amagar';
        };

        syncButtonLabel();

        collapseBtn.addEventListener('click', () => {
            const willOpen = collapsibleCard.classList.contains('is-collapsed');
            collapsibleCard.classList.toggle('is-collapsed');
            syncButtonLabel();

            if (willOpen && targetId === 'analytics-content') {
                window.setTimeout(refreshGeoMap, 420);
            }
        });
    });

    const backToTop = document.querySelector('.admin-back-to-top');
    if (backToTop) {
        const updateBackToTop = () => {
            backToTop.classList.toggle('is-visible', window.scrollY > 520);
        };

        updateBackToTop();
        window.addEventListener('scroll', updateBackToTop, { passive: true });
        backToTop.addEventListener('click', (event) => {
            event.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    document.querySelectorAll('[data-sortable-table]').forEach((tbl) => {
        const thead = tbl.querySelector('thead');
        if (!thead) return;

        thead.addEventListener('click', (e) => {
            const th = e.target.closest('th[data-sort-type]');
            if (!th) return;

            const idx = Array.from(th.parentNode.children).indexOf(th);
            const tbody = tbl.querySelector('tbody');
            if (!tbody) return;

            const dir = th.classList.contains('sort-asc') ? 'desc' : 'asc';
            th.closest('tr').querySelectorAll('th').forEach((h) => {
                h.classList.remove('sort-asc', 'sort-desc');
                h.removeAttribute('aria-sort');
            });
            th.classList.add('sort-' + dir);
            th.setAttribute('aria-sort', dir === 'asc' ? 'ascending' : 'descending');

            const dataRows = Array.from(tbody.querySelectorAll('tr[data-class]'));
            const sortType = th.getAttribute('data-sort-type') || 'text';
            const editorMap = new Map();

            dataRows.forEach((r) => {
                const next = r.nextElementSibling;
                if (next && next.classList.contains('student-editor-row')) editorMap.set(r, next);
            });

            dataRows.sort((a, b) => {
                const va = (a.children[idx]?.textContent || '').trim();
                const vb = (b.children[idx]?.textContent || '').trim();

                if (sortType === 'number') {
                    const na = Number.parseFloat(va.replace(',', '.')) || 0;
                    const nb = Number.parseFloat(vb.replace(',', '.')) || 0;
                    return dir === 'asc' ? na - nb : nb - na;
                }

                return dir === 'asc'
                    ? va.localeCompare(vb, 'ca', { numeric: true, sensitivity: 'base' })
                    : vb.localeCompare(va, 'ca', { numeric: true, sensitivity: 'base' });
            });

            const frag = document.createDocumentFragment();
            dataRows.forEach((r) => {
                frag.appendChild(r);
                const ed = editorMap.get(r);
                if (ed) frag.appendChild(ed);
            });
            tbody.appendChild(frag);
        });
    });

    document.querySelectorAll('[data-filter-table]').forEach((bar) => {
        const table = document.getElementById(bar.getAttribute('data-filter-table') || '');
        if (!table) return;

        bar.addEventListener('click', (event) => {
            const chip = event.target.closest('.admin-filters__chip, .filter-chip');
            if (!chip || !bar.contains(chip)) return;

            const chips = Array.from(bar.querySelectorAll('.admin-filters__chip, .filter-chip'));
            const value = chip.getAttribute('data-value');

            if (value === 'all') {
                chips.forEach((item) => item.classList.toggle('is-active', item === chip));
            } else {
                chip.classList.toggle('is-active');
                const hasActiveClass = chips.some((item) => {
                    return item.getAttribute('data-value') !== 'all' && item.classList.contains('is-active');
                });
                const allChip = bar.querySelector('.admin-filters__chip[data-value="all"], .filter-chip[data-value="all"]');
                if (allChip) allChip.classList.toggle('is-active', !hasActiveClass);
            }

            const activeClasses = chips
                .filter((item) => item.getAttribute('data-value') !== 'all' && item.classList.contains('is-active'))
                .map((item) => normalizeFilterValue(item.getAttribute('data-value')));
            const showAll = activeClasses.length === 0;

            table.querySelectorAll('tbody tr[data-class]').forEach((row) => {
                const rowClass = normalizeFilterValue(row.getAttribute('data-class'));
                const isVisible = showAll || activeClasses.includes(rowClass);
                row.hidden = !isVisible;

                const editorRow = row.nextElementSibling;
                if (editorRow?.classList.contains('student-editor-row')) {
                    editorRow.hidden = !isVisible;
                    if (!isVisible) editorRow.classList.remove('open');
                }
            });

            closeHiddenEditorRows(table);
            updateStudentCount(table);
        });

        updateStudentCount(table);
    });

    document.querySelectorAll('[data-role-filter]').forEach((select) => {
        const groups = Array.from(document.querySelectorAll('[data-role-group]'));
        if (groups.length === 0) return;

        const storageKey = 'admin-role-filter';
        const normalize = (value) => normalizeFilterValue(value).replace(/\s+/g, ' ');

        const applyFilter = (value) => {
            const normalizedValue = normalize(value);
            const showAll = normalizedValue === '' || normalizedValue === 'all';

            groups.forEach((group) => {
                const groupName = normalize(group.getAttribute('data-role-name'));
                group.hidden = !showAll && groupName !== normalizedValue;
            });
        };

        const storedValue = window.localStorage.getItem(storageKey);
        if (storedValue) {
            select.value = storedValue;
            applyFilter(storedValue);
        } else {
            applyFilter(select.value);
        }

        select.addEventListener('change', () => {
            const value = select.value;
            window.localStorage.setItem(storageKey, value);
            applyFilter(value);
        });
    });
});
