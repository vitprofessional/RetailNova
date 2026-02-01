<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Alert;

class ExpenseManagementController extends Controller
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
     * Display expense categories
     */
    public function expenseCategories()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('expense.categories', compact('categories'));
    }

    /**
     * Show form to create expense category
     */
    public function createCategory()
    {
        return view('expense.create-category');
    }

    /**
     * Store expense category
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string',
        ]);

        try {
            ExpenseCategory::create($validated);

            Alert::success('Success!', 'Expense category created successfully');
            return redirect()->route('expense.categories');
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Category creation failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show form to edit expense category
     */
    public function editCategory($id)
    {
        $category = ExpenseCategory::findOrFail($id);
        return view('expense.edit-category', compact('category'));
    }

    /**
     * Update expense category
     */
    public function updateCategory(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $category->update($validated);

            Alert::success('Success!', 'Expense category updated successfully');
            return redirect()->route('expense.categories');
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Category update failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Delete expense category
     */
    public function deleteCategory($id)
    {
        try {
            $category = ExpenseCategory::findOrFail($id);
            
            if ($category->expenses()->count() > 0) {
                Alert::warning('Warning!', 'Cannot delete category with existing expenses');
                return back();
            }

            $category->delete();

            Alert::success('Success!', 'Expense category deleted successfully');
            return redirect()->route('expense.categories');
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Category deletion failed: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Display expense entries
     */
    public function expenseList(Request $request)
    {
        $query = ExpenseEntry::with(['category', 'creator', 'businessLocation'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $expenses = $query->paginate(50);
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $paymentMethods = ExpenseEntry::getPaymentMethods();
        $totalExpense = $query->sum('amount');

        return view('expense.list', compact('expenses', 'categories', 'paymentMethods', 'totalExpense'));
    }

    /**
     * Show form to create expense
     */
    public function createExpense()
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $paymentMethods = ExpenseEntry::getPaymentMethods();
        $businessLocations = BusinessLocation::all();
        $expenseAccounts = Account::where('account_type', 'expense')->active()->orderBy('account_name')->get();
        $paymentAccounts = Account::whereIn('account_type', ['asset', 'liability'])->active()->orderBy('account_name')->get();
        
        return view('expense.create', compact('categories', 'paymentMethods', 'businessLocations', 'expenseAccounts', 'paymentAccounts'));
    }

    /**
     * Store expense entry
     */
    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank,card,cheque,mobile',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'business_location_id' => 'nullable|exists:business_locations,id',
            'expense_account_id' => 'nullable|exists:accounts,id',
            'payment_account_id' => 'nullable|exists:accounts,id',
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
                throw new \RuntimeException('No authenticated user found for creating expense');
            }

            $validated['created_by'] = $userId;
            $validated['created_by_type'] = $userType;

            // Handle file upload
            if ($request->hasFile('receipt_file')) {
                $file = $request->file('receipt_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('expense_receipts', $filename, 'public');
                $validated['receipt_file'] = $path;
            }

            // Create accounting transaction if accounts are specified
            if ($request->filled('expense_account_id') && $request->filled('payment_account_id')) {
                $transaction = AccountTransaction::create([
                    'transaction_date' => $validated['expense_date'],
                    'transaction_type' => 'expense',
                    'reference_no' => 'EXP-' . time() . '-' . rand(1000, 9999),
                    'debit_account_id' => $validated['expense_account_id'],
                    'credit_account_id' => $validated['payment_account_id'],
                    'amount' => $validated['amount'],
                    'description' => $validated['description'] ?? 'Expense payment',
                    'created_by' => $userId,
                    'created_by_type' => $userType,
                    'business_location_id' => $validated['business_location_id'] ?? null,
                ]);

                // Update account balances
                $expenseAccount = Account::find($validated['expense_account_id']);
                $paymentAccount = Account::find($validated['payment_account_id']);

                $expenseAccount->current_balance += $validated['amount'];
                $paymentAccount->current_balance -= $validated['amount'];

                $expenseAccount->save();
                $paymentAccount->save();

                $validated['account_transaction_id'] = $transaction->id;
            }

            // Create expense entry
            unset($validated['expense_account_id'], $validated['payment_account_id']);
            $validated['created_by'] = $userId;
            $expense = ExpenseEntry::create($validated);

            DB::commit();

            Alert::success('Success!', 'Expense recorded successfully');
            return redirect()->route('expense.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Failed!', 'Expense recording failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show form to edit expense
     */
    public function editExpense($id)
    {
        $expense = ExpenseEntry::with('accountTransaction')->findOrFail($id);
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $paymentMethods = ExpenseEntry::getPaymentMethods();
        $businessLocations = BusinessLocation::all();
        $expenseAccounts = Account::where('account_type', 'expense')->active()->orderBy('account_name')->get();
        $paymentAccounts = Account::whereIn('account_type', ['asset', 'liability'])->active()->orderBy('account_name')->get();
        
        return view('expense.edit', compact('expense', 'categories', 'paymentMethods', 'businessLocations', 'expenseAccounts', 'paymentAccounts'));
    }

    /**
     * Update expense entry
     */
    public function updateExpense(Request $request, $id)
    {
        $expense = ExpenseEntry::findOrFail($id);

        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank,card,cheque,mobile',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'business_location_id' => 'nullable|exists:business_locations,id',
        ]);

        DB::beginTransaction();
        try {
            // Handle file upload
            if ($request->hasFile('receipt_file')) {
                // Delete old file if exists
                if ($expense->receipt_file) {
                    Storage::disk('public')->delete($expense->receipt_file);
                }

                $file = $request->file('receipt_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('expense_receipts', $filename, 'public');
                $validated['receipt_file'] = $path;
            }

            // Note: Updating related accounting transactions should be done carefully
            // For simplicity, we're not updating the accounting transaction here
            // In production, you might want to reverse the old transaction and create a new one

            $expense->update($validated);

            DB::commit();

            Alert::success('Success!', 'Expense updated successfully');
            return redirect()->route('expense.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Failed!', 'Expense update failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Delete expense entry
     */
    public function deleteExpense($id)
    {
        DB::beginTransaction();
        try {
            $expense = ExpenseEntry::findOrFail($id);
            
            // Delete related accounting transaction if exists
            if ($expense->account_transaction_id) {
                $transaction = AccountTransaction::find($expense->account_transaction_id);
                if ($transaction) {
                    // Reverse account balances
                    $expenseAccount = Account::find($transaction->debit_account_id);
                    $paymentAccount = Account::find($transaction->credit_account_id);

                    if ($expenseAccount) {
                        $expenseAccount->current_balance -= $transaction->amount;
                        $expenseAccount->save();
                    }

                    if ($paymentAccount) {
                        $paymentAccount->current_balance += $transaction->amount;
                        $paymentAccount->save();
                    }

                    $transaction->delete();
                }
            }

            // Delete receipt file if exists
            if ($expense->receipt_file) {
                Storage::disk('public')->delete($expense->receipt_file);
            }

            $expense->delete();

            DB::commit();

            Alert::success('Success!', 'Expense deleted successfully');
            return redirect()->route('expense.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Failed!', 'Expense deletion failed: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Generate expense reports
     */
    public function expenseReports(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        $groupBy = $request->get('group_by', 'category');

        $query = ExpenseEntry::whereBetween('expense_date', [$startDate, $endDate]);

        if ($groupBy == 'category') {
            $data = $query->select('category_id', DB::raw('SUM(amount) as total'))
                ->groupBy('category_id')
                ->with('category')
                ->get();
        } elseif ($groupBy == 'payment_method') {
            $data = $query->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->get();
        } elseif ($groupBy == 'date') {
            $data = $query->select('expense_date', DB::raw('SUM(amount) as total'))
                ->groupBy('expense_date')
                ->orderBy('expense_date')
                ->get();
        }

        $totalExpense = $query->sum('amount');

        return view('expense.reports', compact('data', 'startDate', 'endDate', 'groupBy', 'totalExpense'));
    }

    /**
     * Get expense statistics for dashboard
     */
    public function expenseStatistics(Request $request)
    {
        $period = $request->get('period', 'month');
        
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $totalExpense = ExpenseEntry::where('expense_date', '>=', $startDate)->sum('amount');
        $expenseCount = ExpenseEntry::where('expense_date', '>=', $startDate)->count();
        
        $categoryWise = ExpenseEntry::where('expense_date', '>=', $startDate)
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return response()->json([
            'total_expense' => $totalExpense,
            'expense_count' => $expenseCount,
            'category_wise' => $categoryWise,
        ]);
    }
}
