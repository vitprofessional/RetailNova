@extends('include')
@section('backTitle') {{ __('navigation.sales_history') }} @endsection
@section('container')
<style>
    .rn-sales-history { min-width: 0; color: #172b4d; }
    .rn-sales-history * { box-sizing: border-box; }
    .rn-sales-history-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin: 8px 0 22px; }
    .rn-sales-history-breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; color: #6d7f9a; font-size: 13px; }
    .rn-sales-history-breadcrumb a { color: #1670ef; text-decoration: none; }
    .rn-sales-history-title { display: flex; align-items: center; gap: 12px; margin: 0; color: #142b4d; font-size: 26px; font-weight: 750; line-height: 1.25; }
    .rn-sales-history-title-icon { display: inline-flex; flex: 0 0 42px; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 11px; color: #1765ef; background: #eaf1ff; font-size: 23px; }
    .rn-sales-history-description { margin: 6px 0 0 54px; color: #6c7d97; font-size: 14px; }
    .rn-sales-history-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 12px; }
    .rn-sales-history-actions .btn { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; padding: 9px 16px; border-radius: 8px; font-weight: 600; white-space: nowrap; }
    .rn-sales-history-actions .btn-primary { border-color: #1765ef; background: #1765ef; box-shadow: 0 4px 10px rgb(23 101 239 / 16%); }
    .rn-sales-history-export { border-color: #ff824e; color: #e96a35; background: #fff; }
    .rn-sales-history-export:hover, .rn-sales-history-export:focus { border-color: #ec6c36; color: #cf5120; background: #fff8f4; }
    .rn-sales-history-card { min-width: 0; margin-bottom: 18px; padding: 20px; border: 1px solid #e0e8f2; border-radius: 13px; background: #fff; box-shadow: 0 5px 18px rgb(30 58 95 / 5%); }
    .rn-sales-history-filters { display: grid; grid-template-columns: minmax(200px, 1.55fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(225px, 1.55fr) auto; align-items: end; gap: 14px; }
    .rn-sales-history-field { min-width: 0; }
    .rn-sales-history-field label, .rn-sales-history-date-label { display: block; margin: 0 0 7px; color: #344965; font-size: 12px; font-weight: 700; line-height: 1.35; }
    .rn-sales-history .form-control { width: 100%; min-width: 0; height: 42px; min-height: 42px; padding: 8px 11px; border: 1px solid #cbd7e6; border-radius: 8px; color: #24364f; font-size: 13px; }
    .rn-sales-history-filters .form-control { height: 44px; min-height: 44px; padding: 8px 12px !important; line-height: 1.35; }
    .rn-sales-history .form-control:focus { border-color: #3478f6; box-shadow: 0 0 0 3px rgb(52 120 246 / 12%); }
    .rn-sales-history-search-wrap { position: relative; }
    .rn-sales-history-search-wrap > i { position: absolute; top: 50%; left: 14px; z-index: 1; width: 16px; color: #687f9e; pointer-events: none; text-align: center; transform: translateY(-50%); }
    .rn-sales-history-search-wrap .form-control { padding-left: 42px !important; padding-right: 12px !important; }
    .rn-sales-history-date-range { display: grid; grid-template-columns: minmax(0,1fr) auto minmax(0,1fr); align-items: center; gap: 8px; }
    .rn-sales-history-date-range .form-control { padding-inline: 8px; }
    .rn-sales-history-date-separator { color: #71819a; font-size: 12px; white-space: nowrap; }
    .rn-sales-history-filter-actions { display: flex; align-items: end; gap: 8px; }
    .rn-sales-history-filter-actions .btn { display: inline-flex; min-width: 88px; height: 44px; min-height: 44px; align-items: center; justify-content: center; gap: 7px; padding: 8px 13px; border-radius: 8px; font-size: 13px; font-weight: 600; white-space: nowrap; }
    .rn-sales-history-filter-actions .btn-primary { border-color: #1765ef; background: #1765ef; }
    .rn-sales-history-reset { border: 1px solid #d3ddea; color: #4c617e; background: #fff; }
    .rn-sales-history-reset:hover, .rn-sales-history-reset:focus { border-color: #9eb2ce; color: #233d63; background: #f7faff; }
    .rn-sales-history-table-card { padding: 18px 16px 14px; }
    .rn-sales-history-table-card .rn-sales-history-bulk { margin: 0 0 12px; }
    .rn-sales-history-table-card .bulk-actions { display: flex; flex-wrap: wrap; }
    .rn-sales-history-table-card .rn-dt-toolbar { display: flex; min-width: 0; align-items: center; justify-content: flex-start; gap: 12px; margin-bottom: 12px; color: #536985; font-size: 13px; }
    .rn-sales-history-table-card .dataTables_length { margin: 0; }
    .rn-sales-history-table-card .dataTables_length label { display: inline-flex; align-items: center; gap: 8px; margin: 0; color: #344965; font-size: 13px; font-weight: 600; }
    .rn-sales-history-table-card .dataTables_length select { width: auto; min-width: 60px; height: 38px; min-height: 38px; margin: 0 3px; padding: 6px 24px 6px 9px; border: 1px solid #d0dbea; border-radius: 7px; color: #24364f; }
    .rn-sales-history-table-wrap { width: 100%; min-width: 0; overflow-x: auto; border: 1px solid #dce5f0; border-radius: 9px; -webkit-overflow-scrolling: touch; }
    .rn-sales-history-table-wrap:focus { outline: 3px solid rgb(52 120 246 / 22%); outline-offset: 2px; }
    .rn-sales-history-table { width: 100%; min-width: 1040px; margin: 0 !important; table-layout: fixed; border-collapse: separate; border-spacing: 0; color: #26374f; font-size: 12px; }
    .rn-sales-history-table thead th { position: relative; padding: 12px 22px 12px 10px !important; border: 0 !important; border-bottom: 1px solid #dce5f0 !important; color: #3b506d; background: #eff4fb; font-size: 11px !important; font-weight: 750; letter-spacing: .02em; line-height: 1.35; text-align: left; vertical-align: middle; white-space: nowrap; }
    .rn-sales-history-table thead th.sorting, .rn-sales-history-table thead th.sorting_asc, .rn-sales-history-table thead th.sorting_desc { background-color: #eff4fb; }
    .rn-sales-history-table tbody td { height: 48px; padding: 8px 9px !important; border-top: 1px solid #e9eef5 !important; color: #34465f; font-size: 12px; line-height: 1.45; vertical-align: middle; }
    .rn-sales-history-table.dataTable thead th, .rn-sales-history-table.dataTable tbody td { box-sizing: border-box; }
    .rn-sales-history-table th:nth-child(1), .rn-sales-history-table td:nth-child(1) { width: 40px; }
    .rn-sales-history-table th:nth-child(2), .rn-sales-history-table td:nth-child(2) { width: 42px; }
    .rn-sales-history-table th:nth-child(3), .rn-sales-history-table td:nth-child(3) { width: 130px; }
    .rn-sales-history-table th:nth-child(4), .rn-sales-history-table td:nth-child(4) { width: 150px; }
    .rn-sales-history-table th:nth-child(5), .rn-sales-history-table td:nth-child(5) { width: 105px; }
    .rn-sales-history-table th:nth-child(6), .rn-sales-history-table td:nth-child(6),
    .rn-sales-history-table th:nth-child(7), .rn-sales-history-table td:nth-child(7),
    .rn-sales-history-table th:nth-child(8), .rn-sales-history-table td:nth-child(8) { width: 88px; }
    .rn-sales-history-table th:nth-child(9), .rn-sales-history-table td:nth-child(9) { width: 80px; }
    .rn-sales-history-table th:nth-child(10), .rn-sales-history-table td:nth-child(10) { width: 96px; }
    .rn-sales-history-table th:nth-child(11), .rn-sales-history-table td:nth-child(11) { width: 164px; }
    .rn-sales-history-table tbody tr:first-child td { border-top: 0 !important; }
    .rn-sales-history-table tbody tr:nth-child(even) { background: #f9fbfe; }
    .rn-sales-history-table tbody tr:hover { background: #f1f6ff; }
    .rn-sales-history-table .rn-check-col { width: 40px; min-width: 40px; text-align: center; }
    .rn-sales-history-table .rn-row-number { width: 38px; min-width: 38px; color: #71819a; text-align: center; }
    .rn-sales-history-table .rn-invoice-cell { width: 128px; min-width: 0; color: #263d5d; font-size: 11px; font-weight: 650; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .rn-sales-history-table .rn-customer-cell { width: 142px; min-width: 0; }
    .rn-sales-history-table .rn-truncate { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rn-sales-history-table .rn-salesperson-cell { width: 92px; min-width: 0; }
    .rn-sales-history-table .rn-amount-cell { width: 78px; min-width: 0; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rn-sales-history-table .rn-status-cell { width: 82px; min-width: 0; text-align: center; }
    .rn-sales-history-table .rn-status-badge { display: inline-flex; min-width: 72px; min-height: 27px; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 7px; font-size: 12px; font-weight: 700; }
    .rn-sales-history-table .rn-status-paid { color: #14804a; background: #dcf6e8; }
    .rn-sales-history-table .rn-status-partial { color: #946200; background: #fff0bd; }
    .rn-sales-history-table .rn-status-due { color: #b42318; background: #ffe0df; }
    .rn-sales-history-table .rn-date-cell { width: 92px; min-width: 0; white-space: nowrap; }
    .rn-sales-history-table .rn-actions-cell { min-width: 0; width: 160px; }
    .rn-sales-history-table .rn-actions { display: flex; align-items: center; justify-content: center; gap: 5px; }
    .rn-sales-history-table .rn-action-btn { display: inline-flex; width: 30px; height: 32px; flex: 0 0 30px; align-items: center; justify-content: center; padding: 0; border: 1px solid #c8d8f1; border-radius: 7px; color: #1765ef; background: #fff; font-size: 15px; text-decoration: none; transition: background-color .15s ease, border-color .15s ease, color .15s ease; }
    .rn-sales-history-table .rn-action-btn:hover, .rn-sales-history-table .rn-action-btn:focus-visible { border-color: #1765ef; color: #fff; background: #1765ef; outline: none; }
    .rn-sales-history-table .rn-action-return { border-color: #ffd1aa; color: #e96a35; }
    .rn-sales-history-table .rn-action-return:hover, .rn-sales-history-table .rn-action-return:focus-visible { border-color: #e96a35; background: #e96a35; }
    .rn-sales-history-table .rn-action-delete { border-color: #f0c5c5; color: #c62828; }
    .rn-sales-history-table .rn-action-delete:hover, .rn-sales-history-table .rn-action-delete:focus-visible { border-color: #c62828; background: #c62828; }
    .rn-sales-history-table-card .rn-dt-footer { display: flex; min-width: 0; align-items: center; justify-content: space-between; gap: 14px; padding-top: 14px; color: #667b98; font-size: 13px; }
    .rn-sales-history-table-card .dataTables_info { padding: 0; }
    .rn-sales-history-table-card .dataTables_paginate { display: flex; align-items: center; gap: 4px; padding: 0; }
    .rn-sales-history-table-card .dataTables_paginate .paginate_button { min-width: 36px; min-height: 34px; margin: 0 !important; padding: 7px 10px !important; border: 1px solid #d7e1ef !important; border-radius: 7px !important; color: #536985 !important; background: #fff !important; line-height: 1.2; }
    .rn-sales-history-table-card .dataTables_paginate .paginate_button.current { border-color: #1765ef !important; color: #fff !important; background: #1765ef !important; }
    .rn-sales-history-table-card .dataTables_paginate .paginate_button.disabled { opacity: .5; }
    .rn-sales-history .btn:focus-visible, .rn-sales-history input:focus-visible, .rn-sales-history select:focus-visible { outline: 3px solid rgb(52 120 246 / 22%); outline-offset: 1px; }
    @media (max-width: 1199px) {
        /* The shared shell keeps a desktop content offset on some sub-1300px layouts. */
        body:has(.rn-sales-history) .content-page { width: 100%; margin-left: 0 !important; }
        .rn-sales-history-filters { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .rn-sales-history-search-field, .rn-sales-history-date-field { grid-column: span 2; }
        .rn-sales-history-filter-actions { grid-column: span 2; }
    }
    @media (max-width: 767px) {
        .rn-sales-history-header { align-items: flex-start; flex-direction: column; gap: 14px; margin-bottom: 16px; }
        .rn-sales-history-description { margin-left: 0; }
        .rn-sales-history-actions { width: 100%; justify-content: flex-start; }
        .rn-sales-history-filters { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }
        .rn-sales-history-search-field, .rn-sales-history-date-field, .rn-sales-history-filter-actions { grid-column: 1 / -1; }
        .rn-sales-history-card { padding: 16px; }
        .rn-sales-history-table-card { padding: 14px 12px; }
        .rn-sales-history-table-card .rn-dt-footer { align-items: flex-start; flex-direction: column; }
    }
    @media (max-width: 479px) {
        .rn-sales-history-title { font-size: 23px; }
        .rn-sales-history-filters { grid-template-columns: minmax(0,1fr); }
        .rn-sales-history-search-field, .rn-sales-history-date-field, .rn-sales-history-filter-actions { grid-column: 1; }
        .rn-sales-history-filter-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .rn-sales-history-actions .btn { flex: 1 1 auto; }
        .rn-sales-history-date-range { grid-template-columns: minmax(0,1fr); gap: 7px; }
        .rn-sales-history-date-separator { justify-self: center; }
        .rn-sales-history-table-card .rn-dt-toolbar { align-items: flex-start; }
    }
</style>

<main class="rn-sales-history" aria-labelledby="rn-sales-history-title">
    @include('sweetalert::alert')
    <header class="rn-sales-history-header">
        <div>
            <nav class="rn-sales-history-breadcrumb" aria-label="{{ __('sales.sale_navigation') }}">
                <a href="{{ route('saleList') }}">{{ __('navigation.sales_management') }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ __('navigation.sales_history') }}</span>
            </nav>
            <h1 id="rn-sales-history-title" class="rn-sales-history-title"><span class="rn-sales-history-title-icon" aria-hidden="true"><i class="las la-shopping-cart"></i></span>{{ __('navigation.sales_history') }}</h1>
            <p class="rn-sales-history-description">{{ __('sales.sales_history_description') }}</p>
        </div>
        <div class="rn-sales-history-actions">
            <a href="{{ route('newsale') }}" class="btn btn-primary"><i class="las la-plus" aria-hidden="true"></i>{{ __('sales.new_sale') }}</a>
            <button type="button" id="exportCsvBtn" class="btn rn-sales-history-export"><i class="las la-download" aria-hidden="true"></i>{{ __('sales.export_csv') }}</button>
        </div>
    </header>

    <section class="rn-sales-history-card" aria-label="{{ __('sales.sales_history_filters') }}">
        <div class="rn-sales-history-filters">
            <div class="rn-sales-history-field rn-sales-history-search-field">
                <label for="salesHistorySearch">{{ __('common.search') }}</label>
                <div class="rn-sales-history-search-wrap"><i class="las la-search" aria-hidden="true"></i><input id="salesHistorySearch" type="search" class="form-control" placeholder="{{ __('sales.search_sales_history') }}" autocomplete="off"></div>
            </div>
            <div class="rn-sales-history-field">
                <label for="filterCustomer">{{ __('sales.customer') }}</label>
                <select id="filterCustomer" class="form-control rn-sales-history-filter">
                    <option value="">{{ __('common.all_customers') }}</option>
                    @foreach(\App\Models\Customer::orderBy('name')->get() as $filterCustomer)
                        <option value="{{ $filterCustomer->id }}">{{ $filterCustomer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rn-sales-history-field">
                <label for="filterStatus">{{ __('sales.payment_status') }}</label>
                <select id="filterStatus" class="form-control rn-sales-history-filter">
                    <option value="">{{ __('common.all_status') }}</option>
                    <option value="Paid">{{ __('sales.paid') }}</option>
                    <option value="Partial">{{ __('sales.partial') }}</option>
                    <option value="Due">{{ __('sales.due') }}</option>
                </select>
            </div>
            <div class="rn-sales-history-field rn-sales-history-date-field">
                <span class="rn-sales-history-date-label">{{ __('sales.date_range') }}</span>
                <div class="rn-sales-history-date-range">
                    <input type="date" id="salesDateFrom" class="form-control" aria-label="{{ __('common.date_from') }}">
                    <span class="rn-sales-history-date-separator" aria-hidden="true">{{ __('common.to') }}</span>
                    <input type="date" id="salesDateTo" class="form-control" aria-label="{{ __('common.date_to') }}">
                </div>
            </div>
            <div class="rn-sales-history-filter-actions">
                <button type="button" id="applySalesFilters" class="btn btn-primary"><i class="las la-filter" aria-hidden="true"></i>{{ __('common.apply') }}</button>
                <button type="button" id="resetSalesFilters" class="btn rn-sales-history-reset"><i class="las la-redo-alt" aria-hidden="true"></i>{{ __('common.reset') }}</button>
            </div>
        </div>
    </section>

    <section class="rn-sales-history-card rn-sales-history-table-card" aria-label="{{ __('sales.sales_history_records') }}">
        @include('partials.bulk-actions', ['deleteRoute' => 'sales.bulkDelete', 'entity' => __('sales.sales')])
        <div class="rn-sales-history-table-wrap" tabindex="0" role="region" aria-label="{{ __('sales.sales_history_table_scroll') }}">
            @php use Carbon\Carbon; @endphp
            <table id="salesTable" class="table rn-sales-history-table">
                <thead>
                    <tr>
                        <th class="rn-check-col" data-export="false"><input type="checkbox" class="checkbox-input" id="selectAllSales" aria-label="{{ __('sales.select_all_sales') }}"></th>
                        <th class="rn-row-number">#</th>
                        <th>{{ __('sales.invoice') }}</th>
                        <th>{{ __('sales.customer') }}</th>
                        <th>{{ __('sales.salesperson') }}</th>
                        <th class="rn-amount-cell">{{ __('sales.grand_total') }}</th>
                        <th class="rn-amount-cell">{{ __('sales.paid') }}</th>
                        <th class="rn-amount-cell">{{ __('sales.due') }}</th>
                        <th class="rn-status-cell">{{ __('sales.status') }}</th>
                        <th>{{ __('sales.date') }}</th>
                        <th class="rn-actions-cell" data-export="false">{{ __('sales.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($saleList ?? collect()) as $sl)
                        @php
                            $customer = \App\Models\Customer::find($sl->customerId);
                            $customerName = $customer ? $customer->name : '-';
                            $grand = \App\Support\Currency::format($sl->grandTotal ?? 0);
                            $paid = \App\Support\Currency::format($sl->paidAmount ?? 0);
                            $due = \App\Support\Currency::format($sl->curDue ?? 0);
                            $status = 'Due';
                            if (isset($sl->curDue) && (float) $sl->curDue <= 0) {
                                $status = 'Paid';
                            } elseif (isset($sl->paidAmount) && (float) $sl->paidAmount > 0) {
                                $status = 'Partial';
                            }
                            try {
                                $dateSort = Carbon::parse($sl->date)->format('Y-m-d');
                                $dateFormatted = Carbon::parse($sl->date)->format('d M Y');
                            } catch (\Throwable $e) {
                                $dateSort = '';
                                $dateFormatted = (string) ($sl->date ?? '-');
                            }
                        @endphp
                        <tr data-customer="{{ $sl->customerId ?? '' }}" data-status="{{ $status }}" data-date="{{ $dateSort }}">
                            <td class="rn-check-col" data-export="false"><input type="checkbox" class="checkbox-input bulk-select" value="{{ $sl->id }}" aria-label="{{ __('sales.select_sale_invoice', ['invoice' => $sl->invoice]) }}"></td>
                            <td class="rn-row-number" data-order="{{ $sl->id }}">{{ $loop->iteration }}</td>
                            <td class="rn-invoice-cell" title="{{ $sl->invoice ?? '-' }}"><span class="rn-truncate">{{ $sl->invoice ?? '-' }}</span></td>
                            <td class="rn-customer-cell" title="{{ $customerName }}"><span class="rn-truncate">{{ $customerName }}</span></td>
                            <td class="rn-salesperson-cell" title="{{ optional($sl->salesperson)->fullName ?? '-' }}"><span class="rn-truncate">{{ optional($sl->salesperson)->fullName ?? '-' }}</span></td>
                            <td class="rn-amount-cell">{{ $grand }}</td>
                            <td class="rn-amount-cell">{{ $paid }}</td>
                            <td class="rn-amount-cell">{{ $due }}</td>
                            <td class="rn-status-cell">
                                @if($status === 'Paid')
                                    <span class="rn-status-badge rn-status-paid">{{ __('sales.paid') }}</span>
                                @elseif($status === 'Partial')
                                    <span class="rn-status-badge rn-status-partial">{{ __('sales.partial') }}</span>
                                @else
                                    <span class="rn-status-badge rn-status-due">{{ __('sales.due') }}</span>
                                @endif
                            </td>
                            <td class="rn-date-cell" data-order="{{ $dateSort }}">{{ $dateFormatted }}</td>
                            <td class="rn-actions-cell" data-export="false">
                                <div class="rn-actions">
                                    <a class="rn-action-btn" href="{{ route('invoiceGenerate', ['id' => $sl->id]) }}" title="{{ __('sales.print') }}" aria-label="{{ __('sales.print') }}"><i class="las la-print" aria-hidden="true"></i></a>
                                    <a class="rn-action-btn rn-action-return" href="{{ route('returnSale', ['id' => $sl->id]) }}" title="{{ __('common.return') }}" aria-label="{{ __('common.return') }}"><i class="las la-undo" aria-hidden="true"></i></a>
                                    @canEdit(true)
                                        <a class="rn-action-btn" href="{{ route('sale.edit', ['id' => $sl->id]) }}" title="{{ __('sales.edit') }}" aria-label="{{ __('sales.edit') }}"><i class="las la-edit" aria-hidden="true"></i></a>
                                        <form method="POST" action="{{ route('delSale', ['id' => $sl->id]) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rn-action-btn rn-action-delete" data-confirm="delete" title="{{ __('sales.delete') }}" aria-label="{{ __('sales.delete') }}"><i class="las la-trash-alt" aria-hidden="true"></i></button>
                                        </form>
                                    @endcanEdit
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@section('scripts')
@include('partials.bulk-actions-script')
<script>
(function () {
    function initializeSalesHistory() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) return;

        var tableElement = document.getElementById('salesTable');
        if (!tableElement) return;

        var $table = jQuery(tableElement);
        var dt = jQuery.fn.DataTable.isDataTable(tableElement) ? $table.DataTable() : $table.DataTable({
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            processing: true,
            responsive: false,
            order: [[1, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 1, 10] },
                { className: 'text-right', targets: [5, 6, 7] }
            ],
            dom: '<"rn-dt-toolbar"l>t<"rn-dt-footer"ip>',
            drawCallback: function () {
                var info = this.api().page.info();
                this.api().column(1, { page: 'current' }).nodes().each(function (cell, index) {
                    cell.textContent = info.start + index + 1;
                });
            }
        });

        var extSearch = jQuery.fn.dataTable.ext.search;
        if (!extSearch.some(function (filter) { return filter._rnSalesHistoryFilter === true; })) {
            var historyFilter = function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'salesTable') return true;
                var row = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
                if (!row) return true;

                var customer = document.getElementById('filterCustomer').value;
                var status = document.getElementById('filterStatus').value;
                var from = document.getElementById('salesDateFrom').value;
                var to = document.getElementById('salesDateTo').value;
                var date = row.getAttribute('data-date') || '';

                if (customer && row.getAttribute('data-customer') !== customer) return false;
                if (status && row.getAttribute('data-status') !== status) return false;
                if (from && (!date || date < from)) return false;
                if (to && (!date || date > to)) return false;
                return true;
            };
            historyFilter._rnSalesHistoryFilter = true;
            extSearch.push(historyFilter);
        }

        var search = document.getElementById('salesHistorySearch');
        var customer = document.getElementById('filterCustomer');
        var status = document.getElementById('filterStatus');
        var from = document.getElementById('salesDateFrom');
        var to = document.getElementById('salesDateTo');
        var apply = document.getElementById('applySalesFilters');
        var reset = document.getElementById('resetSalesFilters');

        function applyFilters() {
            dt.search(search.value || '').draw();
        }

        search.addEventListener('input', applyFilters);
        [customer, status, from, to].forEach(function (control) {
            control.addEventListener('change', applyFilters);
        });
        apply.addEventListener('click', applyFilters);
        reset.addEventListener('click', function () {
            search.value = '';
            customer.value = '';
            status.value = '';
            from.value = '';
            to.value = '';
            dt.search('').order([[1, 'desc']]).page('first').draw();
        });

        document.getElementById('exportCsvBtn').addEventListener('click', function () {
            var headers = Array.from(tableElement.tHead.rows[0].cells)
                .filter(function (cell) { return cell.getAttribute('data-export') !== 'false'; })
                .map(function (cell) { return cell.textContent.trim(); });
            var lines = [headers.map(csvCell).join(',')];
            var rows = dt.rows({ search: 'applied', order: 'applied' }).nodes().toArray();
            rows.forEach(function (row, index) {
                var cells = Array.from(row.cells).filter(function (cell) { return cell.getAttribute('data-export') !== 'false'; });
                var values = cells.map(function (cell, cellIndex) {
                    return cellIndex === 0 ? String(index + 1) : cell.textContent.trim();
                });
                lines.push(values.map(csvCell).join(','));
            });
            var blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'sales-export-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        });
    }

    function csvCell(value) {
        var safe = String(value == null ? '' : value).replace(/"/g, '""');
        if (/^[=+\-@\t\r]/.test(safe)) safe = "'" + safe;
        return '"' + safe + '"';
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeSalesHistory);
    else initializeSalesHistory();
})();
</script>
@endsection
