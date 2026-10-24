@extends('include')
@section('backTitle'){{ __('common.heading_edit_profile') }}@endsection
@section('container')
<div class="card">
    <div class="card-body">
        <h4 class="rn-m-form-page-title">{{ __('common.edit_profile') }}</h4>
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="rn-m-form rn-m-admin-form">
            @csrf
            <div class="form-row rn-m-form-section">
                <div class="form-group col-md-6 rn-m-admin-field">
                    <label>{{ __('common.full_name') }}</label>
                    <input type="text" name="fullName" class="form-control" value="{{ old('fullName', $admin->fullName) }}" required>
                </div>
                <div class="form-group col-md-6 rn-m-admin-field">
                    <label>{{ __('common.sure_name') }}</label>
                    <input type="text" name="sureName" class="form-control" value="{{ old('sureName', $admin->sureName) }}">
                </div>
            </div>

            <div class="form-row rn-m-form-section">
                <div class="form-group col-md-6 rn-m-admin-field">
                    <label>{{ __('common.email') }}</label>
                    <input type="email" name="mail" class="form-control" value="{{ old('mail', $admin->mail) }}" required>
                </div>
                <div class="form-group col-md-6 rn-m-admin-field">
                    <label>{{ __('common.contact_number') }}</label>
                    <input type="text" name="contactNumber" class="form-control" value="{{ old('contactNumber', $admin->contactNumber) }}">
                </div>
            </div>

            <div class="form-row rn-m-form-section">
                <div class="form-group col-md-6 rn-m-admin-field">
                    <label>{{ __('common.avatar') }}</label>
                    @mobile
                    <div class="rn-m-file-picker">
                        <label for="profileAvatar" class="rn-m-file-button">{{ __('mobile.add_file') }}</label>
                        <span class="rn-m-file-name" data-rn-file-name>{{ __('mobile.no_file_selected') }}</span>
                        <input type="file" name="avatar" class="rn-m-file-input" id="profileAvatar" accept="image/*">
                    </div>
                    @else
                    <input type="file" name="avatar" class="form-control-file">
                    @endmobile
                    @if($admin->avatar)
                        <div class="mt-2"><img src="{{ asset('storage/'.$admin->avatar) }}" alt="avatar" style="max-width:120px"></div>
                    @endif
                </div>
            </div>

            <div class="rn-m-sticky-actions rn-m-admin-actions">
                <a class="btn btn-secondary rn-m-btn rn-m-btn--outline" href="{{ route('admin.profile.show') }}">{{ __('common.cancel') }}</a>
                <button class="btn btn-primary rn-m-btn rn-m-btn--primary" type="submit">{{ __('common.save') }}</button>
            </div>
        </form>

        <hr>
        <h5 id="change-password">{{ __('common.change_password') }}</h5>
        <form action="{{ route('admin.profile.password') }}" method="POST" class="rn-m-password-form rn-m-admin-form">
            @csrf
            <div class="form-row rn-m-form-section">
                <div class="form-group col-md-4 rn-m-admin-field">
                    <label>{{ __('common.current_password') }}</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4 rn-m-admin-field">
                    <label>{{ __('common.new_password') }}</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4 rn-m-admin-field">
                    <label>{{ __('common.confirm_new_password') }}</label>
                    <input type="password" name="new_password_confirmation" class="form-control" required>
                </div>
            </div>
            <button class="btn btn-warning" type="submit">{{ __('common.change_password') }}</button>
        </form>

    </div>
</div>
@endsection
