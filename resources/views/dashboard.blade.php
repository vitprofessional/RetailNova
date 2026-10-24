@extends('include')
@section('backTitle'){{ __('common.heading_dashboard') }}@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@endpush

@section('container')
@php
    $hour = (int) \Carbon\Carbon::now()->format('H');
    $greeting = $hour >= 5 && $hour < 12 ? __('dashboard.good_morning')
        : ($hour >= 12 && $hour < 17 ? __('dashboard.good_afternoon')
        : ($hour >= 17 && $hour < 21 ? __('dashboard.good_evening') : __('dashboard.good_night')));
    $formatDashboardMoney = static function ($amount) use ($dashboardCurrencySymbol, $dashboardCurrencyPosition, $dashboardCurrencyNegParentheses) {
        $number = is_numeric($amount) ? (float) $amount : 0.0;
        $formatted = number_format(abs($number), 2, '.', ',');
        $value = $dashboardCurrencyPosition === 'right'
            ? $formatted . "\u{00A0}" . $dashboardCurrencySymbol
            : $dashboardCurrencySymbol . $formatted;
        return $number < 0
            ? ($dashboardCurrencyNegParentheses ? '(' . $value . ')' : '-' . $value)
            : $value;
    };
@endphp
@mobile
@include('partials.mobile-home')
@else
<main class="rn-dashboard" data-currency-symbol="{{ $dashboardCurrencySymbol }}" data-currency-position="{{ $dashboardCurrencyPosition }}" data-currency-negative-parentheses="{{ $dashboardCurrencyNegParentheses ? 'true' : 'false' }}">
    <section class="rn-dashboard-intro" aria-labelledby="rn-dashboard-title">
        <div class="rn-dashboard-welcome">
            <span class="rn-dashboard-welcome-icon" aria-hidden="true"><i class="ri-sun-line"></i></span>
            <div class="rn-dashboard-welcome-copy">
                <p class="rn-dashboard-eyebrow">{{ $greeting }}</p>
                <h1 id="rn-dashboard-title">{{ __('dashboard.hello', ['name' => optional($adminUser)->fullName ?? __('dashboard.guest')]) }}</h1>
                <p class="rn-dashboard-subtitle">{{ __('dashboard.your_dashboard_highlights_sales_purchases_cash_flow_stock_and_more') }}</p>
            </div>
        </div>
        <div class="rn-dashboard-filters" aria-label="{{ __('dashboard.range') }}">
            <div class="rn-dashboard-filter-field">
                <label for="rn-range">{{ __('dashboard.range') }}</label>
                <select id="rn-range" class="form-control">
                    <option value="today">{{ __('dashboard.today') }}</option>
                    <option value="week">{{ __('dashboard.this_week') }}</option>
                    <option value="month" selected>{{ __('dashboard.this_month') }}</option>
                    <option value="year">{{ __('dashboard.this_year') }}</option>
                    <option value="custom">{{ __('dashboard.custom') }}</option>
                </select>
            </div>
            <div id="rn-custom-range" class="rn-dashboard-custom-range" hidden>
                <div class="rn-dashboard-filter-field">
                    <label for="rn-start">{{ __('dashboard.from') }}</label>
                    <input type="date" id="rn-start" class="form-control">
                </div>
                <div class="rn-dashboard-filter-field">
                    <label for="rn-end">{{ __('dashboard.to') }}</label>
                    <input type="date" id="rn-end" class="form-control">
                </div>
            </div>
            <button id="rn-apply" class="btn btn-primary" type="button"><i class="ri-refresh-line" aria-hidden="true"></i>{{ __('dashboard.apply') }}</button>
        </div>
    </section>

    <section class="rn-dashboard-kpi-grid" aria-label="{{ __('dashboard.key_metrics') }}">
        <article class="rn-dashboard-kpi rn-kpi-blue">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-user-3-line"></i></span>
            <div><p>{{ __('dashboard.customer_opening_balance') }}</p><h2 data-rn-money="{{ $customerOpeningTotal ?? 0 }}">{{ $formatDashboardMoney($customerOpeningTotal ?? 0) }}</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-violet">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-truck-line"></i></span>
            <div><p>{{ __('dashboard.supplier_opening_balance') }}</p><h2 data-rn-money="{{ $supplierOpeningTotal ?? 0 }}">{{ $formatDashboardMoney($supplierOpeningTotal ?? 0) }}</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-blue">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-shopping-bag-3-line"></i></span>
            <div><p>{{ __('dashboard.sales') }}</p><h2 id="rn-sales">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-green">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-inbox-archive-line"></i></span>
            <div><p>{{ __('dashboard.purchase') }}</p><h2 id="rn-purchases">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-teal">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-hand-coin-line"></i></span>
            <div><p>{{ __('dashboard.received') }}</p><h2 id="rn-receipts">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-amber">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-bank-card-line"></i></span>
            <div><p>{{ __('dashboard.payment') }}</p><h2 id="rn-payments">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-rose">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-bill-line"></i></span>
            <div><p>{{ __('dashboard.expense') }}</p><h2 id="rn-expenses">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-teal">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-exchange-dollar-line"></i></span>
            <div><p>{{ __('dashboard.cash_flow') }}</p><h2 id="rn-cashflow">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-violet">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-line-chart-line"></i></span>
            <div><p>{{ __('dashboard.net_sale') }}</p><h2 id="rn-net">0.00</h2></div>
        </article>
        <article class="rn-dashboard-kpi rn-kpi-slate">
            <span class="rn-kpi-icon" aria-hidden="true"><i class="ri-stack-line"></i></span>
            <div><p>{{ __('dashboard.total_stock_qty') }}</p><h2 id="rn-stock-total">0</h2></div>
        </article>
    </section>

    <section class="rn-dashboard-panels rn-dashboard-panels-primary">
        <article class="rn-dashboard-panel rn-dashboard-chart-panel">
            <header class="rn-dashboard-panel-header">
                <div><h2>{{ __('dashboard.financial_report') }}</h2><p>{{ __('dashboard.sales_purchases_expenses_over_time') }}</p></div>
                <span class="rn-dashboard-period">{{ __('dashboard.this_month') }}</span>
            </header>
            <div class="rn-dashboard-chart"><canvas id="rn-financial-chart"></canvas></div>
        </article>
        <article class="rn-dashboard-panel rn-dashboard-invoices-panel">
            <header class="rn-dashboard-panel-header">
                <div><h2>{{ __('dashboard.latest_invoices') }}</h2><p>{{ __('dashboard.recent_sales_activity') }}</p></div>
                <a href="{{ route('saleList') }}" class="rn-dashboard-panel-link">{{ __('dashboard.view_sales') }}<i class="ri-arrow-right-line" aria-hidden="true"></i></a>
            </header>
            <div class="table-responsive">
                <table class="rn-dashboard-table">
                    <thead><tr><th>{{ __('dashboard.invoice') }}</th><th>{{ __('dashboard.customer') }}</th><th class="text-right">{{ __('dashboard.total') }}</th></tr></thead>
                    <tbody id="rn-latest-invoices"><tr><td colspan="3" class="rn-dashboard-loading">{{ __('dashboard.loading') }}</td></tr></tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="rn-dashboard-panels rn-dashboard-panels-secondary">
        <article class="rn-dashboard-panel rn-dashboard-low-stock-panel">
            <header class="rn-dashboard-panel-header">
                <div><h2>{{ __('dashboard.low_stock_products') }}</h2><p>{{ __('dashboard.products_below_alert_quantity') }}</p></div>
                <span class="rn-dashboard-section-icon rn-tone-rose" aria-hidden="true"><i class="ri-error-warning-line"></i></span>
            </header>
            <ul class="rn-dashboard-stock-list" id="rn-low-stock"><li class="rn-dashboard-loading">{{ __('dashboard.loading') }}</li></ul>
        </article>
        <article class="rn-dashboard-panel rn-dashboard-top-products-panel">
            <header class="rn-dashboard-panel-header">
                <div><h2>{{ __('dashboard.top_products') }}</h2><p>{{ __('dashboard.this_month') }}</p></div>
                <span class="rn-dashboard-section-icon rn-tone-blue" aria-hidden="true"><i class="ri-bar-chart-grouped-line"></i></span>
            </header>
            <div class="rn-dashboard-empty-state">
                <span aria-hidden="true"><i class="ri-bar-chart-box-line"></i></span>
                <p>{{ __('dashboard.no_top_products_available') }}</p>
            </div>
        </article>
    </section>
