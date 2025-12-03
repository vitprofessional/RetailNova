// Sale page behaviors: enable product select after customer selection, fetch purchase details,
// and append sale rows with proper input names expected by saveSale controller.
(function(){
    function byId(id){ return document.getElementById(id); }
    function num(v){ v = (v==null? '': String(v)); v = v.replace(/,/g,''); var n = Number(v); return isNaN(n)? 0 : n; }

    // Derive dynamic base & templates from seed div (more reliable than url('/'))
    function deriveRoutes(){
        var seed = document.getElementById('rn-route-seeds');
        if(!seed) return {};
        var newsale = seed.getAttribute('data-newsale') || '';
        var productsTpl = seed.getAttribute('data-products-template') || '';
        var purchaseTpl = seed.getAttribute('data-purchase-template') || '';
        var purchaseByIdTpl = seed.getAttribute('data-purchase-by-id-template') || '';
        // Use URL API when possible
        var base = '';
        try{
            if(newsale){
                var u = new URL(newsale, window.location.origin);
                // Remove trailing /new/sale from path
                var path = u.pathname.replace(/\/?new\/sale\/?$/,'');
                base = u.origin + path;
            }
        }catch(e){ base = window.location.origin; }
        if(base.endsWith('/')) base = base.slice(0,-1);
        // Subfolder correction: if current location path contains a first segment (e.g. /RetailNova)
        // not present in derived base, prepend it.
        try {
            var locPath = window.location.pathname; // e.g. /RetailNova/new/sale
            var segmentMatch = locPath.match(/^\/(\w[^\/]+)(?:\/|$)/); // first segment
            if(segmentMatch){
                var firstSeg = segmentMatch[1];
                if(base === window.location.origin || base === window.location.origin+''){
                    base = window.location.origin + '/' + firstSeg;
                } else if(base.indexOf('/'+firstSeg) === -1){
                    // ensure we include the subfolder if missing
                    base = window.location.origin + '/' + firstSeg;
                }
            }
        } catch(e){ /* ignore */ }
        return { base: base, productsTpl: productsTpl, purchaseTpl: purchaseTpl, purchaseByIdTpl: purchaseByIdTpl };
    }
    var ROUTES = {}; // will populate on DOMContentLoaded

    // Ensure AJAX URLs are finalized safely. If a template is absolute or root-relative,
    // use it as-is. Otherwise fall back to `adjustForSubfolder` for legacy relative paths.
    function safeFinalizeUrl(url){
        try{
            if(!url) return url;
            var s = String(url).trim();
            if(!s) return s;
            // if absolute url or protocol-relative or root-relative, trust it
            if(s.indexOf('http://') === 0 || s.indexOf('https://') === 0 || s.indexOf('///') === 0 || s.charAt(0) === '/') return s;
            return adjustForSubfolder(s);
        }catch(e){ return url; }
    }

    // Enable product dropdown when customer selected and load products via AJAX for that customer
    document.addEventListener('DOMContentLoaded', function(){
        ROUTES = deriveRoutes();
        var customerSel = byId('customerName');
        var productSel = document.querySelector('.js-sale-product-select') || byId('productName');
        if(!productSel) return;
        productSel.disabled = true;
        // Ensure form action posts to subfolder-aware URL
        try{
            var form = byId('saveSaleForm');
            if(form){
                var tmpl = form.getAttribute('data-action-template') || '/sale/save/data';
                var url = ROUTES.base + tmpl;
                url = adjustForSubfolder(url);
                form.setAttribute('action', url);
                if(window.__SALEDEBUG) console.debug('[SALE] Form action set to:', url);
            }
        }catch(e){ }
        // Wire payment inputs to recalc totals (direct and delegated for reliability)
        try{
            var dam = byId('discountAmount'); if(dam) dam.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
            var paid = byId('paidAmount'); if(paid) paid.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
        }catch(e){ }
        // Delegated fallback: ensure dynamically-added discount/paid inputs trigger recalculation
        try{
            document.addEventListener('input', function(ev){ try{ var t = ev.target; if(!t) return; if(t.id === 'discountAmount' || t.id === 'paidAmount'){ try{ recalcTotals(); }catch(e){} } }catch(e){} }, true);
            document.addEventListener('change', function(ev){ try{ var t = ev.target; if(!t) return; if(t.id === 'discountAmount' || t.id === 'paidAmount'){ try{ recalcTotals(); }catch(e){} } }catch(e){} }, true);
            document.addEventListener('keyup', function(ev){ try{ var t = ev.target; if(!t) return; if(t.id === 'discountAmount'){ try{ recalcTotals(); }catch(e){} } }catch(e){} }, true);
        }catch(e){ }
                if(customerSel){
            customerSel.addEventListener('change', function(){
                var cid = this.value;
                        // Fetch and display customer's previous due
                        try{
                            var prevTpl = ROUTES.base + '/ajax/public/customer/'+encodeURIComponent(cid)+'/previous-due';
                            prevTpl = safeFinalizeUrl(prevTpl);
                            fetch(prevTpl, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                                .then(function(res){ if(res.status >= 400) throw new Error('prevDue fetch failed'); return res.json(); })
                                .then(function(json){ try{ var v = (json && json.prevDue) ? json.prevDue : '0.00'; var prevEl = byId('prevDue'); if(prevEl) prevEl.value = v; var disp = byId('prevDueDisplay'); if(disp) disp.textContent = 'Previous Due: ' + v; }catch(e){} })
                                .catch(function(e){ if(window.__SALEDEBUG) console.warn('prevDue fetch error', e); var prevEl = byId('prevDue'); if(prevEl) prevEl.value = 0; var disp = byId('prevDueDisplay'); if(disp) disp.textContent = 'Previous Due: 0.00'; });
                        }catch(e){ /* ignore prev due fetch errors */ }
                if(!cid){ productSel.innerHTML = '<option value="">Select</option>'; productSel.disabled = true; return; }
                var tpl = ROUTES.productsTpl || customerSel.getAttribute('data-products-url') || '';
                    var url = tpl ? tpl.replace('__ID__', encodeURIComponent(cid)) : (ROUTES.base + '/ajax/public/customer/'+encodeURIComponent(cid)+'/products');
                    url = safeFinalizeUrl(url);
                    if(window.__SALEDEBUG) console.debug('[SALE] Products final URL:', url);
                    // Guard: do not accidentally call auth routes which may destroy session
                    try{ var low = String(url).toLowerCase(); if(low.indexOf('/logout')!==-1 || low.indexOf('/login')!==-1){ console.warn('[SALE] Aborting AJAX to auth route:', url); showToast && showToast('AJAX blocked','Invalid products URL','error'); productSel.disabled = false; return; } }catch(e){}
                    fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                    .then(function(res){
                        // If redirected to login or unauthorized, abort and notify
                        if(res.redirected || res.status === 302 || res.status === 401 || res.status === 403){
                            try{ showToast('Products fetch blocked','Authentication required or redirected','error'); }catch(_){}
                            throw new Error('Products fetch redirected or unauthorized');
                        }
                        var ct = res.headers.get('content-type') || '';
                        if(ct.indexOf('application/json') === -1){
                            // often an HTML login page or 404; bail out
                            try{ showToast('Products fetch invalid','Server returned non-JSON response','error'); }catch(_){}
                            throw new Error('Non-JSON response');
                        }
                        return res.json();
                    })
                    .then(function(data){
                        try{
                            productSel.innerHTML = (data && data.data) ? data.data : '<option value="">Select</option>';
                            productSel.disabled = false;
                        }catch(e){ productSel.disabled = false; }
                    }).catch(function(err){ console.warn('products fetch error', err); productSel.disabled = false; });
            });
        }

        // When a product is selected, fetch purchase rows and prefill first one; allow adding multiple via table
        productSel.addEventListener('change', function(){
            var pid = this.value; if(!pid) return;
            var tpl2 = ROUTES.purchaseTpl || productSel.getAttribute('data-purchase-url') || '';
            // If the option value indicates a specific purchase row (purchase_123),
            // use the purchase-by-id endpoint instead of the product-level one.
            var val = this.value;
            var isPurchaseRow = false;
            var purchaseId = null;
            if(String(val).indexOf('purchase_') === 0){ isPurchaseRow = true; purchaseId = val.split('_')[1]; }
            var url2 = '';
            if(isPurchaseRow){
                var tplById = ROUTES.purchaseByIdTpl || '';
                url2 = tplById ? tplById.replace('__ID__', encodeURIComponent(purchaseId)) : (ROUTES.base + '/ajax/public/purchase/'+encodeURIComponent(purchaseId)+'/details');
            } else {
                url2 = tpl2 ? tpl2.replace('__ID__', encodeURIComponent(pid)) : (ROUTES.base + '/ajax/public/sale/product/'+encodeURIComponent(pid)+'/purchase-details');
            }
            url2 = safeFinalizeUrl(url2);
            if(window.__SALEDEBUG) console.debug('[SALE] Fetch purchase details URL:', url2, 'isPurchaseRow:', isPurchaseRow);
            try{ var low2 = String(url2).toLowerCase(); if(low2.indexOf('/logout')!==-1 || low2.indexOf('/login')!==-1){ console.warn('[SALE] Aborting AJAX to auth route:', url2); showToast && showToast('AJAX blocked','Invalid product details URL','error'); return; } }catch(e){}
            fetch(url2, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                        .then(function(res){
                            if(res.redirected || res.status === 302 || res.status === 401 || res.status === 403){
                                try{ showToast('Product details blocked','Authentication required or redirected','error'); }catch(_){}
                                throw new Error('Purchase details fetch redirected/unauthorized');
                            }
                            var ct = res.headers.get('content-type') || '';
                            if(ct.indexOf('application/json') === -1){
                                try{ showToast('Product details invalid','Server returned non-JSON response','error'); }catch(_){}
                                throw new Error('Non-JSON response for purchase details');
                            }
                            return res.json();
                        })
                        .then(function(res){
                            try{
                                if(!res || !res.getData || !res.getData.length){ showToast('No purchase found','This product has no purchase rows','warning'); return; }
                                appendSaleRow(res.getData[0]);
                                recalcTotals();
                            }catch(e){ console.warn('sale product details parse', e); }
                        }).catch(function(e){ console.warn('getSaleProductDetails failed', e); });
        });
    });

    function appendSaleRow(p){
        try{
            var tbody = byId('productDetails'); if(!tbody) return;
            // If this purchaseId already exists in the table, increment qty instead of adding a duplicate row
            var purchaseId = p.purchaseId || p.id || '';
            if(purchaseId){
                var existing = tbody.querySelector('input[name="purchaseData[]"][value="'+purchaseId+'"]');
                if(existing){
                    try{
                        var row = existing.closest('tr');
                        var qtyEl = row.querySelector('.qty');
                        if(qtyEl){ qtyEl.value = (parseInt(qtyEl.value||0,10) || 0) + 1; recalcRow(row, p.currentStock || 0); recalcTotals(); showToast && showToast('Updated','Increased quantity for existing row','success'); return; }
                    }catch(e){ /* fallback to adding a new row */ }
                }
            }
            var tr = document.createElement('tr');
            // Expected by controller: purchaseData[], qty[], salePrice[], buyPrice[]; also show computed totals
            var purchaseId = p.purchaseId || p.id || '';
            var salePrice = (p.salePriceExVat!=null ? p.salePriceExVat : '')
            var buyPrice = (p.buyPrice!=null ? p.buyPrice : '')
            tr.innerHTML = ''+
                '<td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>'+
                '<td><input type="text" class="form-control" value="'+ (p.productName || '') +'" readonly></td>'+
                '<td>'+
                    '<input type="hidden" name="purchaseData[]" value="'+ purchaseId +'">'+
                    '<div class="small">Supplier: '+ (p.supplierName || '') +'</div>'+
                    '<div class="small">Invoice: '+ (p.invoice || '') +'</div>'+
                    '<div class="small">Stock: <span class="row-stock">'+ (p.currentStock || 0) +'</span></div>'+
                '</td>'+
                '<td><input type="number" class="form-control qty" name="qty[]" min="1" step="1" value="1"></td>'+
                '<td><input type="number" class="form-control salePrice" name="salePrice[]" value="'+ salePrice +'" readonly></td>'+
                '<td><input type="number" class="form-control totalSale" readonly></td>'+
                '<td><input type="number" class="form-control buyPrice" name="buyPrice[]" value="'+ buyPrice +'" readonly></td>'+
                '<td><input type="number" class="form-control totalPurchase" readonly></td>'+
                '<td><input type="text" class="form-control profitMargin" readonly></td>'+
                '<td><input type="number" class="form-control profitTotal" readonly></td>';
            tbody.appendChild(tr);

            // Attach current stock as attribute for validation
            try{ tr.setAttribute('data-current-stock', (p.currentStock || 0)); }catch(e){}

            var qtyEl = tr.querySelector('.qty');
            var saleEl = tr.querySelector('.salePrice');
            var buyEl = tr.querySelector('.buyPrice');
            try{ if(saleEl) saleEl.title = 'Sale price (managed from database)'; if(buyEl) buyEl.title = 'Buy price (managed from database)'; }catch(e){}
            [qtyEl, saleEl, buyEl].forEach(function(inp){ inp && inp.addEventListener('input', function(){ recalcRow(tr, p.currentStock || 0); recalcTotals(); }); });
            tr.querySelector('.remove-row').addEventListener('click', function(){ tr.parentNode.removeChild(tr); recalcTotals(); });
            // initial calc
            recalcRow(tr, p.currentStock || 0);
        }catch(e){ console.warn('appendSaleRow error', e); }
    }

    // Disable/enable Save button depending on whether any product rows exist
    function toggleSaveButton(){
        try{
            var saveBtn = document.querySelector('form#saveSaleForm button[type="submit"]');
            var hasRows = document.querySelectorAll('#productDetails tr').length > 0;
            if(saveBtn) saveBtn.disabled = !hasRows;
        }catch(e){}
    }

    // Client-side validation and UX for Save
    document.addEventListener('DOMContentLoaded', function(){
        try{
            toggleSaveButton();
            var form = byId('saveSaleForm');
            if(!form) return;
            form.addEventListener('submit', function(ev){
                try{
                    // basic validation: at least one row
                    var rows = Array.prototype.slice.call(document.querySelectorAll('#productDetails tr'));
                    if(rows.length === 0){ ev.preventDefault(); showToast && showToast('No items','Add at least one product before saving','warning'); return false; }
                    // validate each row qty and purchaseData
                    for(var i=0;i<rows.length;i++){
                        var r = rows[i];
                        var qty = r.querySelector('.qty');
                        var purchase = r.querySelector('input[name="purchaseData[]"]');
                        if(!purchase || !purchase.value){ ev.preventDefault(); showToast && showToast('Missing data','One or more rows are invalid','error'); return false; }
                        var qv = qty ? parseInt(qty.value||0,10) : 0;
                        if(qv <= 0){ ev.preventDefault(); showToast && showToast('Invalid quantity','Quantity must be at least 1','error'); return false; }
                        // Block submit if any row exceeds available stock
                        try{ if(r.classList && r.classList.contains('stock-exceeded')){ ev.preventDefault(); showToast && showToast('Stock exceeded','Adjust quantities below stock before saving','error'); return false; } }catch(e){}
                    }
                    // disable submit to avoid double-post and show saving indicator
                    var submitBtn = form.querySelector('button[type="submit"]');
                    if(submitBtn){ submitBtn.disabled = true; var old = submitBtn.innerHTML; submitBtn.setAttribute('data-old-label', old); submitBtn.innerHTML = 'Saving...'; }
                    return true;
                }catch(e){ console.warn('saveSale form validation failed', e); }
            });
            // keep toggling save button when rows change
            var observer = new MutationObserver(function(){ toggleSaveButton(); });
            var target = document.getElementById('productDetails'); if(target) observer.observe(target, { childList: true, subtree: false });
        }catch(e){ console.warn('sale form init failed', e); }
    });

    function recalcRow(tr, stock){
        try{
            var qty = num(tr.querySelector('.qty').value);
            var sale = num(tr.querySelector('.salePrice').value);
            var buy = num(tr.querySelector('.buyPrice').value);
            var totalSale = sale * qty;
            var totalPurchase = buy * qty;
            var profitTotal = totalSale - totalPurchase;
            var profitPercent = totalPurchase > 0 ? ((profitTotal / totalPurchase) * 100) : 0;
            tr.querySelector('.totalSale').value = Number(totalSale.toFixed(2));
            tr.querySelector('.totalPurchase').value = Number(totalPurchase.toFixed(2));
            tr.querySelector('.profitTotal').value = Number(profitTotal.toFixed(2));
            tr.querySelector('.profitMargin').value = Number(profitPercent.toFixed(2));
            // client-side stock validation (per-row)
            var errBox = byId('saleErrorSummary');
            if(errBox){ errBox.innerHTML = ''; }
            var curStock = (tr.getAttribute && tr.getAttribute('data-current-stock')) ? parseInt(tr.getAttribute('data-current-stock')||0,10) : (stock || 0);
            // ensure per-row note element
            try{
                var noteEl = tr.querySelector('.stock-note');
                if(!noteEl){ noteEl = document.createElement('div'); noteEl.className = 'text-danger small stock-note'; noteEl.style.display = 'none'; try{ tr.children[0].appendChild(noteEl); }catch(e){} }
            }catch(e){ var noteEl = tr.querySelector('.stock-note'); }
            if(curStock && qty > curStock){
                var msg = 'Quantity exceeds current stock ('+curStock+'). Adjust before saving.';
                try{ if(noteEl){ noteEl.style.display = 'block'; noteEl.textContent = msg; } }catch(e){}
                try{ tr.classList.add('stock-exceeded'); }catch(e){}
                if(errBox){ errBox.innerHTML = '<div class="text-danger small">One or more items exceed current stock. Adjust before saving.</div>'; }
            } else {
                try{ if(noteEl){ noteEl.style.display = 'none'; noteEl.textContent = ''; } }catch(e){}
                try{ tr.classList.remove('stock-exceeded'); }catch(e){}
            }
        }catch(e){ console.warn('recalcRow error', e); }
    }
    function recalcTotals(){
        try{
            var totals = Array.prototype.slice.call(document.querySelectorAll('#productDetails .totalSale'));
            var base = 0; totals.forEach(function(t){ base += num(t.value); });
            var discount = num(byId('discountAmount') && byId('discountAmount').value);
            var grand = Math.max(0, base - discount);
            var paid = num(byId('paidAmount') && byId('paidAmount').value);
            var due = Math.max(0, grand - paid);
            var curDue = due; // server will recompute safely
            var totalSaleEl = byId('totalSaleAmount'); if(totalSaleEl) totalSaleEl.value = Number(base.toFixed(2));
            var grandEl = byId('grandTotal'); if(grandEl) grandEl.value = Number(grand.toFixed(2));
            var dueEl = byId('dueAmount'); if(dueEl) dueEl.value = Number(due.toFixed(2));
            var curDueEl = byId('curDue'); if(curDueEl) curDueEl.value = Number(curDue.toFixed(2));
            // Update visible total outstanding: previous due + current due
            try{
                var prev = num(byId('prevDue') && byId('prevDue').value);
                var totalOutstanding = Number((prev + curDue).toFixed(2));
                var outEl = byId('totalOutstandingDisplay'); if(outEl) outEl.textContent = 'Total Outstanding: ' + Number(totalOutstanding.toFixed(2));
            }catch(e){ /* ignore display errors */ }
        }catch(e){ console.warn('recalcTotals error', e); }
    }
})();

// Helper injected after IIFE: adjust URL for subfolder deployments like /RetailNova
function adjustForSubfolder(url){
    try{
        var locPath = window.location.pathname; // e.g. /RetailNova/new/sale
        var segMatch = locPath.match(/^\/(\w[^\/]+)(?:\/|$)/);
        if(!segMatch) return url; // no subfolder
        var seg = segMatch[1];
        // If url already contains /seg/ after origin, leave it.
        var origin = window.location.origin;
        if(url.indexOf(origin) === 0){
            var afterOrigin = url.substring(origin.length);
            if(afterOrigin.indexOf('/'+seg+'/') === 0 || afterOrigin === '/'+seg) return url;
            // Insert segment after origin
            url = origin + '/' + seg + afterOrigin;
        } else if(url.charAt(0) === '/' && url.indexOf('/'+seg+'/') !== 0){
            url = '/' + seg + url; // relative path case
        }
    }catch(e){ /* ignore */ }
    return url;
}
