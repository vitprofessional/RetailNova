@extends('include') @section('backTitle') damage product @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('damageProductSave') }}" class="row" method="POST" id="damage-product-form">
    @csrf
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Damage product</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reference" class="form-label">Reference *</label>
                                    <input type="text" class="form-control" id="reference" name="refData" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label for="productName" class="form-label">Product *</label>
                                    <select id="productName" name="productName" class="form-control js-product-select" required>
                                    <!--  form option show proccessing -->
                                        <option value="">Select</option>
                                    @if(!empty($productList) && count($productList)>0)
                                    @foreach($productList as $productData)
                                        <option value="{{$productData->id}}">{{$productData->name}}</option>
                                    @endforeach
                                    @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-3 table-responsive product-table">
                    <table class="table mb-0 table-bordered rounded-0">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>Product Name</th>
                                <th>In Date</th>
                                <th>Product Qty</th>
                                <th>Current Stock</th>
                                <th>Price</th>
                                <th>Total Price</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <tr>
                                <td width="20%">
                                    <input type="text" class="form-control" name="selectProductName" value="" id="selectProductName" readonly />
                                </td>
                                <td width="8%" id="inDate">-
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="qty" name="qty" min="1" step="1" readonly />
                                    <div id="qty-error" class="text-danger small mt-1" style="display:none;"></div>
                                    <div id="qty-stock" class="text-muted small mt-1">Current stock: <span id="qty-stock-val">0</span></div>
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="currentStock" name="currentStock" readonly />
                                </td>
                                <td width="9%">
                                    <input type="number" class="form-control" id="buyingPrice" name="buyingPrice" step="0.01" />
                                </td>
                                <td width="9%">
                                    <div style="display:flex;flex-direction:column;">
                                        <input type="number" class="form-control mb-1" id="salingPriceWithoutVat" name="salingPriceWithoutVat" step="0.01" />
                                        <input type="text" class="form-control" id="computedTotal" name="total" readonly placeholder="Total" />
                                    </div>
                                </td>
                                <td width="9%">
                                   
                                    <div class="list-action">
                                        <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-remove-row="true" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"><i class="ri-delete-bin-line mr-0"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class=" d-md-flex  mt-2">
                    <button class="btn btn-primary btn-sm" type="submit">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>

