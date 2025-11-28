// Purchase-related helpers extracted from `customScript.blade.php`
// This file is included into the main `customScript.blade.php` <script> block.

// calculateSaleDetails: defined as a global function (uses jQuery when executed)
function calculateSaleDetails(pid, proField, pf, bp, sp, ts, tp, qd, pm, pt) {
    try{
        if(window.__RNDEBUG) console.debug('calculateSaleDetails called', {pid:pid, pf:pf, bp:bp, sp:sp, qd:qd, ts:ts, tp:tp});
        var $ = window.jQuery;
        var buyPrice   = num(resolveValue(bp));
        var salePrice  = num(resolveValue(sp));
        var qty        = num(resolveValue(qd));
        var totalPurchase = buyPrice * qty;
        var totalSale     = salePrice * qty;
        var profitValue   = totalSale - totalPurchase;
        var profitPercent = totalPurchase > 0 ? Number(((profitValue / totalPurchase) * 100).toFixed(2)) : 0;

        // VAT handling: use per-row vat percent only. Do NOT fall back to global inputs.
        // Default to 0% when not present so totals in the other-details table are not affected
        // by a global VAT toggle.
        var vatPercent = 0;
        try{
            var rowEl = null;
            if(typeof pf === 'string' && pf){ rowEl = document.getElementById(pf); }
            if(!rowEl) rowEl = document.querySelector('tr.product-row[data-idx="' + (rowEl ? rowEl.getAttribute('data-idx') : '') + '"]') || document.querySelector('.product-row');
            var perEl = rowEl ? (rowEl.querySelector('.vat-percent') || rowEl.querySelector('[id^="vatStatus"]')) : null;
            if(perEl){ var v = parseFloat(perEl.value); if(!isNaN(v)) vatPercent = v; }
        }catch(e){}

        // include-VAT flag: only consider the per-row `.include-vat` checkbox. Do not use the
        // global `#includeVat` control so the other-details table remains independent.
        var includeVat = false;
        try{
            var incEl = null;
            if(typeof pf === 'string' && pf){ var rowEl2 = document.getElementById(pf); if(rowEl2) incEl = rowEl2.querySelector('.include-vat'); }
            if(!incEl) incEl = document.querySelector('tr.product-row[data-idx] .include-vat') || document.querySelector('.product-row .include-vat');
            if(incEl){ if(incEl.type === 'checkbox' || incEl.type === 'radio') includeVat = !!incEl.checked; else includeVat = (String(incEl.value) === '1' || String(incEl.value).toLowerCase() === 'yes' || String(incEl.value).toLowerCase() === 'true'); }
        }catch(e){}

        var totalSaleShown = includeVat ? (totalSale * (1 + (vatPercent/100))) : totalSale;

        if ($) {
            if(ts) $('#' + ts).html(totalSaleShown);
            if(tp) $('#' + tp).html(totalPurchase);
            if(pm) $('#' + pm).html(profitPercent);
            if(pt) $('#' + pt).html(profitValue);
        } else {
            try{ if(ts) document.getElementById(ts).innerHTML = totalSaleShown; }catch(_){ }
            try{ if(tp) document.getElementById(tp).innerHTML = totalPurchase; }catch(_){ }
            try{ if(pm) document.getElementById(pm).innerHTML = profitPercent; }catch(_){ }
            try{ if(pt) document.getElementById(pt).innerHTML = profitValue; }catch(_){ }
        }
        // Client-side VAT-aware behavior (Option A): update per-row and aggregate totals locally
        try{
            if(typeof updateTotalsClientSide === 'function') updateTotalsClientSide();
            if(typeof window.recalcFinancials === 'function') window.recalcFinancials();
        }catch(e){ console.warn('calculateSaleDetails local wrapper error', e); }
    }catch(e){ console.warn('calculateSaleDetails error', e); }
}

// Aggregate totals locally from the product rows and update grand/total/due fields
function updateTotalsClientSide(){
    try{
        if(window.__RNDEBUG) console.debug('updateTotalsClientSide running');
        var rows = document.querySelectorAll('tr.product-row');
        var totalPurchase = 0;
        rows.forEach(function(r){
            try{
                // prefer explicit total element if present, otherwise compute from buy-price * qty
                var tsEl = r.querySelector('[id^="totalAmount"]');
                var rowTotal = 0;
                if(tsEl){
                    var txt = tsEl.value || tsEl.innerHTML || '0';
                    rowTotal = Number(String(txt).replace(/,/g,'')) || 0;
                } else {
                    var buy = num((r.querySelector('[id^="buyPrice"], .buy-price')||{}).value || 0);
                    var qty = num((r.querySelector('.quantity')||{}).value || 0);
                    rowTotal = buy * qty;
                }
                totalPurchase += rowTotal;
            }catch(e){}
        });
        var totalSaleEl = document.getElementById('totalSaleAmount'); if(totalSaleEl) totalSaleEl.value = totalPurchase;
        var discountAmount = num((document.getElementById('discountAmount')||{value:0}).value);
        var paidAmount = num((document.getElementById('paidAmount')||{value:0}).value);
        var gTotal = Math.max(0, totalPurchase - discountAmount);
        var due = Math.max(0, gTotal - paidAmount);
        var grandEl = document.getElementById('grandTotal'); if(grandEl) grandEl.value = gTotal;
        var dueEl = document.getElementById('dueAmount'); if(dueEl) dueEl.value = due;
        var curEl = document.getElementById('curDue'); if(curEl) curEl.value = due;
    }catch(e){ console.warn('updateTotalsClientSide error', e); }
}

