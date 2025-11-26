<script>
/* RN custom script - guarded initialization to avoid errors when jQuery loads after this file */

// Minimal helpers that do not require jQuery at load time
window.updateSaleErrorSummary = function(){
    try{
        var container = document.getElementById('saleErrorSummary');
        if(!container) return;
        var lines = document.querySelectorAll('.invalid-feedback.sale-error');
        var errors = [];
        lines.forEach(function(el, i){
            var txt = el.textContent.trim();
            var row = el.closest('tr');
            var product = '';
            if(row){
                var tds = row.getElementsByTagName('td');
                if(tds && tds[1]) product = tds[1].textContent.trim();
            }
            if(!product) product = 'Line '+(i+1);
            errors.push(product+': '+txt);
        });
        container.innerHTML = errors.length? errors.map(function(s){ return '<div>'+s+'</div>'; }).join('\n') : '';
    }catch(e){ console.warn('updateSaleErrorSummary', e); }
};

// Ensure `actProductList` exists so inline onchange handlers won't throw when called
window.actProductList = function(){
    try{
        var sup = document.getElementById('supplierName');
        var prod = document.getElementById('productName');
        if(!sup || !prod) return;
        var val = sup.value || '';
        if(!val){
            // show explicit placeholder when supplier not selected
            try{
                if(window._productNameDefaultOptions === undefined){ window._productNameDefaultOptions = prod.innerHTML; }
                prod.innerHTML = '<option value="">Select supplier first</option>';
            }catch(e){}
            prod.selectedIndex = 0;
            prod.setAttribute('disabled','disabled');
            var row = document.querySelector('#productDetails tr');
            if(row){
                var inputs = row.querySelectorAll('input');
                inputs.forEach(function(i){ if(i.type==='number' || i.type==='text') i.value = ''; });
                var selects = row.querySelectorAll('select'); selects.forEach(function(s){ s.selectedIndex = 0; });
            }
        } else {
            // restore the original (server-rendered) product options when supplier selected
            try{ if(window._productNameDefaultOptions !== undefined) prod.innerHTML = window._productNameDefaultOptions; }catch(e){}
            // Enable the product dropdown and keep all server-rendered options (no supplier filtering)
            prod.removeAttribute('disabled');
            try{ prod.selectedIndex = 0; }catch(e){}
            if(typeof window.productSelect === 'function'){
                try{ window.productSelect(); }catch(e){}
            }
        }
    }catch(e){ console.warn('actProductList', e); }
};

// Calculate total and profit for the single-row purchase table
function recalcPurchaseRow(){
    try{
        var qtyEl = document.getElementById('quantity');
        var buyEl = document.getElementById('buyPrice');
        var saleEl = document.getElementById('salePriceExVat');
        var totalEl = document.getElementById('totalAmount');
        var profitEl = document.getElementById('profitMargin');
        if(!qtyEl || !buyEl || !totalEl) return;
        var qty = parseFloat(qtyEl.value || 0) || 0;
        var buy = parseFloat(buyEl.value || 0) || 0;
        var sale = saleEl ? parseFloat(saleEl.value || 0) || 0 : 0;
        var unit = (sale > 0) ? sale : buy;
        var total = (unit * qty) || 0;
        totalEl.value = total ? (Math.round((total + Number.EPSILON) * 100) / 100) : '';
        // profit: only meaningful when sale price is provided
        if(profitEl){
            if(buy > 0 && sale > 0){
                var profitValue = (sale * qty) - (buy * qty);
                var profitPercent = ((profitValue / (buy * qty)) * 100) || 0;
                profitEl.value = Number(profitPercent.toFixed(2));
            } else {
                // clear profit if insufficient data
                profitEl.value = '';
            }
        }
    }catch(e){ console.warn('recalcPurchaseRow error', e); }
}

// Update the Other Details area (discount, grand total, due) based on current totals
function updateOtherDetails(){
    try{
        var totalEl = document.getElementById('totalAmount');
        var grandEl = document.getElementById('grandTotal');
        var discountStatusEl = document.getElementById('discountStatus');
        var discountAmountEl = document.getElementById('discountAmount');
        var discountPercentEl = document.getElementById('discountPercent');
        var paidEl = document.getElementById('paidAmount');
        var dueEl = document.getElementById('dueAmount');

        var base = parseFloat(totalEl ? (totalEl.value || 0) : 0) || 0;
        var disType = discountStatusEl ? discountStatusEl.value : '';
        var disAmount = discountAmountEl ? (parseFloat(discountAmountEl.value || 0) || 0) : 0;
        var disPercent = discountPercentEl ? (parseFloat(discountPercentEl.value || 0) || 0) : 0;

        var discount = 0;
        if(disType == '1'){
            // amount
            discount = disAmount;
        } else if(disType == '2'){
            discount = (base * disPercent / 100) || 0;
        }

        var grand = Math.max(0, base - discount);
        if(grandEl) grandEl.value = Number(grand.toFixed(2));

        var paid = paidEl ? (parseFloat(paidEl.value || 0) || 0) : 0;
        var due = Math.max(0, grand - paid);
        if(dueEl) dueEl.value = Number(due.toFixed(2));
    }catch(e){ console.warn('updateOtherDetails error', e); }
}

