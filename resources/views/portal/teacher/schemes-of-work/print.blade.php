<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoPSE Scheme of Work - {{ $scheme->title }}</title>
    <style>
        @page {
            size: landscape A4;
            margin: 10mm 10mm 10mm 10mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Times New Roman', Times, serif, Arial, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            font-size: 11px;
            line-height: 1.3;
        }
        .outer-frame {
            border: 3px double #000;
            padding: 14px;
            min-height: 96vh;
        }
        .header-block {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .country-title {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .ministry-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .sub-dept {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            background: #000;
            color: #fff;
            display: inline-block;
            padding: 2px 10px;
            margin-bottom: 6px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 4px;
            text-decoration: underline;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .meta-row {
            display: flex;
            align-items: baseline;
        }
        .meta-label {
            font-weight: bold;
            text-transform: uppercase;
            width: 140px;
            flex-shrink: 0;
        }
        .meta-value {
            border-bottom: 1px dotted #000;
            flex-grow: 1;
            padding-left: 4px;
        }
        .aims-box {
            border: 1px solid #000;
            padding: 8px 10px;
            margin-bottom: 12px;
            background-color: #f9f9f9;
        }
        .aims-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .aims-content {
            white-space: pre-line;
            font-size: 11px;
        }
        table.scheme-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        table.scheme-table th,
        table.scheme-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            vertical-align: top;
        }
        table.scheme-table th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.5px;
        }
        .signoff-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 10px;
        }
        .signoff-box {
            border: 1px dashed #666;
            padding: 8px;
            min-height: 60px;
        }
        .signoff-label {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .no-print-bar {
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: sans-serif;
            font-size: 12px;
        }
        .btn-print {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .outer-frame {
                border: 2px solid #000;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong>Official MoPSE Zimbabwe Document Format</strong> &bull; {{ $scheme->title }}
    </div>
    <div>
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>
</div>

<div class="outer-frame">
    <!-- Header Block -->
    <div class="header-block">
        <div class="country-title">ZIMBABWE</div>
        <div class="ministry-title">MINISTRY OF PRIMARY AND SECONDARY EDUCATION</div>
        <div class="sub-dept">CURRICULUM DEVELOPMENT AND TECHNICAL SERVICES</div>
        <div class="doc-title">SCHEME OF WORK / SCHEME-CUM PLAN</div>
    </div>

    <!-- Metadata Section -->
    <div class="meta-grid">
        <div class="meta-row">
            <span class="meta-label">School:</span>
            <span class="meta-value">{{ $scheme->school->name ?? 'Hande High School' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Academic Year / Term:</span>
            <span class="meta-value">{{ $scheme->academic_year }} &bull; {{ $scheme->term }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Learning Area / Subject:</span>
            <span class="meta-value">{{ $scheme->subject->name ?? '-' }} ({{ $scheme->subject->code ?? '-' }})</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Form / Level:</span>
            <span class="meta-value">{{ $scheme->schoolClass->name ?? '-' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Teacher / Facilitator:</span>
            <span class="meta-value">{{ $scheme->teacher->full_name ?? Auth::user()->name }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">General Topic / Theme:</span>
            <span class="meta-value">{{ $scheme->general_topic ?? $scheme->title }}</span>
        </div>
        @if($scheme->syllabus_reference)
            <div class="meta-row" style="grid-column: 1 / -1;">
                <span class="meta-label">Syllabus Reference:</span>
                <span class="meta-value">{{ $scheme->syllabus_reference }}</span>
            </div>
        @endif
        @if(!empty($scheme->cross_cutting_themes))
            <div class="meta-row" style="grid-column: 1 / -1;">
                <span class="meta-label">Cross-Cutting Themes:</span>
                <span class="meta-value">{{ implode(', ', $scheme->cross_cutting_themes) }}</span>
            </div>
        @endif
    </div>

    <!-- General Aims Block -->
    @if($scheme->aims)
        <div class="aims-box">
            <div class="aims-title">General Aims:</div>
            <div class="aims-content">{{ $scheme->aims }}</div>
        </div>
    @endif

    <!-- 8-Column Scheme-Cum Plan Table -->
    <table class="scheme-table">
        <thead>
            <tr>
                <th style="width: 8%;">WEEK ENDING</th>
                <th style="width: 14%;">CONTENT / TOPIC</th>
                <th style="width: 20%;">OBJECTIVES</th>
                <th style="width: 13%;">COMPETENCIES / SKILLS</th>
                <th style="width: 11%;">SOM / MEDIA</th>
                <th style="width: 11%;">FACILITY / EQUIPMENT</th>
                <th style="width: 13%;">METHODS / ACTIVITIES</th>
                <th style="width: 10%;">EVALUATION</th>
            </tr>
        </thead>
        <tbody>
            @forelse($scheme->items as $item)
                <tr>
                    <td style="text-align: center; font-weight: bold;">
                        Week {{ $item->week_number }}<br>
                        <span style="font-weight: normal; font-size: 9px;">{{ $item->week_ending ? $item->week_ending->format('d/m/Y') : ($item->day_of_week ?? '-') }}</span>
                    </td>
                    <td>
                        <strong>{{ $item->topic }}</strong>
                        @if($item->sub_topic)
                            <div style="font-size: 9.5px; font-style: italic; margin-top: 2px;">{{ $item->sub_topic }}</div>
                        @endif
                    </td>
                    <td style="white-space: pre-line;">{{ $item->objectives }}</td>
                    <td style="white-space: pre-line;">{{ $item->competencies_skills ?? '-' }}</td>
                    <td style="white-space: pre-line;">{{ $item->som_media ?? $item->resources ?? '-' }}</td>
                    <td style="white-space: pre-line;">{{ $item->facility_equipment ?? '-' }}</td>
                    <td style="white-space: pre-line;">{{ $item->methods_activities ?? $item->teaching_methods ?? '-' }}</td>
                    <td style="white-space: pre-line; font-style: italic;">{{ $item->evaluation ?? $item->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">No scheme items registered.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Official Supervision & Sign-off Section -->
    <div class="signoff-section">
        <div class="signoff-box">
            <div class="signoff-label">Teacher / Facilitator Signature</div>
            <div>Name: {{ $scheme->teacher->full_name ?? Auth::user()->name }}</div>
            <div style="margin-top: 15px;">Date: ________________________</div>
        </div>
        <div class="signoff-box">
            <div class="signoff-label">Head of Department (HOD)</div>
            <div>Signature: ______________________</div>
            <div style="margin-top: 15px;">Date & Comment: ______________</div>
        </div>
        <div class="signoff-box">
            <div class="signoff-label">Headmaster / Deputy Headmaster</div>
            <div>Status: <strong>{{ strtoupper($scheme->status) }}</strong></div>
            <div style="margin-top: 15px;">Official School Stamp: ________</div>
        </div>
    </div>
</div>

</body>
</html>
