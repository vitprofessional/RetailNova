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
        const start = form?.querySelector('[name="start_date"]');
        const end = form?.querySelector('[name="end_date"]');
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
                    start?.focus();
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
