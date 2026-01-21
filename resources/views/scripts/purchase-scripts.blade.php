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
                var qtyEl = row.querySelector('.quantity') || row.querySelector('input[name="quantity"]') || row.querySelector('#quantity');
                if(qtyEl) { /* leave blank by default; do not auto-fill */ }
                var qty = 0;
                try{ qty = qtyEl ? (parseFloat(String(qtyEl.value || '').replace(/,/g,'')) || 0) : 0; }catch(e){ qty = 0; }
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

// Helper: CSRF token
function getCsrfToken(){
    try{ var m = document.querySelector('meta[name="csrf-token"]'); if(m && m.content) return m.content; }catch(e){}
    try{ if(window.Laravel && window.Laravel.csrfToken) return window.Laravel.csrfToken; }catch(e){}
    return '';
}

// Enforce serial count not exceeding quantity (hides add button and new serial inputs)
function enforceSerialCapacity(modal){
    try{
        modal = modal || document.getElementById('serialModal');
        if(!modal) return {disabled:true};
        
        // Get quantity - try multiple approaches
        var qty = 0;
        
        // First try: get from the data-current-idx (for multi-row purchases)
        var idx = modal.getAttribute('data-current-idx') || '';
        var row = idx ? document.querySelector('tr.product-row[data-idx="'+idx+'"]') : null;
        var qtyEl = row ? (row.querySelector('.quantity') || row.querySelector('input[name="quantity"]') || row.querySelector('#quantity')) : null;
        
        // Second try: if not found, try global #quantity (for single edit/new purchase)
        if(!qtyEl) qtyEl = document.getElementById('quantity');
        
        // Third try: try to find any quantity input in the page
        if(!qtyEl) {
            var qtyInputs = document.querySelectorAll('input[name="quantity"]');
            if(qtyInputs.length > 0) qtyEl = qtyInputs[qtyInputs.length - 1]; // Use last one
        }
        
        try{ qty = qtyEl ? (parseInt(String(qtyEl.value||'').replace(/,/g,'')) || 0) : 0; }catch(e){ qty = 0; }
        
        // Count existing serials (saved) and only FILLED new serial inputs (ignore empty rows)
        var existingInputs = modal.querySelectorAll('.existing-serial-input[data-serial-id]');
        var existingCount = existingInputs.length;
        var newInputs = modal.querySelectorAll('#serialNumberBox input[name="serialNumber[]"]');
        var newFilledCount = 0;
        newInputs.forEach(function(inp){ try{ if((inp.value||'').trim() !== '') newFilledCount++; }catch(_){} });
        var totalFilled = existingCount + newFilledCount;
        var atCapacity = (qty > 0 && totalFilled >= qty);
        var totalCount = totalFilled;
        // Hide/disable add controls; keep serial inputs visible so users can see generated values
        var addSerialBtn = document.getElementById('add-serial');
        var serialNumberBox = document.getElementById('serialNumberBox');
        var buttonContainer = addSerialBtn ? addSerialBtn.closest('.d-flex') : null; // container for Add/Auto/Remove All
        var perRowAutoBtns = modal.querySelectorAll('#serialNumberBox .auto-gen-serial-row');

        if(atCapacity){
            if(buttonContainer) { buttonContainer.style.setProperty('display', 'none', 'important'); }
            // Disable new inputs and per-row auto buttons but keep them visible
            newInputs.forEach(function(inp){ try{ inp.setAttribute('disabled','disabled'); }catch(_){}});
            perRowAutoBtns.forEach(function(btn){ try{ btn.setAttribute('disabled','disabled'); }catch(_){}});
        } else {
            if(buttonContainer) { buttonContainer.style.removeProperty('display'); }
            newInputs.forEach(function(inp){ try{ inp.removeAttribute('disabled'); }catch(_){}});
            perRowAutoBtns.forEach(function(btn){ try{ btn.removeAttribute('disabled'); }catch(_){}});
        }
        var info = document.getElementById('serialCapacityInfo');
        if(info){
            if(atCapacity){ info.style.display='block'; info.textContent = 'Serial limit reached for current quantity ('+qty+').'; }
            else { info.style.display='none'; }
        }
        return {count:totalCount, qty:qty, disabled:atCapacity};
    }catch(e){ 
        console.warn('enforceSerialCapacity error:', e);
        return {disabled:false}; 
    }
}

