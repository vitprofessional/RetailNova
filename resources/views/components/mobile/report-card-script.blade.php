<script>
    document.querySelectorAll('.rn-report-table').forEach((table) => {
        const headings = Array.from(table.querySelectorAll('thead th')).map((heading) =>
            heading.textContent.replace(/\s+/g, ' ').trim()
        );

        if (!headings.length) return;

        table.querySelectorAll('tbody tr').forEach((row) => {
            const cells = Array.from(row.children).filter((cell) => cell.tagName === 'TD');
            if (cells.length !== headings.length) return;

            cells.forEach((cell, index) => {
                cell.dataset.label = headings[index];
            });
        });
    });

    document.querySelectorAll('[data-rn-report-period-tools]').forEach((tools) => {
        const form = tools.closest('form') || tools.parentElement.querySelector('form[data-rn-report-date-form]');
        let filterScope = form;
        if (form && window.matchMedia('(max-width: 767px)').matches) {
            const overlay = document.createElement('div');
            overlay.className = 'rn-report-filter-sheet';
            overlay.hidden = true;
            const filterLabel = tools.querySelector('[data-rn-report-filter]')?.getAttribute('aria-label') || '';
            const closeLabel = @json(__('mobile.close'));
            const resetLabel = @json(__('mobile.filter_reset'));
            const applyLabel = @json(__('mobile.filter_apply'));
            overlay.innerHTML = `<div class="rn-report-filter-backdrop" data-rn-filter-close></div><section class="rn-report-filter-panel" role="dialog" aria-modal="true" aria-label="${filterLabel}"><header><strong>${filterLabel}</strong><button type="button" data-rn-filter-close aria-label="${closeLabel}">×</button></header><div class="rn-report-filter-fields"></div><footer><button type="button" data-rn-filter-reset>${resetLabel}</button><button type="button" data-rn-filter-apply>${applyLabel}</button></footer></section>`;
            document.body.append(overlay);
            const fields = overlay.querySelector('.rn-report-filter-fields');
            filterScope = overlay;
            if (!form.id) form.id = `rnReportFilterForm${Math.random().toString(36).slice(2)}`;
            form.querySelector('button[type="submit"]')?.closest('[class*="col-"]')?.remove();
            [...form.children].filter(child => child !== tools).forEach(child => {
                fields.append(child);
                child.querySelectorAll('input,select,button').forEach(control => control.setAttribute('form', form.id));
            });
            const open = () => { overlay.hidden = false; document.body.classList.add('rn-report-filter-open'); overlay.querySelector('input,select')?.focus(); };
            const close = () => { overlay.hidden = true; document.body.classList.remove('rn-report-filter-open'); tools.querySelector('[data-rn-report-filter]')?.focus(); };
            tools.querySelector('[data-rn-report-filter]')?.addEventListener('click', open);
            tools.querySelector('[data-rn-report-period="custom"]')?.addEventListener('click', open);
            overlay.querySelectorAll('[data-rn-filter-close]').forEach(button => button.addEventListener('click', close));
            overlay.querySelector('[data-rn-filter-reset]')?.addEventListener('click', () => { window.location.assign(form.action); });
            overlay.querySelector('[data-rn-filter-apply]')?.addEventListener('click', () => form.requestSubmit());
            document.addEventListener('keydown', event => { if (!overlay.hidden && event.key === 'Escape') close(); });
        }
        const start = filterScope?.querySelector('[name="start_date"]');
        const end = filterScope?.querySelector('[name="end_date"]');
        const today = new Date();
        const iso = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
        const currentStart = start?.value;
        const currentEnd = end?.value;
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        const weekStart = new Date(today); weekStart.setDate(today.getDate()-6);
        tools.querySelectorAll('[data-rn-report-period]').forEach(button => {
            const period = button.dataset.rnReportPeriod;
            const expectedStart = period === 'today' ? iso(today) : (period === 'week' ? iso(weekStart) : iso(monthStart));
            const isPreset = period !== 'custom' && currentStart === expectedStart && currentEnd === iso(today);
            button.setAttribute('aria-pressed', String(isPreset));
        });
        tools.querySelectorAll('[data-rn-report-period]').forEach((button) => {
            button.addEventListener('click', () => {
                const period = button.dataset.rnReportPeriod;
                if (period === 'custom') {
                    return;
                }
                if (!start || !end) return;
                const today = new Date();
                const from = new Date(today);
                if (period === 'week') from.setDate(today.getDate() - 6);
                if (period === 'month') from.setDate(1);
                start.value = iso(from);
                end.value = iso(today);
                form.requestSubmit();
            });
        });
    });

    document.querySelectorAll('[data-rn-report-download]').forEach((button) => {
        button.addEventListener('click', () => {
            const tables = [...document.querySelectorAll('.rn-report-page .rn-report-table')];
            if (!tables.length) return;
            const quote = value => `"${String(value ?? '').replaceAll('"', '""')}"`;
            const rows = [];
            tables.forEach((table, index) => {
                if (index) rows.push([]);
                const headings = [...table.querySelectorAll('thead th')].map(cell => cell.innerText.trim());
                if (headings.length) rows.push(headings);
                table.querySelectorAll('tbody tr').forEach(row => rows.push([...row.querySelectorAll('td')].map(cell => cell.innerText.trim())));
            });
            const csv = '\uFEFF' + rows.map(row => row.map(quote).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8'}));
            link.download = 'retailnova-report.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    });
</script>
