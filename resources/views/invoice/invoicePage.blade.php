@extends('include')
@section('backTitle') Invoice @endsection
@section('container')

@php
  // Fallback: ensure helper is loaded even if autoload caches are stale
  if (!function_exists('numberToWords')) {
    $helperPath = app_path('Support/helpers.php');
    if (file_exists($helperPath)) {
      @require_once $helperPath;
    }
  }
  // Last-resort inline converter to avoid view breakage if autoload/opcache is stale
  if (!function_exists('numberToWords')) {
    function numberToWords($number) {
      $number = (int) $number;
      if ($number === 0) return 'zero';
      $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
      $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
      $scales = ['', 'thousand', 'million', 'billion', 'trillion'];
      $words = [];
      $scaleIndex = 0;
      if ($number < 0) { $words[] = 'minus'; $number = abs($number); }
      while ($number > 0) {
        $chunk = $number % 1000;
        if ($chunk > 0) {
          $chunkWords = [];
          $hundreds = (int)($chunk / 100);
          if ($hundreds > 0) $chunkWords[] = $ones[$hundreds] . ' hundred';
          $remainder = $chunk % 100;
          if ($remainder >= 20) {
            $chunkWords[] = $tens[(int)($remainder/10)];
            if ($remainder % 10) $chunkWords[] = $ones[$remainder % 10];
          } elseif ($remainder > 0) {
            $chunkWords[] = $ones[$remainder];
          }
          if ($scaleIndex > 0 && $scales[$scaleIndex]) $chunkWords[] = $scales[$scaleIndex];
          $words[] = implode(' ', $chunkWords);
        }
        $number = (int)($number / 1000);
        $scaleIndex++;
      }
      return implode(' ', array_reverse($words));
    }
  }
@endphp

@php
  // Ensure business context is available across the view
  $b = $business ?? null;
@endphp

@php
  // Compute payment summary variables globally for reuse
  $paidAmount = (float)($invoice->paidAmount ?? 0);
  $grandTotal = (float)($invoice->grandTotal ?? 0);
  $currentDue = (float)($invoice->curDue ?? max($grandTotal - $paidAmount, 0));
  if ($currentDue <= 0 && $grandTotal > 0) { $paymentStatus = 'PAID'; $paymentBadge = 'success'; }
  elseif ($grandTotal <= 0) { $paymentStatus = 'PAID'; $paymentBadge = 'success'; }
  elseif ($paidAmount <= 0 && $currentDue > 0) { $paymentStatus = 'DUE'; $paymentBadge = 'danger'; }
  elseif ($paidAmount > 0 && $currentDue > 0) { $paymentStatus = 'PARTIAL'; $paymentBadge = 'warning'; }
  else { $paymentStatus = 'PAID'; $paymentBadge = 'success'; }
@endphp

@php
  $printVariant = strtolower((string) request()->query('printer', 'a4'));
  if(!in_array($printVariant, ['a4', 'thermal80', 'thermal58'], true)){
    $printVariant = 'a4';
  }
  $isThermal = in_array($printVariant, ['thermal80', 'thermal58'], true);
@endphp

