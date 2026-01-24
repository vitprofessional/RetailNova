@extends('include')
@section('backTitle') Service Invoice @endsection
@section('container')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4>Service Invoice - {{ $invoice->invoice_number }}</h4>
                    <small class="text-muted">Created: {{ $invoice->created_at->format('Y-m-d H:i') }}</small>
                </div>
                <div>
                    <a href="{{ route('serviceInvoicePrint',['id'=>$invoice->id]) }}" class="btn btn-outline-secondary" target="_blank"><i class="fa-solid fa-print"></i> Print</a>
                    <a href="{{ route('serviceProvideList') }}" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Bill To</h6>
                        @if($customer)
                            <p><strong>{{ $customer->name }}</strong><br>{{ $customer->mobile ?? '' }}<br>{{ $customer->email ?? '' }}</p>
                        @else
                            <p><strong>Walking Customer</strong></p>
                        @endif
                    </div>
                    <div class="col-md-6 text-end">
                        <h6>{{ $business->businessName ?? config('app.name') }}</h6>
                        <p>{{ $business->address ?? '' }}<br>{{ $business->phone ?? '' }}</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered rn-table-pro">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Line Total</th>
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
                                <td colspan="4" class="text-end"><strong>Total</strong></td>
                                <td class="text-end"><strong>{{ number_format($invoice->total_amount,2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($invoice->note)
                <div class="mt-3">
                    <strong>Note:</strong>
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
