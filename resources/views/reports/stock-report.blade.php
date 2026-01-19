@extends('include')
@section('backTitle')
Stock Report
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">Stock & Low Stock Report</h3>
                        <p class="mb-0">View current inventory levels and identify low stock products.</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line"></i> Back to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height bg-primary">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Products</h6>
                        <h3>{{ $totalProducts }}</h3>
                        <p class="mb-0">In Inventory</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height bg-warning">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Low Stock Products</h6>
                        <h3>{{ $lowStockCount }}</h3>
                        <p class="mb-0">10 or Fewer Items</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height bg-danger">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Out of Stock</h6>
                        <h3>{{ $outOfStockCount }}</h3>
                        <p class="mb-0">Zero Quantity</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.stock') }}" class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Status</label>
                        <select name="filter" class="form-control">
                            <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>All Products</option>
                            <option value="low" {{ $filter == 'low' ? 'selected' : '' }}>Low Stock Only</option>
                            <option value="out" {{ $filter == 'out' ? 'selected' : '' }}>Out of Stock Only</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Search Product</label>
                        <input type="text" name="search" class="form-control" placeholder="Product name or barcode" value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-3-line"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Stock Table -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    @if($filter == 'low')
                        Low Stock Products
                    @elseif($filter == 'out')
                        Out of Stock Products
                    @else
                        All Products
                    @endif
                </h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="ri-printer-line"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Product Name</th>
                                <th>Barcode</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                @php
                                    $quantity = intval($product->quantity ?? 0);
                                    $status = 'normal';
                                    $statusClass = 'success';
                                    
                                    if($quantity <= 0) {
                                        $status = 'Out of Stock';
                                        $statusClass = 'danger';
                                    } elseif($quantity <= 10) {
                                        $status = 'Low Stock';
                                        $statusClass = 'warning';
                                    } else {
                                        $status = 'In Stock';
                                        $statusClass = 'success';
                                    }
                                @endphp
                                <tr class="{{ $quantity <= 0 ? 'table-danger' : ($quantity <= 10 ? 'table-warning' : '') }}">
                                    <td><strong>{{ $product->name }}</strong></td>
                                    <td>{{ $product->barCode ?? 'N/A' }}</td>
                                    <td>{{ $product->brandModel->name ?? 'N/A' }}</td>
                                    <td>{{ $product->categoryModel->name ?? 'N/A' }}</td>
                                    <td>{{ $product->unitModel->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <strong class="text-{{ $statusClass }}">{{ $quantity }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No products found matching the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    @if($lowStockCount > 0 || $outOfStockCount > 0)
        <div class="col-lg-12 mt-3">
            <div class="alert alert-warning">
                <h6><i class="ri-alert-line"></i> Stock Alert</h6>
                <p class="mb-0">
                    You have <strong>{{ $lowStockCount }}</strong> low stock products and 
                    <strong>{{ $outOfStockCount }}</strong> out of stock products. 
                    Consider restocking these items to avoid sales disruptions.
                </p>
            </div>
        </div>
    @endif
</div>
@endsection
