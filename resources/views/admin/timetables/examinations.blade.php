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
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Timetable Examination Slots</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1 pl-8">Configure examination schedules (national, mock, internal). National exams are hard constraints and cannot be overwritten.</p>
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
        <!-- Add Examination Slot Form -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-100 mb-4">Add Examination Slot</h2>
            <form action="{{ route('admin.timetables.examinations.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Exam Title</label>
                    <input type="text" name="title" required placeholder="e.g. ZIMSEC / Cambridge O-Level Maths Paper 1" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Exam Type</label>
                        <select name="exam_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="internal" {{ old('exam_type') === 'internal' ? 'selected' : '' }}>Internal Exam</option>
                            <option value="mock" {{ old('exam_type') === 'mock' ? 'selected' : '' }}>Mock Exam</option>
                            <option value="national" {{ old('exam_type') === 'national' ? 'selected' : '' }}>National Exam (Hard Lock)</option>
                            <option value="other" {{ old('exam_type') === 'other' ? 'selected' : '' }}>Other</option>
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
                        <label class="block text-xs font-medium text-slate-300 mb-1">Subject</label>
                        <select name="subject_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Select Subject (Optional)</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ (string)old('subject_id') === (string)$sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Class / Form</label>
                        <select name="school_class_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">All Candidates / General</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ (string)old('school_class_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Supervisor / Invigilator</label>
                        <select name="supervisor_teacher_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Select Teacher (Optional)</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ (string)old('supervisor_teacher_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Exam Room / Hall</label>
                        <select name="room_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Select Room (Optional)</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ (string)old('room_id') === (string)$r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_locked" value="1" id="is_locked" checked class="rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-0">
                    <label for="is_locked" class="text-xs text-slate-300 font-medium">Hard Lock Against Normal Lessons</label>
                </div>

                <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 text-xs font-medium text-white hover:bg-blue-500 transition shadow-sm">Save Exam Slot</button>
            </form>
        </div>

        <!-- Exam Slots List -->
        <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900/70 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 bg-slate-800/40 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-100">Scheduled Exam Periods ({{ $examinations->count() }})</h2>
                <span class="text-xs text-slate-400">Hard constraint verification</span>
            </div>

            @if($examinations->isEmpty())
                <div class="p-10 text-center text-xs text-slate-400">
                    No examination periods scheduled. Use the form on the left to add mock, internal, or national examination slots.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 bg-slate-800/60 text-slate-300 font-semibold">
                                <th class="p-3">Exam Title</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Day & Time</th>
                                <th class="p-3">Subject / Class</th>
                                <th class="p-3">Supervisor</th>
                                <th class="p-3 text-center">Lock</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($examinations as $exam)
                                <tr class="hover:bg-slate-800/20 transition">
                                    <td class="p-3 font-medium text-slate-200">{{ $exam->title }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                            {{ $exam->isNational() ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' }}">
                                            {{ ucfirst($exam->exam_type) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-300 font-mono">{{ $exam->day_of_week }}, {{ $exam->getFormattedTime() }}</td>
                                    <td class="p-3 text-slate-400">
                                        <div>{{ $exam->subject?->name ?? 'General' }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $exam->schoolClass?->name ?? 'All Candidates' }}</div>
                                    </td>
                                    <td class="p-3 text-slate-400">{{ $exam->supervisorTeacher?->full_name ?? '—' }}</td>
                                    <td class="p-3 text-center">
                                        @if($exam->isHardLocked())
                                            <span class="inline-flex items-center gap-1 text-rose-400 text-xs font-semibold">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                Locked
                                            </span>
                                        @else
                                            <span class="text-slate-500 text-xs">Soft</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <form action="{{ route('admin.timetables.examinations.destroy', $exam) }}" method="POST" onsubmit="return confirm('Delete exam slot {{ $exam->title }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 transition" title="Delete Exam Slot">
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
