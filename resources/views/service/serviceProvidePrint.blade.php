<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('common.print_provided_service') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: white;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        .header-section {
            border-bottom: 3px solid #4680ff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-section h2 {
            font-size: 24px;
            color: #4680ff;
            margin-bottom: 10px;
        }
        .header-info {
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
            width: 30%;
        }
        td {
            background-color: white;
        }
        tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        .footer-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .amount-highlight {
            font-weight: bold;
            color: #4680ff;
            font-size: 16px;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .print-container {
                padding: 0;
            }
        }
    </style>
@include('partials.localization')
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="header-section">
            <h2>{{ __('common.provided_service_record') }}</h2>
            <div class="header-info">{{ __('common.service_id') }}: {{ $row->id }}</div>
        </div>

        <table>
            <tr><th>{{ __('common.customer') }}</th><td>{{ $row->customerLabel() }}</td></tr>
            <tr><th>{{ __('common.service') }}</th><td>{{ $row->serviceName }}</td></tr>
            <tr><th>{{ __('common.quantity') }}</th><td>{{ $row->qty ?? '-' }}</td></tr>
            <tr><th>{{ __('common.rate') }}</th><td>{{ isset($row->rate) ? '$' . number_format($row->rate, 2) : '-' }}</td></tr>
            <tr><th>{{ __('common.amount') }}</th><td class="amount-highlight">${{ number_format($row->amount ?? (($row->rate ?? 0)*($row->qty ?? 1)), 2) }}</td></tr>
            <tr><th>{{ __('common.notes') }}</th><td>{{ $row->note ?? '-' }}</td></tr>
            <tr><th>{{ __('common.date') }}</th><td>{{ optional($row->created_at)->format('M d, Y - h:i A') }}</td></tr>
        </table>

        <div class="footer-section">
            <p>{{ __('common.printed_on') }} {{ now()->format('F d, Y') }} at {{ now()->format('h:i A') }}</p>
            <p style="margin-top: 10px; font-size: 11px;">{{ __('common.this_is_a_computer_generated_document') }}</p>
        </div>
    </div>
</body>
</html>