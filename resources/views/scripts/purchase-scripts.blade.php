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
        if ($) {
            if(ts) $('#' + ts).html(totalSale);
            if(tp) $('#' + tp).html(totalPurchase);
            if(pm) $('#' + pm).html(profitPercent);
            if(pt) $('#' + pt).html(profitValue);
        } else {
            try{ if(ts) document.getElementById(ts).innerHTML = totalSale; }catch(_){ }
            try{ if(tp) document.getElementById(tp).innerHTML = totalPurchase; }catch(_){ }
            try{ if(pm) document.getElementById(pm).innerHTML = profitPercent; }catch(_){ }
            try{ if(pt) document.getElementById(pt).innerHTML = profitValue; }catch(_){ }
        }
        // Update local aggregated totals immediately (client-side, without server)
        try{
            if(typeof updateTotalsClientSide === 'function') updateTotalsClientSide();
        }catch(e){}

        // debounce server call (build items without jQuery; use fetch fallback)
        window._saleDebounceTimers = window._saleDebounceTimers || {};
        var timerKey = String(qd || pid) + '_calc';
        if (window._saleDebounceTimers[timerKey]) clearTimeout(window._saleDebounceTimers[timerKey]);
        window._saleDebounceTimers[timerKey] = setTimeout(function(){
            try{
                var items = [];
                var rows = document.querySelectorAll('.product-row');
                rows.forEach(function(r){
                    try{
                        var priceEl = r.querySelector('.sale-price');
                        var qtyElRow = r.querySelector('.quantity');
                        var price = parseFloat(priceEl ? (priceEl.value || 0) : 0) || 0;
                        var quantity = parseFloat(qtyElRow ? (qtyElRow.value || 0) : 0) || 0;
                        items.push({ price: price, quantity: quantity });
                    }catch(e){}
                });

                // Build URL with encoded items for GET fallback (server previously accepted GET)
                var url = '{{ route("calculate.grand.total") }}';
                var params = 'purchaseId=' + encodeURIComponent(pid || '') + '&items=' + encodeURIComponent(JSON.stringify(items));

                var handleResponse = function(response){
                    try{
                        const serverGrandTotal = num(response.grandTotal || response.total || 0);
                        const currentStock = parseInt(response.currentStock) || 0;
                        const discountAmount = num((document.getElementById('discountAmount')||{value:0}).value);
                        const paidAmount     = num((document.getElementById('paidAmount')||{value:0}).value);
                        const gTotal    = Math.max(0, serverGrandTotal - discountAmount);
                        const dueAmount = Math.max(0, gTotal - paidAmount);
                        var setVal = function(id, v){ var el = document.getElementById(id); if(el) el.value = v; };
                        setVal('grandTotal', gTotal);
                        setVal('totalSaleAmount', serverGrandTotal);
                        setVal('dueAmount', dueAmount);
                        setVal('curDue', dueAmount);

                        var qtyEl = document.getElementById(qd);
                        if (qty > currentStock) {
                            if (qtyEl) {
                                // show inline error
                                var next = qtyEl.nextElementSibling;
                                if(!next || !next.classList || !next.classList.contains('invalid-feedback')){
                                    var div = document.createElement('div'); div.className = 'invalid-feedback sale-error'; div.textContent = 'Only '+currentStock+' units available for the selected purchase row'; qtyEl.parentNode.insertBefore(div, qtyEl.nextSibling);
                                } else { next.textContent = 'Only '+currentStock+' units available for the selected purchase row'; }
                                qtyEl.classList.add('is-invalid');
                                var tr = qtyEl.closest('tr'); if(tr) tr.classList.add('table-danger');
                            }
                            // disable submit
                            try{ var form = document.querySelector('form[action="{{ route('saveSale') }}"]'); if(form){ var btn = form.querySelector('button[type=submit]'); if(btn) btn.disabled = true; } }catch(e){}
                        } else {
                            if(qtyEl){ qtyEl.classList.remove('is-invalid'); if(qtyEl.nextElementSibling && qtyEl.nextElementSibling.classList && qtyEl.nextElementSibling.classList.contains('invalid-feedback')) qtyEl.nextElementSibling.remove(); var tr = qtyEl.closest('tr'); if(tr) tr.classList.remove('table-danger'); }
                            var any = document.querySelectorAll('.invalid-feedback.sale-error'); if(!any || any.length === 0){ try{ var form = document.querySelector('form[action="{{ route('saveSale') }}"]'); if(form){ var btn = form.querySelector('button[type=submit]'); if(btn) btn.disabled = false; } }catch(e){} }
                        }
                    }catch(e){ console.warn('handleResponse error', e); }
                };

                // Prefer jQuery if present for the GET request (server expects previous signature), otherwise use fetch
                if(window.jQuery && typeof window.jQuery.get === 'function'){
                    window.jQuery.get(url, { items: items, purchaseId: pid }, function(response){ handleResponse(response); }).fail(function(){ /* ignored */ }).always(function(){ delete window._saleDebounceTimers[timerKey]; });
                } else {
                    fetch(url + '?' + params, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(res){ return res.json ? res.json() : {}; })
                        .then(function(resp){ handleResponse(resp); delete window._saleDebounceTimers[timerKey]; })
                        .catch(function(){ delete window._saleDebounceTimers[timerKey]; });
                }
            }catch(e){ try{ delete window._saleDebounceTimers[timerKey]; }catch(_){} }
        }, 300);
    }catch(e){ console.warn('calculateSaleDetails error', e); }
}

