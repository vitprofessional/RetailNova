(() => {
 const root = document.querySelector('[data-q6-purchase]');
 const form = document.getElementById('savePurchase');
 if (!root || !form) return;
 const $ = selector => root.querySelector(selector);
 const rows = form.querySelector('#productDetails');
 const picker = form.querySelector('[data-rn-product-picker][data-mode="purchase"]');
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
    footer.append(price,serial); card.append(image,copy,total,footer);
    card.addEventListener('click', event => { if (event.target.closest('[data-q6-serial-tag]')) { card.dispatchEvent(new CustomEvent('q6:edit-line',{bubbles:true,detail:{row}})); return; } card.dispatchEvent(new CustomEvent('q6:edit-line',{bubbles:true,detail:{row}})); });
    target.appendChild(card);
   }
   const imageBox=row.querySelector('[data-product-image]'); const img=imageBox?.querySelector('img'); const cardImage=card.querySelector('.rn-q6-line-image');
   cardImage.replaceChildren();
   if(img?.src){const view=new Image();view.src=img.src;view.alt='';view.loading='lazy';view.className='rn-q6-line-image';cardImage.replaceWith(view);}
   else {cardImage.textContent=imageBox?.textContent?.trim() || name.trim().slice(0,1);}
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
 };
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
})();
