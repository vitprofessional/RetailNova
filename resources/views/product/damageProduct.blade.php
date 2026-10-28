@extends('include') @section('backTitle'){{ __('common.heading_damage_product') }}@endsection @section('container')
@mobile
@php($damageInitialDate = old('purchaseDate', now()->format('Y-m-d')))
<form action="{{ route('damageProductSave') }}" method="POST" class="rn-m-form rn-m-damage-form" data-rn-damage-form
    data-label-empty="{{ __('inventory.damage_empty') }}" data-label-stock-error="{{ __('inventory.damage_exceeds_stock', ['qty'=>'__QTY__','stock'=>'__STOCK__']) }}"
    data-label-total="{{ __('inventory.total_price') }}" data-label-stock="{{ __('inventory.current_stock') }}" data-label-price="{{ __('inventory.price') }}"
    data-label-count="{{ __('inventory.damage_count', ['count'=>'__COUNT__']) }}" data-label-saved="{{ __('inventory.damage_saved_count', ['count'=>'__COUNT__']) }}">
    @csrf
    <input type="hidden" name="purchaseDate" value="{{ $damageInitialDate }}" data-damage-date-value>
    <section class="rn-m-form-section rn-m-damage-meta-card">
        <div class="rn-m-damage-date-row">
            <button type="button" data-damage-open-date><i class="ri-calendar-line" aria-hidden="true"></i><span data-damage-date-label>{{ \App\Support\MobileDateTime::date($damageInitialDate) }}</span></button>
            <label for="damageReference">{{ __('common.reference') }}</label>
            <input id="damageReference" name="refData" type="text" maxlength="190" value="{{ old('refData') }}" placeholder="{{ __('common.reference') }}">
        </div>
    </section>
    <section class="rn-m-damage-item-section">
        <header><h2>{{ __('inventory.damage_product') }}</h2><span data-damage-count>০টি</span></header>
        <div class="rn-m-damage-empty" data-damage-empty>{{ __('inventory.damage_empty') }}</div>
        <div class="rn-m-damage-lines" data-damage-lines></div>
        <button type="button" class="rn-m-damage-add" data-damage-open-picker><i class="ri-add-line" aria-hidden="true"></i>{{ __('inventory.damage_add_products') }}</button>
    </section>
    @error('items')<div class="rn-m-error-banner" role="alert" data-rn-error-banner>{{ $message }}</div>@enderror
    <footer class="rn-m-sticky-actions rn-m-damage-sticky" data-damage-sticky>
        <div><span data-damage-summary-count>০টি পণ্য</span><strong data-damage-summary-total>৳০</strong></div>
        <button type="submit" class="rn-m-btn rn-m-btn--primary" data-damage-submit disabled>{{ __('common.save') }}</button>
    </footer>
