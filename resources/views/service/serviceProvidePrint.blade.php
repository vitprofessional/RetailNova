<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Provided Service</title>
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
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="header-section">
            <h2>Provided Service Record</h2>
            <div class="header-info">Service ID: {{ $row->id }}</div>
        </div>
        
        <table>
            <tr><th>Customer</th><td>{{ $row->customer_name ?? 'Customer #'.$row->customerName }}</td></tr>
            <tr><th>Service</th><td>{{ $row->serviceName }}</td></tr>
            <tr><th>Quantity</th><td>{{ $row->qty ?? '-' }}</td></tr>
            <tr><th>Rate</th><td>{{ isset($row->rate) ? '$' . number_format($row->rate, 2) : '-' }}</td></tr>
            <tr><th>Amount</th><td class="amount-highlight">${{ number_format($row->amount ?? (($row->rate ?? 0)*($row->qty ?? 1)), 2) }}</td></tr>
            <tr><th>Notes</th><td>{{ $row->note ?? '-' }}</td></tr>
            <tr><th>Date</th><td>{{ optional($row->created_at)->format('M d, Y - h:i A') }}</td></tr>
        </table>
        
        <div class="footer-section">
            <p>Printed on {{ now()->format('F d, Y') }} at {{ now()->format('h:i A') }}</p>
            <p style="margin-top: 10px; font-size: 11px;">This is a computer-generated document.</p>
        </div>
    </div>
</body>
</html>