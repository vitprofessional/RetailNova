<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SaleProduct;
use App\Models\PurchaseProduct;
use App\Models\AccountTransaction;
use App\Models\ExpenseEntry;
use App\Models\SaleReturn;
use App\Models\Product;
use App\Models\ProductStock;

class dashboardController extends Controller
{
    public function dashboard()
    {
        $customerOpeningTotal = (int) Customer::sum('openingBalance');
        $supplierOpeningTotal = (int) Supplier::sum('openingBalance');
        $admin = Auth::guard('admin')->user();

        return view('dashboard', [
            'customerOpeningTotal' => $customerOpeningTotal,
            'supplierOpeningTotal' => $supplierOpeningTotal,
            'adminUser' => $admin,
        ]);
    }

    public function metrics(Request $request)
    {
        $range = $request->query('range', 'month'); // today|week|month|year|custom
        $today = Carbon::today();
        switch ($range) {
            case 'today':
                $start = $today->copy()->startOfDay();
                $end = $today->copy()->endOfDay();
                break;
            case 'week':
                $start = $today->copy()->startOfWeek();
                $end = $today->copy()->endOfWeek();
                break;
            case 'year':
                $start = $today->copy()->startOfYear();
                $end = $today->copy()->endOfYear();
                break;
            case 'custom':
                $start = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : $today->copy()->startOfMonth();
                $end = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : $today->copy()->endOfMonth();
                break;
            case 'month':
            default:
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
        }

        $sales = (float) SaleProduct::whereBetween('date', [$start->toDateString(), $end->toDateString()])->sum('grandTotal');
        $purchases = (float) PurchaseProduct::whereBetween('purchase_date', [$start, $end])->sum('grandTotal');
        $receipts = (float) AccountTransaction::ofType(AccountTransaction::TYPE_RECEIPT)->dateRange($start, $end)->sum('amount');
        $payments = (float) AccountTransaction::ofType(AccountTransaction::TYPE_PAYMENT)->dateRange($start, $end)->sum('amount');
        $expenses = (float) ExpenseEntry::dateRange($start, $end)->sum('amount');
        $saleReturns = (float) SaleReturn::whereBetween('created_at', [$start, $end])->sum('totalReturnAmount');
        $netSales = $sales - $saleReturns;
        $cashFlow = $receipts - $payments - $expenses;

        $latest = SaleProduct::with('customer')
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(function ($s) {
                return [
                    'invoiceNo' => $s->invoice,
                    'customer' => optional($s->customer)->name ?? '—',
                    'total' => (float) ($s->grandTotal ?? 0),
                    'date' => $s->date ? (string) $s->date : null,
                ];
            })->values();

        $totalStockQty = (int) ProductStock::sum('currentStock');
        $lowStock = Product::with('stocks')
            ->get()
            ->map(function ($p) {
                $stock = (int) ($p->stocks->sum('currentStock') ?? 0);
                $alert = (int) ($p->quantity ?? 0);
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock' => $stock,
                    'alert' => $alert,
                    'is_low' => $stock > 0 && $alert > 0 && $stock < $alert,
                ];
            })
            ->filter(function ($p) {
                return $p['is_low'];
            })
            ->sortBy('stock')
            ->take(5)
            ->values();

        $labels = [];
        $seriesSales = [];
        $seriesPurchases = [];
        $seriesExpenses = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $labels[] = $d->format('Y-m-d');
            $dayStart = $d->copy()->startOfDay();
            $dayEnd = $d->copy()->endOfDay();
            $seriesSales[] = (float) SaleProduct::where('date', $d->toDateString())->sum('grandTotal');
            $seriesPurchases[] = (float) PurchaseProduct::whereBetween('purchase_date', [$dayStart, $dayEnd])->sum('grandTotal');
            $seriesExpenses[] = (float) ExpenseEntry::whereBetween('expense_date', [$dayStart, $dayEnd])->sum('amount');
        }

        return response()->json([
            'totals' => [
                'sales' => $sales,
                'purchases' => $purchases,
                'receipts' => $receipts,
                'payments' => $payments,
                'expenses' => $expenses,
                'cash_flow' => $cashFlow,
                'net_sales' => $netSales,
            ],
            'latest_invoices' => $latest,
            'stock' => [
                'total_quantity' => $totalStockQty,
                'low_stock' => $lowStock,
            ],
            'chart' => [
                'labels' => $labels,
                'series' => [
                    'sales' => $seriesSales,
                    'purchases' => $seriesPurchases,
                    'expenses' => $seriesExpenses,
                ],
            ],
        ]);
    }
}
