<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Damage Record</title>
    <style>
        body{font-family: Arial, sans-serif; margin:20px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border:1px solid #ccc;text-align:left}
        h2{margin-bottom:12px}
    </style>
</head>
<body data-onload="window.print()">
    <h2>Damage Record #{{ $damage->id }}</h2>
    <table>
        <tr><th>Reference</th><td>{{ $damage->reference ?? '-' }}</td></tr>
        <tr><th>Product</th><td>{{ $damage->product ? $damage->product->name : '-' }}</td></tr>
        <tr><th>Quantity</th><td>{{ $damage->qty }}</td></tr>
        <tr><th>Unit Price</th><td>{{ number_format($damage->sale_price ?? $damage->buy_price ?? 0,2) }}</td></tr>
        <tr><th>Total</th><td>{{ number_format($damage->total ?? 0,2) }}</td></tr>
        <tr><th>Reported By</th><td>{{ $damage->admin ? $damage->admin->name : ($damage->admin_id ? 'Admin #'.$damage->admin_id : '-') }}</td></tr>
        <tr><th>Date</th><td>{{ optional($damage->date)->format('Y-m-d') }}</td></tr>
    </table>
</body>
</html>
