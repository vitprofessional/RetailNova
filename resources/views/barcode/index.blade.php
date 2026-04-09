@extends('include')
@section('backTitle') Product Barcodes @endsection
@section('container')

<div class="col-12">
    @include('sweetalert::alert')
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="las la-barcode mr-2"></i>Manage Product Barcodes
                </h5>
                <div>
                    <a href="{{ route('generateAllMissingBarcodes') }}" class="btn btn-sm btn-warning mr-2" onclick="return confirm('Generate barcodes for all products without one?')">
                        <i class="las la-sync-alt mr-1"></i>Generate Missing Barcodes
                    </a>
                    <button type="submit" form="bulkPrintForm" class="btn btn-sm btn-primary" title="Print selected product barcodes">
                        <i class="las la-print mr-1"></i>Print Selected
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-end mb-3" style="gap:.5rem;">
                    <div class="rn-search-box" style="min-width:240px;">
                        <span class="rn-search-icon"><i class="las la-search"></i></span>
                        <input type="text" class="rn-search-input rn-filter-input" placeholder="Search by product name or barcode..." data-table-target="barcodeTable">
                        <button class="rn-search-clear">&times;</button>
                    </div>
                </div>

                <form id="bulkPrintForm" method="POST" action="{{ route('barcode.print') }}" target="_blank">
                    @csrf

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="data-tables table table-hover mb-0 tbl-server-info rn-table-pro" id="barcodeTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 5%">
                                    <input type="checkbox" id="selectAllProducts" title="Select all products" />
                                </th>
                                <th style="width: 5%">#</th>
                                <th>Product Name</th>
                                <th style="width: 20%">Barcode</th>
                                <th style="width: 15%">Status</th>
                                <th style="width: 20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>
                                    <input type="checkbox" class="product-checkbox" name="product_ids[]" value="{{ $product->id }}" />
                                </td>
                                <td>{{ $product->id }}</td>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                </td>
                                <td>
                                    @if($product->barCode)
                                    <code class="bg-info text-white px-2 py-1 rounded">{{ $product->barCode }}</code>
                                    @else
                                    <span class="badge badge-secondary">No Barcode</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->barCode)
                                    <span class="badge badge-success">Active</span>
                                    @else
                                    <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->barCode)
                                    <a href="{{ route('barcode.generate', $product->id) }}" class="btn btn-sm btn-info" title="Display Barcode">
                                        <i class="las la-eye mr-1"></i>View
                                    </a>
                                    <button type="button" class="btn btn-sm btn-primary edit-barcode-btn" data-product-id="{{ $product->id }}" data-barcode="{{ $product->barCode }}" title="Edit Barcode">
                                        <i class="las la-edit mr-1"></i>Edit
                                    </button>
                                    @else
                                    <a href="{{ route('barcode.generate', $product->id) }}" class="btn btn-sm btn-success" title="Generate Barcode">
                                        <i class="las la-plus mr-1"></i>Generate
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="las la-inbox" style="font-size: 40px; opacity: 0.5;"></i>
                                    <p class="mt-2">No products available</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Select products and click Print Selected</small>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="las la-print mr-1"></i>Print Selected
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Barcode Modal -->
<div class="modal fade" id="editBarcodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product Barcode</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editBarcodeForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" class="form-control" id="productName" disabled />
                    </div>
                    <div class="form-group">
                        <label>Barcode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newBarcode" name="barcode" required />
                        <small class="text-muted">Enter a unique barcode for this product</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Barcode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    let currentProductId = null;

    const bulkPrintForm = document.getElementById('bulkPrintForm');
    const selectAllProducts = document.getElementById('selectAllProducts');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');

    window.__jqOnReady(function() {
        try {
            if (window.jQuery && typeof jQuery.fn.DataTable === 'function') {
                const $table = jQuery('#barcodeTable');
                if ($table.length && !jQuery.fn.DataTable.isDataTable($table[0])) {
                    $table.DataTable({
                        pageLength: 25,
                        order: [[2, 'asc']],
                        lengthChange: false,
                        columnDefs: [
                            { orderable: false, targets: [0, 5] }
                        ]
                    });
                }
            }
        } catch (e) {
            console.warn('DataTable init failed', e);
        }
    });

    if (selectAllProducts) {
        selectAllProducts.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!selectAllProducts) {
                return;
            }

            const allChecked = productCheckboxes.length > 0 && Array.from(productCheckboxes).every(item => item.checked);
            selectAllProducts.checked = allChecked;
        });
    });

    if (bulkPrintForm) {
        bulkPrintForm.addEventListener('submit', function(e) {
            const selected = Array.from(productCheckboxes).filter(checkbox => checkbox.checked);
            if (!selected.length) {
                e.preventDefault();
                alert('Please select at least one product to print.');
            }
        });
    }

    // Edit barcode button click
    document.querySelectorAll('.edit-barcode-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentProductId = this.dataset.productId;
            document.getElementById('productName').value = this.closest('tr').querySelector('td:nth-child(3)').textContent.trim();
            document.getElementById('newBarcode').value = this.dataset.barcode;
            document.getElementById('editBarcodeModal').classList.add('show');
            document.getElementById('editBarcodeModal').style.display = 'block';
        });
    });

    // Close modal
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        });
    });

    // Save barcode form
    document.getElementById('editBarcodeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const newBarcode = document.getElementById('newBarcode').value.trim();

        if (!newBarcode) {
            alert('Please enter a barcode');
            return;
        }

        try {
            const updateUrlTemplate = @json(route('barcode.update', ['id' => '__ID__']));
            const updateUrl = updateUrlTemplate.replace('__ID__', String(currentProductId));

            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ barcode: newBarcode })
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert('Barcode updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update barcode'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating barcode');
        }
    });
})();
</script>

<!-- Page end -->
@endsection