<div class="card printer-shell" id="rn-invoice-root" data-printer-variant="{{ $printVariant }}">
  <div class="card-body">
    <div id="invoiceBodyA4" class="invoice-body-block {{ $isThermal ? 'd-none' : '' }}">
    <!-- Professional Header -->
    <div class="invoice-header">
      <div class="row align-items-start justify-content-between">
        <div class="col-md-6">
          <div class="d-flex align-items-start">
            <div class="me-3">
              @php
                $logoUrl = null;
                if($b && !empty($b->businessLogo)){
                  $logoUrl = (strpos($b->businessLogo, 'http') === 0)
                    ? $b->businessLogo
                    : asset('uploads/business/' . ltrim($b->businessLogo, '/'));
                }
              @endphp
              @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="invoice-logo" onerror="this.style.display='none'">
              @else
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="invoice-logo" onerror="this.style.display='none'">
              @endif
            </div>
            <div class="company-info">
              @php
                $companyLogoUrl = null;
                if($b && !empty($b->businessLogo)){
                  $companyLogoUrl = (strpos($b->businessLogo, 'http') === 0)
                    ? $b->businessLogo
                    : asset('public/uploads/business/' . ltrim($b->businessLogo, '/'));
                }
              @endphp
              @if($companyLogoUrl)
                <img src="{{ $companyLogoUrl }}" alt="Logo" class="company-logo-small" onerror="this.style.display='none'">
              @endif
              <h4 class="company-name">{{ $b && $b->businessName ? $b->businessName : 'Computer Care' }}</h4>
              <div class="company-details">
                <div>{{ $b && $b->businessLocation ? $b->businessLocation : 'Office Road, Burichong Bazar, Cumilla' }}</div>
                <div>Phone: {{ $b && $b->mobile ? $b->mobile : '0123456789' }}</div>
                @if($b && $b->email)<div>Email: {{ $b->email }}</div>@else<div>Email: info@computercare.com</div>@endif
                @if($b && $b->website)<div>Web: {{ $b->website }}</div>@endif
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h2 class="invoice-title">Invoice</h2>
              <table class="invoice-info-table">
                <tr>
                  <td><strong>Invoice No:</strong></td>
                  <td>{{ $invoice->invoice }}</td>
                </tr>
                <tr>
                  <td><strong>Date:</strong></td>
                  <td>{{ \Carbon\Carbon::parse($invoice->date)->format('d-M-Y') }}</td>
                </tr>
                <tr>
                  <td><strong>Status:</strong></td>
                  <td><span class="badge badge-{{ $paymentBadge }}">{{ $paymentStatus }}</span></td>
                </tr>
              </table>
            </div>
            <div class="invoice-qr-section ms-3">
              @php
                $companyName = $b && $b->businessName ? $b->businessName : 'Computer Care';
                $invoiceNo = $invoice->invoice ?? '';
                $dateIso = \Carbon\Carbon::parse($invoice->date)->format('Y-m-d');
                $grandTotalVal = number_format((float)$grandTotal, 2);
                $dueVal = number_format((float)$currentDue, 2);
                $statusVal = $paymentStatus;
                // Human-readable, newline-separated payload for easy scanning
                $qrPayload = "Company: {$companyName}\nInvoice: {$invoiceNo}\nDate: {$dateIso}\nGrand Total: {$grandTotalVal}\nDue: {$dueVal}\nStatus: {$statusVal}";
              @endphp
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($qrPayload) }}" alt="QR Code" class="invoice-qr">
            </div>
          </div>
        </div>
      </div>
    </div>

    <hr class="invoice-divider">

    <!-- Customer Information -->
    <div class="row mb-4 invoice-parties">
      <div class="col-md-6">
        <div class="party-box">
          <h6 class="party-label">BILL TO:</h6>
          <div class="party-content">
            <div class="party-name">{{ $customer->name ?? '-' }}</div>
            @php 
              $custAddress = '';
              if(!empty($customer)){
                // Show only area or address; exclude city/state/country/email
                if(!empty($customer->area)){
                  $custAddress = trim($customer->area);
                } elseif(!empty($customer->address)) {
                  $custAddress = trim($customer->address);
                }
              }
            @endphp
            @if(!empty($custAddress))<div class="party-detail">{{ $custAddress }}</div>@endif
            @if(!empty($customer) && !empty($customer->mobile))<div class="party-detail">Contact: {{ $customer->mobile }}</div>@endif
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="party-box">
          <h6 class="party-label">PAYMENT INFORMATION:</h6>
          <div class="party-content">
            <div class="party-detail">Previous Due: <strong>@money($invoice->prevDue ?? 0)</strong></div>
            <div class="party-detail">Payment Mode: <strong>{{ ucfirst($invoice->paymentMode ?? 'Cash') }}</strong></div>
            @php
              $billBy = null;
              try{
                  if(isset($invoice->salespersonId) && $invoice->salespersonId){
                      $billBy = \App\Models\AdminUser::find($invoice->salespersonId);
                  }
              }catch(\Throwable $_){ $billBy = null; }
            @endphp
            @if($billBy)
              <div class="party-detail">Bill Generated By: <strong>{{ $billBy->fullName ?? ($billBy->mail ?? 'Staff') }}</strong></div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Professional Items Table -->
    <div class="table-responsive mb-2">
      <table class="table invoice-items-table">
        <thead>
          <tr>
            <th style="width:5%;">#</th>
            <th style="width:15%;">Code</th>
            <th style="width:43%;">Product Name</th>
            <th style="width:8%;" class="text-center">Qty</th>
            <th style="width:10%;" class="text-center">Warranty (days)</th>
            <th style="width:12%;" class="text-end">Price/Unit</th>
            <th style="width:17%;" class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          @php $sl = 1; $subtotal = 0; @endphp
          @forelse($items as $item)
            @php $line = (float)($item->totalSale ?? ($item->salePrice * $item->qty)); $subtotal += $line; @endphp
            <tr>
              <td>{{ $sl++ }}</td>
              <td>{{ $item->productCode ?? '-' }}</td>
              <td>
                <span class="product-name">{{ $item->productName }}</span>
                @php
                  $serials = isset($serialsByPurchase) ? ($serialsByPurchase[$item->purchaseId] ?? collect()) : collect();
                @endphp
                @if($serials->count() > 0)
                  <div class="serial-info">S/N: {{ $serials->pluck('serialNumber')->join(', ') }}</div>
                @endif
              </td>
              <td class="text-center">{{ $item->qty }}</td>
              <td class="text-center">{{ $item->warranty_days ?? '-' }}</td>
              <td class="text-end">@money($item->salePrice ?? 0)</td>
              <td class="text-end fw-normal">@money($line)</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center">No items found</td></tr>
          @endforelse
        </tbody>
        <tfoot>
          @if(($invoice->discountAmount ?? 0) > 0 or ($invoice->discountType ?? ''))
            @if(($invoice->additionalChargeAmount ?? 0) > 0)
            <tr class="addcharge-row">
              <td colspan="6" class="text-end"><strong>{{ $invoice->additionalChargeName ? e($invoice->additionalChargeName) : 'Additional Charge' }}:</strong></td>
              <td class="text-end">@money($invoice->additionalChargeAmount ?? 0)</td>
            </tr>
            @endif
            <tr class="subtotal-row">
              <td colspan="6" class="text-end"><strong>Total Amount:</strong></td>
              <td class="text-end">@money($subtotal + ($invoice->additionalChargeAmount ?? 0))</td>
            </tr>
            @if(($invoice->discountAmount ?? 0) > 0)
            <tr class="discount-row">
              <td colspan="6" class="text-end"><strong>Discount:</strong></td>
              <td class="text-end">@money($invoice->discountAmount ?? 0)</td>
            </tr>
            @endif
          @endif
          <tr class="grandtotal-row">
            <td colspan="6" class="text-end"><strong>Grand Total (BDT):</strong></td>
            <td class="text-end"><strong>@money($invoice->grandTotal ?? $subtotal)</strong></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Collection Details -->
    <div class="row mb-3">
      <div class="col-md-8">
        <div class="amount-in-words">
          <strong>Invoice Amount In Words:</strong>
          <div class="words-text">{{ ucwords(\Illuminate\Support\Str::title(numberToWords($invoice->grandTotal ?? $subtotal))) }} Taka Only</div>
        </div>
        
        @php 
          $showTerms = ($b && isset($b->invoice_terms_enabled)) ? (bool)$b->invoice_terms_enabled : true; 
          $termsText = ($b && !empty($b->invoice_terms_text)) 
                        ? nl2br(e($b->invoice_terms_text)) 
                        : "• Thanks for doing business with us.<br>• Warranty doesn't cover any physical damage, burn, water damage to the product or warranty sticker removed.<br>• Payment is due within 15 days from the date of invoice.<br>• Goods once sold cannot be returned or exchanged.";
        @endphp
        @if($showTerms)
        <div class="terms-conditions mt-2">
          <strong>Terms & Conditions:</strong>
          <div class="terms-text">{!! $termsText !!}</div>
        </div>
        @endif
        
        @if(!empty($invoice->note))
        <div class="invoice-remarks mt-2">
          <strong>Remarks:</strong>
          <div>{{ $invoice->note }}</div>
        </div>
        @endif
      </div>
      <div class="col-md-4">
        <div class="collection-detail-box">
          <h6 class="collection-title">Payment Summary (BDT)</h6>
          <table class="collection-table">
            <tr>
              <td class="label">Status</td>
              <td class="value"><span class="badge badge-{{ $paymentBadge }}">{{ $paymentStatus }}</span></td>
            </tr>
            <tr>
              <td class="label">Payment Mode</td>
              <td class="value">{{ ucfirst($invoice->paymentMode ?? 'Cash') }}</td>
            </tr>
            <tr>
              <td class="label">Received Amount</td>
              <td class="value">@money($invoice->paidAmount ?? 0)</td>
            </tr>
            <tr class="due-row">
              <td class="label">Outstanding Due</td>
              <td class="value text-danger fw-bold">@money($invoice->curDue ?? 0)</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    @php
      // Walk-in and config-driven display toggles for signatures and acknowledgement
      $isWalking = isset($customer) && is_string($customer->name ?? null) && strcasecmp(trim($customer->name), 'Walking Customer') === 0;
      $hideAckByBusiness = $b && isset($b->hide_invoice_acknowledgement) ? (bool) $b->hide_invoice_acknowledgement : false;
      $hideAck = $hideAckByBusiness || ($isWalking && config('pos.hide_ack_walkin', true));
      $hideSignatures = $isWalking && config('pos.hide_signatures_walkin', true);
    @endphp
    @if(!$hideSignatures)
    <!-- Signatures -->
    <div class="row mt-2 mb-1">
      <div class="col-12">
        <div class="signature-boxes">
          <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Customer's Signature</div>
          </div>
          <div class="signature-box">
            <div class="signature-line authorized-signature">
              @if($b && !empty($b->stamp))
                <img src="{{ asset('uploads/' . ltrim($b->stamp, '/')) }}" alt="Stamp" class="signature-stamp" onerror="this.style.display='none'">
              @endif
            </div>
            <div class="signature-label">Authorized Signature</div>
          </div>
        </div>
      </div>
    </div>
    @endif
    <!-- Invoice footer: appears on both view and print -->
    <div class="invoice-footer mt-4">
      <div style="text-align:center; font-size:0.92rem; color:#333;">{{ $business && $business->invoiceFooter ? $business->invoiceFooter : 'Thank you for your business. Visit us at ' . config('app.url', '/') }}</div>
      <div style="text-align:center; font-size:0.82rem; color:#666; margin-top:6px;">Powered by {{ config('app.name', env('APP_NAME', 'POS')) }}</div>
    </div>

      @if(!$hideAck)
      <!-- Acknowledgement Section (placed after footer per reference) -->
      <div id="acknowledgementSection" class="acknowledgement-section">
        <div class="ack-separator-wrap">
          <i class="las la-cut ack-separator-icon" aria-hidden="true"></i>
          <hr class="ack-separator">
        </div>
        <div class="ack-title text-center">ACKNOWLEDGMENT</div>
        <div class="row ack-grid">
          <div class="col-md-4">
            <div class="ack-card">
              @php
                $ackLogoUrl = null;
                if($b && !empty($b->businessLogo)){
                  $ackLogoUrl = (strpos($b->businessLogo, 'http') === 0)
                    ? $b->businessLogo
                    : asset('public/uploads/business/' . ltrim($b->businessLogo, '/'));
                }
              @endphp
              @if($ackLogoUrl)
                <img src="{{ $ackLogoUrl }}" alt="Logo" class="ack-logo" onerror="this.style.display='none'">
              @endif
              <div class="ack-company">{{ $b && $b->businessName ? $b->businessName : 'Computer Care' }}</div>
              <div class="ack-address">{{ $b && $b->businessLocation ? $b->businessLocation : 'Office Road, Burichong Bazar, Cumilla' }}</div>
              <div class="ack-details">
                <div>Phone: {{ $b && $b->mobile ? $b->mobile : '0123456789' }}</div>
                @if($b && $b->email)
                  <div>Email: {{ $b->email }}</div>
                @else
                  <div>Email: info@computercare.com</div>
                @endif
                @if($b && $b->website)
                  <div>Web: {{ $b->website }}</div>
                @endif
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ack-card">
              <div class="ack-meta ack-meta-block">
                <div><strong>Invoice No:</strong> {{ $invoice->invoice }}</div>
                <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d-M-Y') }}</div>
                <div><strong>Grand Total:</strong> @money($grandTotal)</div>
                <div><strong>Due:</strong> @money($currentDue)</div>
                <div><strong>Status:</strong> <span class="badge badge-{{ $paymentBadge }}">{{ $paymentStatus }}</span></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ack-card">
              <h4 class="fw-bold">Bill To:</h4>
              <div class="ack-customer-name">{{ $customer->name ?? '-' }}</div>
              @php 
                $ackAddress = '';
                if(!empty($customer)){
                  if(!empty($customer->area)){
                    $ackAddress = trim($customer->area);
                  } elseif(!empty($customer->address)) {
                    $ackAddress = trim($customer->address);
                  }
                }
              @endphp
              @if(!empty($ackAddress))
                <div class="ack-customer-detail">{{ $ackAddress }}</div>
              @endif
              @if(!empty($customer) && !empty($customer->mobile))
                <div class="ack-customer-detail">Contact: {{ $customer->mobile }}</div>
              @endif
            </div>
          </div>
        </div>
        <div class="receiver-section mt-2 text-center">
          <div class="signature-line-small"></div>
          <div class="receiver-label">Receiver's Seal & Sign</div>
        </div>
      </div>
      @endif

    </div>

    <div id="invoiceBodyThermal" class="invoice-body-block thermal-body {{ $isThermal ? '' : 'd-none' }} {{ $printVariant === 'thermal58' ? 'thermal-58' : 'thermal-80' }}">
    @php
      $billBy = null;
      try{
          if(isset($invoice->salespersonId) && $invoice->salespersonId){
              $billBy = \App\Models\AdminUser::find($invoice->salespersonId);
          }
      }catch(\Throwable $_){ $billBy = null; }

      $thermalSubtotal = 0;
      foreach($items as $it){
          $thermalSubtotal += (float)($it->totalSale ?? (($it->salePrice ?? 0) * ($it->qty ?? 0)));
      }
      $thermalAddCharge = (float)($invoice->additionalChargeAmount ?? 0);
      $thermalDiscount = (float)($invoice->discountAmount ?? 0);
      $thermalGrand = (float)($invoice->grandTotal ?? max(0, $thermalSubtotal + $thermalAddCharge - $thermalDiscount));
    @endphp

    <div class="thermal-receipt-layout">
      <div class="thermal-center">
        <div class="thermal-company">{{ $b && $b->businessName ? $b->businessName : 'Computer Care' }}</div>
        <div class="thermal-line">{{ $b && $b->businessLocation ? $b->businessLocation : 'Office Road, Burichong Bazar, Cumilla' }}</div>
        <div class="thermal-line">Phone: {{ $b && $b->mobile ? $b->mobile : '0123456789' }}</div>
      </div>

      <div class="thermal-sep"></div>

      <div class="thermal-meta">
        <div><span>Invoice:</span><strong>{{ $invoice->invoice }}</strong></div>
        <div><span>Date:</span><strong>{{ \Carbon\Carbon::parse($invoice->date)->format('d-M-Y h:i A') }}</strong></div>
        <div><span>Customer:</span><strong>{{ $customer->name ?? '-' }}</strong></div>
        @if(!empty($customer) && !empty($customer->mobile))
          <div><span>Mobile:</span><strong>{{ $customer->mobile }}</strong></div>
        @endif
        @if($billBy)
          <div><span>Cashier:</span><strong>{{ $billBy->fullName ?? ($billBy->mail ?? 'Staff') }}</strong></div>
        @endif
        <div><span>Status:</span><strong>{{ $paymentStatus }}</strong></div>
      </div>

      <div class="thermal-sep"></div>

      <table class="thermal-items-table" aria-label="Thermal invoice items">
        <colgroup>
          <col style="width:48%">
          <col style="width:12%">
          <col style="width:20%">
          <col style="width:20%">
        </colgroup>
        <thead>
          <tr>
            <th>Item</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Rate</th>
            <th class="text-end">Amt</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $item)
            @php $line = (float)($item->totalSale ?? (($item->salePrice ?? 0) * ($item->qty ?? 0))); @endphp
            <tr>
              <td>
                <div class="thermal-item-name">{{ $item->productName }}</div>
                @if(!empty($item->productCode))
                  <div class="thermal-item-code">Code: {{ $item->productCode }}</div>
                @endif
              </td>
              <td class="text-center">{{ (int)($item->qty ?? 0) }}</td>
              <td class="text-end">@money($item->salePrice ?? 0)</td>
              <td class="text-end">@money($line)</td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center">No items</td></tr>
          @endforelse
        </tbody>
      </table>

      <div class="thermal-sep"></div>

      <table class="thermal-totals-table" aria-label="Thermal invoice totals">
        <tbody>
          <tr>
            <td>Subtotal</td>
            <td class="text-end">@money($thermalSubtotal)</td>
          </tr>
          @if($thermalAddCharge > 0)
            <tr>
              <td>{{ $invoice->additionalChargeName ? e($invoice->additionalChargeName) : 'Additional' }}</td>
              <td class="text-end">@money($thermalAddCharge)</td>
            </tr>
          @endif
          @if($thermalDiscount > 0)
            <tr>
              <td>Discount</td>
              <td class="text-end">- @money($thermalDiscount)</td>
            </tr>
          @endif
          <tr class="thermal-grand-row">
            <td>Grand Total</td>
            <td class="text-end">@money($thermalGrand)</td>
          </tr>
          <tr>
            <td>Paid</td>
            <td class="text-end">@money($invoice->paidAmount ?? 0)</td>
          </tr>
          <tr class="thermal-due-row">
            <td>Due</td>
            <td class="text-end">@money($invoice->curDue ?? 0)</td>
          </tr>
        </tbody>
      </table>

      @if(!empty($invoice->note))
        <div class="thermal-note">Note: {{ $invoice->note }}</div>
      @endif

      <div class="thermal-sep"></div>

      <div class="thermal-center thermal-footer">
        <div>{{ $business && $business->invoiceFooter ? $business->invoiceFooter : 'Thank you for your business' }}</div>
        <div>Powered by {{ config('app.name', env('APP_NAME', 'POS')) }}</div>
      </div>
    </div>
    </div>

    <div class="d-flex justify-content-between align-items-center no-print mt-3">
      <div></div>
      <div class="action-buttons">
        <div class="invoice-action-panel">
          <label for="invoicePrinterProfile" class="mb-0">Printer Profile</label>
          <select id="invoicePrinterProfile" class="form-control form-control-sm printer-select">
            <option value="a4" {{ $printVariant === 'a4' ? 'selected' : '' }}>A4 Invoice</option>
            <option value="thermal80" {{ $printVariant === 'thermal80' ? 'selected' : '' }}>POS Thermal 80mm</option>
            <option value="thermal58" {{ $printVariant === 'thermal58' ? 'selected' : '' }}>POS Thermal 58mm</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm" type="button" onclick="applyInvoicePrinterProfile()"><i class="las la-sync"></i> Update Layout</button>
          <button class="btn btn-outline-secondary btn-sm" type="button" onclick="printSelectedInvoice()"><i class="las la-print"></i> Print Selected</button>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('sale.items.edit', ['id' => $invoice->id]) }}"><i class="las la-edit"></i> Edit Items</a>
        <a class="btn btn-primary btn-sm" href="{{ route('saleList') }}"><i class="las la-arrow-left"></i> Back to Sales</a>
      </div>
    </div>
  </div>
