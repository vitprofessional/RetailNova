<!-- Barcode Scanner Input Component -->
<div class="form-group mb-3">
    <label for="barcodeInput" class="form-label font-weight-600">
        <i class="las la-barcode mr-2"></i>Scan Barcode or Search Product
    </label>
    <div class="input-group">
        <input 
            type="text" 
            class="form-control" 
            id="barcodeInput"
            placeholder="Scan barcode here... (press Enter or click Search)" 
            autocomplete="off"
            data-barcode-input
        />
        <button 
            class="btn btn-info" 
            type="button" 
            id="searchBarcodeBtn"
            data-barcode-search-btn
        >
            <i class="las la-search mr-1"></i>Search
        </button>
    </div>
    <small class="text-muted d-block mt-2">
        <i class="las la-info-circle mr-1"></i>
        Scan a product barcode to quickly add items to your sale. The system will automatically search and load product details.
    </small>
    <div id="barcodeSearchMessage" class="mt-2"></div>
</div>

<style>
    [data-barcode-input] {
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    
    [data-barcode-input]:focus {
        border-color: #4680ff;
        box-shadow: 0 0 0 0.2rem rgba(70, 128, 255, 0.25);
    }
    
    .barcode-search-success {
        color: #28a745;
        padding: 8px 12px;
        background-color: #f0fdf4;
        border-left: 3px solid #28a745;
        border-radius: 3px;
    }
    
    .barcode-search-error {
        color: #dc3545;
        padding: 8px 12px;
        background-color: #fef8f8;
        border-left: 3px solid #dc3545;
        border-radius: 3px;
    }
</style>

<script>
(function() {
    const barcodeInput = document.querySelector('[data-barcode-input]');
    const searchBtn = document.querySelector('[data-barcode-search-btn]');
    const messageDiv = document.getElementById('barcodeSearchMessage');
    
    if (!barcodeInput) return;
    
    /**
     * Fetch product by barcode via AJAX
     */
    async function searchProductByBarcode(barcode) {
        if (!barcode || barcode.trim() === '') {
            showMessage('Please enter a barcode', 'error');
            return;
        }
        
        try {
            const response = await fetch("{{ route('product.findByBarcode') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ barcode: barcode.trim() })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                showMessage(`Found: ${data.product.name}`, 'success');
                handleProductFound(data.product);
                barcodeInput.value = '';
                barcodeInput.focus();
            } else {
                showMessage(data.message || 'Product not found', 'error');
                barcodeInput.value = '';
                barcodeInput.focus();
            }
        } catch (error) {
            console.error('Barcode search error:', error);
            showMessage('Error searching product', 'error');
        }
    }
    
    /**
     * Handle found product - add to the product list
     */
    function handleProductFound(product) {
        // Check if product already in the list
        const productDropdown = document.getElementById('productName');
        if (productDropdown) {
            // Try to find option by product ID
            const option = Array.from(productDropdown.options).find(opt => opt.value === String(product.id));
            if (option) {
                productDropdown.value = product.id;
                productDropdown.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                showMessage(`Product ${product.name} not found in dropdown`, 'error');
            }
        }
    }
    
    /**
     * Display message to user
     */
    function showMessage(message, type) {
        messageDiv.innerHTML = `<div class="barcode-search-${type}">${message}</div>`;
        
        // Auto-clear success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 3000);
        }
    }
    
    // Handle Enter key press
    barcodeInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchProductByBarcode(barcodeInput.value);
        }
    });
    
    // Handle search button click
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            searchProductByBarcode(barcodeInput.value);
        });
    }
    
    // Clear message when user starts typing
    barcodeInput.addEventListener('focus', () => {
        messageDiv.innerHTML = '';
    });
})();
</script>
