@extends('include')

@section('backTitle'){{ __('common.heading_category_type') }}@endsection @section('container')
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
    $mobileMasterRows = collect($listItem ?? [])->map(fn($item) => [
        'title' => $item->name,
        'edit' => route('editCategory', ['id' => $item->id]),
        'delete' => route('delCategory', ['id' => $item->id]),
        'confirm' => __('inventory.delete').' '.$item->name.'?',
    ]);
@endphp
<div class="card rn-m-master-shell">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4 rn-m-form-page-title">
            <h4 class="mb-0">@if(isset($profile)) Edit Category @else Add Category @endif</h4>
        </div>
        <form id="rnMobileMasterForm" action="{{route('saveCategory')}}" method="POST" class="rn-m-form rn-m-admin-form rn-m-master-form {{ isset($profile) ? 'rn-m-master-edit' : '' }}">
            @csrf
            <div class="row rn-m-form-section">
                <input type="hidden" name="profileId" value="{{ $profileId }}">
                <div class="col-md-4 rn-m-admin-field">
                    <label class="rn-m-master-label" for="categoryName">{{ __('inventory.category') }}</label>
                    <input type="text" id="categoryName" class="form-control" placeholder="{{ __('common.enter_category_name') }}" aria-label="{{ __('common.name') }}"  name ="name" value="{{$name}}" />
                    <div class="rn-m-sticky-actions rn-m-admin-actions">
                        <a class="btn btn-secondary rn-m-btn rn-m-btn--outline" href="{{ route('addCategory') }}">{{ __('common.cancel') }}</a>
                        <button class="btn btn-primary rn-m-btn rn-m-btn--primary" type="submit">
                            @mobile
                                {{ __('common.save') }}
                            @else
                                @if(isset($profile)) Update Category @else Add Category @endif
                            @endmobile
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if(!isset($profile))
        @mobile
            @include('components.mobile.master-data-cards', ['records' => $mobileMasterRows, 'title' => __('inventory.category')])
        @else
        <div class="thead-start mt-4 rn-m-master-desktop-list">
            @include('partials.bulk-actions', ['deleteRoute' => 'categories.bulkDelete', 'entity' => 'Categories'])
            <table class=" data-tables    table  table-success table-bordered table-striped-colums rn-table-pro">
                <thead class="">
                    <tr class=" ">
                        <th>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input" id="selectAllCategories">
                                <label for="selectAllCategories" class="mb-0"></label>
                            </div>
                        </th>
                        <th scope="">{{ __('inventory.category') }}</th>
                        <th scope="">{{ __('inventory.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($listItem))
                    @foreach($listItem as $categoryList)
                    <tr>
                        <td>
                            <div class="checkbox d-inline-block">
                                <input type="checkbox" class="checkbox-input bulk-select" value="{{$categoryList->id}}">
                                <label class="mb-0"></label>
                            </div>
                        </td>
                        <td> {{$categoryList->name}}</td>
                        <td>
                            <div class="d-flex list-action">
                                <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ __('inventory.edit') }}" href="{{route('editCategory',['id'=>$categoryList->id])}}"><i class="ri-pencil-line mr-0"></i>{{ __('inventory.edit') }}</a>
                                <form action="{{ route('delCategory',['id'=>$categoryList->id]) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="badge bg-warning mr-2" data-confirm="Are you sure to delete this category?" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ __('inventory.delete') }}" style="border:none; background:transparent; padding:0;"><i class="ri-delete-bin-line mr-0"></i>{{ __('inventory.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td>{{ __('inventory.mark') }}</td>
                        <td>
                            <div class="d-flex list-action">
                                <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ __('inventory.edit') }}" href="#"><i class="ri-pencil-line mr-0"></i>{{ __('inventory.edit') }}</a>
                                <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ __('inventory.delete') }}"><i class="ri-delete-bin-line mr-0"></i>{{ __('inventory.delete') }}</button>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @endmobile
        @endif
    </div>
</div>


@endsection
@section('scripts')
@include('partials.bulk-actions-script')
<script>
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('.rn-m-master-fab'); if (!trigger) return;
    const form = document.getElementById('rnMobileMasterForm'); if (!form) return;
    event.preventDefault(); document.querySelectorAll('.rn-m-master-menu[open]').forEach(menu => menu.removeAttribute('open'));
    form.classList.add('is-open'); form.scrollIntoView({behavior:'smooth',block:'start'});
    form.querySelector('input[name="name"]')?.focus({preventScroll:true});
});
</script>
@endsection
