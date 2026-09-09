<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #1e293b; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0;">{{ $school->name }}</h2>
        <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 13px;">Employee Payslip Notification</p>
    </div>
    
    <div style="background: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px;">
        <p>Dear <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong>,</p>
        
        <p>Your payslip for <strong>{{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->format('F Y') }}</strong> has been processed.</p>
        
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin: 20px 0;">
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Employee ID:</td>
                    <td style="font-weight: bold; text-align: right;">{{ $employee->employee_id }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">ZIMRA TIN:</td>
                    <td style="font-weight: bold; text-align: right;">{{ $employee->zimra_tin ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Gross Earnings (USD):</td>
                    <td style="font-weight: bold; text-align: right; color: #047857;">${{ number_format($item->gross_usd, 2) }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; padding: 4px 0;">Total Deductions (USD):</td>
                    <td style="font-weight: bold; text-align: right; color: #be123c;">${{ number_format($item->total_deductions_usd, 2) }}</td>
                </tr>
                <tr style="border-top: 1px solid #cbd5e1;">
                    <td style="font-size: 16px; font-weight: bold; color: #1e293b; padding-top: 8px;">Net Take-Home Pay (USD):</td>
                    <td style="font-size: 18px; font-weight: bold; text-align: right; color: #1d4ed8; padding-top: 8px;">${{ number_format($item->net_pay_usd, 2) }}</td>
                </tr>
                @if($item->net_pay_zwg > 0)
                <tr>
                    <td style="font-size: 14px; font-weight: bold; color: #1e293b;">Net Take-Home Pay (ZWG):</td>
                    <td style="font-size: 15px; font-weight: bold; text-align: right; color: #047857;">{{ number_format($item->net_pay_zwg, 2) }} ZWG</td>
                </tr>
                @endif
                <tr style="border-top: 1px dashed #cbd5e1;">
                    <td style="color: #64748b; padding-top: 8px;">Available Leave Balance:</td>
                    <td style="font-weight: bold; text-align: right; color: #b45309; padding-top: 8px;">{{ number_format($item->leave_balance, 1) }} days</td>
                </tr>
            </table>
        </div>

        <p style="font-size: 13px; color: #64748b;">
            This payslip has been prepared in compliance with the Zimbabwe Revenue Authority (ZIMRA), NSSA, and the Zimbabwe Labour Act.
        </p>

        <p style="font-size: 12px; color: #94a3b8; margin-top: 24px; border-top: 1px solid #f1f5f9; padding-top: 12px;">
            This is an automated notification from {{ $school->name }}. Please contact the HR or Finance office for any queries.
        </p>
    </div>
</body>
</html>