</div>

<style>
/* ============================================
   PROFESSIONAL INVOICE STYLING
   ============================================ */

/* Force pure black text across invoice */
#rn-invoice-root, #rn-invoice-root * { color: #000 !important; }

/* Invoice Container */
#rn-invoice-root {
  background: #fff;
  max-width: 980px;
  margin: 16px auto 20px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.thermal-items-table tbody{
  font-size: 8px;
}

.invoice-body-block {
  width: 100%;
}

.invoice-body-block.thermal-body {
  max-width: 80mm;
  margin: 0 auto;
  border: 1px solid #ddd;
  border-radius: 2px;
  padding: 8px;
}

.invoice-body-block.thermal-body.thermal-58 {
  max-width: 58mm;
}

#rn-invoice-root.printer-thermal {
  max-width: 80mm;
  margin: 10px auto 14px;
  border-radius: 0;
  box-shadow: none;
  border: 1px solid #ddd;
}

#rn-invoice-root.printer-thermal.printer-thermal-58 {
  max-width: 58mm;
}

#rn-invoice-root.printer-thermal .card-body {
  padding: 8px;
}

#rn-invoice-root.printer-thermal .invoice-title {
  font-size: 1.2rem;
  letter-spacing: 1px;
  margin: 2px 0 4px;
}

