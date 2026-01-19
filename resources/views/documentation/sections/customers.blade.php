<div class="section">
    <h1>👥 Customer Management</h1>
    
    <h2>Overview</h2>
    <p>
        The Customer Management module is the heart of your customer relationship management in RetailNova POS. 
        It allows you to maintain detailed customer profiles, track purchase history, manage credit sales, 
        handle payments, and build long-term customer relationships.
    </p>

    <h2>📝 Adding New Customers</h2>
    
    <h3>Single Customer Entry</h3>
    <p>To add a new customer:</p>
    
    <div class="step">
        <span class="step-number">1</span>
        Navigate to <strong>Customers → Add Customer</strong> from the main menu
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Fill in the customer details form with the following information:
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
                <td><strong>Customer Name</strong></td>
                <td>✅ Yes</td>
                <td>Full name of the customer</td>
            </tr>
            <tr>
                <td><strong>Mobile Number</strong></td>
                <td>❌ Optional</td>
                <td>Primary contact number (10-15 digits)</td>
            </tr>
            <tr>
                <td><strong>Email Address</strong></td>
                <td>❌ Optional</td>
                <td>Customer's email for receipts and communications</td>
            </tr>
            <tr>
                <td><strong>Address</strong></td>
                <td>❌ Optional</td>
                <td>Physical address (street, city, postal code)</td>
            </tr>
            <tr>
                <td><strong>Customer Group</strong></td>
                <td>❌ Optional</td>
                <td>Categorize customers (Retail, Wholesale, VIP, etc.)</td>
            </tr>
            <tr>
                <td><strong>Opening Balance</strong></td>
                <td>❌ Optional</td>
                <td>Initial credit/debit balance (if any)</td>
            </tr>
            <tr>
                <td><strong>Credit Limit</strong></td>
                <td>❌ Optional</td>
                <td>Maximum credit amount allowed</td>
            </tr>
            <tr>
                <td><strong>Tax Number</strong></td>
                <td>❌ Optional</td>
                <td>VAT/Tax identification number</td>
            </tr>
        </tbody>
    </table>

    <div class="step">
        <span class="step-number">3</span>
        Click the <strong>"Save Customer"</strong> button to create the customer record
    </div>

    <div class="note">
        <strong>💡 Note:</strong> Only the customer name is required. All other fields are optional, 
        allowing you to quickly add customers during busy periods and complete their information later.
    </div>

    <h3>Bulk Customer Import</h3>
    <p>To add multiple customers at once during a sale:</p>
    <ol>
        <li>Click the <strong>"Add New Customer"</strong> button on the sales page</li>
        <li>The modal supports AJAX submission for quick processing</li>
        <li>Enter customer name (required) and optional details</li>
        <li>Submit the form - the customer is added without page refresh</li>
        <li>The new customer immediately appears in the customer selection dropdown</li>
    </ol>

    <h2>🔍 Finding Customers</h2>
    
    <h3>Search Functions</h3>
    <p>RetailNova provides multiple ways to find customers quickly:</p>
    <ul>
        <li><strong>Quick Search:</strong> Type name, mobile, or email in the search box</li>
        <li><strong>Advanced Filters:</strong> Filter by group, location, balance status</li>
        <li><strong>Alphabetical Browse:</strong> Click letter tabs to view customers by first letter</li>
        <li><strong>Recent Customers:</strong> Quick access to recently added or active customers</li>
    </ul>

    <h2>📊 Customer Details & Profile</h2>
    <p>Clicking on a customer name opens their detailed profile showing:</p>

    <h3>Basic Information</h3>
    <ul>
        <li>Full name and contact details</li>
        <li>Customer ID and joining date</li>
        <li>Customer group and tax information</li>
        <li>Total purchases to date</li>
        <li>Current balance (receivable/payable)</li>
    </ul>

    <h3>Purchase History</h3>
    <p>Complete record of all transactions including:</p>
    <ul>
        <li>Invoice number and date</li>
        <li>Products purchased</li>
        <li>Transaction amount</li>
        <li>Payment status</li>
        <li>Actions (view invoice, process return)</li>
    </ul>

    <h3>Payment History</h3>
    <p>Track all payments made by the customer:</p>
    <ul>
        <li>Payment date and amount</li>
        <li>Payment method (Cash, Card, Transfer, etc.)</li>
        <li>Invoice reference</li>
        <li>Remaining balance after payment</li>
        <li>Receipt printing option</li>
    </ul>

    <h3>Service History</h3>
    <p>If the customer has used your service department:</p>
    <ul>
        <li>Service request details</li>
        <li>Device/product serviced</li>
        <li>Service status and completion date</li>
        <li>Service charges</li>
        <li>Technician notes</li>
    </ul>

    <h2>💳 Managing Customer Credit</h2>
    
    <h3>Setting Credit Limits</h3>
    <p>To enable credit sales for a customer:</p>
    <ol>
        <li>Open the customer profile</li>
        <li>Click <strong>"Edit"</strong></li>
        <li>Set the <strong>Credit Limit</strong> amount</li>
        <li>Save changes</li>
    </ol>

    <div class="warning">
        <strong>⚠️ Important:</strong> The system will prevent sales that exceed a customer's credit limit. 
        Regularly review and adjust credit limits based on payment history and relationship strength.
    </div>

    <h3>Recording Customer Payments</h3>
    <p>To record a payment from a customer:</p>
    
    <div class="step">
        <span class="step-number">1</span>
        Navigate to customer profile or Customers → Receive Payment
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Select the customer from the dropdown
    </div>

    <div class="step">
        <span class="step-number">3</span>
        Enter payment details:
        <ul>
            <li>Payment amount</li>
            <li>Payment date</li>
            <li>Payment method</li>
            <li>Reference number (if applicable)</li>
            <li>Notes/remarks</li>
        </ul>
    </div>

    <div class="step">
        <span class="step-number">4</span>
        Click <strong>"Save Payment"</strong> - the customer balance updates automatically
    </div>

    <div class="step">
        <span class="step-number">5</span>
        Print or email the payment receipt if needed
    </div>

    <h2>✏️ Editing Customer Information</h2>
    <p>To update customer details:</p>
    <ol>
        <li>Find and open the customer profile</li>
        <li>Click the <strong>"Edit"</strong> button</li>
        <li>Modify the necessary fields</li>
        <li>Click <strong>"Update"</strong> to save changes</li>
    </ol>

    <div class="note">
        <strong>📝 Note:</strong> Changes to customer information are logged in the system audit trail 
        for security and tracking purposes.
    </div>

    <h2>🗑️ Deleting Customers</h2>
    <p>To remove a customer from the system:</p>
    <ol>
        <li>Open the customer list</li>
        <li>Locate the customer you wish to delete</li>
        <li>Click the <strong>Delete</strong> button (red trash icon)</li>
        <li>Confirm the deletion when prompted</li>
    </ol>

    <div class="warning">
        <strong>⚠️ Warning:</strong> You cannot delete customers who have:
        <ul>
            <li>Existing transactions (sales, purchases)</li>
            <li>Outstanding balances</li>
            <li>Active service requests</li>
        </ul>
        Settle all accounts before attempting deletion.
    </div>

    <h2>📱 Customer Communication</h2>
    
    <h3>Mobile Number Quick Copy</h3>
    <p>
        When viewing a customer with a mobile number, a <strong>copy button</strong> appears next to the number. 
        Click it to copy the number to your clipboard for quick dialing or messaging.
    </p>
    <p><strong>Note:</strong> The copy button only appears when a mobile number is available.</p>

    <h3>Sending Receipts & Notifications</h3>
    <p>You can send customers:</p>
    <ul>
        <li>Sale invoices via email or SMS</li>
        <li>Payment receipts</li>
        <li>Service status updates</li>
        <li>Promotional offers (if enabled)</li>
        <li>Payment reminders for overdue balances</li>
    </ul>

    <h2>📈 Customer Analytics</h2>
    <p>The system automatically tracks valuable customer metrics:</p>
    <ul>
        <li><strong>Total Purchases:</strong> Lifetime value of the customer</li>
        <li><strong>Average Order Value:</strong> Mean transaction amount</li>
        <li><strong>Purchase Frequency:</strong> How often they buy</li>
        <li><strong>Last Purchase Date:</strong> Recency metric</li>
        <li><strong>Favorite Products:</strong> Most frequently purchased items</li>
        <li><strong>Payment Behavior:</strong> On-time vs. delayed payments</li>
    </ul>

    <h2>👥 Customer Groups</h2>
    <p>Organize customers into groups for better management:</p>
    <ul>
        <li><strong>Retail:</strong> Walk-in customers, one-time buyers</li>
        <li><strong>Wholesale:</strong> Bulk buyers with special pricing</li>
        <li><strong>VIP:</strong> High-value customers with privileges</li>
        <li><strong>Corporate:</strong> Business accounts</li>
        <li><strong>Student/Senior:</strong> Customers eligible for discounts</li>
    </ul>

    <div class="tip">
        <strong>💡 Pro Tip:</strong> Use customer groups to apply different pricing strategies. 
        For example, wholesale customers can automatically receive bulk discounts, while VIP 
        customers might get exclusive offers.
    </div>

    <h2>🎯 Best Practices</h2>
    
    <h3>Data Quality</h3>
    <ul>
        <li>Always collect at least a name and mobile number</li>
        <li>Verify phone numbers for accuracy</li>
        <li>Keep email addresses updated for digital receipts</li>
        <li>Standardize address formats for consistency</li>
        <li>Avoid duplicate entries - search before creating</li>
    </ul>

    <h3>Credit Management</h3>
    <ul>
        <li>Start with conservative credit limits for new customers</li>
        <li>Increase limits based on payment history</li>
        <li>Set up payment reminder schedules</li>
        <li>Review receivables weekly</li>
        <li>Document credit terms clearly</li>
    </ul>

    <h3>Customer Relationships</h3>
    <ul>
        <li>Use purchase history to make personalized recommendations</li>
        <li>Acknowledge and reward loyal customers</li>
        <li>Follow up on service requests promptly</li>
        <li>Send thank you messages after significant purchases</li>
        <li>Request feedback to improve service</li>
    </ul>

    <h2>🔐 Privacy & Data Protection</h2>
    <p>RetailNova takes customer data seriously:</p>
    <ul>
        <li>Customer information is encrypted and secured</li>
        <li>Access is restricted based on user roles</li>
        <li>Audit logs track who accesses customer data</li>
        <li>Comply with local data protection regulations</li>
        <li>Customers can request their data or deletion (contact admin)</li>
    </ul>

    <div class="note">
        <strong>📱 Mobile Features:</strong> The customer management interface is fully responsive. 
        You can add, search, and manage customers from any mobile device, making it easy to update 
        information or look up details while on the go.
    </div>

    <h2>📊 Customer Reports</h2>
    <p>Access detailed customer reports from the Reports module:</p>
    <ul>
        <li><strong>Top Customers:</strong> Best customers by purchase volume or value</li>
        <li><strong>Customer Ledger:</strong> Complete transaction history</li>
        <li><strong>Receivables Report:</strong> Outstanding customer balances</li>
        <li><strong>Customer Activity:</strong> Purchase patterns and trends</li>
        <li><strong>Inactive Customers:</strong> Customers who haven't purchased recently</li>
    </ul>

    <div class="tip">
        <strong>🎓 Training Tip:</strong> Practice adding customers during quiet periods to familiarize 
        yourself with all fields. This ensures smooth, quick customer registration during peak hours.
    </div>
</div>
