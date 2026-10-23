@extends('include')
@section('backTitle'){{ __('common.heading_addcoustomer') }}@endsection @section('container')
@php $canDeleteAllData = auth('admin')->user()?->canDeleteAllRelatedData() ?? false; @endphp

<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="row">
            @php
                if (isset($profile)) {
                    $name           = $profile->name;
                    $openingBalance = $profile->openingBalance;
                    $mail           = $profile->mail;
                    $mobile         = $profile->mobile;
                    $country        = $profile->country;
                    $state          = $profile->state;
                    $city           = $profile->city;
                    $area           = $profile->area;
                    $profileId      = $profile->id;
                } else {
                    $name           = '';
                    $openingBalance = '';
                    $mail           = '';
                    $mobile         = '';
                    $country        = '';
                    $state          = '';
                    $city           = '';
                    $area           = '';
                    $profileId      = '';
                }
            @endphp
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">{{ isset($profile) ? __('customers.update_customer') : __('customers.add_customer') }}</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{route('saveCustomer')}}" method="POST" >
                    @csrf
                    <div class="row">
                        @include('partials.business_selector', ['businessSelectionAllowed'=>app(\App\Services\PurchaseWorkflow::class)->platform(), 'businesses' => $businesses ?? [] , 'selectedBusinessId' => $profile->businessId ?? null])
                        <input type="hidden" name="profileId" value="{{ $profileId }}">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('common.name') }} *</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}"  id="fullName" name="fullName"  value="{{$name}}" required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="openingBalance" class="form-label">{{ __('customers.opening_balance') }}
                                    <span class="ml-1" data-toggle="tooltip" data-bs-toggle="tooltip" title="{{ __('common.positive_customer_owes_you_receivable_negative_you_owe_customer_payable') }}">
                                        <i class="ri-information-line"></i>
                                    </span>
                                </label>
                                <input type="number" step="1" class="form-control" placeholder="{{ __('common.enter_opening_balance') }}" id="openingBalance" name="openingBalance" value="{{$openingBalance}}" />
                                <small class="text-muted">{{ __('customers.use_positive_for_receivable_negative_for_payable') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('customers.email') }}</label>
                                @error('mail')<div class="text-danger">{{ $message }}</div>@enderror
                                <input type="email" class="form-control" placeholder="{{ __('common.enter_email') }}" id="mail" name="mail" value="{{old('mail',$mail)}}" />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('customers.phone_number') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_phone_number') }}" id="mobile" name="mobile" value="{{$mobile}}" />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">{{ __('customers.country') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_country') }}" id="country" name="country" value="{{$country}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">{{ __('customers.state') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_state') }}" id="state" name="state" value="{{$state}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">{{ __('customers.city') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_city') }}" id="city" name="city" value="{{$city}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">{{ __('customers.area') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_area') }}" id="area" name="area" value="{{$area}}" />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">{{ isset($profile) ? __('customers.update_customer') : __('customers.add_customer') }}</button>
                    <button type="reset" class="btn btn-danger">{{ __('customers.reset') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@if(!isset($profile))
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header ">
                    <div class="row">
                        <div class="col-md-12 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('customers.opening_balance_summary') }}</h5>
                            <div>
                                <span class="font-weight-bold">{{ __('customers.total_opening_balance') }}:</span>
                                <span>@money($openingTotal ?? 0)</span>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            @include('partials.searchbar', [
                'searchId' => 'customer-table-search',
                'tableId' => 'customer-table',
                'placeholder' => 'Search customers by name, email, mobile, address...'
            ])
            <div class="d-flex align-items-center">
                <button id="delete-selected-customers" class="btn btn-sm btn-danger mr-3">{{ __('customers.delete_selected') }}</button>
                <div class="small text-muted mt-2 mt-sm-0 ml-sm-3">
                    <span class="badge bg-success">{{ __('customers.positive') }}</span> {{ __('common.receivable') }}, <span class="badge bg-danger">{{ __('customers.negative') }}</span> {{ __('customers.payable') }}
                </div>
            </div>
        </div>
        <div class="table-responsive rounded mb-3">
            @include('partials.bulk-actions', [
                'deleteRoute' => 'customers.bulkDelete',
                'entity' => 'Customers',
                'deleteChoice' => true,
                'fullDeleteAllowed' => $canDeleteAllData,
                'fullDeleteWarning' => __('customers.delete_profile_and_all_related_transaction_data'),
                'fullDeleteConfirm' => __('customers.delete_all_data_confirm'),
            ])
            <table class="data-tables table mb-0 tbl-server-info rn-table-pro" id="customer-table">
            <thead class="bg-white text-uppercase">
                    <tr class="ligth ligth-data">
                        <th class="rn-col-compact d-none d-sm-table-cell">
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="selectAllCustomers">
                                <label for="selectAllCustomers" class="mb-0"></label>
                            </div>
                        </th>
                        <th class="text-left">{{ __('customers.customer') }}</th>
                        <th>{{ __('customers.opening_balance') }}</th>
                        <th>{{ __('customers.mobile') }}</th>
                        <th class="d-none d-lg-table-cell">{{ __('customers.address') }}</th>
                        <th class="d-none d-xl-table-cell">{{ __('customers.last_transaction') }}</th>
                        <th class="rn-col-compact">{{ __('customers.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="ligth-body">
                    @if(!empty($listItem))
                    @foreach($listItem as $customerList)
                    <tr>
                        <td class="rn-col-compact d-none d-sm-table-cell">
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input bulk-select" value="{{ $customerList->id }}">
                                <label class="mb-0"></label>
                            </div>
                        </td>
                        <td class="text-left">
                            <div class="font-weight-600">{{$customerList->name}}</div>
                            <div class="text-muted small">{{$customerList->mail}}</div>
                        </td>
                        <td>
                            @php $ob = (int)($customerList->openingBalance ?? 0); @endphp
                            <span class="badge {{ $ob < 0 ? 'bg-danger' : 'bg-success' }}">@money($ob)</span>
                        </td>
                        <td>
                            @if(!empty($customerList->mobile))
                            <a href="tel:{{$customerList->mobile}}" class="text-dark">{{$customerList->mobile}}</a>
                            <a href="javascript:void(0)" class="badge badge-light ml-2" data-toggle="tooltip" data-bs-toggle="tooltip" title="{{ __('customers.copy') }}" data-onclick="copyToClipboard('{{$customerList->mobile}}')"><i class="ri-file-copy-line"></i></a>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="d-none d-lg-table-cell text-left">
                            <div class="rn-ellipsis rn-addr">{{$customerList->full_address ?? $customerList->area}}</div>
                        </td>
                        <td class="d-none d-xl-table-cell">{{ __('customers.not_entry') }}</td>
                        <td class="rn-col-compact">
                                     <div class="d-flex align-items-center justify-content-center list-action">
                                <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" data-original-title="{{ __('customers.view') }}"
                                   href="{{route('balancesheet')}}"><i class="ri-eye-line mr-0 "></i></a>
                                <a href="{{route('editCustomer',['id'=>$customerList->id])}}" class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" data-original-title="{{ __('customers.edit') }}">
                                   <i class="ri-pencil-line mr-0"></i></a>
                                <button type="button" class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" data-original-title="{{ __('customers.delete') }}" style="border:none;background:transparent;padding:0;"
                                    data-delete-id="{{ $customerList->id }}" data-delete-name="{{ $customerList->name }}" onclick="showDeleteModal(this, '{{ route('delCustomer', ['id' => $customerList->id]) }}')">
                                    <i class="ri-delete-bin-line mr-0"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input bulk-select" value="demo">
                                <label class="mb-0"></label>
                            </div>
                        </td>
                        <td>
                            Hasnat Saimun
                        </td>
                        <td>1200</td>
                        <td>01755048017</td>
                        <td>Cumilla</td>
                        <td>10000</td>
                        <td>10.10.2025</td>
                        <td class="rn-col-compact">
                            <div class="d-flex align-items-center justify-content-center list-action">
                                <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" data-original-title="{{ __('customers.view') }}"
                                   href="{{route('balancesheet')}}"><i class="ri-eye-line mr-0"></i></a>
                                <a  class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" data-original-title="{{ __('customers.edit') }}" href="#"><i class="ri-pencil-line mr-0"></i></a>
                                <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" data-original-title="{{ __('customers.delete') }}"><i class="ri-delete-bin-line mr-0"></i></button>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
                </table>
            </div>
            <!-- Bulk Delete Form (outside table to avoid nesting) -->
            <form id="bulkDeleteForm" method="POST" action="{{ route('customers.bulkDelete') }}">
                @csrf
            </form>
        </div>
        </div>
        </div>
    </div>
    @if(!empty($trashedList) && count($trashedList)>0)
    <div class="col-lg-12 mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ __('customers.deleted_customers') }}</h5>
            </div>
            <div class="table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info rn-table-pro">
                    <thead class="bg-white text-uppercase">
                        <tr class="ligth ligth-data">
                            <th>{{ __('customers.customer_name') }}</th>
                            <th>{{ __('customers.mobile') }}</th>
                            <th>{{ __('customers.deleted_at') }}</th>
                            <th>{{ __('customers.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedList as $t)
                        <tr>
                            <td>{{$t->name}}</td>
                            <td>{{$t->mobile}}</td>
                            <td>{{$t->deleted_at}}</td>
                            <td>
                                <a class="badge bg-info" href="{{route('restoreCustomer',['id'=>$t->id])}}">{{ __('customers.restore') }}</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endif
<!-- Page end  -->

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">{{ __('customers.delete_customer') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('customers.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
                <div class="alert alert-info mt-3">
                    <strong>{{ __('customers.choose_delete_type') }}:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>{{ __('customers.profile_only') }}:</strong> {{ __('customers.delete_only_the_customer_profile_data') }}</li>
                        @if($canDeleteAllData)
                        <li class="text-danger"><strong>{{ __('customers.delete_all_data') }}:</strong> {{ __('customers.delete_profile_and_all_related_transaction_data') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('customers.cancel') }}</button>
                <button type="button" class="btn btn-warning" onclick="submitDelete('profileOnly')">{{ __('customers.delete_profile_only') }}</button>
                @if($canDeleteAllData)
                <button type="button" class="btn btn-danger" data-full-delete onclick="submitDelete('fullDelete')">{{ __('customers.delete_all_data') }}</button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
    <input type="hidden" id="deleteType" name="deleteType" value="profileOnly">
</form>

<script>
    let currentDeleteUrl = '';

    function showDeleteModal(button, deleteUrl) {
        currentDeleteUrl = deleteUrl;
        const customerName = button.getAttribute('data-delete-name');
        document.getElementById('deleteMessage').innerHTML =
            `Are you sure you want to delete the customer: <strong>${customerName}</strong>?`;

        // Show the modal
        $('#deleteModal').modal('show');
    }

    function submitDelete(deleteType) {
        if (!currentDeleteUrl) return;
        // Deleting all data is permanent, including the history of cancelled sales.
        if (deleteType === 'fullDelete' && !window.confirm(@json(__('customers.delete_all_data_confirm')))) return;

        // Set the delete type
        document.getElementById('deleteType').value = deleteType;

        // Set form action and submit
        document.getElementById('deleteForm').action = currentDeleteUrl;
        document.getElementById('deleteForm').submit();

        // Hide modal
        $('#deleteModal').modal('hide');
    }
</script>
@include('partials.bulk-actions-script')
@endsection
