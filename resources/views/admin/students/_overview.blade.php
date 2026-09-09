<!-- Overview Tab -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Personal Info -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-300 mb-4">Personal Information</h3>
        <div class="space-y-3">
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Full Name</span><span class="text-slate-100 text-sm">{{ $student->full_name }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Gender</span><span class="text-slate-100 text-sm">{{ $student->gender ? ucfirst($student->gender) : '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Date of Birth</span><span class="text-slate-100 text-sm">{{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Admission #</span><span class="text-slate-100 text-sm">{{ $student->admission_number ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Registration #</span><span class="text-slate-100 text-sm">{{ $student->registration_number ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Boarding</span><span class="text-slate-100 text-sm">{{ $student->is_boarding ? 'Yes' : 'No' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Transport</span><span class="text-slate-100 text-sm">{{ $student->has_transport ? 'Yes' : 'No' }}</span></div>
            @if($student->exit_type)
            <div class="flex justify-between border-t border-slate-700 pt-3">
                <span class="text-slate-400 text-sm">Exit</span>
                <span class="text-{{ $student->exit_type === 'graduated' ? 'blue' : 'yellow' }}-400 text-sm">{{ ucfirst($student->exit_type) }} ({{ $student->exit_date?->format('d M Y') }})</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Academic -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-300 mb-4">Academic Details</h3>
        <div class="space-y-3">
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Grade</span><span class="text-slate-100 text-sm">{{ $student->grade ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Class</span><span class="text-slate-100 text-sm">{{ $student->class_name ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">House</span>
                @if($student->house)
                    <span class="text-sm" style="color: {{ $student->house->color }}">{{ $student->house->emoji }} {{ $student->house->name }}</span>
                @else
                    <span class="text-slate-500 text-sm">—</span>
                @endif
            </div>
            <div class="flex justify-between"><span class="text-slate-400 text-sm">Subjects</span><span class="text-slate-100 text-sm">{{ $student->subjects->count() }}</span></div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 space-y-3">
            <h4 class="text-xs text-slate-500 uppercase">Quick Actions</h4>

            <!-- Move Class -->
            <form method="POST" action="{{ route('admin.students.move-class', $student) }}" class="bg-slate-800/50 rounded-xl p-3 border border-slate-700/50">
                @csrf
                <p class="text-xs text-slate-400 mb-2">Move to another class</p>
                <div class="space-y-2">
                    <select name="grade" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-1.5 text-xs">
                        @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Form 1','Form 2','Form 3','Form 4','Form 5','Form 6'] as $g)
                            <option value="{{ $g }}" {{ $student->grade == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="class_name" value="{{ $student->class_name }}" placeholder="e.g. Form 3 B"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-1.5 text-xs" required>
                    <button type="submit" class="w-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-lg px-3 py-1.5 text-xs hover:bg-cyan-500/20 transition"
                        onclick="return confirm('Move {{ $student->full_name }} to this class?')">
                        Move Class
                    </button>
                </div>
            </form>

            <div class="grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route('admin.students.promote', $student) }}" class="inline">
                    @csrf
                    <button type="submit" class="w-full bg-green-500/10 text-green-400 border border-green-500/20 rounded-lg px-3 py-1.5 text-xs hover:bg-green-500/20 transition" onclick="return confirm('Promote {{ $student->full_name }}?')">
                        Promote
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.students.demote', $student) }}" class="inline">
                    @csrf
                    <button type="submit" class="w-full bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 rounded-lg px-3 py-1.5 text-xs hover:bg-yellow-500/20 transition" onclick="return confirm('Demote {{ $student->full_name }}?')">
                        Demote
                    </button>
                </form>
            </div>
            <a href="{{ route('admin.students.transfer-form', $student) }}" class="block text-center bg-orange-500/10 text-orange-400 border border-orange-500/20 rounded-lg px-3 py-1.5 text-xs hover:bg-orange-500/20 transition">
                Transfer Out
            </a>
            <a href="{{ route('admin.students.exit-form', $student) }}" class="block text-center bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg px-3 py-1.5 text-xs hover:bg-red-500/20 transition">
                Exit / Graduate
            </a>
        </div>
    </div>

    <!-- Quick Summary -->
    <div class="space-y-4">
        <!-- Guardians -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-300 mb-3">Guardians</h3>
            @forelse($student->guardians as $guardian)
                <div class="flex items-center gap-3 py-2">
                    <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 text-xs font-bold">{{ strtoupper(substr($guardian->name ?? $guardian->first_name ?? '?', 0, 1)) }}</div>
                    <div>
                        <p class="text-slate-200 text-sm">{{ $guardian->name ?? $guardian->first_name . ' ' . $guardian->last_name }}</p>
                        <p class="text-slate-500 text-xs">{{ $guardian->pivot->relationship ?? '' }} {{ $guardian->pivot->is_primary ? '(Primary)' : '' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-slate-500 text-sm">No guardians linked.</p>
            @endforelse
        </div>

        <!-- Finance & Debtor Overview -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-300">Finance & Account</h3>
                @php
                    $bal = $financeSummary['outstanding_balance'] ?? 0;
                    $balColor = $bal > 0 ? 'rose' : ($bal < 0 ? 'amber' : 'emerald');
                @endphp
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $balColor }}-500/20 text-{{ $balColor }}-400 font-semibold uppercase">
                    {{ $bal > 0 ? 'Balance Due' : ($bal < 0 ? 'Credit' : 'Cleared') }}
                </span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400 text-sm">Debtor Balance</span>
                    <span class="text-slate-100 text-sm font-mono font-bold">
                        ${{ number_format(abs($bal), 2) }}
                        @if($bal < 0) <span class="text-xs text-amber-400 font-sans">(CR)</span> @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400 text-sm">Total Invoiced</span>
                    <span class="text-slate-200 text-sm font-mono">${{ number_format($financeSummary['total_invoiced'] ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400 text-sm">Total Paid</span>
                    <span class="text-emerald-400 text-sm font-mono">${{ number_format($financeSummary['total_paid'] ?? 0, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('admin.students.show', ['student' => $student, 'tab' => 'finance']) }}" class="block text-center mt-3 text-xs text-indigo-400 hover:text-indigo-300 font-medium transition">
                View Full Finance History & Statement &rarr;
            </a>
        </div>

        <!-- Boarding -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-300 mb-3">Boarding</h3>
            @if($student->currentBedAssignment)
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="text-slate-400 text-sm">Dormitory</span><span class="text-slate-100 text-sm">{{ $student->currentBedAssignment->bed->dormitory->name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 text-sm">Bed</span><span class="text-slate-100 text-sm">{{ $student->currentBedAssignment->bed->bed_number ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 text-sm">Since</span><span class="text-slate-100 text-sm">{{ $student->currentBedAssignment->assigned_date }}</span></div>
                </div>
            @else
                <p class="text-slate-500 text-sm">{{ $student->is_boarding ? 'Boarding (no bed assigned)' : 'Day Scholar' }}</p>
            @endif
            <a href="{{ route('admin.students.manage-boarding', $student) }}" class="block text-center mt-3 text-xs text-indigo-400 hover:text-indigo-300 transition">Manage Boarding</a>
        </div>

        <!-- Positions -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-300 mb-3">Leadership</h3>
            @forelse($student->currentPositions as $pos)
                <div class="py-1">
                    <span class="text-xs px-2 py-1 rounded-full bg-purple-500/20 text-purple-400">{{ $pos->position_title }}</span>
                </div>
            @empty
                <p class="text-slate-500 text-sm">No current positions.</p>
            @endforelse
            <a href="{{ route('admin.students.manage-positions', $student) }}" class="block text-center mt-3 text-xs text-indigo-400 hover:text-indigo-300 transition">Manage Positions</a>
        </div>
    </div>
</div>
