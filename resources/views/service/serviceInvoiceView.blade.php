@extends('include')
@section('backTitle'){{ __('common.heading_service_invoice') }}@endsection
@section('container')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center rn-m-service-invoice-header">
                <div class="rn-m-service-invoice-actions">
                    <h4>{{ __('common.service_invoice') }} {{ $invoice->invoice_number }}</h4>
                    <small class="text-muted">{{ __('common.created') }}: @mobile{{ \App\Support\MobileDateTime::date($invoice->created_at) }} {{ \App\Support\MobileDateTime::time($invoice->created_at) }}@else{{ $invoice->created_at->format('Y-m-d H:i') }}@endmobile</small>
                </div>
                <div>
                    <a href="{{ route('serviceInvoicePrint',['id'=>$invoice->id]) }}" class="btn btn-outline-secondary" target="_blank"><i class="fa-solid fa-print"></i> {{ __('invoices.print') }}</a>
                    <a href="{{ route('serviceProvideList') }}" class="btn btn-secondary">{{ __('invoices.back_to_list') }}</a>
                </div>
            </div>
            <div class="card-body">
                @mobile
                <div class="rn-m-detail-summary"><span class="rn-m-detail-summary__status">{{ __('common.service_invoice') }}</span><strong>{{ \App\Support\Currency::format($invoice->total_amount) }}</strong><small>{{ __('common.invoice') }} · {{ $invoice->invoice_number }}</small></div>
                @endmobile
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>{{ __('invoices.bill_to') }}</h6>
                        @if($customer)
                            @php
                                $addr = '';
                                if(!empty($customer->area)){
                                    $addr = trim($customer->area);
                                } elseif(!empty($customer->address)) {
                                    $addr = trim($customer->address);
                                }
                            @endphp
                            <p>
                                <strong>{{ $customer->name }}</strong><br>
                                @if(!empty($addr)){{ $addr }}<br>@endif
                                @if(!empty($customer->mobile))Contact: {{ $customer->mobile }}@endif
                            </p>
                        @else
                            <p><strong>{{ __('invoices.walking_customer') }}</strong></p>
                        @endif
                    </div>
                    <div class="col-md-6 text-end">
                        <h6>{{ $business->businessName ?? config('app.name') }}</h6>
                        <p>{{ $business->address ?? '' }}<br>{{ $business->phone ?? '' }}</p>
                    </div>
                </div>

                <div class="table-responsive rn-m-detail-page">
                    <table class="table table-bordered rn-table-pro rn-m-detail-lines">
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
                                <td class="rn-m-detail-index">{{ $idx+1 }}</td>
                                <td data-label="{{ __('invoices.service') }}">{{ $item->service_name }}</td>
                                <td data-label="{{ __('invoices.rate') }}" class="text-end">{{ number_format($item->rate,2) }}</td>
                                <td data-label="{{ __('invoices.qty') }}" class="text-end">{{ $item->qty }}</td>
                                <td data-label="{{ __('invoices.line_total') }}" class="text-end">{{ number_format($item->line_total,2) }}</td>
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
                </div>

                @if($invoice->note)
                <div class="mt-3">
                    <strong>{{ __('common.note') }}:</strong>
                    <div>{{ $invoice->note }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
    (function(){
        // If autoprint flag present, trigger in-place print dialog on this invoice view.
        var auto = '{{ request()->query('autoprint') ? 1 : 0 }}';
        if(auto == 1){
            // small delay to ensure page renders before opening print dialog
            setTimeout(function(){
                try{
                    window.print();
                }catch(e){
                    console.warn('autoprint failed', e);
                    // fallback: open printable page in new tab
                    try{ window.open('{{ route('serviceInvoicePrint', ['id' => $invoice->id]) }}', '_blank'); }catch(e){}
                }
            }, 500);
        }
    })();
</script>
@endsection
