@extends('include') 

@section('backTitle')Category Type @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
@php
    if(isset($profile)):
        $name           = $profile->name;
        $profileId      = $profile->id;
    else:
        $name           ='';
        $profileId      = '';
    endif;
@endphp
    <form action="{{route('saveCategory')}}" method="POST" >
    @csrf
<div class="row">
    <input type="hidden" name="profileId" value="{{ $profileId }}">
    <div class="col-md-4">
        <input type="text" class="form-control" placeholder="Enter category name" aria-label="name"  name ="name" value="{{$name}}" />
        <div class=" d-md-flex justify-content-md-end mt-2">
            <button class="btn btn-primary btn-sm" type="submit">@if(isset($profile)) Update @else Add @endif Category </button>
        </div>
    </div>
</div>

@if(!isset($profile))
<div class="thead-start mt-4">
    <table class=" data-tables    table  table-success table-bordered table-striped-colums">
        <thead class="">
            <tr class=" ">
                <th>
                    <div class="checkbox d-inline-block">
                        <input type="checkbox" class="checkbox-input" id="checkbox1">
                        <label for="checkbox1" class="mb-0"></label>
                    </div>
                </th>
                <th scope="">Category</th>
                <th scope="">Action</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($listItem))
            @foreach($listItem as $categoryList)
            <tr>
                 <td>
                    <div class="checkbox d-inline-block">
                        <input type="checkbox" class="checkbox-input" id="checkbox2">
                        <label for="checkbox2" class="mb-0"></label>
                    </div>
                </td>
                <td> {{$categoryList->name}}</td>
                <td>
                    <div class="d-flex list-action">
                        <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="{{route('editCategory',['id'=>$categoryList->id])}}"><i class="ri-pencil-line mr-0"></i>Edit</a>
                        <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="{{route('delCategory',['id'=>$categoryList->id])}}"><i class="ri-delete-bin-line mr-0"></i>Delete</a>
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
                        <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i>Delete</a>
                    </div>
                </td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
@endif


@endsection