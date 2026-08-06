<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-xs text-slate-400">Full Name</p><p class="text-sm text-slate-100">{{ $teacher->full_name }}</p></div>
                <div><p class="text-xs text-slate-400">Email</p><p class="text-sm text-slate-100">{{ $teacher->email }}</p></div>
                <div><p class="text-xs text-slate-400">Phone</p><p class="text-sm text-slate-100">{{ $teacher->phone ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Gender</p><p class="text-sm text-slate-100">{{ ucfirst($teacher->gender ?? '—') }}</p></div>
                <div><p class="text-xs text-slate-400">Date of Birth</p><p class="text-sm text-slate-100">{{ $teacher->date_of_birth?->format('d M Y') ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Address</p><p class="text-sm text-slate-100">{{ $teacher->address ?? '—' }}</p></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Professional Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-xs text-slate-400">Employee ID</p><p class="text-sm text-slate-100">{{ $teacher->employee_id }}</p></div>
                <div><p class="text-xs text-slate-400">Specialization</p><p class="text-sm text-slate-100">{{ $teacher->specialization ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Highest Qualification</p><p class="text-sm text-slate-100">{{ $teacher->qualification ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Experience</p><p class="text-sm text-slate-100">{{ $teacher->experience_years }} years</p></div>
                <div><p class="text-xs text-slate-400">Hire Date</p><p class="text-sm text-slate-100">{{ $teacher->hire_date?->format('d M Y') ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Salary</p><p class="text-sm text-slate-100">{{ $teacher->salary ? '$'.number_format($teacher->salary, 2) : '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Status</p>
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $teacher->status ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                        {{ $teacher->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Current Positions</h3>
            @php $positions = $teacher->activePositions; @endphp
            @if($positions->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($positions as $pos)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-indigo-500/20 text-indigo-400">{{ $pos }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No positions assigned.</p>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Class Teacher</h3>
            @php $ctClass = $classes->first(); @endphp
            @if($ctClass)
                <p class="text-lg font-semibold text-emerald-400">{{ $ctClass->name }}</p>
                <p class="text-xs text-slate-400">{{ $ctClass->enrollments->count() }} students</p>
            @else
                <p class="text-xs text-slate-400">Not assigned as class teacher.</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Subjects Taught</h3>
            @if($teacher->subjects->isNotEmpty())
                <div class="space-y-2">
                    @foreach($teacher->subjects as $subj)
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-100">{{ $subj->name }}</span>
                            <span class="text-[10px] text-slate-400">{{ $subj->code }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No subjects assigned.</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Class Assignments</h3>
            @if($subjectCurricula->isNotEmpty())
                @php $uniqueClasses = $subjectCurricula->pluck('class')->unique('id')->filter(); @endphp
                @foreach($uniqueClasses as $cls)
                    <div class="flex items-center justify-between py-1">
                        <span class="text-xs text-slate-100">{{ $cls->name }}</span>
                        <span class="text-[10px] text-slate-400">{{ $subjectCurricula->where('class_id', $cls->id)->count() }} subjects</span>
                    </div>
                @endforeach
            @else
                <p class="text-xs text-slate-400">No class assignments.</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-xs font-medium text-slate-400 mb-2">User Account</h3>
            @if($teacher->user)
                <p class="text-xs text-emerald-400">{{ $teacher->user->email }}</p>
            @else
                <p class="text-xs text-slate-400">No user account linked.</p>
            @endif
        </div>

        @if($teacher->notes)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-xs font-medium text-slate-400 mb-2">Notes</h3>
                <p class="text-xs text-slate-100">{{ $teacher->notes }}</p>
            </div>
        @endif
    </div>
</div>
