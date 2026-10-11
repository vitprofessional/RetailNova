@extends('include')

@section('backTitle'){{ __('common.heading_expense_management') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.expense_management') }}</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('expense.categories') }}" class="btn btn-secondary btn-sm">
                    <i class="las la-tags"></i> {{ __('common.manage_categories') }}
                </a>
                <a href="{{ route('expense.create') }}" class="btn btn-primary btn-sm">
                    <i class="las la-plus"></i> {{ __('common.add_expense') }}
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('expense.list') }}">
            <div class="row mb-4">
                            <div class="col-md-3">
                                <label>{{ __('common.start_date') }}</label>
                                <input type="date" class="form-control" name="start_date"
                                       value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>{{ __('common.end_date') }}</label>
                                <input type="date" class="form-control" name="end_date"
                                       value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label>{{ __('common.category') }}</label>
                                <select class="form-control" name="category_id">
                                    <option value="">{{ __('common.all_categories') }}</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                            {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>{{ __('common.payment_method') }}</label>
                                <select class="form-control" name="payment_method">
                                    <option value="">{{ __('common.all_methods') }}</option>
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
                                    <i class="fas fa-filter"></i> {{ __('common.filter') }}
                                </button>
                                <a href="{{ route('expense.list') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> {{ __('common.reset') }}
                                </a>
                                <a href="{{ route('expense.reports') }}" class="btn btn-info float-right">
                                    <i class="fas fa-chart-bar"></i> {{ __('common.view_reports') }}
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Summary --}}
                    <div class="row mt-3 mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>{{ __('common.total_expenses') }}:</strong> {{ number_format($totalExpense, 2) }}
                                <span class="ml-3">|</span>
                                <strong class="ml-3">{{ __('common.count') }}:</strong> {{ $expenses->total() }} {{ __('common.expenses') }}
                            </div>
                        </div>
                    </div>

                    {{-- Expenses Table --}}
                    <div class="table-responsive">
                        <table class="data-tables table mb-0 table-bordered rn-table-pro">
                            <thead class="bg-white text-uppercase">
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('common.category') }}</th>
                                    <th>{{ __('common.amount') }}</th>
                                    <th>{{ __('common.payment_method') }}</th>
                                    <th>{{ __('common.reference_no') }}</th>
                                    <th>{{ __('common.description') }}</th>
                                    <th>{{ __('common.receipt') }}</th>
                                    <th>{{ __('common.created_by') }}</th>
                                    <th>{{ __('common.actions') }}</th>
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
                                            <i class="fas fa-file-download"></i> {{ __('common.view') }}
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
                                               title="{{ __('common.edit') }}">
                                                <i class="las la-edit"></i>
                                            </a>
                                            <a href="{{ route('expense.delete', $expense->id) }}"
                                               class="btn btn-sm btn-danger"
                                               title="{{ __('common.delete') }}"
                                               onclick="return confirm('Are you sure you want to delete this expense?')">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">
                                        {{ __('common.no_expenses_found') }} <a href="{{ route('expense.create') }}">{{ __('common.add_your_first_expense') }}</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right">{{ __('common.total') }}:</td>
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
