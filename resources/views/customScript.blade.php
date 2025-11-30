<script>
/* RN custom script - guarded initialization to avoid errors when jQuery loads after this file */
// Base URL helper to avoid 404s when app runs in subfolders
window.RN_BASE = window.RN_BASE || "{{ url('/') }}";
if(window.RN_BASE && window.RN_BASE.endsWith('/')){ try{ window.RN_BASE = window.RN_BASE.replace(/\/+$/,''); }catch(e){} }

// Debug toggle (temporary): enable with `window.__RNDEBUG = true` or `enableRNDebug()`
window.__RNDEBUG = window.__RNDEBUG || false;
// Temporarily enable sale debug to trace redirects in subfolder deployments
window.__SALEDEBUG = (typeof window.__SALEDEBUG !== 'undefined') ? window.__SALEDEBUG : true;
window.enableRNDebug = function(){ window.__RNDEBUG = true; console.warn('RNDEBUG enabled'); };
window.disableRNDebug = function(){ window.__RNDEBUG = false; console.warn('RNDEBUG disabled'); };

// Lightweight toast helper: use existing `showToast` if present, otherwise provide a minimal fallback
if(typeof window.showToast !== 'function'){
    window.showToast = function(title, text, icon){
        try{
            // Prefer SweetAlert if available
            if(window.Swal || window.swal){
                var s = window.Swal || window.swal;
                try{ s.fire ? s.fire({ title: title, text: text, icon: icon || 'info', timer: 2500, toast: true, position: 'top-end', showConfirmButton:false }) : s({ title: title, text: text }); }catch(e){ try{ s({ title: title, text: text }); }catch(_){} }
                return;
            }
            // Prefer Bootstrap 5 toasts if available
            if(window.bootstrap && document){
                try{
                    var container = document.getElementById('rn-toast-container');
                    if(!container){ container = document.createElement('div'); container.id = 'rn-toast-container'; container.style.position = 'fixed'; container.style.top = '1rem'; container.style.right = '1rem'; container.style.zIndex = '1080'; document.body.appendChild(container); }
                    var toast = document.createElement('div'); toast.className = 'toast'; toast.setAttribute('role','alert'); toast.setAttribute('aria-live','assertive'); toast.setAttribute('aria-atomic','true');
                    toast.innerHTML = '<div class="toast-header"><strong class="me-auto">'+(title||'')+'</strong><small></small><button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body">'+(text||'')+'</div>';
                    container.appendChild(toast);
                    try{ var bToast = new bootstrap.Toast(toast, { delay: 2500 }); bToast.show(); }catch(e){ toast.style.display='block'; setTimeout(function(){ try{ toast.parentNode.removeChild(toast); }catch(_){} },2500); }
                    return;
                }catch(e){}
            }
            // Fallback: simple alert non-blocking via console + small on-page message
            console.log('Toast:', title, text);
            try{ var el = document.createElement('div'); el.style.position='fixed'; el.style.top='1rem'; el.style.right='1rem'; el.style.background='#222'; el.style.color='#fff'; el.style.padding='8px 12px'; el.style.borderRadius='4px'; el.style.zIndex='1080'; el.textContent = (title? title + ': ' : '') + (text || ''); document.body.appendChild(el); setTimeout(function(){ try{ el.parentNode.removeChild(el); }catch(_){} },2500); }catch(e){}
        }catch(e){ /* ignore */ }
    };
}

// Core helper: parse numeric input safely
function numVal(v){ if(v === null || v === undefined) return 0; var s = String(v).trim(); if(!s) return 0; return Number(s.replace(/,/g,'')) || 0; }

