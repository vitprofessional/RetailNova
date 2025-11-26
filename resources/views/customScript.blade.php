<script>
/* RN custom script - guarded initialization to avoid errors when jQuery loads after this file */

console.log('RN customScript loaded');

document.addEventListener('DOMContentLoaded', function(){
    try{
        console.log('RN diagnostics: DOMContentLoaded');
        var qty = document.getElementById('quantity');
        var buy = document.getElementById('buyPrice');
        var sale = document.getElementById('salePriceExVat');
        console.log('RN diagnostics: elements presence', { quantity: !!qty, buyPrice: !!buy, salePriceExVat: !!sale });
        if(qty){ qty.addEventListener('input', function(){ console.log('RN diag: quantity input', this.value); }); }
        if(buy){ buy.addEventListener('input', function(){ console.log('RN diag: buyPrice input', this.value); }); }
        if(sale){ sale.addEventListener('input', function(){ console.log('RN diag: salePrice input', this.value); }); }
        console.log('RN diagnostics: product rows', document.querySelectorAll('tr.product-row').length);
        console.log('RN diagnostics: purchase-row-template present', !!document.getElementById('purchase-row-template'));
        var addBtnDiag = document.getElementById('addProductRow');
        console.log('RN diagnostics: addProductRow present', !!addBtnDiag);
        if(addBtnDiag){ addBtnDiag.addEventListener('click', function(){ console.log('RN diag: addProductRow clicked'); }); }
        console.log('RN diagnostics: functions', { calculateSaleDetails: typeof window.calculateSaleDetails, recalcPurchaseRow: typeof window.recalcPurchaseRow, updateTotalsClientSide: typeof window.updateTotalsClientSide });
    }catch(e){ console.warn('RN diagnostics error', e); }
});

// Capture-phase click logger to catch clicks before other handlers
document.addEventListener('click', function(e){
    try{
        var t = e.target || e.srcElement;
        if(!t) return;
        var info = { id: t.id||null, cls: t.className||null, tag: t.tagName||null, text: (t.innerText||t.value||'').trim().slice(0,80) };
        if(info.id === 'addProductRow' || (info.cls && info.cls.indexOf('addProductRow')!==-1) || info.text.indexOf('Add To List')!==-1){
            console.log('RN capture click detected', info);
        }
    }catch(e){}
}, true);

// Minimal helpers that do not require jQuery at load time
window.updateSaleErrorSummary = function(){
    try{
        var container = document.getElementById('saleErrorSummary');
        if(!container) return;
        var lines = document.querySelectorAll('.invalid-feedback.sale-error');
    }catch(e){ console.error('updateSaleErrorSummary error', e); }
};

@include('scripts.purchase-scripts')

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

        function applyData(data){
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
            }catch(e){ console.warn('fillPurchaseProductDetails applyData error', e); }
        }

        // Prefer jQuery if available, otherwise use fetch
        if(window.jQuery && typeof window.jQuery.get === 'function'){
            window.jQuery.get(ajaxUrl, function(data){ applyData(data); }).fail(function(err){ console.warn('product/details fetch failed', err); });
        } else {
            fetch(ajaxUrl, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
                .then(function(res){ if(!res.ok) return {}; return res.json(); })
                .then(function(data){ applyData(data); })
                .catch(function(err){ console.warn('product/details fetch failed', err); });
        }
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

        @include('scripts.product-scripts')

        // register RNHandlers for global functions (deduplicated, idempotent)
        (function(){
            var names = ['remove','removeServiceRow','removeSerialField','purchaseData','calculateSaleDetails','totalPriceCalculate','priceCalculation','profitCalculation','discountType','discountAmountChange','discountPercentChange','dueCalculate','serviceSelect','actProductList','actSaleProduct','productSelect','saleProductSelect'];
            if(!window.RNHandlers || typeof window.RNHandlers.register !== 'function') return;
            names.forEach(function(n){
                try{
                    if(typeof window[n] !== 'function') return;
                    // avoid re-registering the same function reference
                    if(window.RNHandlers[n] && window.RNHandlers[n] === window[n]) return;
                    window.RNHandlers.register(n, window[n]);
                }catch(e){ /* ignore individual registration errors */ }
            });
        })();
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
            // Show modal: prefer Bootstrap's JS API, fall back to jQuery, otherwise toggle classes
            try{
                if(window.bootstrap && window.bootstrap.Modal){
                    try{ var inst = new window.bootstrap.Modal(modal); inst.show(); }
                    catch(e){ /* ignore */ }
                } else if(window.jQuery){
                    try{ $('#serialModal').modal('show'); }catch(e){}
                } else {
                    modal.classList.add('show'); modal.style.display = 'block';
                }
            }catch(e){ console.warn('show serial modal failed', e); }
        }
    }catch(e){ console.warn('row click handler', e); }
});

