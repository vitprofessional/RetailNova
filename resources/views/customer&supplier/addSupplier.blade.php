@extends('include')
@section('backTitle'){{ __('common.heading_addsupplier') }}@endsection
@section('container')
@php $canDeleteAllData = auth('admin')->user()?->canDeleteAllRelatedData() ?? false; @endphp
<div class="col-12">@include('sweetalert::alert')</div>
<div class="row">
    <div class="col-sm-12">
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
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">@if(isset($profile)) Update @else Add @endif Supplier</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{route('saveSupplier')}}" method="POST">
                    @csrf
                    <input type="hidden" name="profileId" value="{{ $profileId }}">
                    <div class="row">
                        @include('partials.business_selector', ['businesses' => $businesses ?? [] , 'selectedBusinessId' => $profile->businessId ?? null])
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('common.name') }} *</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_name') }}" id="fullName" name="fullName" value="{{$name}}" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="openingBalance" class="form-label">{{ __('suppliers.opening_balance') }}
                                    <span class="ml-1" data-toggle="tooltip" data-bs-toggle="tooltip" title="{{ __('common.positive_you_owe_supplier_payable_negative_supplier_owes_you_receivable') }}">
                                        <i class="ri-information-line"></i>
                                    </span>
                                </label>
                                <input type="number" step="1" class="form-control" placeholder="{{ __('common.enter_opening_balance') }}" id="openingBalance" name="openingBalance" value="{{$openingBalance}}" />
                                <small class="text-muted">{{ __('suppliers.positive_payable_negative_receivable') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('suppliers.email') }}</label>
                                <input type="email" class="form-control" placeholder="{{ __('common.enter_email') }}" id="mail" name="mail" value="{{$mail}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('suppliers.phone_number') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_phone_number') }}" id="mobile" name="mobile" value="{{$mobile}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('suppliers.country') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_country') }}" id="country" name="country" value="{{$country}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('suppliers.state') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_state') }}" id="state" name="state" value="{{$state}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('suppliers.city') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_city') }}" id="city" name="city" value="{{$city}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('suppliers.area') }}</label>
                                <input type="text" class="form-control" placeholder="{{ __('common.enter_the_area') }}" id="area" name="area" value="{{$area}}" />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">@if(isset($profile)) Update @else Add @endif Supplier</button>
                    <button type="reset" class="btn btn-danger">{{ __('suppliers.reset') }}</button>
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
                        <h5 class="mb-0">{{ __('suppliers.opening_balance_summary') }}</h5>
                        <div>
                            <span class="font-weight-bold">{{ __('suppliers.total_opening_balance') }}:</span>
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
                'searchId' => 'supplier-table-search',
                'tableId' => 'supplier-table',
                'placeholder' => 'Search suppliers by name, email, mobile, address...'
            ])
            <div class="d-flex align-items-center">
                <div class="small text-muted mt-2 mt-sm-0 ml-sm-3">
                    <span class="badge bg-success">{{ __('suppliers.positive') }}</span> {{ __('common.payable') }}, <span class="badge bg-danger">{{ __('suppliers.negative') }}</span> {{ __('suppliers.receivable') }}
                </div>
            </div>
        </div>
        <div class="table-responsive rounded mb-3">
            @include('partials.bulk-actions', [
                'deleteRoute' => 'suppliers.bulkDelete',
                'entity' => 'Suppliers',
                'fullDeleteAllowed' => $canDeleteAllData,
                'fullDeleteWarning' => __('suppliers.delete_profile_and_all_related_transaction_data'),
                'fullDeleteConfirm' => __('suppliers.delete_all_data_confirm'),
            ])
            <form id="bulkDeleteForm" method="POST" action="{{ route('suppliers.bulkDelete') }}">
                @csrf
                <table class="data-tables table mb-0 tbl-server-info rn-table-pro" id="supplier-table">
                <thead class="bg-white text-uppercase">
                    <tr class="ligth ligth-data">
                        <th class="rn-col-compact d-none d-sm-table-cell">
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="selectAllSuppliers">
                                <label for="selectAllSuppliers" class="mb-0"></label>
                            </div>
                        </th>
                        <th class="text-left">{{ __('suppliers.supplier') }}</th>
                        <th>{{ __('suppliers.opening_balance') }}</th>
                        <th>{{ __('suppliers.mobile') }}</th>
                        <th class="d-none d-lg-table-cell">{{ __('suppliers.address') }}</th>
                        <th class="d-none d-xl-table-cell">{{ __('suppliers.last_transaction') }}</th>
                        <th class="rn-col-compact">{{ __('suppliers.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="ligth-body">
                    @if(!empty($listItem))
                    @foreach($listItem as $supplierList)
                    <tr>
                        <td class="rn-col-compact d-none d-sm-table-cell">
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input bulk-select" value="{{ $supplierList->id }}">
                                <label class="mb-0"></label>
                            </div>
                        </td>
                        <td class="text-left"><div class="font-weight-600">{{$supplierList->name}}</div><div class="text-muted small">{{$supplierList->mail}}</div></td>
                        <td>
                            @php $ob = (int)($supplierList->openingBalance ?? 0); @endphp
                            <span class="badge {{ $ob < 0 ? 'bg-danger' : 'bg-success' }}">@money($ob)</span>
                        </td>
                        <td>{{$supplierList->mobile}}</td>
                        <td class="d-none d-lg-table-cell text-left"><div class="rn-ellipsis rn-addr">@php $addr = array_filter([$supplierList->area,$supplierList->city,$supplierList->state,$supplierList->country]); @endphp {{ implode(', ', $addr) }}</div></td>
                        <td class="d-none d-xl-table-cell">{{ __('suppliers.not_entry') }}</td>
                        <td>
                                <div class="d-flex align-items-center justify-content-center list-action">
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" data-original-title="{{ __('suppliers.view') }}" href="{{route('supplierbalancesheet')}}"><i class="ri-eye-line mr-0 "></i></a>
                                    <a href="{{route('editSupplier',['id'=>$supplierList->id])}}" class="badge bg-success mr-2" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" data-original-title="{{ __('suppliers.edit') }}"><i class="ri-pencil-line mr-0"></i></a>
                                    <button type="button" class="badge bg-warning mr-2" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" title="{{ __('suppliers.delete') }}" style="border:none;background:transparent;padding:0;"
                                        data-delete-id="{{ $supplierList->id }}" data-delete-name="{{ $supplierList->name }}" onclick="showDeleteModal(this, '{{ route('delSupplier', ['id' => $supplierList->id]) }}')">
                                        <i class="ri-delete-bin-line mr-0"></i>
                                    </button>
                                </div>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td class="rn-col-compact d-none d-sm-table-cell">
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input bulk-select" value="demo">
                                <label class="mb-0"></label>
                            </div>
                        </td>
                        <td><div class="font-weight-600">Hasnat Saimun</div><div class="text-muted small">demo@mail.com</div></td>
                        <td>1200</td>
                        <td>01755048017</td>
                        <td class="d-none d-lg-table-cell"><div class="rn-ellipsis rn-addr">Cumilla</div></td>
                        <td class="d-none d-xl-table-cell">10.10.2025</td>
                        <td class="rn-col-compact">
                            <div class="d-flex align-items-center justify-content-center list-action">
                                <a class="badge badge-info mr-2" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" data-original-title="{{ __('suppliers.view') }}" href="#"><i class="ri-eye-line mr-0"></i></a>
                                <a  class="badge bg-success mr-2" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" data-original-title="{{ __('suppliers.edit') }}" href="#"><i class="ri-pencil-line mr-0"></i></a>
                                <form method="POST" action="#" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="badge bg-warning mr-2" data-confirm="delete" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" title="{{ __('suppliers.delete') }}"><i class="ri-delete-bin-line mr-0"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
                </table>
            </form>
        </div>
        </div>
        </div>
    </div>

</div>
@endif
<!-- Page end  -->

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">{{ __('suppliers.delete_supplier') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('suppliers.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
                <div class="alert alert-info mt-3">
                    <strong>{{ __('suppliers.choose_delete_type') }}:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>{{ __('suppliers.profile_only') }}:</strong> {{ __('suppliers.delete_only_the_supplier_profile_data') }}</li>
                        @if($canDeleteAllData)
                        <li class="text-danger"><strong>{{ __('suppliers.delete_all_data') }}:</strong> {{ __('suppliers.delete_profile_and_all_related_transaction_data') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('suppliers.cancel') }}</button>
                <button type="button" class="btn btn-warning" onclick="submitDelete('profileOnly')">{{ __('suppliers.delete_profile_only') }}</button>
                @if($canDeleteAllData)
                <button type="button" class="btn btn-danger" data-full-delete onclick="submitDelete('fullDelete')">{{ __('suppliers.delete_all_data') }}</button>
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
        const supplierName = button.getAttribute('data-delete-name');
        document.getElementById('deleteMessage').innerHTML =
            `Are you sure you want to delete the supplier: <strong>${supplierName}</strong>?`;

        // Show the modal
        $('#deleteModal').modal('show');
    }

    function submitDelete(deleteType) {
        if (!currentDeleteUrl) return;
        // Deleting all data is permanent, including the history of cancelled sales.
        if (deleteType === 'fullDelete' && !window.confirm(@json(__('suppliers.delete_all_data_confirm')))) return;

        // Set the delete type
        document.getElementById('deleteType').value = deleteType;

        // Set form action and submit
        document.getElementById('deleteForm').action = currentDeleteUrl;
        document.getElementById('deleteForm').submit();

        // Hide modal
        $('#deleteModal').modal('hide');
    }
</script>

@endsection
@include('partials.bulk-actions-script')
