@extends('include')
@section('backTitle') Admin Profile @endsection
@section('container')
<div class="card">
    <div class="card-body">
        <h4>My Profile</h4>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row">
            <div class="col-md-3 text-center">
                <img src="{{ $avatarUrl ?? asset('/public/eshop/') . '/assets/images/user/1.png' }}" class="img-fluid rounded" alt="avatar" data-onerror="this.onerror=null;this.src='{{ asset('/public/eshop/') . '/assets/images/user/1.png' }}'">
            </div>
            <div class="col-md-9">
                <table class="table table-borderless">
                    <tr><th>Full names</th><td>{{ $admin->fullName }}</td></tr>
                    <tr><th>Sure name</th><td>{{ $admin->sureName }}</td></tr>
                    <tr><th>Email</th><td>{{ $admin->mail }}</td></tr>
                    <tr><th>Contact</th><td>{{ $admin->contactNumber }}</td></tr>
                </table>

                <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                <a href="{{ route('admin.profile.edit') }}#change-password" class="btn btn-secondary">Change Password</a>
            </div>
        </div>
    </div>
</div>
@endsection
