@extends('include')

@section('title', 'RMA Details')
@section('container')
<div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4>{{ __('common.rma_details') }}</h4>
        <div>
            <a href="{{ route('rma.index') }}" class="btn btn-sm btn-secondary">{{ __('common.back_to_list') }}</a>
            <a href="{{ route('rma.edit', $rma->id) }}" class="btn btn-sm btn-primary">{{ __('common.edit') }}</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">{{ __('common.rma') }}</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">{{ __('common.rma_no') }}</dt>
                        <dd class="col-8">{{ $rma->rma_no ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.status') }}</dt>
                        <dd class="col-8">{{ ucfirst(str_replace('_',' ',$rma->status)) }}</dd>
                        <dt class="col-4">{{ __('common.created') }}</dt>
                        <dd class="col-8">{{ optional($rma->created_at)->format('Y-m-d H:i') }}</dd>
                        <dt class="col-4">{{ __('common.resolved_at') }}</dt>
                        <dd class="col-8">{{ optional($rma->resolved_at)->format('Y-m-d H:i') ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.reason') }}</dt>
                        <dd class="col-8">{{ $rma->reason ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.notes') }}</dt>
                        <dd class="col-8">{{ $rma->notes ?? '-' }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">{{ __('common.product_serial') }}</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">{{ __('common.product') }}</dt>
                        <dd class="col-8">{{ optional($product)->productName ?? optional($product)->name ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.serial') }}</dt>
                        <dd class="col-8">{{ optional($serial)->serialNumber ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.purchase_ref') }}</dt>
                        <dd class="col-8">{{ optional($purchase)->invoice ?? optional($purchase)->reference ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.purchase_date') }}</dt>
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
                    <h6 class="text-muted mb-2">{{ __('common.customer') }}</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">{{ __('common.name') }}</dt>
                        <dd class="col-8">{{ optional($customer)->name ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.mobile') }}</dt>
                        <dd class="col-8">{{ optional($customer)->mobile ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.email') }}</dt>
                        <dd class="col-8">{{ optional($customer)->mail ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.address') }}</dt>
                        <dd class="col-8">{{ optional($customer)->address ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Supplier @if(!empty($supplier) && !empty($supplierInferred))<span class="badge badge-info" style="margin-left:6px;">{{ __('common.inferred') }}</span>@endif</h6>
                    <dl class="row mb-0">
                        <dt class="col-4">{{ __('common.name') }}</dt>
                        <dd class="col-8">{{ optional($supplier)->name ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.mobile') }}</dt>
                        <dd class="col-8">{{ optional($supplier)->mobile ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.email') }}</dt>
                        <dd class="col-8">{{ optional($supplier)->mail ?? '-' }}</dd>
                        <dt class="col-4">{{ __('common.address') }}</dt>
                        <dd class="col-8">{{ optional($supplier)->address ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
