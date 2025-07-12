@extends('include') @section('backTitle') addCoustomer @endsection @section('container')
<div class="row">
    <div class="col-sm-12">
        <div class="row">
<div class="col-12">
    @include('sweetalert::alert')
</div>
            @php
                if(isset($profile)):
                    $name           = $profile->name;
                    $accReceivable  = $profile->accReceivable;
                    $accPayable     = $profile->accPayable;
                    $mail           = $profile->mail;
                    $mobile         = $profile->mobile;
                    $country        = $profile->country;
                    $state          = $profile->state;
                    $city           = $profile->city;
                    $area           = $profile->area;
                    $profileId      = $profile->id;
                else:
                    $name           ='';
                    $accReceivable  ='';
                    $accPayable     ='';
                    $mail           ='';
                    $mobile         ='';
                    $country        ='';
                    $state          ='';
                    $city           ='';
                    $area           ='';
                    $profileId      = '';
                endif;
            @endphp
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">@if(isset($profile)) Update @else Add @endif Supplier</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{route('saveSupplier')}}" method="POST" >
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
                                <label for="inputState" class="form-label">Accoount Receivable *</label>
                                
                                <input type="number" class="form-control" placeholder="Enter Accoount Receivable Amount" id="accReceivable" name="accReceivable" value="{{$accReceivable}}"  required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">Accoount Payable *</label>
                                
                                <input type="number" class="form-control" placeholder="Enter Accoount Payable Amount" id="accPayable" name="accPayable"value="{{$accPayable}}"   required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" class="form-control" placeholder="Enter Email" id="mail" name="mail" value="{{$mail}}"   required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number" id="mobile" name="mobile" value="{{$mobile}}"  required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">Country *</label>
                                
                                <input type="text" class="form-control" placeholder="Enter The Country" id="country" name="country" value="{{$country}}"  required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">State *</label>
                                
                                <input type="text" class="form-control" placeholder="Enter The State" id="state" name="state" value="{{$state}}"  required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">City *</label>
                               
                                <input type="text" class="form-control" placeholder="Enter The City" id="city" name="city" value="{{$city}}"  required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="area" class="form-label">Area *</label>
                               
                                <input type="text" class="form-control" placeholder="Enter The Area" id="area" name="area" value="{{$area}}"  required />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2">@if(isset($profile)) Update @else Add @endif Supplier</button>
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
                        <div class="col-md-6">
                            <h5 class="">Customer Receivable : 1000000</h4>
                        </div>
                        <div class="col-md-6 text-last">
                            <h5 class="">Customer Payable : 50000000</h4>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="table-responsive rounded mb-3">
            <table class="data-tables table mb-0 tbl-server-info">
                <thead class="bg-white text-uppercase">
                    <tr class="ligth ligth-data">
                        <th>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox1">
                                <label for="checkbox1" class="mb-0"></label>
                            </div>
                        </th>
                        <th>Supplier Name</th>
                        <th>Balance</th>
                        <th>Mobile</th>
                        <th>Address</th>
                        <th>Total Credit</th>
                        <th>Last Transaction</th>
                        <th>Balance Sheet</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="ligth-body">
                    @if(!empty($listItem))
                    @foreach($listItem as $supplierList)
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox2">
                                <label for="checkbox2" class="mb-0"></label>
                            </div>
                        </td>
                        <td>
                            {{$supplierList->name}}
                        </td>
                        <td>{{$supplierList->accReceivable}}</td>
                        <td>{{$supplierList->mobile}}</td>
                        <td>{{$supplierList->area}}</td>
                        <td>{{$supplierList->accPayable}}</td>
                        <td>not entry</td>
                        <td>
                            <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                    href="{{route('supplierbalancesheet')}}"><i class="ri-eye-line mr-0 "></i></a></td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                
                                <a href="{{route('editSupplier',['id'=>$supplierList->id])}}" class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"
                                    href="#"><i class="ri-pencil-line mr-0"></i></a>
                                <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"
                                    href="{{route('delSupplier',['id'=>$supplierList->id])}}"><i class="ri-delete-bin-line mr-0"></i></a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox2">
                                <label for="checkbox2" class="mb-0"></label>
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
                        <td><a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="View"
                                    href="#"><i class="ri-eye-line mr-0"></i></a></td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                
                                <a  class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"
                                    href="#"><i class="ri-pencil-line mr-0"></i></a>
                                <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"
                                    href="#"><i class="ri-delete-bin-line mr-0"></i></a>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    
</div>
@endif
<!-- Page end  -->
@endsection
