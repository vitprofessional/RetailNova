@extends('include')
@section('backTitle') Invoice @endsection
@section('container')

<div class="card" id="rn-invoice-root">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div class="d-flex align-items-center">
        @php $b = isset($business) ? $business : null; @endphp
        @if($b && !empty($b->businessLogo))
          @php
            $logoUrl = (strpos($b->businessLogo, 'http') === 0) ? $b->businessLogo : asset('uploads/' . ltrim($b->businessLogo, '/'));
          @endphp
          <img src="{{ $logoUrl }}" alt="Logo" style="height:56px; width:auto; margin-right:12px; object-fit:contain;" onerror="this.style.display='none'">
        @else
          <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height:56px; width:auto; margin-right:12px; object-fit:contain;" onerror="this.style.display='none'">
        @endif
        <div>
          <h5 class="mb-0">{{ $b && $b->businessName ? $b->businessName : 'Computer Care' }}</h5>
          <small class="text-muted">{{ $b && $b->businessLocation ? $b->businessLocation : 'Office Road, Burichong Bazar, Cumilla' }} · {{ $b && $b->mobile ? $b->mobile : '0123456789' }} @if($b && $b->email) · {{ $b->email }} @else · info@computercare.com @endif</small>
          @if($b && $b->website) <div><small class="text-muted">{{ $b->website }}</small></div> @endif
        </div>
      </div>
      <div class="text-end">
        <h3 class="mb-0">INVOICE</h3>
        <div class="text-muted">#{{ $invoice->invoice }}</div>
        <div class="text-muted">{{ \Carbon\Carbon::parse($invoice->date)->format('d M, Y') }}</div>
      </div>
      <div class="ms-3 text-end no-print">
        {{-- QR code for quick verification (hidden from interactive header spacing) --}}
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('invoiceGenerate',['id'=>$invoice->id]).'?'.$invoice->invoice) }}" alt="QR" style="width:90px; height:90px; object-fit:contain;">
      </div>
      <div class="ms-3 text-end only-print">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('invoiceGenerate',['id'=>$invoice->id]).'?'.$invoice->invoice) }}" alt="QR" style="width:90px; height:90px; object-fit:contain;">
      </div>
    </div>

    <div class="row mb-3 invoice-meta">
      <div class="col-6">
        <h6 class="mb-1">Billed To</h6>
        <div><strong>{{ $customer->name ?? '-' }}</strong></div>
        <div class="text-muted">{{ $customer->address ?? '' }}</div>
        <div class="text-muted">{{ $customer->mobile ?? '' }}</div>
        @if(!empty($customer->email))<div class="text-muted">{{ $customer->email }}</div>@endif
      </div>
      <div class="col-6 text-end">
        <h6 class="mb-1">Payment Info</h6>
        <div>Previous Due: @money($invoice->prevDue ?? 0)</div>
      </div>
    </div>

    <div class="table-responsive mb-3">
      <table class="table table-striped table-bordered invoice-table" style="width:100%;">
        <colgroup>
          <col class="col-no" style="width:5%">
          <col class="col-item" style="width:56%">
          <col class="col-qty" style="width:9%">
          <col class="col-unit" style="width:15%">
          <col class="col-line" style="width:15%">
        </colgroup>
        <thead class="small text-uppercase text-muted">
          <tr>
            <th class="col-no" style="width:48px;">#</th>
            <th class="col-item">Item</th>
            <th class="text-end col-qty" style="width:90px;">Qty</th>
            <th class="text-end col-unit" style="width:140px;">Unit Price</th>
            <th class="text-end col-line" style="width:140px;">Line Total</th>
          </tr>
        </thead>
        <tbody>
          @php $sl = 1; $subtotal = 0; @endphp
          @forelse($items as $item)
            @php $line = (float)($item->totalSale ?? ($item->salePrice * $item->qty)); $subtotal += $line; @endphp
            <tr>
              <td>{{ $sl++ }}</td>
              <td>
                {{ $item->productName }}
                @php
                  $serials = isset($serialsByPurchase) ? ($serialsByPurchase[$item->purchaseId] ?? collect()) : collect();
                @endphp
                @if($serials->count() > 0)
                  <div class="text-muted small mt-1">Serials: {{ $serials->pluck('serialNumber')->join(', ') }}</div>
                @endif
              </td>
              <td class="text-end">{{ $item->qty }}</td>
              <td class="text-end">@money($item->salePrice ?? 0)</td>
              <td class="text-end">@money($line)</td>
            </tr>
          @empty
            <tr><td colspan="5">No items found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end mb-4">
      <div class="totals-block" style="min-width:300px;">
        <div class="d-flex justify-content-between line">
          <div class="key">Subtotal</div>
          <div class="value">@money($subtotal)</div>
        </div>
        <div class="d-flex justify-content-between line">
          <div class="key">Discount</div>
          <div class="value">@money($invoice->discountAmount ?? 0)</div>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between line grand">
          <div class="key">Grand Total</div>
          <div class="value">@money($invoice->grandTotal ?? $subtotal)</div>
        </div>
        <div class="d-flex justify-content-between line">
          <div class="key">Paid</div>
          <div class="value">@money($invoice->paidAmount ?? 0)</div>
        </div>
        <div class="d-flex justify-content-between line">
          <div class="key">Current Due</div>
          <div class="value">@money($invoice->curDue ?? 0)</div>
        </div>
        <div class="d-flex justify-content-between line grand">
          <div class="key">Total Outstanding (incl. prev. due)</div>
          <div class="value">@money(($invoice->curDue ?? 0) + ($invoice->prevDue ?? 0))</div>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <strong>Notes</strong>
      <div class="text-muted">{{ $invoice->note ?? 'Thank you for your business. Payment is due within 15 days.' }}</div>
    </div>

    <!-- Invoice footer: appears on both view and print -->
    <div class="invoice-footer mt-4">
      <hr style="border-color:#ddd">
      <div style="text-align:center; font-size:0.92rem; color:#333;">{{ $business && $business->invoiceFooter ? $business->invoiceFooter : 'Thank you for your business. Visit us at ' . config('app.url', '/') }}</div>
      <div style="text-align:center; font-size:0.82rem; color:#666; margin-top:6px;">Powered by {{ config('app.name', env('APP_NAME', 'POS')) }}</div>
    </div>

    <div class="d-flex justify-content-between align-items-center no-print mt-3">
      <div></div>
      <div>
        <button class="btn btn-outline-secondary btn-sm" onclick="printInvoice()">Print</button>
        <a class="btn btn-primary btn-sm" href="{{ route('saleList') }}">Back to Sales Page</a>
      </div>
    </div>
  </div>
