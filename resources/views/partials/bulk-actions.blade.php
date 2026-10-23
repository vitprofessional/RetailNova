@props(['deleteRoute' => null, 'printRoute' => null, 'pdfRoute' => null, 'entity' => 'Items', 'deleteLabel' => null, 'reasonRequired' => false,
    'fullDeleteAllowed' => true, 'fullDeleteWarning' => null, 'fullDeleteConfirm' => null])
<div id="bulkActionsWrapper" class="bulk-actions align-items-center mb-3 d-none" style="gap: 0.5rem;">
    <div class="me-3"><strong id="bulkSelectedCount">0</strong> {{ __('common.selected') }} {{ $entity }}</div>
    <div id="bulkGroupHint" class="alert alert-warning mb-0 py-1 px-2 d-none" style="font-size:12px;">{{ __('common.selection_must_be_from_the_same_customer_and_date_to_print') }}</div>
    @if($deleteRoute)
    <form id="bulkDeleteForm" data-entity="{{ $entity }}" method="POST" action="{{ route($deleteRoute) }}" class="d-inline" @if(!empty($reasonRequired)) data-bulk-reason @endif
        data-full-delete="{{ ($fullDeleteAllowed ?? true) ? 'allowed' : 'denied' }}"
        @if(($fullDeleteAllowed ?? true) && !empty($fullDeleteWarning)) data-full-delete-warning="{{ $fullDeleteWarning }}" @endif
        @if(($fullDeleteAllowed ?? true) && !empty($fullDeleteConfirm)) data-full-delete-confirm="{{ $fullDeleteConfirm }}" @endif>
        @csrf
        <input type="hidden" id="bulkDeleteType" name="deleteType" value="profileOnly">
        @if(!empty($reasonRequired))
        <input type="hidden" name="reason" value="">
        @endif
        <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled>{{ $deleteLabel ?? __('common.delete_selected') }}</button>
    </form>
    @endif
    @if($printRoute)
    <form id="bulkPrintForm" data-entity="{{ $entity }}" method="POST" action="{{ route($printRoute) }}" target="_blank" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary" id="bulkPrintBtn" disabled title="{{ __('common.all_selected_services_must_belong_to_the_same_customer_and_date_to_print') }}">{{ __('common.print_selected') }}</button>
    </form>
    @endif
    @if($pdfRoute)
    <form id="bulkPdfForm" data-entity="{{ $entity }}" method="POST" action="{{ route($pdfRoute) }}" target="_blank" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-secondary" id="bulkPdfBtn" disabled title="{{ __('common.all_selected_services_must_belong_to_the_same_customer_and_date_to_generate_a_consolidated_pdf') }}">{{ __('common.download_pdf') }}</button>
    </form>
    @endif
</div>