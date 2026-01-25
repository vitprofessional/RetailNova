@extends('include')
@section('backTitle') Quotation @endsection
@section('container')
@php
  $currencySymbol = optional($business)->currencySymbol ?: '৳';
  $customer = $quote->customer;
  $b = $business ?? null;
  // Build logo URL similar to sale invoice
  $logoUrl = null;
  if($b && !empty($b->businessLogo)){
    $logoUrl = (strpos($b->businessLogo, 'http') === 0)
      ? $b->businessLogo
      : asset('public/uploads/business/' . ltrim($b->businessLogo, '/'));
  }
  // Map currency symbol to name
  $currencyName = null;
  switch($currencySymbol){
    case '৳': $currencyName = 'Taka'; break;
    case '$': $currencyName = 'Dollar'; break;
    case '€': $currencyName = 'Euro'; break;
    case '£': $currencyName = 'Pound'; break;
    case '₹': $currencyName = 'Rupees'; break;
    default: $currencyName = null; break;
  }
  if(!function_exists('rn_number_to_words')){
    function rn_number_to_words($num){
      $num = (int)$num;
      if($num === 0) return 'Zero';
      $units = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
      $tens  = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
      $scales = [1000000000 => 'Billion', 1000000 => 'Million', 1000 => 'Thousand', 100 => 'Hundred'];
      $out = '';
      foreach($scales as $value => $name){
        if($num >= $value){
          $out .= rn_number_to_words(intval($num / $value)) . ' ' . $name . ' ';
          $num = $num % $value;
        }
      }
      if($num >= 20){
        $out .= $tens[intval($num/10)] . ' ' . $units[$num%10];
      } elseif($num > 0){
        $out .= $units[$num];
      }
      return trim(preg_replace('/\s+/', ' ', $out));
    }
  }
  $amountInt = floor($quote->grand_total);
  $amountFrac = (int)round(($quote->grand_total - $amountInt) * 100);
  $amountWords = rn_number_to_words($amountInt);