#rn-invoice-root.printer-thermal .invoice-logo,
#rn-invoice-root.printer-thermal .company-logo-small,
#rn-invoice-root.printer-thermal .invoice-qr-section,
#rn-invoice-root.printer-thermal .acknowledgement-section,
#rn-invoice-root.printer-thermal .signature-boxes,
#rn-invoice-root.printer-thermal .terms-conditions {
  display: none !important;
}

#rn-invoice-root.printer-thermal .invoice-header,
#rn-invoice-root.printer-thermal .invoice-parties,
#rn-invoice-root.printer-thermal .row,
#rn-invoice-root.printer-thermal .col-md-6,
#rn-invoice-root.printer-thermal .col-md-4,
#rn-invoice-root.printer-thermal .col-md-8,
#rn-invoice-root.printer-thermal .col-12 {
  display: block !important;
  width: 100% !important;
  max-width: 100% !important;
  flex: 0 0 100% !important;
}

#rn-invoice-root.printer-thermal .company-name {
  font-size: 1rem;
  margin-bottom: 4px;
}

#rn-invoice-root.printer-thermal .company-details,
#rn-invoice-root.printer-thermal .party-detail,
#rn-invoice-root.printer-thermal .collection-table,
#rn-invoice-root.printer-thermal .invoice-info-table,
#rn-invoice-root.printer-thermal .terms-text,
#rn-invoice-root.printer-thermal .words-text,
#rn-invoice-root.printer-thermal .invoice-items-table,
#rn-invoice-root.printer-thermal .invoice-footer {
  font-size: 0.72rem !important;
  line-height: 1.25;
}

