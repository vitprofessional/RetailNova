@props(['deleteRoute' => null, 'printRoute' => null, 'pdfRoute' => null, 'entity' => 'Items'])
<div id="bulkActionsWrapper" class="bulk-actions align-items-center mb-3 d-none" style="gap: 0.5rem;">
    <div class="me-3"><strong id="bulkSelectedCount">0</strong> selected {{ $entity }}</div>
    <div id="bulkGroupHint" class="alert alert-warning mb-0 py-1 px-2 d-none" style="font-size:12px;">Selection must be from the same customer and date to print.</div>
    @if($deleteRoute)
    <form id="bulkDeleteForm" data-entity="{{ $entity }}" method="POST" action="{{ route($deleteRoute) }}" class="d-inline">
        @csrf
        <input type="hidden" id="bulkDeleteType" name="deleteType" value="profileOnly">
        <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled>Delete Selected</button>
    </form>
    @endif
    @if($printRoute)
    <form id="bulkPrintForm" data-entity="{{ $entity }}" method="POST" action="{{ route($printRoute) }}" target="_blank" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary" id="bulkPrintBtn" disabled title="All selected services must belong to the same customer and date to print.">Print Selected</button>
    </form>
    @endif
    @if($pdfRoute)
    <form id="bulkPdfForm" data-entity="{{ $entity }}" method="POST" action="{{ route($pdfRoute) }}" target="_blank" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-secondary" id="bulkPdfBtn" disabled title="All selected services must belong to the same customer and date to generate a consolidated PDF.">Download PDF</button>
    </form>
    @endif
</div>