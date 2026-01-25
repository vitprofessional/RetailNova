<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Quotation {{ $quote->quote_number }}</title>
  <style>
    @page{ size: A4 portrait; margin: 10mm; }
    body{ font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
    .wrap{ max-width: 980px; margin:0 auto; }
    .row{ display:flex; flex-wrap:wrap; }
    .col-8{ width:66.666%; }
    .col-4{ width:33.333%; }
    .text-end{ text-align: right; }
    .mb-6{ margin-bottom: 12px; }
    .company-name{ font-size: 20px; font-weight: 700; color:#2c3e50; margin: 6px 0; }
    .invoice-title{ font-size: 28px; font-weight: 800; margin: 0 0 6px 0; }
    .logo{ height:70px; width:auto; object-fit:contain; margin-bottom:6px; }
    .info-table td{ padding:2px 6px; }
    .divider{ border:0; border-top:1px solid #e5e7eb; margin:10px 0; }
    .party-box{ border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px; background:#fafafa; }
    .party-label{ font-weight:700; margin:0 0 6px 0; }
    .table{ width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .table th, .table td{ border: 1px solid #ddd; padding: 0; }
    .table thead{ background:#f3f4f6; }
    .grand{ background:transparent; color:inherit; }
    .table tfoot tr.grand td{ border-top:2px solid #333; font-weight:bold; }
    .flex{ display:flex; justify-content: space-between; align-items: flex-start; }
    .sig-line{ height:26px; border-bottom:1px solid #888; margin-bottom:4px; }
  </style>
</head>
<body>
  <style>
    @page{ size: A4 portrait; margin: 10mm; }
    body{ font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
    #rn-invoice-root { background:#fff; max-width:980px; margin:0 auto; }
    #rn-invoice-root .card-body { padding:20px; }
    .row{ display:flex; flex-wrap:wrap; }
    .col-md-8{ width:66.666%; }
    .col-md-4{ width:33.333%; }
    .text-end{ text-align: right; }
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
    .invoice-items-table { width:100%; border-collapse:collapse; font-size:.95rem; margin-bottom:0; }
    .invoice-items-table thead { background:#f3f4f6; }
    .invoice-items-table th, .invoice-items-table td { border:1px solid #dee2e6; padding:0; }
    .grandtotal-row td { background:transparent; color:inherit; border-top:2px solid #333; font-weight:700; }
    .signature-boxes { display:flex; justify-content:space-between; gap:18px; align-items:flex-end; }
    .signature-box { flex:1; text-align:center; }
    .signature-line { width:60%; height:28px; border-bottom:1px solid #888; margin:0 auto 4px; }
    .signature-label { font-size:.75rem; font-weight:600; color:#444; text-transform:uppercase; }
    .amount-in-words { margin-top:8px; font-size:.95rem; }
  </style>
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
  <div id="rn-invoice-root">
    <div class="card-body">
      <div class="invoice-header">
        <div class="row align-items-start">
          <div class="col-md-8">
            @if($logoUrl)
              <img src="{{ $logoUrl }}" alt="Logo" class="invoice-logo" onerror="this.style.display='none'">
            @endif
            <h4 class="company-name">{{ optional($business)->businessName ?? config('app.name') }}</h4>
            <div>{{ optional($business)->businessLocation }}</div>
            @if(!empty($business) && !empty($business->mobile))<div>Phone: {{ $business->mobile }}</div>@endif
            @if(!empty($business) && !empty($business->email))<div>Email: {{ $business->email }}</div>@endif
          </div>
          <div class="col-md-4 text-end">
            <h2 class="invoice-title">Quotation</h2>
            <table class="invoice-info-table" style="float:right;">
              <tr><td><strong>No:</strong></td><td>{{ $quote->quote_number }}</td></tr>
              <tr><td><strong>Date:</strong></td><td>{{ optional($quote->date)->format('d-M-Y') }}</td></tr>
              <tr><td><strong>Validity:</strong></td><td>{{ $quote->validity_days }} days</td></tr>
              <tr><td><strong>Status:</strong></td><td>{{ ucfirst($quote->status) }}</td></tr>
            </table>
          </div>
        </div>
      </div>
      <hr class="invoice-divider" />

    <div class="row" style="gap:3.333%;">
      <div style="width:48.333%;">
        <div class="party-box">
          <div class="row invoice-parties" style="gap:3.333%;">
          <div><strong>{{ $customer->name ?? 'Walking Customer' }}</strong></div>
          @php
                <h6 class="party-label">BILL TO:</h6>
            if(!empty($customer)){
              if(!empty($customer->area)){ $addr = trim($customer->area); }
              elseif(!empty($customer->address)){ $addr = trim($customer->address); }
            }
          @endphp
          @if(!empty($addr))<div>{{ $addr }}</div>@endif
          @if(!empty($customer) && !empty($customer->mobile))<div>Contact: {{ $customer->mobile }}</div>@endif
        </div>
                @if(!empty($addr))<div class="party-detail">{{ $addr }}</div>@endif
                @if(!empty($customer) && !empty($customer->mobile))<div class="party-detail">Contact: {{ $customer->mobile }}</div>@endif
        <div class="party-box">
          <div class="party-label">QUOTATION INFORMATION:</div>
          <div>Subtotal: <strong>{{ number_format($quote->subtotal,2) }}</strong></div>
          <div>Discount: <strong>{{ number_format($quote->discount_total,2) }}</strong></div>
                <h6 class="party-label">QUOTATION INFORMATION:</h6>
                <div class="party-detail">Subtotal: <strong>{{ number_format($quote->subtotal,2) }}</strong></div>
                <div class="party-detail">Discount: <strong>{{ number_format($quote->discount_total,2) }}</strong></div>
                <div class="party-detail">Grand Total: <strong>{{ number_format($quote->grand_total,2) }}</strong></div>

  <table class="table" style="margin-top:10px;">
    <thead>
      <tr>
        <div class="invoice-items table-responsive">
          <table class="table invoice-items-table" style="margin-top:10px;">
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
      <tr><td colspan="5" class="text-end"><strong>Subtotal</strong></td><td class="text-end"><strong>{{ number_format($quote->subtotal,2) }}</strong></td></tr>
      <tr><td colspan="5" class="text-end"><strong>Discount Total</strong></td><td class="text-end"><strong>{{ number_format($quote->discount_total,2) }}</strong></td></tr>
      <tr class="grand"><td colspan="5" class="text-end"><strong>Grand Total</strong></td><td class="text-end"><strong>{{ number_format($quote->grand_total,2) }}</strong></td></tr>
    </tfoot>
  </table>
            <tr class="grandtotal-row"><td colspan="5" class="text-end"><strong>Grand Total</strong></td><td class="text-end"><strong>{{ number_format($quote->grand_total,2) }}</strong></td></tr>
  @if(!empty($quote->notes))
          </table>
        </div>
  @endif
  <div style="margin-top:8px;"><strong>Amount in Words:</strong> <span>@if($currencyName){{ $currencyName }} @endif{{ $amountWords }}@if($amountFrac>0) and {{ $amountFrac }}/100 @endif Only</span></div>
  

        <div class="amount-in-words"><strong>Amount in Words:</strong> <span>@if($currencyName){{ $currencyName }} @endif{{ $amountWords }}@if($amountFrac>0) and {{ $amountFrac }}/100 @endif Only</span></div>
  
        <div class="invoice-footer" style="margin-top:14px;">
          <div style="text-align:center; font-size:0.92rem; color:#333;">{{ optional($business)->invoiceFooter }}</div>
        </div>
    <div style="width:45%; text-align:center;">
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
</body>
</html>
