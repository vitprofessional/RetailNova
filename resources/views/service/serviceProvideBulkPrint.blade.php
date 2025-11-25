@extends('include')
@section('backTitle', 'Bulk Print Provided Services')

@section('container')
<style>
    body {
        background: #fff;
    }
    .print-container {
        margin: 20px;
    }
    .print-header .header-inner { display:flex; align-items:center; gap:16px; }
    .print-header .logo img{
        max-height:72px;
        max-width:140px;
        width: auto;
        height: auto;
        object-fit:contain;
        display:block;
    }
    .print-header .meta{ display:flex; flex-direction:column; }
    .print-header .business-name{ margin:0; font-size:16px; line-height:1.05; }
    .print-header .business-info{ font-size:12px; color:#333; margin-top:4px; }
    .customer-section {
        margin-bottom: 18px;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        page-break-inside: avoid;
    }
    .customer-header {
        border-bottom: 1px solid #333;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }
    .customer-header h4 {
        margin: 0;
    }
    .service-table {
        width: 100%;
        border-collapse: collapse;
    }
    .service-table th, .service-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .service-table th {
        background-color: #f2f2f2;
    }
    .text-right {
        text-align: right !important;
    }
    .total-row td {
        font-weight: bold;
        border-top: 2px solid #333;
    }
    /* Footer layout */
    .print-footer { font-size:12px; color:#333; }
    .print-footer .footer-inner { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .print-footer .footer-email { font-weight:600; }
    .print-footer .footer-since { font-size:11px; color:#666; }
    .print-footer .footer-actions a { display:inline-block; margin-left:6px; padding:6px 10px; border-radius:14px; border:1px solid #ccc; color:#333; text-decoration:none; font-size:11px; }
    @media print {
        body {
            margin: 0;
            background: #fff;
        }
        .print-container {
            margin: 0;
        }
        .customer-section {
            border: none;
            box-shadow: none;
            margin-bottom: 12px;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        .no-print {
            display: none;
        }
        /* Print header/footer should be visible on each printed page in modern browsers */
        .print-header { position: fixed; top: 0; left: 0; right: 0; background: #fff; z-index: 9999; padding: 8px 12px; box-sizing: border-box; border-bottom: 1px solid #e9e9e9; }
        /* Make footer fixed for print so it repeats on every page; reserve space using container padding */
        .print-footer { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; z-index: 9999; padding: 8px 12px; box-sizing: border-box; border-top: 1px solid #e9e9e9; }
        /* Reserve vertical space for header/footer when printing so content won't overlap */
        .print-spacer { display:block; height:110px; }
        .print-footer-spacer { display:block; height:64px; }
        /* Attempt to fit content on single page by scaling slightly (browser dependent) */
        body { -webkit-print-color-adjust: exact; }
        @page { size: A4 portrait; margin: 10mm; }
        /* Try slight shrink to fit header+content+footer on a single page when possible */
        /* Reserve top/bottom space equal to header/footer heights so they don't overlap content */
        html, body { height: 100%; }
        /* Aggressive shrink for print to try to force footer on same page */
        .print-container { zoom: 0.75; padding-top: 64px; padding-bottom: 64px; box-sizing: border-box; }
        .print-header { padding: 6px 8px; }
        .print-footer { padding: 6px 8px; }
        .print-header .logo img { max-height:56px; max-width:120px; }
        /* Reduce table cell padding and font sizes for print */
        .service-table th, .service-table td { padding: 6px; font-size:11px; }
        .print-header .business-name { font-size:15px; }
        .customer-section { padding:8px; margin-bottom:10px; }
        .customer-header { margin-bottom:6px; padding-bottom:4px; }
        .footer-email { font-size:12px; }
        .footer-since { font-size:10px; }
        .footer-actions a { padding:4px 8px; font-size:10px; }
        .print-spacer { display: none; }
        .print-footer-spacer { display: none; }
    }
</style>

<div class="print-container">
    {{-- Print header (fixed) --}}
    <div class="print-header">
        <div class="header-inner">
            <div class="logo">
                @if(!empty($business->businessLogo) && file_exists(public_path('uploads/business/' . $business->businessLogo)))
                    <img src="{{ asset('public/uploads/business/' . $business->businessLogo) }}" alt="Logo" />
                @endif
            </div>
            <div class="meta">
                <h2 class="business-name">{{ $business->businessName ?? config('app.name','Retail Nova') }}</h2>
                <div class="business-info">{{ $business->businessLocation ?? '' }} {{ $business->mobile ? ' | ' . $business->mobile : '' }} {{ $business->email ? ' | ' . $business->email : '' }}</div>
            </div>
        </div>
    </div>
    <div class="print-spacer"></div>
    <div class="text-center mb-4 no-print">
    <button data-onclick="window.print()" class="btn btn-primary">Print</button>
        <a href="{{ route('serviceProvideList') }}" class="btn btn-secondary">Back to List</a>
    </div>

    {{-- Note: footer moved to end of content so it can sit at bottom when printing --}}

    @if($groupedServices->isEmpty())
        <div class="alert alert-warning">No services found for the selected criteria.</div>
    @else
        @foreach($groupedServices as $customerId => $services)
            @php
                // When fetched via leftJoin we have a 'customer_name' field
                $customerName = $services->first()->customer_name ?? ($services->first()->customer->name ?? 'N/A');
                $totalAmount = $services->sum('amount');
            @endphp
            <div class="customer-section">
                <div class="customer-header">
                    <h4>Customer: {{ $customerName }}</h4>
                    <p>Date Range: {{ optional($services->min('created_at'))->format('Y-m-d') }} to {{ optional($services->max('created_at'))->format('Y-m-d') }}</p>
                </div>

                <table class="service-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Service Name</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $index => $service)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $service->serviceName }}</td>
                                <td>{{ $service->created_at->format('Y-m-d') }}</td>
                                <td class="text-right">{{ number_format($service->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                            <td class="text-right"><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    @endif
    {{-- Print footer (moved here so it appears after content and can sit at page bottom) --}}
    <div class="print-footer">
        <div class="footer-inner">
            <div class="footer-left">{!! $business->invoiceFooter ?? '' !!}</div>
            <div class="footer-center" style="text-align:center">
                @if(!empty($business->email))
                    <div class="footer-email">{{ $business->email }}</div>
                @endif
                <div class="footer-since">Since {{ optional($business->created_at)->format('d M, Y') ?? '' }}</div>
            </div>
            <div class="footer-right footer-actions" style="text-align:right">
                <a href="#">My Profile</a>
                <a href="#">Dashboard</a>
                <a href="#">Sign Out</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Optional: Automatically trigger print dialog
    window.onload = function() {
        // window.print();
    };
</script>
@endsection
