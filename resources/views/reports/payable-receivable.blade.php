@extends('include')
@section('backTitle')
Payable & Receivable
@endsection
@section('container')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-transparent card-block card-stretch card-height border-none">
            <div class="card-body p-0 mt-lg-2 mt-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-3">Payable & Receivable Report</h3>
                        <p class="mb-0">Monitor outstanding payments from customers and to suppliers.</p>
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
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-success">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Receivables</h6>
                        <h3>@money($totalReceivable)</h3>
                        <p class="mb-0">From {{ $receivables->count() }} Customers</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height bg-danger">
                    <div class="card-body text-white">
                        <h6 class="mb-2">Total Payables</h6>
                        <h3>@money($totalPayable)</h3>
                        <p class="mb-0">To {{ $payables->count() }} Suppliers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Receivables Section -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between">
                <h5 class="mb-0">Accounts Receivable (From Customers)</h5>
                <button onclick="window.print()" class="btn btn-sm btn-light">
                    <i class="ri-printer-line"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th class="text-right">Outstanding Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receivables as $index => $customer)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $customer->name }}</strong></td>
                                    <td>{{ $customer->mail ?? 'N/A' }}</td>
                                    <td>{{ $customer->mobile ?? 'N/A' }}</td>
                                    <td class="text-right">
                                        <strong class="text-success">@money($customer->openingBalance)</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No outstanding receivables from customers.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($receivables->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">Total Receivable:</th>
                                    <th class="text-right text-success">@money($totalReceivable)</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payables Section -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header bg-danger text-white d-flex justify-content-between">
                <h5 class="mb-0">Accounts Payable (To Suppliers)</h5>
                <button onclick="window.print()" class="btn btn-sm btn-light">
                    <i class="ri-printer-line"></i> Print
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Supplier Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th class="text-right">Outstanding Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payables as $index => $supplier)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $supplier->name }}</strong></td>
                                    <td>{{ $supplier->mail ?? 'N/A' }}</td>
                                    <td>{{ $supplier->mobile ?? 'N/A' }}</td>
                                    <td class="text-right">
                                        <strong class="text-danger">@money($supplier->openingBalance)</strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No outstanding payables to suppliers.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($payables->count() > 0)
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">Total Payable:</th>
                                    <th class="text-right text-danger">@money($totalPayable)</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Net Position -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>Total Receivable:</h6>
                        <h4 class="text-success">@money($totalReceivable)</h4>
                    </div>
                    <div class="col-md-4">
                        <h6>Total Payable:</h6>
                        <h4 class="text-danger">@money($totalPayable)</h4>
                    </div>
                    <div class="col-md-4">
                        <h6>Net Position:</h6>
                        @php
                            $netPosition = $totalReceivable - $totalPayable;
                        @endphp
                        <h4 class="{{ $netPosition >= 0 ? 'text-success' : 'text-danger' }}">
                            @money(abs($netPosition))
                            @if($netPosition >= 0)
                                (In Favor)
                            @else
                                (Against)
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
