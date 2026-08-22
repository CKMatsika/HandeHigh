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
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Fixed Timetable Activities</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">Configure recurring fixed events (assembly, chapel, sports, staff meetings) that lock periods against lesson scheduling.</p>
        </div>
        <div class="flex items-center gap-2">
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
        <!-- Add Activity Form -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-100 mb-4">Add Fixed Event</h2>
            <form action="{{ route('admin.timetables.fixed-activities.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Event Name</label>
                    <input type="text" name="name" required placeholder="e.g. Monday Morning Assembly, Friday Sports" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Activity Type</label>
                        <select name="activity_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="assembly" {{ old('activity_type') === 'assembly' ? 'selected' : '' }}>Assembly</option>
                            <option value="chapel" {{ old('activity_type') === 'chapel' ? 'selected' : '' }}>Chapel</option>
                            <option value="sport" {{ old('activity_type') === 'sport' ? 'selected' : '' }}>Sports</option>
                            <option value="club" {{ old('activity_type') === 'club' ? 'selected' : '' }}>Club</option>
                            <option value="staff_meeting" {{ old('activity_type') === 'staff_meeting' ? 'selected' : '' }}>Staff Meeting</option>
                            <option value="other" {{ old('activity_type') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Day of Week</label>
                        <select name="day_of_week" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
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
                        <label class="block text-xs font-medium text-slate-300 mb-1">Start Time</label>
                        <input type="time" name="start_time" required value="{{ old('start_time') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">End Time</label>
                        <input type="time" name="end_time" required value="{{ old('end_time') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Target Class</label>
                        <select name="school_class_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Whole School (All Classes)</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ (string)old('school_class_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Teacher / Staff</label>
                        <select name="teacher_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">All Teachers / General</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ (string)old('teacher_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Specific Timetable Scope</label>
                    <select name="timetable_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Global (All School Timetables)</option>
                        @foreach($timetables as $tb)
                            <option value="{{ $tb->id }}" {{ (string)old('timetable_id') === (string)$tb->id ? 'selected' : '' }}>{{ $tb->name }} ({{ $tb->academic_year }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_locked" value="1" id="is_locked" checked class="rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-0">
                    <label for="is_locked" class="text-xs text-slate-300 font-medium">Hard Lock (Blocks ordinary lesson scheduling)</label>
                </div>

                <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 text-xs font-medium text-white hover:bg-blue-500 transition shadow-sm">Save Fixed Event</button>
            </form>
        </div>

        <!-- Fixed Activities List -->
        <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900/70 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 bg-slate-800/40 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-100">Configured Fixed Events ({{ $activities->count() }})</h2>
                <span class="text-xs text-slate-400">Hard constraint locked</span>
            </div>

            @if($activities->isEmpty())
                <div class="p-10 text-center text-xs text-slate-400">
                    No fixed events defined yet. Use the form on the left to add school assemblies, chapel, or sports periods.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 bg-slate-800/60 text-slate-300 font-semibold">
                                <th class="p-3">Event Name</th>
                                <th class="p-3">Day & Time</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Scope</th>
                                <th class="p-3 text-center">Lock</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($activities as $activity)
                                <tr class="hover:bg-slate-800/20 transition">
                                    <td class="p-3 font-medium text-slate-200">{{ $activity->name }}</td>
                                    <td class="p-3 text-slate-300 font-mono">{{ $activity->day_of_week }}, {{ $activity->getFormattedTime() }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                            {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-400">
                                        @if($activity->school_class_id)
                                            Class: {{ $activity->schoolClass?->name }}
                                        @elseif($activity->teacher_id)
                                            Teacher: {{ $activity->teacher?->full_name }}
                                        @else
                                            Whole School
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($activity->is_locked)
                                            <span class="inline-flex items-center gap-1 text-rose-400 text-xs font-semibold">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                Locked
                                            </span>
                                        @else
                                            <span class="text-slate-500 text-xs">Soft</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <form action="{{ route('admin.timetables.fixed-activities.destroy', $activity) }}" method="POST" onsubmit="return confirm('Delete fixed event {{ $activity->name }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 transition" title="Delete Event">
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
