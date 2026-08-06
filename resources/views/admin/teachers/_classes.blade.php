<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="space-y-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-50">Class Teacher</h3>
                <button onclick="document.getElementById('assignClassForm').classList.toggle('hidden')" class="text-xs text-emerald-400 hover:text-emerald-300">Assign</button>
            </div>
            @php $ctClass = $classes->first(); @endphp
            @if($ctClass)
                <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-800/50">
                    <div>
                        <p class="text-sm text-slate-100">{{ $ctClass->name }}</p>
                        <p class="text-[10px] text-slate-400">Grade {{ $ctClass->grade }} · {{ $ctClass->enrollments->count() }} students</p>
                    </div>
                    <form method="POST" action="{{ route('admin.teachers.class-teacher.remove', [$teacher, $ctClass]) }}" onsubmit="return confirm('Remove as class teacher?')">
                        @csrf
                        <button type="submit" class="text-xs text-red-400 hover:text-red-300">Remove</button>
                    </form>
                </div>
            @else
                <p class="text-xs text-slate-400">Not assigned as class teacher.</p>
            @endif

            <form id="assignClassForm" method="POST" action="{{ route('admin.teachers.class-teacher.assign', $teacher) }}" class="hidden mt-4 p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                @csrf
                <select name="class_id" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" required>
                    <option value="">Select Class</option>
                    @foreach($allClasses as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }} (Grade {{ $cls->grade }})</option>
                    @endforeach
                </select>
                <button type="submit" class="mt-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded">Assign</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-50">Subject Teaching Assignments</h3>
            </div>
            @if($subjectCurricula->isNotEmpty())
                <div class="space-y-2">
                    @foreach($subjectCurricula as $curr)
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-800/50">
                            <div>
                                <p class="text-xs text-slate-100">{{ $curr->subject->name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-slate-400">{{ $curr->class->name ?? 'N/A' }} · {{ $curr->weekly_periods }} periods/week</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-slate-500">{{ $curr->term }} {{ $curr->academic_year }}</span>
                                <form method="POST" action="{{ route('admin.teachers.teaching-assignments.destroy', [$teacher, $curr]) }}" onsubmit="return confirm('Remove this teaching assignment?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-300">Remove</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No subject teaching assignments yet. Use the quick assign form to add one.</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-4">Quick Assign Teaching</h3>
        <p class="text-xs text-slate-400 mb-4">Assign this teacher to teach a subject in a specific class.</p>
        <form method="POST" action="{{ route('admin.teachers.teaching-assignments.store', $teacher) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Class *</label>
                <select name="class_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                    <option value="">Select Class</option>
                    @foreach($allClasses as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }} (Grade {{ $cls->grade }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Subject *</label>
                <select name="subject_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                    <option value="">Select Subject</option>
                    @foreach($allSubjects as $subj)
                        <option value="{{ $subj->id }}">{{ $subj->name }} ({{ $subj->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Weekly Periods</label>
                    <input type="number" name="weekly_periods" value="4" min="1" max="40" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Term</label>
                    <select name="term" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Academic Year</label>
                <input type="text" name="academic_year" value="{{ date('Y') }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium px-4 py-2.5 rounded-lg">Assign Teaching</button>
        </form>
    </div>
</div>