// Recalculate a single purchase row (works for single-row and multi-row tables)
function recalcPurchaseRow(ctx){
    try{
        if(window.__RNDEBUG) console.debug('recalcPurchaseRow start', ctx && ctx.target? ctx.target : ctx);
        var el = ctx && ctx.target ? ctx.target : ctx;
        if(!el) el = document;
        var row = (el && el.closest) ? (el.closest('tr.product-row') || el.closest('tr')) : document.querySelector('tr.product-row') || document;

        var qtyEl = row.querySelector('.quantity') || document.getElementById('quantity');
        var buyEl = row.querySelector('[id^="buyPrice"]') || row.querySelector('.buy-price') || document.getElementById('buyPrice');
        var saleEl = row.querySelector('[id^="salePriceExVat"]') || row.querySelector('.sale-price') || document.getElementById('salePriceExVat');
        var totalEl = row.querySelector('[id^="totalAmount"]') || row.querySelector('.total-amount') || document.getElementById('totalAmount');
        var profitEl = row.querySelector('[id^="profitMargin"]') || row.querySelector('.profit-margin') || document.getElementById('profitMargin');

        if(!qtyEl || !buyEl || !totalEl) return;
        var qty = numVal(qtyEl.value);
        var buy = numVal(buyEl.value);
        var sale = saleEl ? numVal(saleEl.value) : 0;
        // Totals are calculated from Buy Price only per new requirement
        var total = (buy * qty) || 0;
        totalEl.value = total ? (Math.round((total + Number.EPSILON) * 100) / 100) : '';
        totalEl.classList.add('total-amount');

        if(profitEl){
            if(buy > 0 && sale > 0){
                var profitValue = (sale * qty) - (buy * qty);
                var profitPercent = ((profitValue / (buy * qty)) * 100) || 0;
                profitEl.value = Number(profitPercent.toFixed(2));
            } else {
                profitEl.value = '';
            }
        }

        // Compute and set Sale Price (Inc. VAT) for this row using per-row VAT controls only
        try{
            var saleIncEl = row.querySelector('.sale-price-inc') || row.querySelector('[id^="salePriceInVat"]');
            var vatEl = row.querySelector('.vat-percent') || row.querySelector('[id^="vatStatus"]');
            var incEl = row.querySelector('.include-vat');
            var vatPct = vatEl ? numVal(vatEl.value) : 0;
            var includeFlag = false; if(incEl){ if(incEl.type === 'checkbox') includeFlag = !!incEl.checked; else includeFlag = (String(incEl.value) === '1' || String(incEl.value).toLowerCase() === 'yes' || String(incEl.value).toLowerCase() === 'true'); }
            if(saleIncEl){
                var saleIncVal = sale;
                if(includeFlag && vatPct > 0){ saleIncVal = Number((sale * (1 + (vatPct/100))).toFixed(2)); }
                try{ if('value' in saleIncEl) saleIncEl.value = saleIncVal; else saleIncEl.innerHTML = saleIncVal; }catch(_){ }
            }
            // Compute per-row VAT amount and display it as plain text under the VAT input (per user's request)
            try{
                var rowVatEl = row.querySelector('.row-vat-amount') || document.getElementById('rowVat__' + (row.getAttribute && row.getAttribute('data-idx') || '0'));
                var badgeEl = row.querySelector('.vat-badge') || document.getElementById('vatBadge__' + (row.getAttribute && row.getAttribute('data-idx') || '0'));
                var vatAmount = 0;
                if(includeFlag && vatPct > 0 && sale > 0 && qty > 0){ vatAmount = sale * qty * (vatPct/100); }
                if(rowVatEl){ rowVatEl.textContent = vatAmount ? ('VAT: ' + Number(vatAmount.toFixed(2))) : ''; }
                // Visual highlight and badge
                try{
                    if(includeFlag){ row.classList.add('vat-enabled'); if(badgeEl){ badgeEl.style.display = 'inline-block'; badgeEl.title = vatPct ? ('VAT included: ' + vatPct + '%') : 'VAT included'; } }
                    else { row.classList.remove('vat-enabled'); if(badgeEl){ badgeEl.style.display = 'none'; badgeEl.title = ''; } }
                }catch(e){}
            }catch(e){ /* ignore per-row VAT text failures */ }
        }catch(e){ /* ignore VAT display errors */ }

        // Update aggregated totals after row recalc
        updateOtherDetails();
        if(window.__RNDEBUG) console.debug('recalcPurchaseRow end', { qty: qty, buy: buy, sale: sale, total: total });
    }catch(e){ console.warn('recalcPurchaseRow error', e); }
}

