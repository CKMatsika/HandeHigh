<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-4">Assigned Subjects</h3>
        @if($teacher->subjects->isNotEmpty())
            <div class="space-y-2">
                @foreach($teacher->subjects as $subj)
                    <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-800/50">
                        <div>
                            <p class="text-sm text-slate-100">{{ $subj->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $subj->code }} · {{ $subj->is_core ? 'Core' : 'Elective' }}</p>
                        </div>
                        <span class="text-[10px] text-slate-500">{{ $subj->description ? \Illuminate\Support\Str::limit($subj->description, 30) : '' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-400">No subjects assigned.</p>
        @endif
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-4">Manage Subject Assignments</h3>
        <form method="POST" action="{{ route('admin.teachers.subjects.assign', $teacher) }}">
            @csrf
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @foreach($allSubjects as $subj)
                    <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-slate-800/50 cursor-pointer">
                        <input type="checkbox" name="subjects[]" value="{{ $subj->id }}"
                               {{ $teacher->subjects->contains($subj->id) ? 'checked' : '' }}
                               class="rounded bg-slate-700 border-slate-600 text-emerald-600">
                        <div>
                            <p class="text-xs text-slate-100">{{ $subj->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $subj->code }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Update Subjects</button>
        </form>
    </div>
</div>
