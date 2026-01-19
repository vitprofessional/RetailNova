<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RetailNova POS Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.8;
            color: #2c3e50;
        }
        .cover-page {
            text-align: center;
            padding: 150px 50px;
            page-break-after: always;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .cover-page h1 {
            font-size: 52pt;
            font-weight: 800;
            margin-bottom: 25px;
            letter-spacing: 3px;
            line-height: 1.1;
        }
        .cover-page .subtitle {
            font-size: 22pt;
            margin-bottom: 60px;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 1px;
        }
        .cover-page .version {
            font-size: 14pt;
            margin-top: 80px;
            opacity: 0.9;
            font-weight: 500;
        }
        .cover-page .date {
            font-size: 13pt;
            margin-top: 25px;
            opacity: 0.85;
        }
        .toc {
            page-break-after: always;
            padding: 40px;
        }
        .toc h2 {
            font-size: 26pt;
            color: #667eea;
            margin-bottom: 35px;
            border-bottom: 4px solid #667eea;
            padding-bottom: 20px;
            letter-spacing: 1px;
        }
        .toc ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc li {
            padding: 14px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12pt;
            font-weight: 500;
        }
        .toc li:last-child {
            border-bottom: none;
        }
        .toc li:hover {
            background-color: #f7fafc;
        }
        .section {
            page-break-before: always;
            padding: 40px;
            margin: 20px 0;
        }
        .section h1 {
            font-size: 30pt;
            color: #667eea;
            border-bottom: 4px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }
        .section h2 {
            font-size: 22pt;
            color: #34495e;
            margin-top: 35px;
            margin-bottom: 20px;
            position: relative;
            padding-left: 20px;
            font-weight: 700;
        }
        .section h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            width: 4px;
            height: 28px;
            background: #667eea;
        }
        .section h3 {
            font-size: 16pt;
            color: #4a5568;
            margin-top: 25px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .section p {
            margin-bottom: 16px;
            line-height: 1.9;
            text-align: justify;
            color: #4a5568;
            font-size: 11.5pt;
            letter-spacing: 0.3px;
        }
        .section ul,
        .section ol {
            margin-bottom: 20px;
            padding-left: 30px;
            margin-left: 0;
        }
        .section li {
            margin-bottom: 10px;
            line-height: 1.8;
            color: #4a5568;
            font-size: 11pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background: white;
            page-break-inside: avoid;
        }
        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 14px;
            text-align: left;
            font-weight: 700;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #5568d3;
        }
        table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 13px 14px;
            font-size: 10.5pt;
            line-height: 1.6;
            color: #4a5568;
        }
        table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }
        table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .note {
            background: linear-gradient(135deg, #e8f4f8 0%, #d4e8f1 100%);
            border-left: 5px solid #3498db;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .warning {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border-left: 5px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .tip {
            background: linear-gradient(135deg, #e8f8f0 0%, #d4edda 100%);
            border-left: 5px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .step {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #6c757d;
            padding: 15px;
            margin: 12px 0;
            border-radius: 4px;
        }
        .step-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: 700;
            margin-right: 12px;
            font-size: 13pt;
        }
        code {
            background-color: #f7fafc;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            color: #e83e8c;
            border: 1px solid #e2e8f0;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            color: #718096;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <!-- Cover Page -->
    <div class="cover-page">
        <h1>RetailNova POS</h1>
        <div class="subtitle">Professional Documentation & User Guide</div>
        <div style="margin: 80px 0;">
            <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <rect width="120" height="120" rx="20" fill="white" opacity="0.2"/>
                <text x="60" y="75" font-size="60" fill="white" text-anchor="middle" font-weight="bold">RN</text>
            </svg>
        </div>
        <div class="version">Version 1.0 | Complete Feature Guide</div>
        <div class="date">Generated: {{ date('F d, Y \a\t h:i A') }}</div>
        <div style="margin-top: 50px; font-size: 11pt; opacity: 0.9;">
            <p style="margin-bottom: 10px;">Comprehensive guide for using RetailNova Point of Sale System</p>
            <p>© {{ date('Y') }} RetailNova. All rights reserved.</p>
        </div>
    </div>

    <!-- Table of Contents -->
    @if(!isset($singleSection) || !$singleSection)
    <div class="toc">
        <h2>📑 Table of Contents</h2>
        <ul>
            @foreach($sections as $key => $title)
            <li><strong>{{ $loop->iteration }}.</strong> {{ $title }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Content Sections -->
    <div class="content">
        {!! $content !!}
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>RetailNova POS Documentation</strong> | {{ date('Y') }} | Professional Edition
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 25;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
