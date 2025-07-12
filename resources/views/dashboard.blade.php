@extends('include')
@section('backTitle')
Dashboard
@endsection
@section('container')
<div class="row">
            <div class="col-lg-12">
                <div class="card card-transparent card-block card-stretch card-height border-none">
                    <div class="card-body p-0 mt-lg-2 mt-0">
                        <h3 class="mb-3">Hi Hasnat, 
                            @php
                                $hour = \Carbon\Carbon::now()->format('H'); // 24-hour format

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
                        <p class="mb-0 mr-4">Your dashboard gives you views of key performance or business process.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Sales</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Purchase</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Received</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Payment</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
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
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">My Account</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Sales Return</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Service</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Sales Invoice</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">Purchases Invoice</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 ">
                        <div class="alert alert-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center  card-total-sale">
                                    <div>
                                        <p class="mb-2">net sale</p>
                                        <h4>31.50</h4>
                                    </div>
                                </div>                                
                                <div class="iq-progress-bar">
                                    <span class="bg-info iq-progress progress-1" data-percent="70">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Overview</h4>
                        </div>                        
                        <div class="card-header-toolbar d-flex align-items-center">
                            <div class="dropdown">
                                <span class="dropdown-toggle dropdown-bg btn" id="dropdownMenuButton001"
                                    data-toggle="dropdown">
                                    This Month<i class="ri-arrow-down-s-line ml-1"></i>
                                </span>
                                <div class="dropdown-menu dropdown-menu-right shadow-none"
                                    aria-labelledby="dropdownMenuButton001">
                                    <a class="dropdown-item" href="#">Year</a>
                                    <a class="dropdown-item" href="#">Month</a>
                                    <a class="dropdown-item" href="#">Week</a>
                                </div>
                            </div>
                        </div>
                    </div>                    
                    <div class="card-body">
                        <div id="layout1-chart1"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Revenue Vs Cost</h4>
                        </div>
                        <div class="card-header-toolbar d-flex align-items-center">
                            <div class="dropdown">
                                <span class="dropdown-toggle dropdown-bg btn" id="dropdownMenuButton002"
                                    data-toggle="dropdown">
                                    This Month<i class="ri-arrow-down-s-line ml-1"></i>
                                </span>
                                <div class="dropdown-menu dropdown-menu-right shadow-none"
                                    aria-labelledby="dropdownMenuButton002">
                                    <a class="dropdown-item" href="#">Yearly</a>
                                    <a class="dropdown-item" href="#">Monthly</a>
                                    <a class="dropdown-item" href="#">Weekly</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="layout1-chart-2" style="min-height: 360px;"></div>
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
                                            <img src="../assets/images/product/01.png" class="style-img img-fluid m-auto p-3" alt="image">
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
                                            <img src="../assets/images/product/02.png" class="style-img img-fluid m-auto p-3" alt="image">
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
                                            <img src="../assets/images/product/03.png" class="style-img img-fluid m-auto p-3" alt="image">
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
                                            <img src="../assets/images/product/02.png" class="style-img img-fluid m-auto p-3" alt="image">
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
            <div class="col-lg-4"> 
                <div class="card card-block card-stretch card-height-helf">
                    <div class="card-body card-item-right">
                        <div class="d-flex align-items-top">
                            <div class="style-text text-left">
                                <h5 class="mb-0">Last sale</h5>
                                <p class="mb-0">Total Sell : 45897</p>
                                <p class="mb-0">Reference No : 32589</p>
                                <p class="mb-0">Total Sell : 45897</p>
                                <p class="mb-0">Discount: 1000</p>
                                <p class="mb-0">Paid : 45897</p>
                                <p class="mb-0">Due Amount : $45,89 M</p>
                                <p class="mb-0">Date : 10.10.2025</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height-helf">
                    <div class="card-body card-item-right">
                        <div class="d-flex align-items-top">
                            <div class="style-text text-left">
                                <h5 class="mb-0">Last Purchase</h5>
                                <p class="mb-0">Total Sell : 45897</p>
                                <p class="mb-0">Reference No : 32589</p>
                                <p class="mb-0">Total Sell : 45897</p>
                                <p class="mb-0">Discount: 1000</p>
                                <p class="mb-0">Paid : 45897</p>
                                <p class="mb-0">Due Amount : $45,89 M</p>
                                <p class="mb-0">Date : 10.10.2025</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-block card-stretch card-height-helf">
                    <div class="card-body card-item-right">
                        <div class="d-flex align-items-top">
                            <div class="style-text text-left">
                                <h5 class="mb-0">Last Transaction</h5>
                                <p class="mb-0">Total Sell : 45897</p>
                                <p class="mb-0">Reference No : 32589</p>
                                <p class="mb-0">Total Sell : 45897</p>
                                <p class="mb-0">Discount: 1000</p>
                                <p class="mb-0">Paid : 45897</p>
                                <p class="mb-0">Due Amount : $45,89 M</p>
                                <p class="mb-0">Date : 10.10.2025</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <div class="row">        
            <div class="col-lg-6">  
                <div class="card card-block card-stretch card-height-helf">
                    <div class="card-body">
                        <div class="d-flex align-items-top justify-content-between">
                            <div class="">
                                <p class="mb-0">Income</p>
                                <h5>$ 98,7800 K</h5>
                            </div>
                            <div class="card-header-toolbar d-flex align-items-center">
                                <div class="dropdown">
                                    <span class="dropdown-toggle dropdown-bg btn" id="dropdownMenuButton003"
                                        data-toggle="dropdown">
                                        This Month<i class="ri-arrow-down-s-line ml-1"></i>
                                    </span>
                                    <div class="dropdown-menu dropdown-menu-right shadow-none"
                                        aria-labelledby="dropdownMenuButton003">
                                        <a class="dropdown-item" href="#">Year</a>
                                        <a class="dropdown-item" href="#">Month</a>
                                        <a class="dropdown-item" href="#">Week</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="layout1-chart-3" class="layout-chart-1"></div>
                    </div>
                </div>
            </div>          
            <div class="col-lg-6">  
                <div class="card card-block card-stretch card-height-helf">
                    <div class="card-body">
                        <div class="d-flex align-items-top justify-content-between">
                            <div class="">
                                <p class="mb-0">Expenses</p>
                                <h5>$ 45,8956 K</h5>
                            </div>
                            <div class="card-header-toolbar d-flex align-items-center">
                                <div class="dropdown">
                                    <span class="dropdown-toggle dropdown-bg btn" id="dropdownMenuButton004"
                                        data-toggle="dropdown">
                                        This Month<i class="ri-arrow-down-s-line ml-1"></i>
                                    </span>
                                    <div class="dropdown-menu dropdown-menu-right shadow-none"
                                        aria-labelledby="dropdownMenuButton004">
                                        <a class="dropdown-item" href="#">Year</a>
                                        <a class="dropdown-item" href="#">Month</a>
                                        <a class="dropdown-item" href="#">Week</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="layout1-chart-4" class="layout-chart-2"></div>
                    </div>
                </div>
            </div>
            </div>
            <div class="col-lg-12">  
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Order Summary</h4>
                        </div>                        
                        <div class="card-header-toolbar d-flex align-items-center">
                            <div class="dropdown">
                                <span class="dropdown-toggle dropdown-bg btn" id="dropdownMenuButton005"
                                    data-toggle="dropdown">
                                    This Month<i class="ri-arrow-down-s-line ml-1"></i>
                                </span>
                                <div class="dropdown-menu dropdown-menu-right shadow-none"
                                    aria-labelledby="dropdownMenuButton005">
                                    <a class="dropdown-item" href="#">Year</a>
                                    <a class="dropdown-item" href="#">Month</a>
                                    <a class="dropdown-item" href="#">Week</a>
                                </div>
                            </div>
                        </div>
                    </div> 
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center mt-2">
                            <div class="d-flex align-items-center progress-order-left">
                                <div class="progress progress-round m-0 orange conversation-bar" data-percent="46">
                                    <span class="progress-left">
                                        <span class="progress-bar"></span>
                                    </span>
                                    <span class="progress-right">
                                        <span class="progress-bar"></span>
                                    </span>
                                    <div class="progress-value text-secondary">46%</div>
                                </div>
                                <div class="progress-value ml-3 pr-5 border-right">
                                    <h5>$12,6598</h5>
                                    <p class="mb-0">Average Orders</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center ml-5 progress-order-right">
                                <div class="progress progress-round m-0 primary conversation-bar" data-percent="46">
                                    <span class="progress-left">
                                        <span class="progress-bar"></span>
                                    </span>
                                    <span class="progress-right">
                                        <span class="progress-bar"></span>
                                    </span>
                                    <div class="progress-value text-primary">46%</div>
                                </div>
                                <div class="progress-value ml-3">
                                    <h5>$59,8478</h5>
                                    <p class="mb-0">Top Orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="layout1-chart-5"></div>
                    </div>
                </div>
            </div>
        </div>
@endsection