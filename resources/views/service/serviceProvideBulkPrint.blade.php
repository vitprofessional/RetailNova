@extends('include')
@section('backTitle') Bulk Print Provided Services @endsection
@section('container')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @foreach($groupedServices as $customer => $dates)
                    @foreach($dates as $date => $rows)
                        <div class="rn-service-invoice mb-4" style="page-break-after: always;">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <h5>{{ $customer }}</h5>
                                    <div class="text-muted">Date: {{ $date }}</div>
                                </div>
                                <div class="text-end">
                                    <h5>{{ $business->businessName ?? config('app.name') }}</h5>
                                    <div class="text-muted">{{ $business->address ?? '' }}</div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Service</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $total = 0; @endphp
                                        @foreach($rows as $i => $r)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $r->serviceName }}</td>
                                                <td class="text-end">{{ number_format($r->amount ?? 0, 2) }}</td>
                                                <td class="text-end">{{ $r->qty ?? 1 }}</td>
                                            </tr>
                                            @php $total += floatval($r->amount ?? 0); @endphp
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end"><strong>{{ number_format($total,2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            @if(!empty($business->invoiceFooter))
                                <div class="mt-3 small text-muted">{!! nl2br(e($business->invoiceFooter)) !!}</div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

                @section('scripts')
                @parent
                <script>
                    // Attempt to auto-open print dialog when this view is opened in a new window
                    (function(){
                        try{
                            // Give the browser a short moment to finish rendering
                            window.addEventListener('load', function(){
                                try{
                                    window.focus();
                                    setTimeout(function(){
                                        if(typeof window.print === 'function'){
                                            window.print();
                                        }
                                    }, 250);

                                    // Attempt to close the window after printing.
                                    // Use onafterprint where available and a timeout fallback.
                                    if (typeof window.onafterprint !== 'undefined'){
                                        window.onafterprint = function(){
                                            try{ window.close(); } catch(e){ /* noop */ }
                                        };
                                    }
                                    // Fallback: listen for print media change
                                    try{
                                        var mql = window.matchMedia && window.matchMedia('print');
                                        if(mql && typeof mql.addListener === 'function'){
                                            mql.addListener(function(m){ if(!m.matches){ try{ window.close(); }catch(e){} } });
                                        }
                                    }catch(e){ /* noop */ }

                                    // Extra fallback: close after a reasonable delay in case onafterprint is not fired
                                    setTimeout(function(){ try{ window.close(); }catch(e){} }, 5000);
                                }catch(e){ console.warn('Auto-print failed', e); }
                            });
                        }catch(e){ console.warn('Auto-print init failed', e); }
                    })();
                </script>
                @endsection
