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
            @desktop
            <a href="{{ route('expense.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> {{ __('common.add_category') }}
            </a>
            @enddesktop
        </div>

        <div class="table-responsive rn-m-master-desktop-list">
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
                    @foreach($categories as $category)
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
                    @endforeach
                </tbody>
            </table>
        </div>
        @mobile
        <div class="rn-m-master-cards" aria-label="{{ __('common.expense_categories') }}">
            @forelse($categories as $category)
            <article class="rn-m-master-card">
                <div class="rn-m-master-card__copy">
                    <strong>{{ $category->name }}</strong>
                    @if($category->description)<small>{{ \Illuminate\Support\Str::limit($category->description, 80) }}</small>@endif
                    <span class="rn-m-master-card__amount">{{ $category->is_active ? __('common.active') : __('common.inactive') }}</span>
                </div>
                <details class="rn-m-master-menu">
                    <summary aria-label="{{ __('mobile.actions') }}">⋮</summary>
                    <div class="rn-m-master-menu__items">
                        <a href="{{ route('expense.categories.edit', $category->id) }}">{{ __('mobile.edit') }}</a>
                        <a href="{{ route('expense.categories.delete', $category->id) }}" onclick="return confirm('{{ addslashes(__('common.delete_category')) }}?')">{{ __('mobile.delete') }}</a>
                    </div>
                </details>
            </article>
            @empty <div class="rn-m-empty-state">{{ __('mobile.list_empty') }}</div> @endforelse
            <a class="rn-m-master-fab" href="{{ route('expense.categories.create') }}"><span aria-hidden="true">+</span> {{ __('mobile.add_new') }}</a>
        </div>
        @endmobile
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
