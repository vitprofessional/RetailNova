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
            // clear product details row
            prod.selectedIndex = 0;
            prod.setAttribute('disabled','disabled');
            var row = document.querySelector('#productDetails tr');
            if(row){
                var inputs = row.querySelectorAll('input');
                inputs.forEach(function(i){ if(i.type==='number' || i.type==='text') i.value = ''; });
                var selects = row.querySelectorAll('select'); selects.forEach(function(s){ s.selectedIndex = 0; });
            }
        } else {
            prod.removeAttribute('disabled');
            // If a global productSelect function exists, call it to refresh details
            if(typeof window.productSelect === 'function'){
                try{ window.productSelect(); }catch(e){}
            }
        }
    }catch(e){ console.warn('actProductList', e); }
};

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
        }catch(e){ console.error('data-onload processing error', e); }
    }
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();

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
</script>