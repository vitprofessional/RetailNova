<div class="section">
    <h1>🏭 Supplier Management</h1>
    
    <h2>Overview</h2>
    <p>
        The Supplier Management module helps you maintain relationships with your vendors, track purchases, 
        manage payments, and ensure smooth procurement operations. Effective supplier management is crucial 
        for maintaining optimal inventory levels and controlling costs.
    </p>

    <h2>📝 Adding New Suppliers</h2>
    
    <h3>Creating a Supplier Record</h3>
    <p>To add a new supplier:</p>
    
    <div class="step">
        <span class="step-number">1</span>
        Navigate to <strong>Suppliers → Add Supplier</strong> from the main menu
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Complete the supplier information form:
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
                <td><strong>Supplier Name</strong></td>
                <td>✅ Yes</td>
                <td>Company or individual name</td>
            </tr>
            <tr>
                <td><strong>Contact Person</strong></td>
                <td>❌ Optional</td>
                <td>Primary contact name</td>
            </tr>
            <tr>
                <td><strong>Mobile Number</strong></td>
                <td>❌ Optional</td>
                <td>Primary phone number</td>
            </tr>
            <tr>
                <td><strong>Email Address</strong></td>
                <td>❌ Optional</td>
                <td>Business email</td>
            </tr>
            <tr>
                <td><strong>Company Address</strong></td>
                <td>❌ Optional</td>
                <td>Physical business address</td>
            </tr>
            <tr>
                <td><strong>Tax/VAT Number</strong></td>
                <td>❌ Optional</td>
                <td>Business tax identification</td>
            </tr>
            <tr>
                <td><strong>Opening Balance</strong></td>
                <td>❌ Optional</td>
                <td>Initial payable/receivable amount</td>
            </tr>
            <tr>
                <td><strong>Payment Terms</strong></td>
                <td>❌ Optional</td>
                <td>Credit terms (e.g., Net 30)</td>
            </tr>
            <tr>
                <td><strong>Bank Details</strong></td>
                <td>❌ Optional</td>
                <td>Account info for direct payments</td>
            </tr>
        </tbody>
    </table>

    <div class="step">
        <span class="step-number">3</span>
        Click <strong>"Save Supplier"</strong> to create the record
    </div>

    <div class="note">
        <strong>💡 Note:</strong> Only the supplier name is required, allowing quick supplier addition. 
        You can complete other details later as your relationship develops.
    </div>

    <h2>🔍 Finding Suppliers</h2>
    <p>Locate suppliers quickly using these methods:</p>
    <ul>
        <li><strong>Quick Search:</strong> Search by name, contact person, or mobile</li>
        <li><strong>Filter by Status:</strong> Active, inactive, or all suppliers</li>
        <li><strong>Sort Options:</strong> By name, total purchases, or balance</li>
        <li><strong>Recent Suppliers:</strong> Quick access to recently added or active suppliers</li>
    </ul>

    <h2>📊 Supplier Profile</h2>
    <p>Click on a supplier to view their complete profile:</p>

    <h3>Basic Information</h3>
    <ul>
        <li>Supplier ID and registration date</li>
        <li>Complete contact information</li>
        <li>Tax and payment terms</li>
        <li>Total purchase history</li>
        <li>Current balance (payable)</li>
    </ul>

    <h3>Purchase History</h3>
    <p>Complete record of all purchases from this supplier:</p>
    <ul>
        <li>Purchase order number and date</li>
        <li>Products purchased with quantities</li>
        <li>Purchase amount</li>
        <li>Payment status</li>
        <li>Actions (view details, make payment)</li>
    </ul>

    <h3>Payment History</h3>
    <p>Track all payments made to the supplier:</p>
    <ul>
        <li>Payment date and amount</li>
        <li>Payment method used</li>
        <li>Purchase order reference</li>
        <li>Remaining balance</li>
        <li>Payment receipt</li>
    </ul>

    <h2>💳 Managing Supplier Payments</h2>
    
    <h3>Recording Payments</h3>
    <p>To record a payment to a supplier:</p>
    
    <div class="step">
        <span class="step-number">1</span>
        Go to <strong>Suppliers → Make Payment</strong>
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Select the supplier from the dropdown
    </div>

    <div class="step">
        <span class="step-number">3</span>
        Enter payment details:
        <ul>
            <li>Payment amount</li>
            <li>Payment date</li>
            <li>Payment method (Cash, Bank Transfer, Cheque)</li>
            <li>Account to deduct from</li>
            <li>Reference number</li>
            <li>Notes (optional)</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">4</span>
        Click <strong>"Save Payment"</strong> - balance updates automatically
    </div>

    <div class="tip">
        <strong>💡 Pro Tip:</strong> Use the reference number field to record cheque numbers or 
        bank transaction IDs for easy reconciliation.
    </div>

    <h2>📦 Linking Suppliers to Products</h2>
    <p>Associate products with their suppliers for efficient reordering:</p>
    <ol>
        <li>When creating or editing a product, select the primary supplier</li>
        <li>You can add multiple suppliers for the same product</li>
        <li>Set different purchase prices for different suppliers</li>
        <li>Mark one supplier as "preferred" for automatic ordering</li>
    </ol>

    <h2>✏️ Editing Supplier Information</h2>
    <p>To update supplier details:</p>
    <ol>
        <li>Find and open the supplier profile</li>
        <li>Click the <strong>"Edit"</strong> button</li>
        <li>Modify necessary fields</li>
        <li>Click <strong>"Update"</strong> to save changes</li>
    </ol>

    <h2>🗑️ Deleting Suppliers</h2>
    <p>To remove a supplier:</p>
    <ol>
        <li>Navigate to the supplier list</li>
        <li>Locate the supplier to delete</li>
        <li>Click the <strong>Delete</strong> button</li>
        <li>Confirm deletion</li>
    </ol>

    <div class="warning">
        <strong>⚠️ Warning:</strong> Cannot delete suppliers with:
        <ul>
            <li>Existing purchase orders</li>
            <li>Products linked to them</li>
            <li>Outstanding payments</li>
        </ul>
    </div>

    <h2>📈 Supplier Performance Tracking</h2>
    <p>RetailNova automatically tracks supplier metrics:</p>
    <ul>
        <li><strong>Total Purchases:</strong> Lifetime purchase value</li>
        <li><strong>Average Order Value:</strong> Mean purchase amount</li>
        <li><strong>Order Frequency:</strong> How often you order</li>
        <li><strong>Last Purchase Date:</strong> Most recent transaction</li>
        <li><strong>Product Range:</strong> Number of products supplied</li>
        <li><strong>Payment History:</strong> On-time vs. delayed payments</li>
    </ul>

    <h2>🎯 Best Practices</h2>
    
    <h3>Supplier Relationship Management</h3>
    <ul>
        <li>Maintain accurate contact information</li>
        <li>Document payment terms clearly</li>
        <li>Keep track of delivery lead times</li>
        <li>Rate suppliers on quality, price, and reliability</li>
        <li>Maintain relationships with backup suppliers</li>
    </ul>

    <h3>Payment Management</h3>
    <ul>
        <li>Pay suppliers on time to maintain good relationships</li>
        <li>Negotiate favorable payment terms</li>
        <li>Take advantage of early payment discounts</li>
        <li>Keep accurate payment records</li>
        <li>Reconcile statements regularly</li>
    </ul>

    <h3>Cost Control</h3>
    <ul>
        <li>Compare prices from multiple suppliers</li>
        <li>Negotiate volume discounts</li>
        <li>Review supplier pricing regularly</li>
        <li>Track price changes over time</li>
        <li>Consider total cost, not just unit price</li>
    </ul>

    <h2>📊 Supplier Reports</h2>
    <p>Access supplier reports from the Reports module:</p>
    <ul>
        <li><strong>Purchase Report:</strong> Detailed purchase history by supplier</li>
        <li><strong>Supplier Ledger:</strong> Complete transaction history</li>
        <li><strong>Payables Report:</strong> Outstanding amounts owed</li>
        <li><strong>Supplier Comparison:</strong> Price and performance analysis</li>
        <li><strong>Purchase Trends:</strong> Ordering patterns and seasonality</li>
    </ul>

    <div class="note">
        <strong>📝 Supplier Evaluation:</strong> Periodically review supplier performance using the 
        built-in reports. Consider factors like product quality, delivery reliability, pricing 
        competitiveness, and customer service when deciding which suppliers to continue working with.
    </div>
</div>