#rn-invoice-root.printer-thermal .invoice-items-table thead th,
#rn-invoice-root.printer-thermal .invoice-items-table tbody td,
#rn-invoice-root.printer-thermal .invoice-items-table tfoot td {
  padding: 2px 1px;
  font-size: 0.68rem !important;
}

#rn-invoice-root.printer-thermal .invoice-items-table th:nth-child(2),
#rn-invoice-root.printer-thermal .invoice-items-table td:nth-child(2),
#rn-invoice-root.printer-thermal .invoice-items-table th:nth-child(5),
#rn-invoice-root.printer-thermal .invoice-items-table td:nth-child(5) {
  display: none;
}

#rn-invoice-root.printer-thermal.printer-thermal-58 .invoice-items-table th:nth-child(6),
#rn-invoice-root.printer-thermal.printer-thermal-58 .invoice-items-table td:nth-child(6) {
  display: none;
}

.thermal-receipt-layout {
  width: 100%;
}

.thermal-center {
  text-align: center;
}

.thermal-company {
  font-size: 0.96rem;
  font-weight: 700;
  margin-bottom: 2px;
}

.thermal-line,
.thermal-footer {
  font-size: 0.74rem;
  line-height: 1.3;
}

.thermal-sep {
  border-top: 1px dashed #333;
  margin: 6px 0;
}

.thermal-meta div {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  font-size: 0.72rem;
  line-height: 1.3;
}

.thermal-meta span {
  opacity: 0.9;
}

.thermal-items-table,
.thermal-totals-table {
  width: 100%;
  margin: 0;
  border-collapse: collapse;
  table-layout: fixed;
}

.thermal-items-table thead th,
.thermal-items-table tbody td,
.thermal-totals-table td {
  border: none;
  padding: 1px 0;
  font-size: 0.68rem;
  vertical-align: top;
  background: transparent;
}

.thermal-items-table thead th {
  text-transform: uppercase;
  letter-spacing: 0.4px;
  border-bottom: 1px dashed #333;
  font-weight: 700;
  padding-bottom: 4px;
}

.thermal-items-table tbody tr + tr td {
  border-top: 1px dotted #ccc;
}

.thermal-items-table tbody tr:last-child td {
  padding-bottom: 4px;
  border-bottom: 1px dashed #333;
}

.thermal-items-table th:nth-child(2),
.thermal-items-table th:nth-child(3),
.thermal-items-table th:nth-child(4),
.thermal-items-table td:nth-child(2),
.thermal-items-table td:nth-child(3),
.thermal-items-table td:nth-child(4),
.thermal-totals-table td:last-child {
  white-space: nowrap;
}

.thermal-item-name {
  font-size: 0.7rem;
  font-weight: 600;
  line-height: 1.15;
  word-break: break-word;
}

.thermal-item-code {
  font-size: 0.62rem;
  opacity: 0.9;
}

.thermal-totals-table td {
  padding-top: 2px;
  padding-bottom: 2px;
}

.thermal-grand-row td,
.thermal-due-row td {
  font-weight: 700;
}

.thermal-grand-row td {
  border-top: 1px dashed #333;
  padding-top: 4px;
}

.thermal-note {
  font-size: 0.7rem;
  line-height: 1.3;
  margin-top: 4px;
}

#rn-invoice-root .card-body {
  padding: 22px;
}

.invoice-footer {
  margin-top: 8px;
}

/* Action buttons spacing */
.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}
.action-buttons .btn { margin: 0; }

.invoice-action-panel {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f8f9fb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 6px 8px;
}

.invoice-action-panel label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #222;
  white-space: nowrap;
}

.printer-select {
  min-width: 180px;
}

/* Header Section */
.invoice-header {
  margin-bottom: 14px;
}

.invoice-logo {
  height: 70px;
  width: auto;
  margin-bottom: 15px;
  object-fit: contain;
}

.company-logo-small {
  height: 100px;
  width: auto;
  margin-bottom: 6px;
  object-fit: contain;
}

.company-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #000;
  margin-bottom: 8px;
}

.company-details {
  font-size: 0.875rem;
  color: #000;
  line-height: 1.45;
}

.invoice-title {
  font-size: 1.9rem;
  font-weight: 600;
  color: #000;
  letter-spacing: 3px;
  margin: 6px 0 8px;
}

.invoice-qr {
  width: 100px;
  height: 100px;
  border: 2px solid #ddd;
  padding: 5px;
  margin-bottom: 6px;
}

.invoice-info-table {
  width: 100%;
  font-size: 0.8rem;
}

.invoice-info-table td {
  padding: 2px 4px;
  border-bottom: 1px solid #eee;
}

.invoice-info-table td:first-child {
  color: #000;
  width: 50%;
}

.invoice-divider {
  border-top: 2px solid #333;
  margin: 12px 0;
}

/* Party Boxes (Bill To / Payment Info) */
.invoice-parties {
  margin-bottom: 14px;
}

.party-box {
  background: transparent;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 10px;
  min-height: 100px;
}

.party-label {
  font-size: 0.875rem;
  font-weight: 700;
  color: #000;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #333;
  padding-bottom: 5px;
}

.party-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #000;
  margin-bottom: 5px;
}

