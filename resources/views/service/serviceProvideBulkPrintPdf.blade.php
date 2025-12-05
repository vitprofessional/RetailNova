<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Provided Services - Bulk Print</title>
    <style>
        /* Dompdf-friendly CSS: use explicit sizes and avoid complex rules */
        @page { size: A4 portrait; margin: 12mm 10mm 14mm 10mm; }
        html, body { font-family: DejaVu Sans, Arial, sans-serif; color: #222; }
        body { margin: 0; padding: 0; font-size: 12px; }
        .header { text-align: left; border-bottom: 1px solid #ddd; padding-bottom: 6px; margin-bottom: 6px; }
        .header .logo { float: left; margin-right: 8px; }
        .header .meta { display: inline-block; vertical-align: middle; }
        .header .business-name { font-size: 14px; font-weight: 700; margin: 0; }
        .header .business-info { font-size: 10px; margin-top: 2px; color:#444 }

        .customer-section { margin-bottom: 8px; padding: 6px 0; }
        .customer-header { font-weight:700; font-size:12px; margin-bottom:4px; }
        .customer-range { font-size:10px; color:#555; margin-bottom:4px; }

        table { width: 100%; border-collapse: collapse; font-size:11px; }
        th, td { border: 1px solid #ddd; padding: 6px 6px; }
        th { background:#f7f7f7; font-size:11px; }
        .text-right { text-align: right; }
        .total-row td { font-weight:700; border-top: 1px solid #333; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size:11px; border-top:1px solid #ddd; padding-top:6px; }
        .footer .center { text-align:center; font-weight:600 }
        .footer .meta { text-align:left; font-size:10px; color:#555 }

        /* Avoid page-breaks inside rows and customer sections */
        .customer-section, tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if(!empty($business->businessLogo) && file_exists(public_path('uploads/business/' . $business->businessLogo)))
                <img src="{{ public_path('uploads/business/' . $business->businessLogo) }}" alt="Logo" style="height:48px; width:auto;" />
            @endif
        </div>
        <div class="meta">
            <div class="business-name">{{ $business->businessName ?? config('app.name','Retail Nova') }}</div>
            <div class="business-info">{{ $business->businessLocation ?? '' }} {{ $business->mobile ? ' | ' . $business->mobile : '' }}</div>
        </div>
    </div>

    <div class="content">
        @if(empty($groupedServices))
            <div>No services found for the selected criteria.</div>
        @else
            @foreach($groupedServices as $customerName => $dates)
                @foreach($dates as $date => $rows)
                    @php $total = array_sum(array_map(function($r){ return floatval($r->amount ?? 0); }, $rows)); @endphp
                    <div class="customer-section">
                        <div class="customer-header">Customer: {{ $customerName }} - {{ $date }}</div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:6%;">#</th>
                                    <th style="width:64%;">Service Name</th>
                                    <th style="width:15%;" class="text-right">Qty</th>
                                    <th style="width:15%;" class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $i => $s)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $s->serviceName }}</td>
                                        <td class="text-right">{{ $s->qty ?? 1 }}</td>
                                        <td class="text-right">{{ number_format($s->amount,2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($total,2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endforeach
            @endforeach
        @endif
    </div>

    <div class="footer">
        <div class="meta">{{ $business->invoiceFooter ?? '' }}</div>
        <div class="center">{{ $business->email ?? '' }} &nbsp; | &nbsp; Since {{ optional($business->created_at)->format('d M, Y') ?? '' }}</div>
    </div>
</body>
</html>
