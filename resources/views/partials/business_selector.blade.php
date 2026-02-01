@php
    $actor = auth('admin')->user();
    $role = $actor ? strtolower($actor->role) : null;
    $isAdminLike = in_array($role, ['admin','superadmin']);
    $bizList = isset($businesses) ? $businesses : \App\Models\BusinessSetup::orderBy('id','asc')->get();
    $selectedBizId = isset($selectedBusinessId) ? $selectedBusinessId : old('businessId');
@endphp

@if($isAdminLike)
<div class="col-md-4">
    <div class="form-group">
        <label for="businessId" class="form-label">Business / Store</label>
        <select id="businessId" name="businessId" class="form-control">
            <option value="">All / Unassigned</option>
            @foreach($bizList as $biz)
                <option value="{{ $biz->id }}" {{ (string)$selectedBizId === (string)$biz->id ? 'selected' : '' }}>{{ $biz->businessName }}</option>
            @endforeach
        </select>
        <small class="text-muted">Choose target business for this record.</small>
    </div>
</div>
@else
    {{-- For GM/Store Manager scoping is automatic; keep optional hidden here for completeness --}}
    @if(isset($actor) && !empty($actor->businessId))
        <input type="hidden" name="businessId" value="{{ $actor->businessId }}" />
    @endif
@endif