function handleSerialDelete(serialId, triggerEl){
    try{
        var url = '{!! route('deleteProductSerial', ['id' => '__ID__']) !!}'.replace('__ID__', encodeURIComponent(serialId));
        fetch(url, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': getCsrfToken()}, body: JSON.stringify({_method:'DELETE'}) })
            .then(function(res){ return res.json().catch(function(){ return {}; }); })
            .then(function(data){
                if(data && data.status === 'success'){
                    try{ var li = triggerEl.closest('.existing-serial-row'); if(li) li.remove(); }catch(e){}
                    try{ var hidden = document.querySelector('input[data-serial-id="'+serialId+'"]'); if(hidden) hidden.remove(); }catch(e){}
                    enforceSerialCapacity(document.getElementById('serialModal'));
                    try{ showToast && showToast('Deleted','Serial removed','success'); }catch(e){ alert('Serial deleted'); }
                } else {
                    var msg = (data && data.message) ? data.message : 'Failed to delete serial';
                    try{ showToast && showToast('Error', msg, 'error'); }catch(e){ alert(msg); }
                }
            })
            .catch(function(){ try{ showToast && showToast('Error','Failed to delete serial','error'); }catch(e){ alert('Failed to delete serial'); } });
    }catch(e){ console.warn('handleSerialDelete failed', e); }
}

function updateExistingSerial(serialId, newValue, inputEl){
    return fetch('{!! route('updateProductSerial', ['id' => '__ID__']) !!}'.replace('__ID__', encodeURIComponent(serialId)), {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': getCsrfToken()},
        body: JSON.stringify({_method:'PATCH', serialNumber: newValue})
    })
    .then(function(res){ 
        return res.json().catch(function(){ return {}; }); 
    })
    .then(function(data){
        if(data && data.status === 'success'){
            if(inputEl){ inputEl.dataset.original = newValue; }
            try{ showToast && showToast('Success', 'Serial updated', 'success'); }catch(e){}
            return true;
        }
        var msg = (data && data.message) ? data.message : 'Failed to update serial';
        try{ showToast && showToast('Error', msg, 'error'); }catch(e){ alert(msg); }
        throw new Error(msg);
    })
    .catch(function(err){
        throw err;
    });
}

async function saveSerialsFromModal(){
    var modal = document.getElementById('serialModal');
    if(!modal) return;
    var existingInputs = modal.querySelectorAll('.existing-serial-input[data-serial-id]');
    var updates = [];
    existingInputs.forEach(function(inp){
        var sid = inp.dataset.serialId;
        var val = (inp.value || '').trim();
        var orig = inp.dataset.original || '';
        if(sid && val && val !== orig){
            updates.push(updateExistingSerial(sid, val, inp));
        }
    });
    try{ await Promise.all(updates); }catch(e){ console.warn('Some serial updates failed', e); }
    // After saving existing serials, close modal to trigger hidden input sync for new serials
    try{
        if(window.jQuery){ $('#serialModal').modal('hide'); }
        else if(window.bootstrap && window.bootstrap.Modal){ var inst = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal); inst.hide(); }
        else { modal.classList.remove('show'); modal.style.display='none'; }
    }catch(e){ modal.classList.remove('show'); modal.style.display='none'; }
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
            enforceSerialCapacity(document.getElementById('serialModal'));
            return;
        }

        // delete existing serial via AJAX
        if(target && target.classList && target.classList.contains('delete-serial')){
            var sid = target.getAttribute('data-id');
            if(!sid) return;
            try{
                if(window.Swal && window.Swal.fire){
                    window.Swal.fire({title:'Delete serial?', text:'This will permanently remove the serial.', icon:'warning', showCancelButton:true, confirmButtonText:'Delete'}).then(function(res){ if(res && res.isConfirmed){ handleSerialDelete(sid, target); } });
                } else if(confirm('Delete this serial?')){
                    handleSerialDelete(sid, target);
                }
            }catch(e){ if(confirm('Delete this serial?')){ handleSerialDelete(sid, target); } }
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
            var modal = document.getElementById('serialModal');
            var capacity = enforceSerialCapacity(modal);
            if(capacity && capacity.disabled){ try{ showToast && showToast('Info','Serial limit reached for this quantity','info'); }catch(e){ alert('Serial limit reached for this quantity'); } return; }
            var box = document.getElementById('serialNumberBox'); if(!box) return;
            var r = createSerialRow(''); box.appendChild(r);
            enforceSerialCapacity(modal);
            return;
        }
        if(target && target.id === 'removeAllSerials'){
            var modal = document.getElementById('serialModal'); if(!modal) return;
            var existingBox = document.getElementById('existingSerialsList'); if(!existingBox) return;
            var box = document.getElementById('serialNumberBox'); if(!box) return;
            
            // Remove all existing serials (delete from DB)
            var existingInputs = Array.from(existingBox.querySelectorAll('.existing-serial-row'));
            existingInputs.forEach(function(row){
                try{
                    var serialId = row.getAttribute('data-serial-id');
                    if(serialId){
                        // Delete from database
                        fetch(window.location.origin + '/product/serial/delete/' + serialId, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': getCsrfToken() }
                        }).catch(function(e){ console.warn('Delete failed:', e); });
                    }
                    row.remove();
                }catch(e){ console.warn('Error removing existing serial:', e); }
            });
            // Show "No serials" message
            if(existingBox.children.length === 0){
                var noMsg = document.getElementById('existingSerialsEmpty');
                if(!noMsg){
                    noMsg = document.createElement('div');
                    noMsg.id = 'existingSerialsEmpty';
                    noMsg.className = 'text-muted small';
                    noMsg.textContent = 'No serials for this purchase.';
                    existingBox.appendChild(noMsg);
                } else {
                    noMsg.style.display = '';
                }
            }
            
            // Remove all new serials
            var newRows = Array.from(box.querySelectorAll('.serial-input-row'));
            newRows.forEach(function(row){ try{ row.remove(); }catch(e){} });
            // Add one empty serial input row
            var r = createSerialRow(''); box.appendChild(r);
            
            enforceSerialCapacity(modal);
            try{ showToast && showToast('Success','All serials removed','success'); }catch(e){}
            return;
        }
        if(target && target.id === 'autoGenerateSerials'){
            var modal = document.getElementById('serialModal'); if(!modal) return;
            var idx = modal.getAttribute('data-current-idx'); if(!idx) { alert('Row not found'); return; }
            var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]');
            if(!row){ alert('Product row not found'); return; }
            var qtyEl = row.querySelector('.quantity') || row.querySelector('input[name="quantity"]') || row.querySelector('#quantity');
            var qty = 0;
            try{ qty = qtyEl ? (parseInt(String(qtyEl.value || '').replace(/,/g,'')) || 0) : 0; }catch(e){ qty = 0; }
            if(qty <= 0){ try{ showToast && showToast('Warning','Please enter a valid quantity for this row before auto-generating serials','warning'); }catch(e){ alert('Please enter a valid quantity for this row before auto-generating serials'); } return; }

            var box = document.getElementById('serialNumberBox'); if(!box) return;
            var existingBox = document.getElementById('existingSerialsList');
            // collect all inputs (existing + new)
            var inputs = [];
            try{ inputs = inputs.concat(Array.from(existingBox ? existingBox.querySelectorAll('.existing-serial-input') : [])); }catch(e){}
            try{ inputs = inputs.concat(Array.from(box.querySelectorAll('input[name="serialNumber[]"]'))); }catch(e){}

            // create extra rows if fewer than qty
            while(inputs.length < qty){
                var r = createSerialRow('');
                box.appendChild(r);
                var inp = r.querySelector('input[name="serialNumber[]"]');
                if(inp) inputs.push(inp);
            }
            // trim extra non-existing rows if over qty
            if(inputs.length > qty){
                var toRemove = inputs.length - qty;
                // remove from new rows container only to preserve existing DB rows
                var newRows = box.querySelectorAll('.serial-input-row');
                for(var ri=newRows.length-1; ri>=0 && toRemove>0; ri--){
                    try{ newRows[ri].remove(); toRemove--; }catch(e){}
                }
                inputs = [];
                try{ inputs = inputs.concat(Array.from(existingBox ? existingBox.querySelectorAll('.existing-serial-input') : [])); }catch(e){}
                try{ inputs = inputs.concat(Array.from(box.querySelectorAll('input[name="serialNumber[]"]'))); }catch(e){}
            }

            // generate codes and assign to all inputs (existing + new) up to qty
            var prodName = '';
            try{ prodName = (row.querySelector('input[name="selectProductName[]"]')||{}).value || ''; }catch(e){}
            var prodCode = buildProductCode(prodName) || ('P'+idx);
            var dt = new Date();
            var ymd = dt.getFullYear().toString() + String(dt.getMonth()+1).padStart(2,'0') + String(dt.getDate()).padStart(2,'0');
            for(var i=0;i<qty && i<inputs.length;i++){
                try{
                    var code = (prodCode + '-' + ymd + '-' + idx + '-' + String(i+1).padStart(4,'0')).toUpperCase();
                    inputs[i].value = code;
                }catch(e){}
            }
            enforceSerialCapacity(modal);
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
            enforceSerialCapacity(modal);
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

