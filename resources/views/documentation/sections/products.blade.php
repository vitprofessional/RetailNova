<div class="section">
    <h1>📦 Product Management</h1>
    
    <h2>Overview</h2>
    <p>
        The Product Management module is the core of your inventory system. It allows you to create, organize, 
        and manage your entire product catalog with support for categories, brands, variants, serial numbers, 
        barcodes, and comprehensive pricing options.
    </p>

    <h2>📝 Adding New Products</h2>
    
    <h3>Single Product Entry</h3>
    <div class="step">
        <span class="step-number">1</span>
        Navigate to <strong>Products → Add Product</strong>
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Fill in the product details form:
    </div>

    <table>
        <thead>
            <tr>
                <th>Field</th>
                <th>Required</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Product Name</strong></td>
                <td>✅ Yes</td>
                <td>Full product name/description</td>
            </tr>
            <tr>
                <td><strong>SKU</strong></td>
                <td>❌ Optional</td>
                <td>Stock Keeping Unit (auto-generated if blank)</td>
            </tr>
            <tr>
                <td><strong>Barcode</strong></td>
                <td>❌ Optional</td>
                <td>Product barcode number</td>
            </tr>
            <tr>
                <td><strong>Category</strong></td>
                <td>❌ Optional</td>
                <td>Product category (e.g., Electronics, Accessories)</td>
            </tr>
            <tr>
                <td><strong>Brand</strong></td>
                <td>❌ Optional</td>
                <td>Manufacturer or brand name</td>
            </tr>
            <tr>
                <td><strong>Unit</strong></td>
                <td>❌ Optional</td>
                <td>Selling unit (Piece, Box, Kg, etc.)</td>
            </tr>
            <tr>
                <td><strong>Purchase Price</strong></td>
                <td>❌ Optional</td>
                <td>Cost price from supplier</td>
            </tr>
            <tr>
                <td><strong>Selling Price</strong></td>
                <td>❌ Optional</td>
                <td>Retail price to customers</td>
            </tr>
            <tr>
                <td><strong>Opening Stock</strong></td>
                <td>❌ Optional</td>
                <td>Initial inventory quantity</td>
            </tr>
            <tr>
                <td><strong>Product Image</strong></td>
                <td>❌ Optional</td>
                <td>Product photo (JPG, PNG)</td>
            </tr>
            <tr>
                <td><strong>Product Description</strong></td>
                <td>❌ Optional</td>
                <td>Detailed product information</td>
            </tr>
            <tr>
                <td><strong>Serial Number Tracking</strong></td>
                <td>❌ Optional</td>
                <td>Enable for individual item tracking</td>
            </tr>
        </tbody>
    </table>

    <div class="step">
        <span class="step-number">3</span>
        Click <strong>"Save Product"</strong> to add to inventory
    </div>

    <div class="note">
        <strong>💡 Note:</strong> Only product name is required. This allows quick product addition 
        during busy times, with details completed later.
    </div>

    <h3>Bulk Product Entry</h3>
    <p>When creating a new sale, you can quickly add products on-the-fly:</p>
    <ul>
        <li>Click <strong>"Add Brand"</strong> to create new brands instantly</li>
        <li>Click <strong>"Add Category"</strong> to create new categories</li>
        <li>Click <strong>"Add Unit"</strong> to add measurement units</li>
        <li>All modals support AJAX submission without page refresh</li>
        <li>New items immediately appear in selection dropdowns</li>
    </ul>

    <h2>🗂️ Product Organization</h2>
    
    <h3>Categories</h3>
    <p>Organize products into logical categories:</p>
    <ul>
        <li>Navigate to <strong>Settings → Categories</strong></li>
        <li>Click <strong>"Add Category"</strong></li>
        <li>Enter category name (e.g., Laptops, Mobile Phones, Accessories)</li>
        <li>Add description (optional)</li>
        <li>Save the category</li>
    </ul>

    <h3>Brands</h3>
    <p>Track products by manufacturer:</p>
    <ul>
        <li>Navigate to <strong>Settings → Brands</strong></li>
        <li>Click <strong>"Add Brand"</strong></li>
        <li>Enter brand name (e.g., Apple, Samsung, HP)</li>
        <li>Add logo image (optional)</li>
        <li>Save the brand</li>
    </ul>

    <h3>Units</h3>
    <p>Define measurement units for products:</p>
    <ul>
        <li>Navigate to <strong>Settings → Units</strong></li>
        <li>Click <strong>"Add Unit"</strong></li>
        <li>Enter unit name (e.g., Piece, Box, Kg, Liter)</li>
        <li>Add short name/symbol (e.g., pcs, kg)</li>
        <li>Save the unit</li>
    </ul>

    <h2>🔢 Serial Number Tracking</h2>
    <p>For high-value or warranty-tracked items:</p>
    
    <h3>Enabling Serial Numbers</h3>
    <ol>
        <li>When creating/editing a product, check <strong>"Track Serial Numbers"</strong></li>
        <li>During purchase, enter unique serial numbers for each unit</li>
        <li>During sale, select which serial numbers are being sold</li>
        <li>System prevents selling the same serial number twice</li>
    </ol>

    <h3>Serial Number Benefits</h3>
    <ul>
        <li><strong>Warranty Tracking:</strong> Link warranties to specific units</li>
        <li><strong>Service History:</strong> Track repairs for individual items</li>
        <li><strong>Theft Prevention:</strong> Identify stolen goods</li>
        <li><strong>Return Management:</strong> Verify returned items</li>
        <li><strong>Compliance:</strong> Meet regulatory requirements</li>
    </ul>

    <h2>💰 Pricing Management</h2>
    
    <h3>Single Pricing</h3>
    <p>Set one selling price for all customers:</p>
    <ul>
        <li>Enter the <strong>Selling Price</strong> when creating the product</li>
        <li>Price applies to all sales</li>
        <li>Simple and straightforward</li>
    </ul>

    <h3>Profit Margin Calculation</h3>
    <p>The system automatically calculates:</p>
    <ul>
        <li><strong>Profit Amount:</strong> Selling Price - Purchase Price</li>
        <li><strong>Profit Margin %:</strong> (Profit / Selling Price) × 100</li>
        <li><strong>Markup %:</strong> (Profit / Purchase Price) × 100</li>
    </ul>

    <h2>🔍 Finding Products</h2>
    <p>Multiple ways to locate products:</p>
    <ul>
        <li><strong>Quick Search:</strong> Type name, SKU, or barcode</li>
        <li><strong>Barcode Scanner:</strong> Scan product barcode</li>
        <li><strong>Filter by Category:</strong> View all products in a category</li>
        <li><strong>Filter by Brand:</strong> See all products from a brand</li>
        <li><strong>Stock Status Filter:</strong> In Stock, Low Stock, Out of Stock</li>
    </ul>

    <h2>📊 Product Details</h2>
    <p>Click on a product to view comprehensive information:</p>
    <ul>
        <li>Product specifications and images</li>
        <li>Current stock level</li>
        <li>Purchase and sales history</li>
        <li>Serial numbers (if applicable)</li>
        <li>Supplier information</li>
        <li>Profit analysis</li>
    </ul>

    <h2>✏️ Editing Products</h2>
    <p>To update product information:</p>
    <ol>
        <li>Find and open the product</li>
        <li>Click <strong>"Edit"</strong></li>
        <li>Modify necessary fields</li>
        <li>Click <strong>"Update"</strong></li>
    </ol>

    <div class="warning">
        <strong>⚠️ Important:</strong> Changing prices affects only future transactions. 
        Existing sales and purchases retain their original prices.
    </div>

    <h2>📉 Managing Stock Levels</h2>
    
    <h3>Stock Adjustments</h3>
    <p>To manually adjust stock:</p>
    <ol>
        <li>Go to <strong>Products → Stock Adjustment</strong></li>
        <li>Select the product</li>
        <li>Enter adjustment type (Add or Subtract)</li>
        <li>Enter quantity</li>
        <li>Add reason/note</li>
        <li>Save adjustment</li>
    </ol>

    <h3>Low Stock Alerts</h3>
    <p>Products with ≤ 10 units trigger alerts:</p>
    <ul>
        <li>Appear on Dashboard</li>
        <li>Highlighted in product list</li>
        <li>Included in Stock Reports</li>
        <li>Email notifications (if configured)</li>
    </ul>

    <h2>🗑️ Deleting Products</h2>
    <div class="warning">
        <strong>⚠️ Warning:</strong> Cannot delete products with:
        <ul>
            <li>Transaction history</li>
            <li>Current stock</li>
            <li>Active serial numbers</li>
        </ul>
        Adjust stock to zero and ensure no transactions before deletion.
    </div>

    <h2>🎯 Best Practices</h2>
    <ul>
        <li>Use clear, descriptive product names</li>
        <li>Add product images for easier identification</li>
        <li>Keep SKUs and barcodes unique</li>
        <li>Categorize products logically</li>
        <li>Set realistic selling prices</li>
        <li>Regularly review and update pricing</li>
        <li>Monitor slow-moving items</li>
        <li>Maintain accurate stock counts</li>
        <li>Use serial numbers for valuable items</li>
        <li>Document product specifications thoroughly</li>
    </ul>

    <div class="tip">
        <strong>💡 Pro Tip:</strong> Use product images and detailed descriptions to help staff 
        identify items quickly, especially if you have similar-looking products.
    </div>
</div>
