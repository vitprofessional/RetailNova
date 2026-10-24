<script>
(() => {
    const root = document.querySelector('[data-rn-report-overview]');
    if (!root) return;

    const customButton = root.querySelector('[data-rn-custom-period]');
    const range = root.querySelector('.rn-report-custom-range');
    customButton?.addEventListener('click', () => {
        const open = range.hidden;
        range.hidden = !open;
        customButton.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('rn-report-filter-open', open);
        if (open) range.querySelector('input[type="date"]')?.focus();
    });
    range?.addEventListener('submit', () => document.body.classList.remove('rn-report-filter-open'));

    root.querySelector('[data-rn-report-download]')?.addEventListener('click', () => {
        const quote = value => `"${String(value ?? '').replaceAll('"', '""')}"`;
        const lines = [[@json(__('reports.label')), @json(__('reports.value'))]];
        root.querySelectorAll('[data-rn-export-row]').forEach(row => {
            lines.push([row.dataset.label || '', row.dataset.value || '']);
        });
        const csv = '\uFEFF' + lines.map(row => row.map(quote).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], {type: 'text/csv;charset=utf-8'}));
        const link = document.createElement('a');
        link.href = url;
        link.download = 'business-overview.csv';
        link.click();
        URL.revokeObjectURL(url);
    });
})();
</script>
