(() => {
 const form = document.getElementById('purchaseInvoiceEdit');
 if (form) {
  const preview = () => {
   const subtotal = [...form.querySelectorAll('#purchaseInvoiceRows .invoice-item')].reduce((sum, row) => sum + Number(row.querySelector('[data-qty]').value || 0) * Number(row.querySelector('[data-price]').value || 0), 0);
   const discount = document.getElementById('discountType').value === '2' ? subtotal * Number(document.getElementById('invoicePercent').value || 0) / 100 : Number(document.getElementById('invoiceDiscount').value || 0);
   const grand = Math.max(0, subtotal - discount);
   document.getElementById('purchaseInvoiceSubtotal').value = subtotal.toFixed(2);
   document.getElementById('purchaseInvoiceDiscountPreview').value = discount.toFixed(2);
   document.getElementById('purchaseInvoiceDuePreview').value = (grand - Number(form.dataset.paid)).toFixed(2);
  };
  form.addEventListener('input', preview); form.addEventListener('change', preview);
  form.addEventListener('purchase-item-removed', preview); preview();
  let submitting = false;
  form.addEventListener('submit', event => {
   if (event.defaultPrevented) return;
   if (submitting) { event.preventDefault(); return; }
   submitting = true; document.getElementById('purchaseInvoiceSaveButton').disabled = true;
  });
  window.addEventListener('pageshow', () => { submitting = false; document.getElementById('purchaseInvoiceSaveButton').disabled = false; });
 }
 const serialDetails = [...document.querySelectorAll('[data-invoice-serials]')];
 let states;
 window.addEventListener('beforeprint', () => { states = serialDetails.map(el => el.open); serialDetails.forEach(el => { el.open = true; }); });
 window.addEventListener('afterprint', () => { if (states) serialDetails.forEach((el, i) => { el.open = states[i]; }); });
 document.getElementById('purchaseInvoicePrintButton')?.addEventListener('click', () => window.print());
})();
