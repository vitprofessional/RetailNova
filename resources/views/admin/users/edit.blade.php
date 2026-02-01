@extends('include')
@section('backTitle') Edit Admin User @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Edit Admin User</h5></div>
        <div class="card-body">
            @php $actor = Auth::guard('admin')->user(); @endphp
            @php $actorRole = $actor->role ?? 'storemanager'; @endphp
            @php
                $roleOptions = [];
                switch($actorRole){
                    case 'superadmin': $roleOptions = ['superadmin','admin','gm','storemanager','salesmanager']; break;
                    case 'admin': $roleOptions = ['gm','storemanager','salesmanager']; break;
                    case 'gm': $roleOptions = ['storemanager','salesmanager']; break;
                    case 'storemanager': $roleOptions = ['salesmanager']; break;
                }
                $isGM = $actorRole === 'gm';
            @endphp
            @php $routeBase = ($actorRole === 'superadmin') ? 'admin.super' : 'admin.manage'; @endphp
            <form action="{{ route($routeBase.'.users.update', $user->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-4"><input name="fullName" class="form-control" value="{{ $user->fullName }}" required></div>
                    <div class="col-md-3"><input name="mail" type="email" class="form-control" value="{{ $user->mail }}" required></div>
                    <div class="col-md-2">
                        <select name="role" class="form-control" required>
                            @foreach($roleOptions as $r)
                                <option value="{{ $r }}" {{ $user->role===$r?'selected':'' }}>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Business selection is required for GM/Store Manager.</small>
                    </div>
                    <div class="col-md-3">
                        <select name="businessId" class="form-control" id="edit-business">
                            <option value="">Select Business</option>
                            @foreach(($businesses ?? []) as $biz)
                                <option value="{{ $biz->id }}" {{ (string)$user->businessId===(string)$biz->id?'selected':'' }}>{{ $biz->businessName }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Choose the business (required for GM/Store Manager).</small>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4"><input name="password" type="password" class="form-control" placeholder="New Password (optional)"></div>
                </div>
                <div class="mt-3"><button class="btn btn-primary">Save</button></div>
            </form>
            <script>
            (function(){
                var roleSel = document.querySelector('select[name="role"]');
                var bizSel = document.getElementById('edit-business');
                function toggleRequired(){
                    if(!roleSel || !bizSel) return;
                    var v = (roleSel.value || '').toLowerCase();
                    var needs = (v === 'gm' || v === 'storemanager');
                    bizSel.required = needs;
                }
                if(roleSel){
                    roleSel.addEventListener('change', toggleRequired);
                    toggleRequired();
                }
            })();
            </script>
        </div>
    </div>
</div>
@endsection
