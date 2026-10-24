@extends('include')
@section('backTitle'){{ __('common.heading_edit_sale') }}@endsection
@section('container')
<div class="col-12">
  @include('sweetalert::alert')
</div>
<div class="row rn-m-sale-edit-page">
  <div class="col-md-8 col-lg-7">
    <div class="card rn-m-sale-edit-summary">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">{{ __('sales.edit_sale') }}</h4>
          <a href="{{ route('sale.items.edit', ['id' => $sale->id]) }}" class="btn btn-outline-primary btn-sm"><i class="las la-edit"></i> {{ __('sales.edit_items') }}</a>
        </div>
        @php $grand = (float)($sale->grandTotal ?? $sale->totalAmount ?? 0); @endphp
        <div class="mb-2 text-muted">
          <div><strong>{{ __('common.invoice') }}:</strong> {{ $sale->invoice ?? $sale->invoiceNo ?? '-' }}</div>
          <div><strong>{{ __('common.customer') }}:</strong> {{ \App\Models\Customer::displayLabel($customer, $sale->customerId, $sale->businessId) }}</div>
          <div><strong>{{ __('common.grand_total') }}:</strong> @money($grand)</div>
        </div>
        <form method="POST" action="{{ route('sale.update', ['id' => $sale->id]) }}" class="rn-m-form rn-m-sale-edit-form">
          @csrf
          <div class="rn-m-form-section rn-m-sale-edit-fields">
          <div class="form-group mb-3 rn-m-sale-edit-paid">
            <label for="paidAmount">{{ __('sales.paid_amount') }}</label>
            <input type="number" step="0.01" min="0" max="{{ $grand }}" id="paidAmount" name="paidAmount" class="form-control" value="{{ old('paidAmount', $sale->paidAmount ?? 0) }}" required />
            @error('paidAmount')<div class="text-danger small">{{ $message }}</div>@enderror
            <div class="mt-1 small d-flex gap-3 align-items-center rn-m-sale-edit-status">
              <div><strong>{{ __('common.status') }}:</strong> <span id="saleStatusBadge" class="badge badge-secondary">—</span></div>
              <div><strong>{{ __('sales.remaining_due') }}:</strong> <span id="saleDuePreview">—</span></div>
            </div>
            <div id="paidHint" class="text-danger small mt-1" style="display:none;">{{ __('sales.paid_amount_cannot_exceed_grand_total') }}</div>
          </div>
          <div class="form-group mb-3 rn-m-sale-edit-date">
            <label for="date">{{ __('sales.sale_date') }}</label>
            <input type="date" id="date" name="date" class="form-control" value="{{ old('date', isset($sale->date) ? \Carbon\Carbon::parse($sale->date)->format('Y-m-d') : '') }}" />
            @error('date')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="form-group mb-3 rn-m-sale-edit-method">
            <label for="payment_method">{{ __('sales.payment_method') }}</label>
            <select id="payment_method" name="payment_method" class="form-control">
              @php $pm = old('payment_method', $sale->payment_method ?? 'cash'); @endphp
              @foreach(\App\Models\SaleProduct::PAYMENT_METHODS as $method)
                <option value="{{ $method }}" @selected($pm === $method)>{{ __('sales.payment_method_'.$method) }}</option>
              @endforeach
            </select>
            @error('payment_method')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="form-group mb-3 rn-m-sale-edit-note">
            <label for="note">{{ __('sales.remarks') }}</label>
            <textarea id="note" name="note" class="form-control" rows="3" placeholder="{{ __('common.enter_any_special_note') }}">{{ old('note', $sale->note ?? '') }}</textarea>
            @error('note')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          </div>
          <div class="rn-m-sticky-actions rn-m-sale-edit-actions">
            <a href="{{ route('saleList') }}" class="rn-m-btn rn-m-btn--outline">{{ __('sales.cancel') }}</a>
            <button type="submit" id="saveBtn" class="rn-m-btn rn-m-btn--primary">{{ __('sales.save_changes') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@parent
<script>
  (function(){
    var grand = {{ number_format($grand, 2, '.', '') }};
    function fmt(n){
      try{
        return new Intl.NumberFormat('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}).format(n);
      }catch(e){ return parseFloat(n).toFixed(2); }
    }
    function updateStatus(){
      var paidEl = document.getElementById('paidAmount');
      var badge = document.getElementById('saleStatusBadge');
      var dueEl = document.getElementById('saleDuePreview');
      var hint = document.getElementById('paidHint');
      var saveBtn = document.getElementById('saveBtn');
      if(!paidEl || !badge || !dueEl){ return; }
      var paid = parseFloat(paidEl.value || '0');
      if(isNaN(paid)) paid = 0;
      var due = Math.max(0, grand - paid);
      // determine status/badge
      var status = 'PAID'; var cls = 'badge-success';
      if (due <= 0 && grand > 0) { status = 'PAID'; cls = 'badge-success'; }
      else if (grand <= 0) { status = 'PAID'; cls = 'badge-success'; }
      else if (paid <= 0 && due > 0) { status = 'DUE'; cls = 'badge-danger'; }
      else if (paid > 0 && due > 0) { status = 'PARTIAL'; cls = 'badge-warning'; }
      else { status = 'PAID'; cls = 'badge-success'; }
      badge.textContent = status;
      badge.className = 'badge ' + cls;
      dueEl.textContent = '৳ ' + fmt(due);
      // invalid: paid > grand
      var invalid = paid > grand;
      if(hint){ hint.style.display = invalid ? 'block' : 'none'; }
      if(saveBtn){ saveBtn.disabled = !!invalid; }
    }
    document.addEventListener('DOMContentLoaded', function(){
      var paidEl = document.getElementById('paidAmount');
      if(paidEl){ paidEl.addEventListener('input', updateStatus); }
      updateStatus();
    });
  })();
</script>
@endsection
