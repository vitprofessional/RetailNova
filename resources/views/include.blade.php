<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', isset($pageTitle) ? $pageTitle : config('app.name', 'Retail Nova'))</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{asset('/public/eshop/')}}/assets/images/favicon.ico" />
    <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/backend-plugin.min.css">
    <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/backende209.css?v=1.0.0">
    <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/css/rn-custom.css">
    <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/fortawesome/fontawesome-free/css/all.min.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
    <link rel="stylesheet" href="{{asset('/public/eshop/')}}/assets/vendor/remixicon/fonts/remixicon.css"> 
    <script src="https://kit.fontawesome.com/7001e2ea29.js" crossorigin="anonymous"></script>
    <!-- Ensure jQuery is available early for inline scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Defensive shim: make sure window.$ and window.jQuery are consistent
        try{
            if(window.jQuery && !window.$) window.$ = window.jQuery;
        }catch(e){ /* ignore */ }

        // Simple queue for callbacks that want to run when jQuery is ready
        (function(){
            window.__jqReadyQueue = window.__jqReadyQueue || [];
            window.__jqOnReady = function(fn){
                if(typeof fn !== 'function') return;
                if(window.jQuery && typeof jQuery === 'function') return jQuery(fn);
                window.__jqReadyQueue.push(fn);
            };

            function flushQueue(){
                try{
                    if(window.jQuery && window.__jqReadyQueue && window.__jqReadyQueue.length){
                        window.__jqReadyQueue.forEach(function(f){ try{ jQuery(f); }catch(_){} });
                        window.__jqReadyQueue = [];
                    }
                }catch(e){}
            }

            // If jQuery appears later, flush queue
            if(!window.jQuery){
                var __jqInterval = setInterval(function(){
                    if(window.jQuery){
                        if(!window.$) window.$ = window.jQuery;
                        clearInterval(__jqInterval);
                        flushQueue();
                    }
                }, 50);
            } else {
                flushQueue();
            }
        })();
    </script>
    
    <style>
        body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f4f6f8;
        padding: 30px;
        color: #333;
        }
        .invoice-box {
        background-color: #fff;
        padding: 25px;
        border-radius: 12px;
        max-width: 800px;
        margin: auto;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .header {
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 10px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        
        }
        .header .store-info {
        font-size: 14px;
        }
        .header .invoice-title {
        font-size: 28px;
        color: #4CAF50;
        font-weight: bold;
        }
        .info-table, .product-table, .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        }
        .info-table td {
        padding: 5px 10px;
        }
        .product-table th {
        background-color: #4CAF50;
        color: white;
        padding: 10px;
        }
        .product-table td {
        border: 1px solid #ddd;
        padding: 8px;
        }
        .summary-table td {
        padding: 8px 10px;
        }
        .text-right {
        text-align: right;
        }
        .total {
        font-weight: bold;
        background-color: #f2f2f2;
        }
        .footer {
        text-align: center;
        font-size: 14px;
        color: #888;
        margin-top: 30px;
        }
        .qr-code {
        margin-top: 20px;
        text-align: right;
        }
        .qr-code img {
        width: 100px;
        }
        .product-table table td,  th{
            font-size: 10px !important;
            text-align: center !important;
            padding: .5rem .2rem !important;
            min-width: 80px;
        }
        .form-control {
            min-height: 40px !important;
            line-height: 30px;
            background: #fff;
            border: 1px solid #DCDFE8;
            font-size: 13px !important;
            color: #000000;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            -ms-border-radius: 10px;
            -o-border-radius: 10px;
            border-radius: 0;
            box-shadow: none;
        }
        .form-check-input {
            position: absolute;
            margin-top: .5rem;
            margin-left: -1.25rem;
        }
        .thead-start table th {
            text-align:left !important;
            font-size: 15px !important;
            padding-left: 20px !important;
        }
        .btn i {
            margin-right: 0.35rem !important;
        }
        .btn-primary {
            background-color: #4680ff;
            border-color: #4680ff;
            font-weight: 500;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #3566cc;
            border-color: #3566cc;
            color: #fff;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            font-weight: 500;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            color: #fff;
        }
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 13px;
        }
        .card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 7px 7px 0 0;
            padding: 1.25rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-check{
            padding-left: 2.5rem !important;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 14px;
        }
        .table-responsive{
            padding:1rem;
        }
        .form-control, .form-control:focus {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.625rem 0.875rem;
            font-size: 14px;
            transition: all 0.15s ease;
        }
        .form-control:focus {
            border-color: #4680ff;
            box-shadow: 0 0 0 0.2rem rgba(70,128,255,.15);
        }
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        .form-check-label {
            font-weight: normal;
            margin-bottom: 0;
            font-size: 14px;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 13px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        .table tbody td {
            vertical-align: middle;
            border-color: #e9ecef;
            padding: 0.875rem 0.75rem;
        }
        
        /* Documentation Section Table Styles */
        .section table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.12);
            border: 1px solid #e2e8f0;
        }
        
        .section table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .section table th {
            padding: 16px 18px;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #5568d3;
            color: white;
            background: transparent;
        }
        
        .section table td {
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #4a5568;
        }
        
        .section table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .section table tbody tr:hover {
            background-color: #f7fafc;
        }
        
        .section table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }
        
        .section table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        
        .table-responsive {
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .badge {
            padding: 0.35rem 0.65rem;
            font-weight: 500;
            font-size: 12px;
            border-radius: 4px;
        }
        .alert {
            border-radius: 6px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .text-danger {
            font-weight: 500;
            font-size: 13px;
        }
        .small, small {
            font-size: 13px;
            color: #6c757d;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 13px;
        }
        h4 {
            font-weight: 600;
            color: #333;
            font-size: 1.25rem;
        }
        .d-flex.justify-content-between {
            gap: 1rem;
        }
        /* Delete Button Styling */
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            font-weight: 500;
            color: #fff;
            transition: all 0.2s ease;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: #fff;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            transform: translateY(-2px);
        }
        .btn-danger:active {
            background-color: #bd2130;
            border-color: #b21f25;
            box-shadow: 0 1px 3px rgba(220, 53, 69, 0.4);
            transform: translateY(0);
        }
        .btn-danger:disabled {
            background-color: #dc3545;
            border-color: #dc3545;
            opacity: 0.65;
        }
        /* Delete icon styling */
        .btn-danger i {
            transition: transform 0.2s ease;
        }
        .btn-danger:hover i {
            transform: scale(1.1);
        }
        /* Danger badge styling for delete links */
        .badge-danger {
            background-color: #dc3545;
            color: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .badge-danger:hover {
            background-color: #c82333;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            transform: translateY(-2px);
            text-decoration: none;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            font-weight: 500;
            color: #fff;
            transition: all 0.2s ease;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: #fff;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.4);
            transform: translateY(-2px);
        }
        .btn-info:active {
            background-color: #117a8b;
            border-color: #0c5460;
            box-shadow: 0 1px 3px rgba(23, 162, 184, 0.4);
            transform: translateY(0);
        }
        /* Success/Edit button styling */
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: 500;
            color: #fff;
            transition: all 0.2s ease;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #fff;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.4);
            transform: translateY(-2px);
        }
        /* Warning button styling */
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            font-weight: 500;
            color: #212529;
            transition: all 0.2s ease;
        }
        }
        /* Button group styling */
        .btn-group {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        .btn-group .btn {
            margin: 0;
            border-radius: 6px;
        }
        /* Outline button variants */
        .btn-outline-danger {
            color: #dc3545;
            border: 1.5px solid #dc3545;
            background-color: transparent;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: #fff;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            transform: translateY(-2px);
        }
        .btn-outline-danger:active {
            background-color: #bd2130;
            border-color: #b21f25;
        }
        /* Additional danger badge styles for other delete patterns */
        .bg-warning.badge {
            background-color: #dc3545 !important;
            color: #fff !important;
            padding: 0.5rem 0.75rem !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            font-weight: 500 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .bg-warning.badge:hover {
            background-color: #c82333 !important;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4) !important;
            transform: translateY(-2px) !important;
            text-decoration: none !important;
        }
        .btn-link[data-original-title="Delete"] {
            color: #dc3545;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-link[data-original-title="Delete"]:hover {
            color: #c82333;
            transform: scale(1.1);
        }
        /* Generic danger badge styling */
        .badge.bg-danger {
            background-color: #dc3545 !important;
            color: #fff !important;
            padding: 0.5rem 0.75rem !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .badge.bg-danger:hover {
            background-color: #c82333 !important;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4) !important;
            transform: translateY(-2px) !important;
        }
        /* Print Button Styling */
        .btn-print {
            background-color: #6c757d;
            border-color: #6c757d;
            font-weight: 500;
            color: #fff;
            transition: all 0.2s ease;
        }
        .btn-print:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            color: #fff;
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.4);
            transform: translateY(-2px);
        }
        .btn-print i {
            margin-right: 0.35rem;
        }
        /* Utility classes for print control */
        .d-print-none {
            display: none !important;
        }
        .no-print {
            display: block !important;
        }
        @media print {
            .d-print-none {
                display: none !important;
            }
            .no-print {
                display: none !important;
            }
            .d-print-block {
                display: block !important;
            }
            .d-print-inline {
                display: inline !important;
            }
        }
        @media print {
            /* Hide elements that shouldn't print */
            .sidebar, .iq-navbar, .wrapper-menu, .search-toggle, nav, 
            .btn, button, .badge:not(.badge-primary):not(.badge-success):not(.badge-danger):not(.badge-warning), 
            .alert, .form-control, form, .d-print-none, .no-print {
                display: none !important;
            }
            
            /* Print-friendly body */
            body {
                background: white !important;
                padding: 20px !important;
                color: #000 !important;
            }
            
            /* Print container adjustments */
            .content-page, .container-fluid {
                width: 100% !important;
                margin: 0 !important;
                padding: 20px !important;
            }
            
            /* Print-friendly cards */
            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                margin-bottom: 20px !important;
            }
            
            .card-header {
                background: #f5f5f5 !important;
                border-bottom: 2px solid #000 !important;
                padding: 12px !important;
            }
            
            .card-body {
                padding: 15px !important;
            }
            
            /* Print-friendly tables */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-bottom: 15px !important;
                page-break-inside: avoid !important;
            }
            
            table thead th {
                background: #e9ecef !important;
                border: 1px solid #000 !important;
                padding: 10px !important;
                text-align: left !important;
                font-weight: bold !important;
            }
            
            table tbody td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
            }
            
            table tbody tr:nth-child(even) {
                background: #f9f9f9 !important;
            }
            
            /* Print-friendly headings */
            h1, h2, h3, h4, h5, h6 {
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }
            
            /* Print-friendly paragraphs */
            p {
                page-break-inside: avoid !important;
            }
            
            /* Print page breaks */
            .page-break {
                page-break-after: always !important;
            }
            
            /* Print footer */
            .print-footer {
                display: block !important;
                text-align: center !important;
                margin-top: 20px !important;
                padding-top: 20px !important;
                border-top: 1px solid #000 !important;
                font-size: 12px !important;
                color: #666 !important;
            }
            
            /* Link styling for print */
            a {
                text-decoration: none !important;
                color: #000 !important;
            }
        }
        .btn-outline-primary {
            color: #4680ff;
            border: 1.5px solid #4680ff;
            background-color: transparent;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-outline-primary:hover {
            background-color: #4680ff;
            color: #fff;
            box-shadow: 0 2px 8px rgba(70, 128, 255, 0.4);
            transform: translateY(-2px);
        }
        /* Delete row styling for inline delete buttons */
        .delete-row-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background-color: #ffebee;
            color: #dc3545;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-size: 16px;
        }
        .delete-row-action:hover {
            background-color: #dc3545;
            color: #fff;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            transform: scale(1.05);
        }
        .delete-row-action:active {
            transform: scale(0.95);
        }
        /* Global rounded search input styling */
        .rn-search { max-width: 420px; width: 100%; }
        .rn-search-box { position: relative; width: 100%; }
        .rn-search-input { height: 40px; border-radius: 999px; padding-left: 40px; padding-right: 38px; border: 1px solid #e9ecef; transition: all .15s ease; }
        .rn-search-input:focus { border-color: #86b7fe; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
        .rn-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 18px; }
        .rn-search-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #6c757d; padding: 4px; display: none; }
        .rn-search-clear:hover { color: #343a40; }
        /* Table scaling helpers */
        .rn-col-compact { width: 1%; white-space: nowrap; }
        .rn-ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rn-addr { max-width: 260px; }
        .table td, .table th { vertical-align: middle; }
        /* Center most table content, allow opt-out with .text-left */
        .tbl-server-info th, .tbl-server-info td { text-align: center; }
        .tbl-server-info td.text-left, .tbl-server-info th.text-left { text-align: left; }
        /* Make action badges centered and compact */
        .list-action { justify-content: center !important; }
        .list-action .badge { margin: 0 .25rem; }
    </style>
</head>
  <body class="">
    <!-- loader Start -->
    <div id="loading">
          <div id="loading-center">
          </div>
    </div>
    <!-- loader END -->
    <!-- Wrapper Start -->
    <div class="wrapper">
      
      <div class="iq-sidebar  sidebar-default ">
          <div class="iq-sidebar-logo d-flex align-items-center justify-content-between">
              <a href="{{ route('dashboard') }}" class="header-logo">
                  <img src="{{asset('/public/eshop/')}}/assets/images/logo.png" class="img-fluid rounded-normal light-logo" alt="logo"><h5 class="logo-title light-logo ml-3">Retail Nova</h5>
              </a>
              <div class="iq-menu-bt-sidebar ml-0">
                  <i class="las la-bars wrapper-menu"></i>
              </div>
          </div>
          <div class="data-scrollbar" data-scroll="1">
              <nav class="iq-sidebar-menu">
                  <ul id="iq-sidebar-toggle" class="iq-menu">
                      <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                          <a href="{{route('dashboard')}}" class="svg-icon">                        
                              <svg  class="svg-icon" id="p-dash1" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
                              </svg>
                              <span class="ml-4">Dashboards </span>
                          </a>
                      </li>
                      <li class=" ">
                          <a href="#product" class="{{ request()->routeIs('addCustomer','addSupplier') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('addCustomer','addSupplier') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash2" width="20" height="20"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                              </svg>
                              <span class="ml-4">Customer & Supplier</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="product" class="iq-submenu collapse{{ request()->routeIs('addCustomer','addSupplier') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                              <li class="{{ request()->routeIs('addCustomer') ? 'active' : '' }}">
                                  <a href="{{route('addCustomer')}}">
                                      <i class="las la-minus"></i><span>Customer</span>
                                  </a>
                              </li>
                              <li class="{{ request()->routeIs('addSupplier') ? 'active' : '' }}">
                                  <a href="{{route('addSupplier')}}">
                                      <i class="las la-minus"></i><span>Suplier</span>
                                  </a>
                              </li>
                          </ul>
                      </li>
                      <li class=" ">
                                                      <a href="#category" class="{{ request()->routeIs('addProduct','productlist','stockProduct','addBrand','addCategory','addProductUnit') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('addProduct','productlist','stockProduct','addBrand','addCategory','addProductUnit') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash3" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                              </svg>
                              <span class="ml-4">Product</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="category" class="iq-submenu collapse{{ request()->routeIs('addProduct','productlist','stockProduct','addBrand','addCategory','addProductUnit') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="{{ request()->routeIs('addProduct') ? 'active' : '' }}">
                                          <a href="{{route('addProduct')}}">
                                              <i class="las la-minus"></i><span>New Product</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('productlist') ? 'active' : '' }}">
                                          <a href="{{route('productlist')}}">
                                              <i class="las la-minus"></i><span>Product List</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('stockProduct') ? 'active' : '' }}">
                                          <a href="{{route('stockProduct')}}">
                                              <i class="las la-minus"></i><span>Stock Product</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('addBrand') ? 'active' : '' }}">
                                          <a href="{{route('addBrand')}}">
                                              <i class="las la-minus"></i><span>Brand</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('addCategory') ? 'active' : '' }}">
                                          <a href="{{route('addCategory')}}">
                                              <i class="las la-minus"></i><span>Category</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('addProductUnit') ? 'active' : '' }}">
                                          <a href="{{route('addProductUnit')}}">
                                              <i class="las la-minus"></i><span>Unit</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#damage" class="{{ request()->routeIs('damageProduct','damageProductList') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('damageProduct','damageProductList') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash5" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                  <line x1="1" y1="10" x2="23" y2="10"></line>
                              </svg>
                              <span class="ml-4">Damage</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="damage" class="iq-submenu collapse{{ request()->routeIs('damageProduct','damageProductList') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="{{route('damageProduct')}}">
                                              <i class="las la-minus"></i><span>Damage Product</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="{{route('damageProductList')}}">
                                              <i class="las la-minus"></i><span>Damage Product List</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#parchase" class="{{ request()->routeIs('addPurchase','purchaseList') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('addPurchase','purchaseList') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash4" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                              </svg>
                              <span class="ml-4">Purchase</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="parchase" class="iq-submenu collapse{{ request()->routeIs('addPurchase','purchaseList') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="{{route('addPurchase')}}">
                                              <i class="las la-minus"></i><span>Naw Parchase</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="{{route('purchaseList')}}">
                                              <i class="las la-minus"></i><span>Parchase List</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#sale" class="{{ request()->routeIs('newsale','saleList','returnSaleList') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('newsale','saleList','returnSaleList') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash5" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                  <line x1="1" y1="10" x2="23" y2="10"></line>
                              </svg>
                              <span class="ml-4">sale</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="sale" class="iq-submenu collapse{{ request()->routeIs('newsale','saleList','returnSaleList') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="{{route('newsale')}}">
                                              <i class="las la-minus"></i><span>New sale</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="{{route('saleList')}}">
                                              <i class="las la-minus"></i><span>Sale List</span>
                                          </a>
                                  </li>
                                  <li class="">
                                            <a href="{{route('returnSaleList')}}">
                                                <i class="las la-minus"></i><span>Sale Return List</span>
                                            </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                          <a href="#service" class="{{ request()->routeIs('provideService','addServiceName','serviceProvideList') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('provideService','addServiceName','serviceProvideList') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash6" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line>
                              </svg>
                              <span class="ml-4">Service</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="service" class="iq-submenu collapse{{ request()->routeIs('provideService','addServiceName','serviceProvideList') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="">
                                          <a href="{{route('provideService')}}">
                                              <i class="las la-minus"></i><span>Provide Service</span>
                                          </a>
                                  </li>
                                  <li class="">
                                          <a href="{{route('addServiceName')}}">
                                              <i class="las la-minus"></i><span>Service item</span>
                                          </a>
                                  </li>
                                   <li class="">
                                          <a href="{{route('serviceProvideList')}}">
                                              <i class="las la-minus"></i><span>Service List</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>
                      <li class=" ">
                                  <a href="#warranty" class="{{ request()->routeIs('rma','serialList') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('rma','serialList') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash6" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line>
                              </svg>
                              <span class="ml-4">Warranty</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                                  <ul id="warranty" class="iq-submenu collapse{{ request()->routeIs('rma.*','serialList') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                          <li class="{{ request()->routeIs('rma.*') ? 'active' : '' }}">
                              <a href="{{ route('rma.index') }}">
                                  <i class="las la-minus"></i><span>RMA</span>
                              </a>
                          </li>
                          <li class="{{ request()->routeIs('serialList') ? 'active' : '' }}">
                              <a href="{{ route('serialList') }}">
                                  <i class="las la-minus"></i><span>Serial List</span>
                              </a>
                          </li>
                      </ul>
                      </li>
                      <li class=" ">
                          <a href="#account" class="{{ request()->routeIs('account.*') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('account.*') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash8" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                              </svg>
                              <span class="ml-4">Account Management</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="account" class="iq-submenu collapse{{ request()->routeIs('account.*') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="{{ request()->routeIs('account.chart','account.create','account.edit') ? 'active' : '' }}">
                                          <a href="{{route('account.chart')}}">
                                              <i class="las la-book"></i><span>Chart of Accounts</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('account.transactions','account.transactions.create') ? 'active' : '' }}">
                                          <a href="{{route('account.transactions')}}">
                                              <i class="las la-exchange-alt"></i><span>Transactions</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('account.reports') ? 'active' : '' }}">
                                          <a href="{{route('account.reports')}}">
                                              <i class="las la-chart-line"></i><span>Financial Reports</span>
                                          </a>
                                  </li>
                          </ul>
                      </li> <li class=" ">
                          <a href="#expense" class="{{ request()->routeIs('expense.*') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('expense.*') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-dash8" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                  <circle cx="8.5" cy="7" r="4"></circle>
                                  <line x1="18" y1="8" x2="23" y2="13"></line>
                                  <line x1="23" y1="8" x2="18" y2="13"></line>
                              </svg>
                              <span class="ml-4">Expense Management</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="expense" class="iq-submenu collapse{{ request()->routeIs('expense.*') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                  <li class="{{ request()->routeIs('expense.categories','expense.categories.*') ? 'active' : '' }}">
                                          <a href="{{route('expense.categories')}}">
                                              <i class="las la-tags"></i><span>Expense Categories</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('expense.create') ? 'active' : '' }}">
                                          <a href="{{route('expense.create')}}">
                                              <i class="las la-plus-circle"></i><span>Add Expense</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('expense.list','expense.edit') ? 'active' : '' }}">
                                          <a href="{{route('expense.list')}}">
                                              <i class="las la-list"></i><span>Expense List</span>
                                          </a>
                                  </li>
                                  <li class="{{ request()->routeIs('expense.reports') ? 'active' : '' }}">
                                          <a href="{{route('expense.reports')}}">
                                              <i class="las la-chart-bar"></i><span>Expense Reports</span>
                                          </a>
                                  </li>
                          </ul>
                      </li>

                      @can('viewAudits')
                      <li class=" {{ request()->routeIs('audits.index') ? 'active' : '' }}">
                          <a href="{{ route('audits.index') }}" class="svg-icon">
                              <i class="las la-history"></i>
                              <span class="ml-4">Audits</span>
                          </a>
                      </li>
                      @endcan
                      <li class=" ">
                          <a href="#reports" class="{{ request()->routeIs('reports.*') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
                              <svg class="svg-icon" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <line x1="12" y1="2" x2="12" y2="22"></line>
                                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                              </svg>
                              <span class="ml-4">Reports</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="reports" class="iq-submenu collapse{{ request()->routeIs('reports.*') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                              <li class="{{ request()->routeIs('reports.business') ? 'active' : '' }}">
                                  <a href="{{route('reports.business')}}">
                                      <i class="las la-chart-line"></i><span>Business Report</span>
                                  </a>
                              </li>
                              <li class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                                  <a href="{{route('reports.sales')}}">
                                      <i class="las la-shopping-cart"></i><span>Sale Report</span>
                                  </a>
                              </li>
                              <li class="{{ request()->routeIs('reports.purchases') ? 'active' : '' }}">
                                  <a href="{{route('reports.purchases')}}">
                                      <i class="las la-dolly"></i><span>Purchase Report</span>
                                  </a>
                              </li>
                              <li class="{{ request()->routeIs('reports.topCustomers') ? 'active' : '' }}">
                                  <a href="{{route('reports.topCustomers')}}">
                                      <i class="las la-star"></i><span>Top Customers</span>
                                  </a>
                              </li>
                              <li class="{{ request()->routeIs('reports.payableReceivable') ? 'active' : '' }}">
                                  <a href="{{route('reports.payableReceivable')}}">
                                      <i class="las la-money-bill"></i><span>Payable/Receivable</span>
                                  </a>
                              </li>
                              <li class="{{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                                  <a href="{{route('reports.stock')}}">
                                      <i class="las la-cubes"></i><span>Stock Report</span>
                                  </a>
                              </li>
                          </ul>
                      </li>
                      <li class=" {{ request()->routeIs('documentation.*') ? 'active' : '' }}">
                          <a href="{{ route('documentation.index') }}" class="svg-icon">
                              <i class="las la-book"></i>
                              <span class="ml-4">Documentation</span>
                          </a>
                      </li>
                      <li class=" ">
                          <a href="#settings" class="{{ request()->routeIs('addBusinessSetupPage','business.*') ? '' : 'collapsed' }}" data-toggle="collapse" aria-expanded="{{ request()->routeIs('addBusinessSetupPage','business.*') ? 'true' : 'false' }}">
                              <svg class="svg-icon" id="p-settings" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <circle cx="12" cy="12" r="3"></circle>
                                  <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m5.08 5.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m5.08-5.08l4.24-4.24"></path>
                              </svg>
                              <span class="ml-4">Business Settings</span>
                              <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="10 15 15 20 20 15"></polyline><path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                              </svg>
                          </a>
                          <ul id="settings" class="iq-submenu collapse{{ request()->routeIs('addBusinessSetupPage','business.*') ? ' show' : '' }}" data-parent="#iq-sidebar-toggle">
                                <li class="{{ request()->routeIs('addBusinessSetupPage') ? 'active' : '' }}">
                                        <a href="{{route('addBusinessSetupPage')}}">
                                            <i class="las la-cog"></i><span>Business Configuration</span>
                                        </a>
                                </li>
                                <li class="{{ request()->routeIs('business.locations.*','business.locations') ? 'active' : '' }}">
                                        <a href="{{route('business.locations')}}">
                                            <i class="las la-map-marked-alt"></i><span>Business Locations</span>
                                        </a>
                                </li>
                          </ul>
                      </li>
                  </ul>
              </nav>
              <div id="sidebar-bottom" class="position-relative sidebar-bottom">
                  <div class="card border-none">
                      <div class="card-body p-0">
                          <div class="sidebarbottom-content">
                              <div class="image"><img src="{{asset('/public/eshop/')}}/assets/images/layouts/side-bkg.png" class="img-fluid" alt="side-bkg"></div>
                              <h6 class="mt-4 px-4 body-title">Get More Feature by Upgrading</h6>
                              <button type="button" class="btn sidebar-bottom-btn mt-4">Go Premium</button>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="p-3"></div>
          </div>
          </div>      <div class="iq-top-navbar">
          <div class="iq-navbar-custom">
              <nav class="navbar navbar-expand-lg navbar-light p-0">
                  <div class="iq-navbar-logo d-flex align-items-center justify-content-between">
                      <i class="ri-menu-line wrapper-menu"></i>
                      <a href="{{route('dashboard')}}" class="header-logo">
                          <img src="{{asset('/public/eshop/')}}/assets/images/logo.png" class="img-fluid rounded-normal" alt="logo">
                          <h5 class="logo-title ml-3">eshop</h5>
      
                      </a>
                  </div>
                  <div class="iq-search-bar device-search">
                      <form action="#" class="searchbox">
                          <a class="search-link" href="#"><i class="ri-search-line"></i></a>
                          <input type="text" class="text search-input" placeholder="Search here.">
                      </form>
                  </div>
                  <div class="d-flex align-items-center">
                      <button class="navbar-toggler" type="button" data-toggle="collapse"
                          data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                          aria-label="Toggle navigation">
                          <i class="ri-menu-3-line"></i>
                      </button>
                      <div class="collapse navbar-collapse" id="navbarSupportedContent">
                          <ul class="navbar-nav ml-auto navbar-list align-items-center">
                              <li class="nav-item nav-icon dropdown">
                                  <a href="#" class="search-toggle dropdown-toggle btn border add-btn"
                                      id="dropdownMenuButton02" data-toggle="dropdown" aria-haspopup="true"
                                      aria-expanded="false">
                                      <img src="{{asset('/public/eshop/')}}/assets/images/small/flag-01.png" alt="img-flag"
                                          class="img-fluid image-flag mr-2">En
                                  </a>
                                  <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                      <div class="card shadow-none m-0">
                                          <div class="card-body p-3">
                                              <a class="iq-sub-card" href="#"><img
                                                      src="{{asset('/public/eshop/')}}/assets/images/small/flag-02.png" alt="img-flag"
                                                      class="img-fluid mr-2">French</a>
                                              <a class="iq-sub-card" href="#"><img
                                                      src="{{asset('/public/eshop/')}}/assets/images/small/flag-03.png" alt="img-flag" ,+8A6
                                                      lass="iq-sub-card" href="#"><img
                                                      src="{{asset('/public/eshop/')}}/assets/images/small/flag-04.png" alt="img-flag"
                                                      class="img-fluid mr-2">Italian</a>
                                              <a class="iq-sub-card" href="#"><img
                                                      src="{{asset('/public/eshop/')}}/assets/images/small/flag-05.png" alt="img-flag"
                                                      class="img-fluid mr-2">German</a>
                                              <a class="iq-sub-card" href="#"><img
                                                      src="{{asset('/public/eshop/')}}/assets/images/small/flag-06.png" alt="img-flag"
                                                      class="img-fluid mr-2">Japanese</a>
                                          </div>
                                      </div>
                                  </div>
                              </li>
                              <li>
                                  <a href="#" class="btn border add-btn shadow-none mx-2 d-none d-md-block" data-toggle="modal" data-target="#new-order"><i class="las la-plus mr-2"></i>New Order</a>
                              </li>
                              <li class="nav-item nav-icon search-content">
                                  <a href="#" class="search-toggle rounded" id="dropdownSearch" data-toggle="dropdown"
                                      aria-haspopup="true" aria-expanded="false">
                                      <i class="ri-search-line"></i>
                                  </a>
                                  <div class="iq-search-bar iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownSearch">
                                      <form action="#" class="searchbox p-2">
                                          <div class="form-group mb-0 position-relative">
                                              <input type="text" class="text search-input font-size-12"
                                                  placeholder="type here to search.">
                                              <a href="#" class="search-link"><i class="las la-search"></i></a>
                                          </div>
                                      </form>
                                  </div>
                              </li>
                              <li class="nav-item nav-icon dropdown">
                                  <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton2"
                                      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                          stroke-linejoin="round" class="feather feather-mail">
                                          <path
                                              d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                          </path>
                                          <polyline points="22,6 12,13 2,6"></polyline>
                                      </svg>
                                      <span class="bg-primary"></span>
                                  </a>
                                  <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                      <div class="card shadow-none m-0">
                                          <div class="card-body p-0 ">
                                              <div class="cust-title p-3">
                                                  <div class="d-flex align-items-center justify-content-between">
                                                      <h5 class="mb-0">All Messages</h5>
                                                      <a class="badge badge-primary badge-card" href="#">3</a>
                                                  </div>
                                              </div>
                                              <div class="px-3 pt-0 pb-0 sub-card">
                                                  <a href="#" class="iq-sub-card">
                                                      <div class="media align-items-center cust-card py-3 border-bottom">
                                                          <div class="">
                                                              <img class="avatar-50 rounded-small"
                                                                  src="{{asset('/public/eshop/')}}/assets/images/user/01.jpg" alt="01">
                                                          </div>
                                                          <div class="media-body ml-3">
                                                              <div class="d-flex align-items-center justify-content-between">
                                                                  <h6 class="mb-0">Emma Watson</h6>
                                                                  <small class="text-dark"><b>12 : 47 pm</b></small>
                                                              </div>
                                                              <small class="mb-0">Lorem ipsum dolor sit amet</small>
                                                          </div>
                                                      </div>
                                                  </a>
                                                  <a href="#" class="iq-sub-card">
                                                      <div class="media align-items-center cust-card py-3 border-bottom">
                                                          <div class="">
                                                              <img class="avatar-50 rounded-small"
                                                                  src="{{asset('/public/eshop/')}}/assets/images/user/02.jpg" alt="02">
                                                          </div>
                                                          <div class="media-body ml-3">
                                                              <div class="d-flex align-items-center justify-content-between">
                                                                  <h6 class="mb-0">Ashlynn Franci</h6>
                                                                  <small class="text-dark"><b>11 : 30 pm</b></small>
                                                              </div>
                                                              <small class="mb-0">Lorem ipsum dolor sit amet</small>
                                                          </div>
                                                      </div>
                                                  </a>
                                                  <a href="#" class="iq-sub-card">
                                                      <div class="media align-items-center cust-card py-3">
                                                          <div class="">
                                                              <img class="avatar-50 rounded-small"
                                                                  src="{{asset('/public/eshop/')}}/assets/images/user/03.jpg" alt="03">
                                                          </div>
                                                          <div class="media-body ml-3">
                                                              <div class="d-flex align-items-center justify-content-between">
                                                                  <h6 class="mb-0">Kianna Carder</h6>
                                                                  <small class="text-dark"><b>11 : 21 pm</b></small>
                                                              </div>
                                                              <small class="mb-0">Lorem ipsum dolor sit amet</small>
                                                          </div>
                                                      </div>
                                                  </a>
                                              </div>
                                              <a class="right-ic btn btn-primary btn-block position-relative p-2" href="#"
                                                  role="button">
                                                  View All
                                              </a>
                                          </div>
                                      </div>
                                  </div>
                              </li>
                              <li class="nav-item nav-icon dropdown">
                                  <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton"
                                      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                          stroke-linejoin="round" class="feather feather-bell">
                                          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                      </svg>
                                      <span class="bg-primary "></span>
                                  </a>
                                  <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
                                      <div class="card shadow-none m-0">
                                          <div class="card-body p-0 ">
                                              <div class="cust-title p-3">
                                                  <div class="d-flex align-items-center justify-content-between">
                                                      <h5 class="mb-0">Notifications</h5>
                                                      <a class="badge badge-primary badge-card" href="#">3</a>
                                                  </div>
                                              </div>
                                              <div class="px-3 pt-0 pb-0 sub-card">
                                                  <a href="#" class="iq-su€b-card">
                                                      <div class="media align-items-center cust-card py-3 border-bottom">
                                                          <div class="">
                                                              <img class="avatar-50 rounded-small"
                                                                  src="{{asset('/public/eshop/')}}/assets/images/user/01.jpg" alt="01">
                                                          </div>
                                                          <div class="media-body ml-3">
                                                              <div class="d-flex align-items-center justify-content-between">
                                                                  <h6 class="mb-0">Emma Watson</h6>
                                                                  <small class="text-dark"><b>12 : 47 pm</b></small>
                                                              </div>
                                                              <small class="mb-0">Lorem ipsum dolor sit amet</small>
                                                          </div>
                                                      </div>
                                                  </a>
                                                  <a href="#" class="iq-sub-card">
                                                      <div class="media align-items-center cust-card py-3 border-bottom">
                                                          <div class="">
                                                              <img class="avatar-50 rounded-small"
                                                                  src="{{asset('/public/eshop/')}}/assets/images/user/02.jpg" alt="02">
                                                          </div>
                                                          <div class="media-body ml-3">
                                                              <div class="d-flex align-items-center justify-content-between">
                                                                  <h6 class="mb-0">Ashlynn Franci</h6>
                                                                  <small class="text-dark"><b>11 : 30 pm</b></small>
                                                              </div>
                                                              <small class="mb-0">Lorem ipsum dolor sit amet</small>
                                                          </div>
                                                      </div>
                                                  </a>
                                                  <a href="#" class="iq-sub-card">
                                                      <div class="media align-items-center cust-card py-3">
                                                          <div class="">
                                                              <img class="avatar-50 rounded-small"
                                                                  src="{{asset('/public/eshop/')}}/assets/images/user/03.jpg" alt="03">
                                                          </div>
                                                          <div class="media-body ml-3">
                                                              <div class="d-flex align-items-center justify-content-between">
                                                                  <h6 class="mb-0">Kianna Carder</h6>
                                                                  <small class="text-dark"><b>11 : 21 pm</b></small>
                                                              </div>
                                                              <small class="mb-0">Lorem ipsum dolor sit amet</small>
                                                          </div>
                                                      </div>
                                                  </a>
                                              </div>
                                              <a class="right-ic btn btn-primary btn-block position-relative p-2" href="#"
                                                  role="button">
                                                  View All
                                              </a>
                                          </div>
                                      </div>
                                  </div>
                              </li>
                              <li class="nav-item nav-icon dropdown caption-content">
                                  @php
                                      $adminUser = auth('admin')->user();
                                      $topAvatar = asset('/public/eshop/') . '/assets/images/user/1.png';
                                      if($adminUser && !empty($adminUser->avatar) && \Illuminate\Support\Facades\Storage::disk('public')->exists($adminUser->avatar)){
                                          $file = storage_path('app/public/' . $adminUser->avatar);
                                          $timestamp = @filemtime($file) ?: time();
                                          $root = rtrim(request()->root(), '/');
                                          $publicPath = public_path('storage/' . $adminUser->avatar);
                                          if(file_exists($publicPath)){
                                              $topAvatar = $root . '/public/storage/' . $adminUser->avatar . '?v=' . $timestamp;
                                          } else {
                                              $topAvatar = $root . '/storage/' . $adminUser->avatar . '?v=' . $timestamp;
                                          }
                                      }
                                  @endphp
                                  <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton4"
                                      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                      <img src="{{ $topAvatar }}" class="img-fluid rounded" alt="user">
                                  </a>
                                  <div class="iq-sub-dropdown dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton4" style="right:0;left:auto;min-width:260px;max-width:360px;">
                                      <div class="card shadow-none m-0">
                                          <div class="card-body p-0 text-center">
                                              <div class="media-body profile-detail text-center">
                                                  <div class="rounded-top img-fluid mb-3" style="background:#f5f5f7;">
                                                      <img src="{{asset('/public/eshop/')}}/assets/images/page-img/profile-bg.jpg" alt="profile-bg" class="img-fluid" style="width:100%;height:90px;object-fit:cover;border-top-left-radius:6px;border-top-right-radius:6px;">
                                                  </div>
                                                  <img src="{{ $topAvatar }}" alt="profile-img" class="rounded profile-img img-fluid avatar-70" style="margin-top:-36px;border:4px solid #fff;">
                                              </div>
                                              <div class="p-3">
                                                  @php $displayName = ($adminUser->fullName ?? '') . ($adminUser->sureName ? ' ' . $adminUser->sureName : ''); @endphp
                                                  <h5 class="mb-1">{{ $displayName ?: ($adminUser->mail ?? 'Admin User') }}</h5>
                                                  <p class="mb-0">{{ $adminUser->mail ?? '' }}</p>
                                                  <p class="mb-0">Since {{ optional($adminUser->created_at)->format('j M, Y') ?? '' }}</p>
                                                  <div class="d-flex align-items-center justify-content-center mt-3">
                                                      <a href="{{ route('admin.profile.show') }}" class="btn border mr-2">My Profile</a>
                                                      <a href="{{ route('logout') }}" class="btn border">Sign Out</a>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </li>
                          </ul>
                      </div>
                  </div>
              </nav>
          </div>
      </div>     
      <div class="content-page">
        <div class="container-fluid">
            @yield('container')
        </div>
      </div>
      <div class="modal fade" id="new-order" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">
                  <div class="modal-body">
                      <div class="popup text-left">
                          <h4 class="mb-3">New Order</h4>

                          <div class="content create-workform bg-body">
                              <div class="pb-3">
                                  <label class="mb-2">Email</label>
                                  <input type="text" class="form-control" placeholder="Enter Name or Email">
                              </div>
                              <div class="col-lg-12 mt-4">
                                  <div class="d-flex flex-wrap align-items-ceter justify-content-center">
                                      <div class="btn btn-primary mr-4" data-dismiss="modal">Cancel</div>
                                      <div class="btn btn-outline-primary" data-dismiss="modal">Create</div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div> 
    </div>
    <!-- Wrapper End-->
    <footer class="iq-footer">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-body">
                    <div class="p-3">
                        <h5 class="mb-1">{{ $adminUser->mail ?? 'user@domain' }}</h5>
                        <p class="mb-0">Since 10 march, 2020</p>
                        <div class="d-flex align-items-center justify-content-center mt-3">
                            <a href="{{ route('admin.profile.show') }}" class="btn border mr-2">My Profile</a>
                            <a href="{{ route('dashboard') }}" class="btn border mr-2">Dashboard</a>
                            <a href="{{ route('logout') }}" class="btn border">Sign Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        $(document).ready(function() {
            // Only auto-dismiss status alerts (success, warning, danger from SweetAlert/flash messages)
            // Keep validation/content alerts visible
            $(".alert-success, .alert-danger, .alert-warning").not(".alert-validation").fadeTo(3000, 500).slideUp(500, function() {
                $(this).slideUp(500);
            });
        });

        // onclick close modal
        function closeModel(e,f){
            $('#'+e).modal('hide');
            $("#"+f)[0].reset();
        }

        

        function remove(e){
            $(e).remove();
        };
    </script>
    <!-- SweetAlert2 for nicer confirmations -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function(){
            function isDeleteLink(href){
                if(!href) return false;
                try{
                    var url = href.toString();
                    return /\/delete(\/|$)/i.test(url) || /delete\?/i.test(url);
                }catch(e){ return false; }
            }

            function csrfToken(){
                var m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            }

            function submitDeleteViaPost(href){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = href;
                form.style.display = 'none';
                var token = document.createElement('input');
                token.type = 'hidden'; token.name = '_token'; token.value = csrfToken();
                form.appendChild(token);
                var meth = document.createElement('input');
                meth.type = 'hidden'; meth.name = '_method'; meth.value = 'DELETE';
                form.appendChild(meth);
                document.body.appendChild(form);
                form.submit();
            }

            document.addEventListener('click', function(e){
                var el = e.target.closest('a, button');
                if(!el) return;

                var explicit = el.getAttribute('data-confirm');
                if(explicit){
                    if(explicit === 'false' || explicit === '0') return;
                    var msg = explicit === 'delete' ? 'Are you sure you want to delete this item? This action cannot be undone.' : explicit;
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirm',
                        text: msg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Yes, proceed',
                        cancelButtonText: 'Cancel'
                    }).then(function(result){ if(result.isConfirmed){
                        // if link, navigate; if button in form, submit
                        if(el.tagName.toLowerCase() === 'a'){
                            var href = el.getAttribute('href');
                            // for delete-like links prefer POST/DELETE
                            if(isDeleteLink(href)) submitDeleteViaPost(href); else window.location = href;
                        } else {
                            var form = el.closest('form'); if(form) form.submit();
                        }
                    }});
                    return;
                }

                if(el.tagName.toLowerCase() === 'a'){
                    var href = el.getAttribute('href');
                    if(isDeleteLink(href)){
                        e.preventDefault();
                        Swal.fire({
                            title: 'Delete item?',
                            text: 'Are you sure you want to delete this item? This will revert stock where applicable.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete',
                            cancelButtonText: 'Cancel'
                        }).then(function(result){ if(result.isConfirmed){ submitDeleteViaPost(href); } });
                        return;
                    }
                }

                if(el.tagName.toLowerCase() === 'button'){
                    var form = el.closest('form');
                    if(form){
                        var action = form.getAttribute('action') || '';
                        var method = (form.getAttribute('method') || '').toLowerCase();
                        var hasDeleteMethod = !!form.querySelector('input[name="_method"][value="DELETE"]');
                        if(isDeleteLink(action) || hasDeleteMethod || method === 'delete'){
                            e.preventDefault();
                            Swal.fire({
                                title: 'Delete item?',
                                text: 'Are you sure you want to delete this item? This action cannot be undone.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                confirmButtonText: 'Yes, delete',
                                cancelButtonText: 'Cancel'
                            }).then(function(result){ if(result.isConfirmed){ form.submit(); } });
                            return;
                        }
                    }
                }
            }, true);
        })();
    </script>
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
    <!-- Backend Bundle JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/backend-bundle.min.js"></script>
    
    <!-- Table Treeview JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/table-treeview.js"></script>
    
    <!-- Chart Custom JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/customizer.js"></script>
    
    <!-- Chart Custom JavaScript -->
    <script async src="{{asset('/public/eshop/')}}/assets/js/chart-custom.js"></script>
    
    <!-- app JavaScript -->
    <script src="{{asset('/public/eshop/')}}/assets/js/app.js"></script>
    <script>
        // Reusable search binding (DataTables aware, fallback to text filter)
        (function(){
            function filterTable(tableId, query){
                query = (query || '').toLowerCase();
                var table = document.getElementById(tableId);
                if(!table) return;
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(tr){
                    var text = tr.innerText.toLowerCase();
                    tr.style.display = text.indexOf(query) > -1 ? '' : 'none';
                });
            }
            document.querySelectorAll('.rn-search-input').forEach(function(input){
                var tableId = input.getAttribute('data-table-target');
                var clearBtn = input.parentElement.querySelector('.rn-search-clear');
                function toggleClear(){ if(clearBtn) clearBtn.style.display = input.value ? 'block' : 'none'; }
                if(window.$ && typeof $.fn.DataTable === 'function' && tableId){
                    var $table = $('#'+tableId);
                    var dtInstance = null;
                    if ($table.length) {
                        if ($.fn.DataTable.isDataTable($table)) {
                            dtInstance = $table.DataTable();
                        } else {
                            dtInstance = $table.DataTable({
                                pageLength: 10,
                                order: [],
                                lengthChange: false
                            });
                        }
                    }
                    input.addEventListener('input', function(){ if(dtInstance){ dtInstance.search(this.value).draw(); } toggleClear(); });
                    if(clearBtn){ clearBtn.addEventListener('click', function(){ input.value=''; if(dtInstance){ dtInstance.search('').draw(); } toggleClear(); input.focus(); }); }
                } else {
                    input.addEventListener('input', function(){ filterTable(tableId, this.value); toggleClear(); });
                    if(clearBtn){ clearBtn.addEventListener('click', function(){ input.value=''; filterTable(tableId,''); toggleClear(); input.focus(); }); }
                }
                toggleClear();
            });
        })();
    </script>
        <script>
            // Generic table filters binding: handles selects, date range and search inputs with class `rn-filter-input`.
            (function(){
                function parseDateYMD(s){ if(!s) return null; try{ return new Date(s); }catch(e){ return null; } }

                function applyTableFilters(tableId){
                    if(!tableId) return;
                    var table = document.getElementById(tableId);
                    if(!table) return;

                    // collect filters for this table
                    var filters = {};
                    document.querySelectorAll('.rn-filter-input[data-table-target="'+tableId+'"]').forEach(function(el){
                        var fid = el.getAttribute('data-filter-for');
                        var fdate = el.getAttribute('data-filter-date');
                        if(fid){ filters[fid] = (el.value || '').toString().toLowerCase(); }
                        if(fdate === 'from') filters._dateFrom = el.value || '';
                        if(fdate === 'to') filters._dateTo = el.value || '';
                        if(!fid && !fdate && el.classList.contains('rn-search-input')) filters._search = (el.value||'').toString().toLowerCase();
                    });

                    // If DataTables is active for this table, prefer filtering via DataTables rows API
                    var dtInstance = null;
                    try{
                        if(window.jQuery && typeof jQuery.fn.DataTable === 'function'){
                            var $t = jQuery('#'+tableId);
                            if($t.length && jQuery.fn.DataTable.isDataTable($t[0])) dtInstance = $t.DataTable();
                        }
                    }catch(e){ dtInstance = null; }

                    var nodes = dtInstance ? dtInstance.rows().nodes().toArray() : Array.prototype.slice.call(table.querySelectorAll('tbody tr'));

                    nodes.forEach(function(r){
                        var text = r.innerText.toLowerCase();
                        var ok = true;

                        // match selects (string containment)
                        Object.keys(filters).forEach(function(k){
                            if(k.indexOf('_') === 0) return; // skip meta
                            var v = filters[k]; if(!v) return;
                            if(text.indexOf(v) === -1) ok = false;
                        });

                        // generic search
                        if(ok && filters._search){ if(text.indexOf(filters._search) === -1) ok = false; }

                        // date range: prefer row data-date attribute
                        if(ok && (filters._dateFrom || filters._dateTo)){
                            var rdate = r.getAttribute('data-date') || '';
                            var d = parseDateYMD(rdate);
                            if(!d){
                                var t = r.innerText.match(/\d{1,2}[\-/ ]\w{3,}[\-/ ]\d{2,4}|\d{4}-\d{2}-\d{2}/);
                                if(t) d = parseDateYMD(t[0]);
                            }
                            if(filters._dateFrom){ var from = new Date(filters._dateFrom+'T00:00:00'); if(!d || d < from) ok = false; }
                            if(filters._dateTo){ var to = new Date(filters._dateTo+'T23:59:59'); if(!d || d > to) ok = false; }
                        }

                        // show/hide row
                        r.style.display = ok ? '' : 'none';
                    });

                    // If using DataTables, redraw to ensure paging and layout update
                    try{ if(dtInstance) dtInstance.draw(false); }catch(e){}
                }

                // bind inputs
                document.querySelectorAll('.rn-filter-input').forEach(function(el){
                    var tableId = el.getAttribute('data-table-target');
                    if(!tableId) return;
                    el.addEventListener('input', function(){ applyTableFilters(tableId); });
                    el.addEventListener('change', function(){ applyTableFilters(tableId); });
                });
            })();
        </script>
    @yield('scripts')
  </body>
</html>