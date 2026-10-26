(() => {
  const roots = document.querySelectorAll('[data-rn-product-picker]');
  roots.forEach(root => {
    const search = root.querySelector('[data-picker-search]');
    const filterModeButtons = [...root.querySelectorAll('[data-picker-filter-mode]')];
    const chips = root.querySelector('[data-picker-chips]');
    const results = root.querySelector('[data-picker-results]');
    const empty = root.querySelector('[data-picker-empty]');
    const more = root.querySelector('[data-picker-more]');
    const error = root.querySelector('[data-picker-error]');
    const sheet = root.querySelector('[data-picker-sheet]');
    let selectedPurchaseItem = null;
    let updateLineTotal = () => {};
    let mode = 'category', category = '', brand = '', page = 1, hasMore = false, waiting = false, activeRequest = null;
    let timer;
    const label = key => root.dataset[`label${key}`] || '';
    const localized = (template, count) => template.replace('__COUNT__', count.toLocaleString(document.documentElement.lang?.startsWith('bn') ? 'bn-BD' : 'en-US'));
    const setLoading = on => { waiting = on; root.classList.toggle('is-loading', on); more.disabled = on; };
    const renderChips = data => {
      const options = mode === 'category' ? data.categories : data.brands;
      const active = mode === 'category' ? category : brand;
      const allLabel = label(mode === 'category' ? 'AllCategory' : 'AllBrand');
      chips.replaceChildren();
      [[ '', allLabel ], ...options.map(option => [String(option.id), option.name])].forEach(([value, text]) => {
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'rn-m-chip';
        button.textContent = text; button.setAttribute('aria-pressed', String(active === value));
        if (active === value) button.classList.add('is-active');
        button.addEventListener('click', () => {
          if (mode === 'category') category = value; else brand = value;
          load(true);
        });
        chips.append(button);
      });
    };
    const card = item => {
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'rn-m-picker-card';
      button.setAttribute('role', 'listitem');
      const unavailableSale = root.dataset.mode === 'sale' && (item.stock <= 0 || item.sale_price === null);
      if (unavailableSale) { button.disabled = true; button.classList.add('is-unavailable'); }
      if (item.image) {
        const image = document.createElement('img'); image.className = 'rn-m-picker-card__image'; image.src = item.image; image.alt = ''; image.loading = 'lazy'; button.append(image);
      } else {
        const fallback = document.createElement('span'); fallback.className = 'rn-m-picker-card__image rn-m-picker-card__fallback'; fallback.textContent = item.name.slice(0, 1); button.append(fallback);
      }
      const name = document.createElement('strong'); name.className = 'rn-m-picker-card__name'; name.textContent = item.name; button.append(name);
      const meta = [item.brand, item.unit].filter(Boolean).join(' · ');
      if (meta) { const line = document.createElement('span'); line.className = 'rn-m-picker-card__meta'; line.textContent = meta; button.append(line); }
      const price = root.dataset.mode === 'purchase' || root.dataset.mode === 'purchase-return' ? item.buy_price : item.sale_price;
      if (price !== null) {
        const locale = document.documentElement.lang?.startsWith('bn') ? 'bn-BD' : 'en-US';
        const number = new Intl.NumberFormat(locale, { maximumFractionDigits: 2 }).format(Number(price || 0));
        const currency = root.dataset.currency || '';
        const value = root.dataset.currencyPosition === 'right' ? `${number}${currency}` : `${currency}${number}`;
        const amount = document.createElement('span'); amount.className = 'rn-m-picker-card__price'; amount.textContent = value; button.append(amount);
      }
      if (root.dataset.mode === 'sale' || root.dataset.mode === 'purchase-return') {
        const stock = document.createElement('span'); stock.className = 'rn-m-picker-card__stock'; stock.textContent = localized(label('Stock'), item.stock); button.append(stock);
      }
      if (unavailableSale) { const note = document.createElement('span'); note.className = 'rn-m-picker-card__note'; note.textContent = item.sale_price === null ? label('NoPrice') : label('OutOfStock'); button.append(note); }
      else { const add = document.createElement('span'); add.className = 'rn-m-picker-card__add'; add.setAttribute('aria-hidden', 'true'); add.textContent = '+'; button.append(add); }
      button.addEventListener('click', () => {
        if (root.dataset.mode === 'purchase-return') {
          const template = root.dataset.returnUrlTemplate;
          if (template) { window.location.assign(template.replace('__ID__', String(item.item_id))); return; }
        }
        if (root.dataset.mode === 'purchase' && sheet) {
          selectedPurchaseItem = item;
          sheet.querySelector('[data-picker-sheet-name]').textContent = item.name;
          sheet.querySelector('[data-picker-sheet-meta]').textContent = [item.brand, item.unit].filter(Boolean).join(' · ');
          const image = sheet.querySelector('[data-picker-sheet-image]');
          const fallback = sheet.querySelector('[data-picker-sheet-fallback]');
          image.hidden = !item.image;
          image.removeAttribute('src');
          if (item.image) image.src = item.image;
          fallback.hidden = Boolean(item.image);
          fallback.textContent = item.name.slice(0, 1);
          sheet.querySelector('[data-picker-qty]').value = '1';
          sheet.querySelector('[data-picker-buy]').value = item.buy_price ?? '';
          sheet.querySelector('[data-picker-sale]').value = item.sale_price ?? '';
          updateLineTotal();
          sheet.hidden = false;
          sheet.querySelector('[data-picker-qty]').focus();
          return;
        }
        root.dispatchEvent(new CustomEvent('rn:product-picker:selected', { bubbles: true, detail: { ...item, mode: root.dataset.mode } }));
      });
      return button;
    };
    async function load(reset) {
      if (waiting && !reset) return;
      if (reset) { page = 1; results.replaceChildren(); }
      activeRequest?.abort();
      const request = new AbortController();
      activeRequest = request;
      setLoading(true); error.hidden = true;
      const url = new URL(root.dataset.url, window.location.href);
      url.searchParams.set('mode', root.dataset.mode); url.searchParams.set('page', String(page));
      url.searchParams.set('q', search.value.trim()); url.searchParams.set('category', category); url.searchParams.set('brand', brand);
      try {
        const response = await fetch(url, { signal: request.signal, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error(`${response.status}`);
        const data = await response.json();
        renderChips(data);
        data.items.forEach(item => results.append(card(item)));
        hasMore = data.has_more; page = data.page + 1;
        more.hidden = !hasMore; empty.hidden = results.children.length > 0;
      } catch (exception) {
        if (exception.name === 'AbortError') return;
        error.textContent = root.dataset.labelError || label('Loading'); error.hidden = false;
      } finally { if (activeRequest === request) { activeRequest = null; setLoading(false); } }
    }
    search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => load(true), 250); });
    filterModeButtons.forEach(button => button.addEventListener('click', () => {
      mode = button.dataset.pickerFilterMode;
      filterModeButtons.forEach(item => { const active = item === button; item.classList.toggle('is-active', active); item.setAttribute('aria-pressed', String(active)); });
      load(true);
    }));
    more.addEventListener('click', () => { if (hasMore) load(false); });
    if (sheet) {
      const qty = sheet.querySelector('[data-picker-qty]'), buy = sheet.querySelector('[data-picker-buy]');
      updateLineTotal = () => { sheet.querySelector('[data-picker-line-total]').textContent = moneyText((Number(qty.value) || 0) * (Number(buy.value) || 0)); };
      sheet.querySelectorAll('[data-picker-sheet-close]').forEach(button => button.addEventListener('click', () => { sheet.hidden = true; selectedPurchaseItem = null; }));
      [qty, buy].forEach(input => input.addEventListener('input', updateLineTotal));
      sheet.querySelector('[data-picker-sheet-add]').addEventListener('click', () => {
        if (!selectedPurchaseItem || !qty.value || Number(qty.value) < 1 || buy.value === '' || Number(buy.value) < 0 || sheet.querySelector('[data-picker-sale]').value === '') return;
        root.dispatchEvent(new CustomEvent('rn:product-picker:selected', { bubbles: true, detail: {
          ...selectedPurchaseItem, mode: root.dataset.mode, quantity: Number(qty.value), buy_price: Number(buy.value), sale_price: Number(sheet.querySelector('[data-picker-sale]').value)
        } }));
        sheet.hidden = true; selectedPurchaseItem = null;
      });
    }
    load(true);

    function moneyText(amount) {
      const locale = document.documentElement.lang?.startsWith('bn') ? 'bn-BD' : 'en-US';
      const number = new Intl.NumberFormat(locale, { maximumFractionDigits: 2 }).format(Number(amount || 0));
      const currency = root.dataset.currency || '';
      return root.dataset.currencyPosition === 'right' ? `${number}${currency}` : `${currency}${number}`;
    }
  });
})();