// Update grand total, discount, due and related fields
function updateOtherDetails(){
    try{
        // Delegate to central financial recalculation
        if(typeof window.recalcFinancials === 'function'){ window.recalcFinancials(); return; }
    }catch(e){ console.warn('updateOtherDetails error', e); }
}

// Central financial recalculation: sums buy-price totals, applies discount and paid, updates grand/due fields
window.recalcFinancials = function(){
    try{
        if(window.__RNDEBUG) console.debug('recalcFinancials start');
        var totalInputs = Array.prototype.slice.call(document.querySelectorAll('input[id^="totalAmount"], input.total-amount'));
        var base = 0; totalInputs.forEach(function(inp){ base += numVal(inp.value); });

        var discountStatusEl = document.getElementById('discountStatus');
        var discountAmountEl = document.getElementById('discountAmount');
        var discountPercentEl = document.getElementById('discountPercent');
        var paidEl = document.getElementById('paidAmount');

        var disType = discountStatusEl ? discountStatusEl.value : '';
        var disAmount = discountAmountEl ? numVal(discountAmountEl.value) : 0;
        var disPercent = discountPercentEl ? numVal(discountPercentEl.value) : 0;
        var discount = 0;
        if(disType == '1'){
            discount = disAmount;
        } else if(disType == '2'){
            discount = (base * disPercent / 100) || 0;
            // ensure discountAmount displays computed amount for clarity
            if(discountAmountEl) discountAmountEl.value = Number(discount.toFixed(2));
        }

        var grand = Math.max(0, base - discount);
        var totalSaleEl = document.getElementById('totalSaleAmount'); if(totalSaleEl) totalSaleEl.value = Number(base.toFixed(2));
        var grandEl = document.getElementById('grandTotal'); if(grandEl) grandEl.value = Number(grand.toFixed(2));

        var paid = paidEl ? numVal(paidEl.value) : 0;
        var due = Math.max(0, grand - paid);
        var dueEl = document.getElementById('dueAmount'); if(dueEl) dueEl.value = Number(due.toFixed(2));
        var totalDueEl = document.getElementById('totalDue'); if(totalDueEl) totalDueEl.value = Number(due.toFixed(2));
        var curDueEl = document.getElementById('curDue'); if(curDueEl) curDueEl.value = Number(due.toFixed(2));
        if(window.__RNDEBUG) console.debug('recalcFinancials end', { base: base, discount: discount, grand: grand, paid: paid, due: due });
    }catch(e){ console.warn('recalcFinancials error', e); }
};

function discountType(){ try{ updateOtherDetails(); }catch(e){} }

function discountAmountChange(){
    try{
        var discountAmountEl = document.getElementById('discountAmount');
        var discountPercentEl = document.getElementById('discountPercent');
        var base = 0;
        try{ var totalInputs = Array.prototype.slice.call(document.querySelectorAll('input[id^="totalAmount"], input.total-amount')); totalInputs.forEach(function(inp){ base += numVal(inp.value); }); }catch(_){ base = 0; }
        var dam = discountAmountEl ? numVal(discountAmountEl.value) : 0;
        if(discountPercentEl && base > 0){ discountPercentEl.value = Number(((dam / base) * 100).toFixed(2)); }
        // Recalculate financials after manual discount amount change
        if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); else updateOtherDetails();
    }catch(e){ console.warn(e); }
}

