// Sale page behaviors: enable product select after customer selection, fetch purchase details,
// and append sale rows with proper input names expected by saveSale controller.
(function(){
    function byId(id){ return document.getElementById(id); }
    function num(v){ v = (v==null? '': String(v)); v = v.replace(/,/g,''); var n = Number(v); return isNaN(n)? 0 : n; }

    // Laravel-generated route seeds are authoritative for both root and subdirectory deployments.
    function deriveRoutes(){
        var seed = document.getElementById('rn-route-seeds');
        if(!seed) return {};
        var productsTpl = seed.getAttribute('data-products-template') || '';
        var previousDueTpl = seed.getAttribute('data-previous-due-template') || '';
        var categoriesUrl = seed.getAttribute('data-categories-url') || '';
        var brandsUrl = seed.getAttribute('data-brands-url') || '';
        var categoryProductsTpl = seed.getAttribute('data-category-products-template') || '';
        var purchaseTpl = seed.getAttribute('data-purchase-template') || '';
        var purchaseByIdTpl = seed.getAttribute('data-purchase-by-id-template') || '';
        var purchaseSerialTpl = seed.getAttribute('data-purchase-serials-template') || '';
        var productEditTpl = seed.getAttribute('data-product-edit-template') || '';
        return { productsTpl: productsTpl, previousDueTpl: previousDueTpl, categoriesUrl: categoriesUrl, brandsUrl: brandsUrl, categoryProductsTpl: categoryProductsTpl, purchaseTpl: purchaseTpl, purchaseByIdTpl: purchaseByIdTpl, purchaseSerialTpl: purchaseSerialTpl, productEditTpl: productEditTpl };
    }
    function routeForId(template, id){
        return template ? safeFinalizeUrl(template.replace('__ID__', encodeURIComponent(String(id)))) : '';
    }
    function withSelectedBusiness(url){
        var form = byId('saveSaleForm');
        var business = form && form.querySelector('[name="businessId"]');
        if(!url || !business || !String(business.value || '').trim()) return url;
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'businessId=' + encodeURIComponent(String(business.value).trim());
    }
    var ROUTES = {}; // will populate on DOMContentLoaded
    var SALE_SUBMITTING = false;
    var SALE_ROW_COUNTER = 0; // per-row counter to track serial selections
    var SALE_CUSTOMER_VERSION = 0;
    var SALE_PRODUCT_REQUEST = 0;
    var SALE_PRODUCT_BATCHES = Object.create(null);
    var SALE_PRODUCT_ALLOCATED = Object.create(null);
    var SALE_PRODUCT_SALE_PRICE = Object.create(null);
    var SALE_REALLOCATING = false;
    var SALE_PROPAGATING_PRICE = false;
    function customerReady(){
        var customer = byId('customerName');
        return !!(customer && customer.value && customer.dataset.customerReady === '1');
    }
    function customerCanSubmit(){
        var customer = byId('customerName');
        return customerReady() && customer.dataset.dueReady === '1';
    }
    function refreshCustomerGate(){
        var customer = byId('customerName');
        if(!customer) return;
        var productsReady = !!customer.value && customer.dataset.productsReady === '1';
        var ready = productsReady && customer.dataset.dueReady === '1';
        // Product entry depends on server validation of the selected customer's
        // business-scoped catalog. The separate balance lookup remains a save gate.
        customer.dataset.customerReady = productsReady ? '1' : '0';
        ['saleProductSearch','barcodeInput','searchBarcodeBtn','brandSelect','categorySelect','productName','outOfStockBtn'].forEach(function(id){ var el=byId(id); if(el) el.disabled=!productsReady; });
        var note=byId('saleCustomerGateMessage');
        if(note){
            var message = !customer.value ? 'sale_customer_first_instruction'
                : !productsReady ? (customer.dataset.productLookupError === '1' ? 'sale_customer_lookup_failed' : 'sale_customer_loading')
                : !ready ? (customer.dataset.dueLookupError === '1' ? 'sale_customer_balance_failed' : 'sale_customer_balance_loading')
                : '';
            note.textContent = message ? window.RNTranslate(message) : '';
        }
        toggleSaveButton();
    }

    // Laravel-generated route URLs are absolute or root-relative and already contain
    // the correct deployment path.
    function safeFinalizeUrl(url){
        try{
            if(!url) return url;
            var s = String(url).trim();
            if(!s) return s;
            // if absolute url or protocol-relative or root-relative, trust it
            if(s.indexOf('http://') === 0 || s.indexOf('https://') === 0 || s.indexOf('///') === 0 || s.charAt(0) === '/') return s;
            return s;
        }catch(e){ return url; }
    }

    // Enable product dropdown when customer selected and load products via AJAX for that customer
    document.addEventListener('DOMContentLoaded', function(){
        ROUTES = deriveRoutes();
        var customerSel = byId('customerName');
        var productSel = document.querySelector('.js-sale-product-select') || byId('productName');
        var brandSel = byId('brandSelect');
        var categorySel = byId('categorySelect');
        if(!productSel) return;
        productSel.disabled = true;
        if(categorySel) categorySel.disabled = true;
        if(brandSel) brandSel.disabled = true;
        refreshCustomerGate();
        // Keep the form action on Laravel's generated route.
        try{
            var form = byId('saveSaleForm');
            if(form){
                var tmpl = form.getAttribute('data-action-template') || '';
                if(tmpl){
                    var url = safeFinalizeUrl(tmpl);
                    form.setAttribute('action', url);
                    if(window.__SALEDEBUG) console.debug('[SALE] Form action set to:', url);
                }
            }
        }catch(e){ }
        // These billing controls are static fields; bind one authoritative handler each.
        try{
            var dam = byId('discountAmount'); if(dam) dam.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
            var addc = byId('additionalChargeAmount'); if(addc) addc.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
            var paid = byId('paidAmount'); if(paid) paid.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
        }catch(e){ }
        function addSalesQuery(url, key, value){
            if(value === null || value === undefined || String(value) === '') return url;
            return url + (url.indexOf('?') === -1 ? '?' : '&') + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
        }
        function loadSalesCategories(requestVersion, customerVersion){
            if(!categorySel) return;
            var url = withSelectedBusiness(safeFinalizeUrl(ROUTES.categoriesUrl || ''));
            url = addSalesQuery(url, 'brandId', brandSel && brandSel.value);
            if(!url) return;
            fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'})
              .then(function(response){ if(!response.ok) throw new Error('Category lookup failed'); return response.json(); })
              .then(function(json){
                if(requestVersion !== SALE_PRODUCT_REQUEST || customerVersion !== SALE_CUSTOMER_VERSION) return;
                categorySel.innerHTML = (json && json.data) || '<option value="">'+window.RNTranslate('sales.all_categories')+'</option>';
              }).catch(function(){ /* Filter remains usable with the all-category option. */ });
        }
        function loadSalesProducts(requestVersion, customerVersion){
            var cid = customerSel && customerSel.value;
            if(!cid) return;
            productSel.disabled = true;
            productSel.innerHTML = '<option value="">'+window.RNTranslate('common.select')+'</option>';
            var tpl = ROUTES.productsTpl || customerSel.getAttribute('data-products-url') || '';
            var url = withSelectedBusiness(routeForId(tpl, cid));
            url = addSalesQuery(url, 'brandId', brandSel && brandSel.value);
            url = addSalesQuery(url, 'categoryId', categorySel && categorySel.value);
            if(!url) return;
            fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'})
              .then(function(response){ if(!response.ok) throw new Error('Product lookup failed'); return response.json(); })
              .then(function(json){
                if(requestVersion !== SALE_PRODUCT_REQUEST || customerVersion !== SALE_CUSTOMER_VERSION || !customerReady()) return;
                productSel.innerHTML = (json && json.data) || '<option value="">'+window.RNTranslate('common.select')+'</option>';
                productSel.value = '';
                productSel.disabled = false;
              }).catch(function(){
                if(requestVersion !== SALE_PRODUCT_REQUEST || customerVersion !== SALE_CUSTOMER_VERSION) return;
                productSel.innerHTML = '<option value="">'+window.RNTranslate('common.select')+'</option>';
                var feedback = byId('saleSearchFeedback');
                if(feedback) feedback.textContent = window.RNTranslate('sale_search_error');
              });
        }
        // Autofocus the Add Customer modal's name field when modal shows
        try{
            var custModal = document.getElementById('customerModal');
            if(custModal){
                if(window.bootstrap && typeof custModal.addEventListener === 'function'){
                    custModal.addEventListener('shown.bs.modal', function(){ try{ var fn = byId('fullName'); if(fn) fn.focus(); }catch(e){} });
                } else if(window.jQuery){
                    try{ window.jQuery(custModal).on('shown.bs.modal', function(){ try{ window.jQuery('#fullName').trigger('focus'); }catch(e){} }); }catch(e){}
                } else {
                    var addCustomerBtn = document.querySelector('button.rn-sale-new-customer');
                    if(addCustomerBtn){ addCustomerBtn.addEventListener('click', function(){ setTimeout(function(){ try{ var fn = byId('fullName'); if(fn) fn.focus(); }catch(e){} }, 450); }); }
                }
            }
        }catch(e){}
                if(customerSel){
            customerSel.addEventListener('change', function(){
                var cid = this.value;
                var version = ++SALE_CUSTOMER_VERSION; ++SALE_PRODUCT_REQUEST;
                if(brandSel) brandSel.value='';
                if(categorySel) categorySel.value='';
                customerSel.dataset.selectionVersion=String(version);
                customerSel.dataset.productsReady='0'; customerSel.dataset.dueReady='0'; customerSel.dataset.productLookupError='0'; customerSel.dataset.dueLookupError='0';
                if(byId('prevDue')) byId('prevDue').value=0;
                if(byId('prevDueDisplay')) byId('prevDueDisplay').textContent=window.RNTranslate('previous_due_value',{amount:'0.00'});
                recalcTotals(); refreshCustomerGate();
                if(!cid){
                    productSel.innerHTML='<option value="">'+window.RNTranslate('common.select')+'</option>';
                    if(brandSel){ brandSel.innerHTML='<option value="">'+window.RNTranslate('sales.all_brands')+'</option>'; brandSel.value=''; }
                    if(categorySel){ categorySel.innerHTML='<option value="">'+window.RNTranslate('sales.all_categories')+'</option>'; categorySel.value=''; }
                    return;
                }
                        // Fetch and display customer's previous due
                        try{
                            var prevTpl = withSelectedBusiness(routeForId(ROUTES.previousDueTpl, cid));
                            if(!prevTpl) throw new Error('Previous balance route is not configured');
                            fetch(prevTpl, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                                .then(function(res){ if(res.status >= 400) throw new Error('prevDue fetch failed'); return res.json(); })
                                .then(function(json){ if(version!==SALE_CUSTOMER_VERSION) return; if(!json || json.status!=='ok') throw new Error('Invalid customer balance'); var v=json.prevDue || '0.00'; if(byId('prevDue')) byId('prevDue').value=v; if(byId('prevDueDisplay')) byId('prevDueDisplay').textContent=window.RNTranslate('previous_due_value',{amount:v}); customerSel.dataset.dueReady='1'; recalcTotals(); refreshCustomerGate(); })
                                .catch(function(e){ if(version!==SALE_CUSTOMER_VERSION) return; customerSel.dataset.dueLookupError='1'; refreshCustomerGate(); });
                        }catch(e){ customerSel.dataset.dueLookupError='1'; refreshCustomerGate(); }
                if(!cid){ productSel.innerHTML = ('<option value="">' + window.RNTranslate('common.select') + '</option>'); productSel.disabled = true; if(brandSel){ brandSel.innerHTML='<option value="">'+window.RNTranslate('sales.all_brands')+'</option>'; brandSel.disabled=true; } if(categorySel){ categorySel.innerHTML = ('<option value="">' + window.RNTranslate('sales.all_categories') + '</option>'); categorySel.disabled = true; } return; }
                var tpl = ROUTES.productsTpl || customerSel.getAttribute('data-products-url') || '';
                    var url = withSelectedBusiness(routeForId(tpl, cid));
                    if(!url){ customerSel.dataset.productLookupError='1'; refreshCustomerGate(); return; }
                    if(window.__SALEDEBUG) console.debug('[SALE] Products final URL:', url);
                    // Guard: do not accidentally call auth routes which may destroy session
                    try{ var low = String(url).toLowerCase(); if(low.indexOf('/logout')!==-1 || low.indexOf('/login')!==-1){ console.warn('[SALE] Aborting AJAX to auth route:', url); showToast && showToast(window.RNTranslate('common.client_ajax_blocked'),window.RNTranslate('common.client_invalid_products_url'),'error'); customerSel.dataset.productLookupError='1'; refreshCustomerGate(); return; } }catch(e){}
                    fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                    .then(function(res){
                        // If redirected to login or unauthorized, abort and notify
                        if(!res.ok || res.redirected){
                            try{ showToast(window.RNTranslate('common.client_products_fetch_blocked'),window.RNTranslate('common.client_authentication_required_or_redirected'),'error'); }catch(_){}
                            throw new Error('Products fetch redirected or unauthorized');
                        }
                        var ct = res.headers.get('content-type') || '';
                        if(ct.indexOf('application/json') === -1){
                            // often an HTML login page or 404; bail out
                            try{ showToast(window.RNTranslate('common.client_products_fetch_invalid'),window.RNTranslate('common.client_server_returned_non_json_response'),'error'); }catch(_){}
                            throw new Error('Non-JSON response');
                        }
                        return res.json();
                    })
                    .then(function(data){
                        if(version!==SALE_CUSTOMER_VERSION || customerSel.value!==cid) return;
                        try{
                            productSel.innerHTML = (data && data.data) ? data.data : ('<option value="">' + window.RNTranslate('common.select') + '</option>');
                            productSel.disabled = false;
                            customerSel.dataset.productsReady='1'; refreshCustomerGate();
                            // Brand and category options are derived from this business's saleable catalog.
                            if(brandSel){
                                var bUrl = withSelectedBusiness(safeFinalizeUrl(ROUTES.brandsUrl || ''));
                                fetch(bUrl, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'})
                                  .then(function(res){ if(!res.ok) throw new Error('Brand lookup failed'); return res.json(); })
                                  .then(function(json){
                                    if(version!==SALE_CUSTOMER_VERSION) return;
                                    brandSel.innerHTML=(json && json.data) || '<option value="">'+window.RNTranslate('sales.all_brands')+'</option>';
                                    loadSalesCategories(SALE_PRODUCT_REQUEST, version);
                                  }).catch(function(){ if(version===SALE_CUSTOMER_VERSION) loadSalesCategories(SALE_PRODUCT_REQUEST, version); });
                            } else loadSalesCategories(SALE_PRODUCT_REQUEST, version);
                            // If there are out-of-stock items returned, populate modal data and show note/button
                            try{
                                var outNote = byId('outOfStockNote');
                                var outBtn = byId('outOfStockBtn');
                                var outList = byId('outOfStockList');
                                var outArr = (data && data.outOfStock) ? data.outOfStock : [];
                                var outProducts = Object.create(null);
                                outArr.forEach(function(item){
                                    var key=String(item.productId || item.productName || 'unknown');
                                    if(!outProducts[key]) outProducts[key]={productId:item.productId,productName:item.productName || '',currentStock:0,purchases:[]};
                                    outProducts[key].currentStock+=Math.max(0,parseInt(item.currentStock || 0,10));
                                    outProducts[key].purchases.push(item);
                                });
                                var outProductList=Object.keys(outProducts).map(function(key){return outProducts[key];});
                                if(outBtn){
                                    try{ var cnt = outProductList.length; outBtn.innerText = window.RNTranslate('sale_out_of_stock',{count:cnt}); }catch(e){}
                                    try{ outBtn.title = outProductList.length ? window.RNTranslate('sale_out_of_stock',{count:outProductList.length}) : window.RNTranslate('sale_no_out_of_stock'); }catch(e){}
                                    try{ outBtn.style.display = outProductList.length ? 'inline-block' : 'none'; }catch(e){}
                                }
                                if(outNote){ try{ outNote.style.display = 'none'; outNote.textContent = ''; }catch(e){} }
                                if(outList){
                                    if(outProductList.length === 0){ outList.textContent = window.RNTranslate('sale_no_out_of_stock'); outList.className='text-muted'; }
                                    else {
                                        outList.className='list-group'; outList.replaceChildren();
                                        outProductList.forEach(function(product){
                                            var item=document.createElement('div'); item.className='list-group-item d-flex justify-content-between align-items-center';
                                            var details=document.createElement('div'),name=document.createElement('strong'),stock=document.createElement('span');
                                            name.textContent=product.productName; stock.className='badge bg-secondary ms-2'; stock.textContent=window.RNTranslate('common.stock')+': '+product.currentStock;
                                            details.appendChild(name); details.appendChild(stock); item.appendChild(details);
                                            var actions=document.createElement('div');
                                            if(product.productId){ var link=document.createElement('a'); link.className='btn btn-sm btn-outline-primary me-2'; link.href=routeForId(ROUTES.productEditTpl,product.productId); link.target='_blank'; link.textContent=window.RNTranslate('view'); actions.appendChild(link); }
                                            product.purchases.forEach(function(purchase){ var backorder=document.createElement('button'); backorder.type='button'; backorder.hidden=true; backorder.className='btn btn-sm btn-outline-success add-backorder'; backorder.dataset.purchaseId=purchase.purchaseId || ''; backorder.textContent=window.RNTranslate('sale_add_backorder'); actions.appendChild(backorder); });
                                            item.appendChild(actions); outList.appendChild(item);
                                        });
                                    }
                                }
                            }catch(e){}
                        }catch(e){ customerSel.dataset.productLookupError='1'; refreshCustomerGate(); }
                    }).catch(function(err){ if(version!==SALE_CUSTOMER_VERSION) return; customerSel.dataset.productLookupError='1'; refreshCustomerGate(); });
            });
        }

        // When a product is selected, fetch purchase rows and prefill first one; allow adding multiple via table
        productSel.addEventListener('change', function(){
            if(!customerReady()) return;
            var customerVersion=SALE_CUSTOMER_VERSION;
            var productRequest=++SALE_PRODUCT_REQUEST;
            var pid = this.value; if(!pid) return;
            var tpl2 = ROUTES.purchaseTpl || productSel.getAttribute('data-purchase-url') || '';
            var url2 = withSelectedBusiness(routeForId(tpl2, pid));
            if(!url2){ showToast(window.RNTranslate('common.client_product_details_blocked'),window.RNTranslate('common.client_invalid_products_url'),'error'); return; }
            if(window.__SALEDEBUG) console.debug('[SALE] Fetch product purchase batches URL:', url2);
            try{ var low2 = String(url2).toLowerCase(); if(low2.indexOf('/logout')!==-1 || low2.indexOf('/login')!==-1){ console.warn('[SALE] Aborting AJAX to auth route:', url2); showToast && showToast(window.RNTranslate('common.client_ajax_blocked'),window.RNTranslate('common.client_invalid_product_details_url'),'error'); return; } }catch(e){}
            fetch(url2, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                        .then(function(res){
                            if(customerVersion!==SALE_CUSTOMER_VERSION || !customerReady()) return;
                            if(!res.ok || res.redirected){
                                try{ showToast(window.RNTranslate('common.client_product_details_blocked'),window.RNTranslate('common.client_authentication_required_or_redirected'),'error'); }catch(_){}
                                throw new Error('Purchase details fetch redirected/unauthorized');
                            }
                            var ct = res.headers.get('content-type') || '';
                            if(ct.indexOf('application/json') === -1){
                                try{ showToast(window.RNTranslate('common.client_product_details_invalid'),window.RNTranslate('common.client_server_returned_non_json_response'),'error'); }catch(_){}
                                throw new Error('Non-JSON response for purchase details');
                            }
                            return res.json();
                        })
                        .then(function(res){
                            if(productRequest!==SALE_PRODUCT_REQUEST || customerVersion!==SALE_CUSTOMER_VERSION || !customerReady()) return;
                            try{
                                if(!res || !res.getData || !res.getData.length){ showToast(window.RNTranslate('common.client_no_purchase_found'),window.RNTranslate('common.client_this_product_has_no_purchase_rows'),'warning'); return; }
                                var available = res.getData.find(function(batch){
                                    var stock = Math.max(0, parseInt(batch.currentStock || 0, 10));
                                    var allocated = 0;
                                    document.querySelectorAll('#saleAllocationRows tr[data-purchase-id="'+batch.purchaseId+'"] .qty').forEach(function(input){ allocated += Math.max(0, parseInt(input.value || 0, 10)); });
                                    return stock > allocated;
                                });
                                if(!available){
                                    showToast(window.RNTranslate('common.client_out_of_stock'),window.RNTranslate('sale_out_of_stock_product',{product:res.productName || ''}),'error');
                                    return;
                                }
                                SALE_PRODUCT_BATCHES[String(pid)] = res.getData;
                                if(!Object.prototype.hasOwnProperty.call(SALE_PRODUCT_SALE_PRICE, String(pid))){
                                    SALE_PRODUCT_SALE_PRICE[String(pid)] = String(res.defaultSalePrice != null ? res.defaultSalePrice : (res.getData[res.getData.length-1].salePriceExVat || 0));
                                }
                                var requested = (parseInt(SALE_PRODUCT_ALLOCATED[String(pid)] || 0, 10) || 0) + 1;
                                if(allocateSaleProduct(String(pid), requested)){
                                    productSel.value = '';
                                }
                                recalcTotals();
                            }catch(e){ console.warn('sale product details parse', e); }
                        }).catch(function(e){ console.warn('getSaleProductDetails failed', e); });
        });

        function reloadFilteredSaleProducts(){
            if(!customerReady()) return;
            var requestVersion=++SALE_PRODUCT_REQUEST, customerVersion=SALE_CUSTOMER_VERSION;
            loadSalesProducts(requestVersion, customerVersion);
        }
        if(brandSel){
            brandSel.addEventListener('change', function(){
                if(categorySel){ categorySel.value=''; categorySel.innerHTML='<option value="">'+window.RNTranslate('sales.all_categories')+'</option>'; }
                if(!customerReady()) return;
                var requestVersion=++SALE_PRODUCT_REQUEST, customerVersion=SALE_CUSTOMER_VERSION;
                loadSalesCategories(requestVersion, customerVersion);
                loadSalesProducts(requestVersion, customerVersion);
            });
        }
        if(categorySel){ categorySel.addEventListener('change', reloadFilteredSaleProducts); }
    });

    function allocateSaleProduct(productId, requestedTotal, preferredByPurchase){
        var batches = SALE_PRODUCT_BATCHES[String(productId)] || [];
        var tbody = byId('saleAllocationRows');
        if(!tbody) return false;
        var existingGroupRows=Array.prototype.slice.call(tbody.querySelectorAll('tr[data-product-group="'+productId+'"][data-purchase-id]'));
        var backorderRows=existingGroupRows.filter(function(row){return row.classList.contains('backorder-row');});
        var backorderQty=backorderRows.reduce(function(sum,row){return sum+(parseInt(row.querySelector('.qty').value||'0',10)||0);},0);
        var regularRequested=requestedTotal-backorderQty;
        if(regularRequested<0){ showToast && showToast(window.RNTranslate('common.client_invalid_quantity'),window.RNTranslate('sale_quantity_below_backorder'),'error'); return false; }
        if(!batches.length && regularRequested>0) return false;
        var groupSalePrice = SALE_PRODUCT_SALE_PRICE[String(productId)];
        if(groupSalePrice === undefined) groupSalePrice = String(batches.length ? (batches[batches.length-1].salePriceExVat || 0) : 0);
        var totalAvailable = batches.reduce(function(total, batch){ return total + Math.max(0, parseInt(batch.currentStock || 0, 10)); }, 0);
        if(requestedTotal < 0 || regularRequested > totalAvailable){
            showToast && showToast(window.RNTranslate('common.client_low_stock'),window.RNTranslate('sale_stock_limit',{stock:totalAvailable}),'error');
            return false;
        }

        var allocations = [];
        if(preferredByPurchase && Object.keys(preferredByPurchase).length){
            var preferredTotal = Object.keys(preferredByPurchase).reduce(function(sum,id){ return sum + (parseInt(preferredByPurchase[id] || 0,10)||0); },0);
            if(preferredTotal !== regularRequested) return false;
            for(var i=0;i<batches.length;i++){
                var preferredQty=parseInt(preferredByPurchase[String(batches[i].purchaseId)] || '0',10)||0;
                if(preferredQty > 0){ if(preferredQty > Math.max(0,parseInt(batches[i].currentStock||0,10))) return false; allocations.push({batch:batches[i],quantity:preferredQty}); }
            }
            if(allocations.reduce(function(sum,item){return sum+item.quantity;},0)!==regularRequested) return false;
        } else {
            var remaining = regularRequested;
            batches.forEach(function(batch){
                var stock = Math.max(0, parseInt(batch.currentStock || 0, 10));
                var quantity = Math.min(stock, remaining);
                if(quantity > 0){ allocations.push({batch:batch, quantity:quantity}); remaining -= quantity; }
            });
            if(remaining > 0) return false;
        }

        var firstGroupRow = byId('productDetails') && byId('productDetails').querySelector('tr[data-product-group="'+productId+'"]');
        var warrantySource = firstGroupRow ? firstGroupRow.querySelector('.warrantyDays') : null;
        var inheritedWarranty = warrantySource ? warrantySource.value : '';
        SALE_REALLOCATING = true;
        var retained = Object.create(null);
        backorderRows.forEach(function(row){ retained[String(row.getAttribute('data-purchase-id'))]=true; });
        allocations.forEach(function(allocation){
            var batch = allocation.batch;
            batch.productId = parseInt(productId, 10);
            batch.__groupSalePrice = groupSalePrice;
            batch.productName = batch.productName || (byId('productName').selectedOptions[0] || {}).textContent || '';
            var existing = tbody.querySelector('tr[data-product-group="'+productId+'"][data-purchase-id="'+batch.purchaseId+'"]');
            if(existing){
                retained[String(batch.purchaseId)] = true;
                var qtyInput = existing.querySelector('.qty');
                qtyInput.value = String(allocation.quantity);
                qtyInput.dataset.lastValidQty = String(allocation.quantity);
                existing.querySelector('.salePrice').value = groupSalePrice;
                recalcRow(existing, batch.currentStock);
                validateSerialsForRow(existing);
            } else {
                batch.__productGroupId = String(productId);
                appendSaleRow(batch, allocation.quantity);
                var created = tbody.querySelector('tr[data-product-group="'+productId+'"][data-purchase-id="'+batch.purchaseId+'"]');
                if(created){
                    retained[String(batch.purchaseId)] = true;
                    var createdQty = created.querySelector('.qty');
                    createdQty.dataset.lastValidQty = String(allocation.quantity);
                    var warranty = created.querySelector('.warrantyDays');
                    if(warranty) warranty.value = inheritedWarranty;
                }
            }
        });
        tbody.querySelectorAll('tr[data-product-group="'+productId+'"]').forEach(function(row){
            if(!retained[String(row.getAttribute('data-purchase-id'))]) row.remove();
        });
        SALE_REALLOCATING = false;
        SALE_PRODUCT_ALLOCATED[String(productId)] = requestedTotal;
        reindexSaleSerialInputs();
        updateSaleProductRow(productId);
        recalcTotals();
        toggleSaveButton();
        return true;
    }

    function ensureSaleProductRow(product, productId){
        var tbody = byId('productDetails');
        if(!tbody || !productId) return null;
        var row = tbody.querySelector('tr[data-product-group="'+productId+'"][data-product-summary]');
        if(row) return row;
        row = document.createElement('tr');
        row.className = 'rn-sale-product-row';
        row.dataset.productGroup = String(productId);
        row.dataset.productSummary = '1';
        row.dataset.serialAvailable = '0';
        row.innerHTML = `
            <td data-label="${window.RNTranslate('product')}"><strong class="rn-sale-product-name"></strong></td>
            <td data-label="${window.RNTranslate('available_stock')}"><span class="rn-product-stock badge bg-light text-dark"></span></td>
            <td data-label="${window.RNTranslate('serials')}"><button type="button" class="btn btn-sm btn-outline-primary open-sale-serials">${window.RNTranslate('serials')}</button><span class="badge bg-secondary ms-2 serial-count-badge">${window.RNTranslate('selected_count',{count:0})}</span><div class="small text-danger serial-note" hidden></div><div class="small text-muted serial-policy-note"></div></td>
            <td data-label="${window.RNTranslate('warranty_days')}"><input type="number" min="0" step="1" class="form-control warrantyDays" inputmode="numeric" aria-label="${window.RNTranslate('warranty_days')}"></td>
            <td data-label="${window.RNTranslate('quantity')}"><div class="rn-sale-quantity"><button type="button" data-sale-qty="-1" aria-label="${window.RNTranslate('sale_decrease_quantity')}" class="btn btn-outline-secondary">−</button><input type="number" class="form-control qty" min="1" step="1" value="1" inputmode="numeric" aria-label="${window.RNTranslate('quantity')}"><button type="button" data-sale-qty="1" aria-label="${window.RNTranslate('sale_increase_quantity')}" class="btn btn-outline-secondary">+</button></div><div class="small text-danger stock-note" hidden></div></td>
            <td data-label="${window.RNTranslate('sale_price')}"><input type="number" class="form-control salePrice" min="0" max="9999999999.99" step="0.01" inputmode="decimal" aria-label="${window.RNTranslate('sale_price')}" title="${window.RNTranslate('sale_price_editable')}"></td>
            <td data-label="${window.RNTranslate('line_total')}"><strong class="rn-sale-line-total">0.00</strong></td>
            <td data-label="${window.RNTranslate('remove')}"><button type="button" class="btn btn-sm btn-outline-danger remove-product-row" aria-label="${window.RNTranslate('remove')}">${window.RNTranslate('remove')}</button></td>`;
        var name = product.productName || product.name || (byId('productName') && byId('productName').selectedOptions[0] || {}).textContent || '';
        row.querySelector('.rn-sale-product-name').textContent = String(name).trim();
        row.querySelector('.open-sale-serials').dataset.productId = String(productId);
        tbody.appendChild(row);
        row.querySelector('.qty').addEventListener('input', function(){
            if(!/^\d+$/.test(this.value) || parseInt(this.value,10)<1) { validateSaleProductRow(row); toggleSaveButton(); return; }
            if(!allocateSaleProduct(String(productId), parseInt(this.value,10))){ this.value=this.dataset.lastValidQty||'1'; validateSaleProductRow(row); }
            else this.dataset.lastValidQty=this.value;
        });
        row.querySelector('.salePrice').addEventListener('input', function(){ updateProductGroupSalePrice(String(productId), this.value); });
        row.querySelector('.warrantyDays').addEventListener('input', function(){
            document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"] .warrantyDays').forEach(function(input){ input.value = row.querySelector('.warrantyDays').value; });
        });
        row.querySelector('.remove-product-row').addEventListener('click', function(event){
            event.preventDefault(); event.stopPropagation();
            window.RNConfirmItemRemoval(row, function(){
                document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"]').forEach(function(allocation){ allocation.remove(); });
                row.remove(); delete SALE_PRODUCT_ALLOCATED[String(productId)]; delete SALE_PRODUCT_BATCHES[String(productId)]; delete SALE_PRODUCT_SALE_PRICE[String(productId)];
                reindexSaleSerialInputs(); recalcTotals(); toggleSaveButton();
            });
        });
        return row;
    }

    function updateSaleProductRow(productId){
        var row = byId('productDetails') && byId('productDetails').querySelector('tr[data-product-group="'+productId+'"][data-product-summary]');
        if(!row) return;
        var batches = SALE_PRODUCT_BATCHES[String(productId)] || [];
        var stock = batches.reduce(function(sum,b){ return sum + Math.max(0,parseInt(b.currentStock || 0,10)); },0);
        row.dataset.currentStock = String(stock);
        row.querySelector('.rn-product-stock').textContent = String(stock);
        var allocated = parseInt(SALE_PRODUCT_ALLOCATED[String(productId)] || 0,10) || 0;
        var price = SALE_PRODUCT_SALE_PRICE[String(productId)] || '';
        var qtyInput = row.querySelector('.qty');
        if(qtyInput && document.activeElement !== qtyInput) qtyInput.value = String(allocated || 1);
        if(qtyInput && allocated > 0) qtyInput.dataset.lastValidQty=String(allocated);
        row.querySelector('.salePrice').value = String(price);
        var firstAllocation = document.querySelector('#saleAllocationRows tr[data-product-group="'+productId+'"]');
        if(firstAllocation && row.querySelector('.warrantyDays')) row.querySelector('.warrantyDays').value = firstAllocation.querySelector('.warrantyDays').value || '';
        var total = Number((num(price) * allocated).toFixed(2));
        row.querySelector('.rn-sale-line-total').textContent = total.toFixed(2);
        var allocations = Array.prototype.slice.call(document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"][data-purchase-id]'));
        var count = allocations.reduce(function(sum,a){ return sum + a.querySelectorAll('input[data-serial][name^="serialId"]').length; },0);
        row.querySelector('.serial-count-badge').textContent = window.RNTranslate('selected_count',{count:count});
        var fulfilledAllocations = allocations.filter(function(a){ return !a.classList.contains('backorder-row'); });
        var required = fulfilledAllocations.some(function(a){ return a.dataset.serialRequired !== '0'; });
        var serialized = fulfilledAllocations.some(function(a){ return a.dataset.serialized === '1'; });
        var policyError = fulfilledAllocations.some(function(a){ return a.dataset.serialPolicyError === '1'; });
        row.dataset.serialRequired = required ? '1' : '0'; row.dataset.serialized = serialized ? '1' : '0'; row.dataset.serialPolicyError = policyError ? '1' : '0';
        row.dataset.serialAvailable = fulfilledAllocations.reduce(function(sum,a){ return sum + (parseInt(a.dataset.serialAvailable || '0',10)||0); },0);
        row.querySelector('.open-sale-serials').hidden = !serialized;
        row.querySelector('.serial-count-badge').hidden = !serialized;
        var note = row.querySelector('.serial-policy-note');
        note.textContent = serialized && !required ? window.RNTranslate('optional_leave_empty_to_allocate_available_serials_automatically') : '';
        validateSaleProductRow(row);
    }

    function updateProductGroupSalePrice(productId, value){
        SALE_PRODUCT_SALE_PRICE[String(productId)] = String(value);
        document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"]')
            .forEach(function(row){
                var input = row.querySelector('.salePrice');
                if(input) input.value = String(value);
                recalcRow(row, parseInt(row.getAttribute('data-current-stock') || '0', 10));
                validateSerialsForRow(row);
            });
        var summary = byId('productDetails') && byId('productDetails').querySelector('tr[data-product-group="'+productId+'"][data-product-summary]');
        if(summary){
            summary.querySelector('.salePrice').value = String(value);
            var qty=parseInt(summary.querySelector('.qty').value||'0',10)||0;
            summary.querySelector('.rn-sale-line-total').textContent=(num(value)*qty).toFixed(2);
        }
        recalcTotals();
    }

    function validateSaleProductRow(row){
        if(!row) return;
        var qty = parseInt(row.querySelector('.qty').value || '0',10)||0;
        var quantityInput=row.querySelector('.qty');
        var invalidQuantity=!/^\d+$/.test(quantityInput.value) || qty<1;
        if(invalidQuantity) row.classList.add('quantity-invalid'); else row.classList.remove('quantity-invalid');
        var backorderQty=Array.prototype.slice.call(document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+row.dataset.productGroup+'"].backorder-row .qty')).reduce(function(sum,input){return sum+(parseInt(input.value||'0',10)||0);},0);
        var fulfilledQty=Math.max(0,qty-backorderQty);
        var selected = document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+row.dataset.productGroup+'"] input[data-serial][name^="serialId"]');
        var serialCount = selected.length;
        var serialized = row.dataset.serialized === '1';
        var required = row.dataset.serialRequired !== '0';
        var mismatch = row.dataset.serialPolicyError === '1' || (serialized && fulfilledQty > 0 && serialCount !== fulfilledQty && (required || serialCount > 0));
        var note = row.querySelector('.serial-note');
        if(note){ note.hidden = !mismatch; note.textContent = mismatch ? (row.dataset.serialPolicyError === '1' ? window.RNTranslate('unable_to_verify_serial_policy_retry_selection_before_saving') : window.RNTranslate('select_exact',{qty:qty})) : ''; }
        if(mismatch) row.classList.add('serial-mismatch'); else row.classList.remove('serial-mismatch');
        var stock = parseInt(row.dataset.currentStock || '0',10)||0;
        var stockNote = row.querySelector('.stock-note');
        if(fulfilledQty > stock){ row.classList.add('stock-exceeded'); if(stockNote){ stockNote.hidden=false; stockNote.textContent=window.RNTranslate('quantity_exceeds',{stock:stock}); } }
        else { row.classList.remove('stock-exceeded'); if(stockNote){ stockNote.hidden=true; stockNote.textContent=''; } }
        try{ updateSerialMismatchIndicator(); }catch(e){}
    }

    function appendSaleRow(p, requestedQty){
        if(!customerReady()) return;
        try{
            var tbody = byId('saleAllocationRows'); if(!tbody) return;
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
                            var newQty = requestedQty === undefined
                                ? (parseInt(qtyEl.value||0,10) || 0) + 1
                                : parseInt(requestedQty, 10);
                            if(currentStock <= 0){
                                // show friendly UI message and do not add
                                try{ var errBox = byId('saleErrorSummary'); if(errBox) errBox.innerHTML = '<div class="alert alert-warning mb-0">'+window.RNTranslate('sale_out_of_stock_product',{product:p.productName||''})+'</div>'; setTimeout(function(){ try{ errBox.innerHTML=''; }catch(_){} },4000); }catch(e){}
                                showToast && showToast(window.RNTranslate('common.client_out_of_stock'),window.RNTranslate('sale_out_of_stock_product',{product:p.productName||''}),'error');
                                return;
                            }
                            if(newQty > currentStock){
                                try{ var errBox2 = byId('saleErrorSummary'); if(errBox2) errBox2.innerHTML = '<div class="alert alert-danger mb-0">'+window.RNTranslate('sale_stock_limit',{stock:currentStock})+'</div>'; setTimeout(function(){ try{ errBox2.innerHTML=''; }catch(_){} },4000); }catch(e){}
                                showToast && showToast(window.RNTranslate('common.client_low_stock'),window.RNTranslate('common.client_cannot_increase_quantity_beyond_available_stock'),'error');
                                return;
                            }
                            qtyEl.value = newQty;
                            if(requestedQty !== undefined) qtyEl.dataset.lastValidQty = String(newQty);
                            recalcRow(row, p.currentStock || 0);
                            recalcTotals();
                            showToast && showToast(window.RNTranslate('common.client_updated'),window.RNTranslate('common.client_increased_quantity_for_existing_row'),'success');
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
            var groupId = String(p.__productGroupId || p.productId || '');
            var salePrice = (SALE_PRODUCT_SALE_PRICE[groupId] != null ? SALE_PRODUCT_SALE_PRICE[groupId] : (p.__groupSalePrice!=null ? p.__groupSalePrice : (p.salePriceExVat!=null ? p.salePriceExVat : '')))
            var buyPrice = (p.buyPrice!=null ? p.buyPrice : '')
            tr.setAttribute('data-idx', idx);
            tr.setAttribute('data-purchase-id', purchaseId);
            if(p.productId) tr.setAttribute('data-product-id', p.productId);
            if(groupId) tr.setAttribute('data-product-group', groupId);
            tr.setAttribute('data-serial-available', '0');
            tr.className = 'rn-sale-allocation-row';
            tr.hidden = true;
            tr.innerHTML = `
                <td><input type="hidden" name="purchaseData[]" value="${purchaseId}">
                    <input type="hidden" class="warrantyDays" name="warranty_days[]" value="">
                    <input type="hidden" class="qty" name="qty[]" value="${requestedQty === undefined ? 1 : requestedQty}">
                    <input type="hidden" class="salePrice" name="salePrice[]" value="${salePrice}">
                    <input type="hidden" class="buyPrice" name="buyPrice[]" value="${buyPrice}">
                    <input type="hidden" class="totalSale" value="0">
                    <input type="hidden" class="totalPurchase" value="0">
                    <input type="hidden" class="profitMargin" value="0">
                    <input type="hidden" class="profitTotal" value="0">
                    <div class="serial-hidden"></div>
                </td>
            `;
            // If this row was created as a backorder, mark it and include hidden marker for server
            try{
                if(p && p.__backorder){
                    tr.classList.add('backorder-row');
                    var summaryRow = ensureSaleProductRow(p, p.__productGroupId || p.productId);
                    if(summaryRow) summaryRow.classList.add('backorder-row');
                    var hin = document.createElement('input'); hin.type = 'hidden'; hin.name = 'backorder[]'; hin.value = purchaseId || '';
                    tr.appendChild(hin);
                }
            }catch(e){}
            // Prevent adding when there's no stock
            var stockAvailable = parseInt(p.currentStock || 0, 10);
            if(stockAvailable <= 0){
                try{ var errBox3 = byId('saleErrorSummary'); if(errBox3) errBox3.innerHTML = '<div class="alert alert-warning mb-0">'+window.RNTranslate('sale_out_of_stock_product',{product:p.productName||''})+'</div>'; setTimeout(function(){ try{ errBox3.innerHTML=''; }catch(_){} },4000); }catch(e){}
                showToast && showToast(window.RNTranslate('common.client_out_of_stock'),window.RNTranslate('sale_out_of_stock_product',{product:p.productName||''}),'error');
                return;
            }
            tbody.appendChild(tr);

            // Attach current stock as attribute for validation
            try{ tr.setAttribute('data-current-stock', stockAvailable); }catch(e){}

            var qtyEl = tr.querySelector('.qty');
            if(qtyEl) qtyEl.dataset.lastValidQty = String(requestedQty === undefined ? 1 : requestedQty);
            // initial calc
            recalcRow(tr, p.currentStock || 0);
            validateSerialsForRow(tr);
            // Prefetch available serial count to enforce selection when required
            fetchAvailableSerials(purchaseId).then(function(serials){
                try{ tr.setAttribute('data-serial-available', (serials && serials.length) ? serials.length : 0); }catch(e){}
                validateSerialsForRow(tr);
            }).catch(function(){ tr.dataset.serialPolicyError = '1'; validateSerialsForRow(tr); updateSaleProductRow(tr.dataset.productGroup); });
            if(p.__backorder && groupId){
                if(!SALE_PRODUCT_BATCHES[groupId]) SALE_PRODUCT_BATCHES[groupId]=[];
                SALE_PRODUCT_SALE_PRICE[groupId]=SALE_PRODUCT_SALE_PRICE[groupId] || String(salePrice || 0);
                SALE_PRODUCT_ALLOCATED[groupId]=(parseInt(SALE_PRODUCT_ALLOCATED[groupId]||'0',10)||0)+(parseInt(requestedQty||'1',10)||1);
            }
            ensureSaleProductRow(p, groupId);
            updateSaleProductRow(groupId);
            toggleSaveButton();
        }catch(e){ console.warn('appendSaleRow error', e); }
    }

    function fetchAvailableSerials(purchaseId){
        var tpl = ROUTES.purchaseSerialTpl || '';
        var url = routeForId(tpl, purchaseId);
        if(!url) return Promise.reject(new Error('Purchase serial route is not configured'));
        return fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
            .then(function(res){
                var ct = res.headers.get('content-type') || '';
                if(!res.ok || res.redirected) throw new Error('unauthorized');
                if(ct.indexOf('application/json') === -1) throw new Error('invalid-response');
                return res.json();
            })
            .then(function(json){
                if (!json || json.status !== 'ok') throw new Error('invalid-response');
                document.querySelectorAll('#saleAllocationRows tr[data-purchase-id="'+purchaseId+'"]').forEach(function(row){
                    row.dataset.serialRequired = json.selection_required === false ? '0' : '1';
                    row.dataset.serialized = json.serialized === true ? '1' : (json.serials.length ? '1' : '0');
                    row.dataset.serialAvailable = json.serials.length;
                    row.dataset.serialPolicyError = '0';
                    validateSerialsForRow(row);
                    if(row.dataset.productGroup) updateSaleProductRow(row.dataset.productGroup);
                });
                return json.serials;
            });
    }

    // Refresh on return to this tab and periodically while a form remains open.
    // The transaction always resolves the database setting again at submission.
    function refreshSerialPolicies(){
        document.querySelectorAll('#saleAllocationRows tr[data-purchase-id]').forEach(function(row){
            fetchAvailableSerials(row.dataset.purchaseId).catch(function(){
                row.dataset.serialPolicyError = '1'; validateSerialsForRow(row);
            });
        });
    }
    window.addEventListener('focus', refreshSerialPolicies);
    setInterval(function(){ if(!document.hidden) refreshSerialPolicies(); }, 30000);

    function renderSerialList(serials, selectedIds){
        var list = byId('saleSerialList');
        var status = byId('saleSerialStatus');
        var meta = byId('saleSerialMeta');
        if(list) list.innerHTML = '';
        if(!serials || serials.length === 0){
            if(status){ status.className = 'alert alert-warning mb-2'; status.textContent = window.RNTranslate('no_available_serials_for_this_purchase'); }
            if(meta) meta.textContent = '';
            return;
        }
        if(status){ status.className = 'alert alert-success mb-2'; status.textContent = window.RNTranslate('available_count', {count:serials.length}); }
        if(meta) meta.textContent = window.RNTranslate('select_the_serials_to_assign_to_this_sale_row');
        serials.forEach(function(s){
            var li = document.createElement('label');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            var checked = selectedIds.indexOf(String(s.id)) !== -1;
            var box = document.createElement('div'); box.className = 'form-check';
            var input = document.createElement('input'); input.className='form-check-input serial-pick'; input.type='checkbox'; input.name='serialPick[]'; input.value=String(s.id || ''); input.checked=checked; input.dataset.serialNumber=String(s.serialNumber || ''); input.dataset.purchaseId=String(s.purchaseId || '');
            var label = document.createElement('span'); label.className='ms-2'; label.textContent=String(s.serialNumber || '');
            box.appendChild(input); box.appendChild(label); li.appendChild(box);
            var available = document.createElement('span'); available.className='badge bg-light text-muted'; available.textContent=window.RNTranslate('sale_available'); li.appendChild(available);
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
        var status = byId('saleSerialStatus'); if(status){ status.className = 'alert alert-info mb-2'; status.textContent = window.RNTranslate('loading_available_serials'); }
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
            if(hint && qty){ hint.textContent = row.dataset.serialRequired === '0' ? window.RNTranslate('optional_exact', {qty:qty}) : window.RNTranslate('select_exact', {qty:qty}); }
            enforceSerialLimit();
            showSaleSerialModal(modal);
        }).catch(function(err){
            console.warn('fetchAvailableSerials failed', err);
            if(status){ status.className = 'alert alert-danger mb-2'; status.textContent = window.RNTranslate('failed_to_load_serials_please_retry'); }
            showSaleSerialModal(modal);
        });
    }

    function openProductSerialModal(productId){
        var modal = byId('saleSerialModal'); if(!modal) return;
        var row = byId('productDetails').querySelector('tr[data-product-group="'+productId+'"][data-product-summary]');
        if(!row) return;
        var qty = parseInt(row.querySelector('.qty').value || '0',10) || 0;
        var backorderQty=Array.prototype.slice.call(document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"].backorder-row .qty')).reduce(function(sum,input){return sum+(parseInt(input.value||'0',10)||0);},0);
        qty=Math.max(0,qty-backorderQty);
        var status=byId('saleSerialStatus'), hint=byId('saleSerialHint');
        if(status){ status.className='alert alert-info mb-2'; status.textContent=window.RNTranslate('loading_available_serials'); }
        if(hint) hint.textContent='';
        modal.dataset.productId=String(productId); modal.removeAttribute('data-idx'); modal.removeAttribute('data-purchase-id');
        modal.setAttribute('data-qty',String(qty)); modal.setAttribute('data-available-count','0');
        var selected = Array.prototype.slice.call(document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"] input[data-serial][name^="serialId"]')).map(function(input){ return String(input.value); });
        var batches=SALE_PRODUCT_BATCHES[String(productId)] || [];
        Promise.all(batches.map(function(batch){ return fetchAvailableSerials(batch.purchaseId).then(function(serials){ return serials.map(function(serial){ return Object.assign({},serial,{purchaseId:batch.purchaseId}); }); }); }))
            .then(function(groups){
                var serials=[].concat.apply([],groups), unique=[], seen=Object.create(null);
                serials.forEach(function(serial){ var key=String(serial.id); if(!seen[key]){seen[key]=true;unique.push(serial);} });
                modal.setAttribute('data-available-count',String(unique.length));
                renderSerialList(unique,selected);
                if(hint) hint.textContent=row.dataset.serialRequired==='0' ? window.RNTranslate('optional_exact',{qty:qty}) : window.RNTranslate('select_exact',{qty:qty});
                enforceSerialLimit(); showSaleSerialModal(modal);
            }).catch(function(){
                if(status){ status.className='alert alert-danger mb-2'; status.textContent=window.RNTranslate('failed_to_load_serials_please_retry'); }
                updateSaleProductRow(productId); showSaleSerialModal(modal);
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
            var productId = modal.dataset.productId || '';
            if(productId){
                var productRow=byId('productDetails').querySelector('tr[data-product-group="'+productId+'"][data-product-summary]');
                if(!productRow) return;
                var productQty=parseInt(productRow.querySelector('.qty').value||'0',10)||0;
                var backorderQty=Array.prototype.slice.call(document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"].backorder-row .qty')).reduce(function(sum,input){return sum+(parseInt(input.value||'0',10)||0);},0);
                var serialQty=Math.max(0,productQty-backorderQty);
                var productPicks=Array.prototype.slice.call(document.querySelectorAll('#saleSerialModal input.serial-pick:checked'));
                if(serialQty>0 && productPicks.length!==serialQty && !(productRow.dataset.serialRequired==='0' && productPicks.length===0)){
                    var productStatus=byId('saleSerialStatus'); if(productStatus){productStatus.className='alert alert-warning mb-2';productStatus.textContent=window.RNTranslate('sale_serial_selection_count',{qty:serialQty,count:productPicks.length});} return;
                }
                var byPurchase=Object.create(null);
                productPicks.forEach(function(pick){ var purchaseId=String(pick.dataset.purchaseId||''); byPurchase[purchaseId]=(byPurchase[purchaseId]||0)+1; });
                if(productPicks.length && !allocateSaleProduct(productId,productQty,byPurchase)) return;
                document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"] .serial-hidden').forEach(function(box){ Array.prototype.slice.call(box.querySelectorAll('input[data-serial]')).forEach(function(input){input.remove();}); });
                productPicks.forEach(function(pick){
                    var lot=document.querySelector('#saleAllocationRows tr[data-product-group="'+productId+'"][data-purchase-id="'+pick.dataset.purchaseId+'"]');
                    if(!lot) return;
                    var idInput=document.createElement('input'); idInput.type='hidden'; idInput.name='serialId['+lot.dataset.idx+'][]'; idInput.value=pick.value; idInput.dataset.serial='1'; lot.querySelector('.serial-hidden').appendChild(idInput);
                    var numberInput=document.createElement('input'); numberInput.type='hidden'; numberInput.name='serialNumber['+lot.dataset.idx+'][]'; numberInput.value=pick.dataset.serialNumber||''; numberInput.dataset.serial='1'; lot.querySelector('.serial-hidden').appendChild(numberInput);
                });
                reindexSaleSerialInputs();
                document.querySelectorAll('#saleAllocationRows tr[data-product-group="'+productId+'"]').forEach(validateSerialsForRow);
                updateSaleProductRow(productId); recalcTotals(); toggleSaveButton();
                showToast && showToast(window.RNTranslate('serials_applied'),window.RNTranslate('assigned_count',{count:productPicks.length}),'success');
                hideSaleSerialModal(modal); return;
            }
            if(!row){ console.warn('applySaleSerials: row not found for idx', idx); return; }
            // Prefer current row order when writing hidden inputs so server arrays align after reindexing
            var targetIdx = (rowOrder !== null && rowOrder !== '' ? rowOrder : idx);
            var picks = Array.prototype.slice.call(document.querySelectorAll('#saleSerialModal input.serial-pick:checked'));
            if(qty > 0 && picks.length !== qty && !(row.dataset.serialRequired === '0' && picks.length === 0)){
                var status = byId('saleSerialStatus');
                if(status){ status.className = 'alert alert-warning mb-2'; status.textContent = window.RNTranslate('sale_serial_selection_count',{qty:qty,count:picks.length}); }
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
            showToast && showToast(window.RNTranslate('serials_applied'), window.RNTranslate('assigned_count',{count:picks.length}), 'success');
            hideSaleSerialModal(modal);
        }catch(e){ console.error('applySaleSerials error:', e); }
    }

    function validateSerialsForRow(tr){
        if(!tr) return;
        var qty = num((tr.querySelector('.qty')||{}).value || 0);
        var holder = tr.querySelector('.serial-hidden') || tr;
        var selected = holder.querySelectorAll('input[type="hidden"][data-serial][name^="serialId"]');
        var count = selected ? selected.length : 0;
        var badge = tr.querySelector('.serial-count-badge'); if(badge) badge.textContent = window.RNTranslate('selected_count', {count:count});
        var note = tr.querySelector('.serial-note');
        var available = parseInt(tr.getAttribute('data-serial-available') || '0', 10) || 0;
        var serialized = tr.dataset.serialized === '1' || available > 0;
        var requireSerials = tr.dataset.serialRequired !== '0';
        var policyNote = tr.querySelector('.serial-policy-note');
        if(!policyNote){ policyNote = document.createElement('div'); policyNote.className = 'small text-muted serial-policy-note'; if(note) note.parentNode.appendChild(policyNote); }
        policyNote.textContent = serialized && !requireSerials ? window.RNTranslate('optional_leave_empty_to_allocate_available_serials_automatically') : '';
        var mismatch = false;
        if(tr.dataset.serialPolicyError === '1' || (serialized && qty > 0 && count !== qty && (requireSerials || count > 0))){
            mismatch = true;
            if(note){ note.style.display = 'block'; note.textContent = tr.dataset.serialPolicyError === '1' ? window.RNTranslate('unable_to_verify_serial_policy_retry_selection_before_saving') : window.RNTranslate('select_exact', {qty:qty}); }
        } else {
            if(note){ note.style.display = 'none'; note.textContent = ''; }
        }
        if(mismatch){
            tr.classList.add('serial-mismatch');
            // show a per-row toast to prompt user to select serials
            try{ showRowSerialToast(tr, window.RNTranslate(tr.dataset.serialPolicyError === '1' ? 'unable_to_verify_serial_policy_retry_selection_before_saving' : 'select_quantity')); }catch(e){}
        }
        else {
            tr.classList.remove('serial-mismatch');
            // clear per-row toast flag so future mismatches re-trigger
            try{ tr.removeAttribute('data-serial-toast'); }catch(e){}
        }
        // Update global indicator whenever a row's serial state changes
        try{ updateSerialMismatchIndicator(); }catch(e){}
    }

    // Show immediate validation when any row requires serial selection.
    function updateSerialMismatchIndicator(){
        try{
            var rows = Array.prototype.slice.call(document.querySelectorAll('#productDetails tr'));
            var mismatches = rows.filter(function(r){ return r.classList && r.classList.contains('serial-mismatch'); });
            var count = mismatches.length;
            var errBox = byId('saleErrorSummary');
            var submitBtns = document.querySelectorAll('form#saveSaleForm button[type="submit"]');
            if(count > 0){
                var policyFailed = mismatches.some(function(row){ return row.dataset.serialPolicyError === '1'; });
                var msg = '<div class="alert alert-warning mb-0">'+(policyFailed ? window.RNTranslate('unable_to_verify_serial_policy_retry_selection_before_saving') : window.RNTranslate('missing_products',{count:count}))+'</div>';
                if(errBox) errBox.innerHTML = msg;
                submitBtns.forEach(function(btn){ btn.disabled = true; });
            } else {
                if(errBox) errBox.innerHTML = '';
                // Re-evaluate save button enablement based on presence of rows
                try{ toggleSaveButton(); }catch(e){}
            }
        }catch(e){ console.warn('updateSerialMismatchIndicator failed', e); }
    }

    // Show a per-row toast and highlight when a row requires serial selection
    function showRowSerialToast(tr, message){
        try{
            if(!tr) return;
            // Prevent repeated toasts for the same row until resolved
            if(tr.getAttribute('data-serial-toast') === '1') return;
            tr.setAttribute('data-serial-toast','1');
            // Use global toast if available
            try{ if(typeof showToast === 'function'){ showToast(window.RNTranslate(tr.dataset.serialPolicyError === '1' ? 'serial_numbers' : 'serial_required'), message, 'warning'); } }catch(e){}
            // Add a quick visual highlight
            var oldOutline = tr.style.boxShadow || '';
            tr.style.boxShadow = '0 0 10px rgba(255,165,0,0.6)';
            tr.style.transition = 'box-shadow 0.3s ease';
            // Also pulse the serial button if present
            try{
                var btn = tr.querySelector('.open-sale-serials'); if(btn){ btn.classList.add('btn-outline-warning'); }
            }catch(e){}
            setTimeout(function(){ try{ tr.style.boxShadow = oldOutline || ''; var btn = tr.querySelector('.open-sale-serials'); if(btn){ btn.classList.remove('btn-outline-warning'); } }catch(e){} }, 2200);
        }catch(e){ console.warn('showRowSerialToast failed', e); }
    }

    function reindexSaleSerialInputs(){
        try{
            var rows = document.querySelectorAll('#saleAllocationRows tr');
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
            var productId = btn.getAttribute('data-product-id');
            if(productId){ openProductSerialModal(productId); return; }
            var pid = btn.getAttribute('data-purchase-id');
            if(!pid){ showToast && showToast(window.RNTranslate('common.client_missing_purchase'),window.RNTranslate('common.client_purchase_id_not_found_for_this_row'),'error'); return; }
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
            var saveBtns = document.querySelectorAll('form#saveSaleForm button[type="submit"]');
            var hasRows = document.querySelectorAll('#productDetails tr[data-product-summary]').length > 0;
            saveBtns.forEach(function(btn){ btn.disabled = SALE_SUBMITTING || !customerCanSubmit() || !hasRows || document.querySelectorAll('#productDetails tr.serial-mismatch, #productDetails tr.stock-exceeded, #productDetails tr.quantity-invalid').length > 0; });
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
                    if(ev.defaultPrevented) return;
                    if(SALE_SUBMITTING){ ev.preventDefault(); return; }
                    if(!customerCanSubmit()){ ev.preventDefault(); refreshCustomerGate(); return; }
                    reindexSaleSerialInputs();
                    // basic validation: at least one row
                    var rows = Array.prototype.slice.call(document.querySelectorAll('#productDetails tr[data-product-summary]'));
                    if(rows.length === 0 || !document.querySelector('#saleAllocationRows input[name="purchaseData[]"]')){ ev.preventDefault(); showToast && showToast(window.RNTranslate('no_items'),window.RNTranslate('add_product_first'),'warning'); return false; }
                    // Validate product-level quantity and stock; hidden lot rows carry the server payload.
                    for(var i=0;i<rows.length;i++){
                        var r = rows[i];
                        var qty = r.querySelector('.qty');
                        var qv = qty ? parseInt(qty.value||0,10) : 0;
                        if(qv <= 0){ ev.preventDefault(); showToast && showToast(window.RNTranslate('common.client_invalid_quantity'),window.RNTranslate('common.client_quantity_must_be_at_least_1'),'error'); return false; }
                        if(r.classList.contains('quantity-invalid')){ ev.preventDefault(); showToast && showToast(window.RNTranslate('common.client_invalid_quantity'),window.RNTranslate('common.client_quantity_must_be_at_least_1'),'error'); return false; }
                        // Block submit if any row exceeds available stock
                        try{ if(r.classList && r.classList.contains('stock-exceeded')){ ev.preventDefault(); showToast && showToast(window.RNTranslate('stock_exceeded'),window.RNTranslate('adjust_quantities'),'error'); return false; } }catch(e){}
                        // Block submit if serials are required but not matched to qty
                        try{
                            if(r.classList && r.classList.contains('serial-mismatch')){
                                console.error('Form blocked: row has serial-mismatch', r);
                                ev.preventDefault();
                                showToast && showToast(window.RNTranslate('serials_missing'),window.RNTranslate('serials_per_row'),'error');
                                return false;
                            }
                        }catch(e){}
                    }
                    var action = byId('saleAction');
                    if(action && ev.submitter && ev.submitter.dataset.saleAction){ action.value = ev.submitter.dataset.saleAction; }
                    SALE_SUBMITTING = true;
                    form.querySelectorAll('button[type="submit"]').forEach(function(btn){ btn.disabled = true; });
                    var submitStatus = byId('saleSubmitStatus'); if(submitStatus) submitStatus.textContent = window.RNTranslate('sale_saving');
                    return true;
                }catch(e){ console.warn('saveSale form validation failed', e); }
            });
            // keep toggling save button when rows change
            var observer = new MutationObserver(function(){ toggleSaveButton(); });
            var target = document.getElementById('productDetails'); if(target) observer.observe(target, { childList: true, subtree: false });
        }catch(e){ console.warn('sale form init failed', e); }
    });

    window.addEventListener('pageshow', function(){ SALE_SUBMITTING = false; var status=byId('saleSubmitStatus'); if(status) status.textContent=''; toggleSaveButton(); });

    function syncSaleAllocationAmounts(tr){
        if(!tr) return 0;
        var qtyInput=tr.querySelector('.qty'), saleInput=tr.querySelector('.salePrice'), buyInput=tr.querySelector('.buyPrice');
        var qty=num(qtyInput && qtyInput.value), sale=num(saleInput && saleInput.value), buy=num(buyInput && buyInput.value);
        var totalSale=Number((sale*qty).toFixed(2)), totalPurchase=Number((buy*qty).toFixed(2));
        var profitTotal=Number((totalSale-totalPurchase).toFixed(2));
        var profitPercent=totalPurchase>0?Number(((profitTotal/totalPurchase)*100).toFixed(2)):0;
        var totalSaleInput=tr.querySelector('.totalSale'), totalPurchaseInput=tr.querySelector('.totalPurchase');
        var profitTotalInput=tr.querySelector('.profitTotal'), profitMarginInput=tr.querySelector('.profitMargin');
        if(totalSaleInput) totalSaleInput.value=String(totalSale);
        if(totalPurchaseInput) totalPurchaseInput.value=String(totalPurchase);
        if(profitTotalInput) profitTotalInput.value=String(profitTotal);
        if(profitMarginInput) profitMarginInput.value=String(profitPercent);
        return totalSale;
    }
    function recalcRow(tr, stock){
        try{
            syncSaleAllocationAmounts(tr);
            var qtyInput=tr.querySelector('.qty');
            var qty=parseInt(qtyInput && qtyInput.value || '0',10)||0;
            // client-side stock validation (per-row)
            var errBox = byId('saleErrorSummary');
            if(errBox){ errBox.innerHTML = ''; }
            var curStock = (tr.getAttribute && tr.getAttribute('data-current-stock')) ? parseInt(tr.getAttribute('data-current-stock')||0,10) : (stock || 0);
            // ensure per-row note element
            try{
                var noteEl = tr.querySelector('.stock-note');
                if(!noteEl){ noteEl = document.createElement('div'); noteEl.className = 'text-danger small stock-note'; noteEl.style.display = 'none'; try{ tr.querySelector('.qty').parentNode.appendChild(noteEl); }catch(e){} }
            }catch(e){ var noteEl = tr.querySelector('.stock-note'); }
            if(curStock && qty > curStock){
                var msg = window.RNTranslate('quantity_exceeds',{stock:curStock});
                try{ if(noteEl){ noteEl.style.display = 'block'; noteEl.textContent = msg; } }catch(e){}
                try{ tr.classList.add('stock-exceeded'); }catch(e){}
                if(errBox){ errBox.innerHTML = '<div class="text-danger small">'+window.RNTranslate('common.one_or_more_items_exceed_current_stock_adjust_before_saving')+'</div>'; }
            } else {
                try{ if(noteEl){ noteEl.style.display = 'none'; noteEl.textContent = ''; } }catch(e){}
                try{ tr.classList.remove('stock-exceeded'); }catch(e){}
            }
            validateSerialsForRow(tr);
        }catch(e){ console.warn('recalcRow error', e); }
    }
    function recalcTotals(){
        try{
            // Always derive money from the submitted allocation inputs. Cached totals may be
            // stale while the visible product row has already reflected a price/quantity edit.
            var allocationRows=Array.prototype.slice.call(document.querySelectorAll('#saleAllocationRows tr'));
            if(!allocationRows.length && !byId('saveSaleForm')){ return; }
            var base=0, productTotals=Object.create(null);
            allocationRows.forEach(function(row){
                var lineTotal=syncSaleAllocationAmounts(row);
                base+=lineTotal;
                var productId=row.getAttribute('data-product-group');
                if(productId){ productTotals[productId]=(productTotals[productId]||0)+lineTotal; }
            });
            // Keep the visible product-centric rows on the exact same allocation basis as Subtotal.
            document.querySelectorAll('#productDetails tr[data-product-summary]').forEach(function(row){
                var productId=row.getAttribute('data-product-group'), line=row.querySelector('.rn-sale-line-total');
                if(line) line.textContent=Number((productTotals[productId]||0).toFixed(2)).toFixed(2);
            });
            var discount = num(byId('discountAmount') && byId('discountAmount').value);
            var addCharge = num(byId('additionalChargeAmount') && byId('additionalChargeAmount').value);
            var grand = Math.max(0, base - discount + addCharge);
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
                var outEl = byId('totalOutstandingDisplay'); if(outEl) outEl.textContent = window.RNTranslate('outstanding_value',{amount:Number(totalOutstanding.toFixed(2))});
            }catch(e){ /* ignore display errors */ }
        }catch(e){ console.warn('recalcTotals error', e); }
    }
    // Shared scripts can request a refresh, but must use the sale allocation rows as source data.
    window.recalcSaleTotals = recalcTotals;
    // Delegated handler for 'Add as backorder' buttons in out-of-stock modal
    document.addEventListener('click', function(ev){
        try{
            var btn = ev.target.closest && ev.target.closest('.add-backorder');
            if(!btn) return;
            if(!customerReady()) return;
            var purchaseId = btn.getAttribute('data-purchase-id'); if(!purchaseId) return;
            var customerVersion=SALE_CUSTOMER_VERSION;
            // Fetch the purchase row details then force-add as backorder by overriding stock
            var tplById = ROUTES.purchaseByIdTpl || '';
            var url = routeForId(tplById, purchaseId);
            if(!url) return;
            fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                .then(function(res){
                    var ct = res.headers.get('content-type') || '';
                    if(!res.ok || res.redirected) throw new Error('unauthorized');
                    if(ct.indexOf('application/json') === -1) throw new Error('invalid-response');
                    return res.json();
                }).then(function(json){
                        if(customerVersion!==SALE_CUSTOMER_VERSION || !customerReady()) return;
                        try{
                            if(!json || !json.getData || !json.getData.length) { showToast && showToast(window.RNTranslate('common.client_not_found'),window.RNTranslate('common.client_purchase_row_not_found'),'error'); return; }
                            var p = json.getData[0];
                            // mark as backorder by forcing available stock high so appendSaleRow will allow adding
                            p.currentStock = 999999;
                            p.__backorder = true;
                            // If this purchase row already exists in the table, mark it as backorder instead of adding a duplicate
                    var tbody = document.getElementById('saleAllocationRows');
                            var existing = tbody && tbody.querySelector('input[name="purchaseData[]"][value="'+(p.purchaseId || p.id || '')+'"]');
                            if(existing){
                                try{
                                    var row = existing.closest('tr');
                                    if(row){
                                        // add hidden backorder input if not present
                                        if(!row.querySelector('input[name="backorder[]"]')){
                                            var hin = document.createElement('input'); hin.type = 'hidden'; hin.name = 'backorder[]'; hin.value = (p.purchaseId || p.id || ''); row.appendChild(hin);
                                        }
                                        row.classList.add('backorder-row');
                                        var summaryRow=ensureSaleProductRow(p,p.productId);
                                        if(summaryRow) summaryRow.classList.add('backorder-row');
                                        updateSaleProductRow(p.productId);
                                        recalcTotals();
                                        // close modal
                                        try{ var modalEl = document.getElementById('outOfStockModal'); if(modalEl){ if(typeof bootstrap !== 'undefined' && bootstrap.Modal){ try{ var m = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modalEl) : null; if(!m){ m = new bootstrap.Modal(modalEl); } m.hide(); }catch(be){ try{ var m2 = new bootstrap.Modal(modalEl); m2.hide(); }catch(_){ if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } } } else if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } }catch(e){}
                                        showToast && showToast(window.RNTranslate('common.client_backorder_marked'),window.RNTranslate('common.client_existing_row_marked_as_backorder'),'success');
                                        return;
                                    }
                                }catch(e){}
                            }
                            // otherwise append a new backorder row
                            appendSaleRow(p);
                            recalcTotals();
                            // close modal if using Bootstrap 5/4 or jQuery fallback
                            try{ var modalEl = document.getElementById('outOfStockModal'); if(modalEl){ if(typeof bootstrap !== 'undefined' && bootstrap.Modal){ try{ var m = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(modalEl) : null; if(!m){ m = new bootstrap.Modal(modalEl); } m.hide(); }catch(be){ try{ var m2 = new bootstrap.Modal(modalEl); m2.hide(); }catch(_){ if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } } } else if(window.jQuery){ $('#outOfStockModal').modal('hide'); } } }catch(e){}
                            showToast && showToast(window.RNTranslate('common.client_backorder_added'),window.RNTranslate('common.client_item_added_as_backorder'),'success');
                        }catch(e){ console.warn('backorder add failed', e); showToast && showToast(window.RNTranslate('common.client_error'),window.RNTranslate('common.client_could_not_add_backorder'),'error'); }
                    }).catch(function(e){ console.warn('fetch purchase by id failed', e); showToast && showToast(window.RNTranslate('common.client_error'),window.RNTranslate('common.client_could_not_fetch_purchase_details'),'error'); });
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
