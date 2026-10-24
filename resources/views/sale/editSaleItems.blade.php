@extends('include')
@section('backTitle'){{ __('common.heading_edit_sale_items') }}@endsection
@section('container')
<div class="col-12">
  @include('sweetalert::alert')
</div>
<div class="row">
  <div class="col-lg-10">
    <div class="card">
      <div class="card-body">
        <h4 class="mb-3">{{ __('sales.edit_sale_items') }}</h4>
        @php
          $grand = (float)($sale->grandTotal ?? $sale->totalAmount ?? 0);
          $paid  = (float)($sale->paidAmount ?? 0);
          $due   = max(0, $grand - $paid);
        @endphp
        <div class="mb-2 text-muted">
          <div><strong>{{ __('common.invoice') }}:</strong> {{ $sale->invoice ?? $sale->invoiceNo ?? '-' }}</div>
          <div><strong>{{ __('common.customer') }}:</strong> {{ \App\Models\Customer::displayLabel($customer, $sale->customerId, $sale->businessId) }}</div>
          <div><strong>{{ __('common.grand_total') }}:</strong> @money($grand) &nbsp;|&nbsp; <strong>{{ __('common.paid') }}:</strong> @money($paid) &nbsp;|&nbsp; <strong>{{ __('common.due') }}:</strong> @money($due)</div>
        </div>
        @if($errors->any())
          <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('sale.items.update', ['id' => $sale->id]) }}" class="rn-m-form rn-m-sale-items-edit-form">
          @csrf
          <button type="button" class="rn-m-sale-add-item-toggle" id="toggleAddSaleItem" aria-expanded="false">{{ __('sales.add_new_item_optional') }}</button>
          <div class="table-responsive rn-m-form-section rn-m-sale-items-edit-list">
            <table class="table table-bordered rn-table-pro rn-m-sale-mobile-table">
              <thead class="thead-light">
                <tr>
                  <th style="width:6%">#</th>
                  <th style="width:20%">{{ __('sales.product') }}</th>
                  <th style="width:10%" class="rn-m-sale-purchase-id">{{ __('sales.purchase_id') }}</th>
                  <th style="width:12%" class="text-end">{{ __('sales.current_stock') }}</th>
                  <th style="width:12%" class="text-end">{{ __('sales.qty') }}</th>
                  <th style="width:12%" class="text-end">{{ __('sales.warranty_days') }}</th>
                  <th style="width:15%" class="text-end">{{ __('sales.price') }}</th>
                  <th style="width:15%" class="text-end">{{ __('sales.line_total') }}</th>
                </tr>
              </thead>
              <tbody>
                @php $i=1; @endphp
                @forelse($items as $it)
                  @php $line = (float)($it->totalSale ?? ($it->salePrice * $it->qty)); @endphp
                  <tr data-item-id="{{ $it->id }}" data-purchase-id="{{ $it->purchaseId }}">
                    <td data-label="{{ __('sales.item') }}">{{ $i++ }}</td>
                    <td data-label="{{ __('sales.product') }} / {{ __('sales.serials') }}">
                      <div class="fw-semibold">{{ $it->productName }}</div>
                      <button type="button" class="btn btn-link btn-sm p-0" data-action="toggle-serials" data-purchase-id="{{ $it->purchaseId }}">{{ __('sales.manage_serials') }}</button>
                      @error('serialIdByItem.'.(int)$it->id)<div class="text-danger small" data-role="backend-serial-error">{{ $message }}</div>@enderror
                      <div class="serials-wrap mt-1" data-purchase-id="{{ $it->purchaseId }}" style="display:none;">
                        <div class="small text-muted">{{ __('sales.select_prefix') }}<span data-role="required-count">{{ (int)$it->qty }}</span>{{ __('sales.serial_suffix') }}</div>
                        <div class="serials-list border rounded p-2" style="max-height:160px; overflow:auto;"></div>
                        <div class="serial-hidden-container"></div>
                        <div class="small text-danger mt-1" data-role="serial-error" style="display:none;">{{ __('sales.please_select_the_required_number_of_serials') }}</div>
                      </div>
                    </td>
                    <td class="rn-m-sale-purchase-id" data-label="{{ __('sales.purchase_id') }}">{{ $it->purchaseId }}</td>
                    <td class="text-end" data-label="{{ __('mobile.stock') }}">{{ $it->currentStock }}</td>
                    <td data-label="{{ __('sales.quantity') }}">
                      <input type="number" name="items[{{ $it->id }}][qty]" min="0" class="form-control form-control-sm text-end" value="{{ old('items.'.$it->id.'.qty', $it->qty) }}" data-purchase-id="{{ $it->purchaseId }}" data-role="qty">
                    </td>
                    <td data-label="{{ __('sales.warranty_days') }}">
                      <input type="text" name="items[{{ $it->id }}][warranty_days]" class="form-control form-control-sm text-end" value="{{ old('items.'.$it->id.'.warranty_days', $it->warranty_days) }}" placeholder="{{ __('common.e_g_365') }}">
                    </td>
                    <td data-label="{{ __('sales.sale_price') }}">
                      <input type="number" step="0.01" min="0" name="items[{{ $it->id }}][salePrice]" class="form-control form-control-sm text-end" value="{{ old('items.'.$it->id.'.salePrice', $it->salePrice) }}" data-role="price">
                    </td>
                    <td class="text-end" data-label="{{ __('sales.line_total') }}"><span class="line-total" data-role="line-total">@money($line)</span></td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center">{{ __('sales.no_items_found') }}</td></tr>
                @endforelse
                <tr class="rn-m-sale-items-add-row">
                  <td colspan="3" data-label="{{ __('sales.select_product_purchase') }}">
                    <small class="text-muted">{{ __('sales.select_product_purchase') }}</small>
                    <select id="addPurchaseSelect" class="form-control form-control-sm" title="{{ __('common.select_product_purchase_row') }}">
                      <option value="">{{ __('sales.loading_options') }}</option>
                    </select>
                    <input type="hidden" name="add[purchaseId]" id="addPurchaseId" value="{{ old('add.purchaseId') }}">
                    <div class="small text-muted mt-1" id="addPurchaseMeta" style="display:none;"></div>
                  </td>
                  <td data-label="{{ __('sales.available_stock') }}">
                    <small class="text-muted">{{ __('mobile.stock') }}</small>
                    <input type="text" id="addCurrentStock" class="form-control form-control-sm" value="" placeholder="{{ __('mobile.stock') }}" readonly>
                  </td>
                  <td data-label="{{ __('sales.quantity') }}">
                    <small class="text-muted">{{ __('sales.quantity') }}</small>
                    <input type="number" name="add[qty]" id="addQty" min="0" class="form-control form-control-sm" value="{{ old('add.qty') }}" placeholder="{{ __('sales.qty') }}">
                  </td>
                  <td data-label="{{ __('sales.warranty_days') }}">
                    <small class="text-muted">{{ __('sales.warranty_days') }}</small>
                    <input type="text" name="add[warranty_days]" id="addWarrantyDays" class="form-control form-control-sm text-end" value="{{ old('add.warranty_days') }}" placeholder="{{ __('common.e_g_365') }}">
                  </td>
                  <td colspan="2" data-label="{{ __('sales.sale_price') }}">
                    <small class="text-muted">{{ __('sales.sale_price') }}</small>
                    <input type="number" step="0.01" min="0" name="add[salePrice]" id="addSalePrice" class="form-control form-control-sm" value="{{ old('add.salePrice') }}" placeholder="{{ __('sales.price') }}">
                    <div class="mt-2">
                      <button type="button" class="btn btn-link btn-sm p-0" id="addManageSerials">{{ __('sales.manage_serials') }}</button>
                      <div id="addSerialsWrap" class="mt-1" style="display:none;">
                        <div class="small text-muted">{{ __('sales.select_prefix') }}<span id="addRequiredCount">0</span>{{ __('sales.serial_suffix') }}</div>
                        <div id="addSerialsList" class="border rounded p-2" style="max-height:160px; overflow:auto;"></div>
                        <div id="addSerialsHiddenContainer"></div>
                        <div class="small text-danger mt-1" id="addSerialError" style="display:none;">{{ __('sales.please_select_the_required_number_of_serials') }}</div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card mt-2 rn-m-sale-items-edit-summary">
            <div class="card-body py-2">
              @php
                $initialTotal = 0;
                foreach($items as $it){ $initialTotal += ($it->salePrice * $it->qty); }
                $discount = (float)($sale->discountAmount ?? 0);
                $initialGrand = max(0, $initialTotal - $discount);
                $paid = (float)($sale->paidAmount ?? 0);
                $initialDue = max(0, $initialGrand - $paid);
              @endphp
              <div class="rn-m-sale-items-summary-rows">
                <div class="rn-m-sale-items-summary-row"><strong>{{ __('common.total') }}</strong><span id="calcTotal">@money($initialTotal)</span></div>
                <div class="rn-m-sale-items-summary-row rn-m-sale-items-summary-discount"><label for="discountInput"><strong>{{ __('common.discount') }}</strong></label>
                  <input type="number" step="0.01" min="0" name="discountAmount" id="discountInput" class="form-control form-control-sm" style="max-width:120px;" value="{{ number_format($discount,2,'.','') }}" />
                </div>
                <div class="rn-m-sale-items-summary-row"><strong>{{ __('common.grand_total') }}</strong><span id="calcGrand">@money($initialGrand)</span></div>
                <div class="rn-m-sale-items-summary-row"><strong>{{ __('common.paid') }}</strong><span id="calcPaid">@money($paid)</span></div>
                <div class="rn-m-sale-items-summary-row"><strong>{{ __('sales.remaining_due') }}</strong><span id="calcDue">@money($initialDue)</span></div>
              </div>
            </div>
          </div>
          <div class="rn-m-sticky-actions rn-m-sale-items-edit-actions">
            <a href="{{ route('invoiceGenerate', ['id' => $sale->id]) }}" class="rn-m-btn rn-m-btn--outline">{{ __('sales.cancel') }}</a>
            <button type="submit" class="rn-m-btn rn-m-btn--primary">{{ __('sales.save_item_changes') }}</button>
          </div>
        </form>
        <div class="mt-3 small text-muted rn-m-form-section rn-m-sale-items-edit-note">
          {{ __('common.note') }}: {{ __('sales.sale_item_edit_inventory_note') }}
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function(){
    var select = document.getElementById('addPurchaseSelect');
    var addRow = document.querySelector('.rn-m-sale-items-add-row');
    var addRowToggle = document.getElementById('toggleAddSaleItem');
    if(addRowToggle && addRow){
      addRowToggle.addEventListener('click', function(){
        addRow.classList.toggle('is-open');
        addRowToggle.setAttribute('aria-expanded', addRow.classList.contains('is-open') ? 'true' : 'false');
      });
    }
    var hidPid = document.getElementById('addPurchaseId');
    var stockEl = document.getElementById('addCurrentStock');
    var qtyEl   = document.getElementById('addQty');
    var priceEl = document.getElementById('addSalePrice');
    var metaEl  = document.getElementById('addPurchaseMeta');
    var addManageSerialsBtn = document.getElementById('addManageSerials');
    var addSerialsWrap = document.getElementById('addSerialsWrap');
    var addSerialsList = document.getElementById('addSerialsList');
    var addSerialsHiddenContainer = document.getElementById('addSerialsHiddenContainer');
    var addSerialError = document.getElementById('addSerialError');
    var addRequiredCountEl = document.getElementById('addRequiredCount');
    var optsUrl = "{{ route('ajax.customer.products.public', ['id' => $customer->id ?? 0]) }}";

    function fetchOptions(){
      fetch(optsUrl)
        .then(function(r){ return r.json(); })
        .then(function(j){
          if(!j || !j.data){ return; }
          select.innerHTML = j.data;
        })
        .catch(function(e){ console.warn('Failed to load options', e); });
    }

    function onSelectChange(){
      var opt = select.options[select.selectedIndex];
      if(!opt || !opt.value){
        hidPid.value = '';
        stockEl.value = '';
        qtyEl.removeAttribute('max');
        priceEl.value = '';
        metaEl.style.display = 'none';
        metaEl.textContent = '';
        addSerialsWrap.style.display = 'none';
        addSerialsList.innerHTML = '';
        addSerialsHiddenContainer.innerHTML = '';
        return;
      }
      var purchaseId = opt.getAttribute('data-purchase-id') || '';
      var stock = opt.getAttribute('data-current-stock') || '';
      hidPid.value = purchaseId;
      stockEl.value = stock;
      if(stock){ qtyEl.setAttribute('max', stock); }

      // Fetch purchase details to prefill sale price and show meta
      var detailsUrl = "{{ route('ajax.purchase.details.public', ['id' => 0]) }}".replace('/0/', '/' + purchaseId + '/');
      fetch(detailsUrl)
        .then(function(r){ return r.json(); })
        .then(function(j){
          var d = j && j.getData && j.getData[0] ? j.getData[0] : null;
          if(!d){ priceEl.value=''; metaEl.style.display='none'; metaEl.textContent=''; return; }
          var sp = d.salePriceExVat || d.salePrice || 0;
          priceEl.value = sp;
          var date = d.purchaseDate ? (' ['+ d.purchaseDate +']') : '';
          metaEl.textContent = (d.productName || '') + ' — ' + (d.supplierName || '') + date;
          metaEl.style.display = 'block';
          // Prefill serials list (available unsold for purchase)
          if(addManageSerialsBtn){ addSerialsWrap.style.display = 'block'; }
          loadAddSerials(purchaseId);
          recalcTotals();
        })
        .catch(function(e){ console.warn('Failed to load details', e); });
    }

    if(select){
      fetchOptions();
      select.addEventListener('change', onSelectChange);
    }

    function loadAddSerials(purchaseId){
      var url = "{{ route('ajax.purchase.serials.public', ['id' => 0]) }}".replace('/0', '/' + purchaseId);
      fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin'}).then(function(r){ if(!r.ok) throw new Error('Serial lookup failed'); return r.json(); }).then(function(j){
        addSerialsWrap.dataset.serialRequired = j.selection_required === false ? '0' : '1';
        addSerialsWrap.dataset.serialized = j.serialized ? '1' : '0';
        addRequiredCountEl.parentNode.firstChild.textContent = j.selection_required === false ? 'Optional: select all or leave empty to allocate ' : window.RNTranslate('select_prefix');
        var previous = Array.prototype.slice.call(addSerialsList.querySelectorAll('input:checked')).map(function(cb){ return cb.value; });
        addSerialsList.innerHTML = '';
        var reqCount = parseInt(qtyEl.value || '0', 10) || 0;
        addRequiredCountEl.textContent = reqCount;
        if(!j || !j.serials){ return; }
        j.serials.forEach(function(s){
          var id = s.id; var num = s.serialNumber || ('SN#'+id);
          var lbl = document.createElement('label');
          lbl.className = 'd-block small';
          var cb = document.createElement('input'); cb.type='checkbox'; cb.value = id; cb.className='me-1'; cb.checked = previous.indexOf(String(id)) !== -1;
          lbl.appendChild(cb); lbl.appendChild(document.createTextNode(num));
          addSerialsList.appendChild(lbl);
        });
        syncAddSerialsHidden();
      });
    }
    function syncAddSerialsHidden(ev){
      var reqCount = parseInt(addRequiredCountEl.textContent || '0', 10) || 0;
      var cbs = Array.prototype.slice.call(addSerialsList.querySelectorAll('input[type="checkbox"]'));
      var checked = cbs.filter(function(el){ return el.checked; });
      // Enforce limit: if attempting to check beyond limit, revert the change
      if(ev && ev.target && ev.target.checked && checked.length > reqCount){
        ev.target.checked = false;
        checked = cbs.filter(function(el){ return el.checked; });
      }
      // Disable unchecked when limit reached; enable otherwise
      var disable = checked.length >= reqCount;
      cbs.forEach(function(el){ if(!el.checked){ el.disabled = disable; } else { el.disabled = false; } });
      // Recreate hidden inputs as array
      addSerialsHiddenContainer.innerHTML = '';
      checked.forEach(function(el){
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'serialIdByPurchase[' + (hidPid.value || '') + '][]';
        h.value = el.value;
        addSerialsHiddenContainer.appendChild(h);
      });
      addSerialError.style.display = (addSerialsWrap.dataset.serialized === '1' && checked.length !== reqCount && !(addSerialsWrap.dataset.serialRequired === '0' && checked.length === 0)) ? 'block' : 'none';
    }
    if(addSerialsList){ addSerialsList.addEventListener('change', syncAddSerialsHidden); }
    if(qtyEl){ qtyEl.addEventListener('input', function(){ addRequiredCountEl.textContent = parseInt(qtyEl.value || '0', 10) || 0; syncAddSerialsHidden(); recalcTotals(); }); }
    if(priceEl){ priceEl.addEventListener('input', function(){ recalcTotals(); }); }

    function refreshEditSerialPolicies(){
      document.querySelectorAll('.serials-wrap').forEach(function(wrap){
        var pid = wrap.dataset.purchaseId;
        var url = "{{ route('ajax.purchase.serials.public', ['id' => 0]) }}".replace('/0', '/' + pid);
        fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin'})
          .then(function(r){ if(!r.ok) throw new Error('Serial lookup failed'); return r.json(); })
          .then(function(j){
            wrap.dataset.serialRequired = j.selection_required === false ? '0' : '1';
            wrap.dataset.serialized = j.serialized ? '1' : '0';
            var count = wrap.querySelector('[data-role="required-count"]');
            count.parentNode.firstChild.textContent = j.selection_required === false ? 'Optional: select all or leave empty to allocate ' : window.RNTranslate('select_prefix');
            recalcTotals();
          }).catch(function(){ /* Backend verifies current policy and availability on save. */ });
      });
      if(hidPid.value) loadAddSerials(hidPid.value);
    }
    window.addEventListener('focus', refreshEditSerialPolicies);
    setInterval(function(){ if(!document.hidden) refreshEditSerialPolicies(); }, 30000);

    // Existing items: serial management per row
    function qs(sel, ctx){ return (ctx||document).querySelector(sel); }
    function qsa(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }
    qsa('[data-action="toggle-serials"]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var pid = btn.getAttribute('data-purchase-id');
        var wrap = qs('.serials-wrap', btn.closest('tr'));
        if(!wrap) return;
        var list = qs('.serials-list', wrap);
        var hiddenContainer = qs('.serial-hidden-container', wrap);
        var reqCountEl = qs('[data-role="required-count"]', wrap);
        var errorEl = qs('[data-role="serial-error"]', wrap);
        // Toggle display
        wrap.style.display = (wrap.style.display === 'none' ? 'block' : 'none');
        // Reload current policy each time selection is opened.
        // Load available serials and mark currently assigned
        var url = "{{ route('ajax.purchase.serials.public', ['id' => 0]) }}".replace('/0', '/' + pid);
        fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin'}).then(function(r){ if(!r.ok) throw new Error('Serial lookup failed'); return r.json(); }).then(function(j){
          wrap.dataset.serialRequired = j.selection_required === false ? '0' : '1';
          wrap.dataset.serialized = j.serialized ? '1' : '0';
          reqCountEl.parentNode.firstChild.textContent = wrap.dataset.serialRequired === '0' ? 'Optional: select all or leave empty to allocate ' : window.RNTranslate('select_prefix');
          var previousIds = Array.prototype.slice.call(list.querySelectorAll('input:checked')).map(function(cb){ return String(cb.value); });
          var wasLoaded = wrap.dataset.loaded === '1';
          list.innerHTML = '';
          // Preload assigned serials from blade data
          var assigned = (window.__soldSerials && window.__soldSerials[pid]) ? window.__soldSerials[pid] : [];
          var assignedIds = assigned.map(function(x){ return x.id; });
          // Render assigned first
          assigned.forEach(function(s){
            var lbl = document.createElement('label'); lbl.className='d-block small';
            var cb = document.createElement('input'); cb.type='checkbox'; cb.value = s.id; cb.className='me-1'; cb.checked = wasLoaded ? previousIds.indexOf(String(s.id)) !== -1 : true;
            lbl.appendChild(cb); lbl.appendChild(document.createTextNode(s.serialNumber || ('SN#'+s.id)+' (assigned)'));
            list.appendChild(lbl);
          });
          // Then render available (unsold)
          (j.serials||[]).forEach(function(s){
            if(assignedIds.indexOf(s.id) !== -1) return; // skip duplicates
            var lbl = document.createElement('label'); lbl.className='d-block small';
            var cb = document.createElement('input'); cb.type='checkbox'; cb.value = s.id; cb.className='me-1'; cb.checked = previousIds.indexOf(String(s.id)) !== -1;
            lbl.appendChild(cb); lbl.appendChild(document.createTextNode(s.serialNumber || ('SN#'+s.id)));
            list.appendChild(lbl);
          });
          // Hook change to sync hidden value and validate
          function sync(ev){
            var reqCount = parseInt(reqCountEl.textContent || '0', 10) || 0;
            var cbs = qsa('input[type="checkbox"]', list);
            var checked = cbs.filter(function(el){ return el.checked; });
            if(ev && ev.target && ev.target.checked && checked.length > reqCount){
              ev.target.checked = false;
              checked = cbs.filter(function(el){ return el.checked; });
            }
            var disable = checked.length >= reqCount;
            cbs.forEach(function(el){ if(!el.checked){ el.disabled = disable; } else { el.disabled = false; } });
            // Recreate hidden inputs
            hiddenContainer.innerHTML = '';
            // Use item-specific grouping to avoid collisions when multiple rows share the same purchaseId
            var tr = wrap.closest('tr[data-item-id]');
            var itemId = tr ? tr.getAttribute('data-item-id') : '';
            checked.forEach(function(el){
              var h = document.createElement('input'); h.type='hidden';
              if(itemId){
                h.name = 'serialIdByItem['+ itemId +'][]';
              } else {
                // fallback to purchase grouping if item id not found
                h.name = 'serialIdByPurchase['+ pid +'][]';
              }
              h.value = el.value; hiddenContainer.appendChild(h);
            });
            errorEl.style.display = (wrap.dataset.serialized === '1' && checked.length !== reqCount && !(wrap.dataset.serialRequired === '0' && checked.length === 0)) ? 'block' : 'none';
          }
          list.onchange = sync;
          sync();
          wrap.dataset.loaded = '1';
        });
      });
    });
    // Expose sold serials from PHP to JS for precheck
    window.__soldSerials = {!! json_encode($soldSerialsByPurchase ?? []) !!};

    // Auto calculation: update line totals and summary when qty/price change
    var moneySymbol = @json(\App\Support\Currency::symbol());
    var moneyPosition = @json(\App\Support\Currency::position());
    var useBanglaDigits = @json(str_starts_with(app()->getLocale(), 'bn'));
    function fmt(n){
      var value;
      try{ value = new Intl.NumberFormat('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}).format(n); }
      catch(e){ value = parseFloat(n).toFixed(2); }
      if(useBanglaDigits){ value = value.replace(/[0-9]/g, function(d){ return '০১২৩৪৫৬৭৮৯'[Number(d)]; }); }
      return moneyPosition === 'right' ? value + moneySymbol : moneySymbol + value;
    }
    function recalcTotals(){
      var rows = qsa('tr[data-item-id]');
      var total = 0;
      rows.forEach(function(tr){
        var qtyInput = qs('input[data-role="qty"]', tr);
        var priceInput = qs('input[data-role="price"]', tr);
        var lineEl = qs('[data-role="line-total"]', tr);
        var qty = parseFloat(qtyInput && qtyInput.value ? qtyInput.value : '0') || 0;
        var price = parseFloat(priceInput && priceInput.value ? priceInput.value : '0') || 0;
        var line = qty * price;
        total += line;
        if(lineEl){ lineEl.textContent = fmt(line); }
        // Update required serial count if wrap visible
        var pid = tr.getAttribute('data-purchase-id');
        var wrap = qs('.serials-wrap', tr);
        if(wrap){
          var rc = qs('[data-role="required-count"]', wrap); if(rc){ rc.textContent = parseInt(qty,10)||0; }
          // Re-enforce disable/enable after count change
          var list = qs('.serials-list', wrap);
          var errorEl = qs('[data-role="serial-error"]', wrap);
          if(list){
            var evt = null; // force sync without event
            (function(){
              var reqCount = parseInt(rc.textContent || '0', 10) || 0;
              var cbs = qsa('input[type="checkbox"]', list);
              var checked = cbs.filter(function(el){ return el.checked; });
              var disable = checked.length >= reqCount;
              cbs.forEach(function(el){ if(!el.checked){ el.disabled = disable; } else { el.disabled = false; } });
              errorEl.style.display = (wrap.dataset.serialized === '1' && checked.length !== reqCount && !(wrap.dataset.serialRequired === '0' && checked.length === 0)) ? 'block' : 'none';
            })();
          }
        }
      });
      // Include add row if valid
      var addQty = parseFloat(qtyEl && qtyEl.value ? qtyEl.value : '0') || 0;
      var addPrice = parseFloat(priceEl && priceEl.value ? priceEl.value : '0') || 0;
      if(addQty > 0 && addPrice > 0){ total += (addQty * addPrice); }
      var discEl = document.getElementById('discountInput');
      var discount = parseFloat(discEl && discEl.value ? discEl.value : '0');
      if(isNaN(discount) || discount < 0) discount = 0;
      if(discount > total) discount = total;
      var paid = {{ number_format((float)($sale->paidAmount ?? 0), 2, '.', '') }};
      var grand = Math.max(0, total - discount);
      var due = Math.max(0, grand - paid);
      var tEl = document.getElementById('calcTotal');
      var gEl = document.getElementById('calcGrand');
      var dEl = document.getElementById('calcDue');
      var discOut = document.getElementById('calcDiscount');
      if(tEl){ tEl.textContent = fmt(total); }
      if(gEl){ gEl.textContent = fmt(grand); }
      if(dEl){ dEl.textContent = fmt(due); }
      if(discOut){ discOut.textContent = fmt(discount); }
    }
    // Bind inputs in existing rows
    qsa('input[data-role="qty"]').forEach(function(el){ el.addEventListener('input', recalcTotals); });
    qsa('input[data-role="price"]').forEach(function(el){ el.addEventListener('input', recalcTotals); });
    var discEl = document.getElementById('discountInput');
    if(discEl){ discEl.addEventListener('input', recalcTotals); }
    // Initial compute
    recalcTotals();
  })();
