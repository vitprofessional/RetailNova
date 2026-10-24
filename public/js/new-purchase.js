(() => {
 const form = document.getElementById('savePurchase');
 if (!form) return;
 const rows = form.querySelector('#productDetails'), counter = form.querySelector('#purchaseItemCount');
 const supplier = form.querySelector('#supplierName'), product = form.querySelector('#productName');
 const supplierHelp = form.querySelector('[data-purchase-supplier-help]');
 const picker = form.querySelector('.rn-m-purchase-picker');
 const productCards = picker?.querySelector('.rn-m-purchase-product-cards');
 const productSearch = picker?.querySelector('#purchaseProductSearch');
 const refreshProductCards = () => {
  if (!productCards || !product) return;
  const filter = (productSearch?.value || '').trim().toLocaleLowerCase();
  productCards.replaceChildren();
  [...product.options].filter(option => option.value && option.textContent.toLocaleLowerCase().includes(filter)).forEach(option => {
   const button = document.createElement('button'); button.type='button'; button.className='rn-m-purchase-product'; button.dataset.productId=option.value;
   const name=document.createElement('span'); name.textContent=option.textContent.trim();
   const icon=document.createElement('i'); icon.className='ri-add-line'; icon.setAttribute('aria-hidden','true');
   button.append(name,icon); productCards.append(button);
  });
 };
 productSearch?.addEventListener('input', refreshProductCards);
 productCards?.addEventListener('click', event => {
  const card=event.target.closest('.rn-m-purchase-product'); if(!card || product.disabled) return;
  product.value=card.dataset.productId; product.dispatchEvent(new Event('change',{bubbles:true}));
  form.querySelector('#addProductRow')?.click();
 });
 const refreshSupplierHelp = () => {
  if (!supplierHelp) return;
  const state = !supplier?.value ? 'noSupplier' : product?.value ? 'productSelected' : 'supplierSelected';
  const message = supplierHelp.dataset[state];
  if (message && supplierHelp.textContent !== message) supplierHelp.textContent = message;
 };
 const refresh = () => {
  const table = form.querySelector('#purchaseItemsScroll table');
  const labels = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
  rows.querySelectorAll('tr').forEach(row => {
   [...row.querySelectorAll(':scope > td')].forEach((cell,index) => {
    cell.dataset.label = labels[index] || '';
    cell.querySelectorAll('input:not([type="hidden"]),select,textarea').forEach(field => {
     if (!field.hasAttribute('aria-label')) field.setAttribute('aria-label', labels[index]);
    });
   });
   let badge = row.querySelector('.rn-purchase-serial-count');
   if (!badge) { badge = document.createElement('span'); badge.className='rn-purchase-serial-count'; badge.setAttribute('aria-live','polite'); row.querySelector('.open-serials').after(badge); }
   const serialText = row.querySelectorAll('input[data-serial]').length + ' ' + counter.dataset.serialLabel;
   if(badge.textContent!==serialText) badge.textContent=serialText;
   row.querySelectorAll('input.quantity').forEach(input=>{
    if(input.parentElement.classList.contains('rn-m-stepper')) return;
    const wrap=document.createElement('div');wrap.className='rn-m-stepper';const minus=document.createElement('button');minus.type='button';minus.dataset.step='-1';minus.setAttribute('aria-label','−');minus.textContent='−';const plus=document.createElement('button');plus.type='button';plus.dataset.step='1';plus.setAttribute('aria-label','+');plus.textContent='+';input.before(wrap);wrap.append(minus,input,plus);
   });
  });
  const count = rows.querySelectorAll('tr').length;
  counter.textContent=String(count);
  form.querySelector('#purchaseEmptyState').hidden=count>0;
  form.querySelector('#purchaseItemsScroll').hidden=count===0;
 };
 refresh();
 refreshProductCards();
 refreshSupplierHelp();
 form.addEventListener('change', event => {
  if (event.target === supplier || event.target === product) refreshSupplierHelp();
 });
 new MutationObserver(refreshSupplierHelp).observe(product, {childList:true});
 new MutationObserver(refreshProductCards).observe(product, {childList:true});
 rows.addEventListener('click',event=>{const step=event.target.closest('[data-step]');if(!step)return;const input=step.parentElement.querySelector('input.quantity');const value=Math.max(1,(parseInt(input.value||'1',10)||1)+parseInt(step.dataset.step,10));input.value=String(value);input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}));});
 new MutationObserver(refresh).observe(rows,{childList:true,subtree:true});
 // Financial and serial payloads are owned exclusively by existing purchase handlers.
 const snapshot = () => JSON.stringify([...form.querySelectorAll('input,select,textarea')]
  .filter(field=>field.form===form && field.name && field.name!=='_token')
  .map(field=>[field.name,field.value,field.type==='checkbox' ? field.checked : null]));
 let initial='',saving=false,leaving=false,leavePending=false;
 window.addEventListener('load',()=>{initial=snapshot();});
 const dirty = () => initial && snapshot()!==initial;
 form.addEventListener('click',async event => {
  const link=event.target.closest('[data-purchase-leave]');
  if(!link || !dirty()) return;
  event.preventDefault();
  if(leavePending) return;
  leavePending=true;
  try {
  const accepted=window.Swal?.fire
   ? (await window.Swal.fire({title:form.dataset.leaveTitle,text:form.dataset.leaveMessage,icon:'warning',showCancelButton:true,confirmButtonText:form.dataset.leaveConfirm,cancelButtonText:form.dataset.leaveCancel})).isConfirmed===true
   : window.confirm(form.dataset.leaveMessage);
  if(accepted){leaving=true;window.location.assign(link.href);}
  } finally {leavePending=false;}
 });
 document.addEventListener('submit', event => {
  if(event.target===form && !event.defaultPrevented) saving=true;
 });
 window.addEventListener('beforeunload', event=>{if(!saving&&!leaving&&dirty()){event.preventDefault();event.returnValue='';}});
 window.addEventListener('pageshow',()=>{saving=false;leaving=false;});
})();