// purchaseData simplified implementation (uses jQuery when available)
function purchaseData(pid,proField,pf,bp,sp,ts,tp,qd,pm,pt){
    try{
        // Resolve selected purchase id from pf param (element id)
        var pData = parseInt(resolveValue(pf)) || 0;
        if(!pData) return;

        var url = '{{ url('/') }}/purchase/details/' + pData;

        var handleResult = function(result){
            try{
                var buyPrice = parseFloat(result.buyPrice) || 0;
                var salePrice = parseFloat(result.salePrice) || 0;
                var qtyEl = document.getElementById(qd);
                var qty = qtyEl ? (parseInt(qtyEl.value) || 0) : 0;
                var currentStock = parseInt(result.currentStock) || 0;

                var setHtml = function(id, v){ var el = document.getElementById(id); if(!el) return; if('value' in el) el.value = v; else el.innerHTML = v; };

                if(qty > currentStock){
                    if(qtyEl){
                        // inline error element
                        var next = qtyEl.nextElementSibling;
                        if(!next || !next.classList || !next.classList.contains('invalid-feedback')){
                            var div = document.createElement('div'); div.className = 'invalid-feedback sale-error'; div.textContent = 'Only '+currentStock+' units available for the selected purchase row'; qtyEl.parentNode.insertBefore(div, qtyEl.nextSibling);
                        } else { next.textContent = 'Only '+currentStock+' units available for the selected purchase row'; }
                        qtyEl.classList.add('is-invalid');
                        var tr = qtyEl.closest('tr'); if(tr) tr.classList.add('table-danger');
                    }
                    try{ var form = document.querySelector('form[action="{{ route('saveSale') }}"]'); if(form){ var btn = form.querySelector('button[type=submit]'); if(btn) btn.disabled = true; } }catch(e){}
                } else {
                    if(qtyEl){ qtyEl.classList.remove('is-invalid'); if(qtyEl.nextElementSibling && qtyEl.nextElementSibling.classList && qtyEl.nextElementSibling.classList.contains('invalid-feedback')) qtyEl.nextElementSibling.remove(); var tr = qtyEl.closest('tr'); if(tr) tr.classList.remove('table-danger'); }
                    var any = document.querySelectorAll('.invalid-feedback.sale-error'); if(!any || any.length === 0){ try{ var form = document.querySelector('form[action="{{ route('saveSale') }}"]'); if(form){ var btn = form.querySelector('button[type=submit]'); if(btn) btn.disabled = false; } }catch(e){} }
                }

                // totals and fields
                var totalPurchase = parseInt(buyPrice * qty) || 0;
                var totalSale = parseInt(salePrice * qty) || 0;
                var profitValue = totalSale - totalPurchase;
                var profitPercent = totalPurchase>0? Number(((profitValue/totalPurchase)*100).toFixed(2)):0;

                try{ 
                    if(ts) { var el = document.getElementById(ts); if(el) el.innerHTML = totalSale; }
                    if(tp){ var el2 = document.getElementById(tp); if(el2) el2.innerHTML = totalPurchase; }
                    // Intentionally do NOT auto-fill sale/buy price inputs. Leave these fields for the user to enter.
                    if(pm){ var el5 = document.getElementById(pm); if(el5) el5.innerHTML = profitPercent; }
                    if(pt){ var el6 = document.getElementById(pt); if(el6) el6.innerHTML = profitValue; }
                }catch(_){ }
            }catch(e){ console.warn('purchaseData handleResult error', e); }
        };

        // Prefer jQuery.get if available for compatibility, otherwise use fetch
        if(window.jQuery && typeof window.jQuery.get === 'function'){
            window.jQuery.get(url, function(result){ handleResult(result); });
        } else {
            fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With':'XMLHttpRequest','Accept':'application/json' } })
                .then(function(res){ if(!res.ok) return {}; return res.json(); })
                .then(function(json){ handleResult(json); })
                .catch(function(err){ console.warn('purchaseData fetch failed', err); });
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

        function applyData(data){
            try{
                var row = document.querySelector('#productDetails tr');
                if(!row) return;
                var set = function(id, val){ var el = document.getElementById(id); if(el) el.value = (val===undefined||val===null)?'':val; };
                set('selectProductName', data.productName || data.name || '');
                set('currentStock', data.currentStock || 0);
                // Do NOT auto-fill buyPrice or salePriceExVat here; user will enter prices manually.
                // Compute and set Sale Price (Inc. VAT) for single-row form when available.
                // Use only the returned `vatStatus` from the product details; do not use the
                // global `#vatPercent` or global include checkbox so other details remain independent.
                try{
                    var sp = parseFloat(data.salePrice || data.salePriceExVat || 0) || 0;
                    var vat = (data.vatStatus !== undefined ? data.vatStatus : (data.vat || ''));
                    var vatPercent = 0;
                    if(vat !== '' && !isNaN(parseFloat(vat))) vatPercent = parseFloat(vat);
                    var include = false;
                    if(vat === '1' || String(vat).toLowerCase() === 'yes' || String(vat).toLowerCase() === 'true') include = true;
                    var spInc = include ? Number((sp * (1 + (vatPercent/100))).toFixed(2)) : sp;
                    var spIncEl = document.getElementById('salePriceInVat'); if(spIncEl) spIncEl.value = spInc;
                }catch(e){}
                try{ var vat = (data.vatStatus!==undefined? data.vatStatus : (data.vat || '')); var vatEl = document.getElementById('vatStatus'); if(vatEl) { for(var i=0;i<vatEl.options.length;i++){ if(vatEl.options[i].value == vat){ vatEl.selectedIndex = i; break; } } } }catch(_){ }
                // enable qty
                var qty = document.getElementById('quantity'); if(qty){ qty.removeAttribute('readonly'); }
                // compute total if buyPrice/salePrice present
                var buy = parseFloat(document.getElementById('buyPrice')?.value || 0);
                var sale = parseFloat(document.getElementById('salePriceExVat')?.value || 0);
                var qv = parseFloat(document.getElementById('quantity')?.value || 0);
                // total (purchase cost) should be based only on buy price
                var total = (buy * (qv || 0)) || 0;
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

    // Utility helpers used across purchase/sale flows
    function num(v){ if (v === null || v === undefined) return 0; try{ const s = String(v).trim(); if(!s) return 0; return Number(String(s).replace(/,/g,'')) || 0; }catch(e){ return 0; } }

    function resolveValue(idOrEl){ try{ if(!idOrEl) return ''; if(typeof idOrEl === 'string'){ var el = document.getElementById(idOrEl); if(el){ return ('value' in el)? el.value : el.innerHTML; } return idOrEl; } if(idOrEl && 'value' in idOrEl) return idOrEl.value; return ''; }catch(e){ return ''; } }

    // Single-row recalculation is provided by the central `customScript.blade.php` implementation.
    // A legacy lightweight recalc function was intentionally removed to avoid clobbering
    // the more robust `recalcPurchaseRow(ctx)` used for multi-row handling.

    // Price/profit helpers used by inline handlers
    function priceCalculation(){ try{ var sale = num((document.getElementById('salePriceExVat')||{value:0}).value); var buy = num((document.getElementById('buyPrice')||{value:0}).value); var profitEl = document.getElementById('profitMargin'); if(profitEl && buy>0){ var p = (((sale - buy)/buy)*100); profitEl.value = Number(p.toFixed(2)); } recalcPurchaseRow(); }catch(e){ console.warn('priceCalculation', e); } }

    function profitCalculation(){ try{ var pm = num((document.getElementById('profitMargin')||{value:0}).value); var buy = num((document.getElementById('buyPrice')||{value:0}).value); if(buy>0){ var sale = buy * (1 + (pm/100)); var spEl = document.getElementById('salePriceExVat'); if(spEl) spEl.value = Number(sale.toFixed(2)); } recalcPurchaseRow(); }catch(e){ console.warn('profitCalculation', e); } }

    function totalPriceCalculate(){
        try{
            // Keep compatibility name but delegate to central recalculation.
            recalcPurchaseRow();
            if(typeof updateTotalsClientSide === 'function') updateTotalsClientSide();
            if(typeof window.recalcFinancials === 'function') window.recalcFinancials();
        }catch(e){ console.warn('totalPriceCalculate', e); }
    }

    // Discount and due calculations (Add/Edit Purchase pages)
    function discountType(){
        try{
            // Support both legacy string values ('percent'/'amount') and numeric values (1=Amount,2=Percent)
            var status = (document.getElementById('discountStatus')||{}).value || '';
            var dam = document.getElementById('discountAmount');
            var dper = document.getElementById('discountPercent');
            var isPercent = (status === 'percent' || String(status) === '2');
            var isAmount = (status === 'amount' || String(status) === '1');
            if(isPercent){ if(dam) dam.setAttribute('readonly','readonly'); if(dper) dper.removeAttribute('readonly'); }
            else if(isAmount){ if(dper) dper.setAttribute('readonly','readonly'); if(dam) dam.removeAttribute('readonly'); }
            else { if(dper) dper.setAttribute('readonly','readonly'); if(dam) dam.setAttribute('readonly','readonly'); }
        }catch(e){ console.warn('discountType', e); }
    }

    function discountAmountChange(){
        try{
            // Mirror logic in customScript: compute percent from current base total (sum of row totals)
            var discountAmountEl = document.getElementById('discountAmount');
            var discountPercentEl = document.getElementById('discountPercent');
            var base = 0;
            try{ var totalInputs = Array.prototype.slice.call(document.querySelectorAll('input[id^="totalAmount"], input.total-amount')); totalInputs.forEach(function(inp){ base += num(inp.value || 0); }); }catch(_){ base = 0; }
            var dam = discountAmountEl ? num(discountAmountEl.value || 0) : 0;
            if(discountPercentEl && base > 0){ discountPercentEl.value = Number(((dam / base) * 100).toFixed(2)); }
            if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); else dueCalculate();
        }catch(e){ console.warn('discountAmountChange', e); }
    }

    function discountPercentChange(){
        try{
            var discountAmountEl = document.getElementById('discountAmount');
            var discountPercentEl = document.getElementById('discountPercent');
            var base = 0;
            try{ var totalInputs = Array.prototype.slice.call(document.querySelectorAll('input[id^="totalAmount"], input.total-amount')); totalInputs.forEach(function(inp){ base += num(inp.value || 0); }); }catch(_){ base = 0; }
            var per = discountPercentEl ? num(discountPercentEl.value || 0) : 0;
            if(discountAmountEl && base > 0){ discountAmountEl.value = Number(((per/100) * base).toFixed(2)); }
            if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); else dueCalculate();
        }catch(e){ console.warn('discountPercentChange', e); }
    }

    function dueCalculate(){
        try{
            // Legacy wrapper – delegate to the centralized function which computes base from Buy Price × Qty
            if(typeof window.recalcFinancials === 'function') return window.recalcFinancials();
        }catch(e){ console.warn('dueCalculate wrapper error', e); }
    }

    // expose compatibility names for RNHandlers registration
    window.totalPriceCalculate = window.totalPriceCalculate || totalPriceCalculate;
    window.priceCalculation = window.priceCalculation || priceCalculation;
    window.profitCalculation = window.profitCalculation || profitCalculation;
    window.discountType = window.discountType || discountType;
    window.discountAmountChange = window.discountAmountChange || discountAmountChange;
    window.discountPercentChange = window.discountPercentChange || discountPercentChange;
    window.dueCalculate = window.dueCalculate || dueCalculate;

    // ensure key helpers are reachable as globals and bind single-row listeners defensively
    try{
        if(typeof recalcPurchaseRow === 'function'){ window.recalcPurchaseRow = window.recalcPurchaseRow || recalcPurchaseRow; }
        window.calculateSaleDetails = window.calculateSaleDetails || calculateSaleDetails;
        window.purchaseData = window.purchaseData || purchaseData;
        window.fillPurchaseProductDetails = window.fillPurchaseProductDetails || fillPurchaseProductDetails;
        window.addPurchaseRow = window.addPurchaseRow || addPurchaseRow;
        window.num = window.num || num;
        window.resolveValue = window.resolveValue || resolveValue;
    }catch(e){ /* ignore exposure errors */ }

    document.addEventListener('DOMContentLoaded', function(){
        try{
            var qty = document.getElementById('quantity'); if(qty) qty.removeEventListener('input', recalcPurchaseRow); if(qty) qty.addEventListener('input', recalcPurchaseRow);
            var buy = document.getElementById('buyPrice'); if(buy) buy.removeEventListener('input', recalcPurchaseRow); if(buy) buy.addEventListener('input', recalcPurchaseRow);
            var sale = document.getElementById('salePriceExVat'); if(sale) sale.removeEventListener('input', recalcPurchaseRow); if(sale) sale.addEventListener('input', recalcPurchaseRow);
        }catch(e){ /* ignore */ }
    });


// Lightweight multi-row purchase helpers
window._purchaseRowCounter = window._purchaseRowCounter || 0;
window.addPurchaseRow = function(productId){
    try{ if(window.__RNDEBUG) console.debug('addPurchaseRow invoked', productId); }catch(e){}
    try{
        // prevent duplicates: if productId already exists in productName[] hidden inputs, skip
        try{
            var existingInputs = document.querySelectorAll('input[name="productName[]"]');
            for(var ei=0; ei<existingInputs.length; ei++){
                if(String(existingInputs[ei].value) === String(productId)){
                    showToast('Warning','Product already added to the list','warning');
                    try{ var selc = document.getElementById('productName'); if(selc) selc.selectedIndex = 0; }catch(_){}
                    return;
                }
            }
        }catch(_){ }

        window._purchaseRowCounter = (window._purchaseRowCounter || 0) + 1;
        var idx = window._purchaseRowCounter;
        var template = document.getElementById('purchase-row-template');
        if(!template) return;

        // Build resolved HTML once and insert
        var html = template.innerHTML.replace(/__IDX__/g, idx).replace(/__PRODUCT_ID__/g, productId).replace(/__PRODUCT_NAME__/g, 'Loading...');
        var tbody = document.getElementById('productDetails');
        var tmp = document.createElement('tbody'); tmp.innerHTML = html.trim();
        var tr = tmp.querySelector('tr');
        if(!tr) return;

        // mark row and ensure identification
        tr.classList.add('product-row');
        tr.setAttribute('data-idx', idx);

        // append using fragment for better performance
        var frag = document.createDocumentFragment();
        frag.appendChild(tr);
        tbody.appendChild(frag);

        // small initializer that wires per-row inputs to existing helpers
        (function initRow(row, rowIdx){
            try{
            try{ if(window.__RNDEBUG) console.debug('initRow binding', rowIdx); }catch(e){}
                var qtyEl = row.querySelector('.quantity');
                var saleEl = row.querySelector('.sale-price');
                var purchaseSelect = row.querySelector('select[name="purchaseData[]"]');

                var getIds = function(){
                    var pf = purchaseSelect ? (purchaseSelect.id || '') : '';
                    var bp = (row.querySelector('input[id^="buyPrice"]')||{}).id || '';
                    var sp = (row.querySelector('input.sale-price')||{}).id || '';
                    var qd = (row.querySelector('input.quantity')||{}).id || '';
                    var ts = (row.querySelector('[id^="totalSale"]')||{}).id || '';
                    var tp = (row.querySelector('[id^="totalPurchase"]')||{}).id || '';
                    var pm = (row.querySelector('[id^="profitMargin"]')||{}).id || '';
                    var pt = (row.querySelector('[id^="profitTotal"]')||{}).id || '';
                    return { pf: pf, bp: bp, sp: sp, qd: qd, ts: ts, tp: tp, pm: pm, pt: pt };
                };

                var ids = getIds();

                if(qtyEl){ qtyEl.addEventListener('input', function(){ try{ if(window.__RNDEBUG) console.debug('row qty input', rowIdx, this.value); calculateSaleDetails(0, row.id||('productField'+rowIdx), ids.pf, ids.bp, ids.sp, ids.ts, ids.tp, ids.qd, ids.pm, ids.pt); }catch(e){} }); }
                // Sale price input should not trigger server-side grand total recalculation on Purchase pages.
                // Only update the per-row calculation (totals/profit) so Grand Total/Due remains based on Buy Price × Qty.
                if(saleEl){ saleEl.addEventListener('input', function(){ try{ recalcPurchaseRow(this); }catch(e){} }); }
                // wire VAT controls per-row so toggling VAT immediately updates Inc-VAT and totals
                var vatChk = row.querySelector('.include-vat');
                var vatPct = row.querySelector('.vat-percent');
                if(vatChk){ vatChk.addEventListener('change', function(){ try{ recalcPurchaseRow(this); }catch(e){} }); }
                if(vatPct){ vatPct.addEventListener('input', function(){ try{ recalcPurchaseRow(this); }catch(e){} }); }
                if(purchaseSelect){ purchaseSelect.addEventListener('change', function(){
                    try{
                        // recompute ids as some attributes may be set server-side
                        ids = getIds();
                        var m = (purchaseSelect.id||'').match(/purchaseData(\d+)/);
                        var pid = m ? m[1] : rowIdx;
                        var proField = 'productField'+pid;
                        purchaseData(pid, proField, ids.pf, ids.bp, ids.sp, ids.ts, ids.tp, ids.qd, ids.pm, ids.pt);
                    }catch(e){ console.warn('row purchase change', e); }
                }); }
            }catch(e){ console.warn('initRow failed', e); }
        })(tr, idx);

        // fetch product details and populate row fields (same fillRow behavior)
        var url = '{{ url("product/details") }}/' + productId;
        var fillRow = function(data){
            try{
                var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]');
                if(!row) return;
                var nameInput = row.querySelector('input[name="selectProductName[]"]'); if(nameInput) nameInput.value = data.productName || data.name || '';
                var cur = row.querySelector('#currentStock'+idx) || row.querySelector('#currentStock__IDX__'.replace('__IDX__', idx)); if(cur) cur.value = (data.currentStock || 0);
                var buy = row.querySelector('#buyPrice'+idx) || row.querySelector('#buyPrice__IDX__'.replace('__IDX__', idx));
                var sale = row.querySelector('#salePriceExVat'+idx) || row.querySelector('#salePriceExVat__IDX__'.replace('__IDX__', idx));
                // Intentionally do not set buy.value or sale.value from AJAX result. Prices should be entered by user to avoid accidental overrides.
                // compute sale price including VAT for this row using per-row VAT only
                try{
                    var saleVal = parseFloat(sale ? sale.value : 0) || 0;
                    var vat = (data.vatStatus !== undefined ? data.vatStatus : (data.vat || ''));
                    var vatPercent = 0;
                    if(vat !== '' && !isNaN(parseFloat(vat))) vatPercent = parseFloat(vat);
                    var includeEl = row.querySelector('.include-vat');
                    var include = false; if(includeEl){ if(includeEl.type === 'checkbox') include = !!includeEl.checked; else include = (String(includeEl.value) === '1' || String(includeEl.value).toLowerCase() === 'yes' || String(includeEl.value).toLowerCase() === 'true'); }
                    var saleInc = include ? Number((saleVal * (1 + (vatPercent/100))).toFixed(2)) : saleVal;
                    var spIncEl = row.querySelector('#salePriceInVat'+idx) || row.querySelector('#salePriceInVat__IDX__'.replace('__IDX__', idx)) || row.querySelector('.sale-price-inc');
                    if(spIncEl){ if('value' in spIncEl) spIncEl.value = saleInc; else spIncEl.innerHTML = saleInc; }
                }catch(e){}
                try{ var vat = (data.vatStatus!==undefined? data.vatStatus : (data.vat || '')); var vatEl = row.querySelector('#vatStatus'+idx) || row.querySelector('#vatStatus__IDX__'.replace('__IDX__', idx)); if(vatEl){ for(var i=0;i<vatEl.options.length;i++){ if(vatEl.options[i].value == vat){ vatEl.selectedIndex = i; break; } } } }catch(_){ }
                var qtyEl = row.querySelector('.quantity'); if(qtyEl) { /* leave blank by default; do not auto-fill */ }
                var qty = parseFloat(row.querySelector('.quantity').value || 0) || 0;
                var buyVal = parseFloat(buy ? buy.value : 0) || 0;
                var saleVal = parseFloat(sale ? sale.value : 0) || 0;
                var unit = (saleVal>0? saleVal : buyVal);
                // total (purchase cost) should be based only on buy price
                var total = (buyVal * qty) || 0;
                var totalEl = row.querySelector('#totalAmount'+idx) || row.querySelector('#totalAmount__IDX__'.replace('__IDX__', idx)); if(totalEl) totalEl.value = total || '';
                var profitEl = row.querySelector('#profitMargin'+idx) || row.querySelector('#profitMargin__IDX__'.replace('__IDX__', idx)); if(profitEl){ if(buyVal>0 && saleVal>0){ var profitPercent = (((saleVal*qty) - (buyVal*qty)) / (buyVal*qty) * 100) || 0; profitEl.value = Number(profitPercent.toFixed(2)); } }
            }catch(e){ console.warn('fillRow error', e); }
        };

        if(window.jQuery && typeof window.jQuery.get === 'function'){
            window.jQuery.get(url, function(data){ fillRow(data); }).fail(function(){ showToast('Error','Failed to load product details','error'); });
        } else {
            fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, credentials:'same-origin'})
                .then(function(res){ if(!res.ok) return {}; return res.json(); })
                .then(function(data){ fillRow(data); })
                .catch(function(){ showToast('Error','Failed to load product details','error'); });
        }

        // enable discounts area when at least one row exists
        try{ var ds = document.getElementById('discountStatus'); if(ds) ds.removeAttribute('disabled'); var dam = document.getElementById('discountAmount'); if(dam) dam.removeAttribute('readonly'); var dper = document.getElementById('discountPercent'); if(dper) dper.removeAttribute('readonly'); var paid = document.getElementById('paidAmount'); if(paid) paid.removeAttribute('readonly'); var note = document.getElementById('specialNote'); if(note) note.removeAttribute('readonly'); }catch(e){}
        try{ var sel = document.getElementById('productName'); if(sel) sel.selectedIndex = 0; }catch(e){}
    }catch(e){ console.warn('addPurchaseRow error', e); }
};

