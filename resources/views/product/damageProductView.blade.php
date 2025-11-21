@extends('include')
@section('backTitle') Damage Product View @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body">
                <h4>Damage Record #{{ $damage->id }}</h4>
                <table class="table table-bordered">
                    <tr><th>Reference</th><td>{{ $damage->reference ?? '-' }}</td></tr>
                    <tr><th>Product</th><td>{{ $damage->product ? $damage->product->name : '-' }}</td></tr>
                    <tr><th>Quantity</th><td>{{ $damage->qty }}</td></tr>
                    <tr><th>Unit Price</th><td>{{ number_format($damage->sale_price ?? $damage->buy_price ?? 0,2) }}</td></tr>
                    <tr><th>Total</th><td>{{ number_format($damage->total ?? 0,2) }}</td></tr>
                    <tr><th>Reported By</th><td>{{ $damage->admin ? $damage->admin->name : ($damage->admin_id ? 'Admin #'.$damage->admin_id : '-') }}</td></tr>
                    <tr><th>Date</th><td>{{ optional($damage->date)->format('Y-m-d') }}</td></tr>
                </table>
                <div class="mt-3">
                    <a href="{{ route('damageProductList') }}" class="btn btn-outline-primary">Back to list</a>
                    <a href="{{ route('damageProductPrint', $damage->id) }}" target="_blank" class="btn btn-primary">Print</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
