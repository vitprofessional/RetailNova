<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Alert;

class AccountManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $actor = \Illuminate\Support\Facades\Auth::guard('admin')->user();
            if ($actor && in_array(strtolower($actor->role), ['storemanager'])) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }
    /**
     * Display chart of accounts
     */
    public function chartOfAccounts()
    {
        $accounts = Account::with('parentAccount', 'businessLocation')
            ->orderBy('account_code')
            ->get();
        
        return view('account.chart-of-accounts', compact('accounts'));
    }

    /**
     * Show form to create new account
     */
    public function createAccount()
    {
        $accountTypes = Account::getAccountTypes();
        $parentAccounts = Account::active()->orderBy('account_name')->get();
        $businessLocations = BusinessLocation::all();
        
        return view('account.create-account', compact('accountTypes', 'parentAccounts', 'businessLocations'));
    }

    /**
     * Store a new account
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required|unique:accounts,account_code',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'business_location_id' => 'nullable|exists:business_locations,id',
        ]);

        try {
            $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
            $validated['current_balance'] = $validated['opening_balance'];
            
            $account = Account::create($validated);

            Alert::success('Success!', 'Account created successfully');
            return redirect()->route('account.chart');
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Account creation failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show form to edit account
     */
    public function editAccount($id)
    {
        $account = Account::findOrFail($id);
        $accountTypes = Account::getAccountTypes();
        $parentAccounts = Account::active()->where('id', '!=', $id)->orderBy('account_name')->get();
        $businessLocations = BusinessLocation::all();
        
        return view('account.edit-account', compact('account', 'accountTypes', 'parentAccounts', 'businessLocations'));
    }

    /**
     * Update account
     */
    public function updateAccount(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'account_code' => 'required|unique:accounts,account_code,' . $id,
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'business_location_id' => 'nullable|exists:business_locations,id',
        ]);

        try {
            $account->update($validated);

            Alert::success('Success!', 'Account updated successfully');
            return redirect()->route('account.chart');
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Account update failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Delete account
     */
    public function deleteAccount($id)
    {
        try {
            $account = Account::findOrFail($id);
            
            // Check if account has transactions
            if ($account->transactions()->count() > 0) {
                Alert::warning('Warning!', 'Cannot delete account with existing transactions');
                return back();
            }

            $account->delete();

            Alert::success('Success!', 'Account deleted successfully');
            return redirect()->route('account.chart');
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Account deletion failed: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Display transaction list
     */
    public function transactionList(Request $request)
    {
        $query = AccountTransaction::with(['debitAccount', 'creditAccount', 'creator', 'businessLocation'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('account_id')) {
            $query->where(function($q) use ($request) {
                $q->where('debit_account_id', $request->account_id)
                  ->orWhere('credit_account_id', $request->account_id);
            });
        }

        $transactions = $query->paginate(50);
        $accounts = Account::active()->orderBy('account_name')->get();
        $transactionTypes = AccountTransaction::getTransactionTypes();

        return view('account.transactions-list', compact('transactions', 'accounts', 'transactionTypes'));
    }

    /**
     * Show form to create journal entry
     */
    public function createTransaction()
    {
        $accounts = Account::active()->orderBy('account_name')->get();
        $transactionTypes = AccountTransaction::getTransactionTypes();
        $businessLocations = BusinessLocation::all();
        
        return view('account.create-transaction', compact('accounts', 'transactionTypes', 'businessLocations'));
    }

    /**
     * Store a new transaction
     */
    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:journal,payment,receipt,expense,sale,purchase,transfer',
            'reference_no' => 'required|unique:account_transactions,reference_no',
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id|different:debit_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'business_location_id' => 'nullable|exists:business_locations,id',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $userType = 'user';
            
            if (!$userId) {
                $userId = Auth::guard('admin')->id();
                $userType = 'admin';
            }
            
            if (!$userId) {
                throw new \RuntimeException('No authenticated user found for creating transaction');
            }
            
            $validated['created_by'] = $userId;
            $validated['created_by_type'] = $userType;
            
            // Create transaction
            $transaction = AccountTransaction::create($validated);

            // Update account balances
            $debitAccount = Account::find($validated['debit_account_id']);
            $creditAccount = Account::find($validated['credit_account_id']);

            // Debit increases: Assets, Expenses
            // Debit decreases: Liabilities, Equity, Revenue
            if (in_array($debitAccount->account_type, ['asset', 'expense'])) {
                $debitAccount->current_balance += $validated['amount'];
            } else {
                $debitAccount->current_balance -= $validated['amount'];
            }

            // Credit increases: Liabilities, Equity, Revenue
            // Credit decreases: Assets, Expenses
            if (in_array($creditAccount->account_type, ['liability', 'equity', 'revenue'])) {
                $creditAccount->current_balance += $validated['amount'];
            } else {
                $creditAccount->current_balance -= $validated['amount'];
            }

            $debitAccount->save();
            $creditAccount->save();

            DB::commit();

            Alert::success('Success!', 'Transaction recorded successfully');
            return redirect()->route('account.transactions');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Failed!', 'Transaction failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show account ledger
     */
    public function accountLedger(Request $request, $id)
    {
        $account = Account::findOrFail($id);
        
        $query = AccountTransaction::where(function($q) use ($id) {
            $q->where('debit_account_id', $id)
              ->orWhere('credit_account_id', $id);
        })->orderBy('transaction_date')->orderBy('created_at');

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        $transactions = $query->get();

        // Calculate running balance
        $runningBalance = $account->opening_balance;
        $ledgerEntries = [];

        foreach ($transactions as $transaction) {
            $entry = [
                'date' => $transaction->transaction_date,
                'reference' => $transaction->reference_no,
                'description' => $transaction->description,
                'debit' => 0,
                'credit' => 0,
            ];

            if ($transaction->debit_account_id == $id) {
                $entry['debit'] = $transaction->amount;
                // Debit increases: Assets, Expenses
                if (in_array($account->account_type, ['asset', 'expense'])) {
                    $runningBalance += $transaction->amount;
                } else {
                    $runningBalance -= $transaction->amount;
                }
            } else {
                $entry['credit'] = $transaction->amount;
                // Credit increases: Liabilities, Equity, Revenue
                if (in_array($account->account_type, ['liability', 'equity', 'revenue'])) {
                    $runningBalance += $transaction->amount;
                } else {
                    $runningBalance -= $transaction->amount;
                }
            }

            $entry['balance'] = $runningBalance;
            $ledgerEntries[] = $entry;
        }

        return view('account.ledger', compact('account', 'ledgerEntries'));
    }

    /**
     * Generate financial reports
     */
    public function financialReports(Request $request)
    {
        $reportType = $request->get('report_type', 'balance_sheet');
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $data = [];

        if ($reportType == 'balance_sheet') {
            $data = $this->generateBalanceSheet($startDate, $endDate);
        } elseif ($reportType == 'income_statement') {
            $data = $this->generateIncomeStatement($startDate, $endDate);
        } elseif ($reportType == 'trial_balance') {
            $data = $this->generateTrialBalance($startDate, $endDate);
        }

        return view('account.financial-reports', compact('reportType', 'startDate', 'endDate', 'data'));
    }

    /**
     * Generate balance sheet
     */
    private function generateBalanceSheet($startDate, $endDate)
    {
        $assets = Account::where('account_type', 'asset')->active()->get();
        $liabilities = Account::where('account_type', 'liability')->active()->get();
        $equity = Account::where('account_type', 'equity')->active()->get();

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets->sum('current_balance'),
            'total_liabilities' => $liabilities->sum('current_balance'),
            'total_equity' => $equity->sum('current_balance'),
        ];
    }

    /**
     * Generate income statement
     */
    private function generateIncomeStatement($startDate, $endDate)
    {
        $revenue = Account::where('account_type', 'revenue')->active()->get();
        $expenses = Account::where('account_type', 'expense')->active()->get();

        $totalRevenue = $revenue->sum('current_balance');
        $totalExpenses = $expenses->sum('current_balance');

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $totalRevenue - $totalExpenses,
        ];
    }

    /**
     * Generate trial balance
     */
    private function generateTrialBalance($startDate, $endDate)
    {
        $accounts = Account::active()->orderBy('account_code')->get();

        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($accounts as $account) {
            if (in_array($account->account_type, ['asset', 'expense'])) {
                $debitTotal += $account->current_balance;
            } else {
                $creditTotal += $account->current_balance;
            }
        }

        return [
            'accounts' => $accounts,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
        ];
    }
}
