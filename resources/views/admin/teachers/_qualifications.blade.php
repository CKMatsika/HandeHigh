<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-50">Qualifications</h3>
        <button onclick="document.getElementById('addQualForm').classList.toggle('hidden')" class="text-xs text-emerald-400 hover:text-emerald-300">+ Add Qualification</button>
    </div>

    <form id="addQualForm" method="POST" action="{{ route('admin.teachers.qualifications.store', $teacher) }}" class="hidden rounded-xl border border-slate-700 bg-slate-800/50 p-4 space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Qualification Name *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" placeholder="B.Ed. Mathematics">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Institution *</label>
                <input type="text" name="institution" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" placeholder="University of Zimbabwe">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Grade / Class</label>
                <input type="text" name="grade" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" placeholder="Upper Second">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Start Year</label>
                    <input type="number" name="year_start" min="1900" max="2099" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">End Year</label>
                    <input type="number" name="year_end" min="1900" max="2099" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></textarea>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Save</button>
            <button type="button" onclick="this.closest('form').classList.add('hidden')" class="bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs px-4 py-2 rounded-lg">Cancel</button>
        </div>
    </form>

    @if($teacher->qualifications->isEmpty())
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-8 text-center">
            <p class="text-sm text-slate-400">No qualifications recorded.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($teacher->qualifications as $qual)
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-slate-50">{{ $qual->name }}</h4>
                            <p class="text-xs text-slate-400">{{ $qual->institution }}</p>
                            @if($qual->year_start || $qual->year_end)
                                <p class="text-[10px] text-slate-500 mt-1">
                                    {{ $qual->year_start ?? '?' }} – {{ $qual->year_end ?? 'Present' }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($qual->grade)
                                <span class="text-xs text-emerald-400">{{ $qual->grade }}</span>
                            @endif
                            <form method="POST" action="{{ route('admin.teachers.qualifications.destroy', [$teacher, $qual]) }}" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
                            </form>
                        </div>
                    </div>
                    @if($qual->notes)
                        <p class="text-xs text-slate-400 mt-2">{{ $qual->notes }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
