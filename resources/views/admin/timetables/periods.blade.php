@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.timetables.index') }}" class="text-slate-400 hover:text-slate-200 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">School Period Configuration</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">Configure school days, periods sequence, start/end times, and non-lesson intervals.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($periods->isEmpty())
                <form action="{{ route('admin.timetables.periods.seed-defaults') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-700 transition">Initialize Default Periods</button>
                </form>
            @endif
            <a href="{{ route('admin.timetables.index') }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700 transition">Back to Timetables</a>
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('success'))
        <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm text-emerald-400">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-lg bg-rose-500/10 border border-rose-500/20 p-4 text-sm text-rose-400">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Period Form -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-100 mb-4">Define School Period</h2>
            <form action="{{ route('admin.timetables.periods.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Period Name</label>
                    <input type="text" name="name" required placeholder="e.g. Period 1, Morning Break" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Sequence</label>
                        <input type="number" name="period_sequence" min="1" max="50" required value="{{ old('period_sequence', ($periods->max('period_sequence') ?? 0) + 1) }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Day of Week</label>
                        <select name="day_of_week" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">All School Days</option>
                            <option value="Monday" {{ old('day_of_week') === 'Monday' ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ old('day_of_week') === 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ old('day_of_week') === 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ old('day_of_week') === 'Thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ old('day_of_week') === 'Friday' ? 'selected' : '' }}>Friday</option>
                            <option value="Saturday" {{ old('day_of_week') === 'Saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="Sunday" {{ old('day_of_week') === 'Sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Start Time (HH:MM)</label>
                        <input type="time" name="start_time" required value="{{ old('start_time') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">End Time (HH:MM)</label>
                        <input type="time" name="end_time" required value="{{ old('end_time') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Period Type</label>
                    <select name="period_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="lesson" {{ old('period_type') === 'lesson' ? 'selected' : '' }}>Academic Lesson</option>
                        <option value="break" {{ old('period_type') === 'break' ? 'selected' : '' }}>Break (Morning/Tea)</option>
                        <option value="lunch" {{ old('period_type') === 'lunch' ? 'selected' : '' }}>Lunch Break</option>
                        <option value="assembly" {{ old('period_type') === 'assembly' ? 'selected' : '' }}>School Assembly</option>
                        <option value="chapel" {{ old('period_type') === 'chapel' ? 'selected' : '' }}>Chapel Service</option>
                        <option value="sport" {{ old('period_type') === 'sport' ? 'selected' : '' }}>Sports / Athletics</option>
                        <option value="club" {{ old('period_type') === 'club' ? 'selected' : '' }}>Clubs / Societies</option>
                        <option value="other" {{ old('period_type') === 'other' ? 'selected' : '' }}>Other Activity</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" value="1" id="is_active" checked class="rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-0">
                    <label for="is_active" class="text-xs text-slate-300 font-medium">Active (Available for scheduling)</label>
                </div>

                <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 text-xs font-medium text-white hover:bg-blue-500 transition shadow-sm">Save Period</button>
            </form>
        </div>

        <!-- Periods List -->
        <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900/70 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 bg-slate-800/40 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-100">Configured Periods ({{ $periods->count() }})</h2>
                <span class="text-xs text-slate-400">Ordered by sequence</span>
            </div>

            @if($periods->isEmpty())
                <div class="p-10 text-center text-xs text-slate-400">
                    No periods defined yet. Use the form on the left or initialize standard defaults.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 bg-slate-800/60 text-slate-300 font-semibold">
                                <th class="p-3 text-center">Seq</th>
                                <th class="p-3">Period Name</th>
                                <th class="p-3">Time Interval</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Day</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($periods as $period)
                                <tr class="hover:bg-slate-800/20 transition">
                                    <td class="p-3 text-center font-mono font-bold text-slate-300">{{ $period->period_sequence }}</td>
                                    <td class="p-3 font-medium text-slate-200">{{ $period->name }}</td>
                                    <td class="p-3 text-slate-300 font-mono">{{ $period->getFormattedTime() }} <span class="text-slate-500 text-[10px]">({{ $period->getDurationMinutes() }}m)</span></td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                            {{ $period->period_type === 'lesson' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : '' }}
                                            {{ in_array($period->period_type, ['break', 'lunch']) ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '' }}
                                            {{ in_array($period->period_type, ['assembly', 'chapel', 'sport', 'club', 'other']) ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : '' }}">
                                            {{ ucfirst($period->period_type) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-400">{{ $period->day_of_week ?? 'All Days' }}</td>
                                    <td class="p-3 text-center">
                                        @if($period->is_active)
                                            <span class="text-emerald-400 text-xs font-semibold">Active</span>
                                        @else
                                            <span class="text-slate-500 text-xs font-semibold">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <form action="{{ route('admin.timetables.periods.destroy', $period) }}" method="POST" onsubmit="return confirm('Delete period {{ $period->name }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 transition" title="Delete Period">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