</form>
<div class="rn-m-damage-sheet" data-damage-picker-sheet hidden><button type="button" class="rn-m-damage-sheet-scrim" data-damage-close-sheet aria-label="{{ __('mobile.close') }}"></button><section class="rn-m-damage-sheet-panel" role="dialog" aria-modal="true" aria-label="{{ __('inventory.damage_add_products') }}"><header><span></span><h2>{{ __('inventory.damage_add_products') }}</h2><button type="button" data-damage-close-sheet aria-label="{{ __('mobile.close') }}">×</button></header><x-m.product-picker mode="damage" :label="__('mobile.search_products')" /></section></div>
<div class="rn-m-damage-sheet" data-damage-date-sheet hidden><button type="button" class="rn-m-damage-sheet-scrim" data-damage-close-date aria-label="{{ __('mobile.close') }}"></button><section class="rn-m-damage-sheet-panel rn-m-damage-date-panel" role="dialog" aria-modal="true" aria-label="{{ __('common.date') }}"><header><span></span><h2>{{ __('common.date') }}</h2><button type="button" data-damage-close-date aria-label="{{ __('mobile.close') }}">×</button></header><label for="damageDateInput">{{ __('common.date') }}</label><input id="damageDateInput" type="date" value="{{ $damageInitialDate }}" data-damage-date-input><button type="button" class="rn-m-damage-date-done" data-damage-close-date>{{ __('common.save') }}</button></section></div>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<form action="{{ route('damageProductSave') }}" class="row rn-m-form rn-m-damage-form" method="POST" id="damage-product-form">
    @csrf
    <div class="col-12">
        <div class="row">
            <div class="col-md-12 col-12 rn-m-form-section rn-m-damage-meta">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ __('inventory.damage_product') }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date" class="form-label">{{ __('common.date') }} *</label>
                                    <input type="date" class="form-control" id="date" name="purchaseDate" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reference" class="form-label">{{ __('common.reference') }} *</label>
                                    <input type="text" class="form-control" id="reference" name="refData" />
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label for="productName" class="form-label">{{ __('common.product') }} *</label>
                                    <select id="productName" name="productName" class="form-control js-product-select" required>
                                    <!--  form option show proccessing -->
                                        <option value="">{{ __('inventory.select') }}</option>
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
    <div class="col-12 rn-m-damage-items-card">
        <div class="card">
            <div class="card-body">
                <div class="mb-3 table-responsive product-table rn-m-form-section rn-m-damage-items">
                    <table class="table mb-0 table-bordered rounded-0 rn-table-pro rn-m-damage-table">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>{{ __('inventory.product_name') }}</th>
                                <th>{{ __('inventory.in_date') }}</th>
                                <th>{{ __('inventory.product_qty') }}</th>
                                <th>{{ __('inventory.current_stock') }}</th>
                                <th>{{ __('inventory.price') }}</th>
                                <th>{{ __('inventory.total_price') }}</th>
                                <th>{{ __('inventory.remove') }}</th>
                            </tr>
                        </thead>
                        <tbody id="productDetails">
                            <tr>
                                <td width="20%" data-label="{{ __('inventory.product_name') }}">
                                    <input type="text" class="form-control" name="selectProductName" value="" id="selectProductName" readonly />
                                </td>
                                <td width="8%" id="inDate" data-label="{{ __('inventory.in_date') }}">
                                </td>
                                <td width="9%" data-label="{{ __('inventory.product_qty') }}">
                                    <input type="number" class="form-control" id="qty" name="qty" min="1" step="1" readonly />
                                    <div id="qty-error" class="text-danger small mt-1" style="display:none;"></div>
                                    <div id="qty-stock" class="text-muted small mt-1">{{ __('common.current_stock') }}: <span id="qty-stock-val">0</span></div>
                                </td>
                                <td width="9%" data-label="{{ __('inventory.current_stock') }}">
                                    <input type="number" class="form-control" id="currentStock" name="currentStock" readonly />
                                </td>
                                <td width="9%" data-label="{{ __('inventory.price') }}">
                                    <input type="number" class="form-control" id="buyingPrice" name="buyingPrice" step="0.01" />
                                </td>
                                <td width="9%" data-label="{{ __('inventory.total_price') }}">
                                    <div style="display:flex;flex-direction:column;">
                                        <input type="number" class="form-control mb-1" id="salingPriceWithoutVat" name="salingPriceWithoutVat" step="0.01" />
                                        <input type="text" class="form-control" id="computedTotal" name="total" readonly placeholder="{{ __('inventory.total') }}" />
                                    </div>
                                </td>
                                <td width="9%" data-label="{{ __('inventory.remove') }}">

                                    <div class="list-action">
                                        <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-remove-row="true" data-toggle="tooltip" data-placement="top" aria-label="{{ __('inventory.delete') }}" title="{{ __('inventory.delete') }}"><i class="ri-delete-bin-line mr-0"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="rn-m-sticky-actions rn-m-damage-actions">
                    <a class="rn-m-btn rn-m-btn--outline" href="{{ route('damageProductList') }}">{{ __('common.cancel') }}</a>
                    <button class="rn-m-btn rn-m-btn--primary" type="submit">{{ __('inventory.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endmobile
@endsection

@section('scripts')
    @parent
    @mobile
    <script>
    (() => {
        const form = document.querySelector('[data-rn-damage-form]');
        if (!form) return;
        const pickerSheet = document.querySelector('[data-damage-picker-sheet]');
        const dateSheet = document.querySelector('[data-damage-date-sheet]');
        const dateInput = document.querySelector('[data-damage-date-input]');
        const dateValue = form.querySelector('[data-damage-date-value]');
        const dateLabel = form.querySelector('[data-damage-date-label]');
        const lines = form.querySelector('[data-damage-lines]');
        const empty = form.querySelector('[data-damage-empty]');
        const submit = form.querySelector('[data-damage-submit]');
        const locale = document.documentElement.lang?.startsWith('bn') ? 'bn-BD' : 'en-US';
        const digits = value => Number(value || 0).toLocaleString(locale, { maximumFractionDigits: 2 });
        const money = value => `${@json(\App\Support\Currency::symbol())}${digits(value)}`;
        const rows = new Map();
        const labels = Object.fromEntries(['total','stock','price','count','saved','empty','stockError'].map(key => [key, form.dataset[`label${key[0].toUpperCase()}${key.slice(1)}`]]));
        const toast = window.RNToast;
        const updateDateLabel = () => {
            const value = dateInput.value;
            dateValue.value = value;
            if (!value) { dateLabel.textContent = ''; return; }
            const [year, month, day] = value.split('-').map(Number);
            dateLabel.textContent = new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(year, month - 1, day));
        };
        const updateSummary = () => {
            const items = [...rows.values()];
            const quantity = items.reduce((sum, item) => sum + item.quantity, 0);
            const total = items.reduce((sum, item) => sum + item.quantity * item.price, 0);
            form.querySelector('[data-damage-count]').textContent = `${digits(items.length)}টি`;
            form.querySelector('[data-damage-summary-count]').textContent = `${digits(items.length)}টি পণ্য`;
            form.querySelector('[data-damage-summary-total]').textContent = money(total);
            submit.disabled = items.length === 0;
            empty.hidden = items.length > 0;
        };
        const renderRow = item => {
            const id = String(item.product_id);
            const existing = rows.get(id);
            if (existing) {
                if (existing.quantity >= existing.stock) { toast?.error(labels.stockError.replace('__QTY__', digits(existing.quantity + 1)).replace('__STOCK__', digits(existing.stock))); return; }
                existing.quantity++;
                existing.quantityInput.value = existing.quantity;
                updateSummary(); return;
            }
            const stock = Number(item.stock || 0);
            if (stock <= 0) { toast?.error(form.dataset.labelEmpty); return; }
            const price = Number(item.sale_price || item.buy_price || 0);
            const article = document.createElement('article'); article.className = 'rn-m-damage-line'; article.dataset.productId = id;
            const hiddenId = document.createElement('input'); hiddenId.type='hidden'; hiddenId.name=`items[${id}][product_id]`; hiddenId.value=id; article.append(hiddenId);
            const qtyHidden = document.createElement('input'); qtyHidden.type='hidden'; qtyHidden.name=`items[${id}][qty]`; qtyHidden.value='1'; qtyHidden.dataset.damageQtyHidden=''; article.append(qtyHidden);
            const buyHidden = document.createElement('input'); buyHidden.type='hidden'; buyHidden.name=`items[${id}][buy_price]`; buyHidden.value=String(item.buy_price ?? ''); article.append(buyHidden);
            const saleHidden = document.createElement('input'); saleHidden.type='hidden'; saleHidden.name=`items[${id}][sale_price]`; saleHidden.value=String(item.sale_price ?? ''); article.append(saleHidden);
            const heading = document.createElement('div'); heading.className='rn-m-damage-line-heading';
            const name = document.createElement('strong'); name.textContent=item.name || ''; heading.append(name);
            const remove = document.createElement('button'); remove.type='button'; remove.className='rn-m-damage-remove'; remove.setAttribute('aria-label', @json(__('common.remove'))); remove.innerHTML='<i class="ri-delete-bin-line" aria-hidden="true"></i>'; heading.append(remove); article.append(heading);
            const meta = document.createElement('div'); meta.className='rn-m-damage-line-meta';
            const stockLabel = document.createElement('span'); stockLabel.textContent=`${labels.stock}: ${digits(stock)}`; meta.append(stockLabel);
            const priceLabel = document.createElement('span'); priceLabel.textContent=`${labels.price}: ${money(price)}`; meta.append(priceLabel); article.append(meta);
            const footer = document.createElement('div'); footer.className='rn-m-damage-line-footer';
            const stepper = document.createElement('div'); stepper.className='rn-m-damage-stepper';
            const decrement = document.createElement('button'); decrement.type='button'; decrement.textContent='−'; decrement.setAttribute('aria-label', @json(__('mobile.decrease')));
            const quantity = document.createElement('input'); quantity.type='number'; quantity.min='1'; quantity.max=String(stock); quantity.step='1'; quantity.inputMode='numeric'; quantity.value='1'; quantity.setAttribute('aria-label', @json(__('common.quantity')));
            const increment = document.createElement('button'); increment.type='button'; increment.textContent='+'; increment.setAttribute('aria-label', @json(__('mobile.increase')));
            stepper.append(decrement,quantity,increment); footer.append(stepper);
            const lineTotal = document.createElement('strong'); lineTotal.className='rn-m-damage-line-total'; lineTotal.textContent=money(price); footer.append(lineTotal); article.append(footer); lines.append(article);
            const state={quantity:1,stock,price,quantityInput:quantity,hiddenQuantity:qtyHidden,lineTotal}; rows.set(id,state);
            const setQty = value => { state.quantity=Math.max(1,Math.min(stock,parseInt(value||'1',10)||1)); quantity.value=String(state.quantity); qtyHidden.value=String(state.quantity); lineTotal.textContent=money(state.quantity*price); updateSummary(); };
            quantity.addEventListener('input',()=>setQty(quantity.value)); decrement.addEventListener('click',()=>setQty(state.quantity-1)); increment.addEventListener('click',()=>{ if(state.quantity>=stock){toast?.error(labels.stockError.replace('__QTY__',digits(state.quantity+1)).replace('__STOCK__',digits(stock)));return;} setQty(state.quantity+1); });
            remove.addEventListener('click',()=>{ rows.delete(id); article.remove(); updateSummary(); });
            updateSummary();
        };
        form.querySelector('[data-damage-open-picker]').addEventListener('click',()=>{ pickerSheet.hidden=false; document.body.classList.add('rn-m-sheet-open'); });
        pickerSheet.querySelectorAll('[data-damage-close-sheet]').forEach(button=>button.addEventListener('click',()=>{ pickerSheet.hidden=true; document.body.classList.remove('rn-m-sheet-open'); }));
        document.querySelector('[data-rn-product-picker]').addEventListener('rn:product-picker:selected',event=>renderRow(event.detail||{}));
        form.querySelector('[data-damage-open-date]').addEventListener('click',()=>{ dateSheet.hidden=false; document.body.classList.add('rn-m-sheet-open'); });
        dateSheet.querySelectorAll('[data-damage-close-date]').forEach(button=>button.addEventListener('click',()=>{ updateDateLabel(); dateSheet.hidden=true; document.body.classList.remove('rn-m-sheet-open'); }));
        form.addEventListener('submit',event=>{ if(rows.size===0){event.preventDefault();toast?.error(labels.empty);return;} if(!dateValue.value){event.preventDefault();toast?.error(@json(__('inventory.damage_date_required')));return;} submit.disabled=true; submit.innerHTML='<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> '+@json(__('mobile.saving')); });
        updateDateLabel(); updateSummary();
    })();
    </script>
    @else
    <script>
    function productSelect(){
        var id = document.getElementById('productName').value;
        if(!id){
            // clear the single product detail row when no product selected
            var row = document.querySelector('#productDetails tr');
            if(row){
                var inputs = row.querySelectorAll('input');
                inputs.forEach(function(i){ if(i.type === 'number' || i.type === 'text') i.value = ''; });
                var inDateEl = document.getElementById('inDate'); if(inDateEl) inDateEl.innerText = '';
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
        var base = window.RN_BASE || '{{ url('/') }}';
        var url = base + '/ajax/public/product/details/' + id;
        // also fetch latest purchase prices for this product (if available)
        var priceUrl = base + '/ajax/public/sale/product/' + id + '/purchase-details';
        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest','Accept':'application/json'}, credentials: 'same-origin'})
            .then(function(res){ return res.text(); })
            .then(function(txt){
                var first = (txt||'').trim().charAt(0);
                if(first === '<'){ console.warn('damageProduct: product details HTML/redirect'); return {}; }
                try{ return JSON.parse(txt); }catch(e){ console.warn('damageProduct: product details parse fail'); return {}; }
            })
            .then(function(data){
                if(data){
                    document.getElementById('selectProductName').value = data.productName || '';
                    document.getElementById('currentStock').value = data.currentStock || 0;
                    // set inDate if present (from product details this may be empty)
                    var inDateEl = document.getElementById('inDate'); if(inDateEl){ inDateEl.innerText = data.purchaseDate || ''; }
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
            .then(function(res){ return res.text(); })
            .then(function(txt){
                var first = (txt||'').trim().charAt(0);
                if(first === '<'){ console.warn('damageProduct: price details HTML/redirect'); return {}; }
                try{ return JSON.parse(txt); }catch(e){ console.warn('damageProduct: price details parse fail'); return {}; }
            })
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
                        alert(window.RNTranslate('common.client_please_enter_a_valid_damage_quantity'));
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
    @endmobile
@endsection