function discountPercentChange(){
    try{
        var discountAmountEl = document.getElementById('discountAmount');
        var discountPercentEl = document.getElementById('discountPercent');
        var base = 0;
        try{ var totalInputs = Array.prototype.slice.call(document.querySelectorAll('input[id^="totalAmount"], input.total-amount')); totalInputs.forEach(function(inp){ base += numVal(inp.value); }); }catch(_){ base = 0; }
        var per = discountPercentEl ? numVal(discountPercentEl.value) : 0;
        if(discountAmountEl && base > 0){ discountAmountEl.value = Number(((per/100) * base).toFixed(2)); }
        // Recalculate financials after discount percent change
        if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); else updateOtherDetails();
    }catch(e){ console.warn(e); }
}

function dueCalculate(){ try{ updateOtherDetails(); }catch(e){} }

// Wire native delegated handlers for row inputs (works with dynamic rows)
document.addEventListener('input', function(e){
    try{
        var t = e.target;
        if(!t) return;
        // Single-row fields
        if(t.matches && (t.matches('#quantity') || t.matches('#buyPrice') || t.matches('#salePriceExVat'))){ recalcPurchaseRow(t); }

        // Per-row fields
        var row = t.closest && t.closest('.product-row');
        if(row && (t.matches('.quantity') || t.matches('[id^="buyPrice"]') || t.matches('.sale-price') || t.matches('[id^="salePriceExVat"]'))){ recalcPurchaseRow(t); }
    }catch(e){}
}, true);

// VAT include handler: if an input with class 'include-vat' toggles or a field with class 'vat-percent' changes,
// compute sale price ex VAT when necessary. Assumes per-row vat inputs may exist.
document.addEventListener('change', function(e){
    try{
        var t = e.target; if(!t) return;
        if(t.matches && (t.matches('.include-vat') || t.matches('.vat-percent'))){
            var row = t.closest && t.closest('.product-row');
            if(!row) row = document;
            var saleInc = row.querySelector('.sale-price-inc') || row.querySelector('#salePriceIncVat') || null;
            var saleEx = row.querySelector('.sale-price') || row.querySelector('[id^="salePriceExVat"]') || null;
            var vatEl = row.querySelector('.vat-percent') || row.querySelector('[id^="vatStatus"]') || null;

            // If the include-vat checkbox was toggled, enable/disable the vat input
            if(t.matches('.include-vat')){
                try{ if(vatEl) vatEl.disabled = !t.checked; }catch(e){}
            }

            // determine whether this row (or global) is configured to include VAT
            var includeFlag = false;
            try{
                var incEl = row.querySelector('.include-vat');
                if(!incEl) incEl = document.getElementById('includeVat');
                includeFlag = incEl ? !!incEl.checked : false;
            }catch(e){ includeFlag = false; }

            var vat = vatEl ? numVal(vatEl.value) : 0;
            // Visual highlight + badge for include-vat
            try{
                var badge = row.querySelector('.vat-badge') || document.getElementById('vatBadge__' + (row.getAttribute && row.getAttribute('data-idx') || '0'));
                if(includeFlag){ row.classList.add('vat-enabled'); if(badge){ badge.style.display='inline-block'; badge.title = vat ? ('VAT included: '+vat+'%') : 'VAT included'; } }
                else { row.classList.remove('vat-enabled'); if(badge){ badge.style.display='none'; badge.title = ''; } }
            }catch(e){}
            // Only convert inclusive price to exclusive when include flag is set and vat > 0
            if(includeFlag && saleInc && saleEx && vat>0){
                var inc = numVal(saleInc.value);
                var ex = inc / (1 + vat/100);
                saleEx.value = Number(ex.toFixed(2));
                recalcPurchaseRow(row);
            } else {
                // ensure row recalculation runs to keep totals consistent when toggling include-vat off
                recalcPurchaseRow(row);
            }
        }
    }catch(e){ }
}, true);

// (Real-time inclusive-price input handler removed — VAT conversion handled on change events only)