// Vanilla delegated handlers for dynamic elements (mirror jQuery delegated bindings)
(function(){
    // product-row inputs: quantity / sale-price -> recalc per-row
    document.addEventListener('input', function(e){
        try{
            var t = e.target;
            if(!t) return;
            // single-row purchase inputs
            if(t.matches && (t.matches('#quantity') || t.matches('#buyPrice') || t.matches('#salePriceExVat'))){
                try{ recalcPurchaseRow(); }catch(_){}
            }

            // multi-row product inputs
            var row = t.closest && t.closest('.product-row');
            if(row && (t.matches('.quantity') || t.matches('.sale-price') || t.classList && t.classList.contains('sale-price'))){
                try{
                    var rowId = row.id || row.getAttribute('data-idx') || '';
                    var pfEl = row.querySelector('select[name="purchaseData[]"]'); var pf = pfEl ? pfEl.id : '';
                    var bp = (row.querySelector('input[id^="buyPrice"]')||{}).id || '';
                    var sp = (row.querySelector('input.sale-price')||row.querySelector('input[id^="salePrice"]')||{}).id || '';
                    var qd = (row.querySelector('input.quantity')||{}).id || '';
                    var ts = (row.querySelector('[id^="totalSale"]')||{}).id || '';
                    var tp = (row.querySelector('[id^="totalPurchase"]')||{}).id || '';
                    var pm = (row.querySelector('[id^="profitMargin"]')||{}).id || '';
                    var pt = (row.querySelector('[id^="profitTotal"]')||{}).id || '';
                    calculateSaleDetails(0, rowId, pf, bp, sp, ts, tp, qd, pm, pt);
                }catch(err){ console.warn('delegated product-row input handler', err); }
            }
        }catch(err){ /* ignore */ }
    }, true);

    // delegated change handlers
    document.addEventListener('change', function(e){
        try{
            var t = e.target;
            if(!t) return;
            // purchaseData select changed
            if(t.matches && t.matches('select[name="purchaseData[]"]')){
                try{
                    var id = t.id || '';
                    var m = id.match(/purchaseData(\d+)/);
                    if(m){
                        var pid = m[1];
                        var proField = 'productField'+pid;
                        var pf = 'purchaseData'+pid;
                        var bp = 'buyPrice'+pid;
                        var sp = 'salePrice'+pid;
                        var ts = 'totalSale'+pid;
                        var tp = 'totalPurchase'+pid;
                        var qd = 'qty'+pid;
                        var pm = 'profitMargin'+pid;
                        var pt = 'profitTotal'+pid;
                        try{ purchaseData(pid, proField, pf, bp, sp, ts, tp, qd, pm, pt); }catch(e){}
                    }
                }catch(e){ console.warn('purchaseData delegated change failed', e); }
            }

            // product select (js-product-select) - works for dynamic elements
            if(t.matches && t.matches('.js-product-select')){
                try{ fillPurchaseProductDetails(t.value); }catch(e){}
            }

            // supplier/customer change
            if(t.matches && t.matches('#supplierName')){ try{ if(typeof window.actProductList === 'function') window.actProductList(); }catch(e){} }
            if(t.matches && t.matches('#customerName')){ try{ if(typeof window.actSaleProduct === 'function') window.actSaleProduct(); }catch(e){} }
        }catch(err){ /* ignore */ }
    }, true);

    // small modal save buttons: saveBrand, add-category, add-productUnit
    document.addEventListener('click', function(e){
        try{
            var btn = e.target.closest && e.target.closest('#saveBrand, #add-category, #add-productUnit');
            if(!btn) return;
            e.preventDefault();
            if(btn.id === 'saveBrand'){
                var nameEl = document.getElementById('NewBrand') || document.getElementById('brandName') || document.getElementById('brandNameModal');
                var name = nameEl ? (nameEl.value || '') : '';
                if(!name) return;
                var url = '{{ route('createBrand') }}';
                // prefer jQuery
                if(window.jQuery && typeof window.jQuery.get === 'function'){
                    window.jQuery.get(url, { name: name }, function(result){ try{ closeModel('createBrand','brandForm'); var forms = document.querySelectorAll('#brandForm'); forms.forEach(function(f){ try{ f.reset(); }catch(_){} }); var targets = document.querySelectorAll('#brand, #brandName, select[name="brand"]'); targets.forEach(function(t){ try{ t.innerHTML = result.data; }catch(_){} }); }catch(e){}});
                } else {
                    fetch(url + '?name=' + encodeURIComponent(name), { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
                        .then(function(r){ return r.json ? r.json() : {}; })
                        .then(function(result){ try{ closeModel('createBrand','brandForm'); var forms = document.querySelectorAll('#brandForm'); forms.forEach(function(f){ try{ f.reset(); }catch(_){} }); var targets = document.querySelectorAll('#brand, #brandName, select[name="brand"]'); targets.forEach(function(t){ try{ t.innerHTML = result.data; }catch(_){} }); }catch(e){} });
                }
            }
            if(btn.id === 'add-category'){
                var nameEl = document.getElementById('NewCategory') || document.getElementById('categoryName');
                var name = nameEl ? (nameEl.value || '') : '';
                if(!name) return;
                var url = '{{ route('createCategory') }}';
                if(window.jQuery && typeof window.jQuery.get === 'function'){
                    window.jQuery.get(url, { name: name }, function(result){ try{ closeModel('categoryModal','categoryForm'); var forms = document.querySelectorAll('#categoryForm'); forms.forEach(function(f){ try{ f.reset(); }catch(_){} }); var targets = document.querySelectorAll('#categoryList, #categoryName, select[name="category"]'); targets.forEach(function(t){ try{ t.innerHTML = result.data; }catch(_){} }); }catch(e){} });
                } else {
                    fetch(url + '?name=' + encodeURIComponent(name), { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
                        .then(function(r){ return r.json ? r.json() : {}; })
                        .then(function(result){ try{ closeModel('categoryModal','categoryForm'); var forms = document.querySelectorAll('#categoryForm'); forms.forEach(function(f){ try{ f.reset(); }catch(_){} }); var targets = document.querySelectorAll('#categoryList, #categoryName, select[name="category"]'); targets.forEach(function(t){ try{ t.innerHTML = result.data; }catch(_){} }); }catch(e){} });
                }
            }
            if(btn.id === 'add-productUnit'){
                var nameEl = document.getElementById('productUnitName');
                var name = nameEl ? (nameEl.value || '') : '';
                if(!name) return;
                var url = '{{ route('createProductUnit') }}';
                if(window.jQuery && typeof window.jQuery.get === 'function'){
                    window.jQuery.get(url, { name: name }, function(result){ try{ closeModel('productUnitModal','productUnitForm'); var forms = document.querySelectorAll('#productUnitForm'); forms.forEach(function(f){ try{ f.reset(); }catch(_){} }); var targets = document.querySelectorAll('#unit, #unitName, select[name="unitName"]'); targets.forEach(function(t){ try{ t.innerHTML = result.data; }catch(_){} }); }catch(e){} });
                } else {
                    fetch(url + '?name=' + encodeURIComponent(name), { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
                        .then(function(r){ return r.json ? r.json() : {}; })
                        .then(function(result){ try{ closeModel('productUnitModal','productUnitForm'); var forms = document.querySelectorAll('#productUnitForm'); forms.forEach(function(f){ try{ f.reset(); }catch(_){} }); var targets = document.querySelectorAll('#unit, #unitName, select[name="unitName"]'); targets.forEach(function(t){ try{ t.innerHTML = result.data; }catch(_){} }); }catch(e){} });
                }
            }
        }catch(e){ console.warn('vanilla delegated small-modal handlers failed', e); }
    }, true);
})();

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