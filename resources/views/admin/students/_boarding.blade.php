<!-- Boarding Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <h3 class="text-lg font-semibold text-slate-50 mb-6">Boarding Management</h3>

    @if($student->currentBedAssignment)
        <!-- Current Assignment -->
        <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 mb-6">
            <h4 class="text-sm font-medium text-slate-300 mb-3">Current Assignment</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-slate-500">Dormitory</p>
                    <p class="text-slate-100 text-sm font-medium">{{ $student->currentBedAssignment->bed->dormitory->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Bed Number</p>
                    <p class="text-slate-100 text-sm font-medium">{{ $student->currentBedAssignment->bed->bed_number ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Academic Year</p>
                    <p class="text-slate-100 text-sm font-medium">{{ $student->currentBedAssignment->academic_year }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Assigned Date</p>
                    <p class="text-slate-100 text-sm font-medium">{{ $student->currentBedAssignment->assigned_date }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.students.release-bed', $student) }}" class="mt-4">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg px-4 py-2 text-sm hover:bg-red-500/20 transition" onclick="return confirm('Release this bed assignment?')">
                    Release Bed
                </button>
            </form>
        </div>
    @endif

    <!-- Assign Bed -->
    <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
        <h4 class="text-sm font-medium text-slate-300 mb-3">{{ $student->currentBedAssignment ? 'Change Bed Assignment' : 'Assign Bed' }}</h4>
        <form method="POST" action="{{ route('admin.students.assign-bed', $student) }}" id="bedForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Dormitory</label>
                    <select id="dormitorySelect" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        <option value="">Select dormitory...</option>
                        @foreach($dormitories as $dorm)
                            <option value="{{ $dorm->id }}">{{ $dorm->name }} ({{ $dorm->gender ?? 'Mixed' }}) — {{ $dorm->available_count ?? 0 }} beds available</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Bed</label>
                    <select name="bed_id" id="bedSelect" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        <option value="">Select dormitory first...</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Academic Year</label>
                    <input type="text" name="academic_year" value="{{ $currentYear }}" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Term</label>
                    <select name="term" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        <option value="1" {{ $currentTerm == '1' ? 'selected' : '' }}>Term 1</option>
                        <option value="2" {{ $currentTerm == '2' ? 'selected' : '' }}>Term 2</option>
                        <option value="3" {{ $currentTerm == '3' ? 'selected' : '' }}>Term 3</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">Assign Bed</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bed History -->
    @if($student->bedAssignments->count() > 0)
    <div class="mt-6">
        <h4 class="text-sm font-medium text-slate-300 mb-3">Bed History</h4>
        <div class="space-y-2">
            @foreach($student->bedAssignments()->with('bed.dormitory')->orderByDesc('assigned_date')->get() as $assignment)
                <div class="flex items-center justify-between bg-slate-800/30 rounded-lg px-4 py-2 border border-slate-700/30">
                    <div class="text-sm">
                        <span class="text-slate-300">{{ $assignment->bed->dormitory->name ?? '—' }}</span>
                        <span class="text-slate-500"> | Bed {{ $assignment->bed->bed_number ?? '—' }}</span>
                        <span class="text-slate-500 text-xs"> | {{ $assignment->academic_year }} T{{ $assignment->term }}</span>
                    </div>
                    <div class="text-xs">
                        {{ $assignment->is_current ? '<span class="text-green-400">Current</span>' : '<span class="text-slate-500">' . $assignment->assigned_date . ' → ' . ($assignment->released_date ?? '—') . '</span>' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
document.getElementById('dormitorySelect')?.addEventListener('change', function() {
    const dormId = this.value;
    const bedSelect = document.getElementById('bedSelect');
    if (!dormId) { bedSelect.innerHTML = '<option value="">Select dormitory first...</option>'; return; }
    fetch('{{ route("admin.students.get-beds") }}?dormitory_id=' + dormId)
        .then(r => r.json())
        .then(beds => {
            bedSelect.innerHTML = beds.length
                ? beds.map(b => `<option value="${b.id}">Bed ${b.bed_number}${b.description ? ' - ' + b.description : ''}</option>`).join('')
                : '<option value="">No available beds</option>';
        });
});
</script>
