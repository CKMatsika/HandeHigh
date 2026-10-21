<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bid Evaluation Schedule {{ $rfq->rfq_number }} - {{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }}</title>
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
            font-size: 11px;
            line-height: 1.35;
        }

        .screen-toolbar {
            max-width: 1060px;
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
            background-color: #4338ca;
            color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background-color: #3730a3;
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
            max-width: 1060px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            border-bottom: 2px solid #312e81;
            padding-bottom: 14px;
        }

        .school-identity {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            max-width: 580px;
        }

        .crest-box {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #312e81;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-align: center;
        }

        .crest-initials {
            font-size: 14px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .crest-tag {
            font-size: 6px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #c7d2fe;
            margin-top: 1px;
        }

        .school-logo-img {
            max-height: 50px;
            max-width: 50px;
            height: auto;
            width: auto;
            object-fit: contain;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .school-details h1 {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            margin-bottom: 2px;
        }

        .school-motto {
            font-size: 10.5px;
            font-style: italic;
            color: #64748b;
            font-family: Georgia, serif;
            margin-bottom: 2px;
        }

        .school-subtext {
            font-size: 10px;
            color: #64748b;
            line-height: 1.3;
        }

        .doc-title-block {
            text-align: right;
        }

        .doc-type-badge {
            display: inline-block;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #e0e7ff;
            color: #3730a3;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e1b4b;
        }

        .doc-ref {
            font-size: 12px;
            font-weight: 800;
            font-family: monospace;
            color: #4338ca;
            margin-top: 2px;
        }

        .meta-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 10.5px;
        }

        .meta-strip-item .label {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .meta-strip-item .val {
            font-weight: 700;
            color: #0f172a;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10px;
        }

        .matrix-table th {
            background-color: #1e1b4b;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #312e81;
            text-align: center;
        }

        .matrix-table th.vendor-th {
            background-color: #312e81;
        }

        .matrix-table th.winning-th {
            background-color: #064e3b;
            border-color: #047857;
        }

        .matrix-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }

        .matrix-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .matrix-table td.winning-td {
            background-color: #f0fdf4 !important;
            font-weight: 700;
            color: #166534;
        }

        .matrix-table tfoot td {
            font-weight: 700;
            background-color: #f1f5f9;
            border-top: 2px solid #0f172a;
            padding: 7px 8px;
        }

        .award-banner {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .award-banner-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #166534;
            margin-bottom: 4px;
        }

        .award-details {
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.4;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            height: 32px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 5px;
        }

        .sig-role {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .sig-subtext {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-footer {
            border-top: 1px solid #e2e8f0;
            margin-top: 16px;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 8.5px;
            color: #64748b;
        }

        @page {
            size: A4 landscape;
            margin: 8mm 10mm 8mm 10mm;
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

            .signatures-grid, .award-banner, .doc-footer {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php
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
        $comparison = $rfq->comparison;
        $winningQuoteId = $comparison?->winning_quotation_id;
    ?>

    <div class="screen-toolbar no-print">
        <div>
            <div class="toolbar-title">Bid Evaluation &amp; Comparison Schedule {{ $rfq->rfq_number }}</div>
            <div class="toolbar-subtitle">Tender Committee Comparative Matrix · {{ $rfq->title }}</div>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('admin.procurement.rfq.comparison', $rfq) }}" class="btn btn-secondary">&larr; Back to Comparison</a>
            <button onclick="window.print()" class="btn btn-primary">Print Schedule</button>
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
                        <div>{{ $branding['formatted_address'] ?? 'Official Procurement &amp; Tender Committee Record' }}</div>
                        <div>Tender Secretariat · Procurement Unit</div>
                    </div>
                </div>
            </div>
            <div class="doc-title-block">
                <span class="doc-type-badge">Tender Committee Report</span>
                <div class="doc-title">BID COMPARISON SCHEDULE</div>
                <div class="doc-ref">RFQ: {{ $rfq->rfq_number }}</div>
            </div>
        </header>

        {{-- Meta strip --}}
        <div class="meta-strip">
            <div class="meta-strip-item">
                <div class="label">Procurement Title</div>
                <div class="val">{{ Str::limit($rfq->title, 40) }}</div>
            </div>
            <div class="meta-strip-item">
                <div class="label">Requisition Ref / Department</div>
                <div class="val">{{ $rfq->requisition->requisition_number ?? 'Direct RFQ' }}</div>
            </div>
            <div class="meta-strip-item">
                <div class="label">Evaluation Date</div>
                <div class="val">{{ $comparison?->evaluation_date?->format('d M Y') ?? now()->format('d M Y') }}</div>
            </div>
            <div class="meta-strip-item">
                <div class="label">Comparison Status</div>
                <div class="val" style="color: {{ $comparison?->status === 'approved' ? '#166534' : '#b45309' }};">
                    {{ ucfirst($comparison?->status ?? 'Draft / In Progress') }}
                </div>
            </div>
        </div>

        {{-- Matrix Table --}}
        <table class="matrix-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 28px;">#</th>
                    <th rowspan="2" style="text-align: left;">Item Description &amp; Specifications</th>
                    <th rowspan="2" style="width: 50px;">Req Qty</th>
                    @foreach($rfq->quotations as $quotation)
                        <?php $isWinner = (int) $quotation->id === (int) $winningQuoteId; ?>
                        <th colspan="2" class="{{ $isWinner ? 'winning-th' : 'vendor-th' }}">
                            {{ $quotation->vendor->name }}
                            @if($isWinner)
                                <span style="font-size: 8px; background: #22c55e; color: #fff; padding: 1px 4px; border-radius: 3px; margin-left: 3px;">AWARDED</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($rfq->quotations as $quotation)
                        <?php $isWinner = (int) $quotation->id === (int) $winningQuoteId; ?>
                        <th class="{{ $isWinner ? 'winning-th' : 'vendor-th' }}" style="font-size: 8px;">Unit Cost</th>
                        <th class="{{ $isWinner ? 'winning-th' : 'vendor-th' }}" style="font-size: 8px;">Line Total</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $index => $row)
                    <tr>
                        <td style="text-align: center; color: #94a3b8;">{{ $loop->iteration }}</td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $row['item']->name }}</div>
                            <div style="font-size: 8.5px; color: #64748b;">SKU: {{ $row['item']->code }} @if($row['specifications']) · {{ Str::limit($row['specifications'], 40) }} @endif</div>
                        </td>
                        <td style="text-align: center; font-weight: 600;">{{ number_format((float) $row['quantity'], 2) }} {{ $row['item']->unit_of_measure }}</td>
                        @foreach($rfq->quotations as $quotation)
                            <?php
                                $quote = $row['quotes'][$quotation->id] ?? null;
                                $isWinner = (int) $quotation->id === (int) $winningQuoteId;
                            ?>
                            <td class="{{ $isWinner ? 'winning-td' : '' }}" style="text-align: right; font-family: monospace;">
                                {{ $quote && $quote['unit_price'] !== null ? number_format((float) $quote['unit_price'], 2) : '—' }}
                            </td>
                            <td class="{{ $isWinner ? 'winning-td' : '' }}" style="text-align: right; font-family: monospace;">
                                {{ $quote && $quote['total_price'] !== null ? number_format((float) $quote['total_price'], 2) : '—' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 10px;">TOTAL BID AMOUNT:</td>
                    @foreach($rfq->quotations as $quotation)
                        <?php $isWinner = (int) $quotation->id === (int) $winningQuoteId; ?>
                        <td colspan="2" class="{{ $isWinner ? 'winning-td' : '' }}" style="text-align: right; font-family: monospace; font-size: 11px;">
                            {{ number_format((float) $quotation->total_amount, 2) }}
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 9.5px; color: #64748b;">Lead Time (Days):</td>
                    @foreach($rfq->quotations as $quotation)
                        <td colspan="2" style="text-align: center; font-size: 9.5px; color: #475569;">
                            {{ $quotation->lead_time_days }} day(s)
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 9.5px; color: #64748b;">Payment Terms:</td>
                    @foreach($rfq->quotations as $quotation)
                        <td colspan="2" style="text-align: center; font-size: 9px; color: #64748b;">
                            {{ $quotation->payment_terms ?: 'Standard' }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>

        {{-- Award & Justification --}}
        @if($comparison)
            <div class="award-banner">
                <div class="award-banner-title">Tender Committee Award Determination &amp; Justification</div>
                <div class="award-details">
                    <div><strong>Winning Supplier:</strong> {{ $comparison->winningQuotation?->vendor?->name ?? 'None' }} (Quote #{{ $comparison->winningQuotation?->quotation_number }}) · <strong>Total Contract Sum:</strong> {{ number_format((float) ($comparison->winningQuotation?->total_amount ?? 0), 2) }}</div>
                    @if($comparison->purchaseOrder)
                        <div><strong>Purchase Order Generated:</strong> <span style="font-family: monospace; font-weight: 700;">{{ $comparison->purchaseOrder->purchase_order_number }}</span></div>
                    @endif
                    <div style="margin-top: 4px;"><strong>Committee Evaluation Rationale:</strong> {{ $comparison->selection_justification }}</div>
                </div>
            </div>
        @endif

        {{-- Committee Signatures --}}
        <div class="signatures-grid">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Evaluated By</div>
                <div class="sig-subtext">{{ $comparison?->evaluator?->name ?? 'Procurement Officer' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Reviewed By (Finance)</div>
                <div class="sig-subtext">{{ $branding['bursar_name'] ?? 'Bursar / Accounts' }}</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Tender Committee Chair</div>
                <div class="sig-subtext">Signature &amp; Date</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-role">Approved By (Accounting Officer)</div>
                <div class="sig-subtext">{{ $branding['principal_name'] ?? 'Head of School' }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="doc-footer">
            <div>{{ $branding['display_name'] ?? $branding['name'] ?? 'Hande High School' }} · Procurement &amp; Tender Committee</div>
            <div>Generated: {{ now()->format('d M Y, H:i') }} · Schedule Ref: {{ $comparison?->comparison_number ?? 'DRAFT' }} · Page 1 of 1</div>
        </footer>
    </div>
</body>
</html>
