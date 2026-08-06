<!-- Clubs Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <h3 class="text-lg font-semibold text-slate-50 mb-6">Club Memberships</h3>

    <!-- Add Club Form -->
    <form method="POST" action="{{ route('admin.students.add-club', $student) }}" class="mb-6 bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Club Name</label>
                <input type="text" name="club_name" required placeholder="e.g. Debate Club"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Type</label>
                <select name="club_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    <option value="academic">Academic</option>
                    <option value="sports">Sports</option>
                    <option value="arts">Arts</option>
                    <option value="community">Community</option>
                    <option value="technology">Technology</option>
                    <option value="cultural">Cultural</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Role</label>
                <input type="text" name="role" placeholder="e.g. President, Member"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="text-xs text-slate-400 mb-1 block">Joined</label>
                    <input type="date" name="joined_date" required value="{{ date('Y-m-d') }}"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">Add</button>
            </div>
        </div>
        <div class="mt-3">
            <label class="text-xs text-slate-400 mb-1 block">Description (optional)</label>
            <input type="text" name="description" placeholder="Optional notes"
                class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
        </div>
    </form>

    <!-- Club List -->
    <div class="space-y-2">
        @forelse($student->studentClubs as $club)
            <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/50">
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2 py-1 rounded-full {{ $club->is_active ? 'bg-blue-500/20 text-blue-400' : 'bg-slate-600/20 text-slate-500' }}">
                        {{ ucfirst($club->club_type) }}
                    </span>
                    <div>
                        <p class="text-slate-100 text-sm font-medium">{{ $club->club_name }} {{ $club->role ? "({$club->role})" : '' }}</p>
                        <p class="text-slate-500 text-xs">Joined {{ $club->joined_date }} {{ $club->left_date ? '→ Left ' . $club->left_date : '' }}</p>
                    </div>
                </div>
                @if($club->is_active)
                    <form method="POST" action="{{ route('admin.students.remove-club', ['student' => $student, 'club' => $club]) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition" onclick="return confirm('Remove from this club?')">Remove</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center py-8 text-slate-500 text-sm">No clubs yet.</div>
        @endforelse
    </div>
</div>
