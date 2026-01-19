# Reporting System - Technical Reference Card

## Quick API Reference

### ReportController Methods

```php
// GET /reports - Main dashboard
ReportController::index()
  Returns: reports.index view with 6 report cards

// GET /reports/business - Business overview
ReportController::businessReport()
  Params: start_date, end_date
  Returns: Business metrics and trends

// GET /reports/sales - Sales transactions
ReportController::saleReport()
  Params: start_date, end_date, customer_id
  Returns: Paginated sales list (50 per page)

// GET /reports/purchases - Purchase history
ReportController::purchaseReport()
  Params: start_date, end_date, supplier_id
  Returns: Paginated purchase list (50 per page)

// GET /reports/top-customers - Top customers
ReportController::topCustomers()
  Params: start_date, end_date, limit
  Returns: Ranked customer list (limit: 10/20/50/100)

// GET /reports/payable-receivable - Money owed
ReportController::payableReceivable()
  Params: None
  Returns: Receivables + Payables summary

// GET /reports/stock - Inventory status
ReportController::stockReport()
  Params: filter, search
  Returns: Paginated product inventory
```

## Query Patterns Used

### Pattern 1: Sum Aggregation
```php
$total = Model::whereBetween('date', [$start, $end])->sum('field');
```

### Pattern 2: Count & Sum
```php
$count = (clone $query)->count();
$sum = (clone $query)->sum('field');
```

### Pattern 3: Grouped Aggregation
```php
Model::select(
    DB::raw('YEAR(date) as year'),
    DB::raw('MONTH(date) as month'),
    DB::raw('SUM(amount) as total')
)->groupBy('year', 'month')->get();
```

### Pattern 4: Join with Aggregation
```php
Model::select('model.*')
    ->selectRaw('COUNT(related.id) as count')
    ->selectRaw('SUM(related.amount) as total')
    ->leftJoin('related_table', 'model.id', '=', 'related.model_id')
    ->groupBy('model.id')
    ->orderBy('total', 'desc')
    ->get();
```

### Pattern 5: Conditional Where
```php
$query->where(function($q) use ($start, $end) {
    $q->whereBetween('created_at', [$start, $end])
      ->orWhereBetween('date', [$start, $end]);
});
```

## Blade Template Patterns

### Pattern 1: Summary Card
```blade
<div class="card card-block card-stretch card-height bg-primary">
    <div class="card-body text-white">
        <h6 class="mb-2">Label</h6>
        <h3>@money($value)</h3>
        <p class="mb-0">Description</p>
    </div>
</div>
```

### Pattern 2: Filter Form
```blade
<form method="GET" action="{{ route('reports.xyz') }}" 
      class="row align-items-end">
    <div class="col-md-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" 
               class="form-control" value="{{ $startDate }}">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
```

### Pattern 3: Status Badge
```blade
@if($value >= 100)
    <span class="badge bg-success">Good</span>
@elseif($value >= 50)
    <span class="badge bg-warning">Medium</span>
@else
    <span class="badge bg-danger">Low</span>
@endif
```

### Pattern 4: Data Table with Pagination
```blade
<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="bg-light">
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item->field1 }}</td>
                    <td>{{ $item->field2 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $items->appends(request()->query())->links() }}
```

## URL Parameter Reference

### Business Report
```
GET /reports/business?start_date=2024-01-01&end_date=2024-12-31
```

### Sale Report
```
GET /reports/sales?start_date=2024-01-01&end_date=2024-12-31&customer_id=5
```

### Purchase Report
```
GET /reports/purchases?start_date=2024-01-01&end_date=2024-12-31&supplier_id=3
```

### Top Customers
```
GET /reports/top-customers?start_date=2024-01-01&end_date=2024-12-31&limit=20
```

### Stock Report
```
GET /reports/stock?filter=all&search=product_name
GET /reports/stock?filter=low
GET /reports/stock?filter=out
```

## Database Field Mapping

### Sales (sale_products)
```
invoice          → Display invoice number
date             → Sales transaction date
customerId       → Link to customer
totalSale        → Subtotal before discount
discountAmount   → Discount amount
grandTotal       → Final total (totalSale - discount)
paidAmount       → Amount paid
curDue           → Current due amount
```

### Purchases (purchase_products)
```
invoice          → Purchase invoice number
purchase_date    → Purchase transaction date
supplier         → Supplier ID
totalAmount      → Subtotal before discount
disAmount        → Discount amount
grandTotal       → Final total (totalAmount - discount)
paidAmount       → Amount paid
dueAmount        → Outstanding amount
```

### Customers/Suppliers
```
id               → Primary key
name             → Name
mail             → Email address
mobile           → Phone number
openingBalance   → Outstanding balance
created_at       → Record created date
```

