@extends('include')
@section('backTitle') Super Admin Setup @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">First-time Super Admin Setup</h5></div>
        <div class="card-body">
            <p class="text-muted">Create the first Super Admin account. Once created, this form is disabled.</p>
            <form action="{{ route('superadmin.setup.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name</label>
                        <input name="fullName" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sure Name</label>
                        <input name="sureName" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input name="mail" type="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store Name</label>
                        <input name="storeName" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input name="contactNumber" class="form-control">
                    </div>
                </div>
                <button class="btn btn-primary">Create Super Admin</button>
                <a href="{{ route('userLogin') }}" class="btn btn-secondary ml-2">Back to Login</a>
            </form>
        </div>
    </div>
</div>
@endsection