function discountType(){
    try{ updateOtherDetails(); }catch(e){}
}

function discountAmountChange(){
    try{
        // when discount amount changes, clear percent and recalc
        var percent = document.getElementById('discountPercent'); if(percent) percent.value = '';
        updateOtherDetails();
    }catch(e){ console.warn(e); }
}

function discountPercentChange(){
    try{
        // when percent changes, clear amount
        var amt = document.getElementById('discountAmount'); if(amt) amt.value = '';
        updateOtherDetails();
    }catch(e){ console.warn(e); }
}

function dueCalculate(){
    try{ updateOtherDetails(); }catch(e){ }
}

// Helper registry for fallback calls
window.RNHandlers = window.RNHandlers || {};
window.RNHandlers.register = function(name, fn){ if(typeof name === 'string' && typeof fn === 'function') window.RNHandlers[name] = fn; };

// Lightweight jQuery-ready shim: queue `$(fn)` calls until real jQuery loads.
if(typeof window.$ === 'undefined' && typeof window.jQuery === 'undefined'){
    (function(){
        var queued = [];
        function $stub(arg){
            if(typeof arg === 'function'){
                queued.push(arg);
                return;
            }
            // minimal jQuery-like object for chaining (no DOM manipulation)
            return {
                length: 0,
                on: function(){ return this; },
                off: function(){ return this; },
                val: function(v){ if(arguments.length) return this; return undefined; },
                html: function(){ return this; },
                text: function(){ return this; },
                append: function(){ return this; },
                find: function(){ return this; },
                closest: function(){ return this; },
                prop: function(){ return this; },
                data: function(){ return undefined; },
                each: function(){ return this; }
            };
        }
        $stub._runQueued = function(realJq){ try{ queued.forEach(function(fn){ try{ realJq(fn); }catch(e){ console.error('queued ready handler error', e); } }); }catch(e){/*ignore*/} };
        window.$ = window.jQuery = $stub;
        // store queued for later inspection
        window._queuedJqReadyHandlers = queued;
    })();
}

