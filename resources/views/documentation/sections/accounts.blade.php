<div class="section">
    <h1>💰 Accounts & Expense Management</h1>
    
    <h2>Overview</h2>
    <p>
        The Accounts & Expense Management module helps you track all financial transactions, manage multiple 
        payment accounts, record business expenses, and maintain accurate financial records for better 
        business decision-making.
    </p>

    <h2>🏦 Account Management</h2>
    
    <h3>Types of Accounts</h3>
    <p>RetailNova supports multiple account types:</p>
    <ul>
        <li><strong>Cash Account:</strong> Physical cash in register/safe</li>
        <li><strong>Bank Account:</strong> Business bank accounts</li>
        <li><strong>Mobile Money:</strong> Mobile payment accounts (M-Pesa, etc.)</li>
        <li><strong>Card Terminal:</strong> POS card terminals</li>
        <li><strong>Online Payment:</strong> PayPal, Stripe, etc.</li>
    </ul>

    <h3>Adding a New Account</h3>
    
    <div class="step">
        <span class="step-number">1</span>
        Navigate to <strong>Accounts → Add Account</strong>
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Enter account details:
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
                <td><strong>Account Name</strong></td>
                <td>✅ Yes</td>
                <td>Descriptive name (e.g., "Main Cash Register")</td>
            </tr>
            <tr>
                <td><strong>Account Type</strong></td>
                <td>❌ Optional</td>
                <td>Cash, Bank, Mobile Money, Card, Online</td>
            </tr>
            <tr>
                <td><strong>Account Number</strong></td>
                <td>❌ Optional</td>
                <td>Bank account or reference number</td>
            </tr>
            <tr>
                <td><strong>Bank Name</strong></td>
                <td>❌ Optional</td>
                <td>Name of bank (if applicable)</td>
            </tr>
            <tr>
                <td><strong>Branch</strong></td>
                <td>❌ Optional</td>
                <td>Bank branch information</td>
            </tr>
            <tr>
                <td><strong>Opening Balance</strong></td>
                <td>❌ Optional</td>
                <td>Initial balance when adding account</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>❌ Optional</td>
                <td>Active or Inactive</td>
            </tr>
        </tbody>
    </table>

    <div class="step">
        <span class="step-number">3</span>
        Click <strong>"Save Account"</strong>
    </div>

    <h3>Account Dashboard</h3>
    <p>View all accounts with:</p>
    <ul>
        <li>Account name and type</li>
        <li>Current balance</li>
        <li>Recent transactions</li>
        <li>Quick actions (Deposit, Withdraw, Transfer)</li>
    </ul>

    <h2>💸 Account Transactions</h2>
    
    <h3>Deposits (Money In)</h3>
    <p>Record money coming into an account:</p>
    <ol>
        <li>Select the account</li>
        <li>Click <strong>"Deposit"</strong></li>
        <li>Enter amount</li>
        <li>Select source:
            <ul>
                <li>Sales revenue (auto-recorded)</li>
                <li>Customer payment</li>
                <li>Capital injection</li>
                <li>Loan received</li>
                <li>Other income</li>
            </ul>
        </li>
        <li>Add description/reference</li>
        <li>Save transaction</li>
    </ol>

    <h3>Withdrawals (Money Out)</h3>
    <p>Record money leaving an account:</p>
    <ol>
        <li>Select the account</li>
        <li>Click <strong>"Withdraw"</strong></li>
        <li>Enter amount</li>
        <li>Select purpose:
            <ul>
                <li>Expense payment (links to expense)</li>
                <li>Supplier payment</li>
                <li>Owner withdrawal</li>
                <li>Loan repayment</li>
                <li>Other</li>
            </ul>
        </li>
        <li>Add description</li>
        <li>Save transaction</li>
    </ol>

    <h3>Account Transfers</h3>
    <p>Move money between accounts:</p>
    <ol>
        <li>Go to <strong>Accounts → Transfer</strong></li>
        <li>Select source account (from)</li>
        <li>Select destination account (to)</li>
        <li>Enter transfer amount</li>
        <li>Add transfer note</li>
        <li>Save - both accounts update automatically</li>
    </ol>

    <div class="note">
        <strong>💡 Note:</strong> Transfers maintain balance accuracy by automatically debiting 
        one account and crediting the other.
    </div>

    <h2>📋 Expense Management</h2>
    
    <h3>Creating Expense Categories</h3>
    <p>First, set up expense categories:</p>
    <ol>
        <li>Go to <strong>Settings → Expense Categories</strong></li>
        <li>Click <strong>"Add Category"</strong></li>
        <li>Enter category name (e.g., Rent, Utilities, Salaries)</li>
        <li>Add description</li>
        <li>Save category</li>
    </ol>

    <p><strong>Common Expense Categories:</strong></p>
    <ul>
        <li>Rent & Utilities</li>
        <li>Salaries & Wages</li>
        <li>Marketing & Advertising</li>
        <li>Office Supplies</li>
        <li>Maintenance & Repairs</li>
        <li>Transportation</li>
        <li>Insurance</li>
        <li>Professional Fees</li>
        <li>Taxes</li>
        <li>Miscellaneous</li>
    </ul>

    <h3>Recording Expenses</h3>
    
    <div class="step">
        <span class="step-number">1</span>
        Navigate to <strong>Expenses → Add Expense</strong>
    </div>

    <div class="step">
        <span class="step-number">2</span>
        Enter expense details:
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
                <td><strong>Expense Category</strong></td>
                <td>❌ Optional</td>
                <td>Select from predefined categories</td>
            </tr>
            <tr>
                <td><strong>Amount</strong></td>
                <td>❌ Optional</td>
                <td>Expense amount</td>
            </tr>
            <tr>
                <td><strong>Date</strong></td>
                <td>❌ Optional</td>
                <td>When expense was incurred</td>
            </tr>
            <tr>
                <td><strong>Payment Account</strong></td>
                <td>❌ Optional</td>
                <td>Which account paid the expense</td>
            </tr>
            <tr>
                <td><strong>Payment Method</strong></td>
                <td>❌ Optional</td>
                <td>Cash, Bank Transfer, Card, etc.</td>
            </tr>
            <tr>
                <td><strong>Reference/Invoice</strong></td>
                <td>❌ Optional</td>
                <td>Receipt or invoice number</td>
            </tr>
            <tr>
                <td><strong>Description</strong></td>
                <td>❌ Optional</td>
                <td>Detailed expense description</td>
            </tr>
            <tr>
                <td><strong>Attachment</strong></td>
                <td>❌ Optional</td>
                <td>Upload receipt/invoice image</td>
            </tr>
        </tbody>
    </table>

    <div class="step">
        <span class="step-number">3</span>
        Click <strong>"Save Expense"</strong> - account balance adjusts automatically
    </div>

    <h3>Viewing Expenses</h3>
    <p>Access expense history from <strong>Expenses → View Expenses</strong></p>
    <ul>
        <li>Filter by category</li>
        <li>Filter by date range</li>
        <li>Filter by payment account</li>
        <li>Search by description</li>
        <li>Export to Excel/PDF</li>
    </ul>

    <h2>📊 Account Reports</h2>
    
    <h3>Account Statement</h3>
    <p>Detailed transaction history for each account:</p>
    <ul>
        <li>Opening balance</li>
        <li>All deposits (credits)</li>
        <li>All withdrawals (debits)</li>
        <li>Running balance</li>
        <li>Closing balance</li>
    </ul>

    <h3>Expense Report</h3>
    <p>Comprehensive expense analysis:</p>
    <ul>
        <li>Expenses by category</li>
        <li>Monthly expense trends</li>
        <li>Year-over-year comparison</li>
        <li>Top expense categories</li>
        <li>Budget vs. actual (if budgets set)</li>
    </ul>

    <h3>Cash Flow Report</h3>
    <p>Track money movement:</p>
    <ul>
        <li>Total cash inflow (sales, payments, deposits)</li>
        <li>Total cash outflow (expenses, purchases, withdrawals)</li>
        <li>Net cash flow</li>
        <li>Broken down by account</li>
        <li>Broken down by period</li>
    </ul>

    <h2>🔄 Reconciliation</h2>
    
    <h3>Bank Reconciliation</h3>
    <p>Match your records with bank statements:</p>
    <ol>
        <li>Go to <strong>Accounts → Reconcile</strong></li>
        <li>Select account and date range</li>
        <li>Enter bank statement ending balance</li>
        <li>Mark transactions as cleared/reconciled</li>
        <li>System calculates any discrepancies</li>
        <li>Investigate and correct differences</li>
    </ol>

    <h3>Daily Cash Count</h3>
    <p>Verify physical cash matches system records:</p>
    <ol>
        <li>Count actual cash in register</li>
        <li>Compare to system cash account balance</li>
        <li>Record any differences</li>
        <li>Investigate shortages/overages</li>
        <li>Adjust if necessary (with supervisor approval)</li>
    </ol>

    <h2>✏️ Editing & Deleting</h2>
    
    <h3>Editing Transactions</h3>
    <ul>
        <li>Find the transaction</li>
        <li>Click <strong>"Edit"</strong></li>
        <li>Modify necessary fields</li>
        <li>Save changes</li>
        <li>System recalculates balances</li>
    </ul>

    <div class="warning">
        <strong>⚠️ Warning:</strong> Editing past transactions affects current balances. 
        Use cautiously and document reasons for changes.
    </div>

    <h3>Deleting Transactions</h3>
    <ul>
        <li>Only users with proper permissions can delete</li>
        <li>Cannot delete if referenced in other records</li>
        <li>Deletion is logged in audit trail</li>
        <li>Balances recalculate automatically</li>
    </ul>

    <h2>🎯 Best Practices</h2>
    
    <h3>Account Management</h3>
    <ul>
        <li>Keep separate accounts for different purposes</li>
        <li>Reconcile bank accounts monthly</li>
        <li>Perform daily cash counts</li>
        <li>Investigate discrepancies immediately</li>
        <li>Maintain minimum balances</li>
        <li>Monitor account activity regularly</li>
    </ul>

    <h3>Expense Tracking</h3>
    <ul>
        <li>Record expenses immediately</li>
        <li>Always attach receipts/invoices</li>
        <li>Use consistent categorization</li>
        <li>Review expenses weekly</li>
        <li>Set and monitor expense budgets</li>
        <li>Look for cost-saving opportunities</li>
    </ul>

    <h3>Financial Controls</h3>
    <ul>
        <li>Limit who can add/edit transactions</li>
        <li>Require approval for large expenses</li>
        <li>Separate duties (who records vs. who approves)</li>
        <li>Regular audit trail reviews</li>
        <li>Backup financial data regularly</li>
    </ul>

    <div class="tip">
        <strong>💡 Pro Tip:</strong> Set up automatic expense categories for recurring expenses 
        like rent and utilities. This saves time and ensures consistency in your records.
    </div>

    <div class="note">
        <strong>📝 Tax Compliance:</strong> Keep detailed expense records with receipts. This 
        documentation is essential for tax deductions and audits. Consult with your accountant 
        about proper expense categorization for tax purposes.
    </div>

    <h2>📱 Mobile Access</h2>
    <p>Record expenses on the go:</p>
    <ul>
        <li>Add expenses from mobile device</li>
        <li>Snap photos of receipts</li>
        <li>Upload directly to expense records</li>
        <li>Check account balances anytime</li>
        <li>Approve pending expenses remotely</li>
    </ul>
</div>
