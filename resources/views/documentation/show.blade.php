@extends('include')
@section('backTitle')
{{ $title }} - Documentation
@endsection

@push('styles')
<style>
    /* Reset Bootstrap table styles */
    .doc-content-body table.table,
    .section table.table {
        margin-bottom: 0;
    }

    .doc-content-body table.table thead th,
    .section table.table thead th {
        border: none;
        background: inherit;
    }

    .doc-content-body table.table tbody td,
    .section table.table tbody td {
        border: none;
        padding: inherit;
    }
    
    .doc-section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 50px 45px;
        color: white;
        margin-bottom: 50px;
        margin-top: 20px;
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.35);
        position: relative;
        overflow: hidden;
    }
    
    .doc-section-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 15px;
        margin-top: 0;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }
    
    .doc-breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }
    
    .doc-breadcrumb a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    
    .doc-breadcrumb a:hover {
        color: white;
        text-decoration: underline;
    }
    
    .doc-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255,255,255,0.7);
        margin: 0 8px;
    }
    
    .doc-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 30px;
    }
    
    .doc-action-btn {
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: 2px solid white;
        background: rgba(255,255,255,0.15);
        color: white;
        backdrop-filter: blur(10px);
        text-decoration: none;
    }
    
    .doc-action-btn:hover {
        background: white;
        color: #667eea;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255,255,255,0.35);
    }
    
    .doc-content-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .doc-content-body {
        padding: 50px 45px;
        background: white;
        line-height: 1.6;
    }
    
    .doc-content-body h1 {
        color: #2c3e50;
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 30px;
        margin-top: 0;
        padding-bottom: 20px;
        border-bottom: 3px solid #667eea;
        letter-spacing: 0.3px;
    }
    
    .doc-content-body h2 {
        color: #34495e;
        font-size: 1.75rem;
        font-weight: 700;
        margin-top: 50px;
        margin-bottom: 25px;
        position: relative;
        padding-left: 25px;
        letter-spacing: 0.2px;
    }
    
    .doc-content-body h2::before {
        content: '';
        position: absolute;
        left: 0;
        top: 8px;
        width: 4px;
        height: 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
    }
    
    .doc-content-body h3 {
        color: #4a5568;
        font-size: 1.45rem;
        font-weight: 700;
        margin-top: 35px;
        margin-bottom: 20px;
        letter-spacing: 0.2px;
    }
    
    .doc-content-body p {
        color: #4a5568;
        font-size: 1.05rem;
        line-height: 1.9;
        margin-bottom: 22px;
        margin-top: 0;
        font-weight: 400;
        letter-spacing: 0.3px;
    }
    
    .doc-content-body ul,
    .doc-content-body ol {
        color: #4a5568;
        margin-bottom: 25px;
        margin-top: 10px;
        padding-left: 30px;
    }
    
    .doc-content-body li {
        margin-bottom: 12px;
        line-height: 1.8;
        font-size: 1.05rem;
        letter-spacing: 0.2px;
    }
    
    .doc-content-body table,
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
    
    .doc-content-body table thead,
    .section table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .doc-content-body table th,
    .section table th {
        padding: 16px 18px;
        text-align: left;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #5568d3;
        color: white;
    }
    
    .doc-content-body table td,
    .section table td {
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.95rem;
        line-height: 1.6;
        color: #4a5568;
    }
    
    .doc-content-body table tbody tr,
    .section table tbody tr {
        transition: background-color 0.3s ease;
    }
    
    .doc-content-body table tbody tr:hover,
    .section table tbody tr:hover {
        background-color: #f7fafc;
    }
    
    .doc-content-body table tbody tr:nth-child(even),
    .section table tbody tr:nth-child(even) {
        background-color: #fafbfc;
    }
    
    .doc-content-body table tbody tr:nth-child(odd),
    .section table tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }
    
    .doc-content-body .note {
        background: linear-gradient(135deg, #e8f4f8 0%, #d4e8f1 100%);
        border-left: 5px solid #3498db;
        padding: 25px 25px;
        margin: 30px 0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
    }
    
    .doc-content-body .warning {
        background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
        border-left: 5px solid #ffc107;
        padding: 25px 25px;
        margin: 30px 0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.1);
    }
    
    .doc-content-body .tip {
        background: linear-gradient(135deg, #e8f8f0 0%, #d4edda 100%);
        border-left: 5px solid #28a745;
        padding: 25px 25px;
        margin: 30px 0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
    }
    
    .doc-content-body .step {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 5px solid #6c757d;
        padding: 25px 25px;
        margin: 20px 0;
        border-radius: 10px;
        position: relative;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .doc-content-body .step-number {
        display: inline-flex;
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        text-align: center;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        margin-right: 15px;
        flex-shrink: 0;
        font-size: 0.95rem;
    }
    
    .doc-content-body code {
        background-color: #f7fafc;
        padding: 4px 10px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        font-size: 0.95rem;
        color: #e83e8c;
        border: 1px solid #e2e8f0;
        font-weight: 500;
    }
    
    .doc-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 50px;
        padding-top: 40px;
        border-top: 3px solid #e2e8f0;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .doc-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 14px 28px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.35s ease;
    }
    
    .doc-nav-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    @media (max-width: 768px) {
        .doc-section-header {
            padding: 25px 20px;
        }
        
        .doc-section-header h1 {
            font-size: 1.6rem;
        }
        
        .doc-content-body {
            padding: 25px 20px;
        }
        
        .doc-actions {
            flex-direction: column;
        }
        
        .doc-action-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('container')
<div class="row">
    <!-- Section Header -->
    <div class="col-lg-12">
        <div class="doc-section-header">
            <nav aria-label="breadcrumb" class="doc-breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('documentation.index') }}">
                            <i class="ri-home-4-line"></i> Documentation
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                </ol>
            </nav>
            <h1>{{ $title }}</h1>
            <div class="doc-actions">
                <a href="{{ route('documentation.index') }}" class="doc-action-btn">
                    <i class="ri-arrow-left-line"></i>
                    <span>Back to Index</span>
                </a>
                <a href="{{ route('documentation.print') }}" class="doc-action-btn" target="_blank">
                    <i class="ri-printer-line"></i>
                    <span>Print</span>
                </a>
                <a href="{{ route('documentation.sectionPdf', $section) }}" class="doc-action-btn">
                    <i class="ri-download-line"></i>
                    <span>Download PDF</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Documentation Content -->
    <div class="col-lg-12">
        <div class="card doc-content-card">
            <div class="card-body doc-content-body">
                @include('documentation.sections.' . $section)
            </div>
        </div>
    </div>
</div>
@endsection