// Provide a safe global `remove` helper (DOM-only) so inline onclick="remove('#id')" works
window.remove = function(sel){
    try{
        if(!sel) return;
        if(typeof sel === 'string'){
            var id = sel.replace(/^#/, '');
            var el = document.getElementById(id) || document.querySelector(sel);
            if(el) el.remove();
        } else if(sel && sel.nodeType){ sel.remove(); }
    }catch(e){ console.warn('remove helper error', e); }
};

// Core functions (define now; they use jQuery when invoked)
function num(v){ if (v === null || v === undefined) return 0; const s = String(v).trim(); if(!s) return 0; return Number(String(s).replace(/,/g,'')) || 0; }

function resolveValue(arg){
    try{
        if (!arg && arg !== 0) return 0;
        if (typeof arg === 'object' && arg !== null){ if (arg.value !== undefined) return arg.value; if (arg.val && typeof arg.val === 'function') return arg.val(); }
        var el = document.getElementById(arg);
        if (el) return el.value;
        var q = document.querySelector(arg);
        if (q) return q.value;
    }catch(e){ console.warn('resolveValue error', e); }
    return 0;
}

function resolveElement(arg){
    try{
        if (!arg && arg !== 0) return null;
        var el = document.getElementById(arg);
        if (el) return el;
        var q = document.querySelector(arg);
        if (q) return q;
    }catch(e){ console.warn('resolveElement error', e); }
    return null;
}

// calculateSaleDetails: defined as a global function (uses jQuery when executed)
function calculateSaleDetails(pid, proField, pf, bp, sp, ts, tp, qd, pm, pt) {
    try{
        var $ = window.jQuery;
        var buyPrice   = num(resolveValue(bp));
        var salePrice  = num(resolveValue(sp));
        var qty        = num(resolveValue(qd));
        var totalPurchase = buyPrice * qty;
        var totalSale     = salePrice * qty;
        var profitValue   = totalSale - totalPurchase;
        var profitPercent = totalPurchase > 0 ? Number(((profitValue / totalPurchase) * 100).toFixed(2)) : 0;
        if ($) {
            if(ts) $('#' + ts).html(totalSale);
            if(tp) $('#' + tp).html(totalPurchase);
            if(pm) $('#' + pm).html(profitPercent);
            if(pt) $('#' + pt).html(profitValue);
        } else {
            try{ if(ts) document.getElementById(ts).innerHTML = totalSale; }catch(_){}
            try{ if(tp) document.getElementById(tp).innerHTML = totalPurchase; }catch(_){}
            try{ if(pm) document.getElementById(pm).innerHTML = profitPercent; }catch(_){}
            try{ if(pt) document.getElementById(pt).innerHTML = profitValue; }catch(_){}
        }
        // debounce server call if jQuery present
        if ($) {
            window._saleDebounceTimers = window._saleDebounceTimers || {};
            var timerKey = String(qd || pid) + '_calc';
            if (window._saleDebounceTimers[timerKey]) clearTimeout(window._saleDebounceTimers[timerKey]);
            window._saleDebounceTimers[timerKey] = setTimeout(function(){
                var items = [];
                $('.product-row').each(function () { var price = parseFloat($(this).find('.sale-price').val()) || 0; var quantity = parseFloat($(this).find('.quantity').val()) || 0; items.push({ price: price, quantity: quantity }); });
                $.get('{{ route("calculate.grand.total") }}', { items: items, purchaseId: pid }, function (response) {
                    const serverGrandTotal = num(response.grandTotal || response.total || 0);
                    const currentStock = parseInt(response.currentStock) || 0;
                    const discountAmount = num($("#discountAmount").val());
                    const paidAmount     = num($("#paidAmount").val());
                    const gTotal    = Math.max(0, serverGrandTotal - discountAmount);
                    const dueAmount = Math.max(0, gTotal - paidAmount);
                    $('#grandTotal').val(gTotal);
                    $('#totalSaleAmount').val(serverGrandTotal);
                    $('#dueAmount').val(dueAmount);
                    $('#curDue').val(dueAmount);
                    var $qtyEl = $('#' + qd);
                    if (qty > currentStock) {
                        if ($qtyEl.length) {
                            if ($qtyEl.next('.invalid-feedback.sale-error').length === 0) {
                                $qtyEl.after('<div class="invalid-feedback sale-error">Only '+currentStock+' units available for the selected purchase row</div>');
                            } else {
                                $qtyEl.next('.invalid-feedback.sale-error').text('Only '+currentStock+' units available for the selected purchase row');
                            }
                            $qtyEl.addClass('is-invalid');
                            $qtyEl.closest('tr').addClass('table-danger');
                        }
                        $('form[action="{{ route('saveSale') }}"] button[type=submit]').prop('disabled', true);
                    } else {
                        if ($qtyEl.length) {
                            $qtyEl.removeClass('is-invalid');
                            $qtyEl.next('.invalid-feedback.sale-error').remove();
                            $qtyEl.closest('tr').removeClass('table-danger');
                        }
                        if ($('.invalid-feedback.sale-error').length === 0) {
                            $('form[action="{{ route('saveSale') }}"] button[type=submit]').prop('disabled', false);
                        }
                    }
                    delete window._saleDebounceTimers[timerKey];
                }).fail(function(){ delete window._saleDebounceTimers[timerKey]; });
            }, 300);
        }
    }catch(e){ console.warn('calculateSaleDetails error', e); }
}

// purchaseData simplified implementation (uses jQuery when available)
function purchaseData(pid,proField,pf,bp,sp,ts,tp,qd,pm,pt){
    try{
        var $ = window.jQuery;
        var pData = 0;
        if($){ pData = parseInt($('#'+pf).val()) || 0; }
        if(!pData) return;
        if($){
            $.get('{{ url('/') }}/purchase/details/'+pData, function(result){
                var buyPrice = parseFloat(result.buyPrice) || 0;
                var salePrice = parseFloat(result.salePrice) || 0;
                var $qtyEl = $('#'+qd);
                var qty = $qtyEl.length? parseInt($qtyEl.val()) || 0 : 0;
                var currentStock = parseInt(result.currentStock) || 0;
                if(qty > currentStock){ if($qtyEl.length){ if ($qtyEl.next('.invalid-feedback.sale-error').length === 0) $qtyEl.after('<div class="invalid-feedback sale-error">Only '+currentStock+' units available for the selected purchase row</div>'); else $qtyEl.next('.invalid-feedback.sale-error').text('Only '+currentStock+' units available for the selected purchase row'); $qtyEl.addClass('is-invalid'); $qtyEl.closest('tr').addClass('table-danger'); } showToast('Error','Only '+currentStock+' units available for the selected purchase row','error'); $('form[action="{{ route('saveSale') }}"] button[type=submit]').prop('disabled', true); }
                else { if($qtyEl.length){ $qtyEl.removeClass('is-invalid'); $qtyEl.next('.invalid-feedback.sale-error').remove(); $qtyEl.closest('tr').removeClass('table-danger'); } if($('.invalid-feedback.sale-error').length===0) $('form[action="{{ route('saveSale') }}"] button[type=submit]').prop('disabled', false); }
                var totalPurchase = parseInt(buyPrice * qty) || 0;
                var totalSale = parseInt(salePrice * qty) || 0;
                var profitValue = totalSale - totalPurchase;
                var profitPercent = totalPurchase>0? Number(((profitValue/totalPurchase)*100).toFixed(2)):0;
                try{ if(ts) $('#'+ts).html(totalSale); if(tp) $('#'+tp).html(totalPurchase); if(sp) $('#'+sp).val(salePrice); if(bp) $('#'+bp).val(buyPrice); if(pm) $('#'+pm).html(profitPercent); if(pt) $('#'+pt).html(profitValue); }catch(_){ }
            });
        }
    }catch(e){ console.warn('purchaseData error', e); }
}

// Fill product details into the single-row purchase table (used on Add Purchase page)
function fillPurchaseProductDetails(productId){
    try{
        if(!productId) {
            // clear row
            var row = document.querySelector('#productDetails tr');
            if(row){
                var inputs = row.querySelectorAll('input');
                inputs.forEach(function(i){ if(i.type==='number' || i.type==='text') i.value = ''; });
                var selects = row.querySelectorAll('select'); selects.forEach(function(s){ s.selectedIndex = 0; });
            }
            return;
        }

        var ajaxUrl = '{{ url("product/details") }}/' + productId;
        // try using jQuery if available
        if(window.jQuery){
            $.get(ajaxUrl, function(data){
                try{
                    var row = document.querySelector('#productDetails tr');
                    if(!row) return;
                    var set = function(id, val){ var el = document.getElementById(id); if(el) el.value = (val===undefined||val===null)?'':val; };
                    set('selectProductName', data.productName || data.name || '');
                    set('currentStock', data.currentStock || 0);
                    set('buyPrice', data.buyPrice || data.buyingPrice || '');
                    set('salePriceExVat', data.salePrice || data.salePriceExVat || '');
                    try{ var vat = (data.vatStatus!==undefined? data.vatStatus : (data.vat || '')); var vatEl = document.getElementById('vatStatus'); if(vatEl) { for(var i=0;i<vatEl.options.length;i++){ if(vatEl.options[i].value == vat){ vatEl.selectedIndex = i; break; } } } }catch(_){ }
                    // enable qty
                    var qty = document.getElementById('quantity'); if(qty){ qty.removeAttribute('readonly'); qty.value = qty.value || 1; }
                    // compute total if buyPrice/salePrice present
                    var buy = parseFloat(document.getElementById('buyPrice')?.value || 0);
                    var sale = parseFloat(document.getElementById('salePriceExVat')?.value || 0);
                    var qv = parseFloat(document.getElementById('quantity')?.value || 0);
                    var total = ((sale>0?sale:buy) * (qv || 0)) || 0;
                    var totalEl = document.getElementById('totalAmount'); if(totalEl) totalEl.value = total || '';
                }catch(e){ console.warn('fillPurchaseProductDetails (jQuery) handler', e); }
            }).fail(function(err){ console.warn('product/details fetch failed', err); });
            return;
        }

        // fallback to fetch API
        fetch(ajaxUrl, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
            .then(function(res){ if(!res.ok) return {}; return res.json(); })
            .then(function(data){ try{
                var row = document.querySelector('#productDetails tr'); if(!row) return;
                var set = function(id, val){ var el = document.getElementById(id); if(el) el.value = (val===undefined||val===null)?'':val; };
                set('selectProductName', data.productName || data.name || '');
                set('currentStock', data.currentStock || 0);
                set('buyPrice', data.buyPrice || data.buyingPrice || '');
                set('salePriceExVat', data.salePrice || data.salePriceExVat || '');
                try{ var vat = (data.vatStatus!==undefined? data.vatStatus : (data.vat || '')); var vatEl = document.getElementById('vatStatus'); if(vatEl) { for(var i=0;i<vatEl.options.length;i++){ if(vatEl.options[i].value == vat){ vatEl.selectedIndex = i; break; } } } }catch(_){ }
                var qty = document.getElementById('quantity'); if(qty){ qty.removeAttribute('readonly'); qty.value = qty.value || 1; }
                var buy = parseFloat(document.getElementById('buyPrice')?.value || 0);
                var sale = parseFloat(document.getElementById('salePriceExVat')?.value || 0);
                var qv = parseFloat(document.getElementById('quantity')?.value || 0);
                var total = ((sale>0?sale:buy) * (qv || 0)) || 0;
                var totalEl = document.getElementById('totalAmount'); if(totalEl) totalEl.value = total || '';
            }catch(e){ console.warn('fillPurchaseProductDetails (fetch) handler', e); } }).catch(function(err){ console.warn('product/details fetch failed', err); });
    }catch(e){ console.warn('fillPurchaseProductDetails error', e); }
}

function showToast(title, text, icon){
    if(window.Swal && typeof Swal.fire === 'function'){
        Swal.fire({ title: title || '', text: text || '', icon: icon || 'success', timer: 2000, showConfirmButton: false });
    } else if(window.swal){ try{ window.swal(title || '', text || '', icon || 'success'); } catch(e){ alert((title?title+' - ':'')+(text||'')); } }
    else { alert((title?title+' - ':'')+(text||'')); }
}

// Global handler caller usable both with and without jQuery
window._callHandlerSpec = function(spec, el, evt){
    if(!spec) return true;
    try{
        var raw = String(spec).trim();
        var m = raw.match(/^([a-zA-Z0-9_.$]+)\s*(?:\((.*)\))?$/);
        if(!m) return true;
        var name = m[1];
        var argsRaw = (m[2]||'').trim();
        var args = [];
        if(argsRaw){
            try{ args = JSON.parse('[' + argsRaw.replace(/'/g,'"') + ']'); }
            catch(e){ args = argsRaw.split(',').map(function(s){ return s.trim().replace(/^['\"]|['\"]$/g,''); }); }
        }
        var obj = window, fn = null; var parts = name.split('.');
        for(var i=0;i<parts.length;i++){
            if(obj === undefined || obj === null) break;
            if(i === parts.length-1){ fn = obj[parts[i]]; }
            else { obj = obj[parts[i]]; }
        }
        if(typeof fn === 'function'){
            return fn.apply(el, args.concat([evt]));
        }
        if(window.RNHandlers && window.RNHandlers[name] && typeof window.RNHandlers[name] === 'function'){
            return window.RNHandlers[name].apply(el, args.concat([evt]));
        }
    }catch(err){ console.error('global handler call error', err); }
    return true;
};

// Process any `data-onload` attributes when DOM is ready (works without jQuery)
(function(){
    function run(){
        try{
            var els = document.querySelectorAll('[data-onload]');
            els.forEach(function(el){
                var spec = el.getAttribute('data-onload');
                if(spec) window._callHandlerSpec(spec, el, null);
            });
            // Attach `data-onerror` handlers for elements (e.g. images)
            var errEls = document.querySelectorAll('[data-onerror]');
            errEls.forEach(function(el){
                try{
                    var spec = el.getAttribute('data-onerror');
                    if(!spec) return;
                    // assign to the element's onerror so it fires when resource fails
                    el.onerror = function(evt){
                        try{ window._callHandlerSpec(spec, el, evt); }catch(e){ console.error('data-onerror call failed', e); }
                    };
                }catch(e){ console.error('attach data-onerror error', e); }
            });
            // Attach native change handlers for any product selects so Add Purchase works without jQuery
            try{
                var psel = document.querySelectorAll('.js-product-select');
                psel.forEach(function(s){ s.addEventListener('change', function(){ try{ window.fillPurchaseProductDetails(this.value); }catch(e){ console.warn('fillPurchaseProductDetails call failed', e); } }); });
            }catch(e){ /* ignore */ }
            // Native delegated handlers for `data-on*` attributes (works without jQuery)
            try{
                ['click','change','keyup','input'].forEach(function(evtName){
                    document.addEventListener(evtName, function(e){
                        try{
                            var target = e.target;
                            while(target && target.nodeType !== 1) target = target.parentNode;
                            if(!target) return;
                            var el = target.closest('[data-on' + evtName + ']');
                            if(!el) return;
                            var spec = el.getAttribute('data-on' + evtName);
                            if(!spec) return;
                            var res = window._callHandlerSpec(spec, el, e);
                            if(res === false){ e.preventDefault(); e.stopImmediatePropagation(); return false; }
                        }catch(err){ console.error('native data-on'+evtName+' handler error', err); }
                    }, false);
                });
            }catch(e){ /* ignore */ }
        }catch(e){ console.error('data-onload processing error', e); }
    }
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();

// Ensure initial supplier/product state and wire native input listeners for the purchase row
document.addEventListener('DOMContentLoaded', function(){
    try{ 
        // capture original product options so we can restore them after showing a placeholder
        var prodEl = document.getElementById('productName');
        if(prodEl && !window._productNameDefaultOptions){ window._productNameDefaultOptions = prodEl.innerHTML; }
        if(typeof window.actProductList === 'function') window.actProductList(); 
    }catch(e){}
    try{
        var qty = document.getElementById('quantity'); if(qty) qty.addEventListener('input', recalcPurchaseRow);
        var buy = document.getElementById('buyPrice'); if(buy) buy.addEventListener('input', recalcPurchaseRow);
        var sale = document.getElementById('salePriceExVat'); if(sale) sale.addEventListener('input', recalcPurchaseRow);
        // other details listeners
        var discountStatus = document.getElementById('discountStatus'); if(discountStatus) discountStatus.addEventListener('change', discountType);
        var discountAmount = document.getElementById('discountAmount'); if(discountAmount) discountAmount.addEventListener('input', discountAmountChange);
        var discountPercent = document.getElementById('discountPercent'); if(discountPercent) discountPercent.addEventListener('input', discountPercentChange);
        var paidAmount = document.getElementById('paidAmount'); if(paidAmount) paidAmount.addEventListener('input', dueCalculate);
    }catch(e){ /* ignore */ }
    try{
        // Add button for adding selected product to purchase rows
        var addBtn = document.getElementById('addProductRow');
        if(addBtn){
            addBtn.addEventListener('click', function(){
                try{
                    var sel = document.getElementById('productName');
                    if(!sel) return;
                    var id = sel.value;
                    if(!id){ showToast('Error','Please select a product to add','error'); return; }
                    window.addPurchaseRow(id);
                }catch(e){ console.warn('addProductRow click', e); }
            });
        }
    }catch(e){}
});

// Initialize jQuery-dependent bindings when jQuery becomes available
(function waitForjQuery(){
    function init(){
        var $ = window.jQuery;
        if(!$) return;

        // If earlier a stub queued ready callbacks, run them now via real jQuery
        try{
            if(window._queuedJqReadyHandlers && Array.isArray(window._queuedJqReadyHandlers) && window._queuedJqReadyHandlers.length){
                window._queuedJqReadyHandlers.forEach(function(fn){ try{ $(fn); }catch(e){ console.error('flushing queued ready handler failed', e); } });
                window._queuedJqReadyHandlers = [];
            }
        }catch(e){ /* ignore */ }
        // jQuery delegated change for .js-product-select (if not already bound)
        try{ $(document).on('change', '.js-product-select', function(){ try{ var id = $(this).val(); window.fillPurchaseProductDetails(id); }catch(e){ console.warn('js-product-select change handler failed', e); } }); }catch(e){ /* ignore */ }

        // jQuery input handlers for immediate recalculation
        try{ $(document).on('input', '#quantity, #buyPrice, #salePriceExVat', function(){ try{ recalcPurchaseRow(); }catch(e){} }); }catch(e){ /* ignore */ }
        // jQuery handlers for other details
        try{ $(document).on('change', '#discountStatus', function(){ try{ discountType(); }catch(e){} }); }catch(e){}
        try{ $(document).on('input', '#discountAmount', function(){ try{ discountAmountChange(); }catch(e){} }); }catch(e){}
        try{ $(document).on('input', '#discountPercent', function(){ try{ discountPercentChange(); }catch(e){} }); }catch(e){}
        try{ $(document).on('input', '#paidAmount', function(){ try{ dueCalculate(); }catch(e){} }); }catch(e){}

        // Re-bind delegated behavior and handlers
        $(document).on('click','#saveBrand', function(){ var name = $('#NewBrand').val(); $.get('{{ route('createBrand') }}', { name: name }, function(result){ $('#createBrand').modal('hide'); document.getElementById('brandForm').reset(); $('#brandName').html(result.data); }); });

        $(document).on('click','#add-category', function(){ var name = $('#NewCategory').val(); $.get('{{ route('createCategory') }}', { name: name }, function(result){ $('#categoryModal').modal('hide'); document.getElementById('categoryForm').reset(); $('#categoryName').html(result.data); }); });

        $(document).on('click','#add-productUnit', function(){ var name = $('#productUnitName').val(); $.get('{{ route('createProductUnit') }}', { name: name }, function(result){ $('#productUnitModal').modal('hide'); document.getElementById('productUnitForm').reset(); $('#unit').html(result.data); }); });

        // product row input handlers
        $(document).on('input', '.product-row .quantity, .product-row .sale-price', function(){ var $row = $(this).closest('.product-row'); if(!$row.length) return; var rowId = $row.attr('id'); var purchaseSelect = $row.find('select[name="purchaseData[]"]'); var pf = purchaseSelect.attr('id'); var bp = $row.find('input[id^="buyPrice"]').attr('id'); var sp = $row.find('input.sale-price').attr('id'); var qtyEl = $row.find('input.quantity').attr('id'); var ts = $row.find('[id^="totalSale"]').attr('id'); var tp = $row.find('[id^="totalPurchase"]').attr('id'); var pm = $row.find('[id^="profitMargin"]').attr('id'); var pt = $row.find('[id^="profitTotal"]').attr('id'); calculateSaleDetails(0, rowId, pf, bp, sp, ts, tp, qtyEl, pm, pt); });

        $(document).on('change', 'select[name="purchaseData[]"]', function(){ try{ var id = $(this).attr('id')||''; var m = id.match(/purchaseData(\d+)/); if(m){ var pid=m[1]; var proField='productField'+pid; var pf='purchaseData'+pid; var bp='buyPrice'+pid; var sp='salePrice'+pid; var ts='totalSale'+pid; var tp='totalPurchase'+pid; var qd='qty'+pid; var pm='profitMargin'+pid; var pt='profitTotal'+pid; purchaseData(pid, proField, pf, bp, sp, ts, tp, qd, pm, pt); } }catch(e){console.error(e);} });

        // other delegated handlers
        $(document).on('change', '#supplierName', function(){ if(typeof window.actProductList === 'function') window.actProductList(); });
        $(document).on('change', '#customerName', function(){ if(typeof window.actSaleProduct === 'function') window.actSaleProduct(); });
        // product select on Add Purchase / Edit Purchase pages
        $(document).on('change', '.js-product-select', function(){ try{ var id = $(this).val(); window.fillPurchaseProductDetails(id); }catch(e){ console.warn('js-product-select change handler failed', e); } });

        // Generic delegated handler for forms that declare `data-onsubmit`.
        // Supports `data-onsubmit="confirm"` with `data-confirm` message,
        // or a handler name registered via `window.RNHandlers`.
        $(document).on('submit', 'form[data-onsubmit]', function(e){
            try{
                var $f = $(this);
                var handler = $f.data('onsubmit');
                if(!handler) return true;
                if(handler === 'confirm'){
                    var msg = $f.data('confirm') || 'Are you sure?';
                    if(!confirm(msg)){
                        e.preventDefault();
                        return false;
                    }
                    return true;
                }
                var fn = window.RNHandlers && window.RNHandlers[handler] ? window.RNHandlers[handler] : (window[handler] || null);
                if(typeof fn === 'function'){
                    var res = fn.call(this, e);
                    if(res === false){ e.preventDefault(); return false; }
                }
            }catch(err){ console.error('form data-onsubmit handler error', err); }
        });

        // Use the global `window._callHandlerSpec` (defined earlier) for handler parsing and invocation

        // Delegate click/change/keyup/input from `data-on*` attributes
        ['click','change','keyup','input'].forEach(function(evtName){
            $(document).on(evtName, '[data-on' + evtName + ']', function(e){
                try{
                    var spec = $(this).attr('data-on' + evtName);
                    if(!spec) return;
                        var res = window._callHandlerSpec(spec, this, e);
                    if(res === false){ e.preventDefault(); e.stopImmediatePropagation(); return false; }
                }catch(err){ console.error('data-on'+evtName+' handler error', err); }
            });
        });

        // register RNHandlers for global functions
        ['remove','removeServiceRow','removeSerialField','purchaseData','calculateSaleDetails','totalPriceCalculate','priceCalculation','profitCalculation','discountType','discountAmountChange','discountPercentChange','dueCalculate','serviceSelect','actProductList','actSaleProduct','productSelect','saleProductSelect'].forEach(function(n){ if(typeof window[n] === 'function') window.RNHandlers.register(n, window[n]); });
    }

    if (typeof jQuery === 'undefined'){
        var h = setInterval(function(){ if(typeof jQuery !== 'undefined'){ clearInterval(h); init(); } }, 50);
    } else { init(); }
})();

// Lightweight multi-row purchase helpers
window._purchaseRowCounter = window._purchaseRowCounter || 0;
window.addPurchaseRow = function(productId){
    try{
        // prevent duplicates: if productId already exists in productName[] hidden inputs, skip
        try{
            var existingInputs = document.querySelectorAll('input[name="productName[]"]');
            for(var ei=0; ei<existingInputs.length; ei++){
                if(String(existingInputs[ei].value) === String(productId)){
                    showToast('Warning','Product already added to the list','warning');
                    // clear top selector
                    try{ var selc = document.getElementById('productName'); if(selc) selc.selectedIndex = 0; }catch(_){ }
                    return;
                }
            }
        }catch(_){ }
        window._purchaseRowCounter = (window._purchaseRowCounter || 0) + 1;
        var idx = window._purchaseRowCounter;
        var template = document.getElementById('purchase-row-template');
        if(!template) return;
        var html = template.innerHTML.replace(/__IDX__/g, idx).replace(/__PRODUCT_ID__/g, productId).replace(/__PRODUCT_NAME__/g, 'Loading...');
        var tbody = document.getElementById('productDetails');
        var tmp = document.createElement('tbody'); tmp.innerHTML = html.trim();
        var tr = tmp.querySelector('tr');
        if(!tr) return;
        // append row
        tbody.appendChild(tr);

        // fetch product details and populate row fields
        var url = '{{ url("product/details") }}/' + productId;
        var fillRow = function(data){
            try{
                var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]');
                if(!row) return;
                var setByName = function(name, val){ var el = row.querySelector('[name="'+name+'"]'); if(el){ el.value = (val===undefined||val===null)?'':val; } };
                // product name text input
                var nameInput = row.querySelector('input[name="selectProductName[]"]'); if(nameInput) nameInput.value = data.productName || data.name || '';
                // current stock
                var cur = row.querySelector('#currentStock'+idx+'') || row.querySelector('#currentStock__IDX__'.replace('__IDX__', idx));
                if(cur) cur.value = (data.currentStock || 0);
                var buy = row.querySelector('#buyPrice'+idx) || row.querySelector('#buyPrice__IDX__'.replace('__IDX__', idx)); if(buy) buy.value = (data.buyPrice || data.buyingPrice || '');
                var sale = row.querySelector('#salePriceExVat'+idx) || row.querySelector('#salePriceExVat__IDX__'.replace('__IDX__', idx)); if(sale) sale.value = (data.salePrice || data.salePriceExVat || '');
                // vat status select
                try{
                    var vat = (data.vatStatus!==undefined? data.vatStatus : (data.vat || ''));
                    var vatEl = row.querySelector('#vatStatus'+idx) || row.querySelector('#vatStatus__IDX__'.replace('__IDX__', idx));
                    if(vatEl){ for(var i=0;i<vatEl.options.length;i++){ if(vatEl.options[i].value == vat){ vatEl.selectedIndex = i; break; } } }
                }catch(_){ }
                // recalc total for the row
                var qtyEl = row.querySelector('.quantity'); if(qtyEl && (!qtyEl.value || qtyEl.value==0)) qtyEl.value = 1;
                var qty = parseFloat(row.querySelector('.quantity').value || 0) || 0;
                var buyVal = parseFloat(buy ? buy.value : 0) || 0;
                var saleVal = parseFloat(sale ? sale.value : 0) || 0;
                var unit = (saleVal>0? saleVal : buyVal);
                var total = (unit * qty) || 0;
                var totalEl = row.querySelector('#totalAmount'+idx) || row.querySelector('#totalAmount__IDX__'.replace('__IDX__', idx)); if(totalEl) totalEl.value = total || '';
                var profitEl = row.querySelector('#profitMargin'+idx) || row.querySelector('#profitMargin__IDX__'.replace('__IDX__', idx)); if(profitEl){ if(buyVal>0 && saleVal>0){ var profitPercent = (((saleVal*qty) - (buyVal*qty)) / (buyVal*qty) * 100) || 0; profitEl.value = Number(profitPercent.toFixed(2)); } }
            }catch(e){ console.warn('fillRow error', e); }
        };

        // Use jQuery if available
        if(window.jQuery){
            $.get(url, function(data){ fillRow(data); }).fail(function(){ showToast('Error','Failed to load product details','error'); });
        } else {
            fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, credentials:'same-origin'}).then(function(res){ if(!res.ok) return {}; return res.json(); }).then(function(data){ fillRow(data); }).catch(function(){ showToast('Error','Failed to load product details','error'); });
        }
        // enable discounts area when at least one row exists
        try{ var ds = document.getElementById('discountStatus'); if(ds) ds.removeAttribute('disabled'); var dam = document.getElementById('discountAmount'); if(dam) dam.removeAttribute('readonly'); var dper = document.getElementById('discountPercent'); if(dper) dper.removeAttribute('readonly'); var paid = document.getElementById('paidAmount'); if(paid) paid.removeAttribute('readonly'); var note = document.getElementById('specialNote'); if(note) note.removeAttribute('readonly'); }catch(e){}
        // clear the top product select so user can add another easily
        try{ var sel = document.getElementById('productName'); if(sel) sel.selectedIndex = 0; }catch(e){}
    }catch(e){ console.warn('addPurchaseRow error', e); }
};

// remove row handler (delegated binding exists; provide helper)
document.addEventListener('click', function(e){
    try{
        var target = e.target;
        if(!target) return;
        if(target && target.id === 'add-serial'){
            var box = document.getElementById('serialNumberBox'); if(!box) return;
            var r = document.createElement('div'); r.className='row'; r.innerHTML = '<div class="col-10 mb-3"><input class="form-control" name="serialNumber[]" value="" /></div>'; box.appendChild(r); return;
        }
        if(target && target.classList && target.classList.contains('remove-row')){
            var tr = target.closest('tr'); if(tr) tr.remove();
        }
        // open serial modal
        if(target && target.classList && target.classList.contains('open-serials')){
            var idx = target.getAttribute('data-idx');
            if(!idx) return;
            var modal = document.getElementById('serialModal');
            if(!modal) return;
            // set a data attribute to know which row's serials we're editing
            modal.setAttribute('data-current-idx', idx);
            // clear modal inputs and populate with any existing hidden serial inputs from the row
            var box = document.getElementById('serialNumberBox'); if(box) box.innerHTML = '';
            var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]');
            if(row){
                // find existing hidden serial inputs with name starting with serialNumber[idx]
                var existing = row.querySelectorAll('input[type="hidden"][data-serial]');
                if(existing && existing.length>0){
                    existing.forEach(function(h){ var r = document.createElement('div'); r.className='row'; r.innerHTML = '<div class="col-10 mb-3"><input class="form-control" name="serialNumber[]" value="'+(h.value||'')+'" /></div>'; box.appendChild(r); });
                } else {
                    var r = document.createElement('div'); r.className='row'; r.innerHTML = '<div class="col-10 mb-3"><input class="form-control" name="serialNumber[]" value="" /></div>'; box.appendChild(r);
                }
            }
            $('#serialModal').modal('show');
        }
    }catch(e){ console.warn('row click handler', e); }
});

// When serial modal is hidden, copy serial inputs back into the row as hidden inputs
if(window.jQuery){
    $(document).on('hidden.bs.modal', '#serialModal', function(){
        try{
            var modal = document.getElementById('serialModal'); if(!modal) return;
            var idx = modal.getAttribute('data-current-idx'); if(!idx) return;
            var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]'); if(!row) return;
            // remove old hidden serial inputs
            var olds = row.querySelectorAll('input[type="hidden"][data-serial]'); olds.forEach(function(o){ o.remove(); });
            var inputs = document.querySelectorAll('#serialNumberBox input[name="serialNumber[]"]');
            inputs.forEach(function(inp){ var v = inp.value.trim(); if(v==='') return; var h = document.createElement('input'); h.type='hidden'; h.name = 'serialNumber['+idx+'][]'; h.value = v; h.setAttribute('data-serial','1'); row.appendChild(h); });
        }catch(e){ console.warn('serial modal hide handler', e); }
    });
}
</script>