@endphp
<div class="row">
  <div class="col-12">
    <div class="card" id="rn-invoice-root">
      <div class="card-body">
        <div class="invoice-header">
          <div class="row align-items-start">
            <div class="col-md-8">
              @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="invoice-logo" onerror="this.style.display='none'">
              @endif
              <h4 class="company-name">{{ $b && $b->businessName ? $b->businessName : config('app.name') }}</h4>
              <div>{{ $b && $b->businessLocation ? $b->businessLocation : '' }}</div>
              @if($b && $b->mobile)<div>Phone: {{ $b->mobile }}</div>@endif
              @if($b && $b->email)<div>Email: {{ $b->email }}</div>@endif
            </div>
            <div class="col-md-4 text-end">
              <h2 class="invoice-title">Quotation</h2>
              <table class="invoice-info-table">
                <tr><td><strong>Quotation No:</strong></td><td>{{ $quote->quote_number }}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{{ optional($quote->date)->format('d-M-Y') }}</td></tr>
                <tr><td><strong>Validity:</strong></td><td>{{ $quote->validity_days }} days</td></tr>
                <tr><td><strong>Status:</strong></td><td>{{ ucfirst($quote->status) }}</td></tr>
              </table>
            </div>
          </div>
        </div>

        <hr class="invoice-divider">

        <div class="row mb-4 invoice-parties">
          <div class="col-md-6">
            <div class="party-box">
              <h6 class="party-label">BILL TO:</h6>
              <div class="party-content">
                <div class="party-name">{{ $customer->name ?? 'Walking Customer' }}</div>
                @php
                  $addr = '';
                  if(!empty($customer)){
                    if(!empty($customer->area)){ $addr = trim($customer->area); }
                    elseif(!empty($customer->address)) { $addr = trim($customer->address); }
                  }
                @endphp
                @if(!empty($addr))<div class="party-detail">{{ $addr }}</div>@endif
                @if(!empty($customer) && !empty($customer->mobile))<div class="party-detail">Contact: {{ $customer->mobile }}</div>@endif
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="party-box">
              <h6 class="party-label">QUOTATION INFORMATION:</h6>
              <div class="party-content">
                <div class="party-detail">Subtotal: <strong>{{ number_format($quote->subtotal,2) }}</strong></div>
                <div class="party-detail">Discount: <strong>{{ number_format($quote->discount_total,2) }}</strong></div>
                <div class="party-detail">Grand Total: <strong>{{ number_format($quote->grand_total,2) }}</strong></div>
              </div>
            </div>
          </div>
        </div>

        <div class="invoice-items table-responsive">
          <table class="table invoice-items-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Product/Description</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price ({{ $currencySymbol }})</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Line Total ({{ $currencySymbol }})</th>
              </tr>
            </thead>
            <tbody>
              @foreach($quote->items as $i => $it)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($it->product)->name ?? '' }}@if($it->description) - {{ $it->description }}@endif</td>
                <td class="text-end">{{ $it->qty }}</td>
                <td class="text-end">{{ number_format($it->unit_price,2) }}</td>
                <td class="text-end">@if($it->discount_percent>0){{ number_format($it->discount_percent,2) }}%@else{{ number_format($it->discount_amount,2) }}@endif</td>
                <td class="text-end">{{ number_format($it->line_total,2) }}</td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                <td class="text-end"><strong>{{ number_format($quote->subtotal,2) }}</strong></td>
              </tr>
              <tr>
                <td colspan="5" class="text-end"><strong>Discount Total</strong></td>
                <td class="text-end"><strong>{{ number_format($quote->discount_total,2) }}</strong></td>
              </tr>
              <tr class="grandtotal-row">
                <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
                <td class="text-end"><strong>{{ number_format($quote->grand_total,2) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>

        @if(!empty($quote->notes))
        <div class="invoice-remarks mt-2">
          <strong>Notes:</strong>
          <div>{{ $quote->notes }}</div>
        </div>
        @endif

        <div class="amount-in-words mt-2">
          <strong>Amount in Words:</strong>
          <span>
            @if($currencyName){{ $currencyName }} @endif{{ $amountWords }}
            @if($amountFrac > 0) and {{ $amountFrac }}/100 @endif
            Only
          </span>
        </div>

        <div class="row mt-2 mb-1">
          <div class="col-12">
            <div class="signature-boxes">
              <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Customer's Signature</div>
              </div>
              <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Signature</div>
              </div>
            </div>
          </div>
        </div>

        <div class="invoice-footer mt-4">
          <div style="text-align:center; font-size:0.92rem; color:#333;">{{ $b && $b->invoiceFooter ? $b->invoiceFooter : '' }}</div>
        </div>

        <div class="d-flex justify-content-between align-items-center no-print mt-3">
          <div></div>
          <div class="action-buttons">
            @if($quote->status === 'draft')
            <a class="btn btn-warning btn-sm" href="{{ route('quotation.edit', ['id'=>$quote->id]) }}"><i class="las la-pen"></i> Edit</a>
            @endif
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="{{ route('quotation.print', ['id'=>$quote->id]) }}"><i class="las la-print"></i> Print</a>
            <a class="btn btn-primary btn-sm" href="{{ route('quotation.list') }}"><i class="las la-arrow-left"></i> Back to Quotations</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
#rn-invoice-root { background:#fff; max-width:980px; margin:0 auto; box-shadow: 0 0 20px rgba(0,0,0,.1); border:1px solid #e5e7eb; border-radius:8px; }
#rn-invoice-root .card-body { padding:20px; }
.invoice-header { margin-bottom:14px; }
.invoice-logo { height:70px; width:auto; margin-bottom:10px; object-fit:contain; }
.company-name { font-size:1.5rem; font-weight:700; color:#2c3e50; margin-bottom:6px; }
.invoice-title { font-size:2rem; font-weight:800; color:#111827; margin:0 0 6px 0; }
.invoice-info-table td { padding:2px 6px; font-size:.9rem; }
.invoice-divider { border:0; border-top:1px solid #e5e7eb; margin:10px 0; }
.invoice-parties .party-box { border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px; background:#fafafa; }
.party-label { font-weight:700; color:#374151; margin-bottom:6px; }
.party-name { font-weight:600; }
.party-detail { font-size:.9rem; color:#4b5563; }
.invoice-items-table { width:100%; border-collapse:collapse; font-size:.95rem; }
.invoice-items-table th, .invoice-items-table td { border:1px solid #dee2e6; padding:0; }
.invoice-items-table { margin-bottom:0; }
.invoice-items.table-responsive { padding:0; }
.invoice-items-table thead { background:#f3f4f6; }
.grandtotal-row td { background:transparent; color:inherit; border-top:2px solid #333; font-weight:700; }
.amount-in-words { margin-top:8px; font-size:.95rem; }
.signature-boxes { display:flex; justify-content:space-between; gap:18px; align-items:flex-end; }
.signature-box { flex:1; text-align:center; }
.signature-line { width:60%; height:28px; border-bottom:1px solid #888; margin:0 auto 4px; }
.signature-label { font-size:.75rem; font-weight:600; color:#444; text-transform:uppercase; }
.action-buttons { display:flex; gap:8px; align-items:center; }

@media print {
  body * { visibility:hidden; }
  #rn-invoice-root, #rn-invoice-root * { visibility:visible; }
  html, body { margin:0; padding:0; }
  #rn-invoice-root { box-shadow:none !important; border:none !important; max-width:100% !important; }
  .no-print { display:none !important; }
}
</style>
@endsection
