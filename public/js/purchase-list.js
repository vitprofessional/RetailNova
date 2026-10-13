(function () {
    'use strict';
    const form = document.getElementById('purchaseInvoiceBulkForm');
    if (!form) return;
    const invoices = Array.from(form.querySelectorAll('#purchaseInvoiceTable input[name="ids[]"]'));
    const selectPage = document.getElementById('purchaseSelectPage');
    const deleteButton = document.getElementById('purchaseBulkDeleteButton');
    const count = document.getElementById('purchaseSelectionCount');
    function updateSelection() {
        const selected = invoices.filter(input => input.checked).length;
        selectPage.checked = invoices.length > 0 && selected === invoices.length;
        selectPage.indeterminate = selected > 0 && selected < invoices.length;
        selectPage.disabled = invoices.length === 0;
        if (deleteButton) deleteButton.disabled = selected === 0;
        count.textContent = count.dataset.label + ': ' + selected;
    }
    selectPage.addEventListener('change', function () {
        invoices.forEach(input => { input.checked = selectPage.checked; });
        updateSelection();
    });
    invoices.forEach(input => input.addEventListener('change', updateSelection));
    window.addEventListener('pageshow', updateSelection);
    updateSelection();

    const region = form.querySelector('.rn-purchase-table-scroll');
    const mobile = window.matchMedia('(max-width:575px)');
    function updateDescription() {
        region.setAttribute('aria-describedby', mobile.matches ? 'purchaseMobileHint' : 'purchaseScrollHint');
        region.tabIndex = mobile.matches ? -1 : 0;
    }
    mobile.addEventListener('change', updateDescription);
    updateDescription();

    // Preserve printing of original compatibility rows without changing their on-screen state.
    const legacy = document.getElementById('purchaseLegacyRecords');
    let legacyWasOpen = false, printing = false;
    window.addEventListener('beforeprint', function () {
        if (legacy && !printing) { legacyWasOpen = legacy.open; legacy.open = true; printing = true; }
    });
    window.addEventListener('afterprint', function () {
        if (legacy && printing) { legacy.open = legacyWasOpen; printing = false; }
    });
    document.getElementById('purchaseListPrintButton').addEventListener('click', function () { window.print(); });
})();
