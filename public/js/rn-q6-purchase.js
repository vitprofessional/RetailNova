(() => {
 const root = document.querySelector('[data-q6-purchase]');
 const form = document.getElementById('savePurchase');
 if (!root || !form) return;
 const $ = selector => root.querySelector(selector);
 const rows = form.querySelector('#productDetails');
 const picker = form.querySelector('[data-rn-product-picker][data-mode="purchase"]');
 const pickerDone = root.querySelector('[data-q6-picker-done]');
 const cartKey = picker?.dataset.cartKey || '';
 const legacyCartKey = cartKey ? cartKey + '-lines' : '';
 const readCart = () => {
  try {
   const saved = JSON.parse(localStorage.getItem(cartKey) || 'null');
   if (saved && (saved.lines || saved.counts)) return saved;
   const legacy = JSON.parse(localStorage.getItem(legacyCartKey) || 'null');
   return legacy ? Object.assign({ counts:{} }, legacy) : { counts:{}, lines:[] };
  } catch (_) { return { counts:{}, lines:[] }; }
 };
 let restoringCart = true;
 const pickerSlot = $('[data-q6-picker-slot]');
 const pickerAnchor = document.createComment('purchase picker desktop location');
 picker?.parentNode.insertBefore(pickerAnchor, picker);
 const mobile = window.matchMedia('(max-width: 992px)');
 const desktopMethod = form.querySelector('#paymentMethodDesktop');
 const mobileMethod = $('[data-q6-mobile-method]');
 const supplierSelect = form.querySelector('#supplierName');
 const dateInput = form.querySelector('#date');
 const referenceInput = form.querySelector('#reference');
 const discountStatus = form.querySelector('#discountStatus');
 const discountAmount = form.querySelector('#discountAmount');
 const discountPercent = form.querySelector('#discountPercent');
 const grandInput = form.querySelector('#grandTotal');
 const paidInput = form.querySelector('#paidAmount');
 const dueInput = form.querySelector('#dueAmount');
 const noteInput = form.querySelector('#specialNote');
 let openingDue = 0;
 let activeRow = null;
 const digitMap = root.dataset.locale.startsWith('bn') ? '০১২৩৪৫৬৭৮৯' : '0123456789';
 const localDigits = value => String(value).replace(/[0-9]/g, digit => digitMap[Number(digit)]);
 const grouped = value => {
  const n = Number(value || 0);
  const [whole, fraction] = Math.abs(n).toFixed(2).replace(/\.00$/, '').split('.');
  const formatted = whole.length > 3 ? whole.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + whole.slice(-3) : whole;
  return localDigits(formatted + (fraction ? '.' + fraction : ''));
 };
 const refreshPickerFooter = () => {
  if (!pickerDone) return;
  const count = [...rows.querySelectorAll('tr.product-row')].filter(row => row.querySelector('input[name^="productName["]')?.value).length;
  const totalQty = [...rows.querySelectorAll('tr.product-row')].reduce((sum,row)=>sum+numeric(row.querySelector('.quantity')),0);
  const total = [...rows.querySelectorAll('tr.product-row')].reduce((sum,row)=>sum+(numeric(row.querySelector('input[id^="totalAmount"]')) || numeric(row.querySelector('.quantity'))*numeric(row.querySelector('input[id^="buyPrice"]'))),0);
  pickerDone.hidden = count === 0;
  root.querySelector('[data-q6-picker-count]').textContent = localDigits(totalQty);
  root.querySelector('[data-q6-picker-total]').textContent = money(total);
  pickerDone.classList.remove('is-pulsing'); void pickerDone.offsetWidth; pickerDone.classList.add('is-pulsing');
 };
 const money = value => {
  const amount = Number(value || 0);
  return (amount < 0 ? '−' : '') + (root.dataset.currency || '৳') + grouped(Math.abs(amount));
 };
 const numeric = field => Number(String(field?.value || '0').replace(/,/g, '')) || 0;
 const poke = field => {
  if (!field) return;
  ['input', 'change', 'keyup'].forEach(type => field.dispatchEvent(new Event(type, { bubbles:true })));
 };
 const callFinance = () => {
  try { window.discountAmountChange?.(); window.discountPercentChange?.(); window.dueCalculate?.(); window.recalcFinancials?.(); } catch (_) {}
 };
 const movePicker = () => {
  if (!picker || !pickerAnchor.parentNode || !pickerSlot) return;
  if (mobile.matches) pickerSlot.appendChild(picker);
  else pickerAnchor.parentNode.insertBefore(picker, pickerAnchor.nextSibling);
 };
 const setVisibleMode = () => {
  movePicker();
  if (desktopMethod) desktopMethod.disabled = mobile.matches;
  if (mobileMethod) {
   mobileMethod.disabled = !mobile.matches;
   if (desktopMethod && mobile.matches) mobileMethod.value = desktopMethod.value;
   else if (desktopMethod) mobileMethod.value = desktopMethod.value;
  }
 };
 setVisibleMode();
 mobile.addEventListener?.('change', setVisibleMode);
 picker?.addEventListener('rn:product-picker:selected', event => {
  const selected=event.detail;
  window.setTimeout(()=>{
   const row=[...rows.querySelectorAll('tr.product-row')].at(-1);
   if(row && selected){row.dataset.q6Brand=selected.brand||'';row.dataset.q6Meta=[selected.brand,selected.unit].filter(Boolean).join(' · ');renderRows();}
   refreshPickerFooter();
  },0);
 });

 const showSheet = name => { const sheet = root.querySelector(`[data-q6-sheet="${name}"]`); if (sheet) { sheet.hidden = false; document.body.classList.add('rn-q6-sheet-open'); requestAnimationFrame(() => sheet.querySelector('input,button')?.focus({ preventScroll:true })); } };
 const closeSheets = () => { root.querySelectorAll('[data-q6-sheet]').forEach(sheet => sheet.hidden = true); document.body.classList.remove('rn-q6-sheet-open'); };
 root.addEventListener('click', event => {
  const opener = event.target.closest('[data-q6-open-supplier],[data-q6-open-date],[data-q6-open-more]');
  if (opener) {
   const name = opener.hasAttribute('data-q6-open-supplier') ? 'supplier' : opener.hasAttribute('data-q6-open-date') ? 'date' : 'more';
   if (name === 'date') $('[data-q6-date]').value = dateInput.value;
   if (name === 'more') $('[data-q6-reference]').value = referenceInput.value;
   showSheet(name);
  }
  if (event.target.closest('[data-q6-close-sheet]')) closeSheets();
 });
 $('[data-q6-supplier-options]')?.addEventListener('click', event => {
  const choice = event.target.closest('[data-q6-supplier]');
  if (!choice || !supplierSelect) return;
  supplierSelect.value = choice.dataset.q6Supplier;
  supplierSelect.dispatchEvent(new Event('change', { bubbles:true }));
  supplierSelect.dispatchEvent(new Event('input', { bubbles:true }));
  openingDue = Number(choice.dataset.due || 0);
  $('[data-q6-supplier-name]').textContent = choice.dataset.name || '';
  $('[data-q6-opening-due]').textContent = money(openingDue);
  $('[data-q6-previous-due]').hidden = false;
  closeSheets();
 });
 $('[data-q6-supplier-search]')?.addEventListener('input', event => {
  const term = event.target.value.trim().toLocaleLowerCase();
  root.querySelectorAll('[data-q6-supplier]').forEach(button => button.hidden = !button.dataset.name.toLocaleLowerCase().includes(term));
 });
 $('[data-q6-date]')?.addEventListener('change', event => { dateInput.value = event.target.value; $('[data-q6-date-label]').textContent = new Intl.DateTimeFormat(root.dataset.locale.startsWith('bn') ? 'bn-BD' : 'en-GB', { day:'2-digit', month:'2-digit', year:'numeric' }).format(new Date(`${event.target.value}T12:00:00`)); poke(dateInput); });
 $('[data-q6-reference]')?.addEventListener('input', event => { referenceInput.value = event.target.value; poke(referenceInput); });
 const syncDateAndInvoice = () => {
  const d = dateInput.value || new Date().toISOString().slice(0,10);
  $('[data-q6-date-label]').textContent = new Intl.DateTimeFormat(root.dataset.locale.startsWith('bn') ? 'bn-BD' : 'en-GB', { day:'2-digit', month:'2-digit', year:'numeric' }).format(new Date(`${d}T12:00:00`));
  $('[data-q6-invoice-label]').textContent = form.querySelector('#invoice')?.value || '';
 };
 syncDateAndInvoice();

 const rowValue = (row, selector) => row.querySelector(selector)?.value || '0';
 const countSerials = row => row.querySelectorAll('input[type="hidden"][data-serial]').length;
 const renderRows = () => {
  const actual = [...rows.querySelectorAll('tr.product-row')];
  const target = $('[data-q6-lines]');
  const existing = new Map([...target.children].map(card => [card.dataset.idx, card]));
  const seen = new Set();
  actual.forEach(row => {
   const idx = row.dataset.idx || '';
   const nameValue = row.querySelector('input[name^="selectProductName["]')?.value || '';
   const rowBrand = row.dataset.q6Brand || '';
   const name = rowBrand && nameValue.endsWith(` - ${rowBrand}`) ? nameValue.slice(0,-(rowBrand.length+3)) : nameValue;
   if (!name || name === 'Loading...') return;
   seen.add(idx);
   let card = existing.get(idx);
   if (!card) {
    card = document.createElement('article'); card.className='rn-q6-line-card'; card.dataset.idx=idx;
    const image=document.createElement('span'); image.className='rn-q6-line-image'; image.setAttribute('aria-hidden','true');
    const copy=document.createElement('span'); copy.className='rn-q6-line-copy';
    const title=document.createElement('strong'); title.dataset.q6Name='1'; const meta=document.createElement('small'); meta.dataset.q6Meta='1';
    copy.append(title,meta);
    const total=document.createElement('strong'); total.className='rn-q6-line-total'; total.dataset.q6LineTotal='1';
    const footer=document.createElement('div'); footer.className='rn-q6-line-footer';
    const price=document.createElement('span'); price.dataset.q6LinePrice='1';
    const serial=document.createElement('button'); serial.type='button'; serial.dataset.q6SerialTag='1';
    const serialIcon=document.createElement('i'); serialIcon.className='las la-barcode'; serialIcon.setAttribute('aria-hidden','true');
    const serialText=document.createElement('span'); serialText.dataset.q6SerialText='1';
    const arrow=document.createElement('i'); arrow.className='las la-angle-right'; arrow.setAttribute('aria-hidden','true');
    serial.append(serialIcon,serialText,arrow);
    const remove=document.createElement('button'); remove.type='button'; remove.className='rn-q6-line-remove'; remove.textContent='×'; remove.setAttribute('aria-label',root.dataset.removeLabel||'Remove');
    footer.append(price,serial); card.append(image,copy,total,remove,footer);
    card.addEventListener('click', event => { if(event.target.closest('[data-q6-remove-card]')) return; card.dispatchEvent(new CustomEvent('q6:edit-line',{bubbles:true,detail:{row}})); });
    target.appendChild(card);
   }
   const imageBox=row.querySelector('[data-product-image]'); const img=imageBox?.querySelector('img'); const cardImage=card.querySelector('.rn-q6-line-image');
   cardImage.replaceChildren();
   if(img?.src){const view=new Image();view.src=img.src;view.alt='';view.loading='lazy';view.className='rn-q6-line-image';cardImage.replaceWith(view);}
   else {const initial=window.RNAvatar?.initial(name);if(window.RNAvatar)cardImage.classList.add(window.RNAvatar.toneClass(name));if(initial)cardImage.textContent=initial;else{cardImage.classList.add('is-product-icon');const icon=document.createElement('i');icon.className='ri-box-3-line';icon.setAttribute('aria-hidden','true');cardImage.append(icon);}}
   card.querySelector('[data-q6-name]').textContent=name;
   const brand=picker?.dataset.brand || '';
   const unit=picker?.dataset.unit || '';
   const meta=row.dataset.q6Meta || [brand,unit].filter(Boolean).join(' · ');
   card.querySelector('[data-q6-meta]').textContent=meta || '';
   const qty=numeric(row.querySelector('.quantity')); const buy=numeric(row.querySelector('input[id^="buyPrice"]'));
   const sale=numeric(row.querySelector('input.sale-price')); const totalValue=numeric(row.querySelector('input[id^="totalAmount"]')) || qty*buy;
   card.querySelector('[data-q6-line-total]').textContent=money(totalValue);
   card.querySelector('[data-q6-line-price]').textContent=`${grouped(qty)} × ${money(buy)} · ${root.dataset.salePriceShort} ${money(sale)}`;
   const serialCount=countSerials(row); const serialButton=card.querySelector('[data-q6-serial-tag]'); serialButton.hidden=serialCount===0; card.querySelector('[data-q6-serial-text]').textContent=serialCount?`${root.dataset.serialLabel} ${localDigits(serialCount)} / ${grouped(qty)}`:'';
  });
  existing.forEach((card, idx) => { if (!seen.has(idx)) card.remove(); });
  const count=actual.filter(row=>row.querySelector('input[name^="selectProductName["]')?.value && row.querySelector('input[name^="selectProductName["]')?.value!=='Loading...').length;
  $('[data-q6-empty]').hidden=count>0;
  $('[data-q6-item-count]').textContent=`(${localDigits(count)})`;
  $('[data-q6-payment-count]').textContent=localDigits(count);
  const qty=actual.reduce((sum,row)=>sum+numeric(row.querySelector('.quantity')),0);
  $('[data-q6-total-qty]').textContent=grouped(qty);
  let subtotal=actual.reduce((sum,row)=>sum+(numeric(row.querySelector('input[id^="totalAmount"]')) || numeric(row.querySelector('.quantity'))*numeric(row.querySelector('input[id^="buyPrice"]'))),0);
  let computedGrand=numeric(grandInput);
  if (!computedGrand && subtotal) computedGrand=subtotal;
  $('[data-q6-subtotal]').textContent=money(subtotal);
  $('[data-q6-grand]').textContent=money(computedGrand);
  $('[data-q6-total]').textContent=money(computedGrand);
  updateDue(computedGrand);
  refreshPickerFooter();
  if (cartKey && !restoringCart) {
   try {
    const lines = actual.map(row => ({
     product_id: row.querySelector('input[name^="productName["]')?.value || '',
     name: row.querySelector('input[name^="selectProductName["]')?.value || '',
     quantity: row.querySelector('.quantity')?.value || '1',
     buy_price: row.querySelector('input[id^="buyPrice"]')?.value || '',
     sale_price: row.querySelector('input.sale-price')?.value || '',
    })).filter(line => line.product_id);
    const saved = readCart();
    saved.supplier_id = supplierSelect?.value || '';
    saved.lines = lines;
    saved.counts = {};
    lines.forEach(line => { saved.counts[line.product_id] = Number(line.quantity) || 1; });
    Object.assign(saved, {
     discount_status: discountStatus?.value || '1', discount_amount: discountAmount?.value || '0',
     discount_percent: discountPercent?.value || '0', paid_amount: paidInput?.value || '0',
     payment_method: mobileMethod?.value || 'cash', note: noteInput?.value || '' });
    localStorage.setItem(cartKey, JSON.stringify(saved));
    if (legacyCartKey) localStorage.removeItem(legacyCartKey);
    picker?.querySelectorAll('[data-picker-count]').forEach(badge=>{const n=Number(saved.counts[badge.dataset.pickerCount]||0);badge.textContent=n?localDigits(n):'';badge.hidden=!n;});
   } catch (_) {}
  }
 };
 let purchaseSwipeStart=null;
 $('[data-q6-lines]')?.addEventListener('touchstart',event=>{purchaseSwipeStart={x:event.changedTouches[0].clientX,y:event.changedTouches[0].clientY};},{passive:true});
 $('[data-q6-lines]')?.addEventListener('touchend',event=>{if(!purchaseSwipeStart)return;const dx=event.changedTouches[0].clientX-purchaseSwipeStart.x,dy=event.changedTouches[0].clientY-purchaseSwipeStart.y;purchaseSwipeStart=null;if(dx < -70 && Math.abs(dx)>Math.abs(dy))event.target.closest('.rn-q6-line-card')?.querySelector('.rn-q6-line-remove')?.click();},{passive:true});
 $('[data-q6-lines]')?.addEventListener('click',event=>{
  const button=event.target.closest('.rn-q6-line-remove');if(!button)return;event.preventDefault();event.stopPropagation();
  const card=button.closest('.rn-q6-line-card'), row=[...rows.querySelectorAll('tr.product-row')].find(item=>item.dataset.idx===card.dataset.idx);if(!row)return;
  const snapshot={product_id:row.querySelector('input[name^="productName["]')?.value||'',name:row.querySelector('input[name^="selectProductName["]')?.value||'',quantity:row.querySelector('.quantity')?.value||'1',buy_price:row.querySelector('input[id^="buyPrice"]')?.value||'',sale_price:row.querySelector('input.sale-price')?.value||''};
  row.querySelector('.remove-row')?.click();
  window.RNToast?.undo('মুছে ফেলা হয়েছে · ফিরিয়ে আনুন',()=>{
   picker?.dispatchEvent(new CustomEvent('rn:product-picker:selected',{bubbles:true,detail:{...snapshot,mode:'purchase',quantity:1}}));
   const started=Date.now();const restore=()=>{const restored=[...rows.querySelectorAll('tr.product-row')].find(item=>item.querySelector('input[name^="productName["]')?.value===snapshot.product_id);if(restored){for(const [selector,value] of [['.quantity',snapshot.quantity],['input[id^="buyPrice"]',snapshot.buy_price],['input.sale-price',snapshot.sale_price]]){const field=restored.querySelector(selector);if(field){field.value=value;poke(field);}}renderRows();}else if(Date.now()-started<5000)setTimeout(restore,60);};restore();
  });
 });
 const updateDue = grand => {
  const paid=numeric($('[data-q6-paid]'));
  const due=Math.max(0, grand-paid);
  $('[data-q6-new-due]').textContent=money(due);
  $('[data-q6-due-total]').textContent=money(openingDue+due);
 };
 rows.addEventListener('input', renderRows);
 rows.addEventListener('change', renderRows);
 new MutationObserver(renderRows).observe(rows,{childList:true,subtree:true,attributes:true,attributeFilter:['value']});
 renderRows();
 pickerDone?.addEventListener('click', closeSheets);

 root.addEventListener('q6:edit-line', event => {
  activeRow=event.detail.row;
  const field=selector=>activeRow.querySelector(selector);
  $('[data-q6-line-name]').textContent=field('input[name^="selectProductName["]')?.value || '';
  $('[data-q6-line-meta]').textContent='';
  $('[data-q6-line-qty]').value=field('.quantity')?.value || '1';
  $('[data-q6-line-buy]').value=field('input[id^="buyPrice"]')?.value || '0';
  $('[data-q6-line-sale]').value=field('input.sale-price')?.value || '0';
  $('[data-q6-vat-check]').checked=!!field('.include-vat')?.checked;
  $('[data-q6-vat-percent]').value=field('.vat-percent')?.value || '0';
  $('[data-q6-warranty]').value=field('.warranty-days')?.value || '';
  const qty=numeric($('[data-q6-line-qty]')), buy=numeric($('[data-q6-line-buy]')), sale=numeric($('[data-q6-line-sale]'));
  const profit=buy>0?(sale-buy):0; const pct=buy>0?profit/buy*100:0;
  $('[data-q6-profit]').textContent=`${money(profit)} (${grouped(pct)}%)`;
  $('[data-q6-serial-count]').textContent=`${localDigits(countSerials(activeRow))} / ${grouped(qty)}`;
  showSheet('line');
 });
 const updateLine = () => {
  if (!activeRow) return;
  const assign=(selector,value)=>{const field=activeRow.querySelector(selector);if(field){field.value=value;poke(field);}};
  assign('.quantity',Math.max(1,parseInt($('[data-q6-line-qty]').value||'1',10)));
  assign('input[id^="buyPrice"]',$('[data-q6-line-buy]').value);
  assign('input.sale-price',$('[data-q6-line-sale]').value);
  const vat=activeRow.querySelector('.include-vat'); if(vat){vat.checked=$('[data-q6-vat-check]').checked;poke(vat);}
  assign('.vat-percent',$('[data-q6-vat-percent]').value);
  assign('.warranty-days',$('[data-q6-warranty]').value);
  renderRows();
 };
 ['[data-q6-line-qty]','[data-q6-line-buy]','[data-q6-line-sale]','[data-q6-vat-check]','[data-q6-vat-percent]','[data-q6-warranty]'].forEach(selector=>$(selector)?.addEventListener('input',updateLine));
 $('[data-q6-step="-1"]')?.addEventListener('click',()=>{const input=$('[data-q6-line-qty]');input.value=String(Math.max(1,numeric(input)-1));updateLine();});
 $('[data-q6-step="1"]')?.addEventListener('click',()=>{const input=$('[data-q6-line-qty]');input.value=String(numeric(input)+1);updateLine();});
 $('[data-q6-open-serial]')?.addEventListener('click',()=>activeRow?.querySelector('.open-serials')?.click());
 $('[data-q6-line-done]')?.addEventListener('click',()=>{updateLine();closeSheets();activeRow=null;});
 $('[data-q6-remove-line]')?.addEventListener('click',()=>{activeRow?.querySelector('.remove-row')?.click();closeSheets();activeRow=null;});

 const main=$('[data-q6-main]'), payment=$('[data-q6-payment]'), mainFooter=$('[data-q6-main-footer]');
 const openPayment=()=>{
  if(!supplierSelect.value){showSheet('supplier');return;}
  if(!rows.querySelector('tr.product-row')){showSheet('products');return;}
  renderRows(); main.hidden=true;mainFooter.hidden=true;payment.hidden=false;window.scrollTo(0,0);
  $('[data-q6-discount-value]').value=discountStatus.value==='2'?discountPercent.value||'0':discountAmount.value||'0';
  root.querySelectorAll('[data-q6-discount-type]').forEach(button=>button.classList.toggle('is-active',button.dataset.q6DiscountType===(discountStatus.value||'1')));
  $('[data-q6-paid]').value=paidInput.value||'0';
  noteInput.value=noteInput.value||'';
  updateDue(numeric(grandInput)||[...rows.querySelectorAll('tr.product-row')].reduce((s,row)=>s+numeric(row.querySelector('.quantity'))*numeric(row.querySelector('input[id^="buyPrice"]')),0));
 };
 const closePayment=()=>{payment.hidden=true;main.hidden=false;mainFooter.hidden=false;window.scrollTo(0,0);};
 $('[data-q6-open-payment]')?.addEventListener('click',openPayment);
 root.querySelectorAll('[data-q6-back-main]').forEach(button=>button.addEventListener('click',closePayment));
 root.querySelectorAll('[data-q6-method]').forEach(button=>button.addEventListener('click',()=>{
  root.querySelectorAll('[data-q6-method]').forEach(item=>item.classList.toggle('is-active',item===button));
  mobileMethod.value=button.dataset.q6Method;
  if(desktopMethod)desktopMethod.value=mobileMethod.value;
  if(button.dataset.q6Method==='due') $('[data-q6-paid]').value='0';
  updateDue(numeric(grandInput));
 }));
 root.querySelectorAll('[data-q6-discount-type]').forEach(button=>button.addEventListener('click',()=>{
  root.querySelectorAll('[data-q6-discount-type]').forEach(item=>item.classList.toggle('is-active',item===button));
  discountStatus.value=button.dataset.q6DiscountType;
  $('[data-q6-discount-value]').value=button.dataset.q6DiscountType==='2'?discountPercent.value||'0':discountAmount.value||'0';
  poke(discountStatus);callFinance();renderRows();
 }));
 $('[data-q6-discount-value]')?.addEventListener('input',event=>{
  if(discountStatus.value==='2'){discountPercent.value=event.target.value;poke(discountPercent);}else{discountAmount.value=event.target.value;poke(discountAmount);}
  callFinance();renderRows();
 });
 $('[data-q6-paid]')?.addEventListener('input',event=>{paidInput.value=event.target.value;poke(paidInput);updateDue(numeric(grandInput));});
 root.querySelectorAll('[data-q6-paid-fraction]').forEach(button=>button.addEventListener('click',()=>{
  const total=numeric(grandInput)||[...rows.querySelectorAll('tr.product-row')].reduce((s,row)=>s+numeric(row.querySelector('.quantity'))*numeric(row.querySelector('input[id^="buyPrice"]')),0);
  const paid=Number((total*Number(button.dataset.q6PaidFraction)).toFixed(2));
  $('[data-q6-paid]').value=String(paid);paidInput.value=String(paid);poke(paidInput);updateDue(total);
 }));
 $('[data-q6-note]')?.addEventListener('input',event=>{noteInput.value=event.target.value;poke(noteInput);});
 $('[data-q6-submit]')?.addEventListener('click',()=>{
  updateDue(numeric(grandInput));
  if(desktopMethod)desktopMethod.disabled=true;
  mobileMethod.disabled=false;
  $('[data-q6-submit]').disabled=true;
  const submit=form.querySelector('#purchaseSaveButton');
  if(submit)submit.click();else form.requestSubmit();
 });
 form.addEventListener('submit',()=>{ if(mobile.matches){ if(desktopMethod)desktopMethod.disabled=true; mobileMethod.disabled=false; } });
 form.querySelector('#purchaseSaveButton')?.addEventListener('click',event=>{ if(mobile.matches && !payment.hidden){ event.preventDefault();form.requestSubmit(); } });
 form.querySelector('#purchaseSaveButton')?.setAttribute('data-q6-desktop-submit','1');
 document.addEventListener('click',event=>{
  const add=event.target.closest('[data-q6-open-picker]'); if(add){if(!supplierSelect.value){showSheet('supplier');return;} showSheet('products');}
 });
 document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeSheets();if(!payment.hidden)closePayment();}});
 if (cartKey) {
  try {
   const saved = readCart();
   if (saved && Array.isArray(saved.lines) && saved.lines.length) {
    if (saved.supplier_id && supplierSelect) { supplierSelect.value = saved.supplier_id; supplierSelect.dispatchEvent(new Event('change', {bubbles:true})); }
    (async () => {
     for (const line of saved.lines) {
      const before = rows.querySelectorAll('tr.product-row').length;
      picker.dispatchEvent(new CustomEvent('rn:product-picker:selected', {bubbles:true, detail:{product_id:line.product_id,name:line.name,buy_price:line.buy_price,sale_price:line.sale_price,quantity:1,mode:'purchase'}}));
      const started = Date.now();
      while (rows.querySelectorAll('tr.product-row').length === before && Date.now()-started < 5000) await new Promise(resolve => setTimeout(resolve, 50));
      const row = [...rows.querySelectorAll('tr.product-row')].at(-1);
      if (row) {
       for (const [selector,value] of [['.quantity',line.quantity],['input[id^="buyPrice"]',line.buy_price],['input.sale-price',line.sale_price]]) {
        const field = row.querySelector(selector); if (field && value !== '') { field.value = value; field.dispatchEvent(new Event('input',{bubbles:true})); field.dispatchEvent(new Event('change',{bubbles:true})); }
       }
      }
     }
     if (saved.discount_status) discountStatus.value = saved.discount_status;
     if (saved.discount_amount !== undefined) discountAmount.value = saved.discount_amount;
     if (saved.discount_percent !== undefined) discountPercent.value = saved.discount_percent;
     if (saved.paid_amount !== undefined) paidInput.value = saved.paid_amount;
     if (saved.payment_method) { mobileMethod.value = saved.payment_method; if (desktopMethod) desktopMethod.value = saved.payment_method; }
     if (saved.note !== undefined) noteInput.value = saved.note;
     [discountStatus, discountAmount, discountPercent, paidInput, noteInput].forEach(field => poke(field));
     restoringCart = false; renderRows();
    })().catch(() => { restoringCart = false; });
   } else restoringCart = false;
  } catch (_) { restoringCart = false; }
 }
})();
