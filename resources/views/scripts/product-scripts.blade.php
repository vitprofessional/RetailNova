// Product-related helpers extracted from `damageProduct.blade.php`
// This partial is intended to be included inside the main `customScript.blade.php` <script> block.

(function(){
    function productSelect(){
        try{
            var idEl = document.getElementById('productName');
            var id = idEl ? idEl.value : '';
            if(!id){
                var row = document.querySelector('#productDetails tr');
                if(row){
                    var inputs = row.querySelectorAll('input');
                    inputs.forEach(function(i){ if(i.type === 'number' || i.type === 'text') i.value = ''; });
                    var inDateEl = document.getElementById('inDate'); if(inDateEl) inDateEl.innerText = '-';
                    var qtyStockVal = document.getElementById('qty-stock-val'); if(qtyStockVal) qtyStockVal.innerText = '0';
                    var qtyEl = document.getElementById('qty'); if(qtyEl) { qtyEl.setAttribute('readonly','readonly'); qtyEl.value = ''; }
                    var currentStockEl = document.getElementById('currentStock'); if(currentStockEl) currentStockEl.value = '';
                    var buyEl = document.getElementById('buyingPrice'); if(buyEl) buyEl.value = '';
                    var spEl = document.getElementById('salingPriceWithoutVat'); if(spEl) spEl.value = '';
                    var totalEl = document.getElementById('computedTotal'); if(totalEl) totalEl.value = '';
                    var selectName = document.getElementById('selectProductName'); if(selectName) selectName.value = '';
                    if(window.validateDamageQty) try{ window.validateDamageQty(); }catch(e){}
                }
                return;
            }

            var seed = document.getElementById('rn-route-seeds');
            var base = window.RN_BASE || '{{ url('/') }}';
            // Only use the explicit product details template; do not confuse with products list template
            var tplProduct = '';
            if (seed) {
                tplProduct = (seed.getAttribute('data-product-details-template') || (seed.dataset && seed.dataset.productDetailsTemplate)) || '';
            }
            tplProduct = tplProduct || (base + '/ajax/public/product/details/__ID__');

            var tplPurchase = '';
            if (seed) {
                tplPurchase = seed.dataset && seed.dataset.purchaseTemplate ? seed.dataset.purchaseTemplate : (seed.getAttribute('data-purchase-template') || '');
            }
            tplPurchase = tplPurchase || (base + '/ajax/public/sale/product/__ID__/purchase-details');

            // Element-level override: if the product select has a `data-purchase-url` attribute
            // that points to a protected route, prefer the public purchase template to avoid redirects.
            try {
                var prodEl = document.getElementById('productName');
                if (prodEl) {
                    var attr = prodEl.getAttribute('data-purchase-url');
                    if (attr && /sale\/product\/details|getSaleProductDetails/i.test(attr)) {
                        // rewrite to public purchase endpoint
                        tplPurchase = tplPurchase || (base + '/ajax/public/sale/product/__ID__/purchase-details');
                    }
                }
            } catch(e) { /* ignore */ }
            var url = tplProduct.replace('__ID__', id);
            var priceUrl = tplPurchase.replace('__ID__', id);

            fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
                    .then(function(res){
                        // quick detection for HTML login redirects
                        return res.text().then(function(txt){
                            var first = (txt||'').trim().charAt(0);
                            if(first === '<' || !txt){
                                console.warn('productSelect: non-JSON response from', url, 'firstChar=', first);
                                try{ document.getElementById('selectProductName').value = ''; }catch(e){}
                                return {};
                            }
                            try{ return JSON.parse(txt); }catch(e){ console.error('Failed parse product JSON', e); return {}; }
                        });
                    })
                    .then(function(data){
                    try{
                        if(data){
                            var sel = document.getElementById('selectProductName'); if(sel) sel.value = data.productName || '';
                            var cur = document.getElementById('currentStock'); if(cur) cur.value = data.currentStock || 0;
                            var inDateEl = document.getElementById('inDate'); if(inDateEl) inDateEl.innerText = data.purchaseDate || '-';
                            var qty = document.getElementById('qty'); if(qty){ qty.removeAttribute('readonly'); qty.max = data.currentStock || 0; qty.value = qty.value || 1; }
                            var qtyStockVal = document.getElementById('qty-stock-val'); if(qtyStockVal) qtyStockVal.innerText = data.currentStock || 0;
                            if(window.validateDamageQty) try{ window.validateDamageQty(); }catch(e){}
                            try{ computeTotal(); }catch(e){}
                        }
                    }catch(e){ console.error('productSelect apply data', e); }
                }).catch(function(err){ console.error('Failed to fetch product details', err); });

            fetch(priceUrl, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
                .then(function(res){
                    return res.text().then(function(txt){
                        var first = (txt||'').trim().charAt(0);
                        if(first === '<' || !txt){
                            console.warn('productSelect price: non-JSON response from', priceUrl, 'firstChar=', first);
                            return {};
                        }
                        try{ return JSON.parse(txt); }catch(e){ console.error('Failed parse price JSON', e); return {}; }
                    });
                })
                .then(function(p){
                    try{
                        if(p){
                            var inDateEl = document.getElementById('inDate');
                            if(inDateEl){ if(p.purchaseDate) inDateEl.innerText = p.purchaseDate; else if(p.getData && p.getData.length && p.getData[0].purchaseDate) inDateEl.innerText = p.getData[0].purchaseDate; }
                            var bp = document.getElementById('buyingPrice'); if(bp && p.buyPrice !== undefined) bp.value = p.buyPrice || '';
                            var sp = document.getElementById('salingPriceWithoutVat'); if(sp && p.salePrice !== undefined) sp.value = p.salePrice || '';
                            try{ computeTotal(); }catch(e){}
                        }
                    }catch(e){ console.error('productSelect price apply', e); }
                }).catch(function(err){ console.error('Price fetch failed', err); });
        }catch(e){ console.error('productSelect', e); }
    }

    function computeTotal(){
        try{
            var qty = parseFloat((document.getElementById('qty')||{value:0}).value || 0);
            var buy = parseFloat((document.getElementById('buyingPrice')||{value:0}).value || 0);
            var sale = parseFloat((document.getElementById('salingPriceWithoutVat')||{value:0}).value || 0);
            var unit = (!isNaN(sale) && sale > 0) ? sale : buy;
            var total = (unit && qty) ? (unit * qty) : 0;
            var totalEl = document.getElementById('computedTotal'); if(totalEl) totalEl.value = total ? total.toFixed(2) : '';
        }catch(e){ console.error('computeTotal', e); }
    }

    // expose for inline handlers and RNHandlers
    window.productSelect = window.productSelect || productSelect;
    window.computeProductTotal = window.computeProductTotal || computeTotal;

    // safe, idempotent event wiring
    document.addEventListener('DOMContentLoaded', function(){
        try{
            var psel = document.getElementById('productName');
            // On Sale page (.js-sale-product-select present), let sale-scripts own the change handler
            var isSalePage = !!document.querySelector('.js-sale-product-select');
            if(psel && !isSalePage && !psel.dataset.rnBound){ psel.addEventListener('change', productSelect); psel.dataset.rnBound = '1'; }
            var qty = document.getElementById('qty'); if(qty && !qty.dataset.rnBound){ qty.addEventListener('input', function(){ computeTotal(); if(window.validateDamageQty) try{ window.validateDamageQty(); }catch(e){} }); qty.dataset.rnBound = '1'; }
            var bp = document.getElementById('buyingPrice'); if(bp && !bp.dataset.rnBound){ bp.addEventListener('input', computeTotal); bp.dataset.rnBound = '1'; }
            var sp = document.getElementById('salingPriceWithoutVat'); if(sp && !sp.dataset.rnBound){ sp.addEventListener('input', computeTotal); sp.dataset.rnBound = '1'; }
        }catch(e){ /* ignore */ }
    });
})();

// actProductList: enable/restore product options when supplier changes
window.actProductList = window.actProductList || function(){
    try{
        var sup = document.getElementById('supplierName');
        var prod = document.getElementById('productName');
        if(!prod) return;
        var val = sup ? (sup.value || '') : '';
        if(!val){
            // restore default options or show placeholder
            if(window._productNameDefaultOptions) prod.innerHTML = window._productNameDefaultOptions;
            try{ prod.selectedIndex = 0; }catch(e){}
            prod.setAttribute('disabled','disabled');
            return;
        }
        // supplier selected: restore options and enable selector
        if(window._productNameDefaultOptions) prod.innerHTML = window._productNameDefaultOptions;
        prod.removeAttribute('disabled');
    }catch(e){ console.warn('actProductList', e); }
};

// actSaleProduct: enable sale product selector when customer is chosen
window.actSaleProduct = window.actSaleProduct || function(){
    try{
        var cust = document.getElementById('customerName');
        var sel = document.querySelector('.js-sale-product-select') || document.getElementById('productName');
        if(!sel) return;
        if(cust && cust.value){ sel.removeAttribute('disabled'); }
        else { sel.setAttribute('disabled','disabled'); try{ sel.selectedIndex = 0; }catch(e){} }
    }catch(e){ console.warn('actSaleProduct', e); }
};

// saleProductSelect: conservative handler for sale product selects
window.saleProductSelect = window.saleProductSelect || function(){
    try{
        var sel = document.querySelector('.js-sale-product-select') || document.getElementById('productName');
        if(!sel) return;
        var id = sel.value || '';
        if(!id) return;
        var productDetails = document.getElementById('productDetails');
        // Only invoke addPurchaseRow when the purchase row template exists (i.e., on Purchase pages)
        var hasPurchaseTemplate = !!document.getElementById('purchase-row-template');
        if(productDetails && productDetails.children.length === 0 && hasPurchaseTemplate && typeof window.addPurchaseRow === 'function'){
            try{ window.addPurchaseRow(id); return; }catch(e){}
        }
        // Otherwise, try the single-row product fillers
        if(typeof window.productSelect === 'function') try{ window.productSelect(); }catch(e){}
        if(typeof window.fillPurchaseProductDetails === 'function') try{ window.fillPurchaseProductDetails(id); }catch(e){}
    }catch(e){ console.warn('saleProductSelect', e); }
};

