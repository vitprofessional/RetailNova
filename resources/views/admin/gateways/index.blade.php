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

            <table class="table">
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
        </div>
    </div>
</div>
@endsection
