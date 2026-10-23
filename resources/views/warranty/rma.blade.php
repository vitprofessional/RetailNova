@extends('include')

@section('backTitle'){{ __('navigation.warranty_claims') }}@endsection
@section('container')
<div class="col-12">
    <h4>{{ __('common.rma_warranty_returns') }}</h4>
    <p class="text-muted">{{ __('common.this_page_will_list_returns_and_rmas_work_in_progress') }}</p>
    <div class="card">
        <div class="card-body">
            <p class="small text-muted">{{ __('common.no_rma_functionality_implemented_yet_you_can_add_forms_or_links_here_to_create_and_manage_rmas') }}</p>
        </div>
    </div>
</div>
@endsection
