<!-- Positions/Leadership Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <h3 class="text-lg font-semibold text-slate-50 mb-6">Leadership Positions</h3>

    <!-- Add Position Form -->
    <form method="POST" action="{{ route('admin.students.add-position', $student) }}" class="mb-6 bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Position Type</label>
                <select name="position_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="prefect">Prefect</option>
                    <option value="head_boy">Head Boy</option>
                    <option value="head_girl">Head Girl</option>
                    <option value="class_rep">Class Monitor/Rep</option>
                    <option value="house_captain">House Captain</option>
                    <option value="sports_captain">Sports Captain</option>
                    <option value="club_president">Club President</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Title</label>
                <input type="text" name="position_title" required placeholder="e.g. Senior Prefect, Head Boy"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Start Date</label>
                <input type="date" name="start_date" required value="{{ date('Y-m-d') }}"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm transition">Add Position</button>
            </div>
        </div>
        <div class="mt-3">
            <label class="text-xs text-slate-400 mb-1 block">Description (optional)</label>
            <input type="text" name="description" placeholder="Optional description"
                class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
        </div>
    </form>

    <!-- Current Positions -->
    <h4 class="text-sm font-medium text-slate-300 mb-3">All Positions</h4>
    <div class="space-y-2">
        @forelse($student->studentPositions as $pos)
            <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/50">
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2 py-1 rounded-full {{ $pos->is_active ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-600/20 text-slate-500' }}">
                        {{ str_replace('_', ' ', ucfirst($pos->position_type)) }}
                    </span>
                    <div>
                        <p class="text-slate-100 text-sm font-medium">{{ $pos->position_title }}</p>
                        <p class="text-slate-500 text-xs">{{ $pos->start_date }} {{ $pos->end_date ? '→ ' . $pos->end_date : '(Ongoing)' }}</p>
                    </div>
                </div>
                @if($pos->is_active)
                    <form method="POST" action="{{ route('admin.students.remove-position', ['student' => $student, 'position' => $pos]) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition" onclick="return confirm('Remove this position?')">End</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center py-8 text-slate-500 text-sm">No positions assigned yet.</div>
        @endforelse
    </div>
</div>
