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

    document.querySelectorAll('.collapse-toggle').forEach((collapseBtn) => {
        const targetId = collapseBtn.getAttribute('data-collapse');
        const collapsibleContent = targetId ? document.getElementById(targetId) : null;
        const collapsibleCard = collapseBtn.closest('.collapsible-card');
        if (!collapsibleCard || !collapsibleContent) return;

        collapseBtn.addEventListener('click', () => {
            collapsibleCard.classList.toggle('is-collapsed');
            collapseBtn.textContent = collapsibleCard.classList.contains('is-collapsed') ? 'Mostrar' : 'Amagar';
        });
    });

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
            const chip = event.target.closest('.filter-chip');
            if (!chip || !bar.contains(chip)) return;

            const chips = Array.from(bar.querySelectorAll('.filter-chip'));
            const value = chip.getAttribute('data-value');

            if (value === 'all') {
                chips.forEach((item) => item.classList.toggle('checked', item === chip));
            } else {
                chip.classList.toggle('checked');
                const hasActiveClass = chips.some((item) => {
                    return item.getAttribute('data-value') !== 'all' && item.classList.contains('checked');
                });
                const allChip = bar.querySelector('.filter-chip[data-value="all"]');
                if (allChip) allChip.classList.toggle('checked', !hasActiveClass);
            }

            const activeClasses = chips
                .filter((item) => item.getAttribute('data-value') !== 'all' && item.classList.contains('checked'))
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
});
