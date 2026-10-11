@extends('include')
@section('backTitle'){{ __('common.heading_subscriptions') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('common.subscriptions') }}</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.super.subscriptions.store') }}" method="POST" class="mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="business_id" class="form-control" placeholder="{{ __('common.business_id') }}" required></div>
                    <div class="col-md-3">
                        <select name="plan_id" class="form-control" required>
                            @foreach($plans as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ number_format($p->price,2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input name="starts_at" type="date" class="form-control" required></div>
                    <div class="col-md-3"><button class="btn btn-primary btn-sm">{{ __('common.start') }}</button></div>
                </div>
            </form>

            <table class="table">
                <thead><tr><th>{{ __('common.id') }}</th><th>{{ __('common.business') }}</th><th>{{ __('common.plan') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.period') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
                <tbody>
                    @foreach($subs as $s)
                    <tr>
                        <td>{{ $s->id }}</td>
                        <td>{{ $s->business_id }}</td>
                        <td>{{ $s->plan->name ?? '-' }}</td>
                        <td><span class="badge badge-info">{{ $s->status }}</span></td>
                        <td>{{ optional($s->starts_at)->format('Y-m-d') }} → {{ optional($s->ends_at)->format('Y-m-d') }}</td>
                        <td>
                            @if($s->status==='active')
                            <a href="{{ route('admin.super.subscriptions.cancel', $s->id) }}" class="btn btn-sm btn-warning" onclick="return confirm('Cancel subscription?')">{{ __('common.cancel') }}</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $subs->links() }}
        </div>
    </div>
</div>
@endsection
