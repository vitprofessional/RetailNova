<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleProduct;
use App\Models\PurchaseProduct;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\ExpenseEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Business Overview Report
     */
    public function businessReport(Request $request)
    {
        $filters=$request->validate([
            'period'=>['nullable','in:today,week,month,custom'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
        ]);
        $period=$filters['period']??((isset($filters['start_date'])||isset($filters['end_date']))?'custom':'month');
        $today=Carbon::today();
        [$startDate,$endDate]=match($period) {
            'today'=>[$today->toDateString(),$today->toDateString()],
            'week'=>[$today->copy()->subDays(6)->toDateString(),$today->toDateString()],
            'custom'=>[$filters['start_date']??$today->copy()->startOfMonth()->toDateString(),$filters['end_date']??$today->toDateString()],
            default=>[$today->copy()->startOfMonth()->toDateString(),$today->toDateString()],
        };

        // Sales Summary (use SaleProduct totalAmount)
        $salesQuery = SaleProduct::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        $totalSales = (clone $salesQuery)->sum('totalSale');
        $totalSalesCount = (clone $salesQuery)->count();
        
        // Purchase Summary (use PurchaseProduct grandTotal)
        $purchaseQuery = PurchaseProduct::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
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

        // Dashboard chart and top products are real, tenant-scoped sales from
        // the trailing seven days, independent of the overview period selector.
        $weekStart=$today->copy()->subDays(6)->toDateString();
        $weekEnd=$today->toDateString();
        $dailyTotals=SaleProduct::query()->whereBetween('date',[$weekStart,$weekEnd])
            ->select('date')->selectRaw('SUM(grandTotal) AS total')->groupBy('date')->pluck('total','date');
        $weeklySales=collect();
        for($day=$today->copy()->subDays(6);$day->lte($today);$day->addDay()) {
            $date=$day->toDateString();
            $weeklySales->push(['date'=>$date,'label'=>$day->copy()->locale(app()->getLocale())->isoFormat('ddd'),
                'total'=>(float)($dailyTotals[$date]??0)]);
        }
        $weeklySaleIds=SaleProduct::query()->whereBetween('date',[$weekStart,$weekEnd])->pluck('id');
        $topProducts=InvoiceItem::query()->whereIn('saleId',$weeklySaleIds)->with('purchase.product')
            ->get()->filter(fn($item)=>$item->purchase?->product)
            ->groupBy(fn($item)=>(int)$item->purchase->product->id)
            ->map(fn($items)=>['name'=>$items->first()->purchase->product->name,'quantity'=>(int)$items->sum(fn($item)=>(int)$item->qty)])
            ->sortByDesc('quantity')->take(5)->values();
        $totalExpenses=(float)ExpenseEntry::query()->whereDate('expense_date','>=',$startDate)
            ->whereDate('expense_date','<=',$endDate)->sum('amount');
        $newDue=(float)SaleProduct::query()->whereDate('date','>=',$startDate)
            ->whereDate('date','<=',$endDate)->sum('curDue');
        $periodRanges=[];
        foreach(['today','week','month'] as $key) {
            [$from,$to]=match($key) {
                'today'=>[$today->toDateString(),$today->toDateString()],
                'week'=>[$today->copy()->subDays(6)->toDateString(),$today->toDateString()],
                default=>[$today->copy()->startOfMonth()->toDateString(),$today->toDateString()],
            };
            $periodRanges[$key]=['start_date'=>$from,'end_date'=>$to,'period'=>$key];
        }
        
        // Monthly trend (last 12 months)
        // SUBSTR is supported by the configured MySQL/MariaDB and SQLite
        // drivers, unlike YEAR()/MONTH(), while retaining database-side grouping.
        $monthlySales = SaleProduct::select(
                DB::raw('CAST(SUBSTR(date, 1, 4) AS INTEGER) as year'),
                DB::raw('CAST(SUBSTR(date, 6, 2) AS INTEGER) as month'),
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
            'monthlySales', 'period', 'periodRanges', 'weeklySales', 'topProducts',
            'totalExpenses', 'newDue'
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

        $rows = PurchaseProduct::query()
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_products.supplier')
            ->when($supplierId, function ($q) use ($supplierId) {
                $q->where('purchase_products.supplier', $supplierId);
            })
            ->where(function ($q) use ($startDateTime, $endDateTime, $startDate, $endDate) {
                $q->whereBetween('purchase_products.created_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('purchase_products.purchase_date', [$startDate, $endDate]);
            })
            ->select(
                'purchase_products.id',
                'purchase_products.invoice',
                'purchase_products.purchase_date',
                'purchase_products.created_at',
                'purchase_products.supplier',
                'purchase_products.totalAmount',
                'purchase_products.disAmount',
                'purchase_products.grandTotal',
                'purchase_products.paidAmount',
                'purchase_products.dueAmount',
                'suppliers.name as supplier_name'
            )
            ->orderByDesc('purchase_products.created_at')
            ->get();

        $transactions = $rows->groupBy(function ($row) {
            if (!empty($row->invoice)) {
                return 'invoice:' . $row->invoice . '|supplier:' . $row->supplier;
            }

            return 'row:' . $row->id;
        })->map(function ($items) {
            $first = $items->first();
            $latest = $items->sortByDesc(function ($item) {
                return strtotime((string) ($item->purchase_date ?: $item->created_at));
            })->first();

            $invoiceNo = !empty($first->invoice) ? $first->invoice : 'ROW-' . $first->id;
            $purchaseDate = $latest->purchase_date ?: $latest->created_at;
            $isInvoiceGroup = !empty($first->invoice);

            return (object) [
                'invoice_no' => $invoiceNo,
                'purchase_date' => $purchaseDate,
                'sort_date' => $latest->created_at,
                'supplier_id' => $first->supplier,
                'supplier_name' => $first->supplier_name ?: 'Unknown Supplier',
                'line_items' => $items->count(),
                'sub_total' => $isInvoiceGroup
                    ? (float) $items->sum(fn ($item) => (float) ($item->totalAmount ?? 0))
                    : (float) ($first->totalAmount ?? 0),
                'discount_total' => (float) ($first->disAmount ?? 0),
                'grand_total' => (float) ($first->grandTotal ?? 0),
                'paid_total' => (float) ($first->paidAmount ?? 0),
                'due_total' => (float) ($first->dueAmount ?? 0),
            ];
        })->values();

        $totals = [
            'sub_total' => (float) $transactions->sum('sub_total'),
            'discount' => (float) $transactions->sum('discount_total'),
            'grand_total' => (float) $transactions->sum('grand_total'),
            'paid_total' => (float) $transactions->sum('paid_total'),
            'due_total' => (float) $transactions->sum('due_total'),
            'transactions' => (int) $transactions->count(),
        ];

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $currentItems = $transactions->slice(($page - 1) * $perPage, $perPage)->values();
        $purchases = new LengthAwarePaginator(
            $currentItems,
            $transactions->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $totalPurchases = (float) $totals['grand_total'];
        $suppliers = Supplier::orderBy('name')->get();

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
            // Joined rows bypass model scopes: exclude cancelled (soft-deleted) sales explicitly.
            ->leftJoin('sale_products', function ($join) {
                $join->on('customers.id', '=', 'sale_products.customerId')
                    ->whereNull('sale_products.deleted_at');
            })
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
