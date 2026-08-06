<!-- Subjects Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-slate-50">Subjects — {{ $currentYear }} Term {{ $currentTerm }}</h3>
    </div>

    <!-- Add Subject -->
    <form method="POST" action="{{ route('admin.students.add-subject', $student) }}" class="mb-6">
        @csrf
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label class="text-xs text-slate-400 mb-1 block">Add Subject</label>
                <select name="subject_id" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="">Select a subject...</option>
                    @foreach($allSubjects as $subject)
                        @unless($student->subjects->contains($subject->id))
                            <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }} {{ $subject->is_core ? '(Core)' : '' }}</option>
                        @endunless
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="academic_year" value="{{ $currentYear }}">
            <input type="hidden" name="term" value="{{ $currentTerm }}">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition">Add</button>
        </div>
    </form>

    <!-- Current Subjects -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse($student->subjects as $subject)
            <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/50">
                <div>
                    <p class="text-slate-100 text-sm font-medium">{{ $subject->name }}</p>
                    <p class="text-slate-500 text-xs">{{ $subject->code }} {{ $subject->is_core ? '| Core' : '| Elective' }}</p>
                </div>
                <form method="POST" action="{{ route('admin.students.remove-subject', ['student' => $student, 'subject' => $subject]) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition" onclick="return confirm('Remove {{ $subject->name }}?')">Remove</button>
                </form>
            </div>
        @empty
            <div class="col-span-full text-center py-8 text-slate-500 text-sm">No subjects assigned yet. Add subjects above.</div>
        @endforelse
    </div>
</div>
