@extends('include') @section('backTitle'){{ __('navigation.sales_returns') }}@endsection @section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="sale-returns">
  <ul class="rn-m-cards" role="list">
    @forelse($returns as $return)
      @php
        $sale = $return->sale;
        $customer = \App\Models\Customer::displayLabel($sale?->customer, $sale?->customerId, $sale?->businessId);
        $actions = [['type'=>'link','label'=>__('sales.view'),'url'=>route('saleReturnDetails',['id'=>$return->id]),'icon'=>'ri-eye-line']];
      @endphp
      <x-m.list-card :card="['id'=>$return->id,'url'=>route('saleReturnDetails',['id'=>$return->id]),'avatar'=>mb_substr($customer,0,1),'title'=>$sale?->invoice ?: __('sales.sale_id').' '.$return->id,'subtitle'=>$customer,'amount'=>\App\Support\LocalizedNumber::money($return->totalReturnAmount ?? 0),'meta'=>array_values(array_filter([$return->created_at ? \App\Support\MobileDateTime::date($return->created_at) : null, __('sales.items_returned').': '.\App\Support\LocalizedNumber::count($return->items?->sum('qty') ?? 0), __('sales.adjust_amount').': '.\App\Support\LocalizedNumber::money($return->adjustAmount ?? 0)])),'badge'=>['label'=>__('sales.sales_return_list'),'tone'=>'warning'],'actions'=>$actions]" />
    @empty
      <li class="rn-m-empty">{{ __('sales.no_sale_returns_found') }}</li>
    @endforelse
  </ul>
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="col-12 p-0 mt-0 mb-4">
                    <h4>{{ __('sales.sales_return_list') }}</h4>
                </div>
                <div class="rounded mb-2 table-responsive product-table">
                    <table class="data-tables table mb-0 table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th style="width: 50px;">
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllReturns" />
                                        <label for="selectAllReturns" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>{{ __('sales.sale_id') }}</th>
                                <th>{{ __('sales.customer') }}</th>
                                <th>{{ __('sales.return_amount') }}</th>
                                <th>{{ __('sales.adjust_amount') }}</th>
                                <th>{{ __('sales.items_returned') }}</th>
                                <th>{{ __('sales.created_by') }}</th>
                                <th>{{ __('sales.date') }}</th>
                                <th style="width: 100px;">{{ __('sales.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($returns && count($returns) > 0)
                                @foreach($returns as $return)
                                    @php
                                        $sale = $return->sale;
                                        $customer = $sale ? \App\Models\Customer::find($sale->customerId) : null;
                                        $customerName = \App\Models\Customer::displayLabel($customer, $sale?->customerId, $sale?->businessId);
                                        $totalQtyReturned = 0;
                                        if ($return->items) {
                                            $totalQtyReturned = $return->items->sum('qty');
                                        }
                                        $createdBy = $sale && $sale->salesperson ? $sale->salesperson->fullName : '-';
                                        $returnAmount = \App\Support\Currency::format($return->totalReturnAmount ?? 0);
                                        $adjustAmount = \App\Support\Currency::format($return->adjustAmount ?? 0);
                                        try {
                                            $dateFmt = \Carbon\Carbon::parse($return->created_at)->format('d M Y');
                                        } catch (\Exception $e) {
                                            $dateFmt = (string)($return->created_at ?? '-');
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="checkbox d-inline-block">
                                                <input type="checkbox" class="checkbox-input bulk-select" value="{{ $return->id }}" />
                                                <label class="mb-0"></label>
                                            </div>
                                        </td>
                                        <td>{{ $sale ? $sale->invoice : '-' }}</td>
                                        <td>{{ $customerName }}</td>
                                        <td>{{ $returnAmount }}</td>
                                        <td>{{ $adjustAmount }}</td>
                                        <td><span class="badge bg-info">{{ $totalQtyReturned }}</span></td>
                                        <td>{{ $createdBy }}</td>
                                        <td>{{ $dateFmt }}</td>
                                        <td>
                                            <div style="display: flex; gap: 4px; justify-content: center;">
                                                <a href="{{ route('saleReturnDetails', ['id' => $return->id]) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('sales.view') }}" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center">{{ __('sales.no_sale_returns_found') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endmobile
@endsection

@section('scripts')
    @include('customScript')
    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize select all checkbox
                const selectAllCheckbox = document.getElementById('selectAllReturns');
                const bulkSelects = document.querySelectorAll('.bulk-select');

                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        bulkSelects.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                }

                // Initialize DataTable if available
                try {
                    if (window.jQuery && $.fn.DataTable) {
                        $('#returnSalesTable').DataTable({
                            responsive: true,
                            order: [[7, 'desc']],
                            columnDefs: [
                                { orderable: false, targets: [0, 8] }
                            ]
                        });
                    }
                } catch(e) {}
            });
        })();
    </script>
@endsection
