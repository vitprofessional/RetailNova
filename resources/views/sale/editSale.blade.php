@extends('include')
@section('backTitle') Edit Sale @endsection
@section('container')
<div class="col-12">
  @include('sweetalert::alert')
</div>
<div class="row">
  <div class="col-md-8 col-lg-7">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Edit Sale</h4>
          <a href="{{ route('sale.items.edit', ['id' => $sale->id]) }}" class="btn btn-outline-primary btn-sm"><i class="las la-edit"></i> Edit Items</a>
        </div>
        @php $grand = (float)($sale->grandTotal ?? $sale->totalAmount ?? 0); @endphp
        <div class="mb-2 text-muted">
          <div><strong>Invoice:</strong> {{ $sale->invoice ?? $sale->invoiceNo ?? '-' }}</div>
          <div><strong>Customer:</strong> {{ $customer->name ?? '-' }}</div>
          <div><strong>Grand Total:</strong> @money($grand)</div>
        </div>
        <form method="POST" action="{{ route('sale.update', ['id' => $sale->id]) }}">
          @csrf
          <div class="form-group mb-3">
            <label for="paidAmount">Paid Amount</label>
            <input type="number" step="0.01" min="0" max="{{ $grand }}" id="paidAmount" name="paidAmount" class="form-control" value="{{ old('paidAmount', $sale->paidAmount ?? 0) }}" required />
            @error('paidAmount')<div class="text-danger small">{{ $message }}</div>@enderror
            <div class="mt-1 small d-flex gap-3 align-items-center">
              <div><strong>Status:</strong> <span id="saleStatusBadge" class="badge badge-secondary">—</span></div>
              <div><strong>Remaining Due:</strong> <span id="saleDuePreview">—</span></div>
            </div>
            <div id="paidHint" class="text-danger small mt-1" style="display:none;">Paid amount cannot exceed grand total.</div>
          </div>
          <div class="form-group mb-3">
            <label for="date">Sale Date</label>
            <input type="date" id="date" name="date" class="form-control" value="{{ old('date', isset($sale->date) ? \Carbon\Carbon::parse($sale->date)->format('Y-m-d') : '') }}" />
            @error('date')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="form-group mb-3">
            <label for="paymentMode">Payment Mode</label>
            <select id="paymentMode" name="paymentMode" class="form-control">
              @php $pm = old('paymentMode', $sale->paymentMode ?? 'Cash'); @endphp
              <option value="Cash" {{ $pm==='Cash' ? 'selected' : '' }}>Cash</option>
              <option value="Card" {{ $pm==='Card' ? 'selected' : '' }}>Card</option>
              <option value="Mobile" {{ $pm==='Mobile' ? 'selected' : '' }}>Mobile</option>
              <option value="Bank" {{ $pm==='Bank' ? 'selected' : '' }}>Bank Transfer</option>
              <option value="Other" {{ $pm==='Other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('paymentMode')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="form-group mb-3">
            <label for="note">Remarks</label>
            <textarea id="note" name="note" class="form-control" rows="3" placeholder="Enter any special note">{{ old('note', $sale->note ?? '') }}</textarea>
            @error('note')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>
          <div class="d-flex justify-content-between">
            <a href="{{ route('saleList') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" id="saveBtn" class="btn btn-primary">Save Changes</button>
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