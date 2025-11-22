@extends('include') 

@section('backTitle')Unit Type @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
@php
    if (isset($profile)) {
        $name           = $profile->name;
        $profileId      = $profile->id;
    } else {
        $name           = '';
        $profileId      = '';
    }
@endphp
    <form action="{{route('saveProductUnit')}}" method="POST" >
    @csrf
<div class="row">
    <input type="hidden" name="profileId" value="{{ $profileId }}">
    <div class="col-md-4">
        <input type="text" class="form-control" placeholder="Enter product unit name" aria-label="name"  name ="name" value="{{$name}}" />
        <div class=" d-md-flex justify-content-md-end mt-2">
            <button class="btn btn-primary btn-sm" type="submit">@if(isset($profile)) Update @else Add @endif Product Unit </button>
        </div>
    </div>
</div>

@if(!isset($profile))
<div class="thead-start mt-4">
    @include('partials.bulk-actions', ['deleteRoute' => 'units.bulkDelete', 'entity' => 'Units'])
    <table class=" data-tables    table  table-success table-bordered table-striped-colums">
        <thead class="">
            <tr class=" ">
                <th>
                    <div class="checkbox d-inline-block">
                        <input type="checkbox" class="checkbox-input" id="selectAllUnits">
                        <label for="selectAllUnits" class="mb-0"></label>
                    </div>
                </th>
                <th scope="">Product Unit</th>
                <th scope="">Action</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($listItem))
            @foreach($listItem as $productUnitList)
            <tr>
                 <td>
                    <div class="checkbox d-inline-block">
                        <input type="checkbox" class="checkbox-input bulk-select" value="{{$productUnitList->id}}">
                        <label class="mb-0"></label>
                    </div>
                </td>
                <td> {{$productUnitList->name}}</td>
                <td>
                    <div class="d-flex list-action">
                        <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="{{route('editProductUnit',['id'=>$productUnitList->id])}}"><i class="ri-pencil-line mr-0"></i>Edit</a>
                        <form action="{{ route('delProductUnit',['id'=>$productUnitList->id]) }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="badge bg-warning mr-2" data-confirm="Are you sure to delete this unit?" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" style="border:none; background:transparent; padding:0;"><i class="ri-delete-bin-line mr-0"></i>Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td>Mark</td>
                <td>
                    <div class="d-flex list-action">
                        <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="#"><i class="ri-pencil-line mr-0"></i>Edit</a>
                        <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"><i class="ri-delete-bin-line mr-0"></i>Delete</button>
                    </div>
                </td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
@endif


@endsection
@section('scripts')
@include('partials.bulk-actions-script')
@endsection