<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $receipt->receipt_number }} - {{ $receipt->school_branding['display_name'] ?? 'School' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        body { background: #f8fafc; color: #0f172a; padding: 24px; }
        .receipt-container { max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .no-print { margin-bottom: 16px; display: flex; justify-content: flex-end; gap: 8px; }
        .btn { background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        
        .header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; }
        .header-left { display: flex; gap: 16px; align-items: flex-start; }
        .logo-box img { max-height: 64px; max-width: 64px; object-fit: contain; }
        .crest-box { width: 56px; height: 56px; border-radius: 8px; background: linear-gradient(135deg, #1e3a8a, #d97706); color: #fff; font-weight: bold; font-size: 18px; display: flex; align-items: center; justify-content: center; }
        .school-name { font-size: 18px; font-weight: 700; text-transform: uppercase; color: #0f172a; }
        .motto { font-size: 11px; font-style: italic; color: #475569; margin-bottom: 2px; }
        .school-info { font-size: 11px; color: #475569; line-height: 1.4; }
        .reg-info { font-size: 10px; color: #64748b; margin-top: 2px; font-family: monospace; }
        
        .header-right { text-align: right; }
        .doc-title { font-size: 14px; font-weight: 800; text-transform: uppercase; color: #2563eb; letter-spacing: 0.5px; }
        .doc-meta { font-size: 11px; color: #334155; margin-top: 4px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 11px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .info-label { color: #64748b; }
        .info-val { font-weight: 600; color: #0f172a; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        .items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
        .items-table th { background: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 10px; color: #475569; }
        .items-table .text-right { text-align: right; font-family: monospace; }
        .total-row { font-weight: 700; background: #f8fafc; font-size: 12px; }
        
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #64748b; text-align: center; space-y: 4px; }
        .footer-note { font-style: italic; margin-bottom: 4px; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { border: none; box-shadow: none; max-width: 100%; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $branding = $receipt->school_branding;
    @endphp

    <div class="receipt-container">
        <div class="no-print">
            <button class="btn" onclick="window.print()">Print Official Receipt</button>
            <a href="{{ route('admin.receipts.index') }}" class="btn btn-secondary">Back to Receipts</a>
        </div>

        <!-- School Header -->
        <div class="header">
            <div class="header-left">
                @if(!empty($branding['logo_base64']) || !empty($branding['logo_url']))
                    <div class="logo-box">
                        <img src="{{ $branding['logo_base64'] ?: $branding['logo_url'] }}" alt="{{ $branding['name'] }}">
                    </div>
                @else
                    <div class="crest-box" style="background: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#1e3a8a' }}, {{ $branding['secondary_color'] ?? '#d97706' }});">
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
                <div class="doc-title">Official Receipt</div>
                <div class="doc-meta">No: <strong>{{ $receipt->receipt_number }}</strong></div>
                <div class="doc-meta">Date: {{ $receipt->receipt_date->format('d M Y') }}</div>
                <div class="doc-meta">Type: {{ ucfirst($receipt->type) }}</div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="info-grid">
            <div>
                <div class="info-row">
                    <span class="info-label">Customer / Payee:</span>
                    <span class="info-val">{{ $receipt->customer?->name ?: ($receipt->customer_name ?: 'Walk-in Payee') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-val">{{ ucfirst(str_replace('_', ' ', $receipt->payment_method)) }}</span>
                </div>
                @if($receipt->reference)
                    <div class="info-row">
                        <span class="info-label">Payment Reference:</span>
                        <span class="info-val">{{ $receipt->reference }}</span>
                    </div>
                @endif
            </div>
            <div>
                <div class="info-row">
                    <span class="info-label">Receipt Status:</span>
                    <span class="info-val" style="color: #059669;">✓ Processed</span>
                </div>
                @if($receipt->bankAccount)
                    <div class="info-row">
                        <span class="info-label">Deposited To:</span>
                        <span class="info-val">{{ $receipt->bankAccount->account_name }} ({{ $receipt->bankAccount->bank_name }})</span>
                    </div>
                @endif
                @if($receipt->creator)
                    <div class="info-row">
                        <span class="info-label">Issued By:</span>
                        <span class="info-val">{{ $receipt->creator->name }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if($receipt->description)
            <div style="font-size: 11px; margin-bottom: 12px; color: #334155;">
                <strong>Description / Particulars:</strong> {{ $receipt->description }}
            </div>
        @endif

        <!-- Itemized Line Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item / Description</th>
                    <th class="text-right" style="width: 70px;">Qty</th>
                    <th class="text-right" style="width: 90px;">Price</th>
                    <th class="text-right" style="width: 100px;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right" style="font-weight: 600;">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right" style="border: none; padding-top: 10px;">Subtotal:</td>
                    <td class="text-right" style="padding-top: 10px; font-weight: 600;">${{ number_format($receipt->total_amount, 2) }}</td>
                </tr>
                @if($receipt->tax_amount > 0)
                    <tr>
                        <td colspan="3" class="text-right" style="border: none;">Tax:</td>
                        <td class="text-right font-mono">${{ number_format($receipt->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td colspan="3" class="text-right" style="font-weight: 800; font-size: 13px;">Grand Total Paid:</td>
                    <td class="text-right" style="font-weight: 800; font-size: 13px; color: #059669;">${{ number_format($receipt->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Document Footer -->
        <div class="footer">
            @if(!empty($branding['footer_text']))
                <div class="footer-note">{{ $branding['footer_text'] }}</div>
            @endif
            <div>Thank you for your payment. This is an official computer-generated receipt issued by {{ $branding['name'] }}.</div>
            <div style="margin-top: 4px; font-size: 9px;">Generated on {{ now()->format('d M Y, H:i:s') }} @if($branding['is_historical_snapshot']) &bull; (Preserved Identity Snapshot) @endif</div>
        </div>
    </div>
</body>
</html>
