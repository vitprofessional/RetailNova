@extends('include') 
@section('backTitle') addCoustomer @endsection @section('container')

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
                    <h4 class="card-title">@if(isset($profile)) Update @else Add @endif Customer</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{route('saveCustomer')}}" method="POST" >
                    @csrf
                    <div class="row">
                        <input type="hidden" name="profileId" value="{{ $profileId }}">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" class="form-control" placeholder="Enter Name"  id="fullName" name="fullName"  value="{{$name}}" required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="openingBalance" class="form-label">Opening Balance
                                    <span class="ml-1" data-toggle="tooltip" data-bs-toggle="tooltip" title="Positive = customer owes you (receivable). Negative = you owe customer (payable).">
                                        <i class="ri-information-line"></i>
                                    </span>
                                </label>
                                <input type="number" step="1" class="form-control" placeholder="Enter Opening Balance" id="openingBalance" name="openingBalance" value="{{$openingBalance}}" />
                                <small class="text-muted">Use positive for receivable, negative for payable.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" placeholder="Enter Email" id="mail" name="mail" value="{{$mail}}" />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number" id="mobile" name="mobile" value="{{$mobile}}" />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">Country</label>
                                <input type="text" class="form-control" placeholder="Enter The Country" id="country" name="country" value="{{$country}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">State</label>
                                <input type="text" class="form-control" placeholder="Enter The State" id="state" name="state" value="{{$state}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">City</label>
                                <input type="text" class="form-control" placeholder="Enter The City" id="city" name="city" value="{{$city}}" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">Area</label>
                                <input type="text" class="form-control" placeholder="Enter The Area" id="area" name="area" value="{{$area}}" />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">@if(isset($profile)) Update @else Add @endif Customer</button>
                    <button type="reset" class="btn btn-danger">Reset</button>
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
                            <h5 class="mb-0">Opening Balance Summary</h5>
                            <div>
                                <span class="font-weight-bold">Total Opening Balance:</span>
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
                <button id="delete-selected-customers" class="btn btn-sm btn-danger mr-3">Delete Selected</button>
                <div class="small text-muted mt-2 mt-sm-0 ml-sm-3">
                    <span class="badge bg-success">Positive</span> receivable, <span class="badge bg-danger">Negative</span> payable
                </div>
            </div>
        </div>
        <div class="table-responsive rounded mb-3">
            @include('partials.bulk-actions', ['deleteRoute' => 'customers.bulkDelete', 'entity' => 'Customers'])
            <table class="data-tables table mb-0 tbl-server-info" id="customer-table">
            <thead class="bg-white text-uppercase">
                    <tr class="ligth ligth-data">
                        <th class="rn-col-compact d-none d-sm-table-cell">
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="selectAllCustomers">
                                <label for="selectAllCustomers" class="mb-0"></label>
                            </div>
                        </th>
                        <th class="text-left">Customer</th>
                        <th>Opening Balance</th>
                        <th>Mobile</th>
                        <th class="d-none d-lg-table-cell">Address</th>
                        <th class="d-none d-xl-table-cell">Last Transaction</th>
                        <th class="rn-col-compact">Actions</th>
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
                            <a href="javascript:void(0)" class="badge badge-light ml-2" data-toggle="tooltip" data-bs-toggle="tooltip" title="Copy" data-onclick="copyToClipboard('{{$customerList->mobile}}')"><i class="ri-file-copy-line"></i></a>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="d-none d-lg-table-cell text-left">
                            <div class="rn-ellipsis rn-addr">{{$customerList->full_address ?? $customerList->area}}</div>
                        </td>
                        <td class="d-none d-xl-table-cell">not entry</td>
                        <td class="rn-col-compact">
                                     <div class="d-flex align-items-center justify-content-center list-action">
                                <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" data-original-title="View"
                                   href="{{route('balancesheet')}}"><i class="ri-eye-line mr-0 "></i></a>
                                <a href="{{route('editCustomer',['id'=>$customerList->id])}}" class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" data-original-title="Edit">
                                   <i class="ri-pencil-line mr-0"></i></a>
                                <form action="{{ route('delCustomer',['id'=>$customerList->id]) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="badge bg-warning mr-2" data-confirm="Are you sure to delete this customer?" data-toggle="tooltip" data-placement="top" data-original-title="Delete" style="border:none;background:transparent;padding:0;"><i class="ri-delete-bin-line mr-0"></i></button>
                                </form>
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
                                <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" data-original-title="View"
                                   href="{{route('balancesheet')}}"><i class="ri-eye-line mr-0"></i></a>
                                <a  class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" data-original-title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>
                                <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" data-original-title="Delete"><i class="ri-delete-bin-line mr-0"></i></button>
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
                <h5 class="card-title">Deleted Customers</h5>
            </div>
            <div class="table-responsive rounded mb-3">
                <table class="data-tables table mb-0 tbl-server-info">
                    <thead class="bg-white text-uppercase">
                        <tr class="ligth ligth-data">
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Deleted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedList as $t)
                        <tr>
                            <td>{{$t->name}}</td>
                            <td>{{$t->mobile}}</td>
                            <td>{{$t->deleted_at}}</td>
                            <td>
                                <a class="badge bg-info" href="{{route('restoreCustomer',['id'=>$t->id])}}">Restore</a>
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
@include('partials.bulk-actions-script')
@endsection
