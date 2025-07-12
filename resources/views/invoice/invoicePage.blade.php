@extends('include') @section('backTitle') invoice @endsection @section('container')

  <div class="invoice-box mb-4">
    <div class="header">
      <div class="invoice-title">INVOICE</div>
      <div class="store-info">
        <strong>Computer Care</strong><br>
        Office Road, Burichong Bazar, Cumilla<br>
        Phone: 0123456789<br>
        Email: info@computercare.com
      </div>
    </div>

    <table class="info-table">
      <tr>
        <td><strong>Invoice #:</strong> {{ $invoice->invoice }}</td>
        <td class="text-right"><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</td>
      </tr>
      <tr>
        <td ><strong>Customer:</strong> {{ $customer->name }}</td>
        <td class="text-right"><strong>Phone:</strong> {{ $customer->mobile }}</td>
      </tr>
    </table>

    <table class="product-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Item</th>
          <th>Qty</th>
          <th>Unit Price</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody class="text-right">
        @if($items)
        @php
        $sl = 1;
        @endphp
        @foreach($items as $item)
        <tr>
          <td>{{ $sl }}</td>
          <td>{{ $item->productName }}</td>
          <td>{{ $item->qty }}</td>
          <td>{{ $item->salePrice }}</td>
          <td>{{ number_format($item->totalSale ?? 0, 2, '.', ',') }}</td>
        </tr>
        @php
        $sl++;
        @endphp
        @endforeach
        @else
        <tr>
          <td colspan="5">No items found</td>
        </tr>
        @endif
      </tbody>
    </table>

    <table class="summary-table">
      <tr>
        <td class="text-right">Subtotal:</td>
        <td class="text-right">{{ number_format($invoice->totalSale ?? 0, 2, '.', ',') }} ৳</td>
      </tr>
      <tr>
        <td class="text-right">Discount:</td>
        <td class="text-right">{{ number_format($invoice->discountAmount ?? 0, 2, '.', ',') }} ৳</td>
      </tr>
      <tr class="total">
        <td class="text-right">Grand Total:</td>
        <td class="text-right">{{ number_format($invoice->grandTotal ?? 0, 2, '.', ',') }} ৳</td>
      </tr>
      <tr>
        <td class="text-right">Paid:</td>
        <td class="text-right">{{ number_format($invoice->paidAmount ?? 0, 2, '.', ',') }} ৳</td>
      </tr>
      <tr>
        <td class="text-right">Previous Due:</td>
        <td class="text-right">{{ number_format($invoice->prevDue ?? 0, 2, '.', ',') }} ৳</td>
      </tr>
      <tr>
        <td class="text-right">Current Due:</td>
        <td class="text-right">{{ number_format($invoice->curDue ?? 0, 2, '.', ',') }} ৳</td>
      </tr>
    </table>

    <div class="qr-code">
      <p><strong>Scan to Verify:</strong></p>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ route('invoiceGenerate',['id'=>$invoice->id]).'?'.$invoice->invoice }}" alt="QR Code">
    </div>

    <div class="footer">
      Thank you for shopping with GreenTech!<br>
      Powered by YourPOS Software
    </div>
  </div>
  
@endsection
