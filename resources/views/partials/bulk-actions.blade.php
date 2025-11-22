@props(['deleteRoute' => null, 'printRoute' => null, 'pdfRoute' => null, 'entity' => 'Items'])
<div id="bulkActionsWrapper" class="bulk-actions align-items-center mb-3 d-none" style="gap: 0.5rem;">
    <div class="me-3"><strong id="bulkSelectedCount">0</strong> selected {{ $entity }}</div>
    @if($deleteRoute)
    <form id="bulkDeleteForm" data-entity="{{ $entity }}" method="POST" action="{{ route($deleteRoute) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled>Delete Selected</button>
    </form>
    @endif
    @if($printRoute)
    <form id="bulkPrintForm" data-entity="{{ $entity }}" method="POST" action="{{ route($printRoute) }}" target="_blank" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary" id="bulkPrintBtn" disabled>Print Selected</button>
    </form>
    @endif
    @if($pdfRoute)
    <form id="bulkPdfForm" data-entity="{{ $entity }}" method="POST" action="{{ route($pdfRoute) }}" target="_blank" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-secondary" id="bulkPdfBtn" disabled>Download PDF</button>
    </form>
    @endif
</div>