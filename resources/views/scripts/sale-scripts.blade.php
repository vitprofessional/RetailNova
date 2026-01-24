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
        var purchaseSerialTpl = seed.getAttribute('data-purchase-serials-template') || '';
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
        return { base: base, productsTpl: productsTpl, purchaseTpl: purchaseTpl, purchaseByIdTpl: purchaseByIdTpl, purchaseSerialTpl: purchaseSerialTpl };
    }
    var ROUTES = {}; // will populate on DOMContentLoaded
    var SALE_ROW_COUNTER = 0; // per-row counter to track serial selections

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
                var url = '';
                // If the template is already absolute or root-relative, skip prefixing with ROUTES.base
                if(tmpl.indexOf('http://') === 0 || tmpl.indexOf('https://') === 0 || tmpl.charAt(0) === '/'){
                    url = tmpl;
                } else {
                    url = (ROUTES.base || '') + tmpl;
                }
                url = safeFinalizeUrl(url);
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
                            // If there are out-of-stock items returned, populate modal data and show note/button
                            try{
                                var outNote = byId('outOfStockNote');
                                var outBtn = byId('outOfStockBtn');
                                var outList = byId('outOfStockList');
                                var outArr = (data && data.outOfStock) ? data.outOfStock : [];
                                if(outNote){ outNote.textContent = outArr.length > 0 ? (outArr.length + ' product(s) out of stock') : ''; }
                                if(outBtn){ outBtn.style.display = outArr.length > 0 ? 'inline-block' : 'none'; }
                                if(outList){
                                    if(outArr.length === 0){ outList.innerHTML = '<p class="text-muted">No out-of-stock items.</p>'; }
                                    else {
                                        var html = '<div class="list-group">';
                                        outArr.forEach(function(it){
                                            var pd = it.purchaseDate ? (' ['+it.purchaseDate+']') : '';
                                            var viewLink = it.productId ? (adjustForSubfolder('/product/edit/' + encodeURIComponent(it.productId))) : '#';
                                            html += '<div class="list-group-item d-flex justify-content-between align-items-center">'+
                                                '<div><strong>'+ (it.productName || '') +'</strong> — '+ (it.supplierName || 'Unknown') + pd +
                                                ' <span class="badge bg-secondary ms-2">Stock: '+ (it.currentStock || 0) +'</span></div>' +
                                                '<div>' +
                                                    (it.productId ? '<a class="btn btn-sm btn-outline-primary me-2" href="'+viewLink+'" target="_blank">View</a>' : '') +
                                                    '<button style="display:none;" class="btn btn-sm btn-outline-success add-backorder" data-purchase-id="'+(it.purchaseId||'')+'">Add as backorder</button>' +
                                                '</div>' +
                                                '</div>';
                                        });
                                        html += '</div>';
                                        outList.innerHTML = html;
                                    }
                                }
                            }catch(e){}
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
                        var currentStock = parseInt(p.currentStock || 0, 10);
                        if(qtyEl){
                            var newQty = (parseInt(qtyEl.value||0,10) || 0) + 1;
                            if(currentStock <= 0){
                                // show friendly UI message and do not add
                                try{ var errBox = byId('saleErrorSummary'); if(errBox) errBox.innerHTML = '<div class="alert alert-warning mb-0">Product "'+(p.productName||'')+'" is out of stock and cannot be added.</div>'; setTimeout(function(){ try{ errBox.innerHTML=''; }catch(_){} },4000); }catch(e){}
                                showToast && showToast('Out of stock','Product "'+(p.productName||'')+'" has no stock','error');
                                return;
                            }
                            if(newQty > currentStock){
                                try{ var errBox2 = byId('saleErrorSummary'); if(errBox2) errBox2.innerHTML = '<div class="alert alert-danger mb-0">Cannot increase quantity beyond available stock ('+currentStock+').</div>'; setTimeout(function(){ try{ errBox2.innerHTML=''; }catch(_){} },4000); }catch(e){}
                                showToast && showToast('Low stock','Cannot increase quantity beyond available stock','error');
                                return;
                            }
                            qtyEl.value = newQty;
                            recalcRow(row, p.currentStock || 0);
                            recalcTotals();
                            showToast && showToast('Updated','Increased quantity for existing row','success');
                            return;
                        }
                    }catch(e){ /* fallback to adding a new row */ }
                }
            }
            var tr = document.createElement('tr');
            SALE_ROW_COUNTER = (SALE_ROW_COUNTER || 0) + 1;
            var idx = SALE_ROW_COUNTER;
            // Expected by controller: purchaseData[], qty[], salePrice[], buyPrice[]; also show computed totals
            var purchaseId = p.purchaseId || p.id || '';
            var salePrice = (p.salePriceExVat!=null ? p.salePriceExVat : '')
            var buyPrice = (p.buyPrice!=null ? p.buyPrice : '')
            tr.setAttribute('data-idx', idx);
            tr.setAttribute('data-purchase-id', purchaseId);
            tr.setAttribute('data-serial-available', '0');
            tr.innerHTML = `
                <td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>
                <td><input type="text" class="form-control" value="${p.productName || ''}" readonly></td>
                <td>
                    <input type="hidden" name="purchaseData[]" value="${purchaseId}">
                    <div class="small">Supplier: ${p.supplierName || ''}</div>
                    <div class="small">Invoice: ${p.invoice || ''}</div>
                    <div class="small">Stock: <span class="row-stock">${p.currentStock || 0}</span></div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary open-sale-serials" data-idx="${idx}" data-purchase-id="${purchaseId}">Serials</button>
                        <span class="badge bg-secondary ms-2 serial-count-badge" data-idx="${idx}">0 selected</span>
                    </div>
                    <div class="text-danger small serial-note" style="display:none;"></div>
                    <div class="serial-hidden" style="display:none"></div>
                </td>
                <td><input type="number" class="form-control qty" name="qty[]" min="1" step="1" value="1"></td>
                <td><input type="number" class="form-control salePrice" name="salePrice[]" value="${salePrice}" readonly></td>
                <td><input type="number" class="form-control totalSale" readonly></td>
                <td><input type="number" class="form-control buyPrice" name="buyPrice[]" value="${buyPrice}" readonly></td>
                <td><input type="number" class="form-control totalPurchase" readonly></td>
                <td><input type="text" class="form-control profitMargin" readonly></td>
                <td><input type="number" class="form-control profitTotal" readonly></td>
            `;
            // If this row was created as a backorder, mark it and include hidden marker for server
            try{
                if(p && p.__backorder){
                    tr.classList.add('backorder-row');
                    var nameCell = tr.children[1];
                    if(nameCell){ nameCell.innerHTML = nameCell.innerHTML + ' <span class="badge bg-warning text-dark backorder-badge ms-2">Backorder</span>'; }
                    var hin = document.createElement('input'); hin.type = 'hidden'; hin.name = 'backorder[]'; hin.value = purchaseId || '';
                    tr.appendChild(hin);
                }
            }catch(e){}
            // Prevent adding when there's no stock
            var stockAvailable = parseInt(p.currentStock || 0, 10);
            if(stockAvailable <= 0){
                try{ var errBox3 = byId('saleErrorSummary'); if(errBox3) errBox3.innerHTML = '<div class="alert alert-warning mb-0">Product "'+(p.productName||'')+'" is out of stock and cannot be added.</div>'; setTimeout(function(){ try{ errBox3.innerHTML=''; }catch(_){} },4000); }catch(e){}
                showToast && showToast('Out of stock','Product "'+(p.productName||'')+'" has no stock','error');
                return;
            }
            tbody.appendChild(tr);

            // Attach current stock as attribute for validation
            try{ tr.setAttribute('data-current-stock', stockAvailable); }catch(e){}

            var qtyEl = tr.querySelector('.qty');
            var saleEl = tr.querySelector('.salePrice');
            var buyEl = tr.querySelector('.buyPrice');
            try{ if(saleEl) saleEl.title = 'Sale price (managed from database)'; if(buyEl) buyEl.title = 'Buy price (managed from database)'; }catch(e){}
            [qtyEl, saleEl, buyEl].forEach(function(inp){ inp && inp.addEventListener('input', function(){ recalcRow(tr, p.currentStock || 0); recalcTotals(); validateSerialsForRow(tr); }); });
            tr.querySelector('.remove-row').addEventListener('click', function(){ tr.parentNode.removeChild(tr); reindexSaleSerialInputs(); recalcTotals(); toggleSaveButton(); });
            // initial calc
            recalcRow(tr, p.currentStock || 0);
            validateSerialsForRow(tr);
            // Prefetch available serial count to enforce selection when required
            fetchAvailableSerials(purchaseId).then(function(serials){
                try{ tr.setAttribute('data-serial-available', (serials && serials.length) ? serials.length : 0); }catch(e){}
                validateSerialsForRow(tr);
            }).catch(function(){ /* ignore */ });
            toggleSaveButton();
        }catch(e){ console.warn('appendSaleRow error', e); }
    }

    function fetchAvailableSerials(purchaseId){
        var tpl = ROUTES.purchaseSerialTpl || '';
        var url = tpl ? tpl.replace('__ID__', encodeURIComponent(purchaseId)) : (ROUTES.base + '/ajax/public/purchase/'+encodeURIComponent(purchaseId)+'/serials');
        url = safeFinalizeUrl(url);
        return fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
            .then(function(res){
                var ct = res.headers.get('content-type') || '';
                if(res.redirected || res.status === 302 || res.status === 401 || res.status === 403) throw new Error('unauthorized');
                if(ct.indexOf('application/json') === -1) throw new Error('invalid-response');
                return res.json();
            })
            .then(function(json){ return (json && json.serials) ? json.serials : []; });
    }

    function renderSerialList(serials, selectedIds){
        var list = byId('saleSerialList');
        var status = byId('saleSerialStatus');
        var meta = byId('saleSerialMeta');
        if(list) list.innerHTML = '';
        if(!serials || serials.length === 0){
            if(status){ status.className = 'alert alert-warning mb-2'; status.textContent = 'No available serials for this purchase.'; }
            if(meta) meta.textContent = '';
            return;
        }
        if(status){ status.className = 'alert alert-success mb-2'; status.textContent = 'Available serials: ' + serials.length; }
        if(meta) meta.textContent = 'Select the serials to assign to this sale row.';
        serials.forEach(function(s){
            var li = document.createElement('label');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            var checked = selectedIds.indexOf(String(s.id)) !== -1;
            li.innerHTML = '<div class="form-check">'+
                '<input class="form-check-input serial-pick" type="checkbox" name="serialPick[]" value="'+(s.id||'')+'" data-serial-number="'+(s.serialNumber||'')+'" '+(checked ? 'checked' : '')+'>\n'+
                '<span class="ms-2">'+ (s.serialNumber || '') +'</span>'+
                '</div>'+
                '<span class="badge bg-light text-muted">Available</span>';
            list.appendChild(li);
        });
        enforceSerialLimit();
    }

    function enforceSerialLimit(){
        try{
            var modal = byId('saleSerialModal');
            if(!modal) return;
            var qty = parseInt(modal.getAttribute('data-qty') || '0', 10) || 0;
            if(qty <= 0) return;
            var picks = Array.prototype.slice.call(document.querySelectorAll('#saleSerialModal input.serial-pick'));
            var checked = picks.filter(function(p){ return p.checked; });
            var limit = qty;
            picks.forEach(function(p){
                if(!p.checked && checked.length >= limit){
                    p.disabled = true;
                } else {
                    p.disabled = false;
                }
            });
        }catch(e){ console.warn('enforceSerialLimit error', e); }
    }

    function showSaleSerialModal(modal){
        if(!modal) return;
        try{
            if(typeof bootstrap !== 'undefined' && bootstrap.Modal){
                var inst = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modal) : null;
                if(!inst){ inst = new bootstrap.Modal(modal); }
                inst.show();
                return;
            }
        }catch(e){}
        if(window.jQuery && typeof window.jQuery.fn.modal === 'function'){
            try{ window.jQuery(modal).modal('show'); return; }catch(e){}
        }
        modal.classList.add('show');
        modal.style.display = 'block';
    }

    function hideSaleSerialModal(modal){
        if(!modal) return;
        var cleanup = function(){
            try{ modal.classList.remove('show'); modal.style.display = 'none'; modal.setAttribute('aria-hidden','true'); modal.removeAttribute('aria-modal'); }catch(_){ }
            try{ document.body.classList.remove('modal-open'); document.body.style.paddingRight = ''; }catch(_){ }
            try{ Array.prototype.slice.call(document.querySelectorAll('.modal-backdrop')).forEach(function(b){ if(b && b.parentNode) b.parentNode.removeChild(b); }); }catch(_){ }
        };
        try{
            if(typeof bootstrap !== 'undefined' && bootstrap.Modal){
                var inst = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modal) : null;
                if(!inst){ inst = new bootstrap.Modal(modal); }
                inst.hide();
                cleanup();
                return;
            }
        }catch(e){}
        if(window.jQuery && typeof window.jQuery.fn.modal === 'function'){
            try{ window.jQuery(modal).modal('hide'); cleanup(); return; }catch(e){}
        }
        cleanup();
    }

    function openSaleSerialModal(idx, purchaseId){
        var modal = byId('saleSerialModal'); if(!modal) return;
        var list = byId('saleSerialList'); if(list) list.innerHTML = '';
        var status = byId('saleSerialStatus'); if(status){ status.className = 'alert alert-info mb-2'; status.textContent = 'Loading available serials...'; }
        var hint = byId('saleSerialHint'); if(hint) hint.textContent = '';
        var row = document.querySelector('tr[data-idx="'+idx+'"]');
        var qty = num((row && row.querySelector('.qty')) ? row.querySelector('.qty').value : 0);
        // Preserve both creation idx and current row order so selected serials stay aligned after reindexing
        var rowOrder = row ? (row.getAttribute('data-row-order') || '') : '';
        modal.setAttribute('data-idx', idx || '');
        modal.setAttribute('data-row-order', rowOrder);
        modal.setAttribute('data-purchase-id', purchaseId || '');
        modal.setAttribute('data-qty', qty || 0);
        modal.setAttribute('data-available-count', '0');
        var existing = [];
        try{
            existing = Array.prototype.slice.call(row.querySelectorAll('input[type="hidden"][data-serial][name^="serialId"]')).map(function(h){ return String(h.value||''); });
        }catch(e){ existing = []; }
            enforceSerialLimit();
            fetchAvailableSerials(purchaseId).then(function(serials){
            var avail = serials.length || 0;
            modal.setAttribute('data-available-count', avail);
            if(row) row.setAttribute('data-serial-available', avail);
            renderSerialList(serials, existing);
            if(hint && qty){ hint.textContent = 'Select exactly '+qty+' serial(s).'; }
            enforceSerialLimit();
            showSaleSerialModal(modal);
        }).catch(function(err){
            console.warn('fetchAvailableSerials failed', err);
            if(status){ status.className = 'alert alert-danger mb-2'; status.textContent = 'Failed to load serials. Please retry.'; }
            showSaleSerialModal(modal);
        });
    }

    function applySaleSerials(){
        try{
            var modal = byId('saleSerialModal'); if(!modal){ console.warn('applySaleSerials: modal not found'); return; }
            var idx = modal.getAttribute('data-idx') || '';
            var rowOrder = modal.getAttribute('data-row-order');
            var qty = parseInt(modal.getAttribute('data-qty') || '0', 10) || 0;
            var avail = parseInt(modal.getAttribute('data-available-count') || '0', 10) || 0;
            var row = document.querySelector('tr[data-idx="'+idx+'"]');
            if(!row){ console.warn('applySaleSerials: row not found for idx', idx); return; }
            // Prefer current row order when writing hidden inputs so server arrays align after reindexing
            var targetIdx = (rowOrder !== null && rowOrder !== '' ? rowOrder : idx);
            var picks = Array.prototype.slice.call(document.querySelectorAll('#saleSerialModal input.serial-pick:checked'));
            if(avail > 0 && qty > 0 && picks.length !== qty){
                var status = byId('saleSerialStatus');
                if(status){ status.className = 'alert alert-warning mb-2'; status.textContent = 'Select exactly '+qty+' serial(s). Selected '+picks.length+'.'; }
                return;
            }
            // clear previous hidden serial inputs within the serial container (safer than direct tr children)
            var serialBox = row.querySelector('.serial-hidden') || row;
            try{ Array.prototype.slice.call(serialBox.querySelectorAll('input[type="hidden"][data-serial]')).forEach(function(h){ h.remove(); }); }catch(e){}
            picks.forEach(function(chk){
                var idVal = chk.value || '';
                var serialNo = chk.getAttribute('data-serial-number') || '';
                var hid = document.createElement('input'); hid.type='hidden'; hid.name = 'serialId['+targetIdx+'][]'; hid.value = idVal; hid.setAttribute('data-serial','1'); serialBox.appendChild(hid);
                var hno = document.createElement('input'); hno.type='hidden'; hno.name = 'serialNumber['+targetIdx+'][]'; hno.value = serialNo; hno.setAttribute('data-serial','1'); serialBox.appendChild(hno);
            });
            reindexSaleSerialInputs();
            validateSerialsForRow(row);
            showToast && showToast('Serials applied', picks.length+' serial(s) assigned to this row', 'success');
            hideSaleSerialModal(modal);
        }catch(e){ console.error('applySaleSerials error:', e); }
    }

    function validateSerialsForRow(tr){
        if(!tr) return;
        var qty = num((tr.querySelector('.qty')||{}).value || 0);
        var holder = tr.querySelector('.serial-hidden') || tr;
        var selected = holder.querySelectorAll('input[type="hidden"][data-serial][name^="serialId"]');
        var count = selected ? selected.length : 0;
        var badge = tr.querySelector('.serial-count-badge'); if(badge) badge.textContent = count + ' selected';
        var note = tr.querySelector('.serial-note');
        var available = parseInt(tr.getAttribute('data-serial-available') || '0', 10) || 0;
        var requireSerials = available > 0;
        var mismatch = false;
        if(requireSerials && qty > 0 && count !== qty){
            mismatch = true;
            if(note){ note.style.display = 'block'; note.textContent = 'Select exactly '+qty+' serial(s).'; }
        } else {
            if(note){ note.style.display = 'none'; note.textContent = ''; }
        }
        if(mismatch){ tr.classList.add('serial-mismatch'); }
        else { tr.classList.remove('serial-mismatch'); }
    }

    function reindexSaleSerialInputs(){
        try{
            var rows = document.querySelectorAll('#productDetails tr');
            rows.forEach(function(r, idx){
                r.setAttribute('data-row-order', idx);
                var holder = r.querySelector('.serial-hidden') || r;
                Array.prototype.slice.call(holder.querySelectorAll('input[type="hidden"][data-serial]')).forEach(function(h){
                    if(h.name.indexOf('serialId[') === 0){ h.name = 'serialId['+idx+'][]'; }
                    else if(h.name.indexOf('serialNumber[') === 0){ h.name = 'serialNumber['+idx+'][]'; }
                });
            });
        }catch(e){ console.warn('reindexSaleSerialInputs failed', e); }
    }

    // Open serial modal for a sale row
    document.addEventListener('click', function(ev){
        try{
            var btn = ev.target.closest && ev.target.closest('.open-sale-serials');
            if(!btn) return;
            var idx = btn.getAttribute('data-idx');
            var pid = btn.getAttribute('data-purchase-id');
            if(!pid){ showToast && showToast('Missing purchase','Purchase id not found for this row','error'); return; }
            openSaleSerialModal(idx, pid);
        }catch(e){ console.warn('open-sale-serials handler', e); }
    });

    // Serial modal actions
    document.addEventListener('click', function(ev){
        try{
            var t = ev.target;
            if(!t) return;
            if(t.id === 'saleSerialSelectAll'){
                var modal = byId('saleSerialModal');
                var qty = parseInt(modal && modal.getAttribute('data-qty') || '0', 10) || 0;
                var picks = Array.prototype.slice.call(document.querySelectorAll('#saleSerialModal input.serial-pick'));
                var count = 0;
                picks.forEach(function(c){
                    if(!qty || count < qty){ c.checked = true; count++; }
                    else { c.checked = false; }
                });
                enforceSerialLimit();
            }
            if(t.id === 'saleSerialClear'){
                Array.prototype.slice.call(document.querySelectorAll('#saleSerialModal input.serial-pick')).forEach(function(c){ c.checked = false; c.disabled = false; });
                enforceSerialLimit();
            }
            if(t.id === 'applySaleSerials'){
                applySaleSerials();
            }
        }catch(e){ console.warn('serial modal action handler', e); }
    });

    // Enforce limit when toggling individual checkboxes
    document.addEventListener('change', function(ev){
        try{
            var t = ev.target;
            if(t && t.matches && t.matches('#saleSerialModal input.serial-pick')){
                enforceSerialLimit();
            }
        }catch(e){ console.warn('serial pick change handler', e); }
    });

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
                    reindexSaleSerialInputs();
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
                        // Block submit if serials are required but not matched to qty
                        try{ 
                            if(r.classList && r.classList.contains('serial-mismatch')){ 
                                console.error('Form blocked: row has serial-mismatch', r);
                                ev.preventDefault(); 
                                showToast && showToast('Serials missing','Select serials equal to quantity for each row','error'); 
                                return false; 
                            } 
                        }catch(e){}
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
            validateSerialsForRow(tr);
        }catch(e){ console.warn('recalcRow error', e); }
    }
    function recalcTotals(){
        try{
            // Guard: only run on Sale pages where `.totalSale` inputs exist
            var totals = Array.prototype.slice.call(document.querySelectorAll('#productDetails .totalSale'));
            if(!totals.length){ return; }
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
    // Delegated handler for 'Add as backorder' buttons in out-of-stock modal
    document.addEventListener('click', function(ev){
        try{
            var btn = ev.target.closest && ev.target.closest('.add-backorder');
            if(!btn) return;
            var purchaseId = btn.getAttribute('data-purchase-id'); if(!purchaseId) return;
            // Fetch the purchase row details then force-add as backorder by overriding stock
            var tplById = ROUTES.purchaseByIdTpl || '';
            var url = tplById ? tplById.replace('__ID__', encodeURIComponent(purchaseId)) : (ROUTES.base + '/ajax/public/purchase/'+encodeURIComponent(purchaseId)+'/details');
            url = safeFinalizeUrl(url);
            fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                .then(function(res){
                    var ct = res.headers.get('content-type') || '';
                    if(res.redirected || res.status === 302 || res.status === 401 || res.status === 403) throw new Error('unauthorized');
                    if(ct.indexOf('application/json') === -1) throw new Error('invalid-response');
                    return res.json();
                }).then(function(json){
                        try{
                            if(!json || !json.getData || !json.getData.length) { showToast && showToast('Not found','Purchase row not found','error'); return; }
                            var p = json.getData[0];
                            // mark as backorder by forcing available stock high so appendSaleRow will allow adding
                            p.currentStock = 999999;
                            p.__backorder = true;
                            // If this purchase row already exists in the table, mark it as backorder instead of adding a duplicate
                            var tbody = document.getElementById('productDetails');
                            var existing = tbody && tbody.querySelector('input[name="purchaseData[]"][value="'+(p.purchaseId || p.id || '')+'"]');
                            if(existing){
                                try{
                                    var row = existing.closest('tr');
                                    if(row){
                                        // add hidden backorder input if not present
                                        if(!row.querySelector('input[name="backorder[]"]')){
                                            var hin = document.createElement('input'); hin.type = 'hidden'; hin.name = 'backorder[]'; hin.value = (p.purchaseId || p.id || ''); row.appendChild(hin);
                                        }
                                        // add visual badge
                                        try{ var nameCell = row.children[1]; if(nameCell && nameCell.querySelector('.badge.backorder-badge')===null){ nameCell.innerHTML = nameCell.innerHTML + ' <span class="badge bg-warning text-dark backorder-badge ms-2">Backorder</span>'; } }catch(e){}
                                        row.classList.add('backorder-row');
                                        recalcTotals();
                                        // close modal
                                        try{ var modalEl = document.getElementById('outOfStockModal'); if(modalEl){ if(typeof bootstrap !== 'undefined' && bootstrap.Modal){ try{ var m = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modalEl) : null; if(!m){ m = new bootstrap.Modal(modalEl); } m.hide(); }catch(be){ try{ var m2 = new bootstrap.Modal(modalEl); m2.hide(); }catch(_){ if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } } } else if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } }catch(e){}
                                        showToast && showToast('Backorder marked','Existing row marked as backorder','success');
                                        return;
                                    }
                                }catch(e){}
                            }
                            // otherwise append a new backorder row
                            appendSaleRow(p);
                            recalcTotals();
                            // close modal if using Bootstrap 5/4 or jQuery fallback
                            try{ var modalEl = document.getElementById('outOfStockModal'); if(modalEl){ if(typeof bootstrap !== 'undefined' && bootstrap.Modal){ try{ var m = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modalEl) : null; if(!m){ m = new bootstrap.Modal(modalEl); } m.hide(); }catch(be){ try{ var m2 = new bootstrap.Modal(modalEl); m2.hide(); }catch(_){ if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } } } else if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } }catch(e){}
                            showToast && showToast('Backorder added','Item added as backorder','success');
                        }catch(e){ console.warn('backorder add failed', e); showToast && showToast('Error','Could not add backorder','error'); }
                    }).catch(function(e){ console.warn('fetch purchase by id failed', e); showToast && showToast('Error','Could not fetch purchase details','error'); });
        }catch(e){ console.warn('add-backorder handler error', e); }
    });

    // Fallback handler to open the out-of-stock modal when the View button is clicked
    document.addEventListener('click', function(ev){
        try{
            var btn = ev.target.closest && ev.target.closest('#outOfStockBtn');
            if(!btn) return;
            var modalEl = document.getElementById('outOfStockModal'); if(!modalEl) return;
                try{
                if(typeof bootstrap !== 'undefined' && bootstrap.Modal){
                    try{ var inst = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modalEl) : null; if(!inst){ inst = new bootstrap.Modal(modalEl); } inst.show(); }catch(be){ try{ var inst2 = new bootstrap.Modal(modalEl); inst2.show(); }catch(_){ if(window.jQuery && typeof window.jQuery.fn.modal === 'function'){ $('#outOfStockModal').modal('show'); } else { modalEl.classList.add('show'); modalEl.style.display = 'block'; modalEl.setAttribute('aria-hidden','false'); var backdrops = document.getElementsByClassName('modal-backdrop'); if(backdrops.length === 0){ var db = document.createElement('div'); db.className = 'modal-backdrop fade show'; document.body.appendChild(db); } } } }
                } else if(window.jQuery && typeof window.jQuery.fn.modal === 'function'){
                    $('#outOfStockModal').modal('show');
                } else {
                    // Minimal fallback: toggle classes and attributes so modal becomes visible
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                    modalEl.setAttribute('aria-hidden','false');
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    if(backdrops.length === 0){
                        var db = document.createElement('div'); db.className = 'modal-backdrop fade show'; document.body.appendChild(db);
                    }
                }
            }catch(e){ console.warn('open outOfStockModal failed', e); }
        }catch(e){ console.warn('outOfStockBtn click handler error', e); }
    });

    // Delegated handler to support modal dismiss buttons (data-bs-dismiss or data-dismiss)
    document.addEventListener('click', function(ev){
        try{
            var btn = ev.target.closest && (ev.target.closest('[data-bs-dismiss="modal"]') || ev.target.closest('[data-dismiss="modal"]'));
            if(!btn) return;
            // find the nearest modal container
            var modalEl = btn.closest && btn.closest('.modal');
            if(!modalEl) return;
            // hide via Bootstrap API when available, else jQuery, else DOM fallback
            try{
                if(typeof bootstrap !== 'undefined' && bootstrap.Modal){
                    try{ var inst = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modalEl) : null; if(!inst){ inst = new bootstrap.Modal(modalEl); } inst.hide(); }catch(be){ try{ var inst2 = new bootstrap.Modal(modalEl); inst2.hide(); }catch(_){ if(window.jQuery && typeof window.jQuery.fn.modal === 'function'){ $(modalEl).modal('hide'); } else { modalEl.classList.remove('show'); modalEl.style.display = 'none'; var backdrops = document.getElementsByClassName('modal-backdrop'); if(backdrops.length){ backdrops[0].parentNode.removeChild(backdrops[0]); } } } }
                } else if(window.jQuery && typeof window.jQuery.fn.modal === 'function'){
                    $(modalEl).modal('hide');
                } else {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    var backdrops = document.getElementsByClassName('modal-backdrop');
                    if(backdrops.length){ backdrops[0].parentNode.removeChild(backdrops[0]); }
                }
            }catch(e){ console.warn('modal dismiss handler failed', e); }
        }catch(e){ console.warn('modal dismiss delegated handler error', e); }
    });
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