// Validate serial counts against quantities before submitting the purchase form (create or edit)
document.addEventListener('submit', function(e){
    try{
        var form = e.target;
        if(!form || form.id !== 'savePurchase') return;

        var rows = Array.from(document.querySelectorAll('tr.product-row'));
        if(!rows.length) return; // nothing to validate

        var isEditPage = (rows.length === 1) && !!document.getElementById('serialModal');
        var problems = [];
        var firstBadRow = null;

        rows.forEach(function(row, idx){
            try{
                var qtyEl = row.querySelector('.quantity') || row.querySelector('input[name="quantity"]') || row.querySelector('#quantity');
                var qty = 0;
                try{ qty = qtyEl ? (parseInt(String(qtyEl.value || '').replace(/,/g,'')) || 0) : 0; }catch(_){ qty = 0; }

                // Count serials
                var newHidden = row.querySelectorAll('input[type="hidden"][data-serial]').length;
                var existing = 0;
                if(isEditPage){
                    // On edit page, existing serials are not posted as hidden inputs. Count from modal DOM.
                    existing = document.querySelectorAll('#existingSerialsList .existing-serial-input').length;
                }
                var totalSerials = newHidden + existing;

                if(qty > totalSerials){
                    var productName = '';
                    try{ productName = (row.querySelector('input[name="selectProductName[]"]')||{}).value || (row.querySelector('#productName')||{}).value || ''; }catch(_){ }
                    problems.push('Row '+(idx+1)+(productName? (' ['+productName+']'):'')+': Qty '+qty+' > Serials '+totalSerials);
                    if(!firstBadRow) firstBadRow = row;
                }
            }catch(_){ }
        });

        if(problems.length){
            e.preventDefault();
            // Re-enable submit button and restore label if a previous submit listener disabled it
            try{
                var submitBtn = form.querySelector('button[type="submit"]');
                if(submitBtn){
                    submitBtn.disabled = false;
                    var old = submitBtn.getAttribute('data-old');
                    if(old){ submitBtn.innerHTML = old; submitBtn.removeAttribute('data-old'); }
                }
            }catch(_){ }
            try{
                if(window.Swal && typeof window.Swal.fire === 'function'){
                    window.Swal.fire({
                        title: 'Validation error',
                        html: 'Each row must have serials ≥ qty.<br><br>'+problems.join('<br>'),
                        icon: 'error'
                    });
                } else if(window.swal && typeof window.swal === 'function'){
                    window.swal('Validation error', problems.join('\n'), 'error');
                } else if(typeof showToast === 'function'){
                    showToast('Validation', problems.join('\n'), 'error');
                } else {
                    alert('Validation error:\n'+problems.join('\n'));
                }
            }catch(_){ alert('Validation error:\n'+problems.join('\n')); }
            try{ if(firstBadRow) firstBadRow.scrollIntoView({behavior:'smooth', block:'center'}); }catch(_){}
        }
    }catch(err){ console.warn('submit validation error', err); }
});

