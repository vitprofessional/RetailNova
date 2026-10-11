@extends('include')
@section('backTitle'){{ __('common.heading_super_admin_setup') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('authentication.first_time_super_admin_setup') }}</h5></div>
        <div class="card-body">
            <p class="text-muted">{{ __('authentication.create_the_first_super_admin_account_once_created_this_form_is_disabled') }}</p>
            <form action="{{ route('superadmin.setup.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('authentication.full_name') }}</label>
                        <input name="fullName" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('authentication.sure_name') }}</label>
                        <input name="sureName" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('authentication.email') }}</label>
                        <input name="mail" type="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('authentication.password') }}</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('authentication.store_name') }}</label>
                        <input name="storeName" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('authentication.contact_number') }}</label>
                        <input name="contactNumber" class="form-control">
                    </div>
                </div>
                <button class="btn btn-primary">{{ __('authentication.create_super_admin') }}</button>
                <a href="{{ route('userLogin') }}" class="btn btn-secondary ml-2">{{ __('authentication.back_to_login') }}</a>
            </form>
        </div>
    </div>
</div>
@endsection
