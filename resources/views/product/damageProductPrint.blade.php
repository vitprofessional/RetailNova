<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Damage Record</title>
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
            border-bottom: 3px solid #dc3545;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-section h2 {
            font-size: 24px;
            color: #dc3545;
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
        .amount-highlight {
            font-weight: bold;
            color: #dc3545;
            font-size: 16px;
        }
        .footer-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
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
            <h2>Damage Record</h2>
            <div class="header-info">Record ID: {{ $damage->id }}</div>
        </div>
        
        <table>
            <tr><th>Reference</th><td>{{ $damage->reference ?? '-' }}</td></tr>
            <tr><th>Product</th><td>{{ $damage->product ? $damage->product->name : '-' }}</td></tr>
            <tr><th>Quantity Damaged</th><td>{{ $damage->qty }}</td></tr>
            <tr><th>Unit Price</th><td>${{ number_format($damage->sale_price ?? $damage->buy_price ?? 0, 2) }}</td></tr>
            <tr><th>Total Loss</th><td class="amount-highlight">${{ number_format($damage->total ?? 0, 2) }}</td></tr>
            <tr><th>Reported By</th><td>{{ $damage->admin ? $damage->admin->name : ($damage->admin_id ? 'Admin #'.$damage->admin_id : '-') }}</td></tr>
            <tr><th>Report Date</th><td>{{ optional($damage->date)->format('M d, Y') }}</td></tr>
        </table>
        
        <div class="footer-section">
            <p>Printed on {{ now()->format('F d, Y') }} at {{ now()->format('h:i A') }}</p>
            <p style="margin-top: 10px; font-size: 11px;">This is a computer-generated damage report.</p>
        </div>
    </div>
</body>
</html>
