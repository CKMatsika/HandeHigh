<div class="space-y-6">
    {{-- Top Alert / Summary --}}
    @if(session('success'))
        <div class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-300 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-300 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Left Column: Managed Class & Teaching Classes List (7 Cols) --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- 1. Administrative Class Teacher Card --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-50 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Class Teacher (Administrative Management)
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Manages attendance registers, daily roll call, and general class supervision.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('assignClassForm').classList.toggle('hidden')" class="px-2.5 py-1 text-xs font-medium rounded-lg border border-emerald-500/40 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20 transition">
                        + Assign Class
                    </button>
                </div>

                {{-- Quick Assign Class Teacher Form --}}
                <form id="assignClassForm" method="POST" action="{{ route('admin.teachers.class-teacher.assign', $teacher) }}" class="hidden mb-4 p-4 rounded-xl border border-slate-700 bg-slate-800/80 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Select Class to Manage Administratively *</label>
                        <select name="class_id" class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500" required>
                            <option value="">-- Choose Class --</option>
                            @foreach($allClasses as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }} (Grade {{ $cls->grade }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 justify-end">
                        <button type="button" onclick="document.getElementById('assignClassForm').classList.add('hidden')" class="px-3 py-1.5 text-xs text-slate-400 hover:text-slate-200">Cancel</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium px-3.5 py-1.5 rounded-lg transition">Save Class Teacher</button>
                    </div>
                </form>

                @if($classes->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach($classes as $ctClass)
                            <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-700/60 bg-slate-800/60 hover:bg-slate-800 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($ctClass->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-100">{{ $ctClass->name }}</p>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-medium border border-emerald-500/30">
                                                Class Teacher
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            Grade {{ $ctClass->grade }} · {{ $ctClass->enrollments->count() }} Enrolled Students
                                            @if($ctClass->academic_year) · Year {{ $ctClass->academic_year }} @endif
                                        </p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.teachers.class-teacher.remove', [$teacher, $ctClass]) }}" onsubmit="return confirm('Remove {{ $teacher->full_name }} as class teacher of {{ $ctClass->name }}?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-lg transition border border-rose-500/20">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-dashed border-slate-800 bg-slate-900/50 text-center">
                        <p class="text-xs text-slate-400">Not assigned as administrative class teacher for any class.</p>
                        <p class="text-[11px] text-slate-500 mt-1">Use the "+ Assign Class" button above if this teacher manages a specific register class.</p>
                    </div>
                @endif
            </div>

            {{-- 2. Subject Teaching Assignments Card --}}
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-50 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                            Classes & Subjects Taught (Subject Specialisations)
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">All classes where this teacher instructs specific subjects (e.g. Practicals to Form 1s and 2s).</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 font-medium">
                            {{ $subjectCurricula->count() }} Subject Allocations
                        </span>
                        <span class="text-[11px] px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700 font-medium">
                            {{ $subjectCurricula->pluck('class_id')->unique()->count() }} Classes Taught
                        </span>
                    </div>
                </div>

                @if($subjectCurricula->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach($subjectCurricula as $curr)
                            <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-800 bg-slate-800/40 hover:bg-slate-800/70 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($curr->subject->code ?? $curr->subject->name ?? 'SUB', 0, 3)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-xs font-semibold text-slate-100">{{ $curr->subject->name ?? 'N/A' }}</p>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">
                                                {{ $curr->class->name ?? 'Class #' . $curr->class_id }}
                                            </span>
                                            @if($curr->class && $curr->class->grade)
                                                <span class="text-[10px] text-slate-400">Grade {{ $curr->class->grade }}</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-1">
                                            {{ $curr->weekly_periods }} periods/wk · 
                                            {{ $curr->class->enrollments->count() ?? 0 }} students · 
                                            <span class="text-slate-500">{{ $curr->term ?? 'All Terms' }} ({{ $curr->academic_year }})</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.teachers.teaching-assignments.destroy', [$teacher, $curr]) }}" onsubmit="return confirm('Remove teaching assignment for {{ $curr->subject->name ?? 'Subject' }} in {{ $curr->class->name ?? 'Class' }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded transition border border-rose-500/20">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 rounded-xl border border-dashed border-slate-800 bg-slate-900/50 text-center">
                        <svg class="w-8 h-8 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="text-xs text-slate-300 font-medium">No teaching classes assigned yet</p>
                        <p class="text-[11px] text-slate-400 mt-1">Use the "Allocate Subject & Classes" form on the right to assign this teacher to classes they teach.</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- Right Column: Multi-Class Teaching Allocator (5 Cols) --}}
        <div class="lg:col-span-5">
            <div class="rounded-xl border border-slate-800 bg-slate-900/90 p-6 sticky top-6 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-50 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Allocate Subject & Classes
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Assign this teacher to teach a subject across one or multiple classes (e.g. Practicals to all Form 1s and Form 2s).
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.teachers.teaching-assignments.store', $teacher) }}" class="space-y-4">
                    @csrf

                    {{-- Subject Selection --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-200 mb-1">Subject Specialisation *</label>
                        <select name="subject_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-emerald-500" required>
                            <option value="">-- Choose Subject --</option>
                            @foreach($allSubjects as $subj)
                                <option value="{{ $subj->id }}">{{ $subj->name }} ({{ $subj->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Classes Selection (Multi-select grouped by Grade) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-200">Classes to Teach *</label>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="toggleAllClasses(true)" class="text-[10px] text-emerald-400 hover:underline">Select All</button>
                                <span class="text-slate-600 text-xs">·</span>
                                <button type="button" onclick="toggleAllClasses(false)" class="text-[10px] text-slate-400 hover:underline">Clear</button>
                            </div>
                        </div>

                        @php
                            $classesByGrade = $allClasses->groupBy('grade');
                        @endphp

                        <div class="space-y-3 max-h-72 overflow-y-auto pr-1 rounded-xl border border-slate-800 bg-slate-950/40 p-3">
                            @forelse($classesByGrade as $grade => $gradeClasses)
                                <div class="p-2.5 rounded-lg bg-slate-900/90 border border-slate-800">
                                    <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-800/80">
                                        <span class="text-[11px] font-semibold text-emerald-400">
                                            Form / Grade {{ $grade }} ({{ $gradeClasses->count() }} classes)
                                        </span>
                                        <button type="button" onclick="toggleGradeClasses('{{ $grade }}')" class="text-[10px] text-slate-400 hover:text-emerald-300">
                                            Select all Form {{ $grade }}
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($gradeClasses as $cls)
                                            <label class="flex items-center gap-2 p-1.5 rounded-md bg-slate-800/60 hover:bg-slate-800 cursor-pointer border border-slate-700/40 text-xs transition">
                                                <input type="checkbox" name="class_ids[]" value="{{ $cls->id }}" class="class-checkbox grade-{{ $grade }} rounded border-slate-600 text-emerald-500 focus:ring-emerald-400 h-3.5 w-3.5">
                                                <span class="text-slate-200 text-[11px] font-medium">{{ $cls->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 text-center py-3">No classes created yet in this school.</p>
                            @endforelse
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Check all classes this teacher will teach for the selected subject.</p>
                    </div>

                    {{-- Weekly Periods & Term --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-200 mb-1">Weekly Periods</label>
                            <input type="number" name="weekly_periods" value="4" min="1" max="40" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-200 mb-1">Term</label>
                            <select name="term" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                                <option value="Term 1">Term 1</option>
                                <option value="Term 2">Term 2</option>
                                <option value="Term 3">Term 3</option>
                                <option value="All Terms">All Terms</option>
                            </select>
                        </div>
                    </div>

                    {{-- Academic Year --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-200 mb-1">Academic Year</label>
                        <input type="text" name="academic_year" value="{{ date('Y') }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium px-4 py-2.5 rounded-lg transition shadow flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Allocate Teaching to Selected Classes</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAllClasses(checked) {
        document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = checked);
    }

    function toggleGradeClasses(grade) {
        const boxes = document.querySelectorAll('.grade-' + grade);
        if (!boxes.length) return;
        const allChecked = Array.from(boxes).every(b => b.checked);
        boxes.forEach(b => b.checked = !allChecked);
    }
</script>
