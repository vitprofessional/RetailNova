@extends('include') @section('backTitle')Add Service @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 mb-3">
                <h4>Create Service</h4>
            </div>
        </div>
        @php
            if(isset($profile)):
                $serviceName = $profile->serviceName;
                $rate = $profile->rate;
                $profileId = $profile->id;
            else:
                $serviceName = '';
                $rate = '';
                $profileId = '';
            endif;

        @endphp
        <form action="{{route('saveService')}}" method="POST">
            @csrf
            <div class="row">
                <input type="hidden" name="profileId" value="{{$profileId}}">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="serviceName" class="form-label">Service Name</label>
                        <input type="text" class="form-control" placeholder="Enter the service name" id="serviceName" name="serviceName" value="{{$serviceName}}" required />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="rate" class="form-label">Service Rate</label>
                        <input type="number" class="form-control" placeholder="Enter the amount" id="rate" name="rate" value="{{$rate}}" required />
                    </div>
                </div>
            </div>
             <div class=" d-md-flex justify-content-md-end mt-2">
            <button class="btn btn-primary btn-sm" type="submit">@if(isset($profile)) Update @else Add @endif Service </button>
        </div>
        </form>
    </div>
</div>

@if(!isset($profile))
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="d-flex flex-wrap flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h4 class="">Service List</h4>
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
                                <input type="checkbox" class="checkbox-input" id="checkbox1" />
                                <label for="checkbox1" class="mb-0"></label>
                            </div>
                        </th>
                        <th>Service Name</th>
                        <th>Service Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="ligth-body ">
                    @if(!empty($listItem))
                    @foreach($listItem as $serviceList)
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                <label for="checkbox2" class="mb-0"></label>
                            </div>
                        </td>
                        <td>{{$serviceList -> serviceName}}</td>
                        <td>{{$serviceList -> rate}}</td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="{{route('editService',['id'=>$serviceList->id])}}"><i class="ri-pencil-line mr-0"></i></a>
                                <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="{{route('delService',['id'=>$serviceList->id])}}"><i class="ri-delete-bin-line mr-0"></i></a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="checkbox2" />
                                <label for="checkbox2" class="mb-0"></label>
                            </div>
                        </td>
                        <td>Standerd</td>
                        <td>1000</td>
                        <td>
                            <div class="d-flex align-items-center list-action">
                                <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="#"><i class="ri-pencil-line mr-0"></i></a>
                                <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i></a>
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

@endsection
