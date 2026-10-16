@php
    $actor = auth('admin')->user();
    $role = $actor ? strtolower($actor->role) : null;
    $isAdminLike = $businessSelectionAllowed ?? in_array($role, ['admin','superadmin']);
    $businessSelectorClass = $businessSelectorClass ?? 'col-md-4';
    $bizList = isset($businesses) ? $businesses : \App\Models\BusinessSetup::orderBy('id','asc')->get();
    $selectedBizId = old('businessId',$selectedBusinessId ?? null);
    if(isset($businessSelectionAllowed) && !$selectedBizId && count($bizList)===1){$selectedBizId=$bizList->first()->id;}
@endphp

@if($isAdminLike)
<div class="{{ $businessSelectorClass }}">
    <div class="form-group">
        <label for="businessId" class="form-label">{{ __('settings.business_store') }}</label>
        @if(!empty($businessSelectorIcon))
        <div class="rn-product-control rn-product-select-control">
            <span class="rn-product-control-icon" aria-hidden="true"><i class="las la-store"></i></span>
        @endif
        <select id="businessId" name="businessId" class="form-control">
            <option value="">{{ __('settings.all_unassigned') }}</option>
            @foreach($bizList as $biz)
                <option value="{{ $biz->id }}" {{ (string)$selectedBizId === (string)$biz->id ? 'selected' : '' }}>{{ $biz->businessName }}</option>
            @endforeach
        </select>
        @if(!empty($businessSelectorIcon))
        </div>
        @endif
        <small class="text-muted">{{ __('settings.choose_target_business_for_this_record') }}</small>
        @error('businessId')<small class="text-danger d-block" role="alert">{{ $message }}</small>@enderror
    </div>
</div>
@else
    {{-- For GM/Store Manager scoping is automatic; keep optional hidden here for completeness --}}
    @if(isset($actor) && !empty($actor->businessId))
        <input type="hidden" name="businessId" value="{{ $actor->businessId }}" />
    @endif
@endif
