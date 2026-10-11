<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('common.service_invoice') }} {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="/css/app.css" />
    <style>
        @page{ margin: 10mm; }
        body{ font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        .table{ width: 100%; border-collapse: collapse; }
        .table th, .table td{ border: 1px solid #ddd; padding: 6px; }
        .text-end{ text-align: right; }
    </style>
@include('partials.localization')
</head>
<body>
    <div class="invoice-root">
        <h3>{{ $business->businessName ?? config('app.name') }}</h3>
        <div>{{ $business->address ?? '' }}</div>
        <div>{{ $business->phone ?? '' }}</div>
        <hr />
        <h4>{{ __('common.invoice') }}: {{ $invoice->invoice_number }}</h4>
        <div>{{ __('common.created') }}: {{ $invoice->created_at->format('Y-m-d H:i') }}</div>

        <h5>{{ __('invoices.bill_to') }}</h5>
        @if($customer)
            @php
                $addr = '';
                if(!empty($customer->area)){
                    $addr = trim($customer->area);
                } elseif(!empty($customer->address)) {
                    $addr = trim($customer->address);
                }
            @endphp
            <div><strong>{{ $customer->name }}</strong></div>
            @if(!empty($addr))<div>{{ $addr }}</div>@endif
            @if(!empty($customer->mobile))<div>{{ __('common.contact') }}: {{ $customer->mobile }}</div>@endif
        @else
            <div><strong>{{ __('invoices.walking_customer') }}</strong></div>
        @endif

        <table class="table" style="margin-top:10px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('invoices.service') }}</th>
                    <th class="text-end">{{ __('invoices.rate') }}</th>
                    <th class="text-end">{{ __('invoices.qty') }}</th>
                    <th class="text-end">{{ __('invoices.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                <tr>
                    <td>{{ $idx+1 }}</td>
                    <td>{{ $item->service_name }}</td>
                    <td class="text-end">{{ number_format($item->rate,2) }}</td>
                    <td class="text-end">{{ $item->qty }}</td>
                    <td class="text-end">{{ number_format($item->line_total,2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>{{ __('invoices.total') }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($invoice->total_amount,2) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        @if($invoice->note)
        <div style="margin-top:10px;">
            <strong>{{ __('common.note') }}:</strong>
            <div>{{ $invoice->note }}</div>
        </div>
        @endif
    </div>
    <script>
        window.onload = function(){ window.print(); setTimeout(function(){ window.close(); }, 500); };
    </script>
</body>
</html>