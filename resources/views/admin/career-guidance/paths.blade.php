@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Career Paths</h1>
            <p class="text-xs text-slate-400 mt-1">Define career paths with subject requirements for AI matching.</p>
        </div>
        <button onclick="document.getElementById('addPathForm').classList.toggle('hidden')" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Career Path
        </button>
    </div>

    <form id="addPathForm" method="POST" action="{{ route('admin.career-guidance.paths.store') }}" class="hidden rounded-xl border border-slate-700 bg-slate-800/50 p-6 space-y-4">
        @csrf
        <h3 class="text-sm font-semibold text-slate-50">New Career Path</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Name *</label><input type="text" name="name" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Education Level</label><input type="text" name="education_level" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" placeholder="e.g. A-Level, Diploma, Degree"></div>
            <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Description</label><textarea name="description" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></textarea></div>
            <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Typical Subjects</label><textarea name="typical_subjects" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" placeholder="e.g. Mathematics, Physics, Chemistry"></textarea></div>
            <div><label class="block text-xs text-slate-400 mb-1">Skills Required</label><textarea name="skills" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></textarea></div>
            <div><label class="block text-xs text-slate-400 mb-1">Career Outlook</label><textarea name="outlook" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></textarea></div>
        </div>

        <div>
            <label class="block text-xs text-slate-400 mb-2">Subject Requirements (minimum scores for matching)</label>
            <div id="reqContainer" class="space-y-2">
                <div class="flex items-center gap-2 requirement-row">
                    <select name="subject_requirements[0][subject_id]" class="flex-1 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj->id }}">{{ $subj->name }} ({{ $subj->code }})</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="subject_requirements[0][subject_name]" class="subject-name">
                    <input type="number" name="subject_requirements[0][min_score]" placeholder="Min %" min="0" max="100" class="w-20 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
                </div>
            </div>
            <button type="button" onclick="addRequirement()" class="mt-2 text-xs text-emerald-400 hover:text-emerald-300">+ Add Subject Requirement</button>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Save</button>
            <button type="button" onclick="this.closest('form').classList.add('hidden')" class="bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs px-4 py-2 rounded-lg">Cancel</button>
        </div>
    </form>

    <div class="space-y-4">
        @forelse($paths as $path)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-slate-50">{{ $path->name }}</h3>
                            @if($path->is_active)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-emerald-500/20 text-emerald-400">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-slate-500/20 text-slate-400">Inactive</span>
                            @endif
                        </div>
                        @if($path->description)
                            <p class="text-xs text-slate-400 mt-1">{{ $path->description }}</p>
                        @endif
                        @if($path->education_level)
                            <p class="text-[10px] text-slate-500 mt-1">Education: {{ $path->education_level }}</p>
                        @endif
                        @if($path->skills)
                            <p class="text-[10px] text-slate-500">Skills: {{ $path->skills }}</p>
                        @endif
                        @if($path->subject_requirements)
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($path->subject_requirements as $req)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-indigo-500/20 text-indigo-400">
                                        {{ $req['subject_name'] ?? 'Unknown' }}: {{ $req['min_score'] ?? 0 }}%
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <button onclick="document.getElementById('editPath{{ $path->id }}').classList.toggle('hidden')" class="text-xs text-amber-400 hover:text-amber-300">Edit</button>
                        <form method="POST" action="{{ route('admin.career-guidance.paths.destroy', $path) }}" onsubmit="return confirm('Delete this career path?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </div>

                <form id="editPath{{ $path->id }}" method="POST" action="{{ route('admin.career-guidance.paths.update', $path) }}" class="hidden mt-4 p-4 rounded-lg border border-slate-700 bg-slate-800/50 space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-xs text-slate-400 mb-1">Name</label><input type="text" name="name" value="{{ $path->name }}" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></div>
                        <div><label class="block text-xs text-slate-400 mb-1">Education Level</label><input type="text" name="education_level" value="{{ $path->education_level }}" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs"></div>
                        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Description</label><textarea name="description" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">{{ $path->description }}</textarea></div>
                        <div><label class="block text-xs text-slate-400 mb-1">Skills</label><textarea name="skills" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">{{ $path->skills }}</textarea></div>
                        <div><label class="block text-xs text-slate-400 mb-1">Outlook</label><textarea name="outlook" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">{{ $path->outlook }}</textarea></div>
                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" {{ $path->is_active ? 'checked' : '' }} class="rounded bg-slate-700 border-slate-600 text-emerald-600">
                                <span class="text-xs text-slate-300">Active</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-4 py-2 rounded-lg">Update</button>
                </form>
            </div>
        @empty
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-12 text-center">
                <p class="text-sm text-slate-400 mb-4">No career paths defined yet.</p>
                <button onclick="document.getElementById('addPathForm').classList.remove('hidden'); document.getElementById('addPathForm').scrollIntoView({behavior: 'smooth'})" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Create Your First Career Path</button>
            </div>
        @endforelse
    </div>
    @if($paths->hasPages())<div class="mt-4">{{ $paths->links() }}</div>@endif
</div>

<script>
let reqIndex = {{ count(old('subject_requirements', [])) ?: 1 }};
function addRequirement() {
    const container = document.getElementById('reqContainer');
    const row = container.querySelector('.requirement-row').cloneNode(true);
    row.querySelectorAll('input, select').forEach(el => {
        el.name = el.name.replace(/\d+/, reqIndex);
        if (el.type === 'text' || el.type === 'number') el.value = '';
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
    });
    container.appendChild(row);
    reqIndex++;
}
</script>
@endsection
