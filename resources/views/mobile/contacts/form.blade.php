{{--
    Add / edit a customer or supplier on the mobile version (UI-2). Posts the same fields to the
    same route as the desktop form, so validation and saving are unchanged.
    $type: "customers" | "suppliers"; $profile: the contact being edited, or null; $businesses.
--}}
@php
    $isCustomer = $type === 'customers';
    $lang = $type;
    $platform = app(\App\Services\PurchaseWorkflow::class)->platform();
    $businessOptions = ['' => __('settings.all_unassigned')]
        + collect($businesses ?? \App\Models\BusinessSetup::orderBy('id')->get())->pluck('businessName', 'id')->all();
@endphp
@include('sweetalert::alert')
<form method="POST" enctype="multipart/form-data" action="{{ route($isCustomer ? 'saveCustomer' : 'saveSupplier') }}" class="rn-m-form" data-rn-contact-form="{{ $type }}">
    @csrf
    <input type="hidden" name="profileId" value="{{ $profile->id ?? '' }}">
    <x-m.form-section :title="$profile ? __('mobile.edit_'.rtrim($type, 's')) : __('mobile.new_'.rtrim($type, 's'))">
        @if($platform)
            <x-m.field name="businessId" :label="__('settings.business_store')" :options="$businessOptions"
                       :value="$profile->businessId ?? (count($businessOptions) === 2 ? array_key_last($businessOptions) : '')"
                       :hint="__('settings.choose_target_business_for_this_record')" />
        @endif
        @include('partials.business-image-field', ['imageRecord' => $profile, 'imageType' => $type, 'imageFieldId' => $type.'Image'])
        <x-m.field name="fullName" :label="__('common.name')" :value="$profile->name ?? ''" required autocomplete="name" maxlength="150" />
        <x-m.field name="mobile" type="tel" :label="__($lang.'.phone_number')" :value="$profile->mobile ?? ''" inputmode="tel" autocomplete="tel" />
        <x-m.field name="mail" type="email" :label="__($lang.'.email')" :value="$profile->mail ?? ''" inputmode="email" autocomplete="email" />
        <x-m.field name="openingBalance" type="number" :label="__($lang.'.opening_balance')" :value="$profile->openingBalance ?? ''" step="1" inputmode="numeric" />
    </x-m.form-section>
    <x-m.form-section :title="__('mobile.contact_address')">
        <x-m.field name="area" :label="__($lang.'.area')" :value="$profile->area ?? ''" />
        <x-m.field name="city" :label="__($lang.'.city')" :value="$profile->city ?? ''" />
        <x-m.field name="state" :label="__($lang.'.state')" :value="$profile->state ?? ''" />
        <x-m.field name="country" :label="__($lang.'.country')" :value="$profile->country ?? ''" />
    </x-m.form-section>
    <x-m.sticky-actions :cancel-url="route($isCustomer ? 'addCustomer' : 'addSupplier')" :submit-label="__('mobile.save')" />
</form>