// When serial modal is shown, enforce capacity limits
if(window.jQuery){
    $(document).on('shown.bs.modal', '#serialModal', function(){
        try{
            var modal = document.getElementById('serialModal');
            if(modal) {
                enforceSerialCapacity(modal);
            }
        }catch(e){ console.warn('shown.bs.modal handler error', e); }
    });
}

// When serial modal is hidden, copy serial inputs back into the row as hidden inputs
if(window.jQuery){
    $(document).on('hidden.bs.modal', '#serialModal', function(){
        try{
            var modal = document.getElementById('serialModal'); if(!modal) return;
            var idx = modal.getAttribute('data-current-idx'); if(!idx) return;
            var row = document.querySelector('tr.product-row[data-idx="'+idx+'"]'); if(!row) return;
            // remove old hidden serial inputs
            var olds = row.querySelectorAll('input[type="hidden"][data-serial]'); olds.forEach(function(o){ o.remove(); });
            
            // collect ONLY NEW serial inputs (not existing ones - they're already in DB)
            var allInputs = [];
            var newInputs = document.querySelectorAll('#serialNumberBox input[name="serialNumber[]"]');
            newInputs.forEach(function(inp){ allInputs.push(inp); });
            
            // Detect if this is an edit page (single row with simple form structure)
            var isEditPage = !document.querySelector('input[name="productName[]"]');
            
            allInputs.forEach(function(inp){ 
                var v = inp.value.trim(); 
                if(v==='') return; 
                var h = document.createElement('input'); 
                h.type='hidden'; 
                // Use flat array for edit page, nested array for add page
                h.name = isEditPage ? 'serialNumber[]' : 'serialNumber['+idx+'][]';
                h.value = v; 
                h.setAttribute('data-serial','1'); 
                row.appendChild(h); 
            });
            // Verify what was added
            var afterAdd = row.querySelectorAll('input[type="hidden"][data-serial]');
        }catch(e){ console.warn('serial modal hide handler', e); }
    });

    // Handle existing serial input changes with blur event
    $(document).on('blur', '.existing-serial-input', function(){
        try{
            var input = this;
            var serialId = input.getAttribute('data-serial-id');
            var newValue = input.value.trim();
            var originalValue = input.getAttribute('data-original');
            
            if(!serialId || newValue === originalValue || newValue === ''){
                return;
            }
            
            updateExistingSerial(serialId, newValue, input).catch(function(e){
                input.value = originalValue;
                console.warn('Restoring original value due to error:', e);
            });
        }catch(e){ console.warn('existing serial blur handler', e); }
    });
}

