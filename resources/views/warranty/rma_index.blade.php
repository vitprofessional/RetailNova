@extends('include')

@section('title','Warranty - RMA list')
@section('backTitle'){{ __('common.heading_warranty_rma_list') }}@endsection
@section('container')
<div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4>{{ __('common.rma_returns') }}</h4>
        <div>
            <a href="{{ route('rma.create') }}" class="btn btn-primary btn-sm">{{ __('common.new_rma') }}</a>
            <a href="{{ route('rma.export', request()->all()) }}" class="btn btn-success btn-sm">{{ __('common.export_csv') }}</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row g-2">
                    <div class="col-auto">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">{{ __('common.all_status') }}</option>
                            <option value="open" @if(request('status')=='open') selected @endif>{{ __('common.open') }}</option>
                            <option value="in_progress" @if(request('status')=='in_progress') selected @endif>{{ __('common.in_progress') }}</option>
                            <option value="resolved" @if(request('status')=='resolved') selected @endif>{{ __('common.resolved') }}</option>
                            <option value="closed" @if(request('status')=='closed') selected @endif>{{ __('common.closed') }}</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="customer_id" class="form-control form-control-sm">
                            <option value="">{{ __('common.all_customers') }}</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @if(request('customer_id') == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">{{ __('common.filter') }}</button>
                    </div>
                </div>
            </form>

            @if($rmas->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.customer') }}</th>
                            <th>{{ __('common.serial') }}</th>
                            <th>{{ __('common.reason') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.created') }}</th>
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
                                <a href="{{ route('rma.show', $r->id) }}" class="btn btn-sm btn-outline-info">{{ __('common.view') }}</a>
                                @canEdit(false)
                                <a href="{{ route('rma.edit', $r->id) }}" class="btn btn-sm btn-outline-primary">{{ __('common.edit') }}</a>
                                <form method="POST" action="{{ route('rma.destroy', $r->id) }}" style="display:inline-block;" data-onsubmit="confirm" data-confirm="Delete this RMA?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('common.delete') }}</button>
                                </form>
                                @endcanEdit
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $rmas->links() }}</div>
            @else
            <div class="text-muted">{{ __('common.no_rmas_found') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
