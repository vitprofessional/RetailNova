@extends('include')
@section('backTitle'){{ __('settings.language') }}@endsection
@section('container')
<div class="col-12 col-lg-8 rn-m-admin-settings"><div class="card"><div class="card-body">
<h4 class="rn-m-form-page-title">{{ __('settings.language') }}</h4>
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
<form method="GET" action="{{ route('language.settings.edit') }}" class="rn-m-settings-selector">
<label for="languageBusiness">{{ __('settings.business') }}</label>
<select id="languageBusiness" name="businessId" class="form-control" onchange="this.form.submit()">
@foreach($businesses as $option)<option value="{{ $option->id }}" @selected($business && $business->id === $option->id)>{{ $option->businessName }}</option>@endforeach
</select></form>
@if($business)
<form method="POST" action="{{ route('language.settings.update') }}" class="mt-3 rn-m-form rn-m-admin-form">
@csrf <input type="hidden" name="businessId" value="{{ $business->id }}">
<label for="defaultLocale">{{ __('settings.business_language') }}</label>
<select id="defaultLocale" name="default_locale" class="form-control">
@foreach(['en'=>'English', 'bn'=>'বাংলা'] as $locale=>$label)<option value="{{ $locale }}" @selected(old('default_locale', $business->default_locale) === $locale)>{{ $label }}</option>@endforeach
</select><div class="rn-m-sticky-actions rn-m-admin-actions"><a class="btn btn-secondary rn-m-btn rn-m-btn--outline" href="{{ route('addBusinessSetupPage') }}">{{ __('common.cancel') }}</a><button class="btn btn-primary rn-m-btn rn-m-btn--primary">{{ __('common.save') }}</button></div>
</form>@endif
</div></div></div>
@endsection
