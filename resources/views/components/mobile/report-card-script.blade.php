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
</script>
