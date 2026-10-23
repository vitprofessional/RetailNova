@extends('include') @section('backTitle'){{ __('mobile.title_sale_view') }}@endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-header text-center" style="color: #c20c0cff;"><h4>{{ __('sales.view_sale') }}</h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5>{{ __('sales.customer_details') }}</h5>
                <hr />
                <p><strong>{{ __('common.name') }}:</strong> {{ $customer->name }}</p>
                <p><strong>{{ __('common.mobile') }}:</strong> {{ $customer->mobile }}</p>
                <p><strong>{{ __('common.address') }}:</strong> {{ $customer->city }},{{ $customer->area }}</p>
            </div>
            <div class="col-md-4 mt-5">
                <p class="mt-1"><strong>{{ __('common.date') }}:</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</p>
                <p><strong>{{ __('common.reference') }}:</strong> {{ $invoice->reference }}</p>
                <p><strong>{{ __('common.note') }}:</strong>{{ $invoice->note }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="card">
                    <div class="card-body">
                        <h6>{{ __('sales.sale_summary') }}</h6>
                        <div class="row">
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.total') }}:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $invoice->totalSale }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.paid_amount') }}:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $invoice->paidAmount }}" style="width: 50%;" />
                                </div>
                            </div>
                            <div class="col-12 p-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label class="form-label" style="margin-bottom: 0px;">{{ __('common.due_amount') }}:</label><input disabled="" class="form-control form-control-sm" type="text" value="{{ $invoice->curDue }}" style="width: 50%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row  product-table">
            <div class="col-md-12">
                <h4>{{ __('sales.products_for_return') }}</h4>
                <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('sales.name') }}</th>
                            <th>{{ __('sales.sale_quantity') }}</th>
                            <th>{{ __('sales.price') }}</th>
                            <th>{{ __('sales.total') }}</th>
                            <th>{{ __('sales.return_qty') }}</th>
                            <th>{{ __('sales.return_amount') }}</th>
                            <th>{{ __('sales.serial_nos_if_any') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($items)
                        @php
                        $sl = 1;
                        @endphp
                        @foreach($items as $item)
                        <tr class="product-row">
                            <td>{{ $sl }}</td>
                            <td>{{ $item->productName }}</td>
                            <td>{{ $item->qty }}</td>
                            <td>{{ $item->salePrice }}</td>
                            <td>{{ number_format($item->totalSale ?? 0, 2, '.', ',') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        @php
                        $sl++;
                        @endphp
                        @endforeach
                        @else
                        <tr>
                            <td>2</td>
                            <td>{{ __('sales.crime_chake') }}</td>
                            <td>20</td>
                            <td>10</td>
                            <td>200</td>
                            <td><input type="checkbox" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="" /></td>
                            <td><input type="number" class="form-control form-control-sm" value="0" /></td>
                            <td></td>
                        </tr>
        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 text-end mx-5 text-danger"><h5>{{ __('sales.total_return_amount') }}: <span id="grandTotal">0</span></h5></div>
        <div class="row shadow p-3">
            <div class="col-12">
                <h5 class="card-title">{{ __('sales.return_details') }}</h5>
                <form>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">{{ __('sales.return_note') }}:</label><textarea class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="submit" class="btn btn-success w-100">{{ __('sales.submit_return') }}</button>
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-center">
                        <div class="d-flex gap-4 w-100">
                            <button type="button" class="btn btn-success w-100">{{ __('sales.return_and_refund') }}</button>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('customScript')
@endsection
