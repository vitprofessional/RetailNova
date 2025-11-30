@extends('include')

@section('title', 'RMA Details')
@section('container')
<div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4>RMA Details</h4>
        <div>
            <a href="{{ route('rma.index') }}" class="btn btn-sm btn-secondary">Back to List</a>
            <a href="{{ route('rma.edit', $rma->id) }}" class="btn btn-sm btn-primary">Edit</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">RMA</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">RMA No</dt>
                        <dd class="col-8">{{ $rma->rma_no ?? '-' }}</dd>
                        <dt class="col-4">Status</dt>
                        <dd class="col-8">{{ ucfirst(str_replace('_',' ',$rma->status)) }}</dd>
                        <dt class="col-4">Created</dt>
                        <dd class="col-8">{{ optional($rma->created_at)->format('Y-m-d H:i') }}</dd>
                        <dt class="col-4">Resolved At</dt>
                        <dd class="col-8">{{ optional($rma->resolved_at)->format('Y-m-d H:i') ?? '-' }}</dd>
                        <dt class="col-4">Reason</dt>
                        <dd class="col-8">{{ $rma->reason ?? '-' }}</dd>
                        <dt class="col-4">Notes</dt>
                        <dd class="col-8">{{ $rma->notes ?? '-' }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Product / Serial</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">Product</dt>
                        <dd class="col-8">{{ optional($product)->productName ?? optional($product)->name ?? '-' }}</dd>
                        <dt class="col-4">Serial</dt>
                        <dd class="col-8">{{ optional($serial)->serialNumber ?? '-' }}</dd>
                        <dt class="col-4">Purchase Ref</dt>
                        <dd class="col-8">{{ optional($purchase)->invoice ?? optional($purchase)->reference ?? '-' }}</dd>
                        <dt class="col-4">Purchase Date</dt>
                        <dd class="col-8">{{ optional(optional($purchase)->created_at)->format('Y-m-d') ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Customer</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">Name</dt>
                        <dd class="col-8">{{ optional($customer)->name ?? '-' }}</dd>
                        <dt class="col-4">Mobile</dt>
                        <dd class="col-8">{{ optional($customer)->mobile ?? '-' }}</dd>
                        <dt class="col-4">Email</dt>
                        <dd class="col-8">{{ optional($customer)->mail ?? '-' }}</dd>
                        <dt class="col-4">Address</dt>
                        <dd class="col-8">{{ optional($customer)->address ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Supplier @if(!empty($supplier) && !empty($supplierInferred))<span class="badge badge-info" style="margin-left:6px;">inferred</span>@endif</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">Name</dt>
                        <dd class="col-8">{{ optional($supplier)->name ?? '-' }}</dd>
                        <dt class="col-4">Mobile</dt>
                        <dd class="col-8">{{ optional($supplier)->mobile ?? '-' }}</dd>
                        <dt class="col-4">Email</dt>
                        <dd class="col-8">{{ optional($supplier)->mail ?? '-' }}</dd>
                        <dt class="col-4">Address</dt>
                        <dd class="col-8">{{ optional($supplier)->address ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