// Helper: create a serial input row (input + per-row Remove + per-row Auto-generate)
function createSerialRow(value){
    try{
        // Use a simple flex layout to avoid grid column wrapping issues inside modal bodies
        var wrap = document.createElement('div'); wrap.className = 'serial-input-row d-flex mb-3 align-items-center';
        // input (flex-grow)
        var inp = document.createElement('input'); inp.className = 'form-control flex-grow-1'; inp.name = 'serialNumber[]'; inp.value = value || ''; inp.placeholder = 'Enter serial number';
        inp.style.marginRight = '12px';
        // buttons container
        var btnGroup = document.createElement('div'); btnGroup.className = 'd-flex';
        var btnRemove = document.createElement('button'); btnRemove.type='button'; btnRemove.className='btn btn-sm btn-outline-danger remove-serial'; btnRemove.textContent='Remove';
        var btnAuto = document.createElement('button'); btnAuto.type='button'; btnAuto.className='btn btn-sm btn-outline-info auto-gen-serial-row'; btnAuto.textContent='Auto'; btnAuto.style.marginLeft='6px';
        btnGroup.appendChild(btnRemove); btnGroup.appendChild(btnAuto);
        wrap.appendChild(inp); wrap.appendChild(btnGroup);
        return wrap;
    }catch(e){
        console.warn('createSerialRow error', e);
        var r = document.createElement('div'); r.className='serial-input-row d-flex mb-3';
        r.innerHTML = '<input class="form-control flex-grow-1" name="serialNumber[]" value="'+(value||'')+'" style="margin-right:12px;" /><div class="d-flex"><button type="button" class="btn btn-sm btn-outline-danger remove-serial">Remove</button><button type="button" class="btn btn-sm btn-outline-info auto-gen-serial-row" style="margin-left:6px;">Auto</button></div>';
        return r;
    }
}

