@extends('include')
@section('backTitle'){{ __('mobile.title_provided_service_view') }}@endsection
@section('container')
<div class="row mt-4">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Provided Service #{{ $row->id }}</h4>
                <table class="table table-bordered rn-table-pro">
                    <tr><th>{{ __('common.customer') }}</th><td>{{ $row->customer_name ?? 'Customer #'.$row->customerName }}</td></tr>
                    <tr><th>{{ __('common.service') }}</th><td>{{ $row->serviceName }}</td></tr>
                    <tr><th>{{ __('common.qty') }}</th><td>{{ $row->qty ?? '-' }}</td></tr>
                    <tr><th>{{ __('common.rate') }}</th><td>{{ isset($row->rate) ? number_format($row->rate,2) : '-' }}</td></tr>
                    <tr><th>{{ __('common.amount') }}</th><td>{{ number_format($row->amount ?? (($row->rate ?? 0)*($row->qty ?? 1)),2) }}</td></tr>
                    <tr><th>{{ __('common.note') }}</th><td>{{ $row->note ?? '-' }}</td></tr>
                    <tr><th>{{ __('common.date') }}</th><td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td></tr>
                </table>
                <div class="mt-3 d-flex">
                    <a href="{{ route('serviceProvideList') }}" class="btn btn-outline-primary mr-2">{{ __('common.back') }}</a>
                    <a href="{{ route('provideServicePrint',$row->id) }}" target="_blank" class="btn btn-primary">{{ __('common.print') }}</a>
                    <a href="{{ route('delProvideService',$row->id) }}" class="btn btn-danger ml-auto" data-confirm="delete">{{ __('common.delete') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection