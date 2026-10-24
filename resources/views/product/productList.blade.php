@extends('include')
@section('backTitle'){{ __('common.heading_product_list') }}@endsection
@section('container')
{{-- UI-2: the mobile version is cards (App\Services\MobileLists); the desktop markup below is unchanged. --}}
@mobile
@include('mobile.lists.page', ['list' => new \App\Services\MobileLists\ProductsList('productlist')])
@else
<link rel="stylesheet" href="{{ asset('css/inventory-products.css') }}?v={{ filemtime(public_path('css/inventory-products.css')) }}">
<div class="col-12">@include('sweetalert::alert')</div>

@php
    $stockSummary = $stockSummary ?? ['total' => count($listItem ?? []), 'in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
@endphp

<main class="rn-product-catalog">
    <header class="rn-catalog-heading">
        <div class="rn-catalog-title-group">
            <div class="rn-catalog-icon" aria-hidden="true"><i class="las la-cube"></i></div>
            <div>
                <div class="rn-product-breadcrumb">{{ __('inventory.product_page_breadcrumb') }} <span aria-hidden="true">/</span> {{ __('inventory.product_list') }}</div>
                <h1>{{ __('inventory.product_list') }}</h1>
                <p>{{ __('inventory.catalog_intro') }}</p>
            </div>
        </div>
        <a href="{{ route('addProduct') }}" class="btn btn-primary rn-catalog-create"><i class="las la-plus mr-1" aria-hidden="true"></i>{{ __('inventory.create_product') }}</a>
    </header>

    <section class="rn-inventory-stats" aria-label="{{ __('inventory.catalog_metrics_total') }}">
        <article class="rn-inventory-stat rn-stat-total">
            <span class="rn-stat-icon" aria-hidden="true"><i class="las la-cube"></i></span>
            <div><span class="rn-stat-label">{{ __('inventory.catalog_metrics_total') }}</span><strong data-stat-value="{{ $stockSummary['total'] }}">{{ $stockSummary['total'] }}</strong></div>
        </article>
        <article class="rn-inventory-stat rn-stat-in-stock">
            <span class="rn-stat-icon" aria-hidden="true"><i class="las la-database"></i></span>
            <div><span class="rn-stat-label">{{ __('inventory.catalog_metrics_in_stock') }}</span><strong data-stat-value="{{ $stockSummary['in_stock'] }}">{{ $stockSummary['in_stock'] }}</strong></div>
        </article>
        <article class="rn-inventory-stat rn-stat-low-stock">
            <span class="rn-stat-icon" aria-hidden="true"><i class="las la-exclamation-triangle"></i></span>
            <div><span class="rn-stat-label">{{ __('inventory.catalog_metrics_low_stock') }}</span><strong data-stat-value="{{ $stockSummary['low_stock'] }}">{{ $stockSummary['low_stock'] }}</strong></div>
        </article>
        <article class="rn-inventory-stat rn-stat-out-stock">
            <span class="rn-stat-icon" aria-hidden="true"><i class="las la-box"></i></span>
            <div><span class="rn-stat-label">{{ __('inventory.catalog_metrics_out_of_stock') }}</span><strong data-stat-value="{{ $stockSummary['out_of_stock'] }}">{{ $stockSummary['out_of_stock'] }}</strong></div>
        </article>
    </section>

    <section class="rn-catalog-filters" aria-labelledby="rn-catalog-filters-title">
        <h2 id="rn-catalog-filters-title"><span aria-hidden="true"><i class="las la-filter"></i></span>{{ __('inventory.catalog_filters_title') }}</h2>
        <div class="rn-catalog-filter-grid">
            <div class="rn-catalog-filter-field">
                <label for="filterBrand"><i class="las la-tag" aria-hidden="true"></i>{{ __('inventory.brand') }}</label>
                <select id="filterBrand" class="form-control rn-product-catalog-filter" data-column="2">
                    <option value="">{{ __('inventory.all_brands') }}</option>
                    @foreach($brandList ?? [] as $brand)
                        <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rn-catalog-filter-field">
                <label for="filterCategory"><i class="las la-th-large" aria-hidden="true"></i>{{ __('inventory.category') }}</label>
                <select id="filterCategory" class="form-control rn-product-catalog-filter" data-column="3">
                    <option value="">{{ __('inventory.all_categories') }}</option>
                    @foreach($categoryList ?? [] as $category)
                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rn-catalog-filter-field rn-catalog-search-field">
                <label for="globalSearchProduct"><i class="las la-search" aria-hidden="true"></i>{{ __('common.search') }}</label>
                <div class="rn-catalog-search">
                    <i class="las la-search" aria-hidden="true"></i>
                    <input id="globalSearchProduct" type="search" class="form-control rn-search-input rn-filter-input" data-table-target="productTable" placeholder="{{ __('inventory.search_products') }}" autocomplete="off">
                    <button class="rn-search-clear" type="button" aria-label="{{ __('common.clear_search') }}"><i class="las la-times" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </section>

    @include('partials.bulk-actions', ['deleteRoute' => 'products.bulkDelete', 'entity' => 'Products'])

    <section class="rn-catalog-table-card" aria-labelledby="rn-catalog-table-title">
        <div class="rn-catalog-table-heading">
            <div class="rn-catalog-table-title">
                <span class="rn-catalog-table-icon" aria-hidden="true"><i class="las la-database"></i></span>
                <div><h2 id="rn-catalog-table-title">{{ __('inventory.catalog_table_title') }}</h2><p>{{ __('inventory.catalog_record_count', ['count' => $stockSummary['total']]) }}</p></div>
            </div>
            <label class="rn-catalog-page-size">{{ __('inventory.show') }}
                <select id="productPageLength" class="form-control" aria-label="{{ __('inventory.entries_per_page') }}">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                {{ __('inventory.entries') }}
            </label>
        </div>
        <div class="rn-catalog-table-scroll">
            {{-- The shared catalog search binding initializes this table with its custom page-size control. --}}
            <table id="productTable" class="table mb-0 table-bordered rn-table-pro">
                <thead>
                    <tr>
                        <th class="rn-check-column"><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input" id="selectAllProducts"><label for="selectAllProducts" class="mb-0"></label></div></th>
                        <th>{{ __('inventory.product_name') }}</th>
                        <th>{{ __('inventory.brand') }}</th>
                        <th>{{ __('inventory.category') }}</th>
                        <th>{{ __('inventory.unit') }}</th>
                        <th>{{ __('inventory.alert_quantity') }}</th>
                        <th>{{ __('inventory.current_stock') }}</th>
                        <th>{{ __('inventory.barcode') }}</th>
                        <th>{{ __('inventory.details') }}</th>
                        <th>{{ __('inventory.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($listItem ?? [] as $productList)
                        @php
                            $totalStock = $productList->total_stock;
                            $stockStatus = $productList->is_out_of_stock ? 'out_of_stock' : ($productList->is_low_stock ? 'low_stock' : 'in_stock');
                        @endphp
                        <tr>
                            <td><div class="checkbox d-inline-block"><input type="checkbox" class="checkbox-input bulk-select" value="{{ $productList->id }}"><label class="mb-0"></label></div></td>
                            <td class="rn-product-name-cell">{{ $productList->name }}</td>
                            <td>{{ $productList->brandModel->name ?? '-' }}</td>
                            <td>{{ $productList->categoryModel->name ?? '-' }}</td>
                            <td>{{ $productList->unitModel->name ?? '-' }}</td>
                            <td class="rn-number-cell">{{ $productList->quantity ?? 0 }}</td>
                            <td><span class="rn-stock-badge rn-stock-{{ $stockStatus }}">{{ $totalStock }} <span>{{ __('inventory.'.$stockStatus) }}</span></span></td>
                            <td class="rn-barcode-cell">{{ $productList->barCode ?: '-' }}</td>
                            <td class="rn-details-cell" title="{{ $productList->details }}">{{ $productList->details ?: '-' }}</td>
                            <td><div class="rn-product-row-actions">
                                @canEdit(false)
                                    <a class="rn-row-action rn-row-edit" href="{{ route('editProduct', ['id' => $productList->id]) }}" title="{{ __('inventory.edit') }}" aria-label="{{ __('inventory.edit') }} {{ $productList->name }}"><i class="ri-pencil-line" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('delProduct', ['id' => $productList->id]) }}" data-onsubmit="confirm" data-confirm="{{ __('inventory.confirm_delete_product') }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rn-row-action rn-row-delete" title="{{ __('inventory.delete') }}" aria-label="{{ __('inventory.delete') }} {{ $productList->name }}"><i class="ri-delete-bin-line" aria-hidden="true"></i></button>
                                    </form>
                                @endcanEdit
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="rn-catalog-empty">{{ __('inventory.no_products_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endmobile
@endsection

@section('scripts')
@desktop
    @parent
    @include('partials.bulk-actions-script')
    <script>
    (function(){
        function applyCatalogFilters(){
            var table = document.getElementById('productTable');
            if(!table) return;
            var api = null;
            try { if(window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(table)) api = jQuery(table).DataTable(); } catch(e) {}
            if(api){
                document.querySelectorAll('.rn-product-catalog-filter').forEach(function(select){
                    var column = Number(select.getAttribute('data-column'));
                    var value = select.value || '';
                    var escaped = jQuery.fn.dataTable.util.escapeRegex(value);
                    api.column(column).search(value ? '^' + escaped + '$' : '', true, false);
                });
                api.draw();
                return;
            }
            var brand = document.getElementById('filterBrand').value.toLowerCase();
            var category = document.getElementById('filterCategory').value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function(row){
                var cells = row.cells;
                var brandText = cells[2] ? cells[2].textContent.trim().toLowerCase() : '';
                var categoryText = cells[3] ? cells[3].textContent.trim().toLowerCase() : '';
                row.style.display = (!brand || brandText === brand) && (!category || categoryText === category) ? '' : 'none';
            });
        }
        document.querySelectorAll('.rn-product-catalog-filter').forEach(function(select){ select.addEventListener('change', applyCatalogFilters); });
        var pageLength = document.getElementById('productPageLength');
        if(pageLength){
            pageLength.addEventListener('change', function(){
                try {
                    var table = document.getElementById('productTable');
                    if(window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(table)) jQuery(table).DataTable().page.len(Number(this.value)).draw();
                } catch(e) {}
            });
        }
    })();
    </script>

@enddesktop
@endsection
