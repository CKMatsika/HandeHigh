<!DOCTYPE html>
<html>
<head>
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
        }
        .receipt-info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        @media print {
            body { padding: 0; }
            .receipt-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="school-name">{{ $receipt->school->name ?? 'School Management System' }}</div>
            <div>{{ $receipt->school->address ?? 'School Address' }}</div>
            <div>{{ $receipt->school->phone ?? 'Phone: +263 XXX XXX XXX' }}</div>
            <div class="receipt-title">OFFICIAL RECEIPT</div>
        </div>

        <div class="receipt-info">
            <div class="info-row">
                <span>Receipt No:</span>
                <span>{{ $receipt->receipt_number }}</span>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span>{{ $receipt->receipt_date->format('M j, Y') }}</span>
            </div>
            <div class="info-row">
                <span>Type:</span>
                <span>{{ ucfirst($receipt->type) }}</span>
            </div>
            <div class="info-row">
                <span>Payment Method:</span>
                <span>{{ ucfirst(str_replace('_', ' ', $receipt->payment_method)) }}</span>
            </div>
            @if($receipt->customer || $receipt->customer_name)
                <div class="info-row">
                    <span>Customer:</span>
                    <span>
                        @if($receipt->customer)
                            {{ $receipt->customer->name }}
                        @else
                            {{ $receipt->customer_name }}
                        @endif
                    </span>
                </div>
            @endif
            @if($receipt->reference)
                <div class="info-row">
                    <span>Reference:</span>
                    <span>{{ $receipt->reference }}</span>
                </div>
            @endif
        </div>

        @if($receipt->description)
            <div style="margin-bottom: 15px; font-size: 11px;">
                <strong>Description:</strong> {{ $receipt->description }}
            </div>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($receipt->total_amount, 2) }}</span>
            </div>
            @if($receipt->tax_amount > 0)
                <div class="total-row">
                    <span>Tax:</span>
                    <span>${{ number_format($receipt->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span>${{ number_format($receipt->grand_total, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            <div>Thank you for your business!</div>
            <div>This receipt is computer generated</div>
            <div>Generated on: {{ now()->format('M j, Y g:i A') }}</div>
            @if($receipt->creator)
                <div>Processed by: {{ $receipt->creator->name }}</div>
            @endif
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
