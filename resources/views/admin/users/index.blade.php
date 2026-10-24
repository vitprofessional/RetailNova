@extends('include')
@section('backTitle'){{ __('common.heading_super_admin_users') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('common.admin_users') }}</h5>
        </div>
        <div class="card-body">
            @php $actorRole = $actor->role ?? 'storemanager'; @endphp
            @php $roleOptions = $allowedRoles ?? ['storemanager']; @endphp
            @php $isGM = $actorRole === 'gm'; @endphp
            @php $routeBase = ($actorRole === 'superadmin') ? 'admin.super' : 'admin.manage'; @endphp
            <p><strong>{{ __('common.new_profile_creation') }}</strong> </p>
            <form action="{{ route($routeBase.'.users.store') }}" method="POST" class="mb-4" id="create-user-form">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="fullName" class="form-control" placeholder="{{ __('common.full_name') }}" required></div>
                    <div class="col-md-3"><input name="mail" type="email" class="form-control" placeholder="{{ __('common.email') }}" required></div>
                    <div class="col-md-2">
                        <select name="role" class="form-control" id="create-role" required>
                            @foreach($roleOptions as $r)
                                <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('common.business_selection_is_required_for_gm_store_manager_sales_manager') }}</small>
                    </div>
                    <div class="col-md-3">
                        <select name="businessId" class="form-control" id="create-business">
                            <option value="">{{ __('common.select_business') }}</option>
                            @foreach(($businesses ?? []) as $biz)
                                <option value="{{ $biz->id }}">{{ $biz->businessName }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('common.choose_the_business_required_for_gm_store_manager') }}</small>
                    </div>
                    <div class="col-md-2 mt-2"><input name="password" type="password" class="form-control" placeholder="{{ __('common.password') }}" required></div>
                </div>
                <div class="mt-2"><button class="btn btn-primary btn-sm">{{ __('common.create') }}</button></div>
            </form>
            <p><strong>{{ __('common.filter_users') }}</strong></p>
            <form action="{{ route($routeBase.'.users.index') }}" method="GET" class="mb-3">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="small text-muted">{{ __('common.search') }}</label>
                        <input name="q" class="form-control" value="{{ request('q') }}" placeholder="{{ __('common.name_or_email') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">{{ __('common.role') }}</label>
                        <select name="role" class="form-control">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach(($viewableRoles ?? []) as $vr)
                                <option value="{{ $vr }}" {{ request('role')===$vr?'selected':'' }}>{{ ucfirst($vr) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(in_array($actorRole,['superadmin','admin']))
                    <div class="col-md-3">
                        <label class="small text-muted">{{ __('common.business') }}</label>
                        <select name="businessId" class="form-control">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach(($businesses ?? []) as $biz)
                                <option value="{{ $biz->id }}" {{ (string)request('businessId')===(string)$biz->id?'selected':'' }}>{{ $biz->businessName }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-sm">{{ __('common.filter') }}</button>
                    </div>
                </div>
            </form>

            <script>
            (function(){
                var roleSel = document.getElementById('create-role');
                var bizSel = document.getElementById('create-business');
                function toggleRequired(){
                    if(!roleSel || !bizSel) return;
                    var v = (roleSel.value || '').toLowerCase();
                    var needs = (v === 'gm' || v === 'storemanager' || v === 'salesmanager');
                    bizSel.required = needs;
                }
                if(roleSel){
                    roleSel.addEventListener('change', toggleRequired);
                    toggleRequired();
                }
            })();
            </script>

            <div id="select-error" class="alert alert-danger d-none">
                <div class="d-flex align-items-center justify-content-between">
                    <div><strong>{{ __('common.no_selection') }}:</strong> {{ __('common.please_select_at_least_one_user_to_delete') }}</div>
                    <div><button type="button" class="btn btn-outline-light btn-sm" id="dismiss-select-error">{{ __('common.dismiss') }}</button></div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('common.confirm_delete') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('common.close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>{{ __('common.this_will_permanently_delete_the_selected_user_s') }}</p>
                            <p class="mb-0"><strong id="modal-selected-count"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                            <button type="submit" class="btn btn-danger" form="bulk-delete-form" id="modal-confirm-delete">{{ __('common.delete') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <form id="bulk-delete-form" action="{{ route($routeBase.'.users.bulkDelete') }}" method="POST">
                @csrf
                <button type="submit" id="bulk-hidden-submit" class="d-none"></button>
                <table class="table table-striped table-hover rn-superadmin-user-table">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="select-all"></th>
                            <th>{{ __('common.id') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>{{ __('common.role') }}</th>
                            <th>{{ __('common.business') }}</th>
                            <th>{{ __('common.contact') }}</th>
                            <th>{{ __('common.created') }}</th>
                            <th class="text-right">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $roleColor = ['superadmin'=>'danger','admin'=>'primary','gm'=>'warning','storemanager'=>'secondary','salesmanager'=>'success']; @endphp
                        @forelse($users as $u)
                        @php $badge = 'badge badge-'.($roleColor[$u->role] ?? 'info'); @endphp
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $u->id }}" class="row-check"></td>
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->fullName }}</td>
                            <td><a href="mailto:{{ $u->mail }}">{{ $u->mail }}</a></td>
                            <td><span class="{{ $badge }}">{{ ucfirst($u->role) }}</span></td>
                            <td>{{ ($businessMap[$u->businessId] ?? '') ?: __('common.no_data_found') }}</td>
                            <td>{{ $u->contactNumber ?: '-' }}</td>
                            <td>{{ optional($u->created_at)->format('Y-m-d') ?: '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route($routeBase.'.users.edit', $u->id) }}" class="btn btn-sm btn-secondary">{{ __('common.edit') }}</a>
                                @if(($actor->id ?? 0) !== $u->id)
                                    <button type="button" class="btn btn-sm btn-danger single-delete" data-id="{{ $u->id }}">{{ __('common.delete') }}</button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="{{ __('common.cannot_delete_yourself') }}">{{ __('common.delete') }}</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">{{ __('common.no_users_found_for_the_current_filters') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </form>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-danger btn-sm" id="bulk-delete-btn">{{ __('common.bulk_delete') }}</button>
                </div>
                <div>{{ $users->links() }}</div>
            </div>

            <script>
            (function(){
                var selectAll = document.getElementById('select-all');
                var selectError = document.getElementById('select-error');
                var dismissSelectError = document.getElementById('dismiss-select-error');
                var bulkBtn = document.getElementById('bulk-delete-btn');
                var form = document.getElementById('bulk-delete-form');
                var modalSelectedCount = document.getElementById('modal-selected-count');

                function getChecks(){ return document.querySelectorAll('.row-check'); }
                function countSelected(){
                    var count = 0; var checks = getChecks();
                    for(var i=0;i<checks.length;i++){ if(checks[i].checked) count++; }
                    return count;
                }
                function updateModalCount(){ if(modalSelectedCount){ modalSelectedCount.textContent = countSelected() + ' selected'; } }
                function showModal(){
                    updateModalCount();
                    var m = document.getElementById('deleteModal');
                    // Bootstrap 5 (various versions) handling
                    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                        try {
                            var Modal = window.bootstrap.Modal;
                            var inst = null;
                            if (typeof Modal.getOrCreateInstance === 'function') {
                                inst = Modal.getOrCreateInstance(m);
                            } else if (typeof Modal.getInstance === 'function') {
                                inst = Modal.getInstance(m) || new Modal(m);
                            } else {
                                inst = new Modal(m);
                            }
                            if (inst && typeof inst.show === 'function') { inst.show(); return; }
                        } catch (e) { /* fall through to jQuery fallback */ }
                    }
                    // Bootstrap 4 jQuery fallback
                    if ((window.jQuery && typeof window.jQuery === 'function') || (window.$ && typeof window.$ === 'function')) {
                        var jq = window.jQuery || window.$;
                        if (jq && typeof jq.fn.modal === 'function') { jq('#deleteModal').modal('show'); return; }
                    }
                    // Final fallback: basic class toggle (no backdrop/keyboard)
                    m.classList.add('show'); m.style.display='block'; m.setAttribute('aria-modal','true'); m.removeAttribute('aria-hidden');
                }
                function showSelectError(){ if(selectError){ selectError.classList.remove('d-none'); } }
                function hideSelectError(){ if(selectError){ selectError.classList.add('d-none'); } }
                function updateBulkBtn(){ if(bulkBtn){ bulkBtn.disabled = (countSelected()<=0); } }

                if(selectAll){
                    selectAll.addEventListener('change', function(){
                        var checks = getChecks();
                        for(var i=0;i<checks.length;i++){ checks[i].checked = selectAll.checked; }
                        updateModalCount();
                        hideSelectError();
                        updateBulkBtn();
                    });
                }
                if(bulkBtn){
                    bulkBtn.addEventListener('click', function(){
                        var cnt = countSelected();
                        if(cnt <= 0){ showSelectError(); return; }
                        showModal();
                    });
                }
                var singleBtns = document.querySelectorAll('.single-delete');
                for(var i=0;i<singleBtns.length;i++){
                    singleBtns[i].addEventListener('click', function(){
                        var id = this.getAttribute('data-id');
                        var checks = getChecks();
                        for(var j=0;j<checks.length;j++){ checks[j].checked = false; }
                        var target = document.querySelector('.row-check[value="'+id+'"]');
                        if(target){ target.checked = true; }
                        showModal();
                        hideSelectError();
                        updateBulkBtn();
                    });
                }
                // Listen to per-row checkbox changes to update state
                var rowChecks = getChecks();
                for(var k=0;k<rowChecks.length;k++){
                    rowChecks[k].addEventListener('change', function(){
                        hideSelectError();
                        updateModalCount();
                        updateBulkBtn();
                    });
                }
                if(dismissSelectError){ dismissSelectError.addEventListener('click', function(){ hideSelectError(); }); }

                // Initialize state on load
                updateModalCount();
                updateBulkBtn();
            })();
            </script>
            <script>
            (function(){
                var table = document.querySelector('.rn-superadmin-user-table');
                if(!table) return;
                var labels = Array.from(table.querySelectorAll('thead th')).map(function(th){ return th.textContent.trim(); });
                table.querySelectorAll('tbody tr').forEach(function(row){
                    Array.from(row.children).forEach(function(cell,index){ if(cell.tagName === 'TD' && labels[index]) cell.dataset.label = labels[index]; });
                });
            })();
            </script>
        </div>
    </div>
</div>
@endsection
