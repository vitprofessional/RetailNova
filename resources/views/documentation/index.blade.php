@extends('include')
@section('backTitle')
Documentation
@endsection

@push('styles')
<style>
    .doc-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 60px 50px;
        color: white;
        margin-bottom: 50px;
        margin-top: 20px;
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.35);
        overflow: hidden;
        position: relative;
    }
    
    .doc-hero h1 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 15px;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }
    
    .doc-hero p {
        font-size: 1.15rem;
        opacity: 0.95;
        margin: 0;
        font-weight: 300;
        letter-spacing: 0.3px;
    }
    
    .doc-card {
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        border-radius: 16px;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        background: #fff;
        display: flex;
        flex-direction: column;
    }
    
    .doc-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
    }
    
    .doc-card .card-body {
        padding: 35px;
        display: flex;
        flex-direction: column;
        flex: 1;
        gap: 12px;
    }
    
    .doc-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
        flex-shrink: 0;
    }
    
    .doc-icon-wrapper i {
        font-size: 2.2rem;
        color: white;
    }
    
    .doc-card h5 {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 15px;
        margin-top: 0;
        color: #2c3e50;
        line-height: 1.3;
    }
    
    .doc-card p {
        font-size: 1.02rem;
        color: #575757;
        line-height: 1.8;
        margin-bottom: 0;
        margin-top: auto;
        flex-grow: 1;
        letter-spacing: 0.2px;
    }
    
    .doc-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.35s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: auto;
        cursor: pointer;
        text-decoration: none;
    }
    
    .doc-btn:hover {
        transform: translateX(4px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.45);
        color: white;
    }
    
    .doc-btn i {
        transition: transform 0.3s ease;
    }
    
    .doc-btn:hover i {
        transform: translateX(3px);
    }
    
    .quick-access-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    
    .quick-access-card .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 3px solid #667eea;
        border-radius: 16px 16px 0 0 !important;
        padding: 30px 35px;
    }
    
    .quick-access-card .card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #2c3e50;
        font-size: 1.4rem;
        letter-spacing: 0.3px;
    }
    
    .quick-access-card .card-body {
        padding: 40px 35px;
    }
    
    .quick-link-section {
        padding-bottom: 30px;
    }
    
    .quick-link-section:last-child {
        padding-bottom: 0;
    }
    
    .quick-link-section h6 {
        color: #667eea;
        font-weight: 700;
        margin-bottom: 20px;
        font-size: 1.15rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    
    .quick-link-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .quick-link-list li {
        margin-bottom: 0;
    }
    
    .quick-link-list a {
        color: #495057;
        text-decoration: none;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 10px;
        border-left: 3px solid transparent;
        font-weight: 500;
    }
    
    .quick-link-list a:hover {
        color: #667eea;
        background: #f5f7ff;
        transform: translateX(6px);
        border-left-color: #667eea;
    }
    
    .quick-link-list a i {
        margin-right: 12px;
        color: #667eea;
        font-weight: 600;
    }
    
    .hero-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .hero-btn {
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: 2px solid white;
        background: rgba(255,255,255,0.2);
        color: white;
        backdrop-filter: blur(10px);
    }
    
    .hero-btn:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255,255,255,0.3);
    }
    
    .hero-btn i {
        font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
        .doc-hero {
            padding: 30px 20px;
            text-align: center;
        }
        
        .doc-hero h1 {
            font-size: 1.8rem;
        }
        
        .hero-actions {
            justify-content: center;
        }
    }
</style>
@endpush

