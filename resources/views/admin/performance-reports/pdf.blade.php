<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>End-of-Term Student Performance Report - {{ $report->student->admission_number }}</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
            size: a4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }

        .school-title {
            font-size: 16pt;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .school-motto {
            font-size: 8.5pt;
            font-style: italic;
            color: #64748b;
            margin-top: 2px;
        }

        .school-contact {
            font-size: 8pt;
            color: #475569;
            margin-top: 4px;
        }

        .report-title-bar {
            background-color: #0f172a;
            color: #ffffff;
            text-align: center;
            padding: 6px;
            font-size: 11pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
        }

        .meta-table td {
            padding: 5px 8px;
            font-size: 8.5pt;
        }

        .meta-label {
            font-weight: 700;
            color: #475569;
            width: 18%;
        }

        .meta-val {
            font-weight: 800;
            color: #0f172a;
            width: 32%;
        }

        .perf-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .perf-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #1e293b;
        }

        .perf-table td {
            padding: 5px 8px;
            font-size: 8.5pt;
            border: 1px solid #cbd5e1;
        }

        .perf-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }

        .pass-text { color: #059669; font-weight: 800; }
        .fail-text { color: #dc2626; font-weight: 800; }

        .summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
        }

        .summary-box td {
            padding: 6px 10px;
            font-size: 9pt;
            text-align: center;
        }

        .comments-section {
            width: 100%;
            margin-bottom: 10px;
        }

        .comment-block {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            padding: 6px 10px;
            margin-bottom: 8px;
        }

        .comment-header {
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }

        .comment-body {
            font-size: 8.5pt;
            color: #334155;
            font-style: italic;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .signatures-table td {
            width: 33.33%;
            vertical-align: top;
            padding: 6px;
        }

        .signature-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 8px;
            text-align: center;
            min-height: 80px;
        }

        .stamp-badge {
            display: inline-block;
            border: 2px dashed #7c3aed;
            color: #7c3aed;
            padding: 6px 10px;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            border-radius: 4px;
        }

        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <!-- Header with School Identity & Branding -->
    <table class="header-table">
        <tr>
            <td style="width: 80px; vertical-align: middle;">
                @if(!empty($branding['logo_base64']))
                    <img src="{{ $branding['logo_base64'] }}" class="header-logo" alt="Logo">
                @else
                    <div style="width: 70px; height: 70px; background: #0f172a; color: #fff; text-align: center; line-height: 70px; font-weight: 900; font-size: 18pt;">SE</div>
                @endif
            </td>
            <td style="vertical-align: middle; padding-left: 12px;">
                <h1 class="school-title">{{ $school->name }}</h1>
                @if($school->motto)
                    <div class="school-motto">"{{ $school->motto }}"</div>
                @endif
                <div class="school-contact">
                    {{ $school->address ?: 'Zimbabwe' }} 
                    @if($school->phone) • Tel: {{ $school->phone }} @endif
                    @if($school->email) • Email: {{ $school->email }} @endif
                    @if($school->zimsec_center_number) • ZIMSEC Centre: {{ $school->zimsec_center_number }} @endif
                </div>
            </td>
            <td style="text-align: right; vertical-align: top; width: 140px;">
                <div style="font-size: 8pt; font-weight: 700; color: #64748b;">REPORT REF</div>
                <div style="font-size: 9pt; font-weight: 900; color: #0f172a; font-family: monospace;">REP-{{ $report->id }}-{{ $report->academic_year }}</div>
                <div style="font-size: 7.5pt; color: #64748b; margin-top: 2px;">Version {{ $report->version }}</div>
            </td>
        </tr>
    </table>

    <!-- Title Bar -->
    <div class="report-title-bar">
        End-of-Term Student Performance Report
    </div>

    <!-- Student Metadata Table -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Student Name:</td>
            <td class="meta-val">{{ $report->student->full_name }}</td>
            <td class="meta-label">Admission No:</td>
            <td class="meta-val">{{ $report->student->admission_number }}</td>
        </tr>
        <tr>
            <td class="meta-label">Class / Stream:</td>
            <td class="meta-val">{{ $report->schoolClass->name ?? $report->student->class_name ?? '—' }}</td>
            <td class="meta-label">Academic Period:</td>
            <td class="meta-val">{{ $report->term }} — {{ $report->academic_year }}</td>
        </tr>
        <tr>
            <td class="meta-label">Academic Form:</td>
            <td class="meta-val">{{ $report->schoolClass->grade ?? $report->student->grade ?? '—' }}</td>
            <td class="meta-label">Grading Scheme:</td>
            <td class="meta-val">{{ $report->gradeScheme->name ?? 'ZIMSEC O-Level Standard' }}</td>
        </tr>
    </table>

    <!-- Subject Performance Table -->
    <table class="perf-table">
        <thead>
            <tr>
                <th style="width: 28%; text-align: left;">Subject</th>
                <th style="width: 10%;">Mark</th>
                <th style="width: 10%;">Max</th>
                <th style="width: 10%;">%</th>
                <th style="width: 10%;">Grade</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 20%; text-align: left;">Teacher</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subj)
                <tr>
                    <td class="font-bold">{{ $subj->subject->name }}</td>
                    <td class="text-center font-bold">{{ $subj->mark_obtained !== null ? $subj->mark_obtained : '—' }}</td>
                    <td class="text-center">{{ $subj->max_mark ?? 100 }}</td>
                    <td class="text-center font-bold">{{ $subj->percentage !== null ? number_format($subj->percentage, 1) . '%' : '—' }}</td>
                    <td class="text-center font-black {{ $subj->is_pass === false ? 'fail-text' : ($subj->is_pass ? 'pass-text' : '') }}">{{ $subj->grade ?? '—' }}</td>
                    <td class="text-center {{ $subj->is_pass === false ? 'fail-text' : ($subj->is_pass ? 'pass-text' : '') }}">
                        {{ $subj->is_pass === false ? 'FAIL' : ($subj->is_pass ? 'PASS' : '—') }}
                    </td>
                    <td>{{ $subj->effective_teacher_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Term Aggregates Summary Box -->
    <table class="summary-box">
        <tr>
            <td>
                <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Term Average</div>
                <div style="font-size: 13pt; font-weight: 900; color: #0f172a;">{{ $report->term_average !== null ? number_format($report->term_average, 1) . '%' : '—' }}</div>
            </td>
            <td>
                <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Overall Grade</div>
                <div style="font-size: 13pt; font-weight: 900; color: #0f172a;">{{ $report->overall_grade ?? '—' }}</div>
            </td>
            <td>
                <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Subjects Passed</div>
                <div style="font-size: 13pt; font-weight: 900; color: #059669;">{{ $report->subjects_passed }} / {{ $report->total_subjects }}</div>
            </td>
            <td>
                <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Overall Standing</div>
                <div style="font-size: 10pt; font-weight: 900; color: #4338ca;">{{ $report->overall_status ?? 'Pass' }}</div>
            </td>
        </tr>
    </table>

    <!-- Subject Teacher Comments -->
    <div class="comments-section">
        <div style="font-size: 9pt; font-weight: 800; text-transform: uppercase; color: #0f172a; margin-bottom: 4px;">Subject Teacher Comments</div>
        @foreach($subjects as $subj)
            @if($subj->comment)
                <div class="comment-block">
                    <div class="comment-header">{{ $subj->subject->name }} — Teacher: {{ $subj->effective_teacher_name }}</div>
                    <div class="comment-body" style="font-family: {{ $subj->comment_font ?? 'Helvetica' }}; font-size: {{ $subj->comment_font_size ? min(11, $subj->comment_font_size) : 8.5 }}pt;">
                        "{{ $subj->comment }}"
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- School Leadership Comments -->
    <div class="comments-section">
        <div class="comment-block">
            <div class="comment-header">Headmaster's Comment</div>
            <div class="comment-body">
                "{{ $report->headmaster_comment ?: 'Satisfactory term progress. Recommended to maintain disciplined effort across all subjects.' }}"
            </div>
        </div>

        @if($report->deputy_comment)
            <div class="comment-block">
                <div class="comment-header">Deputy Headmaster's Comment</div>
                <div class="comment-body">
                    "{{ $report->deputy_comment }}"
                </div>
            </div>
        @endif
    </div>

    <!-- Signatures and Official Digital Stamp Table -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-box">
                    <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Headmaster Signature</div>
                    @if($report->isHeadmasterSigned())
                        <div style="font-size: 9pt; font-weight: 900; color: #059669; margin-top: 10px;">✓ DIGITALLY SIGNED</div>
                        <div style="font-size: 7.5pt; color: #334155; font-weight: 700;">{{ $report->headmasterUser->name ?? 'Headmaster' }}</div>
                        <div style="font-size: 7pt; color: #64748b;">{{ $report->headmaster_signed_at->format('d M Y') }}</div>
                    @else
                        <div style="height: 25px;"></div>
                        <div style="border-bottom: 1px dashed #94a3b8; width: 80%; margin: 0 auto;"></div>
                        <div style="font-size: 7.5pt; color: #64748b; margin-top: 4px;">Pending Signature</div>
                    @endif
                </div>
            </td>

            <td>
                <div class="signature-box">
                    <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Deputy Headmaster Signature</div>
                    @if($report->isDeputySigned())
                        <div style="font-size: 9pt; font-weight: 900; color: #059669; margin-top: 10px;">✓ DIGITALLY SIGNED</div>
                        <div style="font-size: 7.5pt; color: #334155; font-weight: 700;">{{ $report->deputyUser->name ?? 'Deputy Headmaster' }}</div>
                        <div style="font-size: 7pt; color: #64748b;">{{ $report->deputy_signed_at->format('d M Y') }}</div>
                    @else
                        <div style="height: 25px;"></div>
                        <div style="border-bottom: 1px dashed #94a3b8; width: 80%; margin: 0 auto;"></div>
                        <div style="font-size: 7.5pt; color: #64748b; margin-top: 4px;">Pending Signature</div>
                    @endif
                </div>
            </td>

            <td>
                <div class="signature-box">
                    <div style="font-size: 7.5pt; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px;">Official School Stamp</div>
                    @if($report->isStampApplied())
                        <div class="stamp-badge">
                            OFFICIAL STAMP<br>
                            {{ $school->name }}<br>
                            {{ $report->stamp_applied_at->format('d M Y') }}
                        </div>
                    @else
                        <div style="font-size: 7.5pt; color: #94a3b8; margin-top: 15px;">[ Official Digital Stamp ]</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        {{ $school->name }} • End-of-Term Performance Report • Generated on {{ $generatedAt->format('d M Y H:i') }} • Status: {{ $report->status }}
    </div>
</body>
</html>
