@extends('include')
@section('backTitle'){{ __('common.heading_subscription_plans') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('common.plans') }}</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.super.subscriptions.plans.store') }}" method="POST" class="mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="name" class="form-control" placeholder="{{ __('common.plan_name') }}" required></div>
                    <div class="col-md-2"><input name="price" type="number" step="0.01" class="form-control" placeholder="{{ __('common.price') }}" required></div>
                    <div class="col-md-2"><input name="duration_days" type="number" class="form-control" placeholder="{{ __('common.days') }}" required></div>
                    <div class="col-md-5"><input name="features" class="form-control" placeholder="{{ __('common.features_text') }}"></div>
                </div>
                <div class="mt-2"><button class="btn btn-primary btn-sm">{{ __('common.add_plan') }}</button></div>
            </form>

            <table class="table">
                <thead><tr><th>{{ __('common.name') }}</th><th>{{ __('common.price') }}</th><th>{{ __('common.days') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
                <tbody>
                    @foreach($plans as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ number_format($p->price,2) }}</td>
                        <td>{{ $p->duration_days }}</td>
                        <td>
                            <form action="{{ route('admin.super.subscriptions.plans.delete', $p->id) }}" method="POST" style="display:inline-block">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete plan?')">{{ __('common.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