.party-detail {
  font-size: 0.875rem;
  color: #000;
  line-height: 1.6;
}

/* Professional Items Table */
.invoice-items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 4px;
  font-size: 0.85rem;
}

.invoice-items-table thead {
  background: transparent;
  color: #000;
}

.invoice-items-table thead th {
  padding: 2px 2px;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.78rem;
  letter-spacing: 0.5px;
  border: 1px solid #333;
}

.invoice-items-table tbody td {
  padding: 2px 2px;
  border: 1px solid #dee2e6;
  vertical-align: top;
  line-height: 1.25;
}

.invoice-items-table .product-name {
  display: inline-block;
  font-size: 0.83rem;
  font-weight: 600;
  line-height: 1.3;
}

.invoice-items-table tbody tr:nth-child(even) {
  background-color: transparent;
}

.invoice-items-table tbody tr:hover {
  background-color: transparent;
}

.serial-info {
  font-size: 0.7rem;
  color: #000;
  margin-top: 2px;
  font-style: italic;
}

.invoice-items-table tfoot td {
  padding: 2px 2px;
  border: 1px solid #dee2e6;
  font-size: 0.92rem;
  line-height: 1.25;
}

.subtotal-row td {
  background-color: transparent;
}

.discount-row td {
  background-color: transparent;
  color: inherit;
}

.grandtotal-row td {
  background-color: transparent;
  color: inherit;
  font-size: 1rem;
  font-weight: 700;
  border-top: 2px solid #333;
}

/* Amount in Words */
.amount-in-words {
  background: transparent;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 9px 10px;
  margin-bottom: 6px;
}

.words-text {
  font-size: 1rem;
  color: #000;
  margin-top: 5px;
  font-style: italic;
}

/* Collection Detail Box */
.collection-detail-box {
  background: transparent;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 10px;
}

.collection-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #000;
  margin-bottom: 8px;
  text-align: center;
}

.collection-table {
  width: 100%;
  font-size: 0.875rem;
}

.collection-table td {
  padding: 3px 4px;
  border-bottom: 1px dashed #dee2e6;
}

.total-collected-row td {
  border-top: 2px solid #333;
  border-bottom: 2px solid #333;
  font-weight: 700;
  padding-top: 6px;
  padding-bottom: 6px;
}

.due-row td {
  font-weight: 700;
  font-size: 0.95rem;
}

.collection-table .label {
  color: #000;
}

.collection-table .value {
  text-align: right;
  font-weight: 600;
}

/* Signature Boxes */
.signature-boxes {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: flex-end;
}

.signature-box {
  flex: 1;
  text-align: center;
}

.signature-line {
  width: 60%;
  height: 28px;
  border-bottom: 1px solid #888;
  margin-bottom: 4px;
  display: inline-block;
  align-items: flex-end;
  justify-content: center;
}

.signature-stamp {
  max-width: 50px;
  max-height: 40px;
  object-fit: contain;
  margin-bottom: 3px;
}

.signature-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-top: 2px;
}
.acknowledgement-section {
  margin-top: 12px;
}

.ack-separator-wrap {
  position: relative;
  margin: 8px 0 10px;
}

.ack-separator {
  border: 0;
  border-top: 1px dotted #bbb;
  margin: 0;
}

.ack-separator-icon {
  position: absolute;
  left: 0;
  top: -6px;
  font-size: 1rem;
  color: #000;
  background: #fff;
  padding: 0 4px;
}

.ack-title {
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: #000;
  margin-bottom: 4px;
}

.ack-company {
  font-weight: 700;
  font-size: 0.95rem;
  color: #000;
  margin-bottom: 2px;
}

.ack-address {
  font-size: 0.82rem;
  color: #000;
  margin-bottom: 6px;
}

.ack-details {
  font-size: 0.82rem;
  color: #000;
  line-height: 1.6;
}

.ack-meta {
  font-size: 0.82rem;
  color: #000;
}

.ack-logo {
  height: 40px;
  width: auto;
  margin-bottom: 6px;
  object-fit: contain;
}

.ack-grid {
  margin-top: 4px;
}

.ack-card {
  background: transparent;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 8px 10px;
  height: 100%;
}

.ack-card-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #333;
  padding-bottom: 4px;
  margin-bottom: 6px;
}

.ack-customer {
  background: transparent;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 8px 10px;
}

.ack-customer-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #333;
  padding-bottom: 4px;
  margin-bottom: 6px;
}

.ack-customer-name {
  font-size: 1rem;
  font-weight: 600;
  color: #000;
  margin-bottom: 4px;
}

.ack-customer-detail {
  font-size: 0.82rem;
  color: #000;
  line-height: 1.6;
}

/* Terms & Conditions */
.terms-conditions {
  background: transparent;
  border-left: 3px solid #333;
  padding: 8px 10px;
  border-radius: 4px;
}

.terms-conditions .terms-text {
  font-size: 0.8rem;
  color: #000;
  line-height: 1.6;
  margin-top: 4px;
}

.receiver-label {
  font-size: 0.78rem;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.receiver-section {
  display: inline-block;
  margin-top: 10px;
}

.signature-line-small {
  width: 200px;
  height: 34px;
  border-bottom: 1px solid #888;
  margin: 0 auto 3px;
}

/* Invoice Remarks */
.invoice-remarks {
  background: transparent;
  border-left: 4px solid #333;
  padding: 8px 10px;
  border-radius: 4px;
  font-size: 0.875rem;
}

/* Badge Styling */
.badge {
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  background-color: transparent;
  color: #000;
  border: 1px solid #333;
}

.badge-success {
  background-color: transparent;
  color: #000;
}

.badge-warning {
  background-color: transparent;
  color: #000;
}

.badge-danger {
  background-color: transparent;
  color: #000;
}

/* Responsive Design */
@media (max-width: 768px) {
  #rn-invoice-root .card-body {
    padding: 12px;
  }

  #rn-invoice-root {
    margin: 8px auto 12px;
  }

  .invoice-body-block.thermal-body {
    max-width: 100%;
  }

  .invoice-action-panel {
    width: 100%;
    flex-wrap: wrap;
  }

  .printer-select {
    min-width: 0;
    width: 100%;
  }
  
  .invoice-title {
    font-size: 1.8rem;
  }
  
  .signature-boxes {
    flex-direction: column;
  }
  
  .invoice-items-table {
    font-size: 0.8rem;
  }
  
  .invoice-items-table thead th,
  .invoice-items-table tbody td,
  .invoice-items-table tfoot td {
    padding: 2px 2px;
  }
}

