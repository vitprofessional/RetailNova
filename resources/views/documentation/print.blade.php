<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RetailNova POS Documentation - Print Version</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .section {
                page-break-before: always;
            }
            .section:first-child {
                page-break-before: auto;
            }
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.8;
            color: #2c3e50;
            max-width: 210mm;
            margin: 0 auto;
            padding: 30px;
        }
        .print-header {
            text-align: center;
            padding: 30px 0;
            border-bottom: 4px solid #667eea;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 40px 30px;
        }
        .print-header h1 {
            font-size: 30pt;
            color: #2c3e50;
            margin-bottom: 12px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .print-header .subtitle {
            font-size: 15pt;
            color: #6c757d;
            font-weight: 400;
        }
        .print-actions {
            text-align: center;
            margin: 25px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            border: 2px solid #667eea;
        }
        .print-actions button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            margin: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14pt;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .print-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .toc {
            margin: 35px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border-left: 5px solid #667eea;
        }
        .toc h2 {
            font-size: 22pt;
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 12px;
            font-weight: 700;
        }
        .toc ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc li {
            padding: 12px 0;
            border-bottom: 1px dotted #cbd5e0;
            font-size: 13pt;
            font-weight: 500;
        }
        .toc li:last-child {
            border-bottom: none;
        }
        .section {
            margin: 40px 0;
            padding: 30px 0;
        }
        .section h1 {
            font-size: 24pt;
            color: #2c3e50;
            border-bottom: 4px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .section h2 {
            font-size: 18pt;
            color: #34495e;
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 700;
            position: relative;
            padding-left: 15px;
        }
        .section h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 3px;
            width: 4px;
            height: 20px;
            background: #667eea;
        }
        .section h3 {
            font-size: 14pt;
            color: #4a5568;
            margin-top: 20px;
            margin-bottom: 12px;
            font-weight: 700;
        }
        .section p {
            margin-bottom: 12px;
            text-align: justify;
            font-size: 11pt;
            line-height: 1.8;
            color: #4a5568;
            letter-spacing: 0.3px;
        }
        .section ul, .section ol {
            margin-left: 30px;
            margin-bottom: 18px;
        }
        .section li {
            margin-bottom: 8px;
            font-size: 11pt;
            line-height: 1.7;
            color: #4a5568;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            page-break-inside: avoid;
            border: 1px solid #e2e8f0;
        }
        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 14px;
            text-align: left;
            font-weight: 700;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 2px solid #5568d3;
        }
        table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 11px 14px;
            font-size: 10pt;
            line-height: 1.5;
            color: #4a5568;
        }
        table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }
        table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .note {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 10px;
            margin: 15px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 15px 0;
        }
        .tip {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin: 15px 0;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 11pt;
        }
        .step {
            background-color: #f8f9fa;
            border-left: 3px solid #6c757d;
            padding: 10px;
            margin: 10px 0;
        }
        .step-number {
            display: inline-block;
            width: 25px;
            height: 25px;
            background-color: #3498db;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 25px;
            font-weight: bold;
            margin-right: 10px;
        }
        @page {
            margin: 2cm;
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>🛒 RetailNova POS</h1>
        <div class="subtitle">User Documentation & Guide</div>
        <div style="margin-top: 10px; color: #7f8c8d; font-size: 11pt;">
            Generated on {{ date('F d, Y') }} at {{ date('h:i A') }}
        </div>
    </div>

    <div class="print-actions no-print">
        <button onclick="window.print()">🖨️ Print</button>
        <button onclick="window.close()">❌ Close</button>
    </div>

    <div class="toc">
        <h2>📑 Table of Contents</h2>
        <ul>
            @foreach($sections as $key => $title)
            <li>{{ $loop->iteration }}. {{ $title }}</li>
            @endforeach
        </ul>
    </div>

    <div class="content">
        {!! $content !!}
    </div>

    <script>
        // Auto-print prompt (optional)
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