### Products
```
id               → Product ID
name             → Product name
barCode          → Product barcode
quantity         → Current stock
alert_quantity   → Low stock threshold
brand            → Brand ID
category         → Category ID
unitName         → Unit ID
```

## Bootstrap Classes Used

```
Grid System:
  .col-lg-3, .col-md-6, .col-md-4 → Responsive columns
  .row, .row-cols-* → Grid rows

Cards:
  .card → Card container
  .card-header, .card-body → Card sections
  .card-block, .card-stretch, .card-height → Card variants

Colors:
  .bg-primary, .bg-success, .bg-warning, .bg-danger
  .text-primary, .text-success, .text-warning, .text-danger

Tables:
  .table → Table styling
  .table-bordered → Border styling
  .table-hover → Hover effect
  .table-responsive → Mobile responsive

Forms:
  .form-label, .form-control → Form styling
  .form-group, .input-group → Form grouping

Utilities:
  .d-flex, .align-items-center, .justify-content-between
  .mt-3, .mb-2, .p-0 → Spacing
  .text-center, .text-right → Text alignment
```

## Blade Helper Functions Used

```
@extends()        → Extend base layout
@include()        → Include view partial
@section()        → Define content section
@yield()          → Output section content

@money()          → Format currency (custom directive)
@foreach()        → Loop through collection
@forelse()        → Loop with empty fallback
@if() @endif      → Conditional rendering
@empty            → Check if empty

@route()          → Generate route URL
route()           → Generate route URL (PHP)
config()          → Get config value
csrf_token()      → CSRF token
@method()         → HTTP method override
```

## Common Customizations

### Change Pagination Limit
File: `app/Http/Controllers/ReportController.php`
```php
$sales = $query->orderBy('created_at', 'desc')->paginate(100); // was 50
```

### Change Default Date Range
File: `app/Http/Controllers/ReportController.php`
```php
$startDate = $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
```

### Change Summary Card Colors
File: `resources/views/reports/*-report.blade.php`
```blade
<!-- Change bg-primary to: bg-success, bg-warning, bg-danger, bg-info -->
<div class="card bg-success">
```

### Add New Report Field to Table
File: `resources/views/reports/xyz-report.blade.php`
```blade
<th>New Field</th>
<!-- In tbody: -->
<td>{{ $item->new_field }}</td>
```

## Performance Optimization Tips

### 1. Add Database Indexes
```sql
ALTER TABLE sale_products ADD INDEX idx_date (date);
ALTER TABLE sale_products ADD INDEX idx_customerId (customerId);
ALTER TABLE purchase_products ADD INDEX idx_supplier (supplier);
ALTER TABLE purchase_products ADD INDEX idx_purchase_date (purchase_date);
```

### 2. Use Eager Loading
```php
$sales = SaleProduct::with(['customer'])->where(...)->get();
```

### 3. Cache Expensive Queries
```php
$result = Cache::remember('report_key', 3600, function () {
    return Model::expensive_query()->get();
});
```

### 4. Limit Query Results
```php
$sales = $query->paginate(50); // Not get()
```

## Security Checklist

- [x] Routes protected with SuperAdmin middleware
- [x] Admin authentication required
- [x] CSRF protection via routing
- [x] Blade escaping ({{ }})
- [x] No SQL injection risks (using ORM)
- [x] Query parameter validation implicit
- [x] No sensitive data in view names
- [x] No debug info exposed

## Testing Queries

### Test in Tinker
```php
php artisan tinker

# Test business report query
$sales = \App\Models\SaleProduct::sum('totalSale');

# Test top customers
$customers = \App\Models\Customer::selectRaw('COUNT(*) as count')
    ->groupBy('id')->orderBy('count', 'desc')->limit(10)->get();

# Test low stock
$products = \App\Models\Product::where('quantity', '<=', 
    \Illuminate\Support\Facades\DB::raw('alert_quantity'))->count();
```

## Debugging Tips

### View Query Debug
```php
// In controller
$query = Model::where(...);
dd($query->toSql()); // See raw SQL
```

### Check Blade Variables
```blade
<!-- In view -->
{{ dd($variable) }} <!-- Dump and die -->
{{ var_dump($data) }} <!-- Dump variable -->
```

### Check View Cache
```bash
php artisan view:clear
php artisan cache:clear
```

### Check Routes
```bash
php artisan route:list | grep reports
```

---

## Quick Links

| Document | Purpose |
|----------|---------|
| REPORTING_SYSTEM.md | Full technical docs |
| REPORTING_QUICK_START.md | User guide |
| REPORTING_SYSTEM_CHECKLIST.md | Implementation checklist |
| REPORTING_VISUAL_GUIDE.md | Architecture diagrams |

---

**Last Updated**: 2024  
**Audience**: Developers & System Administrators  
**Version**: 1.0