// Helper: build a short product code from product name
function buildProductCode(name){
    try{
        if(!name) return '';
        // take up to first 4 initials from words or uppercase letters
        var parts = String(name).trim().split(/[^A-Za-z0-9]+/).filter(Boolean);
        if(parts.length === 0) return '';
        var initials = '';
        for(var i=0;i<parts.length && initials.length<4;i++){
            initials += parts[i].charAt(0).toUpperCase();
        }
        if(initials.length < 2){ initials = (String(name).replace(/[^A-Za-z0-9]/g,'').slice(0,3) || initials).toUpperCase(); }
        return initials;
    }catch(e){ return '';} 
}

// remove row handler (delegated binding exists; provide helper)
document.addEventListener('click', function(e){
    try{
        var target = e.target;
        if(!target) return;
        // per-row remove serial input button
        if(target && target.classList && target.classList.contains('remove-serial')){
            try{ var serialRow = target.closest('.serial-input-row') || target.closest('.row'); if(serialRow) serialRow.parentNode.removeChild(serialRow); }catch(e){}
            return;
        }

        // per-row auto-generate single serial input
        if(target && target.classList && target.classList.contains('auto-gen-serial-row')){
            try{
                var serialRow = target.closest('.serial-input-row') || target.closest('.row');
                if(!serialRow) return;
                var inp = serialRow.querySelector('input[name="serialNumber[]"]');
                var modal = document.getElementById('serialModal');
                var idx = modal ? modal.getAttribute('data-current-idx') : '';
                        // Professional serial format: <PROD>-YYYYMMDD-<ROW>-<NNNN>
                        var row = document.querySelector('tr.product-row[data-idx="'+(idx||'')+'"]');
                        var prodName = '';
                        try{ if(row) prodName = (row.querySelector('input[name="selectProductName[]"]')||{}).value || ''; }catch(e){}
                        var prodCode = buildProductCode(prodName) || ('P'+(idx||'0'));
                        var dt = new Date();
                        var ymd = dt.getFullYear().toString() + String(dt.getMonth()+1).padStart(2,'0') + String(dt.getDate()).padStart(2,'0');
                        var rnd = Math.floor(Math.random()*9999)+1;
                        var seq = String(rnd).padStart(4,'0');
                        var code = (prodCode + '-' + ymd + '-' + (idx||'0') + '-' + seq).toUpperCase();
                        if(inp) inp.value = code;
            }catch(e){}
            return;
        }

        if(target && target.id === 'add-serial'){
            var box = document.getElementById('serialNumberBox'); if(!box) return;
            var r = createSerialRow(''); box.appendChild(r); return;
        }
        if(target && target.id === 'autoGenerateSerials'){
            var modal = document.getElementById('serialModal'); if(!modal) return;
            var idx = modal.getAttribute('data-current-idx'); if(!idx) { alert('Row not found'); return; }
            var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]');
            if(!row){ alert('Product row not found'); return; }
            var qtyEl = row.querySelector('.quantity');
            var qty = qtyEl ? (parseInt(qtyEl.value) || 0) : 0;
            if(qty <= 0){ try{ showToast && showToast('Warning','Please enter a valid quantity for this row before auto-generating serials','warning'); }catch(e){ alert('Please enter a valid quantity for this row before auto-generating serials'); } return; }
            // generate serials and populate modal inputs
            var box = document.getElementById('serialNumberBox'); if(!box) return; box.innerHTML = '';
            // Use professional uppercase format: <PROD>-YYYYMMDD-<ROW>-<SEQ>
            var prodName = '';
            try{ prodName = (row.querySelector('input[name="selectProductName[]"]')||{}).value || ''; }catch(e){}
            var prodCode = buildProductCode(prodName) || ('P'+idx);
            var dt = new Date();
            var ymd = dt.getFullYear().toString() + String(dt.getMonth()+1).padStart(2,'0') + String(dt.getDate()).padStart(2,'0');
            for(var i=1;i<=qty;i++){
                try{
                    var code = (prodCode + '-' + ymd + '-' + idx + '-' + String(i).padStart(4,'0')).toUpperCase();
                    var r = createSerialRow(code);
                    box.appendChild(r);
                }catch(e){}
            }
            return;
        }
        if(target && target.classList && target.classList.contains('remove-row')){
            var tr = target.closest('tr');
            if(!tr) return;

            var doRemove = function(){
                try{ tr.remove(); }catch(e){}
                try{ if(typeof updateTotalsClientSide === 'function') updateTotalsClientSide(); }catch(e){}
                try{ if(typeof window.recalcFinancials === 'function') window.recalcFinancials(); }catch(e){}
                try{
                    var rows = document.querySelectorAll('tr.product-row');
                    if(!rows || rows.length === 0){
                        var ds = document.getElementById('discountStatus'); if(ds) ds.setAttribute('disabled','disabled');
                        var dam = document.getElementById('discountAmount'); if(dam) dam.setAttribute('readonly','readonly');
                        var dper = document.getElementById('discountPercent'); if(dper) dper.setAttribute('readonly','readonly');
                        var paid = document.getElementById('paidAmount'); if(paid) paid.setAttribute('readonly','readonly');
                        var note = document.getElementById('specialNote'); if(note) note.setAttribute('readonly','readonly');
                    }
                }catch(e){}
            };

            // Prefer SweetAlert2 (Swal.fire) or older swal if available, otherwise fallback to confirm()
            try{
                if(window.Swal && typeof window.Swal.fire === 'function'){
                    window.Swal.fire({ title: 'Remove row', text: 'Are you sure you want to remove this product row?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Remove', cancelButtonText: 'Cancel' })
                        .then(function(result){ if(result && result.isConfirmed){ doRemove(); } });
                } else if(window.swal && typeof window.swal === 'function'){
                    // some versions expose swal as a function returning a promise
                    try{ window.swal({ title: 'Remove row', text: 'Are you sure you want to remove this product row?', icon: 'warning', buttons: true }).then(function(ok){ if(ok) doRemove(); }); }
                    catch(e){ if(confirm('Remove this row?')) doRemove(); }
                } else {
                    if(confirm('Remove this row?')) doRemove();
                }
            }catch(e){ if(confirm('Remove this row?')) doRemove(); }
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
                    existing.forEach(function(h){ box.appendChild(createSerialRow(h.value||'')); });
                } else {
                    box.appendChild(createSerialRow(''));
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

// Client-side validation for Add Purchase form
(function(){
    try{
        var onDom = function(){
            var form = document.getElementById('savePurchase');
            if(!form) return;
            form.addEventListener('submit', function(e){
                try{
                    // Ensure supplier selected
                    var supplier = document.getElementById('supplierName');
                    if(!supplier || !supplier.value){ e.preventDefault(); alert('Please select a Supplier before saving the purchase.'); supplier && supplier.focus(); return false; }

                    // Ensure at least one product row exists
                    var rows = document.querySelectorAll('tr.product-row');
                    if(!rows || rows.length === 0){ e.preventDefault(); alert('Please add at least one product to the purchase.'); return false; }

                    // Validate each row
                    for(var i=0;i<rows.length;i++){
                        var r = rows[i];
                        var prod = r.querySelector('input[name="productName[]"]');
                        var qty = r.querySelector('.quantity');
                        var buy = r.querySelector('[id^="buyPrice"]') || r.querySelector('input[name="buyPrice[]"]');
                        var rowNum = i+1;
                        if(!prod || !prod.value){ e.preventDefault(); alert('Row '+rowNum+': product not set. Please select a product.'); prod && prod.focus(); return false; }
                        var qv = qty ? Number(qty.value) : 0;
                        if(!qty || isNaN(qv) || qv <= 0){ e.preventDefault(); alert('Row '+rowNum+': please enter a valid quantity (>0).'); qty && qty.focus(); return false; }
                        if(!buy || buy.value === '' || isNaN(Number(buy.value))){ e.preventDefault(); alert('Row '+rowNum+': please enter the Buy Price.'); buy && buy.focus(); return false; }
                    }

                    // All client checks passed; allow submit to proceed
                    return true;
                }catch(err){ console.warn('Purchase form validation error', err); }
            });
        };
        if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', onDom); else onDom();
    }catch(e){ console.warn('purchase form validation setup failed', e); }
})();