// Aggregate totals locally from the product rows and update grand/total/due fields
function updateTotalsClientSide(){
    try{
        if(window.__RNDEBUG) console.debug('updateTotalsClientSide running');
        var rows = document.querySelectorAll('tr.product-row');
        var totalSale = 0;
        rows.forEach(function(r){
            try{
                // prefer explicit total element if present, otherwise compute from sale-price * qty
                var tsEl = r.querySelector('[id^="totalSale"], [id^="totalAmount"]');
                var rowTotal = 0;
                if(tsEl){
                    var txt = tsEl.value || tsEl.innerHTML || '0';
                    rowTotal = Number(String(txt).replace(/,/g,'')) || 0;
                } else {
                    var price = num((r.querySelector('.sale-price')||{}).value || 0);
                    var qty = num((r.querySelector('.quantity')||{}).value || 0);
                    rowTotal = price * qty;
                }
                totalSale += rowTotal;
            }catch(e){}
        });
        var totalSaleEl = document.getElementById('totalSaleAmount'); if(totalSaleEl) totalSaleEl.value = totalSale;
        var discountAmount = num((document.getElementById('discountAmount')||{value:0}).value);
        var paidAmount = num((document.getElementById('paidAmount')||{value:0}).value);
        var gTotal = Math.max(0, totalSale - discountAmount);
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

                try{ if(ts) { var el = document.getElementById(ts); if(el) el.innerHTML = totalSale; } if(tp){ var el2 = document.getElementById(tp); if(el2) el2.innerHTML = totalPurchase; } if(sp) { var el3 = document.getElementById(sp); if(el3) el3.value = salePrice; } if(bp){ var el4 = document.getElementById(bp); if(el4) el4.value = buyPrice; } if(pm){ var el5 = document.getElementById(pm); if(el5) el5.innerHTML = profitPercent; } if(pt){ var el6 = document.getElementById(pt); if(el6) el6.innerHTML = profitValue; } }catch(_){ }
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

    // Utility helpers used across purchase/sale flows
    function num(v){ if (v === null || v === undefined) return 0; try{ const s = String(v).trim(); if(!s) return 0; return Number(String(s).replace(/,/g,'')) || 0; }catch(e){ return 0; } }

    function resolveValue(idOrEl){ try{ if(!idOrEl) return ''; if(typeof idOrEl === 'string'){ var el = document.getElementById(idOrEl); if(el){ return ('value' in el)? el.value : el.innerHTML; } return idOrEl; } if(idOrEl && 'value' in idOrEl) return idOrEl.value; return ''; }catch(e){ return ''; } }

    // Single-row recalculation (Add/Edit Purchase pages)
    function recalcPurchaseRow(){ try{ if(window.__RNDEBUG) console.debug('recalcPurchaseRow called'); var qty = num((document.getElementById('quantity')||{value:0}).value); var buy = num((document.getElementById('buyPrice')||{value:0}).value); var sale = num((document.getElementById('salePriceExVat')||{value:0}).value); var unit = (sale>0? sale : buy); var total = (unit * qty) || 0; var totalEl = document.getElementById('totalAmount'); if(totalEl) totalEl.value = (total? total : ''); }catch(e){ console.warn('recalcPurchaseRow', e); } }

    // Price/profit helpers used by inline handlers
    function priceCalculation(){ try{ var sale = num((document.getElementById('salePriceExVat')||{value:0}).value); var buy = num((document.getElementById('buyPrice')||{value:0}).value); var profitEl = document.getElementById('profitMargin'); if(profitEl && buy>0){ var p = (((sale - buy)/buy)*100); profitEl.value = Number(p.toFixed(2)); } recalcPurchaseRow(); }catch(e){ console.warn('priceCalculation', e); } }

    function profitCalculation(){ try{ var pm = num((document.getElementById('profitMargin')||{value:0}).value); var buy = num((document.getElementById('buyPrice')||{value:0}).value); if(buy>0){ var sale = buy * (1 + (pm/100)); var spEl = document.getElementById('salePriceExVat'); if(spEl) spEl.value = Number(sale.toFixed(2)); } recalcPurchaseRow(); }catch(e){ console.warn('profitCalculation', e); } }

    function totalPriceCalculate(){ try{ recalcPurchaseRow(); // keep name for compatibility with inline handlers
        // also update any grand totals if present
        if(typeof window.calculateSaleDetails === 'function') try{ window.calculateSaleDetails(0); }catch(e){} }catch(e){ console.warn('totalPriceCalculate', e); } }

    // Discount and due calculations (Add/Edit Purchase pages)
    function discountType(){ try{ var status = (document.getElementById('discountStatus')||{}).value || ''; var dam = document.getElementById('discountAmount'); var dper = document.getElementById('discountPercent'); if(status === 'percent'){ if(dam) dam.setAttribute('readonly','readonly'); if(dper) dper.removeAttribute('readonly'); } else if(status === 'amount'){ if(dper) dper.setAttribute('readonly','readonly'); if(dam) dam.removeAttribute('readonly'); } else { if(dper) dper.setAttribute('readonly','readonly'); if(dam) dam.setAttribute('readonly','readonly'); } }catch(e){ console.warn('discountType', e); } }

    function discountAmountChange(){ try{ var dam = num((document.getElementById('discountAmount')||{value:0}).value); var total = num((document.getElementById('totalSaleAmount')||{value:0}).value); var dper = document.getElementById('discountPercent'); if(total>0 && dper){ var perc = (dam/total)*100; dper.value = Number(perc.toFixed(2)); } dueCalculate(); }catch(e){ console.warn('discountAmountChange', e); } }

    function discountPercentChange(){ try{ var per = num((document.getElementById('discountPercent')||{value:0}).value); var total = num((document.getElementById('totalSaleAmount')||{value:0}).value); var damEl = document.getElementById('discountAmount'); if(total>0 && damEl){ var amt = (per/100)*total; damEl.value = Number(amt.toFixed(2)); } dueCalculate(); }catch(e){ console.warn('discountPercentChange', e); } }

    function dueCalculate(){ try{ var total = num((document.getElementById('totalSaleAmount')||{value:0}).value); var dam = num((document.getElementById('discountAmount')||{value:0}).value); var paid = num((document.getElementById('paidAmount')||{value:0}).value); var gTotal = Math.max(0, total - dam); var due = Math.max(0, gTotal - paid); var dueEl = document.getElementById('dueAmount'); if(dueEl) dueEl.value = due; var curEl = document.getElementById('curDue'); if(curEl) curEl.value = due; var grandEl = document.getElementById('grandTotal'); if(grandEl) grandEl.value = gTotal; }catch(e){ console.warn('dueCalculate', e); } }

    // expose compatibility names for RNHandlers registration
    window.totalPriceCalculate = window.totalPriceCalculate || totalPriceCalculate;
    window.priceCalculation = window.priceCalculation || priceCalculation;
    window.profitCalculation = window.profitCalculation || profitCalculation;
    window.discountType = window.discountType || discountType;
    window.discountAmountChange = window.discountAmountChange || discountAmountChange;
    window.discountPercentChange = window.discountPercentChange || discountPercentChange;
    window.dueCalculate = window.dueCalculate || dueCalculate;

    // Ensure key helpers are reachable as globals and bind single-row listeners defensively
    try{
        window.recalcPurchaseRow = window.recalcPurchaseRow || recalcPurchaseRow;
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
                if(saleEl){ saleEl.addEventListener('input', function(){ try{ calculateSaleDetails(0, row.id||('productField'+rowIdx), ids.pf, ids.bp, ids.sp, ids.ts, ids.tp, ids.qd, ids.pm, ids.pt); }catch(e){} }); }
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
                var buy = row.querySelector('#buyPrice'+idx) || row.querySelector('#buyPrice__IDX__'.replace('__IDX__', idx)); if(buy) buy.value = (data.buyPrice || data.buyingPrice || '');
                var sale = row.querySelector('#salePriceExVat'+idx) || row.querySelector('#salePriceExVat__IDX__'.replace('__IDX__', idx)); if(sale) sale.value = (data.salePrice || data.salePriceExVat || '');
                try{ var vat = (data.vatStatus!==undefined? data.vatStatus : (data.vat || '')); var vatEl = row.querySelector('#vatStatus'+idx) || row.querySelector('#vatStatus__IDX__'.replace('__IDX__', idx)); if(vatEl){ for(var i=0;i<vatEl.options.length;i++){ if(vatEl.options[i].value == vat){ vatEl.selectedIndex = i; break; } } } }catch(_){ }
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
