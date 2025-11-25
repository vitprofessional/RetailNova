@extends('include')

@section('backTitle')Edit RMA @endsection
@section('container')
<div class="col-12">
    <h4>Edit RMA #{{ $rma->id }}</h4>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('rma.update', $rma->id) }}">
                @csrf
                @method('PUT')
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-control form-control-sm">
                            <option value="">(none)</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @if($rma->customer_id == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Serial</label>
                        <select name="product_serial_id" class="form-control form-control-sm">
                            <option value="">(none)</option>
                            @foreach($serials as $s)
                                <option value="{{ $s->id }}" @if($rma->product_serial_id == $s->id) selected @endif>{{ $s->serialNumber ?? $s->serial ?? $s->serial_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="open" @if($rma->status=='open') selected @endif>Open</option>
                            <option value="in_progress" @if($rma->status=='in_progress') selected @endif>In Progress</option>
                            <option value="resolved" @if($rma->status=='resolved') selected @endif>Resolved</option>
                            <option value="closed" @if($rma->status=='closed') selected @endif>Closed</option>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">Reason</label>
                        <input name="reason" value="{{ $rma->reason }}" class="form-control form-control-sm" />
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4">{{ $rma->notes }}</textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <button class="btn btn-primary">Save</button>
                        <a href="{{ route('rma.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
