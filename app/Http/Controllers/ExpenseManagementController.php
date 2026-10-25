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

            Alert::success(__('common.notification_success'), __('common.notification_expense_category_created_successfully'));
            return redirect()->route('expense.categories');
        } catch (\Exception $e) {
            Alert::error(__('common.notification_failed'), __('common.notification_category_creation_failed') . $e->getMessage());
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

            Alert::success(__('common.notification_success'), __('common.notification_expense_category_updated_successfully'));
            return redirect()->route('expense.categories');
        } catch (\Exception $e) {
            Alert::error(__('common.notification_failed'), __('common.notification_category_update_failed') . $e->getMessage());
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
                Alert::warning(__('common.notification_warning'), __('common.notification_cannot_delete_category_with_existing_expenses'));
                return back();
            }

            $category->delete();

            Alert::success(__('common.notification_success'), __('common.notification_expense_category_deleted_successfully'));
            return redirect()->route('expense.categories');
        } catch (\Exception $e) {
            Alert::error(__('common.notification_failed'), __('common.notification_category_deletion_failed') . $e->getMessage());
            return back();
        }
    }

    /**
     * Display expense entries
     */
    public function expenseList(Request $request)
    {
        $query = ExpenseEntry::with(['category', 'creator', 'adminCreator', 'businessLocation'])
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
        $storedReceipt = null;
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

            $receiptUpload = $request->file('receipt_file');
            unset($validated['receipt_file']);

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
            if ($receiptUpload) {
                $storedReceipt = $this->storePrivateReceipt($receiptUpload, (int) $expense->businessId);
                $expense->receipt_file = $storedReceipt;
                $expense->save();
            }

            DB::commit();

            Alert::success(__('common.notification_success'), __('common.notification_expense_recorded_successfully'));
            return redirect()->route('expense.list');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($storedReceipt) Storage::disk('local')->delete($storedReceipt);
            Alert::error(__('common.notification_failed'), __('common.notification_expense_recording_failed') . $e->getMessage());
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
        $newReceipt = null;
        $oldReceipt = $expense->receipt_file;
        try {
            // Handle file upload
            $receiptUpload = $request->file('receipt_file');
            unset($validated['receipt_file']);
            $newReceipt = $receiptUpload ? $this->storePrivateReceipt($receiptUpload, (int) $expense->businessId) : null;
            if ($newReceipt) $validated['receipt_file'] = $newReceipt;

            // Note: Updating related accounting transactions should be done carefully
            // For simplicity, we're not updating the accounting transaction here
            // In production, you might want to reverse the old transaction and create a new one

            $expense->update($validated);
            DB::commit();
            if ($newReceipt && $oldReceipt) Storage::disk('local')->delete($oldReceipt);

            Alert::success(__('common.notification_success'), __('common.notification_expense_updated_successfully'));
            return redirect()->route('expense.list');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($newReceipt) Storage::disk('local')->delete($newReceipt);
            Alert::error(__('common.notification_failed'), __('common.notification_expense_update_failed') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Delete expense entry
     */
    public function deleteExpense($id)
    {
        DB::beginTransaction();
        $receiptPath = null;
        try {
            $expense = ExpenseEntry::findOrFail($id);
            $receiptPath = $expense->receipt_file;
            
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
            $expense->delete();

            DB::commit();
            if ($receiptPath) Storage::disk('local')->delete($receiptPath);

            Alert::success(__('common.notification_success'), __('common.notification_expense_deleted_successfully'));
            return redirect()->route('expense.list');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error(__('common.notification_failed'), __('common.notification_expense_deletion_failed') . $e->getMessage());
            return back();
        }
    }

    /** Stream only a receipt that belongs to the scoped expense's owning business. */
    public function showReceipt(int $id)
    {
        $expense = ExpenseEntry::query()->findOrFail($id);
        $businessId = (int) $expense->businessId;
        abort_if($businessId < 1 || !preg_match('~^'.preg_quote((string) $businessId, '~').'/receipts/[A-Za-z0-9._-]+$~', (string) $expense->receipt_file), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($expense->receipt_file), 404);
        $mimeType = $disk->mimeType($expense->receipt_file) ?: 'application/octet-stream';
        abort_if(!in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true), 404);
        return response()->file($disk->path($expense->receipt_file), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function storePrivateReceipt(\Illuminate\Http\UploadedFile $file, int $businessId): string
    {
        abort_if($businessId < 1, 403, 'An owning business is required for receipt storage.');
        $extension = strtolower($file->getClientOriginalExtension());
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true) ? $extension : 'bin';
        $directory = $businessId.'/receipts';
        $path = $file->storeAs($directory, \Illuminate\Support\Str::random(40).'.'.$extension, 'local');
        if (!$path) throw new \RuntimeException('The private expense receipt could not be stored.');
        return $path;
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
