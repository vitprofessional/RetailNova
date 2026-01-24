@extends('include') @section('backTitle')provide service @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 mb-3">
                <h4>Provide Service</h4>
                <div id="invoicePreview" class="small text-muted mt-1">Invoice preview: <span id="invoiceNumberPreview">-</span></div>
                @if(!\Schema::hasTable('service_invoices'))
                    <div class="alert alert-warning mt-2">Service Invoices are not enabled. Saving will use legacy mode. Run <code>php artisan migrate</code> to enable invoices and printing.</div>
                @endif
            </div>
        </div>
        <form action="{{ route('saveProvideService') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="customerName" class="form-label">Customer Name</label>
                        <select id="customerName" name="customerName" class="form-control" required>
                                    <option value="">Select Customer Name</option>
                                    <!--  form option show proccessing -->
                                  @if(!empty($customerList) && count($customerList)>0)
                                  @foreach($customerList as $customerData)
                                    <option value="{{$customerData->id}}">{{$customerData->name}}</option>
                                    @endforeach
                                    @endif
                                </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="note" class="form-label">Note</label>
                        <input type="text" class="form-control" placeholder="" id="note" name="note" />
                    </div>
                </div>
                <div class="col-md-12 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="walkinCheck" />
                        <label class="form-check-label" for="walkinCheck">Walking Customer (no registered customer)</label>
                        <input type="hidden" id="is_walkin" name="is_walkin" value="0" />
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Select A Service </label>
                                                <select id="serviceType" class="form-control" name="serviceType" disabled>
                                                    <option value="">Select Service (choose customer first)</option>
                                    <!--  form option show proccessing -->
                                  @if(!empty($serviceList) && count($serviceList)>0)
                                  @foreach($serviceList as $serviceData)
                                    <option value="{{$serviceData->id}}">{{$serviceData->serviceName}}</option>
                                    @endforeach
                                    @endif
                        </select>
                    </div>
                </div>
            </div>
                <div class="row">
                    <div class="mb-3 table-responsive product-table">
                        <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                            <thead class="bg-white text-uppercase">
                                <tr>
                                    <th>Service Type</th>
                                    <th>Rate</th>
                                    <th>Qty</th>
                                    <th>Line Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="serviceBox">

                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                                    <td colspan="2"><strong id="grandTotalDisplay">0.00</strong>
                                        <input type="hidden" name="grandTotal" id="grandTotal" value="0">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div id="saveButton" class="d-none mt-2">
                        <button class="btn btn-primary btn-sm" type="submit">Save</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



    @endsection

    @section('scripts')
    @parent
    <script>
    window.__jqOnReady(function(){
        try{
            // service row helpers
            function serviceSelect(){
                var data = $('#serviceType').val();
                if(!data) return;

                $.ajax({
                    method: 'get',
                    url: '{{url('/')}}/service/details/'+data,
                    contentType: 'html',
                    success:function(result){
                        // if a row for this service exists, increment qty
                        var existing = $('#serviceBox').find('tr[data-service-id="'+result.id+'"]');
                        if(existing.length){
                            var qtyInput = existing.find('.qty-input');
                            var cur = parseInt(qtyInput.val()) || 0;
                            qtyInput.val(cur + 1);
                        }else{
                            var svcName = result.serviceName ? String(result.serviceName).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';
                            var rateVal = (typeof result.rate !== 'undefined' && result.rate !== null) ? parseFloat(result.rate).toFixed(2) : '0.00';
                            var field = '<tr data-service-id="'+result.id+'">'
                                +'<td><input type="text" class="form-control" name="serviceName[]" value="'+svcName+'" readonly/></td>'
                                +'<td><input type="number" step="0.01" class="form-control rate-input" value="'+rateVal+'" name="rate[]" /></td>'
                                +'<td><input type="number" min="1" class="form-control qty-input" value="1" name="qty[]" /></td>'
                                +'<td><input type="text" readonly class="form-control line-total-input" value="'+rateVal+'" /></td>'
                                +'<td><button type="button" class="btn btn-sm btn-outline-danger remove-service" title="delete" data-service-id="'+result.id+'"><i class="ri-delete-bin-line mr-0"></i></button></td>'
                                +'</tr>';

                            $('#serviceBox').append(field);
                        }
                        $('#saveButton').removeClass('d-none');
                        recalcTotals();
                        // reset selection to allow quick adding
                        $('#serviceType').val('');
                    },
                    error:function(){ /* noop */ }
                });
            }

            function recalcTotals(){
                var grand = 0;
                $('#serviceBox').children('tr').each(function(){
                    var rate = parseFloat($(this).find('.rate-input').val()) || 0;
                    var qty = parseInt($(this).find('.qty-input').val()) || 0;
                    var line = rate * qty;
                    $(this).find('.line-total-input').val(line.toFixed(2));
                    grand += line;
                });
                $('#grandTotalDisplay').text(grand.toFixed(2));
                $('#grandTotal').val(grand.toFixed(2));
            }

            // enable service select when a customer is chosen
            $('#customerName').on('change', function(){
                var v = $(this).val();
                if(v){
                    $('#serviceType').prop('disabled', false);
                }else{
                    $('#serviceType').prop('disabled', true);
                    // optional: clear rows when no customer selected
                    // $('#serviceBox').empty(); recalcTotals(); $('#saveButton').addClass('d-none');
                }
            });

            // walking customer toggle
            $('#walkinCheck').on('change', function(){
                var checked = $(this).is(':checked');
                if(checked){
                    $('#customerName').prop('disabled', true).val('');
                    $('#is_walkin').val('1');
                    $('#serviceType').prop('disabled', false);
                }else{
                    $('#customerName').prop('disabled', false);
                    $('#is_walkin').val('0');
                    // disable service select until a customer chosen
                    if(!$('#customerName').val()) $('#serviceType').prop('disabled', true);
                }
            });

            // initialize state in case a customer is pre-selected
            if($('#customerName').val()){
                $('#serviceType').prop('disabled', false);
            }

            // bind change on serviceType to add service
            $('#serviceType').on('change', serviceSelect);

            // fetch invoice preview whenever services change or customer/walkin toggles
            function refreshInvoicePreview(){
                // only ask when there's at least one service row
                if($('#serviceBox').children('tr').length === 0){
                    $('#invoiceNumberPreview').text('-');
                    return;
                }
                window.__jqOnReady(function(){
                    $.ajax({
                        url: '{{ route('service.nextInvoiceNumber') }}',
                        method: 'get',
                        success: function(res){
                            if(res && res.ok){
                                $('#invoiceNumberPreview').text(res.invoice_number);
                            }
                        },
                        error: function(){ /* noop */ }
                    });
                });
            }

            // refresh preview after changes
            $(document).on('change', '#customerName, #walkinCheck, #serviceType', function(){ refreshInvoicePreview(); });
            $(document).on('input', '#serviceBox .qty-input, #serviceBox .rate-input', function(){ refreshInvoicePreview(); });

            // delegate input changes for dynamic rows
            $(document).on('input', '#serviceBox .rate-input, #serviceBox .qty-input', function(){
                recalcTotals();
            });

            // remove row handler
            $(document).on('click', '.remove-service', function(e){
                e.preventDefault();
                var tr = $(this).closest('tr');
                tr.remove();
                recalcTotals();
                if($('#serviceBox').children('tr').length === 0){
                    $('#saveButton').addClass('d-none');
                }
            });
        }catch(e){ console.warn('provideService scripts init failed', e); }
    });
    </script>
@endsection
