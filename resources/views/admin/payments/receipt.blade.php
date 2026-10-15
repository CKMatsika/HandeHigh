<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt PAY-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }} - {{ $payment->school_branding['display_name'] ?? 'School' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        body { background: #f8fafc; color: #0f172a; padding: 24px; }
        .receipt-container { max-width: 720px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .no-print { margin-bottom: 20px; display: flex; justify-content: flex-end; gap: 10px; }
        .btn { background: #10b981; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { background: #059669; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        
        .header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; }
        .header-left { display: flex; gap: 16px; align-items: flex-start; }
        .logo-box img { max-height: 68px; max-width: 68px; object-fit: contain; }
        .crest-box { width: 60px; height: 60px; border-radius: 8px; background: linear-gradient(135deg, #1e3a8a, #059669); color: #fff; font-weight: bold; font-size: 20px; display: flex; align-items: center; justify-content: center; }
        .school-name { font-size: 18px; font-weight: 700; text-transform: uppercase; color: #0f172a; }
        .motto { font-size: 11px; font-style: italic; color: #475569; margin-bottom: 2px; }
        .school-info { font-size: 11px; color: #475569; line-height: 1.4; }
        .reg-info { font-size: 10px; color: #64748b; margin-top: 2px; font-family: monospace; }
        
        .header-right { text-align: right; }
        .doc-title { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #059669; letter-spacing: 0.5px; }
        .doc-meta { font-size: 11px; color: #334155; margin-top: 4px; }
        .doc-meta strong { color: #0f172a; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 11px; }
        .info-col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .info-label { color: #64748b; }
        .info-val { font-weight: 600; color: #0f172a; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        .items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
        .items-table th { background: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 10px; color: #475569; }
        .items-table .text-right { text-align: right; font-family: monospace; }
        .total-row { font-weight: 700; background: #f8fafc; font-size: 12px; }
        .total-amount-badge { font-size: 16px; font-weight: 800; color: #059669; }

        .payment-summary-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .payment-summary-text { font-size: 12px; color: #065f46; font-weight: 600; }
        .payment-summary-amount { font-size: 18px; font-weight: 800; color: #047857; font-family: monospace; }

        .signature-section { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 36px; padding-top: 20px; font-size: 11px; }
        .signature-box { border-top: 1px dashed #94a3b8; padding-top: 8px; text-align: center; color: #475569; }
        .stamp-box { border: 2px dashed #cbd5e1; border-radius: 8px; height: 70px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #64748b; text-align: center; line-height: 1.5; }
        .footer-note { font-style: italic; color: #059669; font-weight: 600; margin-bottom: 4px; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { border: none; box-shadow: none; max-width: 100%; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $branding = $payment->school_branding;
        $primaryInvoice = $payment->allocations?->first()?->invoice;
    @endphp

    <div class="receipt-container">
        <div class="no-print">
            <button class="btn" onclick="window.print()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Receipt
            </button>
            @if($primaryInvoice)
                <a href="{{ route('admin.invoices.show', $primaryInvoice) }}" class="btn btn-secondary">Back to Invoice</a>
            @else
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Back to Invoices</a>
            @endif
        </div>

        <!-- School Header -->
        <div class="header">
            <div class="header-left">
                @if(!empty($branding['logo_base64']) || !empty($branding['logo_url']))
                    <div class="logo-box">
                        <img src="{{ $branding['logo_base64'] ?: $branding['logo_url'] }}" alt="{{ $branding['name'] }}">
                    </div>
                @else
                    <div class="crest-box" style="background: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#1e3a8a' }}, {{ $branding['secondary_color'] ?? '#059669' }});">
                        {{ strtoupper(substr($branding['name'] ?? 'H', 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="school-name">{{ $branding['name'] }}</div>
                    @if(!empty($branding['motto']))
                        <div class="motto">"{{ $branding['motto'] }}"</div>
                    @endif
                    <div class="school-info">{{ $branding['formatted_address'] }}</div>
                    <div class="school-info">{{ $branding['formatted_contacts'] }}</div>
                    @if(!empty($branding['registration_number']) || !empty($branding['zimsec_center_number']))
                        <div class="reg-info">
                            @if(!empty($branding['registration_number'])) Reg: {{ $branding['registration_number'] }} @endif
                            @if(!empty($branding['registration_number']) && !empty($branding['zimsec_center_number'])) &bull; @endif
                            @if(!empty($branding['zimsec_center_number'])) ZIMSEC: {{ $branding['zimsec_center_number'] }} @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="header-right">
                <div class="doc-title">Official Fee Receipt</div>
                <div class="doc-meta">Receipt No: <strong>REC-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
                <div class="doc-meta">Date Paid: <strong>{{ $payment->paid_at?->format('d M Y') ?? now()->format('d M Y') }}</strong></div>
                <div class="doc-meta">Status: <strong style="color: #059669; text-transform: uppercase;">{{ $payment->status ?? 'Completed' }}</strong></div>
            </div>
        </div>

        <!-- Summary Banner -->
        <div class="payment-summary-box">
            <div class="payment-summary-text">
                Amount Received in Full / Partial Settlement
            </div>
            <div class="payment-summary-amount">
                {{ $payment->account?->currency ?? 'USD' }} {{ number_format($payment->amount, 2) }}
            </div>
        </div>

        <!-- Details Grid -->
        <div class="info-grid">
            <div>
                <div class="info-col-title">Student & Guardian Details</div>
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-val">{{ $payment->student?->first_name }} {{ $payment->student?->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student Reg / ID:</span>
                    <span class="info-val">{{ $payment->student?->admission_number ?? $payment->student?->id ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Grade / Class:</span>
                    <span class="info-val">{{ $payment->student?->grade }} {{ $payment->student?->class_name }}</span>
                </div>
                @if($payment->guardian)
                    <div class="info-row">
                        <span class="info-label">Guardian / Payer:</span>
                        <span class="info-val">{{ $payment->guardian->first_name }} {{ $payment->guardian->last_name }}</span>
                    </div>
                @endif
            </div>

            <div>
                <div class="info-col-title">Transaction & Account Details</div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-val">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Deposit Account:</span>
                    <span class="info-val">{{ $payment->account ? ($payment->account->code . ' - ' . $payment->account->name) : 'Operating Bank / Cash' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Reference / Slip:</span>
                    <span class="info-val">{{ $payment->reference ?: ('PAY-' . $payment->id) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Recorded At:</span>
                    <span class="info-val">{{ $payment->created_at?->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Allocation Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Academic Period</th>
                    <th class="text-right">Invoice Total</th>
                    <th class="text-right">Amount Paid</th>
                    <th class="text-right">Balance Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payment->allocations as $allocation)
                    <tr>
                        <td><strong>{{ $allocation->invoice?->number ?? '—' }}</strong></td>
                        <td>{{ $allocation->invoice?->academic_year }} &bull; {{ $allocation->invoice?->term }}</td>
                        <td class="text-right">{{ number_format($allocation->invoice?->total_amount ?? 0, 2) }}</td>
                        <td class="text-right" style="font-weight: 700; color: #059669;">{{ number_format($allocation->amount, 2) }}</td>
                        <td class="text-right" style="font-weight: 600;">{{ number_format($allocation->invoice?->balance ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 12px;">General fee receipt recorded against student account.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Received:</td>
                    <td class="text-right total-amount-badge">{{ number_format($payment->amount, 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures & Stamp -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Authorized Bursar / Accounts Clerk</p>
                <p style="margin-top: 14px; color: #94a3b8; font-size: 10px;">Signature & Date</p>
            </div>
            <div class="stamp-box">
                Official School Stamp
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-note">Thank you for your payment!</p>
            <p>This is an official computer-generated receipt from {{ $branding['name'] }}. Valid without alterations.</p>
        </div>
    </div>
</body>
</html>
