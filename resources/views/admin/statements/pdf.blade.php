<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .statement-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        .student-info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .balance-info {
            background: #f5f5f5;
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .opening-balance-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school->name }}</div>
        <div>Student Statement</div>
        <div>{{ $academicYear }} {{ $term ? '- ' . $term : '' }}</div>
    </div>

    <div class="student-info">
        <div class="info-row">
            <span class="info-label">Student:</span>
            <span>{{ $student->first_name }} {{ $student->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Grade/Class:</span>
            <span>{{ $student->grade }} {{ $student->class_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Admission No:</span>
            <span>{{ $student->admission_number ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="balance-info">
        <div class="balance-row">
            <span>Opening Balance:</span>
            <span class="text-right">{{ number_format($openingBalance, 2) }}</span>
        </div>
        <div class="balance-row">
            <span>Closing Balance:</span>
            <span class="text-right"><strong>{{ number_format($closingBalance, 2) }}</strong></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if($openingBalance != 0)
                <tr class="opening-balance-row">
                    <td colspan="3">Opening Balance (Brought Forward)</td>
                    <td class="text-right">{{ number_format($openingBalance, 2) }}</td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($openingBalance, 2) }}</td>
                </tr>
            @endif
            @forelse($events as $event)
                <tr>
                    <td>{{ $event['date']->format('M j, Y') }}</td>
                    <td>{{ $event['reference'] }}</td>
                    <td>{{ $event['description'] }}</td>
                    <td class="text-right">{{ $event['debit'] ? number_format($event['debit'], 2) : '' }}</td>
                    <td class="text-right">{{ $event['credit'] ? number_format($event['credit'], 2) : '' }}</td>
                    <td class="text-right">{{ number_format($event['balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No activity found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated statement. For any queries, please contact the school administration.</p>
        <p>Generated on: {{ now()->format('M j, Y H:i') }}</p>
    </div>
</body>
</html>
