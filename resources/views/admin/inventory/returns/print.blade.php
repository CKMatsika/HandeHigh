<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Note {{ $purchaseReturn->debit_note_number }} - {{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }}</title>
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
            max-width: 820px;
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
            background-color: #b45309;
            color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background-color: #92400e;
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

        .document-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #b45309;
            padding-bottom: 16px;
        }

        .school-identity {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            max-width: 480px;
        }

        .crest-box {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #78350f;
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
            color: #fde68a;
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
            font-size: 10.5px;
            color: #64748b;
            line-height: 1.35;
        }

        .doc-title-block {
            text-align: right;
        }

        .doc-type-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #fef3c7;
            color: #92400e;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #92400e;
        }

        .doc-debit-number {
            font-size: 13px;
            font-weight: 800;
            font-family: monospace;
            color: #b45309;
            margin-top: 3px;
        }

        .doc-return-ref {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
        }

        .info-box-title {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .info-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }

        .info-table td.label {
            width: 38%;
            color: #64748b;
            font-weight: 600;
        }

        .info-table td.val {
            color: #0f172a;
            font-weight: 700;
        }

        .reason-banner {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 11px;
        }

        .reason-banner strong {
            color: #92400e;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
        }

        .items-table th {
            background-color: #78350f;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }

        .items-table th.num { text-align: center; width: 36px; }
        .items-table th.qty { text-align: right; width: 100px; }
        .items-table th.rate { text-align: right; width: 100px; }
        .items-table th.total { text-align: right; width: 115px; }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: top;
        }

        .items-table td.num { text-align: center; color: #94a3b8; font-weight: 600; }
        .items-table td.qty { text-align: right; font-weight: 700; color: #b45309; }
        .items-table td.rate { text-align: right; font-family: monospace; font-size: 11px; }
        .items-table td.total { text-align: right; font-family: monospace; font-size: 11.5px; font-weight: 700; color: #78350f; }

        .items-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .item-code {
            font-size: 9.5px;
            font-family: monospace;
            color: #64748b;
            margin-top: 1px;
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .totals-card {
            width: 300px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 10px 14px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 0;
            font-size: 11px;
        }

        .totals-row.grand-total {
            border-top: 2px solid #b45309;
            margin-top: 6px;
            padding-top: 6px;
            font-size: 13px;
            font-weight: 800;
            color: #92400e;
        }

        .totals-row.grand-total .val {
            font-family: monospace;
            font-size: 14px;
        }

        .notes-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 24px;
            font-size: 10.5px;
            color: #475569;
        }

        .notes-title {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 30px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            height: 38px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 6px;
        }

        .sig-role {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .sig-subtext {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-footer {
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9px;
            color: #64748b;
        }

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

            .document-card {
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .signatures-grid, .totals-section, .notes-card, .doc-footer {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $totalDebitAmount = (float) $purchaseReturn->items->sum('total_cost');
        $schoolModel = $school ?: auth()->user()?->school;
        $logoSrc = null;
        if (!empty($branding['logo_base64'])) {
            $logoSrc = $branding['logo_base64'];
        } elseif (!empty($branding['logo_url'])) {
            $logoSrc = $branding['logo_url'];
        } elseif (!empty($schoolModel?->logo_base64)) {
            $logoSrc = $schoolModel->logo_base64;
        } elseif (!empty($schoolModel?->logo)) {
            $logoSrc = asset('storage/' . $schoolModel->logo);
        }
    @endphp

    <div class="screen-toolbar no-print">
        <div>
            <div class="toolbar-title">Debit Note {{ $purchaseReturn->debit_note_number }}</div>
            <div class="toolbar-subtitle">Supplier Return Voucher &amp; Commercial Debit Note</div>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('admin.inventory.returns.show', $purchaseReturn) }}" class="btn btn-secondary">&larr; Back to Return</a>
            <button onclick="window.print()" class="btn btn-primary">Print Debit Note</button>
        </div>
    </div>

    <div class="document-card">
        {{-- Header --}}
        <header class="doc-header">
            <div class="school-identity">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="School Logo" class="school-logo-img" data-logo="{{ $schoolModel?->logo }}">
                @else
                    <div class="crest-box">
                        <div class="crest-initials">{{ substr($branding['display_name'] ?? $branding['name'] ?? 'HH', 0, 2) }}</div>
                        <div class="crest-tag">HHS</div>
                    </div>
                @endif
                <div class="school-details">
                    <h1>{{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }}</h1>
                    @if(!empty($branding['motto']))
                        <div class="school-motto">"{{ $branding['motto'] }}"</div>
                    @endif
                    <div class="school-subtext">
                        @if(!empty($branding['formatted_address']))
                            <div>{{ $branding['formatted_address'] }}</div>
                        @endif
                        <div>
                            @if(!empty($branding['phone'])) Tel: {{ $branding['phone'] }} @endif
                            @if(!empty($branding['email'])) · Email: {{ $branding['email'] }} @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="doc-title-block">
                <span class="doc-type-badge">Official Financial Chargeback</span>
                <div class="doc-title">COMMERCIAL DEBIT NOTE</div>
                <div class="doc-debit-number">{{ $purchaseReturn->debit_note_number }}</div>
                <div class="doc-return-ref">Return Ref: {{ $purchaseReturn->return_number }}</div>
            </div>
        </header>

        {{-- Supplier & Order Context --}}
        <section class="info-grid">
            <div class="info-box">
                <div class="info-box-title">Supplier / Vendor Debited</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Vendor Name:</td>
                        <td class="val">{{ $purchaseReturn->vendor->name }}</td>
                    </tr>
                    @if($purchaseReturn->vendor->contact_person)
                    <tr>
                        <td class="label">Contact Person:</td>
                        <td class="val">{{ $purchaseReturn->vendor->contact_person }}</td>
                    </tr>
                    @endif
                    @if($purchaseReturn->vendor->phone)
                    <tr>
                        <td class="label">Phone:</td>
                        <td class="val">{{ $purchaseReturn->vendor->phone }}</td>
                    </tr>
                    @endif
                    @if($purchaseReturn->vendor->email)
                    <tr>
                        <td class="label">Email:</td>
                        <td class="val">{{ $purchaseReturn->vendor->email }}</td>
                    </tr>
                    @endif
                    @if($purchaseReturn->vendor->address)
                    <tr>
                        <td class="label">Address:</td>
                        <td class="val">{{ $purchaseReturn->vendor->address }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <div class="info-box">
                <div class="info-box-title">Audit &amp; Stores Reference</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Return Date:</td>
                        <td class="val">{{ $purchaseReturn->return_date?->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">GRN Inward Ref:</td>
                        <td class="val" style="font-family: monospace;">{{ $purchaseReturn->goodsReceivedVoucher->voucher_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Purchase Order Ref:</td>
                        <td class="val" style="font-family: monospace;">{{ $purchaseReturn->purchaseOrder->purchase_order_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Origin Warehouse:</td>
                        <td class="val">{{ $purchaseReturn->warehouse->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Primary Reason:</td>
                        <td class="val" style="color: #92400e;">{{ ucfirst(str_replace('_', ' ', $purchaseReturn->reason)) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Processed By:</td>
                        <td class="val">{{ $purchaseReturn->creator->name }}</td>
                    </tr>
                </table>
            </div>
        </section>

        @if($purchaseReturn->reason_details)
            <div class="reason-banner">
                <strong>Inspection &amp; Rejection Remarks:</strong> {{ $purchaseReturn->reason_details }}
            </div>
        @endif

        {{-- Returned Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th>Item Description</th>
                    <th style="width: 70px;">UOM</th>
                    <th class="qty">Qty Returned</th>
                    <th class="rate">Unit Cost</th>
                    <th class="total">Debit Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseReturn->items as $index => $line)
                    <tr>
                        <td class="num">{{ $index + 1 }}</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $line->item->name }}</div>
                            <div class="item-code">SKU/Code: {{ $line->item->code }}</div>
                            @if($line->remarks)
                                <div style="font-size: 10px; color: #b45309; margin-top: 1px;">Remarks: {{ $line->remarks }}</div>
                            @endif
                        </td>
                        <td>{{ $line->item->unit_of_measure }}</td>
                        <td class="qty">{{ number_format((float) $line->quantity_returned, 2) }}</td>
                        <td class="rate">{{ number_format((float) $line->unit_cost, 2) }}</td>
                        <td class="total">{{ number_format((float) $line->total_cost, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals Block --}}
        <div class="totals-section">
            <div class="totals-card">
                <div class="totals-row">
                    <span style="color: #64748b; font-weight: 600;">Items Returned:</span>
                    <span style="font-weight: 700;">{{ $purchaseReturn->items->count() }} line(s)</span>
                </div>
                <div class="totals-row">
                    <span style="color: #64748b; font-weight: 600;">Total Units Deducted:</span>
                    <span style="font-weight: 700;">{{ number_format((float) $purchaseReturn->items->sum('quantity_returned'), 2) }}</span>
                </div>
                <div class="totals-row grand-total">
                    <span>TOTAL DEBIT AMOUNT:</span>
                    <span class="val">{{ number_format($totalDebitAmount, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Terms & Accounting Impact Notice --}}
        <div class="notes-card">
            <div class="notes-title">Commercial Notice &amp; Accounting Adjustment</div>
            <div>1. Please credit our account with the total debit sum of <strong>{{ number_format($totalDebitAmount, 2) }}</strong> against invoice(s) or upcoming supplier payments.</div>
            <div>2. Inventory records for warehouse <strong>{{ $purchaseReturn->warehouse->name }}</strong> have been reduced and General Ledger Received-Not-Invoiced accruals adjusted.</div>
            <div>3. The goods described above are available for collection / have been handed over to your representative.</div>
        </div>

        {{-- Signatures --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Returned By (Stores Officer)</div>
                <div class="sig-subtext">{{ $purchaseReturn->creator->name }} · {{ $purchaseReturn->return_date?->format('d M Y') }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Authorized By (Bursar / Finance)</div>
                <div class="sig-subtext">{{ $branding['bursar_name'] ?? 'School Bursar' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Vendor Collection Sign-off</div>
                <div class="sig-subtext">Representative Name, ID &amp; Date</div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="doc-footer">
            <div>{{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }} · Stores &amp; Finance</div>
            <div>System Generated: {{ now()->format('d M Y, H:i') }} · Page 1 of 1</div>
        </footer>
    </div>
</body>
</html>
