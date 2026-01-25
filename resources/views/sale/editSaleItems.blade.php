@extends('include')
@section('backTitle') Edit Sale Items @endsection
@section('container')
<div class="col-12">
  @include('sweetalert::alert')
</div>
<div class="row">
  <div class="col-lg-10">
    <div class="card">
      <div class="card-body">
        <h4 class="mb-3">Edit Sale Items</h4>
        @php
          $grand = (float)($sale->grandTotal ?? $sale->totalAmount ?? 0);
          $paid  = (float)($sale->paidAmount ?? 0);
          $due   = max(0, $grand - $paid);
        @endphp
        <div class="mb-2 text-muted">
          <div><strong>Invoice:</strong> {{ $sale->invoice ?? $sale->invoiceNo ?? '-' }}</div>
          <div><strong>Customer:</strong> {{ $customer->name ?? '-' }}</div>
          <div><strong>Grand Total:</strong> @money($grand) &nbsp;|&nbsp; <strong>Paid:</strong> @money($paid) &nbsp;|&nbsp; <strong>Due:</strong> @money($due)</div>
        </div>
        @if($errors->any())
          <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('sale.items.update', ['id' => $sale->id]) }}">
          @csrf
          <div class="table-responsive">
            <table class="table table-bordered rn-table-pro">
              <thead class="thead-light">
                <tr>
                  <th style="width:6%">#</th>
                  <th style="width:20%">Product</th>
                  <th style="width:10%">Purchase #</th>
                  <th style="width:12%" class="text-end">Current Stock</th>
                  <th style="width:12%" class="text-end">Qty</th>
                  <th style="width:12%" class="text-end">Warranty (days)</th>
                  <th style="width:15%" class="text-end">Price</th>
                  <th style="width:15%" class="text-end">Line Total</th>
                </tr>
              </thead>
              <tbody>
                @php $i=1; @endphp
                @forelse($items as $it)
                  @php $line = (float)($it->totalSale ?? ($it->salePrice * $it->qty)); @endphp
                  <tr data-item-id="{{ $it->id }}" data-purchase-id="{{ $it->purchaseId }}">
                    <td>{{ $i++ }}</td>
                    <td>
                      <div class="fw-semibold">{{ $it->productName }}</div>
                      <button type="button" class="btn btn-link btn-sm p-0" data-action="toggle-serials" data-purchase-id="{{ $it->purchaseId }}">Manage Serials</button>
                      <div class="serials-wrap mt-1" data-purchase-id="{{ $it->purchaseId }}" style="display:none;">
                        <div class="small text-muted">Select exactly <span data-role="required-count">{{ (int)$it->qty }}</span> serial(s).</div>
                        <div class="serials-list border rounded p-2" style="max-height:160px; overflow:auto;"></div>
                        <div class="serial-hidden-container"></div>
                        <div class="small text-danger mt-1" data-role="serial-error" style="display:none;">Please select the required number of serials.</div>
                      </div>
                    </td>
                    <td>{{ $it->purchaseId }}</td>
                    <td class="text-end">{{ $it->currentStock }}</td>
                    <td>
                      <input type="number" name="items[{{ $it->id }}][qty]" min="0" class="form-control form-control-sm text-end" value="{{ old('items.'.$it->id.'.qty', $it->qty) }}" data-purchase-id="{{ $it->purchaseId }}" data-role="qty">
                    </td>
                    <td>
                      <input type="text" name="items[{{ $it->id }}][warranty_days]" class="form-control form-control-sm text-end" value="{{ old('items.'.$it->id.'.warranty_days', $it->warranty_days) }}" placeholder="e.g. 365">
                    </td>
                    <td>
                      <input type="number" step="0.01" min="0" name="items[{{ $it->id }}][salePrice]" class="form-control form-control-sm text-end" value="{{ old('items.'.$it->id.'.salePrice', $it->salePrice) }}" data-role="price">
                    </td>
                    <td class="text-end"><span class="line-total" data-role="line-total">@money($line)</span></td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center">No items found</td></tr>
                @endforelse
                <tr class="table-secondary">
                  <td colspan="7"><strong>Add New Item (optional)</strong></td>
                </tr>
                <tr>
                  <td colspan="3">
                    <small class="text-muted">Select Product/Purchase</small>
                    <select id="addPurchaseSelect" class="form-control form-control-sm" title="Select product purchase row">
                      <option value="">Loading options…</option>
                    </select>
                    <input type="hidden" name="add[purchaseId]" id="addPurchaseId" value="{{ old('add.purchaseId') }}">
                    <div class="small text-muted mt-1" id="addPurchaseMeta" style="display:none;"></div>
                  </td>
                  <td>
                    <small class="text-muted">Available Stock</small>
                    <input type="text" id="addCurrentStock" class="form-control form-control-sm" value="" placeholder="Stock" readonly>
                  </td>
                  <td>
                    <small class="text-muted">Quantity</small>
                    <input type="number" name="add[qty]" id="addQty" min="0" class="form-control form-control-sm" value="{{ old('add.qty') }}" placeholder="Qty">
                  </td>
                  <td>
                    <small class="text-muted">Warranty (days)</small>
                    <input type="text" name="add[warranty_days]" id="addWarrantyDays" class="form-control form-control-sm text-end" value="{{ old('add.warranty_days') }}" placeholder="e.g. 365">
                  </td>
                  <td colspan="2">
                    <small class="text-muted">Sale Price</small>
                    <input type="number" step="0.01" min="0" name="add[salePrice]" id="addSalePrice" class="form-control form-control-sm" value="{{ old('add.salePrice') }}" placeholder="Price">
                    <div class="mt-2">
                      <button type="button" class="btn btn-link btn-sm p-0" id="addManageSerials">Manage Serials</button>
                      <div id="addSerialsWrap" class="mt-1" style="display:none;">
                        <div class="small text-muted">Select exactly <span id="addRequiredCount">0</span> serial(s).</div>
                        <div id="addSerialsList" class="border rounded p-2" style="max-height:160px; overflow:auto;"></div>
                        <div id="addSerialsHiddenContainer"></div>
                        <div class="small text-danger mt-1" id="addSerialError" style="display:none;">Please select the required number of serials.</div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card mt-2">
            <div class="card-body py-2">
              @php
                $initialTotal = 0;
                foreach($items as $it){ $initialTotal += ($it->salePrice * $it->qty); }
                $discount = (float)($sale->discountAmount ?? 0);
                $initialGrand = max(0, $initialTotal - $discount);
                $paid = (float)($sale->paidAmount ?? 0);
                $initialDue = max(0, $initialGrand - $paid);
              @endphp
              <div class="d-flex flex-wrap gap-3 align-items-center">
                <div><strong>Total:</strong> <span id="calcTotal">৳ {{ number_format($initialTotal,2) }}</span></div>
                <div class="d-flex align-items-center" style="gap:6px;">
                  <strong>Discount:</strong>
                  <input type="number" step="0.01" min="0" name="discountAmount" id="discountInput" class="form-control form-control-sm" style="max-width:120px;" value="{{ number_format($discount,2,'.','') }}" />
                </div>
                <div><strong>Grand Total:</strong> <span id="calcGrand">৳ {{ number_format($initialGrand,2) }}</span></div>
                <div><strong>Paid:</strong> <span id="calcPaid">৳ {{ number_format($paid,2) }}</span></div>
                <div><strong>Remaining Due:</strong> <span id="calcDue">৳ {{ number_format($initialDue,2) }}</span></div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between">
            <a href="{{ route('invoiceGenerate', ['id' => $sale->id]) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Item Changes</button>
          </div>
        </form>
        <div class="mt-3 small text-muted">
          Note: Increasing quantities consumes stock from the corresponding purchase row. Reducing quantities returns stock. Adding a new item requires a valid purchase ID with sufficient stock.
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function(){
    var select = document.getElementById('addPurchaseSelect');
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
          // Add a note about out-of-stock (if any)
          if(j.outOfStock && j.outOfStock.length > 0){
            var note = document.createElement('div');
            note.className = 'small text-danger mt-1';
            note.textContent = j.outOfStock.length + ' product(s) currently out of stock are hidden.';
            select.parentElement.appendChild(note);
          }
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
        addSerialsHidden.value = '';
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
      fetch(url).then(function(r){ return r.json(); }).then(function(j){
        addSerialsList.innerHTML = '';
        var reqCount = parseInt(qtyEl.value || '0', 10) || 0;
        addRequiredCountEl.textContent = reqCount;
        if(!j || !j.serials){ return; }
        j.serials.forEach(function(s){
          var id = s.id; var num = s.serialNumber || ('SN#'+id);
          var lbl = document.createElement('label');
          lbl.className = 'd-block small';
          var cb = document.createElement('input'); cb.type='checkbox'; cb.value = id; cb.className='me-1';
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
      addSerialError.style.display = (checked.length !== reqCount) ? 'block' : 'none';
    }
    if(addSerialsList){ addSerialsList.addEventListener('change', syncAddSerialsHidden); }
    if(qtyEl){ qtyEl.addEventListener('input', function(){ addRequiredCountEl.textContent = parseInt(qtyEl.value || '0', 10) || 0; syncAddSerialsHidden(); recalcTotals(); }); }
    if(priceEl){ priceEl.addEventListener('input', function(){ recalcTotals(); }); }

    // Existing items: serial management per row
    function qs(sel, ctx){ return (ctx||document).querySelector(sel); }
    function qsa(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }
    qsa('[data-action="toggle-serials"]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var pid = btn.getAttribute('data-purchase-id');
        var wrap = qs('.serials-wrap[data-purchase-id="'+pid+'"]');
        if(!wrap) return;
        var list = qs('.serials-list', wrap);
        var hiddenContainer = qs('.serial-hidden-container', wrap);
        var reqCountEl = qs('[data-role="required-count"]', wrap);
        var errorEl = qs('[data-role="serial-error"]', wrap);
        // Toggle display
        wrap.style.display = (wrap.style.display === 'none' ? 'block' : 'none');
        if(wrap.dataset.loaded === '1'){ return; }
        // Load available serials and mark currently assigned
        var url = "{{ route('ajax.purchase.serials.public', ['id' => 0]) }}".replace('/0', '/' + pid);
        fetch(url).then(function(r){ return r.json(); }).then(function(j){
          list.innerHTML = '';
          // Preload assigned serials from blade data
          var assigned = (window.__soldSerials && window.__soldSerials[pid]) ? window.__soldSerials[pid] : [];
          var assignedIds = assigned.map(function(x){ return x.id; });
          // Render assigned first
          assigned.forEach(function(s){
            var lbl = document.createElement('label'); lbl.className='d-block small';
            var cb = document.createElement('input'); cb.type='checkbox'; cb.value = s.id; cb.className='me-1'; cb.checked = true;
            lbl.appendChild(cb); lbl.appendChild(document.createTextNode(s.serialNumber || ('SN#'+s.id)+' (assigned)'));
            list.appendChild(lbl);
          });
          // Then render available (unsold)
          (j.serials||[]).forEach(function(s){
            if(assignedIds.indexOf(s.id) !== -1) return; // skip duplicates
            var lbl = document.createElement('label'); lbl.className='d-block small';
            var cb = document.createElement('input'); cb.type='checkbox'; cb.value = s.id; cb.className='me-1';
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
            errorEl.style.display = (checked.length !== reqCount) ? 'block' : 'none';
          }
          list.addEventListener('change', sync);
          sync();
          wrap.dataset.loaded = '1';
        });
      });
    });
    // Expose sold serials from PHP to JS for precheck
    window.__soldSerials = {!! json_encode($soldSerialsByPurchase ?? []) !!};

    // Auto calculation: update line totals and summary when qty/price change
    function fmt(n){ try{ return new Intl.NumberFormat('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}).format(n); }catch(e){ return parseFloat(n).toFixed(2); } }
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
        if(lineEl){ lineEl.textContent = '৳ ' + fmt(line); }
        // Update required serial count if wrap visible
        var pid = tr.getAttribute('data-purchase-id');
        var wrap = qs('.serials-wrap[data-purchase-id="'+pid+'"]');
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
              errorEl.style.display = (checked.length !== reqCount) ? 'block' : 'none';
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
      if(tEl){ tEl.textContent = '৳ ' + fmt(total); }
      if(gEl){ gEl.textContent = '৳ ' + fmt(grand); }
      if(dEl){ dEl.textContent = '৳ ' + fmt(due); }
      if(discOut){ discOut.textContent = '৳ ' + fmt(discount); }
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