</script>
@endsection
@section('scripts')
<script>
// Ensure hidden inputs reflect the current checkbox state for all rows on submit
(function(){
  try{
    var form = document.querySelector('form[action="{{ route('sale.items.update', ['id' => $sale->id]) }}"]');
    if(!form) return;
    form.addEventListener('submit', function(){
      try{
        var wraps = Array.prototype.slice.call(document.querySelectorAll('.serials-wrap'));
        wraps.forEach(function(wrap){
          try{
            var list = wrap.querySelector('.serials-list');
            var hiddenContainer = wrap.querySelector('.serial-hidden-container');
            var tr = wrap.closest('tr[data-item-id]');
            var itemId = tr ? tr.getAttribute('data-item-id') : '';
            var pid = tr ? tr.getAttribute('data-purchase-id') : '';
            if(!list || !hiddenContainer) return;
            var cbs = Array.prototype.slice.call(list.querySelectorAll('input[type="checkbox"]'));
            var checked = cbs.filter(function(el){ return el.checked; });
            hiddenContainer.innerHTML = '';
            checked.forEach(function(el){
              var h = document.createElement('input'); h.type='hidden';
              if(itemId){ h.name = 'serialIdByItem['+ itemId +'][]'; }
              else if(pid){ h.name = 'serialIdByPurchase['+ pid +'][]'; }
              else { h.name = 'serialIdByPurchase[]'; }
              h.value = el.value; hiddenContainer.appendChild(h);
            });
          }catch(e){ /* per-row sync error */ }
        });
      }catch(e){ /* overall sync error */ }
    });
  }catch(e){ }
})();
</script>
@endsection
