<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .statement-title {
            font-size: 16px;
            font-weight: bold;
            color: #34495e;
        }
        .student-info {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #6c757d;
        }
        .balance-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .balance-row:last-child {
            margin-bottom: 0;
            font-weight: bold;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 10px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        .footer p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 12px;
        }
        .attachment-note {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school->name }}</div>
        <div class="statement-title">Student Statement</div>
        <div>{{ $academicYear }} {{ $term ? '- ' . $term : '' }}</div>
    </div>

    <div class="attachment-note">
        <strong>📎 Attachment:</strong> Please find the detailed student statement attached to this email as a PDF document.
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
            <span class="info-label">Period:</span>
            <span>{{ $academicYear }} {{ $term ? '- ' . $term : 'All Terms' }}</span>
        </div>
    </div>

    <div class="balance-summary">
        <div class="balance-row">
            <span>Opening Balance:</span>
            <span>{{ number_format($openingBalance, 2) }}</span>
        </div>
        <div class="balance-row">
            <span>Closing Balance:</span>
            <span>{{ number_format($closingBalance, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <p><strong>{{ $school->name }}</strong></p>
        <p>This is an automated statement. For any queries regarding this statement, please contact the school administration directly.</p>
        <p>Generated on: {{ now()->format('M j, Y H:i') }}</p>
    </div>
</body>
</html>
