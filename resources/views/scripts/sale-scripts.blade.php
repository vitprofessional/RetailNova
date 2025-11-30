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
        return { base: base, productsTpl: productsTpl, purchaseTpl: purchaseTpl };
    }
    var ROUTES = {}; // will populate on DOMContentLoaded

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
        // Wire payment inputs to recalc totals
        try{
            var dam = byId('discountAmount'); if(dam) dam.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
            var paid = byId('paidAmount'); if(paid) paid.addEventListener('input', function(){ try{ recalcTotals(); }catch(e){} });
        }catch(e){ }
                if(customerSel){
            customerSel.addEventListener('change', function(){
                var cid = this.value;
                if(!cid){ productSel.innerHTML = '<option value="">Select</option>'; productSel.disabled = true; return; }
                var tpl = ROUTES.productsTpl || customerSel.getAttribute('data-products-url') || '';
                    var url = tpl ? tpl.replace('__ID__', encodeURIComponent(cid)) : (ROUTES.base + '/ajax/public/customer/'+encodeURIComponent(cid)+'/products');
                    url = adjustForSubfolder(url);
                    if(window.__SALEDEBUG) console.debug('[SALE] Products final URL:', url);
                                fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
                  .then(function(r){ return r.text(); })
                                    .then(function(txt){
                                        var firstChar = txt && String(txt).trim().charAt(0);
                                        if(window.__SALEDEBUG) console.debug('[SALE] Products raw response starts with:', firstChar);
                                        var data = {};
                                        try{ data = JSON.parse(txt); }catch(e){ 
                                                if(firstChar === '<'){ 
                                                        try{ showToast('Products fetch redirected','Login or 404 HTML returned','error'); }catch(_){}
                                                        if(window.__SALEDEBUG) console.warn('[SALE] Products HTML response (likely redirect).');
                                                }
                                                data = { data: '<option value="">Select</option>' }; 
                                        }
                    
                    try{
                        productSel.innerHTML = (data && data.data) ? data.data : '<option value="">Select</option>';
                        productSel.disabled = false;
                    }catch(e){ productSel.disabled = false; }
                }).catch(function(){ productSel.disabled = false; });
            });
        }

        // When a product is selected, fetch purchase rows and prefill first one; allow adding multiple via table
        productSel.addEventListener('change', function(){
            var pid = this.value; if(!pid) return;
            var tpl2 = ROUTES.purchaseTpl || productSel.getAttribute('data-purchase-url') || '';
            // fallback to public purchase details route
            var url2 = tpl2 ? tpl2.replace('__ID__', encodeURIComponent(pid)) : (ROUTES.base + '/ajax/public/sale/product/'+encodeURIComponent(pid)+'/purchase-details');
            url2 = adjustForSubfolder(url2);
                        if(window.__SALEDEBUG) console.debug('[SALE] Fetch purchase details URL:', url2);
                        fetch(url2, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin' })
              .then(function(r){ return r.text(); })
                            .then(function(txt){
                                var firstChar = txt && String(txt).trim().charAt(0);
                                if(window.__SALEDEBUG) console.debug('[SALE] Purchase raw response starts with:', firstChar);
                                var res = {};
                                try{ res = JSON.parse(txt); }catch(e){ 
                                        if(firstChar === '<'){ try{ showToast('Purchase fetch redirected','Login or 404 HTML returned','error'); }catch(_){} }
                                        res = {}; 
                                }
                try{
                    if(!res || !res.getData || !res.getData.length){ showToast('No purchase found','This product has no purchase rows','warning'); return; }
                    // Append a sale row for the first purchase; user can change purchase selection later
                    appendSaleRow(res.getData[0]);
                    recalcTotals();
                }catch(e){ console.warn('sale product details parse', e); }
            }).catch(function(e){ console.warn('getSaleProductDetails failed', e); });
        });
    });

    function appendSaleRow(p){
        try{
            var tbody = byId('productDetails'); if(!tbody) return;
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
                    '<div class="small">Stock: '+ (p.currentStock || 0) +'</div>'+
                '</td>'+
                '<td><input type="number" class="form-control qty" name="qty[]" min="1" step="1" value="1"></td>'+
                '<td><input type="number" class="form-control salePrice" name="salePrice[]" value="'+ salePrice +'"></td>'+
                '<td><input type="number" class="form-control totalSale" readonly></td>'+
                '<td><input type="number" class="form-control buyPrice" name="buyPrice[]" value="'+ buyPrice +'"></td>'+
                '<td><input type="number" class="form-control totalPurchase" readonly></td>'+
                '<td><input type="text" class="form-control profitMargin" readonly></td>'+
                '<td><input type="number" class="form-control profitTotal" readonly></td>';
            tbody.appendChild(tr);

            var qtyEl = tr.querySelector('.qty');
            var saleEl = tr.querySelector('.salePrice');
            var buyEl = tr.querySelector('.buyPrice');
            [qtyEl, saleEl, buyEl].forEach(function(inp){ inp && inp.addEventListener('input', function(){ recalcRow(tr, p.currentStock || 0); recalcTotals(); }); });
            tr.querySelector('.remove-row').addEventListener('click', function(){ tr.parentNode.removeChild(tr); recalcTotals(); });
            // initial calc
            recalcRow(tr, p.currentStock || 0);
        }catch(e){ console.warn('appendSaleRow error', e); }
    }

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
            // client-side stock validation
            var errBox = byId('saleErrorSummary');
            if(errBox){ errBox.innerHTML = ''; }
            if(stock && qty > stock){
                if(errBox){ errBox.innerHTML = '<div class="text-danger small">Quantity exceeds current stock ('+stock+'). Adjust before saving.</div>'; }
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
