@extends('include')

@section('backTitle')Expense Management @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Expense Management</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('expense.categories') }}" class="btn btn-secondary btn-sm">
                    <i class="las la-tags"></i> Manage Categories
                </a>
                <a href="{{ route('expense.create') }}" class="btn btn-primary btn-sm">
                    <i class="las la-plus"></i> Add Expense
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('expense.list') }}">
            <div class="row mb-4">
                            <div class="col-md-3">
                                <label>Start Date</label>
                                <input type="date" class="form-control" name="start_date" 
                                       value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>End Date</label>
                                <input type="date" class="form-control" name="end_date" 
                                       value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Category</label>
                                <select class="form-control" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Payment Method</label>
                                <select class="form-control" name="payment_method">
                                    <option value="">All Methods</option>
                                    @foreach($paymentMethods as $key => $method)
                                    <option value="{{ $key }}" 
                                            {{ request('payment_method') == $key ? 'selected' : '' }}>
                                        {{ $method }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="{{ route('expense.list') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                                <a href="{{ route('expense.reports') }}" class="btn btn-info float-right">
                                    <i class="fas fa-chart-bar"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Summary --}}
                    <div class="row mt-3 mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>Total Expenses:</strong> {{ number_format($totalExpense, 2) }} 
                                <span class="ml-3">|</span>
                                <strong class="ml-3">Count:</strong> {{ $expenses->total() }} expenses
                            </div>
                        </div>
                    </div>

                    {{-- Expenses Table --}}
                    <div class="table-responsive">
                        <table class="data-tables table mb-0 table-bordered rn-table-pro">
                            <thead class="bg-white text-uppercase">
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Reference No</th>
                                    <th>Description</th>
                                    <th>Receipt</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                <tr>
                                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ $expense->category->name }}
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($expense->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ ucfirst($expense->payment_method) }}
                                        </span>
                                    </td>
                                    <td>{{ $expense->reference_no ?? '-' }}</td>
                                    <td>{{ Str::limit($expense->description, 50) ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($expense->receipt_file)
                                        <a href="{{ Storage::url($expense->receipt_file) }}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-file-download"></i> View
                                        </a>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $expense->creator->name ?? 'Unknown' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('expense.edit', $expense->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit">
                                                <i class="las la-edit"></i>
                                            </a>
                                            <a href="{{ route('expense.delete', $expense->id) }}" 
                                               class="btn btn-sm btn-danger" 
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this expense?')">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">
                                        No expenses found. <a href="{{ route('expense.create') }}">Add your first expense</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right">Total:</td>
                                    <td class="text-right">{{ number_format($expenses->sum('amount'), 2) }}</td>
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
