@extends('include')

@section('backTitle'){{ __('common.heading_expense_categories') }}@endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.expense_categories') }}</h4>
            <a href="{{ route('expense.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> {{ __('common.add_category') }}
            </a>
        </div>

        <div class="table-responsive">
            <table id="expenseCategoriesTable" class="data-tables table mb-0 table-bordered rn-table-pro">
                <thead class="bg-white text-uppercase">
                    <tr>
                        <th>{{ __('common.category_name') }}</th>
                        <th>{{ __('common.description') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ \Str::limit($category->description, 50) ?? '-' }}</td>
                        <td>
                            <span class="badge badge-{{ $category->is_active ? 'success' : 'secondary' }}">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('expense.categories.edit', $category->id) }}" class="btn btn-sm btn-info" title="{{ __('common.edit_category') }}">
                                    <i class="las la-edit"></i>
                                </a>
                                <a href="{{ route('expense.categories.delete', $category->id) }}"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this category?');"
                                   title="{{ __('common.delete_category') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="las la-inbox" style="font-size: 40px; color: #ddd;"></i>
                            <p class="text-muted mt-2">{{ __('common.no_categories_found') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    // Initialize DataTables on expense categories
    window.__jqOnReady(function(){
        try {
            if (window.jQuery && typeof jQuery.fn.DataTable === 'function') {
                var $t = jQuery('#expenseCategoriesTable');
                if ($t.length && !jQuery.fn.DataTable.isDataTable($t)) {
                    $t.DataTable({
                        pageLength: 10,
                        order: [],
                        lengthChange: false
                    });
                }
            }
        } catch (e) {
            console.warn('DataTable init failed', e);
        }
    });
</script>

@endsection
