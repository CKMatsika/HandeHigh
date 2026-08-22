<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #0f172a;
            padding: 24px;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 13px;
            color: #475569;
        }
        .timetable-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            background-color: #ffffff;
            font-size: 11px;
        }
        .timetable-table th, .timetable-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            vertical-align: top;
        }
        .timetable-table th {
            background-color: #f1f5f9;
            font-weight: 600;
            text-align: center;
        }
        .day-header {
            background-color: #e2e8f0;
            font-weight: 700;
            width: 90px;
            text-align: center;
        }
        .slot-box {
            padding: 4px;
            border-radius: 4px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .slot-title {
            font-weight: 600;
            font-size: 11px;
            color: #0f172a;
        }
        .slot-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .non-lesson {
            background-color: #f1f5f9;
            color: #64748b;
            font-style: italic;
            text-align: center;
            padding: 8px 4px;
        }
        .print-actions {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .print-actions {
                display: none;
            }
            .timetable-table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button class="btn" onclick="window.print()">Print Timetable</button>
    </div>

    <div class="header">
        <h1>{{ $timetable->school->name }}</h1>
        <p>{{ $title }} &bull; {{ $timetable->academic_year }} - {{ $timetable->term }} &bull; Status: {{ ucfirst($timetable->status) }}</p>
    </div>

    @if($type === 'class' && $data)
        <table class="timetable-table">
            <thead>
                <tr>
                    <th class="day-header">Day</th>
                    @foreach($data['periods'] as $period)
                        <th>
                            <div>{{ $period->name }}</div>
                            <div style="font-weight: normal; font-size: 9px; color: #64748b;">{{ $period->getFormattedTime() }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data['days'] as $day)
                    <tr>
                        <td class="day-header">{{ $day }}</td>
                        @foreach($data['periods'] as $period)
                            @php
                                $cell = $data['grid'][$day][$period->id] ?? null;
                                $slot = $cell['slot'] ?? null;
                            @endphp
                            <td>
                                @if($slot)
                                    <div class="slot-box">
                                        <div class="slot-title">{{ $slot->subject?->name }}</div>
                                        <div class="slot-sub">{{ $slot->teacher?->full_name }}</div>
                                        @if($slot->room)
                                            <div class="slot-sub">Room: {{ $slot->room->name }}</div>
                                        @endif
                                    </div>
                                @elseif(! $period->isLesson())
                                    <div class="non-lesson">{{ $period->name }}</div>
                                @else
                                    <div style="text-align: center; color: #cbd5e1; font-size: 9px; padding: 6px;">—</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($type === 'teacher' && $data)
        <table class="timetable-table">
            <thead>
                <tr>
                    <th class="day-header">Day</th>
                    @foreach($data['periods'] as $period)
                        <th>
                            <div>{{ $period->name }}</div>
                            <div style="font-weight: normal; font-size: 9px; color: #64748b;">{{ $period->getFormattedTime() }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data['days'] as $day)
                    <tr>
                        <td class="day-header">{{ $day }}</td>
                        @foreach($data['periods'] as $period)
                            @php
                                $cell = $data['grid'][$day][$period->id] ?? null;
                                $slot = $cell['slot'] ?? null;
                            @endphp
                            <td>
                                @if($slot)
                                    <div class="slot-box">
                                        <div class="slot-title">{{ $slot->schoolClass?->name }}</div>
                                        <div class="slot-sub">{{ $slot->subject?->name }}</div>
                                        @if($slot->room)
                                            <div class="slot-sub">Room: {{ $slot->room->name }}</div>
                                        @endif
                                    </div>
                                @elseif(! $period->isLesson())
                                    <div class="non-lesson">{{ $period->name }}</div>
                                @else
                                    <div style="text-align: center; color: #cbd5e1; font-size: 9px; padding: 6px;">—</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Master Timetable Grid -->
        @foreach($data['days'] as $day)
            <div style="font-size: 13px; font-weight: 700; margin-bottom: 6px; text-transform: uppercase; color: #1e293b;">
                {{ $day }}
            </div>
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th style="width: 110px;">Class</th>
                        @foreach($data['periods'] as $period)
                            <th>
                                <div>{{ $period->name }}</div>
                                <div style="font-weight: normal; font-size: 9px; color: #64748b;">{{ $period->getFormattedTime() }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['classes'] as $cls)
                        <tr>
                            <td style="font-weight: 600; background-color: #f8fafc;">{{ $cls->name }}</td>
                            @foreach($data['periods'] as $period)
                                @php
                                    $matchingSlot = $data['slots']->first(function($s) use ($day, $period, $cls) {
                                        return $s->school_class_id === $cls->id &&
                                               strcasecmp($s->day_of_week, $day) === 0 &&
                                               (($s->school_period_id && $s->school_period_id === $period->id) ||
                                                (substr($s->start_time, 0, 5) === substr($period->start_time, 0, 5)));
                                    });
                                @endphp
                                <td>
                                    @if($matchingSlot)
                                        <div class="slot-box">
                                            <div class="slot-title">{{ $matchingSlot->subject?->name }}</div>
                                            <div class="slot-sub">{{ $matchingSlot->teacher?->full_name }}</div>
                                            @if($matchingSlot->room)
                                                <div class="slot-sub">Room: {{ $matchingSlot->room->name }}</div>
                                            @endif
                                        </div>
                                    @elseif(! $period->isLesson())
                                        <div class="non-lesson">{{ $period->name }}</div>
                                    @else
                                        <div style="text-align: center; color: #cbd5e1; font-size: 9px; padding: 6px;">—</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</body>
</html>
