@extends('include')
@section('backTitle') Subscriptions @endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Subscriptions</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.super.subscriptions.store') }}" method="POST" class="mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="business_id" class="form-control" placeholder="Business ID" required></div>
                    <div class="col-md-3">
                        <select name="plan_id" class="form-control" required>
                            @foreach($plans as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ number_format($p->price,2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input name="starts_at" type="date" class="form-control" required></div>
                    <div class="col-md-3"><button class="btn btn-primary btn-sm">Start</button></div>
                </div>
            </form>

            <table class="table">
                <thead><tr><th>ID</th><th>Business</th><th>Plan</th><th>Status</th><th>Period</th><th>Actions</th></tr></thead>
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
                            <a href="{{ route('admin.super.subscriptions.cancel', $s->id) }}" class="btn btn-sm btn-warning" onclick="return confirm('Cancel subscription?')">Cancel</a>
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