// Generic AJAX form handler for modal forms (supplier/product etc.)
document.addEventListener('submit', function(e){
    try{
        var f = e.target;
        if(!f || !f.matches || !f.matches('form[data-ajax="true"]')) return;
        e.preventDefault();
        var action = f.getAttribute('action') || window.location.href;
        var method = (f.getAttribute('method') || 'POST').toUpperCase();
        var formData = new FormData(f);
        var url = action;
        var opts = { method: method, credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} };
        // If GET, serialize FormData to query string (fetch doesn't support body for GET reliably)
        if(method === 'GET'){
            var params = new URLSearchParams();
            formData.forEach(function(v,k){ params.append(k, v); });
            url = action + (action.indexOf('?') === -1 ? '?' : '&') + params.toString();
        } else {
            opts.body = formData;
        }
        fetch(url, opts).then(function(res){ return res.json ? res.json() : {}; }).then(function(result){
            // On success, if the form has data-target attribute, update that element's innerHTML
            try{
                var tgt = f.getAttribute('data-target');
                if(tgt && result && result.data){
                    var nodes = document.querySelectorAll(tgt);
                    nodes.forEach(function(n){ try{ n.innerHTML = result.data; }catch(e){} });
                }
                // close modal if bootstrap/jQuery present — fallback to manual cleanup
                var modalId = f.getAttribute('data-modal-id');
                if(modalId){
                    try{
                        var closeModalById = function(id){
                            var modal = document.getElementById(id);
                            if(!modal) return;
                            // Bootstrap 5 API
                            if(window.bootstrap && bootstrap.Modal){
                                try{
                                    var inst = bootstrap.Modal.getInstance(modal);
                                    if(inst) inst.hide();
                                    else { var tmp = new bootstrap.Modal(modal); tmp.hide(); }
                                }catch(e){}
                            }
                            // jQuery / Bootstrap 4
                            if(window.jQuery && window.jQuery(modal) && typeof window.jQuery(modal).modal === 'function'){
                                try{ window.jQuery('#'+id).modal('hide'); }catch(e){}
                            }
                            // Manual fallback: remove visible classes and backdrop
                            try{
                                modal.classList.remove('show');
                                modal.style.display = 'none';
                                modal.setAttribute('aria-hidden','true');
                                modal.removeAttribute('aria-modal');
                                // remove any backdrops
                                Array.prototype.slice.call(document.querySelectorAll('.modal-backdrop')).forEach(function(b){ if(b && b.parentNode) b.parentNode.removeChild(b); });
                                document.body.classList.remove('modal-open');
                                document.body.style.paddingRight = '';
                            }catch(e){}
                        };
                        // After close, attempt to sync serials from modal back to the row
                        var trySync = function(id){ try{ if(typeof window.syncSerialsFromModal === 'function'){ window.syncSerialsFromModal(id); } }catch(e){} };
                        // support multiple ids separated by spaces
                        modalId.split(/\s+/).forEach(function(id){ if(id){ closeModalById(id); trySync(id); } });
                    }catch(err){ console.warn('modal close failed', err); }
                }
                // reset form
                try{ f.reset(); }catch(e){}
            }catch(err){ console.warn('ajax-form result handling', err); }
        }).catch(function(err){ console.warn('ajax form submit failed', err); });
    }catch(e){}
});

// Re-use existing jQuery handlers included in other script fragments — ensure they still run
// (the rest of legacy handlers are included below)
// Existing delegated and jQuery-based handlers remain in the included script fragments

// Keep including other smaller script fragments as before
@include('scripts.purchase-scripts')
@include('scripts.product-scripts')
@include('scripts.sale-scripts')