</div>

<style>
  /* Invoice footer visible on both view and print */
  .invoice-footer { display: block; }

  /* Invoice print styles */
  @media print {
    /* Hide everything then reveal only invoice root and footer to avoid printing page chrome */
    body * { visibility: hidden; }
    #rn-invoice-root, #rn-invoice-root * { visibility: visible; }
    .invoice-footer, .invoice-footer * { visibility: visible; }
    /* Keep invoice in normal flow to avoid forcing an extra blank page */
    html, body { height: auto; }
    #rn-invoice-root { box-shadow: none !important; border: none !important; margin: 0; padding: 0; position: static; width: 100%; page-break-after: avoid; }
    /* Reduce default spacing to fit content and avoid page overflow */
    #rn-invoice-root .card-body { padding-top: 6px !important; padding-bottom: 6px !important; }
    #rn-invoice-root, #rn-invoice-root * { color: #000 !important; -webkit-print-color-adjust: exact; }
    /* Hide interactive elements explicitly marked as no-print */
    .no-print { display: none !important; }
    .only-print { display: block !important; }
    /* Ensure the invoice table and footer print with visible borders and avoid page-splitting */
    .invoice-table thead th, .invoice-table tbody td { border: 1px solid #222 !important; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    /* Invoice footer styling: minimal gap so it doesn't force a new page */
    .invoice-footer { display: block; visibility: visible; position: relative; margin-top: 3mm; page-break-inside: avoid; }
    /* Reduce page margins slightly to maximize content per page */
    @page { margin: 8mm 8mm 8mm 8mm; }
  }
  .invoice-box{font-family: Arial, Helvetica, sans-serif}
  .table th, .table td{vertical-align: middle}
</style>

<style>
  /* Invoice layout tweaks */
  .invoice-meta .col-6{padding-left:0;padding-right:0}
  .invoice-table { width: 100% !important; table-layout: fixed; }
  .invoice-table th.col-item, .invoice-table td.col-item { width: 56%; white-space: normal; word-break: break-word; }
  .invoice-table td, .invoice-table th { white-space: nowrap; }
  .invoice-table td.col-item, .invoice-table th.col-item { white-space: normal; }
  .totals-block .line { padding: 2px 0; }
  .totals-block .key { color: #555; }
  .totals-block .value { font-weight: 600; }
  .totals-block .grand .value { font-weight: 700; font-size: 1.05rem; }
  .totals-block .grand .key { font-weight: 600; }
  .only-print { display: none; }
  /* Ensure print uses full width table */
  @media print {
    .invoice-table { width: 100% !important; }
    .invoice-meta .col-6{display:inline-block; float:left; width:50%;}
  }
</style>

@endsection

@section('scripts')
@parent
<script>
function collectStyles() {
  var html = '';
  // copy all linked stylesheets
  var links = document.querySelectorAll('link[rel="stylesheet"]');
  links.forEach(function(l){ html += l.outerHTML; });
  // copy inline styles
  var styles = document.querySelectorAll('style');
  styles.forEach(function(s){ html += s.outerHTML; });
  return html;
}

function printInvoice(){
  try{
    var root = document.getElementById('rn-invoice-root');
    var footer = document.querySelector('.print-only-footer');
    if(!root) return window.print();
    var content = root.outerHTML + (footer ? footer.outerHTML : '');
    var styleHtml = collectStyles();
    var w = window.open('', '_blank');
    if(!w) return alert('Popup blocked. Allow popups for this site to print.');
    var doc = w.document;
    doc.open();
    doc.write('<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">');
    doc.write(styleHtml);
    // small print CSS to ensure full-bleed table and hide interactive
    doc.write('<style>body{margin:0;padding:8mm;color:#000;font-family:Arial,Helvetica,sans-serif} .no-print{display:none !important} .invoice-table{width:100%;table-layout:fixed} .invoice-table th,.invoice-table td{border:1px solid #222;padding:.35rem}</style>');
    doc.write('</head><body>');
    doc.write(content);
    doc.write('</body></html>');
    doc.close();
    w.focus();
    // Give the new window a moment to render before printing
    setTimeout(function(){ try{ w.print(); setTimeout(function(){ w.close(); }, 500); }catch(e){ console.warn('print failed', e); } }, 250);
  }catch(e){ console.warn('printInvoice error', e); window.print(); }
}
</script>
@endsection
