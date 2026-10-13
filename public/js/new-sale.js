(() => {
  const form = document.getElementById('saveSaleForm');
  if (!form) return;
  const product = document.getElementById('productName');
  const search = document.getElementById('saleProductSearch');
  const feedback = document.getElementById('saleSearchFeedback');
  const rows = document.getElementById('productDetails');
  const t = (key, values) => window.RNTranslate(key, values);
  const filter = () => {
    const query = search.value.trim().toLocaleLowerCase();
    let count = 0;
    Array.from(product.options).forEach(option => {
      option.hidden = !!option.value && !option.textContent.toLocaleLowerCase().includes(query);
      if (option.value && !option.hidden) count++;
    });
    feedback.textContent = product.disabled ? t('sale_select_customer_first') : t('sale_search_count', {count});
  };
  search.addEventListener('input', filter);
  new MutationObserver(filter).observe(product, {childList: true, attributes: true, attributeFilter: ['disabled']});
  filter();
  document.addEventListener('DOMContentLoaded', () => {
    const customer = document.getElementById('customerName');
    if (customer.value) customer.dispatchEvent(new Event('change', {bubbles: true}));
  });

  const presentRows = () => {
    const count = rows.querySelectorAll('tr').length;
    document.getElementById('saleEmptyState').hidden = count > 0;
    document.getElementById('saleItemCount').textContent = String(count);
    form.querySelector('.rn-sale-table-wrap').hidden = count === 0;
    form.querySelector('.rn-sale-table-hint').hidden = count === 0;
  };
  new MutationObserver(presentRows).observe(rows, {childList: true});
  presentRows();
  rows.addEventListener('click', event => {
    const button = event.target.closest('[data-sale-qty]');
    if (!button) return;
    const input = button.closest('tr').querySelector('.qty');
    input.value = String(Math.max(1, (parseInt(input.value, 10) || 1) + Number(button.dataset.saleQty)));
    input.dispatchEvent(new Event('input', {bubbles: true}));
  });

  // Reuse authenticated barcode lookup and the existing purchase selector/change handler.
  const barcode = document.getElementById('barcodeInput');
  const barcodeButton = document.getElementById('searchBarcodeBtn');
  const message = document.getElementById('barcodeSearchMessage');
  const seed = document.getElementById('rn-route-seeds');
  const scan = async () => {
    const customer = document.getElementById('customerName');
    const selectedCustomer = customer.value;
    const selectionVersion = customer.dataset.selectionVersion;
    if (!selectedCustomer || customer.dataset.customerReady !== '1') { message.textContent = t('sale_customer_first_instruction'); return; }
    if (barcodeButton.disabled) return;
    if (product.disabled) { message.textContent = t('sale_select_customer_first'); return; }
    if (!barcode.value.trim()) { message.textContent = t('sale_barcode_empty'); return; }
    barcodeButton.disabled = true;
    try {
      const response = await fetch(seed.dataset.barcodeUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({barcode: barcode.value.trim()})
      });
      const result = await response.json();
      if (customer.value !== selectedCustomer || customer.dataset.selectionVersion !== selectionVersion || customer.dataset.customerReady !== '1') return;
      if (!response.ok || result.status !== 'success') { message.textContent = result.message || t('sale_search_error'); return; }
      const details = await fetch(seed.dataset.purchaseTemplate.replace('__ID__', encodeURIComponent(result.product.id)), {
        credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
      });
      if (!details.ok || details.redirected) throw new Error('Purchase lookup unavailable');
      const batches = await details.json();
      if (customer.value !== selectedCustomer || customer.dataset.selectionVersion !== selectionVersion || customer.dataset.customerReady !== '1') return;
      const batch = (batches.getData || []).find(item => Number(item.currentStock) > 0 && Array.from(product.options).some(option => option.value === 'purchase_' + item.purchaseId));
      const option = batch ? Array.from(product.options).find(item => item.value === 'purchase_' + batch.purchaseId) : Array.from(product.options).find(item => item.value === String(result.product.id));
      if (!option) { message.textContent = t('sale_barcode_unavailable'); return; }
      search.value = ''; filter(); product.value = option.value;
      product.dispatchEvent(new Event('change', {bubbles: true}));
      message.textContent = t('sale_barcode_found', {product: result.product.name});
      barcode.value = ''; barcode.focus();
    } catch (_) { message.textContent = t('sale_search_error'); }
    finally { barcodeButton.disabled = customer.dataset.customerReady !== '1'; }
  };
  barcodeButton.addEventListener('click', scan);
  barcode.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); scan(); } });
  search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); product.focus(); } });
})();
