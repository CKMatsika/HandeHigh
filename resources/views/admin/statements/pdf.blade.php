<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Statement - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        body { font-size: 11px; padding: 20px; background: white; color: #0f172a; }
        .header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 16px; }
        .header-left { display: flex; gap: 14px; align-items: flex-start; }
        .logo-box img { max-height: 56px; max-width: 56px; object-fit: contain; }
        .crest-box { width: 50px; height: 50px; border-radius: 8px; background: linear-gradient(135deg, #1e3a8a, #d97706); color: #fff; font-weight: bold; font-size: 16px; display: flex; align-items: center; justify-content: center; }
        .school-name { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .motto { font-size: 10px; font-style: italic; color: #475569; }
        .school-info { font-size: 10px; color: #475569; }
        .doc-title { font-size: 13px; font-weight: 800; text-transform: uppercase; color: #2563eb; }
        
        .student-box { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; margin-bottom: 14px; }
        .box-item { font-size: 10px; }
        .box-label { color: #64748b; text-transform: uppercase; font-size: 9px; }
        .box-val { font-weight: bold; font-size: 11px; margin-top: 2px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 9px; }
        .text-right { text-align: right; font-family: monospace; }
        
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #cbd5e1; font-size: 9px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    @php
        $branding = $school->branding_data;
    @endphp

    <div class="header">
        <div class="header-left">
            @if(!empty($branding['logo_base64']) || !empty($branding['logo_url']))
                <div class="logo-box">
                    <img src="{{ $branding['logo_base64'] ?: $branding['logo_url'] }}" alt="{{ $branding['name'] }}">
                </div>
            @else
                <div class="crest-box">
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
            </div>
        </div>
        <div style="text-align: right;">
            <div class="doc-title">Student Statement</div>
            <div style="font-size: 10px; color: #475569;">{{ $academicYear }} {{ $term ? '· ' . $term : '' }}</div>
            <div style="font-size: 9px; color: #64748b;">Generated: {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <div class="student-box">
        <div class="box-item">
            <div class="box-label">Student</div>
            <div class="box-val">{{ $student->first_name }} {{ $student->last_name }}</div>
        </div>
        <div class="box-item">
            <div class="box-label">Admission #</div>
            <div class="box-val">{{ $student->admission_number ?? 'N/A' }}</div>
        </div>
        <div class="box-item">
            <div class="box-label">Form / Class</div>
            <div class="box-val">{{ $student->grade }} {{ $student->class_name }}</div>
        </div>
        <div class="box-item">
            <div class="box-label">Closing Balance</div>
            <div class="box-val">${{ number_format($closingBalance, 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Date</th>
                <th style="width: 90px;">Ref</th>
                <th>Description</th>
                <th class="text-right" style="width: 70px;">Debit</th>
                <th class="text-right" style="width: 70px;">Credit</th>
                <th class="text-right" style="width: 80px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if($openingBalance != 0)
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="3">Opening Balance (Brought Forward)</td>
                    <td class="text-right">{{ number_format($openingBalance, 2) }}</td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($openingBalance, 2) }}</td>
                </tr>
            @endif
            @foreach($events as $event)
                <tr>
                    <td>{{ $event['date']->format('d/m/Y') }}</td>
                    <td style="font-family: monospace;">{{ $event['reference'] }}</td>
                    <td>{{ $event['description'] }}</td>
                    <td class="text-right">{{ $event['debit'] ? number_format($event['debit'], 2) : '' }}</td>
                    <td class="text-right">{{ $event['credit'] ? number_format($event['credit'], 2) : '' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($event['balance'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f8fafc;">
                <td colspan="5" class="text-right">Closing Balance:</td>
                <td class="text-right" style="font-size: 11px;">${{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        @if(!empty($branding['bank_name']))
            <div style="font-weight: bold; margin-bottom: 3px;">
                Bank: {{ $branding['bank_name'] }} | Acc: {{ $branding['bank_account_number'] }} ({{ $branding['bank_account_name'] }})
            </div>
        @endif
        <div>This is an official computer-generated document issued by {{ $branding['name'] }}.</div>
    </div>
</body>
</html>