@section('scripts')
    @parent
    <script>
    function productSelect(){
        var id = document.getElementById('productName').value;
        if(!id){
            // clear the single product detail row when no product selected
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
                if(window.validateDamageQty) window.validateDamageQty();
            }
            return;
        }
        var url = '{{ url('product/details') }}/' + id;
        // also fetch latest purchase prices for this product (if available)
        var priceUrl = '{{ url('sale/product/details') }}/' + id;
        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
            .then(function(res){ if(!res.ok){ console.error('product/details not ok', res.status); return {}; } return res.json(); })
            .then(function(data){
                if(data){
                    document.getElementById('selectProductName').value = data.productName || '';
                    document.getElementById('currentStock').value = data.currentStock || 0;
                    // set inDate if present (from product details this may be empty)
                    var inDateEl = document.getElementById('inDate'); if(inDateEl){ inDateEl.innerText = data.purchaseDate || '-'; }
                    // enable qty input and set max to currentStock
                    var qty = document.getElementById('qty');
                    if(qty){ qty.removeAttribute('readonly'); qty.max = data.currentStock || 0; qty.value = qty.value || 1; }
                    // update inline stock display
                    var qtyStockVal = document.getElementById('qty-stock-val'); if(qtyStockVal) qtyStockVal.innerText = data.currentStock || 0;
                    if(window.validateDamageQty) window.validateDamageQty();
                    computeTotal();
                }
            }).catch(function(err){ console.error('Failed to fetch product details', err); });

        // fetch price details separately and set price fields if present
        fetch(priceUrl, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
            .then(function(res){ if(!res.ok){ console.error('sale/product/details not ok', res.status); return {}; } return res.json(); })
            .then(function(p){
                if(p){
                    // p.getData may contain purchase rows; prefer first purchaseDate if available
                    var inDateEl = document.getElementById('inDate');
                    if(inDateEl){
                        if(p.purchaseDate) inDateEl.innerText = p.purchaseDate;
                        else if(p.getData && p.getData.length && p.getData[0].purchaseDate) inDateEl.innerText = p.getData[0].purchaseDate;
                    }
                    var bp = document.getElementById('buyingPrice'); if(bp && p.buyPrice !== undefined) bp.value = p.buyPrice || '';
                    var sp = document.getElementById('salingPriceWithoutVat'); if(sp && p.salePrice !== undefined) sp.value = p.salePrice || '';
                    computeTotal();
                }
            }).catch(function(err){ console.error('Price fetch failed', err); });
    }

    function computeTotal(){
        var qty = parseFloat(document.getElementById('qty').value || 0);
        var buy = parseFloat(document.getElementById('buyingPrice').value || 0);
        var sale = parseFloat(document.getElementById('salingPriceWithoutVat').value || 0);
        var unit = (!isNaN(sale) && sale > 0) ? sale : buy;
        var total = (unit && qty) ? (unit * qty) : 0;
        var totalEl = document.getElementById('computedTotal'); if(totalEl) totalEl.value = total ? total.toFixed(2) : '';
    }

    // bind events
    document.addEventListener('DOMContentLoaded', function(){
        var qty = document.getElementById('qty'); if(qty) qty.addEventListener('input', function(e){ computeTotal(); validateQty(); });
        var bp = document.getElementById('buyingPrice'); if(bp) bp.addEventListener('input', computeTotal);
        var sp = document.getElementById('salingPriceWithoutVat'); if(sp) sp.addEventListener('input', computeTotal);
        // ensure product select triggers productSelect when changed
        try{
            if(typeof productSelect === 'function'){
                var psel = document.getElementById('productName');
                if(psel) psel.addEventListener('change', productSelect);
                // also expose globally for delegated handlers
                window.productSelect = productSelect;
            }
        }catch(e){ console.warn('binding productSelect failed', e); }
        // Form submit guard: prevent damage qty > current stock
        var form = document.getElementById('damage-product-form');
        if(form){
            var submitBtn = form.querySelector('button[type="submit"]');
            var qtyErrorEl = document.getElementById('qty-error');

            function validateQty(){
                var q = qty ? parseInt(qty.value || 0, 10) : 0;
                var s = document.getElementById('currentStock') ? parseInt(document.getElementById('currentStock').value || 0, 10) : 0;
                if(q <= 0){
                    if(qtyErrorEl) { qtyErrorEl.style.display = 'block'; qtyErrorEl.innerText = 'Please enter a valid quantity.'; }
                    if(submitBtn) submitBtn.disabled = true;
                    return false;
                }
                if(q > s){
                    if(qtyErrorEl) { qtyErrorEl.style.display = 'block'; qtyErrorEl.innerText = 'Damage quantity cannot be greater than current stock ('+s+').' ; }
                    if(submitBtn) submitBtn.disabled = true;
                    return false;
                }
                if(qtyErrorEl) { qtyErrorEl.style.display = 'none'; qtyErrorEl.innerText = ''; }
                if(submitBtn) submitBtn.disabled = false;
                return true;
            }
            // expose for other handlers
            window.validateDamageQty = validateQty;

            form.addEventListener('submit', function(e){
                var qtyEl = document.getElementById('qty');
                var stockEl = document.getElementById('currentStock');
                var q = qtyEl ? parseInt(qtyEl.value || 0, 10) : 0;
                var s = stockEl ? parseInt(stockEl.value || 0, 10) : 0;
                if(q <= 0){
                    e.preventDefault();
                    if(window.Swal && typeof Swal.fire === 'function'){
                        Swal.fire({icon:'warning', title: 'Invalid quantity', text: 'Please enter a valid damage quantity.'});
                    } else {
                        alert('Please enter a valid damage quantity.');
                    }
                    return false;
                }
                if(q > s){
                    e.preventDefault();
                    var msg = 'Damage quantity ('+q+') cannot be greater than current stock ('+s+').';
                    if(window.Swal && typeof Swal.fire === 'function'){
                        Swal.fire({icon:'error', title: 'Insufficient stock', text: msg});
                    } else {
                        alert(msg);
                    }
                    return false;
                }
                // show spinner on submit to prevent double submits
                if(submitBtn){
                    submitBtn.disabled = true;
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                }
                return true;
            });
        }

        // Handle remove-row clicks inside the product details table
        var productDetails = document.getElementById('productDetails');
        if(productDetails){
            productDetails.addEventListener('click', function(evt){
                var btn = evt.target.closest('[data-remove-row]');
                if(!btn) return;
                evt.preventDefault();
                // find the row
                var row = btn.closest('tr');
                if(!row) return;
                // if more than one row, remove this row
                var rows = Array.prototype.slice.call(productDetails.querySelectorAll('tr'));
                if(rows.length > 1){
                    row.parentNode.removeChild(row);
                    return;
                }
                // otherwise clear the inputs in the row and reset state
                var inputs = row.querySelectorAll('input');
                inputs.forEach(function(i){
                    if(i.type === 'number' || i.type === 'text') i.value = '';
                });
                var inDateEl = document.getElementById('inDate'); if(inDateEl) inDateEl.innerText = '-';
                var qtyStockVal = document.getElementById('qty-stock-val'); if(qtyStockVal) qtyStockVal.innerText = '0';
                var qtyEl = document.getElementById('qty'); if(qtyEl) { qtyEl.setAttribute('readonly','readonly'); qtyEl.value = ''; }
                var currentStockEl = document.getElementById('currentStock'); if(currentStockEl) currentStockEl.value = '';
                var buyEl = document.getElementById('buyingPrice'); if(buyEl) buyEl.value = '';
                var spEl = document.getElementById('salingPriceWithoutVat'); if(spEl) spEl.value = '';
                var totalEl = document.getElementById('computedTotal'); if(totalEl) totalEl.value = '';
                var selectName = document.getElementById('selectProductName'); if(selectName) selectName.value = '';
                // run validation to disable submit
                if(window.validateDamageQty) window.validateDamageQty();
            });
        }
    });
    </script>
@endsection