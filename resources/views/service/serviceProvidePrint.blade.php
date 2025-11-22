<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Provided Service</title>
    <style>
        body{font-family:Arial,sans-serif;margin:24px;color:#222}
        h2{margin:0 0 16px;font-size:20px}
        table{width:100%;border-collapse:collapse;margin-bottom:16px}
        th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:13px}
        th{background:#f5f5f5}
        .meta{font-size:12px;color:#555;margin-top:8px}
    </style>
</head>
<body onload="window.print()">
    <h2>Provided Service #{{ $row->id }}</h2>
    <table>
        <tr><th>Customer</th><td>{{ $row->customer_name ?? 'Customer #'.$row->customerName }}</td></tr>
        <tr><th>Service</th><td>{{ $row->serviceName }}</td></tr>
        <tr><th>Qty</th><td>{{ $row->qty ?? '-' }}</td></tr>
        <tr><th>Rate</th><td>{{ isset($row->rate) ? number_format($row->rate,2) : '-' }}</td></tr>
        <tr><th>Amount</th><td>{{ number_format($row->amount ?? (($row->rate ?? 0)*($row->qty ?? 1)),2) }}</td></tr>
        <tr><th>Note</th><td>{{ $row->note ?? '-' }}</td></tr>
        <tr><th>Date</th><td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td></tr>
    </table>
    <div class="meta">Printed at {{ now()->format('Y-m-d H:i:s') }}</div>
</body>
</html>