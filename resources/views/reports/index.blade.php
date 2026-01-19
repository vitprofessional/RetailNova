@extends('include')
@section('backTitle')
Reports Dashboard
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <h3 class="mb-3">Reports Dashboard</h3>
                <p class="mb-0">Access comprehensive reports for your business analytics.</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-12">
        <div class="row">
            <!-- Business Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">Business Report</h5>
                                <p class="mb-0">View overall business performance, sales, purchases, and profit analysis.</p>
                            </div>
                            <div class="rounded bg-primary p-3">
                                <i class="ri-bar-chart-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.business') }}" class="btn btn-primary mt-3 w-100">View Report</a>
                    </div>
                </div>
            </div>
            
            <!-- Sale Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">Sale Report</h5>
                                <p class="mb-0">Detailed sales transactions with date filtering and customer analysis.</p>
                            </div>
                            <div class="rounded bg-success p-3">
                                <i class="ri-shopping-cart-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.sales') }}" class="btn btn-success mt-3 w-100">View Report</a>
                    </div>
                </div>
            </div>
            
            <!-- Purchase Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">Purchase Report</h5>
                                <p class="mb-0">Track all purchase transactions and supplier-wise analysis.</p>
                            </div>
                            <div class="rounded bg-info p-3">
                                <i class="ri-file-list-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.purchases') }}" class="btn btn-info mt-3 w-100">View Report</a>
                    </div>
                </div>
            </div>
            
            <!-- Top Customers -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">Top Customers</h5>
                                <p class="mb-0">Identify your best customers by sales volume and order frequency.</p>
                            </div>
                            <div class="rounded bg-warning p-3">
                                <i class="ri-user-star-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.topCustomers') }}" class="btn btn-warning mt-3 w-100">View Report</a>
                    </div>
                </div>
            </div>
            
            <!-- Payable/Receivable -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">Payable/Receivable</h5>
                                <p class="mb-0">Monitor outstanding payments from customers and to suppliers.</p>
                            </div>
                            <div class="rounded bg-danger p-3">
                                <i class="ri-money-dollar-circle-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.payableReceivable') }}" class="btn btn-danger mt-3 w-100">View Report</a>
                    </div>
                </div>
            </div>
            
            <!-- Stock Report -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">Stock & Low Stock</h5>
                                <p class="mb-0">View current inventory levels and identify low stock products.</p>
                            </div>
                            <div class="rounded bg-secondary p-3">
                                <i class="ri-stack-line text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('reports.stock') }}" class="btn btn-secondary mt-3 w-100">View Report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
