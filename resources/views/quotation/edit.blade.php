@extends('include')
@section('backTitle') Edit Quotation @endsection
@section('container')
@php
    $__business = $business ?? null;
    $currencySymbol = ($__business && $__business->currencySymbol) ? $__business->currencySymbol : '৳';
@endphp
<div class="col-12">@include('sweetalert::alert')</div>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="header-title">
          <h4 class="card-title">Edit Quotation - {{ $quote->quote_number }}</h4>
        </div>
        <div>
          <a class="btn btn-secondary btn-sm" href="{{ route('quotation.show', ['id'=>$quote->id]) }}"><i class="las la-eye mr-1"></i>View</a>
          <a class="btn btn-outline-secondary btn-sm" target="_blank" href="{{ route('quotation.print', ['id'=>$quote->id]) }}"><i class="las la-print mr-1"></i>Print</a>
        </div>
      </div>
      <div class="card-body">
        <form action="{{ route('quotation.update', ['id'=>$quote->id]) }}" method="POST" id="quotationForm">
          @csrf
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Date</label>
              <input type="date" name="date" class="form-control" value="{{ optional($quote->date)->format('Y-m-d') }}" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Customer</label>
              <select name="customer_id" class="form-control">
                <option value="">Walking Customer</option>
                @foreach($customers as $c)
                  <option value="{{ $c->id }}" @if($quote->customer_id==$c->id) selected @endif>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Validity (days)</label>
              <input type="number" name="validity_days" class="form-control" value="{{ $quote->validity_days }}" min="1" max="180" />
            </div>
          </div>

          <div class="mb-3 table-responsive product-table">
            <table class="table mb-0 table-bordered rounded-0 rn-table-pro" id="itemsTable">
              <thead>
                <tr>
                  <th style="width:30%">Product</th>
                  <th>Description</th>
                  <th style="width:10%" class="text-end">Qty</th>
                  <th style="width:15%" class="text-end">Unit Price ({{ $currencySymbol }})</th>
                  <th style="width:12%" class="text-end">Disc %</th>
                  <th style="width:15%" class="text-end">Disc Amt ({{ $currencySymbol }})</th>
                  <th style="width:15%" class="text-end">Line Total ({{ $currencySymbol }})</th>
                  <th style="width:6%"></th>
                </tr>
              </thead>
              <tbody id="quoteItems">
                @foreach($quote->items as $it)
                @php
                  $dp = $it->discount_percent ?: 0;
                  $da = $it->discount_amount ?: 0;
                @endphp
                <tr>
                  <td>
                    <select name="items[product_id][]" class="form-control">
                      <option value="">Select Product</option>
                      @foreach($products as $p)
                        <option value="{{ $p->id }}" @if($it->product_id==$p->id) selected @endif>{{ $p->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="text" name="items[description][]" class="form-control" value="{{ $it->description }}" placeholder="Optional" /></td>
                  <td><input type="number" name="items[qty][]" class="form-control text-end qty" value="{{ $it->qty }}" min="1" /></td>
                  <td><input type="number" name="items[unit_price][]" class="form-control text-end price" step="0.01" value="{{ $it->unit_price }}" /></td>
                  <td><input type="number" name="items[discount_percent][]" class="form-control text-end dperc" step="0.01" value="{{ $dp }}" /></td>
                  <td><input type="number" name="items[discount_amount][]" class="form-control text-end damt" step="0.01" value="{{ $da }}" /></td>
                  <td class="text-end line">{{ number_format($it->line_total,2) }}</td>
                  <td><button type="button" class="btn btn-sm btn-danger del">Remove</button></td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="8">
                    <button type="button" class="btn btn-sm btn-primary" id="addItemRow">Add Item</button>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div class="row mb-2">
            <div class="col-md-8">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="3">{{ $quote->notes }}</textarea>
            </div>
            <div class="col-md-4">
              <table class="table table-sm mb-0" id="totalsTable">
                <tr><th>Subtotal</th><td class="text-end" id="subtotal">{{ number_format($quote->subtotal,2) }}</td></tr>
                <tr><th>Discount Total</th><td class="text-end" id="discountTotal">{{ number_format($quote->discount_total,2) }}</td></tr>
                <tr><th>Grand Total</th><td class="text-end" id="grandTotal">{{ number_format($quote->grand_total,2) }}</td></tr>
              </table>
            </div>
          </div>

          <div>
            <button type="submit" class="btn btn-success">Update Quotation</button>
            <a href="{{ route('quotation.show',['id'=>$quote->id]) }}" class="btn btn-link">Cancel</a>
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
  var products = @json($products->map(function($p){ return ['id'=>$p->id,'name'=>$p->name]; }));
  function fmt(n){ return (Math.round(n*100)/100).toFixed(2); }
  function addRow(){
    var opts = '<option value="">Select Product</option>' + products.map(function(p){ return '<option value="'+p.id+'">'+p.name+'</option>'; }).join('');
    var tr = document.createElement('tr');
    tr.innerHTML = `
      <td><select name="items[product_id][]" class="form-control">${opts}</select></td>
      <td><input type="text" name="items[description][]" class="form-control" placeholder="Optional" /></td>
      <td><input type="number" name="items[qty][]" class="form-control text-end qty" value="1" min="1" /></td>
      <td><input type="number" name="items[unit_price][]" class="form-control text-end price" step="0.01" /></td>
      <td><input type="number" name="items[discount_percent][]" class="form-control text-end dperc" step="0.01" /></td>
      <td><input type="number" name="items[discount_amount][]" class="form-control text-end damt" step="0.01" /></td>
      <td class="text-end line">0.00</td>
      <td><button type="button" class="btn btn-sm btn-danger del">Remove</button></td>
    `;
    document.getElementById('quoteItems').appendChild(tr);
  }
  function recalc(){
    var rows = document.querySelectorAll('#quoteItems tr');
    var sub=0, dsum=0;
    rows.forEach(function(r){
      var qty = parseFloat(r.querySelector('.qty')?.value || '0');
      var price = parseFloat(r.querySelector('.price')?.value || '0');
      var dperc = parseFloat(r.querySelector('.dperc')?.value || '0');
      var damt  = parseFloat(r.querySelector('.damt')?.value || '0');
      if(!isFinite(qty) || qty<=0 || !isFinite(price) || price<=0){ r.querySelector('.line').textContent = '0.00'; return; }
      var line = qty*price;
      if(dperc>0){ damt = line*(dperc/100); }
      if(damt>line){ damt = line; }
      var lt = line - damt;
      sub += line; dsum += damt;
      r.querySelector('.damt').value = isFinite(damt)? fmt(damt): '0.00';
      r.querySelector('.line').textContent = fmt(lt);
    });
    document.getElementById('subtotal').textContent = fmt(sub);
    document.getElementById('discountTotal').textContent = fmt(dsum);
    document.getElementById('grandTotal').textContent = fmt(Math.max(0, sub-dsum));
  }
  document.getElementById('addItemRow').addEventListener('click', function(){ addRow(); });
  document.getElementById('quoteItems').addEventListener('input', function(e){ if(['qty','price','dperc','damt'].some(c=> e.target.classList.contains(c))) recalc(); });
  document.getElementById('quoteItems').addEventListener('click', function(e){ if(e.target.classList.contains('del')){ e.target.closest('tr').remove(); recalc(); }});
})();
</script>
@endsection