/* Print Styles */
@media print {
  body * { visibility: hidden; }
  #rn-invoice-root, #rn-invoice-root * { visibility: visible; }
  
  html, body { 
    height: auto; 
    margin: 0;
    padding: 0;
  }
  
  #rn-invoice-root { 
    box-shadow: none !important; 
    border: none !important; 
    margin: 0 !important; 
    padding: 0 !important; 
    position: static; 
    width: 100% !important; 
    max-width: 100% !important;
    page-break-after: avoid; 
  }
  /* Expand tables and content to full page width */
  .invoice-items-table, .table { width: 100% !important; }
  .card { box-shadow: none !important; border: none !important; margin: 0 !important; }
  body { background: #fff !important; }
  
  #rn-invoice-root .card-body { 
    padding: 0 !important; 
  }

  #rn-invoice-root.printer-thermal {
    width: 80mm !important;
    max-width: 80mm !important;
    border: none !important;
    box-shadow: none !important;
  }

  #rn-invoice-root.printer-thermal.printer-thermal-58 {
    width: 58mm !important;
    max-width: 58mm !important;
  }

  #invoiceBodyA4.d-none,
  #invoiceBodyThermal.d-none {
    display: none !important;
  }

  #invoiceBodyThermal.thermal-80 {
    width: 80mm !important;
    max-width: 80mm !important;
    margin: 0 auto !important;
    border: none !important;
  }

  #invoiceBodyThermal.thermal-58 {
    width: 58mm !important;
    max-width: 58mm !important;
    margin: 0 auto !important;
    border: none !important;
  }
  
  /* Keep desktop grid layout regardless of printable viewport width */
  .row { display: flex !important; flex-wrap: wrap !important; }
  .col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; }
  .col-md-4 { flex: 0 0 33.3333% !important; max-width: 33.3333% !important; }
  .col-md-8 { flex: 0 0 66.6667% !important; max-width: 66.6667% !important; }
  .col-md-12, .col-12 { flex: 0 0 100% !important; max-width: 100% !important; }
  .table-responsive { overflow: visible !important; }
  
  /* Force signature boxes to display horizontally (side-by-side) on print */
  .signature-boxes {
    display: flex !important;
    flex-direction: row !important;
    justify-content: space-between !important;
    gap: 18px !important;
  }
  
  .signature-box {
    flex: 1 !important;
  }
  
  .no-print { 
    display: none !important; 
  }
  
  .only-print { 
    display: block !important; 
  }
  
  .invoice-items-table thead th,
  .invoice-items-table tbody td,
  .invoice-items-table tfoot td { 
    border: 1px solid #dee2e6 !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
  
  .invoice-items-table thead {
    background: transparent !important;
    color: #000 !important;
  }
  
  .grandtotal-row td {
    background-color: transparent !important;
    color: inherit !important;
    border-top: 2px solid #333 !important;
  }
  
  tr { 
    page-break-inside: avoid; 
  }
  
  thead { 
    display: table-header-group; 
  }
  
  tfoot { 
    display: table-footer-group; 
  }
  
  .invoice-footer { 
    display: block; 
    visibility: visible; 
    page-break-inside: avoid; 
  }
  #rn-invoice-root.printer-thermal #acknowledgementSection,
  #rn-invoice-root.printer-thermal .signature-boxes,
  #rn-invoice-root.printer-thermal .invoice-qr-section,
  #rn-invoice-root.printer-thermal .terms-conditions {
    display: none !important;
  }
  /* Keep acknowledgment block intact and start it on a new page */
  #acknowledgementSection { page-break-inside: avoid; break-inside: avoid-page; }
  .print-break-before-page { page-break-before: always; break-before: page; }
  
  @page { 
    margin: 8mm; 
    size: A4;
  }
  
  /* Ensure colors print */
  * {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}

.only-print { 
  display: none; 
}

@media print {
  .only-print { 
    display: block !important; 
  }
}
</style>

<style>
  /* Legacy compatibility styles */
  .invoice-box{font-family: Arial, Helvetica, sans-serif}
  .table th, .table td{vertical-align: middle}
  .invoice-footer { display: block; }
</style>

@endsection

