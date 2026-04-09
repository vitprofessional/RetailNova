@extends('include')
@section('backTitle') Print Barcodes @endsection
@section('container')

<div class="col-12">
    <div class="card shadow-sm">
        <div class="card-header bg-gradient py-3">
            <h5 class="card-title mb-0">
                <i class="las la-print mr-2"></i>Print Product Barcodes
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">
                Total products: <strong>{{ count($products) }}</strong>
            </p>

            <!-- Print Grid -->
            <div class="barcode-grid">
                @foreach($products as $product)
                <div class="barcode-card">
                    <div class="barcode-item">
                        <h6 class="product-name">{{ substr($product->name, 0, 20) }}</h6>
                        <div class="barcode-container">
                            <svg class="barcode-svg" data-barcode="{{ $product->barCode }}"></svg>
                        </div>
                        <p class="barcode-text">{{ $product->barCode }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Actions -->
            <div class="mt-4">
                <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">
                    <i class="las la-print mr-2"></i>Print All Barcodes
                </button>
                <a href="{{ route('barcode.index') }}" class="btn btn-secondary btn-lg ml-2">
                    <i class="las la-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Barcode Library -->
<script src="{{ asset('/public/eshop/assets/vendor/jsbarcode/JsBarcode.all.min.js') }}"></script>

<script>
(function() {
    if (typeof window.JsBarcode !== 'function') {
        document.querySelectorAll('.barcode-container').forEach(container => {
            const svg = container.querySelector('.barcode-svg');
            const barcode = svg ? (svg.dataset.barcode || '') : '';
            container.innerHTML = '<div class="text-danger" style="font-size: 11px;">Barcode renderer unavailable</div>' +
                (barcode ? ('<div style="font-size: 10px;">' + barcode + '</div>') : '');
        });
        return;
    }

    // Generate all barcodes
    document.querySelectorAll('.barcode-svg').forEach(svg => {
        const barcode = svg.dataset.barcode;
        if (barcode) {
            JsBarcode(svg, barcode, {
                format: 'CODE128',
                width: 1.5,
                height: 40,
                displayValue: false
            });
        }
    });
})();
</script>

<!-- Print Styles -->
<style media="print">
    * {
        margin: 0;
        padding: 0;
    }

    body {
        background: white;
        font-size: 11px;
    }

    .navbar, .iq-sidebar, .card-header, .alert, [class*="card-body"] > div:last-of-type {
        display: none !important;
    }

    .card {
        border: none;
        box-shadow: none;
    }

    .barcode-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 10px !important;
        page-break-inside: avoid !important;
    }

    .barcode-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .barcode-item {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
        page-break-inside: avoid;
    }

    .product-name {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .barcode-container {
        margin: 8px 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 50px;
    }

    .barcode-svg {
        max-width: 100%;
        max-height: 50px;
    }

    .barcode-text {
        font-size: 9px;
        margin-top: 5px;
        font-weight: bold;
    }
</style>

<style media="screen">
    .barcode-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .barcode-card {
        display: flex;
        justify-content: center;
    }

    .barcode-item {
        border: 2px solid #f0f0f0;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        background: #f9f9f9;
        transition: all 0.3s ease;
        width: 100%;
        max-width: 200px;
    }

    .barcode-item:hover {
        border-color: #4680ff;
        box-shadow: 0 2px 8px rgba(70, 128, 255, 0.1);
        background: #f5f8ff;
    }

    .product-name {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #333;
    }

    .barcode-container {
        margin: 10px 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60px;
        background: white;
        border-radius: 4px;
        padding: 5px;
    }

    .barcode-svg {
        max-width: 100%;
        max-height: 60px;
    }

    .barcode-text {
        font-size: 11px;
        margin-top: 8px;
        font-weight: 600;
        letter-spacing: 0.5px;
        color: #666;
    }
</style>

<!-- Page end -->
@endsection
