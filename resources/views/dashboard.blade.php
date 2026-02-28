@extends('include')
@section('backTitle')
Dashboard
@endsection
@section('container')
<div class="row">
            <div class="col-lg-12">
                <div class="card card-transparent card-block card-stretch card-height border-none">
                    <div class="card-body p-0 mt-lg-2 mt-0">
                        <h3 class="mb-1">Hi {{ optional($adminUser)->fullName ?? 'there' }},
                            @php
                                $hour = \Carbon\Carbon::now()->format('H');
                                if ($hour >= 5 && $hour < 12) {
                                    echo 'Good Morning! 🌅';
                                } elseif ($hour >= 12 && $hour < 17) {
                                    echo 'Good Afternoon! ☀️';
                                } elseif ($hour >= 17 && $hour < 21) {
                                    echo 'Good Evening! 🌇';
                                } else {
                                    echo 'Good Night! 🌙';
                                }
                            @endphp
                        </h3>
                        <p class="mb-3 mr-4">Your dashboard highlights sales, purchases, cash flow, stock, and more.</p>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="form-inline mr-2 mb-2">
                                <label class="mr-2 mb-0">Range</label>
                                <select id="rn-range" class="form-control form-control-sm">
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month" selected>This Month</option>
                                    <option value="year">This Year</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div id="rn-custom-range" class="form-inline mr-2 mb-2" style="display:none;">
                                <label class="mr-2 mb-0">From</label>
                                <input type="date" id="rn-start" class="form-control form-control-sm mr-2">
                                <label class="mr-2 mb-0">To</label>
                                <input type="date" id="rn-end" class="form-control form-control-sm mr-2">
                            </div>
                            <button id="rn-apply" class="btn btn-primary btn-sm mb-2">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <!-- Opening balances -->
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-primary" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Customer Opening Balance</p>
                                        <h4>@money($customerOpeningTotal ?? 0)</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-warning" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Supplier Opening Balance</p>
                                        <h4>@money($supplierOpeningTotal ?? 0)</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Dynamic metric cards -->
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-secondary" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Sales</p>
                                        <h4 id="rn-sales">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-success" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Purchase</p>
                                        <h4 id="rn-purchases">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-primary" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Received</p>
                                        <h4 id="rn-receipts">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-danger" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Payment</p>
                                        <h4 id="rn-payments">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-dark">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Expense</p>
                                        <h4 id="rn-expenses">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-primary iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-info" role="alert" style="background:#17a2b8 !important;">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Cash Flow</p>
                                        <h4 id="rn-cashflow">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-secondary" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Net Sale</p>
                                        <h4 id="rn-net">0.00</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-dark iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert text-white bg-dark" role="alert">
                            <div class="iq-alert-text  card-body">
                                <div class="d-flex align-items-center card-total-sale">
                                    <div>
                                        <p class="mb-2">Total Stock Qty</p>
                                        <h4 class="text-white" id="rn-stock-total">0</h4>
                                    </div>
                                </div>
                                <div class="iq-progress-bar">
                                    <span class="bg-primary iq-progress progress-1" data-percent="70"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Financial Report</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="position:relative;height:280px;">
                            <canvas id="rn-financial-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Latest Invoices</h4>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="rn-latest-invoices">
                                    <tr><td colspan="3" class="text-center">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Low Stock Products</h4>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="rn-low-stock">
                            <li class="list-group-item">Loading...</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Top Products</h4>
                        </div>
                        <div class="card-header-toolbar d-flex align-items-center">
                            <div class="dropdown">
                                <span class="dropdown-toggle dropdown-bg btn" id="dropdownMenuButton006"
                                    data-toggle="dropdown">
                                    This Month<i class="ri-arrow-down-s-line ml-1"></i>
                                </span>
                                <div class="dropdown-menu dropdown-menu-right shadow-none"
                                    aria-labelledby="dropdownMenuButton006">
                                    <a class="dropdown-item" href="#">Year</a>
                                    <a class="dropdown-item" href="#">Month</a>
                                    <a class="dropdown-item" href="#">Week</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled row top-product mb-0">
                            <li class="col-lg-3">
                                <div class="card card-block card-stretch card-height mb-0">
                                    <div class="card-body">
                                        <div class="bg-warning-light rounded">
                                            <img src="{{ asset('public/eshop/assets/') }}/images/product/01.png" class="style-img img-fluid m-auto p-3" alt="image">
                                        </div>
                                        <div class="style-text text-left mt-3">
                                            <h5 class="mb-1">Organic Cream</h5>
                                            <p class="mb-0">789 Item</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-3">
                                <div class="card card-block card-stretch card-height mb-0">
                                    <div class="card-body">
                                        <div class="bg-danger-light rounded">
                                            <img src="{{ asset('public/eshop/assets/') }}/images/product/02.png" class="style-img img-fluid m-auto p-3" alt="image">
                                        </div>
                                        <div class="style-text text-left mt-3">
                                            <h5 class="mb-1">Rain Umbrella</h5>
                                            <p class="mb-0">657 Item</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-3">
                                <div class="card card-block card-stretch card-height mb-0">
                                    <div class="card-body">
                                        <div class="bg-info-light rounded">
                                            <img src="{{ asset('public/eshop/assets/') }}/images/product/03.png" class="style-img img-fluid m-auto p-3" alt="image">
                                        </div>
                                        <div class="style-text text-left mt-3">
                                            <h5 class="mb-1">Serum Bottle</h5>
                                            <p class="mb-0">489 Item</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-3">
                                <div class="card card-block card-stretch card-height mb-0">
                                    <div class="card-body">
                                        <div class="bg-success-light rounded">
                                            <img src="{{ asset('public/eshop/assets/') }}/images/product/02.png" class="style-img img-fluid m-auto p-3" alt="image">
                                        </div>
                                        <div class="style-text text-left mt-3">
                                            <h5 class="mb-1">Organic Cream</h5>
                                            <p class="mb-0">468 Item</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Removed placeholder last sale/purchase/transaction cards -->
            </div>
            <div class="row">        
            <!-- Removed placeholder income/expense charts -->
            </div>
            <!-- Removed placeholder order summary section -->
        </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function(){
        var fmtMoney = function(n){
            try{ return new Intl.NumberFormat(undefined, { style:'currency', currency: (window.APP_CURRENCY||'USD') }).format(n||0); }catch(e){ return (n||0).toFixed(2); }
        };

        var $range = document.getElementById('rn-range');
        var $custom = document.getElementById('rn-custom-range');
        var $start = document.getElementById('rn-start');
        var $end = document.getElementById('rn-end');
        var $apply = document.getElementById('rn-apply');

        if($range){
            $range.addEventListener('change', function(){ $custom.style.display = this.value === 'custom' ? '' : 'none'; });
        }

        var chart, ctx = document.getElementById('rn-financial-chart');
        function renderChart(labels, sales, purchases, expenses){
            if(!ctx) return;
            if(chart){ chart.destroy(); }
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Sales', data: sales, borderColor: '#6c757d', backgroundColor: 'rgba(108,117,125,0.1)', tension:.3 },
                        { label: 'Purchases', data: purchases, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', tension:.3 },
                        { label: 'Expenses', data: expenses, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', tension:.3 },
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        }

        function setText(id, v){ var el = document.getElementById(id); if(el) el.textContent = v; }

        function loadMetrics(){
            var params = new URLSearchParams();
            var r = ($range && $range.value) || 'month';
            params.set('range', r);
            if(r === 'custom'){
                if($start && $start.value) params.set('start', $start.value);
                if($end && $end.value) params.set('end', $end.value);
            }
            fetch("{{ route('dashboard.metrics') }}?"+params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
              .then(function(res){ return res.json(); })
              .then(function(json){
                var t = json.totals || {};
                setText('rn-sales', fmtMoney(t.sales||0));
                setText('rn-purchases', fmtMoney(t.purchases||0));
                setText('rn-receipts', fmtMoney(t.receipts||0));
                setText('rn-payments', fmtMoney(t.payments||0));
                setText('rn-expenses', fmtMoney(t.expenses||0));
                setText('rn-cashflow', fmtMoney(t.cash_flow||0));
                setText('rn-net', fmtMoney(t.net_sales||0));

                var stock = json.stock || {};
                setText('rn-stock-total', (stock.total_quantity||0));

                var list = document.getElementById('rn-latest-invoices');
                if(list){
                    list.innerHTML = '';
                    (json.latest_invoices||[]).forEach(function(i){
                        var tr = document.createElement('tr');
                        tr.innerHTML = '<td>'+ (i.invoiceNo||'-') +'</td>'+
                                       '<td>'+ (i.customer||'-') +'</td>'+
                                       '<td class="text-right">'+ fmtMoney(i.total||0) +'</td>';
                        list.appendChild(tr);
                    });
                    if(!list.children.length){
                        var tr = document.createElement('tr');
                        tr.innerHTML = '<td colspan="3" class="text-center">No invoices</td>';
                        list.appendChild(tr);
                    }
                }

                var low = document.getElementById('rn-low-stock');
                if(low){
                    low.innerHTML = '';
                    (stock.low_stock||[]).forEach(function(p){
                        var li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = '<span>'+ p.name +'</span><span class="badge badge-danger badge-pill">'+ p.stock +'/'+ p.alert +'</span>';
                        low.appendChild(li);
                    });
                    if(!low.children.length){
                        var li = document.createElement('li'); li.className = 'list-group-item'; li.textContent = 'No low stock items'; low.appendChild(li);
                    }
                }

                var ch = json.chart || {}; renderChart(ch.labels||[], (ch.series||{}).sales||[], (ch.series||{}).purchases||[], (ch.series||{}).expenses||[]);
              })
              .catch(function(){ /* silently ignore */ });
        }

        if($apply){ $apply.addEventListener('click', loadMetrics); }
        // Initial load
        loadMetrics();
    })();
</script>
@endsection