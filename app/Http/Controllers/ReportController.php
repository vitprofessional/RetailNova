<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleProduct;
use App\Models\PurchaseProduct;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Business Overview Report
     */
    public function businessReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Sales Summary (use SaleProduct totalAmount)
        $salesQuery = SaleProduct::whereBetween('created_at', [$startDate, $endDate]);
        $totalSales = (clone $salesQuery)->sum('totalSale');
        $totalSalesCount = (clone $salesQuery)->count();
        
        // Purchase Summary (use PurchaseProduct grandTotal)
        $purchaseQuery = PurchaseProduct::whereBetween('created_at', [$startDate, $endDate]);
        $totalPurchases = (clone $purchaseQuery)->sum('grandTotal');
        $totalPurchasesCount = (clone $purchaseQuery)->count();
        
        // Profit Calculation
        $saleIds = (clone $salesQuery)->pluck('id');
        $profit = InvoiceItem::whereIn('saleId', $saleIds)
            ->select(DB::raw('SUM(COALESCE(profitTotal, 0)) as total_profit'))
            ->value('total_profit') ?? 0;
        
        // Customer & Supplier Stats
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::count();
        
        // Product Stats
        $totalProducts = Product::count();
        // Low stock: products with quantity <= 10 (configurable threshold)
        $lowStockProducts = Product::whereNotNull('quantity')
            ->where(DB::raw('CAST(quantity AS SIGNED)'), '<=', 10)
            ->where(DB::raw('CAST(quantity AS SIGNED)'), '>', 0)
            ->count();
        
        // Receivables & Payables
        $totalReceivables = Customer::where('openingBalance', '>', 0)->sum('openingBalance');
        $totalPayables = Supplier::where('openingBalance', '>', 0)->sum('openingBalance');
        
        // Monthly trend (last 12 months)
        $monthlySales = SaleProduct::select(
                DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(grandTotal) as total')
            )
            ->where('date', '>=', Carbon::now()->subMonths(12)->format('Y-m-d'))
            ->whereNotNull('date')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('reports.business-report', compact(
            'startDate', 'endDate', 'totalSales', 'totalSalesCount',
            'totalPurchases', 'totalPurchasesCount', 'profit',
            'totalCustomers', 'totalSuppliers', 'totalProducts',
            'lowStockProducts', 'totalReceivables', 'totalPayables',
            'monthlySales'
        ));
    }

    /**
     * Sale Report
     */
    public function saleReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');

        $query = SaleProduct::with(['customer']);
        
        if ($customerId) {
            $query->where('customerId', $customerId);
        }

        // Try date filtering - handle both 'date' string field and 'created_at' timestamp
        $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate])
              ->orWhereBetween('date', [$startDate, $endDate]);
        });

        $sales = $query->orderBy('created_at', 'desc')->paginate(50);
        
        $totalSales = (clone $query)->sum('totalSale');
        $saleIds = (clone $query)->pluck('id');
        $totalProfit = InvoiceItem::whereIn('saleId', $saleIds)
            ->select(DB::raw('SUM(COALESCE(profitTotal, 0)) as profit'))
            ->value('profit') ?? 0;
        $customers = Customer::orderBy('name')->get();

        return view('reports.sale-report', compact('sales', 'totalSales', 'totalProfit', 'startDate', 'endDate', 'customers', 'customerId'));
    }

    /**
     * Purchase Report
     */
    public function purchaseReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $supplierId = $request->input('supplier_id');

        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        $baseQuery = PurchaseProduct::query()
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_products.supplier');

        if ($supplierId) {
            $baseQuery->where('purchase_products.supplier', $supplierId);
        }

        // Filter by both timestamp and explicit purchase date field.
        $baseQuery->where(function($q) use ($startDateTime, $endDateTime, $startDate, $endDate) {
            $q->whereBetween('purchase_products.created_at', [$startDateTime, $endDateTime])
              ->orWhereBetween('purchase_products.purchase_date', [$startDate, $endDate]);
        });

        // Purchases are saved per product row; aggregate them to one transaction row per invoice.
        $groupedQuery = (clone $baseQuery)
            ->selectRaw("\n                COALESCE(NULLIF(purchase_products.invoice, ''), CONCAT('ROW-', purchase_products.id)) as invoice_no,\n                MAX(purchase_products.purchase_date) as purchase_date,\n                MAX(purchase_products.created_at) as sort_date,\n                purchase_products.supplier as supplier_id,\n                MAX(suppliers.name) as supplier_name,\n                SUM(COALESCE(purchase_products.totalAmount, 0)) as sub_total,\n                MAX(COALESCE(purchase_products.disAmount, 0)) as discount_total,\n                MAX(COALESCE(purchase_products.grandTotal, 0)) as grand_total,\n                MAX(COALESCE(purchase_products.paidAmount, 0)) as paid_total,\n                MAX(COALESCE(purchase_products.dueAmount, 0)) as due_total,\n                COUNT(purchase_products.id) as line_items\n            ")
            ->groupByRaw("COALESCE(NULLIF(purchase_products.invoice, ''), CONCAT('ROW-', purchase_products.id)), purchase_products.supplier");

        $summary = DB::query()
            ->fromSub(clone $groupedQuery, 'purchase_report')
            ->selectRaw('COALESCE(SUM(sub_total), 0) as total_sub_total')
            ->selectRaw('COALESCE(SUM(discount_total), 0) as total_discount_total')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_grand_total')
            ->selectRaw('COALESCE(SUM(paid_total), 0) as total_paid_total')
            ->selectRaw('COALESCE(SUM(due_total), 0) as total_due_total')
            ->selectRaw('COUNT(*) as total_transactions')
            ->first();

        $purchases = DB::query()
            ->fromSub($groupedQuery, 'purchase_report')
            ->orderByDesc('sort_date')
            ->paginate(50);

        $totalPurchases = (float) ($summary->total_grand_total ?? 0);
        $suppliers = Supplier::orderBy('name')->get();
        $totals = [
            'sub_total' => (float) ($summary->total_sub_total ?? 0),
            'discount' => (float) ($summary->total_discount_total ?? 0),
            'grand_total' => $totalPurchases,
            'paid_total' => (float) ($summary->total_paid_total ?? 0),
            'due_total' => (float) ($summary->total_due_total ?? 0),
            'transactions' => (int) ($summary->total_transactions ?? 0),
        ];

        return view('reports.purchase-report', compact('purchases', 'totalPurchases', 'startDate', 'endDate', 'suppliers', 'supplierId', 'totals'));
    }

    /**
     * Top Customers Report
     */
    public function topCustomers(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $limit = $request->input('limit', 20);

        $topCustomers = Customer::select(
                'customers.id',
                'customers.name',
                'customers.mail',
                'customers.mobile',
                'customers.country',
                'customers.state',
                'customers.city',
                'customers.area',
                'customers.openingBalance',
                'customers.created_at',
                'customers.updated_at',
                'customers.deleted_at'
            )
            ->selectRaw('COUNT(sale_products.id) as total_orders')
            ->selectRaw('SUM(sale_products.totalSale) as total_spent')
            ->selectRaw('MAX(sale_products.created_at) as last_purchase_date')
            ->leftJoin('sale_products', 'customers.id', '=', 'sale_products.customerId')
            ->where('customers.deleted_at', null)
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_products.created_at', [$startDate, $endDate])
                  ->orWhereBetween('sale_products.date', [$startDate, $endDate]);
            })
            ->groupBy('customers.id', 'customers.name', 'customers.mail', 'customers.mobile', 
                     'customers.country', 'customers.state', 'customers.city', 'customers.area',
                     'customers.openingBalance', 'customers.created_at', 
                     'customers.updated_at', 'customers.deleted_at')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();

        return view('reports.top-customers', compact('topCustomers', 'startDate', 'endDate', 'limit'));
    }

    /**
     * Payable/Receivable Report
     */
    public function payableReceivable(Request $request)
    {
        // Receivables (from customers)
        $receivables = Customer::select('id', 'name', 'mail', 'mobile', 'openingBalance')
            ->where('openingBalance', '>', 0)
            ->orderBy('openingBalance', 'desc')
            ->get();

        $totalReceivable = $receivables->sum('openingBalance');

        // Payables (to suppliers)
        $payables = Supplier::select('id', 'name', 'mail', 'mobile', 'openingBalance')
            ->where('openingBalance', '>', 0)
            ->orderBy('openingBalance', 'desc')
            ->get();

        $totalPayable = $payables->sum('openingBalance');

        return view('reports.payable-receivable', compact('receivables', 'payables', 'totalReceivable', 'totalPayable'));
    }

    /**
     * Stock & Low Stock Report
     */
    public function stockReport(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $search = $request->input('search');

        $query = Product::with(['brandModel', 'categoryModel', 'unitModel']);

        if ($filter === 'low') {
            // Low stock: quantity between 1 and 10
            $query->whereNotNull('quantity')
                ->where(DB::raw('CAST(quantity AS SIGNED)'), '>', 0)
                ->where(DB::raw('CAST(quantity AS SIGNED)'), '<=', 10);
        } elseif ($filter === 'out') {
            $query->where(function($q) {
                $q->whereNull('quantity')
                  ->orWhere(DB::raw('CAST(quantity AS SIGNED)'), '<=', 0);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barCode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('quantity', 'asc')->paginate(50);
        
        $totalProducts = Product::count();
        // Low stock: quantity between 1 and 10
        $lowStockCount = Product::whereNotNull('quantity')
            ->where(DB::raw('CAST(quantity AS SIGNED)'), '>', 0)
            ->where(DB::raw('CAST(quantity AS SIGNED)'), '<=', 10)
            ->count();
        // Out of stock: quantity 0 or null
        $outOfStockCount = Product::where(function($q) {
            $q->whereNull('quantity')
              ->orWhere(DB::raw('CAST(quantity AS SIGNED)'), '<=', 0);
        })->count();

        return view('reports.stock-report', compact('products', 'filter', 'search', 'totalProducts', 'lowStockCount', 'outOfStockCount'));
    }

    /**
     * Main Reports Dashboard
     */
    public function index()
    {
        return view('reports.index');
    }
}
