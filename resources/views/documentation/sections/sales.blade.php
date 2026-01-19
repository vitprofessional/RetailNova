<div class="section">
    <h1>🛒 Sales Management</h1>
    
    <h2>Overview</h2>
    <p>
        The Sales module is your Point of Sale (POS) system for processing customer transactions. It supports 
        multiple payment methods, partial payments, credit sales, discounts, and automatic invoice generation.
    </p>

    <h2>💳 Processing a Sale</h2>
    
    <div class="step">
        <span class="step-number">1</span>
        Navigate to <strong>Sales → New Sale</strong> or click the POS button on Dashboard
    </div>

    <div class="step">
        <span class="step-number">2</span>
        <strong>Select Customer:</strong>
        <ul>
            <li>Search and select existing customer</li>
            <li>Or click "Add New Customer" to create on-the-fly</li>
            <li>Walk-in customer option available</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">3</span>
        <strong>Add Products:</strong>
        <ul>
            <li>Search product by name, SKU, or scan barcode</li>
            <li>Click on product to add to cart</li>
            <li>Enter quantity (default is 1)</li>
            <li>Adjust price if needed</li>
            <li>For serial-tracked items, select specific serial numbers</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">4</span>
        <strong>Apply Discounts (Optional):</strong>
        <ul>
            <li>Item-level discount: Apply to individual products</li>
            <li>Cart-level discount: Apply to total amount</li>
            <li>Discount types: Percentage or fixed amount</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">5</span>
        <strong>Select Payment Method:</strong>
        <ul>
            <li>Cash</li>
            <li>Card (Credit/Debit)</li>
            <li>Mobile Money</li>
            <li>Bank Transfer</li>
            <li>Credit (pay later)</li>
            <li>Multiple payment methods (split payment)</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">6</span>
        <strong>Enter Payment Amount:</strong>
        <ul>
            <li>Full payment: Enter total amount</li>
            <li>Partial payment: Enter paid amount (balance goes to credit)</li>
            <li>System calculates change for cash payments</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">7</span>
        Click <strong>"Complete Sale"</strong> - Invoice auto-generates
    </div>

    <div class="step">
        <span class="step-number">8</span>
        <strong>Post-Sale Actions:</strong>
        <ul>
            <li>Print invoice</li>
            <li>Email to customer</li>
            <li>SMS receipt</li>
            <li>Process return (if needed)</li>
        </ul>
    </div>

    <h2>💰 Payment Methods</h2>
    
    <h3>Cash Payment</h3>
    <ul>
        <li>Enter amount received</li>
        <li>System calculates change due</li>
        <li>Can process exact amount or overpayment</li>
    </ul>

    <h3>Card Payment</h3>
    <ul>
        <li>Select card type (Visa, MasterCard, etc.)</li>
        <li>Enter transaction reference number</li>
        <li>Select terminal/account</li>
    </ul>

    <h3>Credit Sale</h3>
    <ul>
        <li>Customer must be selected (not walk-in)</li>
        <li>Checks customer credit limit</li>
        <li>Creates receivable balance</li>
        <li>Payment can be collected later</li>
    </ul>

    <h3>Split Payment</h3>
    <ul>
        <li>Accept multiple payment methods for one sale</li>
        <li>Example: $50 cash + $30 card</li>
        <li>Each payment recorded separately</li>
    </ul>

    <h2>📋 Viewing Sales</h2>
    <p>Access sales history from <strong>Sales → View Sales</strong></p>

    <h3>Information Displayed:</h3>
    <ul>
        <li>Invoice number and date</li>
        <li>Customer name</li>
        <li>Total amount</li>
        <li>Payment status (Paid, Pending, Partial)</li>
        <li>Payment method</li>
        <li>Actions (View, Edit, Print, Return)</li>
    </ul>

    <h3>Filtering Options:</h3>
    <ul>
        <li>Date range</li>
        <li>Customer</li>
        <li>Payment status</li>
        <li>Payment method</li>
        <li>Cashier/user</li>
    </ul>

    <h2>↩️ Processing Returns</h2>
    
    <div class="step">
        <span class="step-number">1</span>
        Find the original sale invoice
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Click <strong>"Process Return"</strong>
    </div>

    <div class="step">
        <span class="step-number">3</span>
        Select items being returned
    </div>

    <div class="step">
        <span class="step-number">4</span>
        Enter return quantity for each item
    </div>

    <div class="step">
        <span class="step-number">5</span>
        Add return reason
    </div>

    <div class="step">
        <span class="step-number">6</span>
        Select refund method:
        <ul>
            <li>Cash refund</li>
            <li>Credit to customer account</li>
            <li>Exchange for other products</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">7</span>
        Complete return - stock automatically adjusted
    </div>

    <h2>📦 Purchase Management</h2>
    
    <h2>Creating Purchase Orders</h2>
    
    <div class="step">
        <span class="step-number">1</span>
        Go to <strong>Purchases → New Purchase</strong>
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Select supplier
    </div>

    <div class="step">
        <span class="step-number">3</span>
        Add products with purchase details:
        <ul>
            <li>Product selection</li>
            <li>Quantity ordered</li>
            <li>Purchase price per unit</li>
            <li>For serial-tracked items, enter serial numbers</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">4</span>
        Enter purchase details:
        <ul>
            <li>Purchase date</li>
            <li>Expected delivery date</li>
            <li>Reference number (supplier invoice)</li>
            <li>Notes</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">5</span>
        Select payment method and amount
    </div>

    <div class="step">
        <span class="step-number">6</span>
        Save purchase - stock automatically updated
    </div>

    <h2>📊 Purchase History</h2>
    <p>View all purchases from <strong>Purchases → View Purchases</strong></p>
    <ul>
        <li>Purchase order number</li>
        <li>Supplier name</li>
        <li>Purchase date</li>
        <li>Total amount</li>
        <li>Payment status</li>
        <li>Actions (View, Edit, Make Payment)</li>
    </ul>

    <h2>🎯 Best Practices</h2>
    
    <h3>Sales Processing</h3>
    <ul>
        <li>Always verify product selection before completing</li>
        <li>Check serial numbers match physical items</li>
        <li>Confirm payment amount and method</li>
        <li>Print receipts for all transactions</li>
        <li>Process returns promptly to maintain accuracy</li>
    </ul>

    <h3>Credit Sales</h3>
    <ul>
        <li>Verify customer credit limit before selling</li>
        <li>Document credit terms clearly</li>
        <li>Send regular payment reminders</li>
        <li>Review overdue accounts weekly</li>
    </ul>

    <h3>Purchase Management</h3>
    <ul>
        <li>Verify goods received before marking as complete</li>
        <li>Check quantities and quality</li>
        <li>Record accurate serial numbers</li>
        <li>Match supplier invoices to purchase orders</li>
        <li>Pay suppliers on time for good relationships</li>
    </ul>

    <div class="tip">
        <strong>💡 Pro Tip:</strong> Use keyboard shortcuts in the POS screen for faster processing:
        <ul>
            <li>F1: New Sale</li>
            <li>F2: Search Product</li>
            <li>F3: Search Customer</li>
            <li>F4: Payment</li>
        </ul>
    </div>
</div>