</main>
@endmobile
@endsection

@section('scripts')
@desktop
<script src="{{ \App\Support\VendorAsset::url('chartjs/chart.umd.min.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js') }}"></script>
<script>
    (function(){
        var dashboard = document.querySelector('.rn-dashboard');
        var currencySymbol = dashboard ? dashboard.dataset.currencySymbol : '';
        var currencyPosition = dashboard ? dashboard.dataset.currencyPosition : 'left';
        var useNegativeParentheses = dashboard ? dashboard.dataset.currencyNegativeParentheses === 'true' : true;
        var fmtMoney = function(n){
            var amount = Number(n) || 0;
            var formatted = new Intl.NumberFormat(document.documentElement.lang || undefined, { minimumFractionDigits:2, maximumFractionDigits:2 }).format(Math.abs(amount));
            var value = currencyPosition === 'right' ? formatted + '\u00a0' + currencySymbol : currencySymbol + formatted;
            return amount < 0 ? (useNegativeParentheses ? '(' + value + ')' : '-' + value) : value;
        };
        document.querySelectorAll('[data-rn-money]').forEach(function(el){el.textContent=fmtMoney(el.dataset.rnMoney);});
        var $range = document.getElementById('rn-range');
        var $custom = document.getElementById('rn-custom-range');
        var $start = document.getElementById('rn-start');
        var $end = document.getElementById('rn-end');
        var $apply = document.getElementById('rn-apply');
        if($range){
            $range.addEventListener('change', function(){ $custom.hidden = this.value !== 'custom'; });
        }

        var chart, ctx = document.getElementById('rn-financial-chart');
        function renderChart(labels, sales, purchases, expenses){
            if(!ctx || typeof Chart === 'undefined') return;
            if(chart){ chart.destroy(); }
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: @json(__('dashboard.sales')), data: sales, borderColor: '#3478f6', backgroundColor: 'rgba(52,120,246,.1)', tension:.32, fill:true, pointRadius:2 },
                        { label: @json(__('dashboard.purchase')), data: purchases, borderColor: '#16a579', backgroundColor: 'rgba(22,165,121,.08)', tension:.32, fill:true, pointRadius:2 },
                        { label: @json(__('dashboard.expense')), data: expenses, borderColor: '#e35d6a', backgroundColor: 'rgba(227,93,106,.06)', tension:.32, fill:false, pointRadius:2 }
                    ]
                },
                options: { responsive:true, maintainAspectRatio:false, interaction:{intersect:false,mode:'index'}, plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,padding:18}},tooltip:{callbacks:{label:function(context){return context.dataset.label+': '+fmtMoney(context.parsed.y);}}}}, scales:{y:{beginAtZero:true,grid:{color:'#edf1f7'},ticks:{maxTicksLimit:5,callback:function(value){return fmtMoney(value);}}},x:{grid:{display:false},ticks:{maxTicksLimit:8}}} }
            });
        }
        function setText(id, value){ var el=document.getElementById(id); if(el) el.textContent=value; }
        function loadMetrics(){
            var params = new URLSearchParams();
            var range = ($range && $range.value) || 'month';
            params.set('range', range);
            if(range === 'custom'){
                if($start && $start.value) params.set('start', $start.value);
                if($end && $end.value) params.set('end', $end.value);
            }
            fetch("{{ route('dashboard.metrics') }}?"+params.toString(), {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'})
                .then(function(response){ if(!response.ok) throw new Error('Dashboard metrics unavailable'); return response.json(); })
                .then(function(json){
                    var totals=json.totals||{};
                    setText('rn-sales',fmtMoney(totals.sales||0)); setText('rn-purchases',fmtMoney(totals.purchases||0));
                    setText('rn-receipts',fmtMoney(totals.receipts||0)); setText('rn-payments',fmtMoney(totals.payments||0));
                    setText('rn-expenses',fmtMoney(totals.expenses||0)); setText('rn-cashflow',fmtMoney(totals.cash_flow||0));
                    setText('rn-net',fmtMoney(totals.net_sales||0));
                    var stock=json.stock||{}; setText('rn-stock-total',(stock.total_quantity||0));

                    var invoiceList=document.getElementById('rn-latest-invoices');
                    if(invoiceList){
                        invoiceList.replaceChildren();
                        (json.latest_invoices||[]).forEach(function(invoice){
                            var row=document.createElement('tr');
                            var number=document.createElement('td'); number.textContent=invoice.invoiceNo||'—';
                            var customer=document.createElement('td'); customer.textContent=invoice.customer||'—';
                            var amount=document.createElement('td'); amount.className='text-right'; amount.textContent=fmtMoney(invoice.total||0);
                            row.append(number,customer,amount); invoiceList.appendChild(row);
                        });
                        if(!invoiceList.children.length){ var row=document.createElement('tr'); var cell=document.createElement('td'); cell.colSpan=3; cell.className='rn-dashboard-loading'; cell.textContent=@json(__('dashboard.no_invoices')); row.appendChild(cell); invoiceList.appendChild(row); }
                    }

                    var lowStock=document.getElementById('rn-low-stock');
                    if(lowStock){
                        lowStock.replaceChildren();
                        (stock.low_stock||[]).forEach(function(product){
                            var item=document.createElement('li'); item.className='rn-dashboard-stock-item';
                            var name=document.createElement('span'); name.className='rn-dashboard-stock-name'; name.textContent=product.name||'—';
                            var quantity=document.createElement('span'); quantity.className='rn-dashboard-stock-quantity'; quantity.textContent=String(product.stock)+' / '+String(product.alert);
                            item.append(name,quantity); lowStock.appendChild(item);
                        });
                        if(!lowStock.children.length){ var item=document.createElement('li'); item.className='rn-dashboard-loading'; item.textContent=@json(__('dashboard.no_low_stock_items')); lowStock.appendChild(item); }
                    }
                    var chartData=json.chart||{}; var series=chartData.series||{};
                    renderChart(chartData.labels||[],series.sales||[],series.purchases||[],series.expenses||[]);
                })
                .catch(function(){
                    var invoiceList=document.getElementById('rn-latest-invoices');
                    if(invoiceList){ var row=document.createElement('tr'); var cell=document.createElement('td'); cell.colSpan=3; cell.className='rn-dashboard-loading'; cell.textContent=@json(__('dashboard.metrics_unavailable')); row.appendChild(cell); invoiceList.replaceChildren(row); }
                    var lowStock=document.getElementById('rn-low-stock');
                    if(lowStock){ var item=document.createElement('li'); item.className='rn-dashboard-loading'; item.textContent=@json(__('dashboard.metrics_unavailable')); lowStock.replaceChildren(item); }
                });
        }
        if($apply) $apply.addEventListener('click',loadMetrics);
        loadMetrics();
    })();
</script>
@enddesktop
@endsection