@section('scripts')
@parent
<script>
  // Ensure the global loader is hidden once the invoice view is ready
  document.addEventListener('DOMContentLoaded', function(){
    var l = document.getElementById('loading');
    if (l) { l.style.display = 'none'; }

    // Auto-open printable tab once when requested via query string.
    try {
      var params = new URLSearchParams(window.location.search || '');
      if (params.get('autoprint') === '1') {
        var printer = params.get('printer') || '{{ $printVariant }}';
        setTimeout(function(){
          try { printInvoice(printer); } catch(e){ console.warn('autoprint failed', e); }
        }, 250);
        try {
          params.delete('autoprint');
          var next = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '') + (window.location.hash || '');
          window.history.replaceState({}, document.title, next);
        } catch(e){}
      }
    } catch(e){}
  });

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

  function normalizePrinterProfile(v){
    var pv = String(v || '').toLowerCase();
    if(['a4','thermal80','thermal58'].indexOf(pv) === -1){ pv = 'a4'; }
    return pv;
  }

  function getSelectedPrinterProfile(){
    try{
      var sel = document.getElementById('invoicePrinterProfile');
      return normalizePrinterProfile(sel ? sel.value : (document.getElementById('rn-invoice-root') || {}).getAttribute('data-printer-variant'));
    }catch(e){ return normalizePrinterProfile('{{ $printVariant }}'); }
  }

  function setInvoiceLayoutVariant(variant){
    try{
      var pv = normalizePrinterProfile(variant);
      var root = document.getElementById('rn-invoice-root');
      var a4 = document.getElementById('invoiceBodyA4');
      var thermal = document.getElementById('invoiceBodyThermal');
      if(!root || !a4 || !thermal) return;

      if(pv === 'a4'){
        a4.classList.remove('d-none');
        thermal.classList.add('d-none');
      }else{
        a4.classList.add('d-none');
        thermal.classList.remove('d-none');
        thermal.classList.toggle('thermal-58', pv === 'thermal58');
        thermal.classList.toggle('thermal-80', pv !== 'thermal58');
      }

      root.setAttribute('data-printer-variant', pv);
      try{
        var sel = document.getElementById('invoicePrinterProfile');
        if(sel) sel.value = pv;
      }catch(_e){}
    }catch(e){ console.warn('setInvoiceLayoutVariant failed', e); }
  }

  function applyInvoicePrinterProfile(){
    try{
      var pv = getSelectedPrinterProfile();
      setInvoiceLayoutVariant(pv);
    }catch(e){ console.warn('applyInvoicePrinterProfile failed', e); }
  }

  function printSelectedInvoice(){
    try{
      var pv = getSelectedPrinterProfile();
      return printInvoice(pv);
    }catch(e){ console.warn('printSelectedInvoice failed', e); }
  }

  function printInvoice(variant){
    try{
      var pv = normalizePrinterProfile(variant);
      setInvoiceLayoutVariant(pv);
      var thermalMode = (pv === 'thermal80' || pv === 'thermal58');
      var paperWidth = (pv === 'thermal58') ? '58mm' : (pv === 'thermal80' ? '80mm' : '210mm');
      var pageSizeCss = thermalMode ? (paperWidth + ' auto') : 'A4';
      var pageMarginCss = thermalMode ? '2mm' : '8mm';
      // Ensure acknowledgment section breaks only if it won't fit
      try{ setAckBreakClass(); }catch(e){}
      var root = document.getElementById('rn-invoice-root');
      var footer = document.querySelector('.print-only-footer');
      if(!root) return window.print();
      var printableRoot = root.cloneNode(true);
      try{
        var pA4 = printableRoot.querySelector('#invoiceBodyA4');
        var pThermal = printableRoot.querySelector('#invoiceBodyThermal');
        if(pA4 && pThermal){
          if(thermalMode){
            pA4.classList.add('d-none');
            pThermal.classList.remove('d-none');
            pThermal.classList.toggle('thermal-58', pv === 'thermal58');
            pThermal.classList.toggle('thermal-80', pv !== 'thermal58');
          }else{
            pA4.classList.remove('d-none');
            pThermal.classList.add('d-none');
          }
        }
      }catch(_e){}
      var content = printableRoot.outerHTML + (footer ? footer.outerHTML : '');
      var styleHtml = collectStyles();
      var w = window.open('', '_blank');
      if(!w) return alert('Popup blocked. Allow popups for this site to print.');
      var doc = w.document;
      doc.open();
      doc.write('<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">');
      doc.write(styleHtml);
      // minimal print helper: hide interactive-only elements; rely on page styles for layout
      // Force marginless printing in the popup regardless of browser defaults
      doc.write('<style>@page{margin:'+pageMarginCss+'; size:'+pageSizeCss+';} html,body{margin:0;padding:0;print-color-adjust:exact;-webkit-print-color-adjust:exact;} #rn-invoice-root{margin:0 auto!important;padding:0!important;box-shadow:none!important;border:none!important;width:'+(thermalMode ? paperWidth : '100%')+'!important;max-width:'+(thermalMode ? paperWidth : '100%')+'!important} #rn-invoice-root .card-body{padding:'+(thermalMode ? '2mm' : '3mm')+'!important;}'+
        (thermalMode ? '#rn-invoice-root .invoice-qr-section,#rn-invoice-root .signature-boxes,#rn-invoice-root #acknowledgementSection,#rn-invoice-root .terms-conditions{display:none!important;} #rn-invoice-root .row{display:block!important} #rn-invoice-root .col-md-6,#rn-invoice-root .col-md-4,#rn-invoice-root .col-md-8,#rn-invoice-root .col-12{display:block!important;width:100%!important;max-width:100%!important;flex:0 0 100%!important} #rn-invoice-root .invoice-items-table th:nth-child(2),#rn-invoice-root .invoice-items-table td:nth-child(2),#rn-invoice-root .invoice-items-table th:nth-child(5),#rn-invoice-root .invoice-items-table td:nth-child(5){display:none!important;}'+(pv === 'thermal58' ? '#rn-invoice-root .invoice-items-table th:nth-child(6),#rn-invoice-root .invoice-items-table td:nth-child(6){display:none!important;}' : '')+' #rn-invoice-root,#rn-invoice-root *{font-size:11px!important;line-height:1.25!important;}' : '')+
      '</style>');
      doc.write('<style>.no-print{display:none !important}</style>');
      doc.write('</head><body>');
      doc.write(content);
      // Inject a small helper to compute conditional break inside the print window if needed
      doc.write('<script>(function(){\n  function shouldBreakAck(){\n    try{\n      var root = document.getElementById("rn-invoice-root");\n      var ack = document.getElementById("acknowledgementSection");\n      if(!root || !ack) return false;\n      var r = root.getBoundingClientRect();\n      var a = ack.getBoundingClientRect();\n      var above = a.top - r.top;\n      var pageHeight = 1122; /* approx A4 height in px (11.7in*96) */\n      return (above + a.height) > (pageHeight - 24);\n    }catch(e){ return true; }\n  }\n  function setAckBreak(){\n    try{ var ack = document.getElementById("acknowledgementSection"); if(!ack) return; var cls = "print-break-before-page"; if(shouldBreakAck()) ack.classList.add(cls); else ack.classList.remove(cls); }catch(e){}\n  }\n  try{ setAckBreak(); }catch(e){}\n})();<\/script>');
      doc.write('</body></html>');
      doc.close();
      w.focus();
      // Give the new window a moment to render before printing
      setTimeout(function(){ try{ w.print(); setTimeout(function(){ w.close(); }, 500); }catch(e){ console.warn('print failed', e); } }, 250);
    }catch(e){ console.warn('printInvoice error', e); window.print(); }
  }

  // Conditional page-break for Acknowledgment on direct prints
  function shouldBreakAck(){
    try{
      var root = document.getElementById('rn-invoice-root');
      var ack = document.getElementById('acknowledgementSection');
      if(!root || !ack) return false;
      var r = root.getBoundingClientRect();
      var a = ack.getBoundingClientRect();
      var above = a.top - r.top;
      var pageHeight = 1122; // approx A4 height in px (11.7in*96)
      return (above + a.height) > (pageHeight - 24);
    }catch(e){ return true; }
  }
  function setAckBreakClass(){
    try{
      var ack = document.getElementById('acknowledgementSection');
      var cls = 'print-break-before-page';
      if(!ack) return;
      if(shouldBreakAck()) ack.classList.add(cls); else ack.classList.remove(cls);
    }catch(e){}
  }
  // Apply on beforeprint for direct window.print
  try{ window.addEventListener('beforeprint', setAckBreakClass); }catch(e){}
</script>
@endsection
