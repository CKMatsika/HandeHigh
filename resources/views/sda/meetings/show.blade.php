@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $meeting->title }}</h1>
            <p class="text-xs text-slate-400 mt-1">
                {{ optional($meeting->meeting_date)->format('M d, Y h:i A') }} • {{ optional($meeting->committee)->name }}
            </p>
        </div>
        <a href="{{ route('sda.meetings.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-900/50 border border-green-700 text-green-200 rounded-lg p-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Meeting details --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 text-xs">
            <div>
                <h3 class="text-slate-400 mb-1">Committee</h3>
                <p class="text-slate-200">{{ optional($meeting->committee)->name }}</p>
            </div>
            <div>
                <h3 class="text-slate-400 mb-1">Type</h3>
                <p class="text-slate-200">{{ ucfirst($meeting->meeting_type ?? 'regular') }}</p>
            </div>
            <div>
                <h3 class="text-slate-400 mb-1">Venue</h3>
                <p class="text-slate-200">{{ $meeting->venue ?? 'TBD' }}</p>
            </div>
            <div>
                <h3 class="text-slate-400 mb-1">Duration</h3>
                <p class="text-slate-200">{{ $meeting->duration_minutes ? $meeting->duration_minutes . ' minutes' : 'Not set' }}</p>
            </div>
        </div>

        @if($meeting->agenda)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-200 mb-2">Agenda</h3>
                <div class="bg-slate-800/50 rounded-lg p-4 text-sm text-slate-300 whitespace-pre-wrap">
                    {{ $meeting->agenda }}
                </div>
            </div>
        @endif

        @if($meeting->description)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-200 mb-2">Description / Notes</h3>
                <div class="bg-slate-800/50 rounded-lg p-4 text-sm text-slate-300 whitespace-pre-wrap">
                    {{ $meeting->description }}
                </div>
            </div>
        @endif

        @if(isset($userAttendance) && $userAttendance)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-200 mb-2">Your Attendance</h3>
                <div class="bg-slate-800/50 rounded-lg p-4 text-sm text-slate-300 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <div>
                        <span class="text-slate-200">Status:</span>
                        <span class="ml-1 px-2 py-1 rounded-full text-xs bg-slate-700 text-slate-200">
                            {{ ucfirst($userAttendance->attendance_status) }}
                        </span>
                    </div>
                    @if($userAttendance->arrival_time)
                        <div>
                            <span class="text-slate-200">Arrival Time:</span>
                            <span class="ml-1 text-slate-300">{{ $userAttendance->arrival_time }}</span>
                        </div>
                    @endif
                    @if($userAttendance->apology_reason)
                        <div class="text-slate-300">Reason: {{ $userAttendance->apology_reason }}</div>
                    @endif
                    @if($userAttendance->notes)
                        <div class="text-slate-300">Notes: {{ $userAttendance->notes }}</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Attendance list --}}
        <div class="mb-2">
            <h3 class="text-sm font-medium text-slate-200 mb-3">Committee Members & Attendance</h3>
            <div class="rounded-lg border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-950/60 text-slate-300">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Member</th>
                                <th class="px-4 py-2 text-left font-medium">Role</th>
                                <th class="px-4 py-2 text-left font-medium">Status</th>
                                <th class="px-4 py-2 text-left font-medium">Arrival</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($meeting->committee->activeMembers as $membership)
                                @php
                                    $attendance = $meeting->attendances->firstWhere('user_id', $membership->user_id);
                                @endphp
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-2 text-slate-200">
                                        {{ $membership->user->name ?? 'Member' }}
                                    </td>
                                    <td class="px-4 py-2 text-slate-300">
                                        {{ $membership->role->name ?? 'Member' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($attendance)
                                            <span class="px-2 py-1 rounded-full text-xs bg-slate-700 text-slate-200">
                                                {{ ucfirst($attendance->attendance_status) }}
                                            </span>
                                        @else
                                            <span class="text-slate-500">Not recorded</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-slate-300">
                                        {{ $attendance && $attendance->arrival_time ? $attendance->arrival_time : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
