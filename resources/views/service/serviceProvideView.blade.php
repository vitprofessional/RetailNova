@extends('include')
@section('backTitle') Provided Service View @endsection
@section('container')
<div class="row mt-4">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Provided Service #{{ $row->id }}</h4>
                <table class="table table-bordered">
                    <tr><th>Customer</th><td>{{ $row->customer_name ?? 'Customer #'.$row->customerName }}</td></tr>
                    <tr><th>Service</th><td>{{ $row->serviceName }}</td></tr>
                    <tr><th>Qty</th><td>{{ $row->qty ?? '-' }}</td></tr>
                    <tr><th>Rate</th><td>{{ isset($row->rate) ? number_format($row->rate,2) : '-' }}</td></tr>
                    <tr><th>Amount</th><td>{{ number_format($row->amount ?? (($row->rate ?? 0)*($row->qty ?? 1)),2) }}</td></tr>
                    <tr><th>Note</th><td>{{ $row->note ?? '-' }}</td></tr>
                    <tr><th>Date</th><td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td></tr>
                </table>
                <div class="mt-3 d-flex">
                    <a href="{{ route('serviceProvideList') }}" class="btn btn-outline-primary mr-2">Back</a>
                    <a href="{{ route('provideServicePrint',$row->id) }}" target="_blank" class="btn btn-primary">Print</a>
                    <a href="{{ route('delProvideService',$row->id) }}" class="btn btn-danger ml-auto" data-confirm="delete">Delete</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection