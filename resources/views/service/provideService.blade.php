@extends('include') @section('backTitle')provide service @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 mb-3">
                <h4>Provide Service</h4>
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
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Select A Service </label>
                                                <select id="serviceType" class="form-control" name="serviceType" data-onchange="serviceSelect()" >
                              <option value="">Select Service</option>
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
                        <table class="table mb-0 table-bordered rounded-0">
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
                        if($('#serialField'+result.id).length){ return; }
                        var field = '<tr id="serialField'+result.id+'">'
                            +'<td><input type="text" class="form-control" name="serviceName[]" value="'+result.serviceName+'" readonly/></td>'
                            +'<td><input type="number" step="0.01" class="form-control rate-input" value="'+result.rate+'" name="rate[]" /></td>'
                            +'<td><input type="number" min="1" class="form-control qty-input" value="1" name="qty[]" /></td>'
                            +'<td><input type="text" readonly class="form-control line-total-input" value="'+(parseFloat(result.rate).toFixed(2))+'" /></td>'
                            +'<td><button type="button" class="badge bg-warning mr-2" title="delete" data-onclick="removeServiceRow('+result.id+')"><i class="ri-delete-bin-line mr-0"></i></button></td>'
                            +'</tr>';

                        $('#serviceBox').append(field);
                        $('#saveButton').removeClass('d-none');
                        recalcTotals();
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

            // expose serviceSelect globally for inline handlers
            window.serviceSelect = serviceSelect;

            // delegate input changes for dynamic rows
            $(document).on('input', '#serviceBox .rate-input, #serviceBox .qty-input', function(){
                recalcTotals();
            });
        }catch(e){ console.warn('provideService scripts init failed', e); }
    });
    </script>
@endsection
