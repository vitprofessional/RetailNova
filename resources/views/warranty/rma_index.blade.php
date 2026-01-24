@extends('include')

@section('title','Warranty - RMA list')
@section('backTitle')Warranty - RMA list @endsection
@section('container')
<div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4>RMA / Returns</h4>
        <div>
            <a href="{{ route('rma.create') }}" class="btn btn-primary btn-sm">New RMA</a>
            <a href="{{ route('rma.export', request()->all()) }}" class="btn btn-success btn-sm">Export CSV</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row g-2">
                    <div class="col-auto">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All status</option>
                            <option value="open" @if(request('status')=='open') selected @endif>Open</option>
                            <option value="in_progress" @if(request('status')=='in_progress') selected @endif>In Progress</option>
                            <option value="resolved" @if(request('status')=='resolved') selected @endif>Resolved</option>
                            <option value="closed" @if(request('status')=='closed') selected @endif>Closed</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="customer_id" class="form-control form-control-sm">
                            <option value="">All customers</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @if(request('customer_id') == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Filter</button>
                    </div>
                </div>
            </form>

            @if($rmas->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Serial</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rmas as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->customer->name ?? '-' }}</td>
                            <td>{{ optional($r->productSerial)->serialNumber ?? '-' }}</td>
                            <td>{{ $r->reason }}</td>
                            <td>{{ $r->status }}</td>
                            <td>{{ optional($r->created_at)->format('Y-m-d') }}</td>
                            <td class="text-right">
                                <a href="{{ route('rma.show', $r->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                <a href="{{ route('rma.edit', $r->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('rma.destroy', $r->id) }}" style="display:inline-block;" data-onsubmit="confirm" data-confirm="Delete this RMA?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $rmas->links() }}</div>
            @else
            <div class="text-muted">No RMAs found.</div>
            @endif
        </div>
    </div>
</div>
@endsection
