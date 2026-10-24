@extends('include')

@section('title')Edit RMA #{{ $rma->id }}@endsection
@section('backTitle'){{ __('common.heading_edit_rma') }}@endsection
@section('container')
<div class="col-12 rn-m-rma-page">
    <h4 class="rn-m-rma-title">{{ __('common.heading_edit_rma') }} #{{ $rma->id }}</h4>
    <div class="card rn-m-rma-card">
        <div class="card-body">
            <form method="POST" action="{{ route('rma.update', $rma->id) }}" class="rn-m-form rn-m-rma-form">
                @csrf
                @method('PUT')
                <div class="row g-2 rn-m-form-section rn-m-rma-fields">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.customer') }}</label>
                        <select name="customer_id" class="form-control form-control-sm">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @if($rma->customer_id == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.serial') }}</label>
                        <select name="product_serial_id" class="form-control form-control-sm">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach($serials as $s)
                                <option value="{{ $s->id }}" @if($rma->product_serial_id == $s->id) selected @endif>{{ $s->serialNumber ?? $s->serial ?? $s->serial_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="open" @if($rma->status=='open') selected @endif>{{ __('common.open') }}</option>
                            <option value="in_progress" @if($rma->status=='in_progress') selected @endif>{{ __('common.in_progress') }}</option>
                            <option value="resolved" @if($rma->status=='resolved') selected @endif>{{ __('common.resolved') }}</option>
                            <option value="closed" @if($rma->status=='closed') selected @endif>{{ __('common.closed') }}</option>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">{{ __('common.reason') }}</label>
                        <input name="reason" value="{{ $rma->reason }}" class="form-control form-control-sm" />
                        @error('reason')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4">{{ $rma->notes }}</textarea>
                        @error('notes')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="rn-m-sticky-actions rn-m-rma-actions">
                        <a href="{{ route('rma.index') }}" class="rn-m-btn rn-m-btn--outline">{{ __('common.cancel') }}</a>
                        <button type="submit" class="rn-m-btn rn-m-btn--primary">{{ __('common.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