// Client-side validation for Add Purchase form
(function(){
    try{
        var onDom = function(){
            // Sync hidden productName input with select when select changes
            var prodSelect = document.getElementById('productName');
            var prodHidden = document.getElementById('productNameHidden');
            if(prodSelect && prodHidden){
                // Save original options to data attribute for later restoration if needed
                if(!prodSelect.dataset.origOptions){
                    prodSelect.dataset.origOptions = prodSelect.innerHTML;
                }
                
                // Initial sync - get value from select, or fall back to hidden input if select is disabled
                var selectValue = prodSelect.value;
                if(!selectValue && prodHidden.value){
                    selectValue = prodHidden.value;
                }
                prodHidden.value = selectValue;
                
                // Sync on change
                prodSelect.addEventListener('change', function(){
                    prodHidden.value = this.value;
                });
            }

            var form = document.getElementById('savePurchase');
            if(!form) return;
            form.addEventListener('submit', function(e){
                try{                    
                    // Sync productName hidden input with select value before submission
                    var prodSelect = document.getElementById('productName');
                    var prodHidden = document.getElementById('productNameHidden');
                    if(prodSelect && prodHidden){
                        // Prefer select value, but fall back to hidden if select is disabled/empty
                        var selectValue = prodSelect.value;
                        if(!selectValue && prodHidden.value){
                            selectValue = prodHidden.value;
                        }
                        prodHidden.value = selectValue;
                    }
                    // Collect serial inputs being submitted (no logging)
                    var serialInputs = document.querySelectorAll('input[type="hidden"][data-serial]');
                    
                    // Ensure supplier selected
                    var supplier = document.getElementById('supplierName');
                    if(!supplier || !supplier.value){ e.preventDefault(); alert('Please select a Supplier before saving the purchase.'); supplier && supplier.focus(); return false; }

                    // Check if this is edit mode (has purchaseId) or create mode
                    var purchaseIdField = document.querySelector('input[name="purchaseId"]');
                    var purchaseIdValue = purchaseIdField ? purchaseIdField.value : '';
                    var isEditMode = purchaseIdField && purchaseIdValue;
                    
                    if(isEditMode){
                        // Edit mode: validate single global product and quantity selectors
                        // Note: productName is submitted via hidden input, so check that directly
                        var prodHidden = document.querySelector('input[name="productName"]');
                        var prodSelect = document.querySelector('select[name="productName_select"]');
                        var prodValue = '';
                        
                        // Get value from hidden input (which is kept in sync with select by JS)
                        if(prodHidden && prodHidden.value){
                            prodValue = prodHidden.value;
                        }
                        // If hidden input is empty, try to get from select as fallback
                        if(!prodValue && prodSelect){
                            if(prodSelect.selectedIndex >= 0 && prodSelect.options[prodSelect.selectedIndex]){
                                prodValue = prodSelect.options[prodSelect.selectedIndex].value;
                            }
                        }
                        
                        if(!prodValue){ e.preventDefault(); alert('Please select a Product before saving.'); prodSelect && prodSelect.focus && prodSelect.focus(); return false; }
                        var qty = document.querySelector('input[name="quantity"]');
                        if(!qty || Number(qty.value) <= 0){ e.preventDefault(); alert('Please enter a valid quantity (>0).'); qty && qty.focus(); return false; }
                        var buy = document.querySelector('input[name="buyPrice"]');
                        if(!buy || buy.value === '' || isNaN(Number(buy.value))){ e.preventDefault(); alert('Please enter the Buy Price.'); buy && buy.focus(); return false; }
                        return true;
                    }

                    // Ensure at least one product row exists
                    var rows = document.querySelectorAll('tr.product-row');
                    if(!rows || rows.length === 0){ e.preventDefault(); alert('Please add at least one product to the purchase.'); return false; }

                    // Validate each row
                    for(var i=0;i<rows.length;i++){
                        var r = rows[i];
                        // Support multiple templates: per-row product hidden (`productName[]`), per-row display (`selectProductName[]`),
                        // or single-row edit form using top-level `productName` select/input.
                        var prod = r.querySelector('input[name="productName[]"], input[name="selectProductName[]"], select[name="productName_select"], input[name="productName"]');
                        // if row doesn't have a per-row product input, fall back to top-level product selector
                        if(!prod){ 
                            prod = document.querySelector('select[name="productName_select"], input[name="productName"]');
                        }
                        var qty = r.querySelector('[id^="quantity"]') || r.querySelector('input[name="quantity[]"]') || document.querySelector('input[name="quantity"]');
                        var buy = r.querySelector('[id^="buyPrice"]') || r.querySelector('input[name="buyPrice[]"]') || document.querySelector('input[name="buyPrice"]');
                        var rowNum = i+1;
                        // determine a usable product value: support select/input value or plain text display
                        var prodValue = '';
                        try{
                            if(!prod){ prodValue = ''; }
                            else if(('value' in prod) && prod.value !== undefined){ prodValue = String(prod.value).trim(); }
                            else { prodValue = (prod.textContent || prod.innerText || '').trim(); }
                        }catch(e){ prodValue = ''; }
                        if(!prodValue){ e.preventDefault(); alert('Row '+rowNum+': product not set. Please select a product.'); try{ if(prod && prod.focus) prod.focus(); }catch(_){} return false; }
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
