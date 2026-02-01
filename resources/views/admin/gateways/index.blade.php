@extends('include')
@section('backTitle') Payment Gateways @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Gateways</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.super.gateways.store') }}" method="POST" class="mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
                    <div class="col-md-3"><input name="provider" class="form-control" placeholder="Provider" required></div>
                    <div class="col-md-2">
                        <select name="mode" class="form-control" required>
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input name="api_key" class="form-control" placeholder="API Key"></div>
                    <div class="col-md-2"><input name="api_secret" class="form-control" placeholder="API Secret"></div>
                </div>
                <div class="mt-2"><button class="btn btn-primary btn-sm">Add</button></div>
            </form>

            <table class="table">
                <thead><tr><th>Name</th><th>Provider</th><th>Mode</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($gateways as $g)
                    <tr>
                        <form action="{{ route('admin.super.gateways.update', $g->id) }}" method="POST">
                            @csrf
                            <td><input name="name" class="form-control" value="{{ $g->name }}" ></td>
                            <td><input name="provider" class="form-control" value="{{ $g->provider }}" ></td>
                            <td>
                                <select name="mode" class="form-control">
                                    <option value="sandbox" {{ $g->mode==='sandbox'?'selected':'' }}>Sandbox</option>
                                    <option value="live" {{ $g->mode==='live'?'selected':'' }}>Live</option>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" name="is_active" value="1" {{ $g->is_active ? 'checked' : '' }}>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary">Save</button>
                                <a href="#" onclick="event.preventDefault(); if(confirm('Delete gateway?')) document.getElementById('del-gw-{{ $g->id }}').submit();" class="btn btn-sm btn-danger">Delete</a>
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
