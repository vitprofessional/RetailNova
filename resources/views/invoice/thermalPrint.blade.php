<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('common.thermal_receipt') }} {{ $invoice->invoice ?? 'Invoice' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: white;
            line-height: 1.4;
            color: #000;
        }

        /* Thermal Printer Widths */
        .thermal-80 {
            width: 80mm;
            padding: 0;
            margin: 0 auto;
            page: thermal80;
        }

        .thermal-58 {
            width: 58mm;
            padding: 0;
            margin: 0 auto;
            page: thermal58;
        }

        .thermal-receipt {
            background: white;
            padding: 3mm 2mm;
            font-size: 10px;
        }

        /* Header Section */
        .receipt-header {
            text-align: center;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
        }

        .receipt-logo {
            display: block;
            width: auto;
            max-width: 34mm;
            max-height: 18mm;
            margin: 0 auto 2mm;
            object-fit: contain;
        }

        .business-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 1mm;
            line-height: 1.2;
        }

        .business-details {
            font-size: 8px;
            line-height: 1.3;
            margin-bottom: 1mm;
            color: #000;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        /* Invoice Info */
        .info-section {
            font-size: 8px;
            margin-bottom: 2mm;
            line-height: 1.4;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
            word-wrap: break-word;
        }

        .info-label {
            font-weight: bold;
            margin-right: 2mm;
            flex: 0 0 auto;
        }

        .info-value {
            text-align: right;
            flex: 1;
            word-break: break-all;
        }

        /* Items Table */
        .items-section {
            margin: 2mm 0;
            font-size: 8px;
        }

        .items-header {
            display: grid;
            grid-template-columns: 2fr 0.8fr 1fr 1fr;
            gap: 2px;
            font-weight: bold;
            border-bottom: 1px dashed #000;
            padding: 1mm 0;
            margin-bottom: 1mm;
        }

        .items-header-cell {
            text-align: left;
        }

        .items-header-cell:nth-child(2),
        .items-header-cell:nth-child(3),
        .items-header-cell:nth-child(4) {
            text-align: right;
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 0.8fr 1fr 1fr;
            gap: 2px;
            margin-bottom: 1.5mm;
            padding-bottom: 1mm;
            border-bottom: 1px dotted #ccc;
        }

        .item-row:last-child {
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }

        .item-name {
            font-weight: bold;
            word-break: break-word;
            line-height: 1.2;
        }

        .item-code {
            font-size: 7px;
            color: #555;
            margin-top: 0.5mm;
        }

        .qty,
        .rate,
        .amt {
            text-align: right;
        }

        /* Totals Section */
        .totals-section {
            margin: 2mm 0;
            font-size: 8px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            padding: 0.5mm 0;
        }

        .total-label {
            flex: 1;
        }

        .total-value {
            font-weight: normal;
            text-align: right;
            min-width: 30mm;
        }

        .subtotal-row .total-label {
            font-weight: normal;
        }

        .subtotal-row .total-value {
            font-weight: normal;
        }

        .grand-total-row {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 1mm;
            margin-top: 1mm;
        }

        .grand-total-row .total-label {
            font-weight: bold;
        }

        .grand-total-row .total-value {
            font-weight: bold;
        }

        .paid-row {
            margin-top: 1mm;
        }

        .paid-row .total-label {
            font-weight: normal;
        }

        .due-row {
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 1mm;
            margin-top: 1mm;
        }

        .due-row .total-label {
            font-weight: bold;
        }

        .due-row .total-value {
            font-weight: bold;
        }

        /* Notes Section */
        .notes-section {
            font-size: 8px;
            margin: 2mm 0;
            line-height: 1.3;
            border-top: 1px dashed #000;
            padding-top: 2mm;
        }

        .note-label {
            font-weight: bold;
            display: inline;
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            font-size: 8px;
            margin-top: 2mm;
            padding-top: 2mm;
            border-top: 1px dashed #000;
            line-height: 1.3;
        }

        .footer-text {
            margin: 0.5mm 0;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .thermal-receipt {
                margin: 0;
                padding: 0;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Page Size */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        @page thermal80 {
            size: 80mm auto;
            margin: 0;
        }

        @page thermal58 {
            size: 58mm auto;
            margin: 0;
        }
    </style>
@include('partials.localization')
</head>
<body>
    @php
        $b = $business ?? $invoice->business ?? null;
        $invoiceLogoUrl = $b ? $b->invoiceLogoUrl() : null;
        $invoiceFallbackLogoUrl = asset('logo.png');
        $thermalPrinter = strtolower((string) request()->query('printer', 'thermal80'));
        if (!in_array($thermalPrinter, ['thermal80', 'thermal58'], true)) {
            $thermalPrinter = 'thermal80';
        }
        $thermalWidthClass = $thermalPrinter === 'thermal58' ? 'thermal-58' : 'thermal-80';
        $paidAmount = (float)($invoice->paidAmount ?? 0);
        $grandTotal = (float)($invoice->grandTotal ?? 0);
        $currentDue = (float)($invoice->curDue ?? max($grandTotal - $paidAmount, 0));

        // Determine payment status
        if ($currentDue <= 0 && $grandTotal > 0) {
            $paymentStatus = 'PAID';
        } elseif ($grandTotal <= 0) {
            $paymentStatus = 'PAID';
        } elseif ($paidAmount <= 0 && $currentDue > 0) {
            $paymentStatus = 'DUE';
        } elseif ($paidAmount > 0 && $currentDue > 0) {
            $paymentStatus = 'PARTIAL';
        } else {
            $paymentStatus = 'PAID';
        }

        $thermalSubtotal = 0;
        foreach($items as $it){
            $thermalSubtotal += (float)($it->totalSale ?? (($it->salePrice ?? 0) * ($it->qty ?? 0)));
        }
        $thermalAddCharge = (float)($invoice->additionalChargeAmount ?? 0);
        $thermalDiscount = (float)($invoice->discountAmount ?? 0);
    @endphp

    <div class="{{ $thermalWidthClass }}" data-printer-variant="{{ $thermalPrinter }}">
        <div class="thermal-receipt">

            <!-- Header -->
            <div class="receipt-header">
                <img class="receipt-logo" src="{{ $invoiceLogoUrl ?: $invoiceFallbackLogoUrl }}" alt="{{ $b->businessName ?? __('common.thermal_receipt') }}" onerror="this.onerror=null;this.src='{{ $invoiceFallbackLogoUrl }}'">
                @if($b && $b->businessName)<div class="business-name">{{ $b->businessName }}</div>@endif
                <div class="business-details">
                    @if($b && $b->businessLocation)<div>{{ $b->businessLocation }}</div>@endif
                    @if($b && $b->mobile)<div>{{ __('common.phone') }}: {{ $b->mobile }}</div>@endif
                    @if($b && $b->email)<div>{{ __('common.email') }}: {{ $b->email }}</div>@endif
                </div>
            </div>

            <div class="divider"></div>

            <!-- Invoice Info -->
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">{{ __('common.invoice') }}:</span>
                    <span class="info-value"><strong>{{ $invoice->invoice }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('common.date') }}:</span>
                    <span class="info-value"><strong>{{ \Carbon\Carbon::parse($invoice->date)->format('d-M-Y h:i A') }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('common.customer') }}:</span>
                    <span class="info-value"><strong>{{ $customer->name ?? '-' }}</strong></span>
                </div>
                @if(!empty($customer) && !empty($customer->mobile))
                    <div class="info-row">
                        <span class="info-label">{{ __('common.mobile') }}:</span>
                        <span class="info-value"><strong>{{ $customer->mobile }}</strong></span>
                    </div>
                @endif
                @if($invoice->salespersonId)
                    @php
                        $billBy = \App\Models\AdminUser::find($invoice->salespersonId);
                    @endphp
                    @if($billBy)
                        <div class="info-row">
                            <span class="info-label">{{ __('invoices.cashier') }}:</span>
                            <span class="info-value"><strong>{{ $billBy->fullName ?? ($billBy->mail ?? __('common.staff')) }}</strong></span>
                        </div>
                    @endif
                @endif
                <div class="info-row">
                    <span class="info-label">{{ __('common.status') }}:</span>
                    <span class="info-value"><strong>{{ $paymentStatus ?? 'DUE' }}</strong></span>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Items -->
            <div class="items-section">
                <div class="items-header">
                    <div class="items-header-cell">{{ __('invoices.item') }}</div>
                    <div class="items-header-cell">{{ __('invoices.qty') }}</div>
                    <div class="items-header-cell">{{ __('invoices.rate') }}</div>
                    <div class="items-header-cell">{{ __('invoices.amt') }}</div>
                </div>

                @forelse($items as $item)
                    @php
                        $lineAmount = (float)($item->totalSale ?? (($item->salePrice ?? 0) * ($item->qty ?? 0)));
                    @endphp
                    <div class="item-row">
                        <div>
                            <div class="item-name">{{ $item->productName }}</div>
                            @if(!empty($item->productCode))
                                <div class="item-code">{{ __('common.code') }}: {{ $item->productCode }}</div>
                            @endif
                        </div>
                        <div class="qty">{{ (int)($item->qty ?? 0) }}</div>
                        <div class="rate">@money($item->salePrice ?? 0)</div>
                        <div class="amt">@money($lineAmount)</div>
                    </div>
                @empty
                    <div class="item-row">
                        <div colspan="4" style="text-align: center;">{{ __('invoices.no_items') }}</div>
                    </div>
                @endforelse
            </div>

            <div class="divider"></div>

            <!-- Totals -->
            <div class="totals-section">
                <div class="total-row subtotal-row">
                    <span class="total-label">{{ __('invoices.subtotal') }}</span>
                    <span class="total-value">@money($thermalSubtotal)</span>
                </div>

                @if($thermalAddCharge > 0)
                    <div class="total-row">
                        <span class="total-label">{{ $invoice->additionalChargeName ? e($invoice->additionalChargeName) : __('common.additional_charge') }}</span>
                        <span class="total-value">@money($thermalAddCharge)</span>
                    </div>
                @endif

                @if($thermalDiscount > 0)
                    <div class="total-row">
                        <span class="total-label">{{ __('invoices.discount') }}</span>
                        <span class="total-value">- @money($thermalDiscount)</span>
                    </div>
                @endif

                <div class="total-row grand-total-row">
                    <span class="total-label">{{ __('invoices.grand_total') }}</span>
                    <span class="total-value">@money($grandTotal)</span>
                </div>

                <div class="total-row paid-row">
                    <span class="total-label">{{ __('invoices.paid') }}</span>
                    <span class="total-value">@money($paidAmount)</span>
                </div>

                @if($invoice->paymentMethodLabel())
                    <div class="total-row paid-row">
                        <span class="total-label">{{ __('invoices.payment_mode') }}</span>
                        <span class="total-value">{{ $invoice->paymentMethodLabel() }}</span>
                    </div>
                @endif

                <div class="total-row due-row">
                    <span class="total-label">{{ __('invoices.due') }}</span>
                    <span class="total-value">@money($currentDue)</span>
                </div>
            </div>

            <div class="thermal-amount-words">{{ \App\Support\AmountInWords::money($grandTotal) }}</div>
            <!-- Notes -->
            @if(!empty($invoice->note))
                <div class="notes-section">
                    <div class="note-label">{{ __('common.note') }}:</div> {{ $invoice->note }}
                </div>
            @endif

            <div class="divider"></div>

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="footer-text">{{ __('invoices.thank_you_for_your_business') }}</div>
                <div class="footer-text">{{ __('invoices.powered_by_retailnova') }}</div>
            </div>

        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.addEventListener('load', function() {
            document.fonts.ready.then(function(){ window.print(); });
        });
    </script>
</body>
</html>
