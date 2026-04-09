@extends('include')
@section('backTitle') Generate Barcode @endsection
@section('container')

<div class="col-12">
    @include('sweetalert::alert')
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient py-3">
                <h5 class="card-title mb-0">
                    <i class="las la-barcode mr-2"></i>Product Barcode
                </h5>
            </div>
            <div class="card-body">
                <!-- Product Info -->
                <div class="alert alert-info mb-4">
                    <h6 class="mb-2">
                        <i class="las la-box mr-2"></i>{{ $product->name }}
                    </h6>
                    <p class="mb-0 text-muted">ID: <strong>#{{ $product->id }}</strong></p>
                </div>

                <!-- Barcode Display -->
                <div class="text-center mb-4">
                    <div class="card border-secondary bg-light p-4" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                        <div>
                            <!-- Barcode SVG will be generated here -->
                            <svg id="barcodeContainer" style="max-width: 100%;"></svg>
                            
                            @if(!$product->barCode)
                            <p class="text-muted mt-3">Generating barcode...</p>
                            @else
                            <p class="mt-3">
                                <strong>{{ $product->barCode }}</strong>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Barcode Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-600">Barcode</label>
                            <input type="text" class="form-control" id="barcodeValue" value="{{ $product->barCode ?? '' }}" readonly />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-600">Copy Barcode</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcodeText" value="{{ $product->barCode ?? '' }}" readonly />
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="copyBtn">
                                        <i class="las la-copy mr-1"></i>Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="row no-print">
                    <div class="col-12">
                        <a href="{{ route('barcode.index') }}" class="btn btn-secondary btn-lg ml-2">
                            <i class="las la-arrow-left mr-2"></i>Back to Barcodes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barcode Generation Script -->
<script src="{{ asset('/public/eshop/assets/vendor/jsbarcode/JsBarcode.all.min.js') }}"></script>

<script>
(function() {
    const barcodeValue = '{{ $product->barCode ?? '' }}';
    const barcodeContainer = document.getElementById('barcodeContainer');
    
    if (!barcodeValue) {
        return;
    }

    if (typeof window.JsBarcode !== 'function') {
        if (barcodeContainer) {
            barcodeContainer.outerHTML = '<div class="text-danger">Barcode renderer could not be loaded. Barcode: <strong>' + barcodeValue + '</strong></div>';
        }
        return;
    }

    if (barcodeValue) {
        // Generate Code128 barcode
        JsBarcode('#barcodeContainer', barcodeValue, {
            format: 'CODE128',
            width: 2,
            height: 100,
            displayValue: false
        });
    }

    // Copy button
    document.getElementById('copyBtn').addEventListener('click', function() {
        const barcodeText = document.getElementById('barcodeText');
        barcodeText.select();
        document.execCommand('copy');
        
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="las la-check mr-1"></i>Copied!';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 2000);
    });
})();
</script>

<!-- Print Styles -->
<style media="print">
    body {
        background: white;
    }
    
    .navbar, .iq-sidebar, .card-header, .alert, .no-print {
        display: none;
    }
    
    .card {
        border: none;
        box-shadow: none;
        page-break-inside: avoid;
    }
    
    svg {
        max-width: 100%;
    }
</style>

<!-- Page end -->
@endsection
