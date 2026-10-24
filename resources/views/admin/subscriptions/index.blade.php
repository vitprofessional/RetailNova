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
                    <div class="col-md-3">
                        <select name="business_id" class="form-control" required aria-label="{{ __('common.business') }}">
                            <option value="">{{ __('common.select_business') }}</option>
                            @foreach($businesses as $business)
                                <option value="{{ $business->id }}">{{ $business->businessName }}</option>
                            @endforeach
                        </select>
                    </div>
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

            <table class="table rn-superadmin-desktop-list">
                <thead><tr><th>{{ __('common.id') }}</th><th>{{ __('common.business') }}</th><th>{{ __('common.plan') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.period') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
                <tbody>
                    @foreach($subs as $s)
                    <tr>
                        <td>{{ $s->id }}</td>
                        <td>{{ $businessMap[$s->business_id] ?? __('common.no_data_found') }}</td>
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
            <div class="rn-superadmin-mobile-list" aria-label="{{ __('common.subscriptions') }}">
                @forelse($subs as $s)
                    <article class="rn-superadmin-card">
                        <div class="rn-superadmin-card__main">
                            <strong>{{ $s->plan->name ?? __('common.plan') }}</strong>
                            <span>{{ $businessMap[$s->business_id] ?? __('common.no_data_found') }} · {{ optional($s->starts_at)->format('d/m/Y') }} – {{ optional($s->ends_at)->format('d/m/Y') }}</span>
                            <span class="rn-superadmin-tag">{{ $s->status }}</span>
                        </div>
                        @if($s->status === 'active')
                            <details class="rn-superadmin-menu"><summary aria-label="{{ __('common.actions') }}">⋮</summary><div class="rn-superadmin-menu__items">
                                <a href="{{ route('admin.super.subscriptions.cancel', $s->id) }}" onclick="return confirm('{{ __('common.cancel') }}?')">{{ __('common.cancel') }}</a>
                            </div></details>
                        @endif
                    </article>
                @empty
                    <div class="rn-m-empty-state">{{ __('common.no_data_found') }}</div>
                @endforelse
            </div>
            {{ $subs->links() }}
        </div>
    </div>
</div>
@endsection
