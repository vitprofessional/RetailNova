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
    const count = rows.querySelectorAll('tr[data-product-summary]').length;
    document.getElementById('saleEmptyState').hidden = count > 0;
    const digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    document.getElementById('saleItemCount').textContent = String(count).replace(/[0-9]/g, digit =>
      (document.documentElement.lang || '').toLowerCase().startsWith('bn') ? digits[Number(digit)] : digit
    );
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
      const business = document.querySelector('#saveSaleForm [name="businessId"]');
      const response = await fetch(seed.dataset.barcodeUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({barcode: barcode.value.trim(), ...(business && business.value ? {businessId: business.value} : {})})
      });
      const result = await response.json();
      if (customer.value !== selectedCustomer || customer.dataset.selectionVersion !== selectionVersion || customer.dataset.customerReady !== '1') return;
      if (!response.ok || result.status !== 'success') { message.textContent = result.message || t('sale_search_error'); return; }
      let option = Array.from(product.options).find(item => item.value === String(result.product.id));
      if (!option) {
        // Barcode is an explicit product target. If the current category filter
        // hid it, reload the selected customer's authorized catalog first.
        const productsTemplate = seed.dataset.productsTemplate || '';
        const productsUrl = productsTemplate.replace('__ID__', encodeURIComponent(String(selectedCustomer))) +
          (business && business.value ? (productsTemplate.includes('?') ? '&' : '?') + 'businessId=' + encodeURIComponent(business.value) : '');
        const catalog = await fetch(productsUrl, {credentials:'same-origin', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        if (!catalog.ok || catalog.redirected) throw new Error('Authorized catalog lookup unavailable');
        const catalogData = await catalog.json();
        if (customer.value !== selectedCustomer || customer.dataset.selectionVersion !== selectionVersion || customer.dataset.customerReady !== '1') return;
        if (!catalogData || typeof catalogData.data !== 'string') throw new Error('Invalid authorized catalog response');
        product.innerHTML = catalogData.data;
        const brand = document.getElementById('brandSelect');
        if (brand) brand.value = '';
        const category = document.getElementById('categorySelect');
        if (category) category.value = '';
        option = Array.from(product.options).find(item => item.value === String(result.product.id));
      }
      if (!option) { message.textContent = t('sale_barcode_unavailable'); return; }
      search.value = ''; filter(); product.value = option.value;
      product.dispatchEvent(new Event('change', {bubbles: true}));
      message.textContent = t('sale_barcode_found', {product: result.product.name});
      barcode.value = ''; barcode.focus();
    } catch (_) { message.textContent = t('sale_search_error'); }
    finally { barcodeButton.disabled = customer.dataset.customerReady !== '1'; }
  };
  // Payment method: the select mirrors into the hidden field; "due" means nothing is paid now.
  const paymentMethod = document.getElementById('paymentMethod');
  const paymentSelect = document.getElementById('paymentMethodSelect');
  paymentSelect.addEventListener('change', () => {
    paymentMethod.value = paymentSelect.value;
    if (paymentSelect.value !== 'due') return;
    const paid = document.getElementById('paidAmount');
    paid.value = '0';
    paid.dispatchEvent(new Event('input', {bubbles: true}));
  });

  barcodeButton.addEventListener('click', scan);
  barcode.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); scan(); } });
  search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); product.focus(); } });
})();
