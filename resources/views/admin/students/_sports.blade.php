<!-- Sports Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <h3 class="text-lg font-semibold text-slate-50 mb-6">Sports Activities</h3>

    <!-- Add Sport Form -->
    <form method="POST" action="{{ route('admin.students.add-sport', $student) }}" class="mb-6 bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Sport Name</label>
                <input type="text" name="sport_name" required placeholder="e.g. Football, Athletics"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Category</label>
                <select name="sport_category" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="team_sport">Team Sport</option>
                    <option value="individual_sport">Individual Sport</option>
                    <option value="athletics">Athletics</option>
                    <option value="water_sport">Water Sport</option>
                    <option value="winter_sport">Winter Sport</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Position / Role</label>
                <input type="text" name="position" placeholder="e.g. Captain, Striker"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="text-xs text-slate-400 mb-1 block">Started</label>
                    <input type="date" name="started_date" required value="{{ date('Y-m-d') }}"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm transition">Add</button>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Team Level</label>
                <input type="text" name="team_level" placeholder="e.g. Varsity, Junior"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Achievements</label>
                <input type="text" name="achievements" placeholder="e.g. 1st Place Inter-School 2024"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
        </div>
    </form>

    <!-- Sports List -->
    <div class="space-y-2">
        @forelse($student->studentSports as $sport)
            <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/50">
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2 py-1 rounded-full {{ $sport->is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-600/20 text-slate-500' }}">
                        {{ str_replace('_', ' ', ucfirst($sport->sport_category)) }}
                    </span>
                    <div>
                        <p class="text-slate-100 text-sm font-medium">{{ $sport->sport_name }} {{ $sport->position ? "({$sport->position})" : '' }}</p>
                        <p class="text-slate-500 text-xs">
                            {{ $sport->team_level ? $sport->team_level . ' | ' : '' }}
                            Started {{ $sport->started_date }}
                            {{ $sport->achievements ? '| 🏆 ' . $sport->achievements : '' }}
                        </p>
                    </div>
                </div>
                @if($sport->is_active)
                    <form method="POST" action="{{ route('admin.students.remove-sport', ['student' => $student, 'sport' => $sport]) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition" onclick="return confirm('Remove this sport?')">Remove</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center py-8 text-slate-500 text-sm">No sports activities yet.</div>
        @endforelse
    </div>
</div>