// Ensure initial wiring for non-jQuery environment
document.addEventListener('DOMContentLoaded', function(){
    try{
        // initial recalc to populate totals if any values are present
        Array.prototype.slice.call(document.querySelectorAll('input.quantity, input[id^="buyPrice"], input[id^="salePriceExVat"]')).forEach(function(i){ try{ recalcPurchaseRow(i); }catch(e){} });
        // wire simple listeners to single-row controls
        var qty = document.getElementById('quantity'); if(qty) qty.addEventListener('input', recalcPurchaseRow);
        var buy = document.getElementById('buyPrice'); if(buy) buy.addEventListener('input', recalcPurchaseRow);
        var sale = document.getElementById('salePriceExVat'); if(sale) sale.addEventListener('input', recalcPurchaseRow);
        var discountStatus = document.getElementById('discountStatus'); if(discountStatus) discountStatus.addEventListener('change', discountType);
        var discountAmount = document.getElementById('discountAmount'); if(discountAmount) discountAmount.addEventListener('input', discountAmountChange);
        var discountPercent = document.getElementById('discountPercent'); if(discountPercent) discountPercent.addEventListener('input', discountPercentChange);
        var paidAmount = document.getElementById('paidAmount'); if(paidAmount) paidAmount.addEventListener('input', function(){ try{ if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); else dueCalculate(); }catch(e){} });
        // wire Add To List button for multi-row purchases
        try{
            var addBtn = document.getElementById('addProductRow');
            if(addBtn){
                addBtn.addEventListener('click', function(ev){
                    try{
                        if(window.__RNDEBUG) console.debug('addProductRow click');
                        // prefer explicit select with .js-product-select
                        var sel = document.querySelector('.js-product-select');
                        if(!sel) sel = document.getElementById('productName');
                        if(!sel){
                            try{ showToast && showToast('Error','Product select not found','error'); }catch(_){ alert('Product select not found'); }
                            return;
                        }
                        var pid = sel.value || sel.options && sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].value;
                        if(!pid){ try{ showToast && showToast('Warning','Please select a product first','warning'); }catch(_){ alert('Please select a product first'); } return; }
                        if(typeof addPurchaseRow === 'function'){
                            addPurchaseRow(pid);
                        } else {
                            try{ window.addPurchaseRow(pid); }catch(e){ console.warn('addPurchaseRow not available', e); }
                        }
                    }catch(e){ console.warn('addProductRow click', e); }
                });
            }
        }catch(e){ console.warn('failed to bind addProductRow', e); }
    }catch(e){ /* ignore */ }
});

// Supplier -> product enabling: keep original product options hidden and enable when supplier selected
document.addEventListener('DOMContentLoaded', function(){
    try{
        var productSelect = document.querySelector('.js-product-select');
        if(productSelect){
            // Save original options contained within special markers if present
            var html = '';
            var start = document.documentElement.innerHTML.indexOf('<!--product-options-start-->');
            if(start !== -1){
                // Fallback: try to extract between markers within the select element text
                // But simplest: capture any options rendered inside the select now (excluding the placeholder)
                html = '';
                Array.prototype.slice.call(productSelect.querySelectorAll('option')).forEach(function(o){ if(o.value) html += o.outerHTML; });
            }
            productSelect.dataset.origOptions = html;
            // Replace current options with the supplier-prompt and disable
            productSelect.innerHTML = '<option value="">Please select a Supplier First</option>';
            productSelect.disabled = true;
        }

        var supplier = document.getElementById('supplierName');
        if(supplier && productSelect){
            supplier.addEventListener('change', function(){
                try{
                    if(this.value){
                        // restore original options
                        if(productSelect.dataset.origOptions && productSelect.dataset.origOptions.length>0) productSelect.innerHTML = productSelect.dataset.origOptions;
                        productSelect.disabled = false;
                    } else {
                        productSelect.innerHTML = '<option value="">Please select a Supplier First</option>';
                        productSelect.disabled = true;
                    }
                }catch(e){ console.warn('supplier change handler', e); }
            });
        }
    }catch(e){ /* ignore */ }
});

