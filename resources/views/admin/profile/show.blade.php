@extends('include')
@section('backTitle'){{ __('common.heading_admin_profile') }}@endsection
@section('container')
<div class="card">
    <div class="card-body">
        <h4>{{ __('common.my_profile') }}</h4>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row">
            <div class="col-md-3 text-center">
                <img src="{{ $avatarUrl ?? asset('/public/eshop/') . '/assets/images/user/1.png' }}" class="img-fluid rounded" alt="avatar" data-onerror="this.onerror=null;this.src='{{ asset('/public/eshop/') . '/assets/images/user/1.png' }}'">
            </div>
            <div class="col-md-9">
                <table class="table table-borderless">
                    <tr><th>{{ __('common.full_names') }}</th><td>{{ $admin->fullName }}</td></tr>
                    <tr><th>{{ __('common.sure_name') }}</th><td>{{ $admin->sureName }}</td></tr>
                    <tr><th>{{ __('common.email') }}</th><td>{{ $admin->mail }}</td></tr>
                    <tr><th>{{ __('common.contact') }}</th><td>{{ $admin->contactNumber }}</td></tr>
                </table>

                <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">{{ __('common.edit_profile') }}</a>
                <a href="{{ route('admin.profile.edit') }}#change-password" class="btn btn-secondary">{{ __('common.change_password') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
