@extends('include')
@section('backTitle'){{ __('common.heading_payment_gateways') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('common.gateways') }}</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.super.gateways.store') }}" method="POST" class="mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="name" class="form-control" placeholder="{{ __('common.name') }}" required></div>
                    <div class="col-md-3"><input name="provider" class="form-control" placeholder="{{ __('common.provider') }}" required></div>
                    <div class="col-md-2">
                        <select name="mode" class="form-control" required>
                            <option value="sandbox">{{ __('common.sandbox') }}</option>
                            <option value="live">{{ __('common.live') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input name="api_key" class="form-control" placeholder="{{ __('common.api_key') }}"></div>
                    <div class="col-md-2"><input name="api_secret" class="form-control" placeholder="{{ __('common.api_secret') }}"></div>
                </div>
                <div class="mt-2"><button class="btn btn-primary btn-sm">{{ __('common.add') }}</button></div>
            </form>

            <table class="table rn-superadmin-desktop-list">
                <thead><tr><th>{{ __('common.name') }}</th><th>{{ __('common.provider') }}</th><th>{{ __('common.mode') }}</th><th>{{ __('common.active') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
                <tbody>
                    @foreach($gateways as $g)
                    <tr>
                        <form action="{{ route('admin.super.gateways.update', $g->id) }}" method="POST">
                            @csrf
                            <td><input name="name" class="form-control" value="{{ $g->name }}" ></td>
                            <td><input name="provider" class="form-control" value="{{ $g->provider }}" ></td>
                            <td>
                                <select name="mode" class="form-control">
                                    <option value="sandbox" {{ $g->mode==='sandbox'?'selected':'' }}>{{ __('common.sandbox') }}</option>
                                    <option value="live" {{ $g->mode==='live'?'selected':'' }}>{{ __('common.live') }}</option>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" name="is_active" value="1" {{ $g->is_active ? 'checked' : '' }}>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary">{{ __('common.save') }}</button>
                                <a href="#" onclick="event.preventDefault(); if(confirm('Delete gateway?')) document.getElementById('del-gw-{{ $g->id }}').submit();" class="btn btn-sm btn-danger">{{ __('common.delete') }}</a>
                            </td>
                        </form>
                        <form id="del-gw-{{ $g->id }}" action="{{ route('admin.super.gateways.destroy', $g->id) }}" method="POST" style="display:none">@csrf @method('DELETE')</form>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="rn-superadmin-mobile-list" aria-label="{{ __('common.gateways') }}">
                @forelse($gateways as $g)
                    <article class="rn-superadmin-card">
                        <div class="rn-superadmin-card__main">
                            <strong>{{ $g->name }}</strong>
                            <span>{{ $g->provider }} · {{ __('common.' . $g->mode) }}</span>
                            <span class="rn-superadmin-tag">{{ $g->is_active ? __('common.active') : __('common.inactive') }}</span>
                        </div>
                        <details class="rn-superadmin-menu">
                            <summary aria-label="{{ __('common.actions') }}">⋮</summary>
                            <div class="rn-superadmin-menu__items">
                                <details class="rn-superadmin-edit"><summary>{{ __('common.edit') }}</summary>
                                    <form action="{{ route('admin.super.gateways.update', $g->id) }}" method="POST">@csrf
                                        <label>{{ __('common.name') }}<input name="name" class="form-control" value="{{ $g->name }}" required></label>
                                        <label>{{ __('common.provider') }}<input name="provider" class="form-control" value="{{ $g->provider }}" required></label>
                                        <label>{{ __('common.mode') }}<select name="mode" class="form-control"><option value="sandbox" {{ $g->mode==='sandbox'?'selected':'' }}>{{ __('common.sandbox') }}</option><option value="live" {{ $g->mode==='live'?'selected':'' }}>{{ __('common.live') }}</option></select></label>
                                        <label class="rn-superadmin-checkbox"><input type="checkbox" name="is_active" value="1" {{ $g->is_active ? 'checked' : '' }}> {{ __('common.active') }}</label>
                                        <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                                    </form>
                                </details>
                                <form action="{{ route('admin.super.gateways.destroy', $g->id) }}" method="POST">@csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button>
                                </form>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="rn-m-empty-state">{{ __('common.no_data_found') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
