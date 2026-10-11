@auth('admin')
<form method="POST" action="{{ route('language.preference') }}" class="mx-2">
@csrf
<label for="preferredLocale" class="sr-only">{{ __('settings.language') }}</label>
<select id="preferredLocale" name="preferred_locale" class="form-control form-control-sm" onchange="this.form.submit()">
<option value="" @selected(!auth('admin')->user()->preferred_locale)>{{ __('settings.business_default') }}</option>
<option value="en" @selected(auth('admin')->user()->preferred_locale === 'en')>English</option>
<option value="bn" @selected(auth('admin')->user()->preferred_locale === 'bn')>বাংলা</option>
</select></form>
@endauth