// If jQuery is present, also bind delegated handlers so older inline code/events still trigger
try{
    if(window.jQuery){
        (function($){
            $(function(){
                try{
                    $(document).on('input change', '#buyPrice, [id^="buyPrice"], .buy-price', function(){ try{ recalcPurchaseRow(this); }catch(e){ console.warn('jq buyPrice handler', e); } });
                    $(document).on('input change', '#salePriceExVat, [id^="salePriceExVat"], .sale-price', function(){ try{ recalcPurchaseRow(this); }catch(e){ console.warn('jq salePrice handler', e); } });
                    $(document).on('input change', '#quantity, .quantity', function(){ try{ recalcPurchaseRow(this); }catch(e){ console.warn('jq quantity handler', e); } });
                    // Ensure paid amount changes also trigger the central financial recalculation
                    $(document).on('input change', '#paidAmount', function(){ try{ if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); else if(typeof dueCalculate === 'function') dueCalculate(); }catch(e){ console.warn('jq paidAmount handler', e); } });
                    // Keep legacy price/profit helpers callable
                    $(document).on('input change', '#salePriceExVat, #buyPrice, .sale-price, .buy-price', function(){ try{ if(typeof priceCalculation === 'function') priceCalculation(); }catch(e){} });
                }catch(e){ console.warn('jQuery init handlers failed', e); }
            });
        })(window.jQuery);
    }
}catch(e){ /* ignore */ }
// Synchronize serials entered in a modal back into the corresponding product row's hidden inputs
window.syncSerialsFromModal = function(modalId){
    try{
        if(!modalId) return;
        var modal = document.getElementById(modalId);
        if(!modal) return;
        // the Add Purchase page sets modal.setAttribute('data-current-idx', idx) when opening the serial modal
        var idx = modal.getAttribute('data-current-idx') || modal.getAttribute('data-idx') || modal.getAttribute('data-row-index');
        // If not present, try to find inputs inside modal named serialNumber[] (legacy)
        var serialEls = Array.prototype.slice.call(modal.querySelectorAll('#serialNumberBox input[name="serialNumber[]"], input[name^="serial"]'));
        if(!serialEls.length) return;

        // locate the target row by data-idx (template uses data-idx) or fallback to first .product-row
        var targetRow = null;
        if(idx){ targetRow = document.querySelector('tr.product-row[data-idx="'+idx+'"]') || document.querySelector('tr[data-idx="'+idx+'"]'); }
        if(!targetRow) targetRow = document.querySelector('tr.product-row') || null;
        if(!targetRow) return;

        // remove any existing hidden serial inputs for that row to avoid duplicates
        try{ Array.prototype.slice.call(targetRow.querySelectorAll('input[type="hidden"][data-serial]')).forEach(function(h){ h.parentNode.removeChild(h); }); }catch(e){}

        // add hidden inputs using the same naming convention used elsewhere: serialNumber[idx][] and mark with data-serial
        var added = 0;
        serialEls.forEach(function(si){
            try{
                var v = (si.value || si.getAttribute('value') || '').trim();
                if(!v) return;
                var h = document.createElement('input'); h.type = 'hidden'; h.name = 'serialNumber['+ (idx || '0') +'][]'; h.value = v; h.setAttribute('data-serial','1');
                targetRow.appendChild(h);
                added++;
            }catch(e){ /* ignore individual failures */ }
        });

        // notify user if any serials were synced
        try{ if(added > 0){ showToast('Serials saved', 'Saved '+added+' serial(s) for row '+ (idx || ''), 'success'); } }catch(e){}
    }catch(e){ console.warn('syncSerialsFromModal error', e); }
};

// Bind Save button inside serial modal to sync serials immediately when clicked
document.addEventListener('click', function(e){
    try{
        var t = e.target;
        if(!t) return;
        if(t.matches && t.matches('#saveSerials')){
            // determine modal ancestor id
            var modal = t.closest && t.closest('.modal');
            var id = modal && modal.id ? modal.id : t.getAttribute('data-modal-id');
            if(id && typeof window.syncSerialsFromModal === 'function'){
                try{ window.syncSerialsFromModal(id); }catch(er){ console.warn('saveSerials sync failed', er); }
            }
        }
    }catch(e){}
}, true);

</script>