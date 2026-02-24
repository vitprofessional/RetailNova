<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RetailNova Brochure</title>
    <style>
        :root {
            --primary: #1d4ed8;
            --accent: #16a34a;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
        }
        .page {
            max-width: 1000px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
        .actions {
            max-width: 1000px;
            margin: 16px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-print {
            border: 0;
            background: var(--primary);
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-print:hover {
            background: #1e40af;
        }
        .hero {
            background: linear-gradient(135deg, #1d4ed8, #2563eb 60%, #16a34a);
            color: #fff;
            padding: 34px 40px;
        }
        .logo-wrap {
            margin-bottom: 14px;
        }
        .logo {
            height: 56px;
            width: auto;
            display: inline-block;
            background: rgba(255,255,255,0.95);
            padding: 6px 10px;
            border-radius: 8px;
        }
        .brand {
            font-size: 34px;
            font-weight: 700;
            margin: 0;
        }
        .tagline {
            margin: 8px 0 0;
            font-size: 20px;
            font-weight: 500;
        }
        .sub {
            margin: 14px 0 0;
            font-size: 15px;
            line-height: 1.6;
            max-width: 860px;
            opacity: 0.96;
        }
        .section {
            padding: 24px 40px;
            border-top: 1px solid var(--line);
        }
        .section h2 {
            margin: 0 0 14px;
            font-size: 20px;
            color: var(--primary);
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px 16px;
            background: #fff;
        }
        .card h3 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #111827;
        }
        ul {
            margin: 8px 0 0 18px;
            padding: 0;
            line-height: 1.55;
            font-size: 14px;
        }
        .benefits {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 22px;
            font-size: 14px;
        }
        .benefits div::before {
            content: "✔";
            color: var(--accent);
            margin-right: 8px;
            font-weight: 700;
        }
        .cta {
            background: #f0f9ff;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 16px;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer {
            padding: 14px 40px 24px;
            color: var(--muted);
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        @media (max-width: 860px) {
            .grid-2, .benefits { grid-template-columns: 1fr; }
            .hero, .section, .footer { padding-left: 20px; padding-right: 20px; }
            .brand { font-size: 30px; }
            .tagline { font-size: 18px; }
        }
        @media print {
            @page { size: A4; margin: 10mm; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .page {
                margin: 0;
                max-width: 100%;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
            .hero {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button type="button" class="btn-print" onclick="window.print()">Print Brochure</button>
    </div>
    <main class="page">
        <section class="hero">
            <div class="logo-wrap">
                <img src="{{ asset('public/logo.png') }}" alt="RetailNova Logo" class="logo">
                <h1 class="brand">RetailNova</h1>
                <p class="tagline">Smart POS + Accounting + Expense Control</p>
            </div>
            <p class="sub">
                RetailNova helps retailers and service businesses run sales, inventory, customer operations,
                accounting, expenses, warranty, and reporting in one unified platform.
            </p>
        </section>

        <section class="section">
            <h2>Why RetailNova?</h2>
            <div class="benefits">
                <div>All-in-one operations across sales, stock, and finance</div>
                <div>Faster workflows for checkout, returns, and service</div>
                <div>Real-time visibility into cashflow and profitability</div>
                <div>Role-based access and auditing for stronger control</div>
            </div>
        </section>

        <section class="section">
            <h2>Core Modules</h2>
            <div class="grid-2">
                <article class="card">
                    <h3>Sales & Customer Operations</h3>
                    <ul>
                        <li>New sale, sale list, and returns</li>
                        <li>Customer and supplier management</li>
                        <li>Quotation creation and tracking</li>
                        <li>Service workflows for mixed businesses</li>
                    </ul>
                </article>
                <article class="card">
                    <h3>Product & Inventory</h3>
                    <ul>
                        <li>Products, brands, categories, and units</li>
                        <li>Stock monitoring and purchase flow</li>
                        <li>Damage product logging</li>
                        <li>Serial tracking and warranty (RMA)</li>
                    </ul>
                </article>
                <article class="card">
                    <h3>Accounting & Expenses</h3>
                    <ul>
                        <li>Chart of accounts and double-entry transactions</li>
                        <li>Balance sheet, income statement, trial balance</li>
                        <li>Expense categories, entries, and receipts</li>
                        <li>Payment methods and cost analysis reports</li>
                    </ul>
                </article>
                <article class="card">
                    <h3>Business Administration</h3>
                    <ul>
                        <li>User and role management</li>
                        <li>Business settings and locations</li>
                        <li>Audit logs and activity history</li>
                        <li>Dashboards and operational reporting</li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="section">
            <h2>Who It’s For</h2>
            <div class="grid-2">
                <article class="card">
                    <ul>
                        <li>Retail stores and chain outlets</li>
                        <li>Electronics/device businesses with serial needs</li>
                        <li>Service + product businesses</li>
                        <li>SMEs upgrading from manual operations</li>
                    </ul>
                </article>
                <article class="cta">
                    <strong>Book a free demo of RetailNova today.</strong><br>
                    See how your business can simplify operations, improve reporting visibility,
                    and grow faster with one connected platform.
                </article>
            </div>
        </section>

        <div class="footer">
            <span>Tagline: From Sale to Ledger—Everything in One Place.</span>
            <span>RetailNova Marketing Brochure</span>
        </div>
    </main>

    <script>
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key && event.key.toLowerCase() === 'p') {
                event.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