@section('container')
<div class="row">
    <!-- Hero Section -->
    <div class="col-lg-12">
        <div class="doc-hero">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1>📚 User Documentation</h1>
                    <p class="mb-0">Complete guide to mastering RetailNova Point of Sale system</p>
                </div>
                <div class="hero-actions">
                    <a href="{{ route('documentation.print') }}" class="hero-btn" target="_blank">
                        <i class="ri-printer-line"></i>
                        <span>Print All</span>
                    </a>
                    <a href="{{ route('documentation.downloadPdf') }}" class="hero-btn">
                        <i class="ri-download-line"></i>
                        <span>Download PDF</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Documentation Sections Grid -->
    <div class="col-lg-12">
        <div class="row">
            @foreach($sections as $key => $title)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card doc-card">
                    <div class="card-body">
                        <div class="doc-icon-wrapper">
                            @switch($key)
                                @case('overview')
                                    <i class="ri-information-line"></i>
                                    @break
                                @case('dashboard')
                                    <i class="ri-dashboard-line"></i>
                                    @break
                                @case('customers')
                                    <i class="ri-user-line"></i>
                                    @break
                                @case('suppliers')
                                    <i class="ri-truck-line"></i>
                                    @break
                                @case('products')
                                    <i class="ri-product-hunt-line"></i>
                                    @break
                                @case('purchases')
                                    <i class="ri-shopping-cart-line"></i>
                                    @break
                                @case('sales')
                                    <i class="ri-money-dollar-circle-line"></i>
                                    @break
                                @case('services')
                                    <i class="ri-tools-line"></i>
                                    @break
                                @case('warranty')
                                    <i class="ri-shield-check-line"></i>
                                    @break
                                @case('accounts')
                                    <i class="ri-bank-line"></i>
                                    @break
                                @case('expenses')
                                    <i class="ri-bill-line"></i>
                                    @break
                                @case('reports')
                                    <i class="ri-bar-chart-box-line"></i>
                                    @break
                                @case('settings')
                                    <i class="ri-settings-3-line"></i>
                                    @break
                            @endswitch
                        </div>
                        <h5>{{ $title }}</h5>
                        <p class="text-muted mb-3">
                            @switch($key)
                                @case('overview')
                                    Introduction to the system, key features, and getting started guide
                                    @break
                                @case('dashboard')
                                    Learn how to interpret business metrics and KPIs at a glance
                                    @break
                                @case('customers')
                                    Complete guide to managing customer information and credit
                                    @break
                                @case('suppliers')
                                    Managing vendor contacts, purchases, and payables
                                    @break
                                @case('products')
                                    Product catalog, inventory tracking, and stock management
                                    @break
                                @case('purchases')
                                    Recording purchases, managing suppliers, and tracking orders
                                    @break
                                @case('sales')
                                    POS operations, payment processing, and transaction management
                                    @break
                                @case('services')
                                    Service request workflow, tracking, and billing procedures
                                    @break
                                @case('warranty')
                                    Warranty registration, claims processing, and RMA management
                                    @break
                                @case('accounts')
                                    Financial accounts, transaction tracking, and reconciliation
                                    @break
                                @case('expenses')
                                    Recording and categorizing business expenses with receipts
                                    @break
                                @case('reports')
                                    Comprehensive business analytics and performance insights
                                    @break
                                @case('settings')
                                    System configuration, user management, and preferences
                                    @break
                            @endswitch
                        </p>
                        <a href="{{ route('documentation.show', $key) }}" class="doc-btn">
                            Read Guide <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Links -->
    <div class="col-lg-12 mt-4">
        <div class="card quick-access-card">
            <div class="card-header">
                <h5>🚀 Quick Access Links</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 quick-link-section">
                        <h6>📖 Getting Started</h6>
                        <ul class="quick-link-list">
                            <li>
                                <a href="{{ route('documentation.show', 'overview') }}">
                                    <i class="ri-arrow-right-s-line"></i>
                                    <span>System Overview & Introduction</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('documentation.show', 'dashboard') }}">
                                    <i class="ri-arrow-right-s-line"></i>
                                    <span>Dashboard Guide & Metrics</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('documentation.show', 'settings') }}">
                                    <i class="ri-arrow-right-s-line"></i>
                                    <span>Business Setup & Configuration</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6 quick-link-section">
                        <h6>⚡ Daily Operations</h6>
                        <ul class="quick-link-list">
                            <li>
                                <a href="{{ route('documentation.show', 'sales') }}">
                                    <i class="ri-arrow-right-s-line"></i>
                                    <span>Processing Sales & Payments</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('documentation.show', 'products') }}">
                                    <i class="ri-arrow-right-s-line"></i>
                                    <span>Managing Inventory & Stock</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('documentation.show', 'reports') }}">
                                    <i class="ri-arrow-right-s-line"></i>
                                    <span>Viewing Reports & Analytics</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
