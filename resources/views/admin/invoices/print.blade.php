<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Invoice {{ $invoice->number }} - {{ $invoice->school_branding['display_name'] ?? $invoice->school_branding['name'] ?? 'Hande High School' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 24px 16px;
            font-size: 12px;
            line-height: 1.4;
        }

        .screen-toolbar {
            max-width: 800px;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .toolbar-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        .toolbar-subtitle {
            font-size: 11px;
            color: #64748b;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 16px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            border: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
        }

        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
        }

        /* Top Header */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .school-identity {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .crest-box {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #0b1e36;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-align: center;
        }

        .crest-initials {
            font-size: 15px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .crest-tag {
            font-size: 6.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #cbd5e1;
            margin-top: 1px;
        }

        .school-logo-img {
            max-height: 56px;
            max-width: 56px;
            height: auto;
            width: auto;
            object-fit: contain;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .school-details h1 {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            margin-bottom: 2px;
        }

        .school-motto {
            font-size: 11px;
            font-style: italic;
            color: #64748b;
            font-family: Georgia, serif;
            margin-bottom: 2px;
        }

        .school-subtext {
            font-size: 11px;
            color: #64748b;
            line-height: 1.35;
        }

        .doc-title-block {
            text-align: right;
        }

        .doc-title {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .doc-number {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-top: 2px;
        }

        .doc-term {
            font-size: 11px;
            font-weight: 500;
            color: #64748b;
            margin-top: 2px;
        }

        /* Metadata 3-Column Bar */
        .meta-bar {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
            padding-top: 4px;
        }

        .meta-item-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .meta-item-val {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .meta-status-unpaid {
            color: #e11d48;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-status-paid {
            color: #059669;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-status-partial {
            color: #d97706;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-status-cancelled {
            color: #64748b;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Student Card */
        .student-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 16px;
        }

        .student-name {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }

        .student-grade {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .admission-number {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            font-family: monospace;
            margin-top: 2px;
        }

        .academic-year-text {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* 3 KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 14px;
            margin-bottom: 22px;
        }

        .kpi-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
        }

        .kpi-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .kpi-total-val {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace;
            letter-spacing: -0.5px;
        }

        .kpi-paid-val {
            font-size: 20px;
            font-weight: 800;
            color: #059669;
            font-family: monospace;
            letter-spacing: -0.5px;
        }

        .kpi-balance-val {
            font-size: 20px;
            font-weight: 800;
            color: #e11d48;
            font-family: monospace;
            letter-spacing: -0.5px;
        }

        /* Table Section */
        .section-heading {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 10px;
        }

        .items-table thead tr {
            background-color: #0f172a;
        }

        .items-table th {
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 9px 14px;
            text-align: left;
        }

        .items-table th:first-child {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .items-table th:last-child {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        .items-table th.col-qty {
            text-align: center;
            width: 60px;
        }

        .items-table th.col-price {
            text-align: right;
            width: 110px;
        }

        .items-table th.col-amount {
            text-align: right;
            width: 110px;
        }

        .items-table td {
            padding: 9px 14px;
            font-size: 11.5px;
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table td.col-desc {
            color: #1e293b;
            font-weight: 500;
        }

        .items-table td.col-qty {
            text-align: center;
            color: #475569;
            font-family: monospace;
        }

        .items-table td.col-price {
            text-align: right;
            color: #475569;
            font-family: monospace;
        }

        .items-table td.col-amount {
            text-align: right;
            color: #0f172a;
            font-weight: 700;
            font-family: monospace;
        }

        /* Summary Right Block */
        .summary-block {
            width: 290px;
            margin-left: auto;
            margin-top: 4px;
            margin-bottom: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 6px;
        }

        .summary-label-main {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
        }

        .summary-val-main {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace;
        }

        .summary-label-sub {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
        }

        .summary-val-sub {
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            font-family: monospace;
        }

        .balance-banner {
            background-color: #0f172a;
            border-radius: 6px;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
        }

        .balance-banner-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #ffffff;
        }

        .balance-banner-val {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            font-family: monospace;
        }

        /* Banking Details Card */
        .banking-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 13px 18px;
            margin-bottom: 14px;
        }

        .banking-title {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0f172a;
        }

        .banking-subtitle {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 2px;
            margin-bottom: 10px;
        }

        .banking-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 6px 16px;
            font-size: 11px;
            align-items: center;
        }

        .bank-field-label {
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bank-field-val {
            color: #1e293b;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        /* Payment Note */
        .payment-note-block {
            margin-bottom: 14px;
        }

        .payment-note-label {
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 2px;
        }

        .payment-note-text {
            font-size: 10.5px;
            color: #475569;
        }

        /* Footer */
        .doc-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9.5px;
            color: #64748b;
        }

        .footer-left {
            line-height: 1.4;
        }

        .footer-right {
            text-align: right;
            line-height: 1.4;
        }

        .footer-motto {
            font-style: italic;
            color: #475569;
        }

        /* Print Media Styles */
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-card {
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .kpi-grid {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            .student-card {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .banking-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .summary-block {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .doc-footer {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $totalPaid = (float) ($invoice->allocations?->sum('amount') ?? 0);
        $schoolModel = $invoice->school ?: ($school ?? auth()->user()?->school);
        $branding = $invoice->school_branding;
        $status = strtolower($invoice->status);

        // Robust logo resolution with multiple fallbacks to ensure uploaded school logo is displayed
        $logoSrc = null;
        if (!empty($branding['logo_base64'])) {
            $logoSrc = $branding['logo_base64'];
        } elseif (!empty($branding['logo_url'])) {
            $logoSrc = $branding['logo_url'];
        } elseif ($schoolModel && !empty($schoolModel->logo)) {
            if (str_starts_with($schoolModel->logo, 'http://') || str_starts_with($schoolModel->logo, 'https://')) {
                $logoSrc = $schoolModel->logo;
            } else {
                try {
                    $logoSrc = \Illuminate\Support\Facades\Storage::disk('public')->url($schoolModel->logo);
                } catch (\Throwable $e) {
                    $logoSrc = asset('storage/' . ltrim($schoolModel->logo, '/'));
                }
            }
        }
    @endphp

    <div class="screen-toolbar no-print">
        <div>
            <div class="toolbar-title">Invoice Print Preview</div>
            <div class="toolbar-subtitle">Official student tuition & fees billing document</div>
        </div>
        <div class="toolbar-actions">
            @if(request('display', 'summary') === 'summary')
                <a href="{{ route('admin.invoices.print', [$invoice, 'display' => 'itemized']) }}" class="btn btn-secondary" title="View individual vote breakdown lines">
                    Show Itemized Votes
                </a>
            @else
                <a href="{{ route('admin.invoices.print', [$invoice, 'display' => 'summary']) }}" class="btn btn-secondary" title="View single package total line">
                    Show Package Total Only
                </a>
            @endif
            <button type="button" onclick="window.print()" class="btn btn-primary">
                Print Invoice
            </button>
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-secondary">
                Back to Invoice
            </a>
        </div>
    </div>

    <div class="invoice-card">
        <!-- Top Header -->
        <div class="doc-header">
            <div class="school-identity">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" 
                         data-logo="{{ $schoolModel?->logo }}"
                         alt="{{ $branding['display_name'] ?? $branding['name'] ?? 'School Logo' }}" 
                         class="school-logo-img">
                @else
                    <div class="crest-box">
                        <span class="crest-initials">{{ strtoupper(substr($branding['name'] ?? 'H', 0, 2)) }}</span>
                        <span class="crest-tag">SCHOOL</span>
                    </div>
                @endif

                <div class="school-details">
                    <h1>{{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }}</h1>
                    @if(!empty($branding['motto']))
                        <div class="school-motto">"{{ $branding['motto'] }}"</div>
                    @else
                        <div class="school-motto">"Strive to Achieve"</div>
                    @endif
                    <div class="school-subtext">{{ $branding['formatted_address'] ?: 'Nyashanu, Buhera, Buhera' }}</div>
                    <div class="school-subtext">{{ $branding['email'] ?: 'admin@handehigh.test' }}</div>
                </div>
            </div>

            <div class="doc-title-block">
                <div class="doc-title">STUDENT INVOICE</div>
                <div class="doc-number">{{ $invoice->number }}</div>
                <div class="doc-term">{{ $invoice->academic_year }} &nbsp;/&nbsp; {{ strtoupper($invoice->term) }}</div>
            </div>
        </div>

        <!-- Metadata Bar (Issued On, Due Date, Status) -->
        <div class="meta-bar">
            <div>
                <div class="meta-item-label">ISSUED ON</div>
                <div class="meta-item-val">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : now()->format('d M Y') }}</div>
            </div>
            <div>
                <div class="meta-item-label">DUE DATE</div>
                <div class="meta-item-val">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '31 Jan ' . $invoice->academic_year }}</div>
            </div>
            <div>
                <div class="meta-item-label">STATUS</div>
                @if($status === 'paid')
                    <div class="meta-status-paid">PAID</div>
                @elseif($status === 'partial')
                    <div class="meta-status-partial">PARTIAL</div>
                @elseif($status === 'cancelled')
                    <div class="meta-status-cancelled">CANCELLED</div>
                @else
                    <div class="meta-status-unpaid">UNPAID</div>
                @endif
            </div>
        </div>

        <!-- Student Details Card -->
        <div class="student-card">
            <div>
                <div class="meta-item-label">BILL TO / STUDENT</div>
                <div class="student-name">{{ $invoice->student?->first_name }} {{ $invoice->student?->last_name }}</div>
                <div class="student-grade">
                    @if($invoice->student?->class_name && $invoice->student?->grade && str_contains($invoice->student?->class_name, $invoice->student?->grade))
                        {{ $invoice->student?->class_name }}
                    @elseif($invoice->student?->grade || $invoice->student?->class_name)
                        {{ trim(($invoice->student?->grade ?? '') . ' ' . ($invoice->student?->class_name ?? '')) }}
                    @else
                        Form 1 South
                    @endif
                </div>
            </div>
            <div>
                <div class="meta-item-label">ADMISSION NUMBER</div>
                <div class="admission-number">{{ $invoice->student?->admission_number ?? 'N/A' }}</div>
                <div class="academic-year-text">Academic year {{ $invoice->academic_year }}</div>
            </div>
        </div>

        <!-- 3 KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">INVOICE TOTAL</div>
                <div class="kpi-total-val">${{ number_format($invoice->total_amount, 2) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">AMOUNT PAID</div>
                <div class="kpi-paid-val">${{ number_format($totalPaid, 2) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">BALANCE DUE</div>
                <div class="kpi-balance-val">${{ number_format($invoice->balance, 2) }}</div>
            </div>
        </div>

        @php
            $displayMode = request('display', 'summary');
            $hasSubVotes = $invoice->items->contains(fn($it) => !empty($it->feeStructure?->parent_id) || ($it->feeStructure?->subVotes?->count() ?? 0) > 0);
            $parentStructure = $invoice->items->first(fn($it) => $it->feeStructure?->parent !== null)?->feeStructure?->parent 
                ?? $invoice->items->first(fn($it) => ($it->feeStructure?->subVotes?->count() ?? 0) > 0)?->feeStructure;
            $isSummary = $displayMode === 'summary' && ($hasSubVotes || $parentStructure);
        @endphp

        <!-- Itemised Fee Charges Table -->
        <div class="section-heading">{{ $isSummary ? 'FEE CHARGES SUMMARY' : 'ITEMISED FEE CHARGES' }}</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-desc">DESCRIPTION</th>
                    <th class="col-qty">QTY</th>
                    <th class="col-price">UNIT PRICE</th>
                    <th class="col-amount">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @if($isSummary)
                    <tr>
                        <td class="col-desc">
                            <strong>{{ $parentStructure?->label ?? ($invoice->student?->is_boarding ? 'Boarding Student Fees Package' : 'Day-Scholar Student Fees Package') }}</strong>
                            <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                                Comprehensive school fees package including Tuition, Building Fund, GPF, Sports, ICT, Science Levy & {{ $invoice->student?->is_boarding ? 'Boarding' : 'Lunch Meals' }}.
                            </div>
                        </td>
                        <td class="col-qty">1</td>
                        <td class="col-price">${{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="col-amount">${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                @else
                    @forelse($invoice->items as $item)
                        <tr>
                            <td class="col-desc">{{ $item->description }}</td>
                            <td class="col-qty">{{ $item->quantity }}</td>
                            <td class="col-price">${{ number_format($item->unit_amount, 2) }}</td>
                            <td class="col-amount">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="col-desc">School Tuition & General Fees</td>
                            <td class="col-qty">1</td>
                            <td class="col-price">${{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="col-amount">${{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>

        <!-- Summary Totals -->
        <div class="summary-block">
            <div class="summary-row">
                <span class="summary-label-main">TOTAL INVOICE</span>
                <span class="summary-val-main">${{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label-sub">LESS PAYMENTS</span>
                <span class="summary-val-sub">${{ number_format($totalPaid, 2) }}</span>
            </div>
            <div class="balance-banner">
                <span class="balance-banner-label">BALANCE DUE</span>
                <span class="balance-banner-val">${{ number_format($invoice->balance, 2) }}</span>
            </div>
        </div>

        <!-- Banking & Payment Details -->
        <div class="banking-card">
            <div class="banking-title">BANKING & PAYMENT DETAILS</div>
            <div class="banking-subtitle">Please use the invoice reference when making payment.</div>
            <div class="banking-grid">
                <div class="bank-field-label">BANK NAME</div>
                <div class="bank-field-val">{{ $branding['bank_name'] ?: 'To be provided by school' }}</div>

                <div class="bank-field-label">ACCOUNT NAME</div>
                <div class="bank-field-val">{{ $branding['bank_account_name'] ?: '____________________________' }}</div>

                <div class="bank-field-label">ACCOUNT NUMBER</div>
                <div class="bank-field-val" style="font-family: monospace;">{{ $branding['bank_account_number'] ?: '____________________________' }}</div>

                <div class="bank-field-label">BRANCH / REFERENCE</div>
                <div class="bank-field-val">{{ $branding['bank_branch'] ?: '____________________________' }}</div>
            </div>
        </div>

        <!-- Payment Note -->
        <div class="payment-note-block">
            <div class="payment-note-label">PAYMENT NOTE</div>
            <div class="payment-note-text">Kindly retain proof of payment and submit it to the school accounts office.</div>
        </div>

        <!-- Footer -->
        <div class="doc-footer">
            <div class="footer-left">
                <div>Official computer-generated invoice @if(!empty($branding['is_historical_snapshot'])) | Historical snapshot @endif</div>
                <div style="margin-top: 2px;">{{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }} &bull; Accounts Department</div>
            </div>
            <div class="footer-right">
                <div class="footer-motto">{{ $branding['motto'] ?? 'Excellence in Education' }}</div>
                <div style="margin-top: 2px;">Page 1 of 1</div>
            </div>
        </div>
    </div>
</body>
</html>
