<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOPSE Official Receipt #{{ $payment->mopse_receipt_number ?? ('PAY-' . $payment->id) }} - Hande Secondary School</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace, -apple-system, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 24px 12px;
            font-size: 13px;
        }

        .screen-toolbar {
            max-width: 480px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .btn-primary {
            background: #059669;
            color: #ffffff;
        }
        .btn-primary:hover { background: #047857; }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover { background: #f8fafc; }

        /* Physical Slip Container */
        .voucher-slip {
            max-width: 460px;
            margin: 0 auto;
            background: #ffffff;
            border: 2px solid #1e293b;
            padding: 20px 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .top-slogan {
            text-align: right;
            font-size: 10px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .header-title {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.8px;
            line-height: 1.3;
            color: #0f172a;
            text-transform: uppercase;
        }

        .sub-header-title {
            text-align: center;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 1.2px;
            margin-top: 2px;
            margin-bottom: 14px;
            color: #0f172a;
            text-transform: uppercase;
        }

        .meta-line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 6px;
            font-size: 12.5px;
        }

        .dotted-fill {
            border-bottom: 1px dotted #334155;
            padding-left: 6px;
            font-weight: 700;
            color: #0f172a;
        }

        .receipt-no-highlight {
            font-size: 17px;
            font-weight: 900;
            color: #047857;
            font-family: monospace;
            letter-spacing: 1px;
        }

        /* Vote Table */
        .voucher-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 12px;
            border: 1.5px solid #0f172a;
        }

        .voucher-table th, .voucher-table td {
            border: 1px solid #0f172a;
            padding: 4px 8px;
            font-size: 11.5px;
        }

        .voucher-table th {
            text-align: center;
            font-weight: 800;
            background: #f1f5f9;
        }

        .col-desc {
            width: 68%;
            text-align: left;
            font-weight: 600;
        }

        .col-dollars {
            width: 20%;
            text-align: right;
            font-family: monospace;
            font-weight: 700;
        }

        .col-cents {
            width: 12%;
            text-align: center;
            font-family: monospace;
            font-weight: 700;
        }

        .row-total {
            font-weight: 900;
            font-size: 12.5px;
            background: #f8fafc;
        }

        /* Sum in words block */
        .words-section {
            margin-top: 10px;
            font-size: 12px;
            line-height: 1.5;
        }

        .words-line {
            border-bottom: 1px dotted #334155;
            display: inline-block;
            font-weight: 700;
            color: #1e3a8a;
            font-style: italic;
        }

        /* Signature and Stamp */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 24px;
            padding-bottom: 4px;
        }

        .sig-block {
            width: 55%;
        }

        .sig-line {
            border-bottom: 1px dotted #334155;
            height: 24px;
            margin-bottom: 4px;
        }

        .stamp-box {
            width: 130px;
            height: 65px;
            border: 1.5px dashed #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Printflow footer note */
        .slip-footer {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #64748b;
            font-style: italic;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .screen-toolbar {
                display: none !important;
            }
            .voucher-slip {
                border: 2px solid #000000;
                box-shadow: none;
                max-width: 100%;
                width: 100%;
                margin: 0;
                padding: 16px 20px;
            }
        }
    </style>
</head>
<body>
    @php
        $primaryInvoice = $payment->allocations?->first()?->invoice;
        $student = $payment->student;
        $termStr = $primaryInvoice?->term ?? 'Term 3';
        if (preg_match('/(\d+)/', $termStr, $m)) {
            $termDisplay = $m[1];
        } else {
            $termDisplay = $termStr;
        }
        $formDisplay = $student?->grade ?? '4';
        if (preg_match('/(\d+)/', $formDisplay, $fm)) {
            $formNumber = $fm[1];
        } else {
            $formNumber = $formDisplay;
        }
    @endphp

    <div class="screen-toolbar">
        <a href="{{ $primaryInvoice ? route('admin.invoices.show', $primaryInvoice) : route('admin.invoices.index') }}" class="btn btn-secondary">
            &larr; Back
        </a>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.payments.receipt', $payment) }}" class="btn btn-secondary">
                Standard Receipt View
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                Print MOPSE Voucher
            </button>
        </div>
    </div>

    <div class="voucher-slip">
        <div class="top-slogan">
            DD August 26
        </div>

        <div class="header-title">
            BUHERA RURAL DISTRICT COUNCIL
        </div>
        <div class="sub-header-title">
            HANDE SECONDARY SCHOOL
        </div>

        <div class="meta-line">
            <div>
                Date: <span class="dotted-fill" style="min-width: 100px; display: inline-block;">{{ $payment->paid_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
            </div>
            <div>
                Receipt No: <span class="receipt-no-highlight">{{ $payment->mopse_receipt_number ?? str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>

        <div class="meta-line">
            <div style="flex: 1; display: flex;">
                Name of Child: <span class="dotted-fill" style="flex: 1; margin-right: 12px;">{{ strtoupper($student?->last_name . ' ' . $student?->first_name) }}</span>
            </div>
        </div>

        <div class="meta-line">
            <div style="width: 50%;">
                Class/Form: <span class="dotted-fill" style="display: inline-block; min-width: 45px;">{{ $formNumber }}</span>
            </div>
            <div style="width: 50%; text-align: right;">
                Term: <span class="dotted-fill" style="display: inline-block; min-width: 45px; text-align: center;">{{ $termDisplay }}</span>
            </div>
        </div>

        <!-- Schedule Grid Table -->
        <table class="voucher-table">
            <thead>
                <tr>
                    <th class="col-desc">VOTE / CATEGORY</th>
                    <th class="col-dollars">$</th>
                    <th class="col-cents">c</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scheduleRows as $voteKey => $row)
                    @php
                        $dollars = (int) floor($row['amount']);
                        $cents = (int) round(($row['amount'] - $dollars) * 100);
                        $hasVal = $row['amount'] > 0.001;
                    @endphp
                    <tr>
                        <td class="col-desc">{{ $row['label'] }}</td>
                        <td class="col-dollars">{{ $hasVal ? $dollars : '' }}</td>
                        <td class="col-cents">{{ $hasVal ? str_pad($cents, 2, '0', STR_PAD_LEFT) : '' }}</td>
                    </tr>
                @endforeach

                @php
                    $totalDollars = (int) floor($payment->amount);
                    $totalCents = (int) round(($payment->amount - $totalDollars) * 100);
                    $balance = $primaryInvoice ? (float)$primaryInvoice->balance : 0.0;
                    $balDollars = (int) floor($balance);
                    $balCents = (int) round(($balance - $balDollars) * 100);
                @endphp

                <tr>
                    <td class="col-desc" style="font-weight: 800;">BALANCE</td>
                    <td class="col-dollars" style="color: #64748b;">{{ $balance > 0 ? $balDollars : '' }}</td>
                    <td class="col-cents" style="color: #64748b;">{{ $balance > 0 ? str_pad($balCents, 2, '0', STR_PAD_LEFT) : '' }}</td>
                </tr>
                <tr class="row-total">
                    <td class="col-desc" style="font-size: 13px; font-weight: 900;">TOTAL</td>
                    <td class="col-dollars" style="font-size: 14px; font-weight: 900; color: #047857;">{{ $totalDollars }}</td>
                    <td class="col-cents" style="font-size: 13px; font-weight: 900; color: #047857;">{{ str_pad($totalCents, 2, '0', STR_PAD_LEFT) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- The sum of words -->
        <div class="words-section">
            The sum of: <span class="words-line">{{ $amountInWords }}</span>
        </div>

        <!-- Signature and Stamp Block -->
        <div class="bottom-section">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div style="font-size: 11px; font-weight: 700; color: #475569;">
                    Signature (Accounts Clerk)
                </div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                    Issued by: {{ $payment->creator?->name ?? (Auth::user()?->name ?? 'Bursar') }}
                </div>
            </div>

            <div class="stamp-box">
                STAMP
            </div>
        </div>

        <!-- Footers -->
        <div class="slip-footer">
            <span>Printed by Printflow (Private) Limited</span>
            <span style="font-weight: bold; color: #e11d48;">Say no to all forms of violence against children</span>
        </div>
    </div>
</body>
</html>
