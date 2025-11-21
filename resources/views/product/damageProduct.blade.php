@extends('include') @section('backTitle') damage product @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('damageProductSave') }}" class="row" method="POST">
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
                                        <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"><i class="ri-delete-bin-line mr-0"></i></button>
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

<script>
    function productSelect(){
        var id = document.getElementById('productName').value;
        if(!id) return;
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
        var qty = document.getElementById('qty'); if(qty) qty.addEventListener('input', computeTotal);
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
    });
</script>

@endsection