@extends('layouts.app')

@section('content')
@include('portal.teacher.schemes-of-work.ai-resource-finder')

<div id="create-header" class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Create Scheme of Work</h1>
            <p class="text-xs text-slate-400 mt-1">Plan your teaching sessions</p>
        </div>
        <button type="button" onclick="openAiFinder()" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-violet-500 to-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:from-violet-600 hover:to-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            Find Resources
        </button>
    </div>
    <a href="{{ route('teacher.schemes-of-work.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
        Back to List
    </a>
</div>

<!-- Success Toast -->
<div id="ai-success-toast" class="fixed bottom-4 right-4 z-50 hidden bg-emerald-500 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium">
    Resources added to scheme!
</div>

@push('scripts')
<script>
document.addEventListener('resources-selected', function(e) {
    var textareas = document.querySelectorAll('textarea[name*="[resources]"]');
    if (textareas.length > 0) {
        var ta = textareas[0];
        ta.value = ta.value ? ta.value + '\n' + e.detail.resources : e.detail.resources;
    }
    var toast = document.getElementById('ai-success-toast');
    toast.classList.remove('hidden');
    setTimeout(function() { toast.classList.add('hidden'); }, 3000);
});
</script>
@endpush

@if($errors->any())
    <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
        <ul class="list-disc ml-4 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('teacher.schemes-of-work.store') }}" class="space-y-6 text-xs text-slate-100">
    @csrf

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 space-y-3">
        <h2 class="text-sm font-semibold text-slate-100 mb-1">Scheme Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Year 7 Mathematics - Term 1">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="Brief overview of the scheme">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Subject *</label>
                <select name="subject_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    <option value="">Select subject</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Class *</label>
                <select name="school_class_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    <option value="">Select class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Academic Year *</label>
                <input type="text" name="academic_year" value="{{ old('academic_year', $currentYear) }}" required
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Term *</label>
                <select name="term" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    <option value="">Select term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term }}" {{ old('term') == $term ? 'selected' : '' }}>{{ $term }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 space-y-3">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-sm font-semibold text-slate-100">Teaching Sessions</h2>
            <button type="button" onclick="addSession()" class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-emerald-600 transition">
                + Add Session
            </button>
        </div>

        <div id="sessions-container" class="space-y-4">
            <div class="session-item rounded-xl border border-slate-700 bg-slate-800/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-slate-400">Session 1</span>
                    <button type="button" onclick="removeSession(this)" class="text-red-400 hover:text-red-300 text-[11px]">Remove</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Week *</label>
                        <input type="number" name="items[0][week_number]" min="1" value="1" required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Day *</label>
                        <select name="items[0][day_of_week]" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Topic *</label>
                        <input type="text" name="items[0][topic]" required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Introduction to Algebra">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Sub-topic</label>
                    <input type="text" name="items[0][sub_topic]"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Variables and Expressions">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Learning Objectives *</label>
                    <textarea name="items[0][objectives]" rows="2" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="What students will learn..."></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Teaching Methods</label>
                        <input type="text" name="items[0][teaching_methods]"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Lecture, Discussion">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Resources</label>
                        <input type="text" name="items[0][resources]"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Textbook Ch.3, Whiteboard">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Assessment</label>
                        <input type="text" name="items[0][assessment]"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Class exercise p.45">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Remarks</label>
                    <input type="text" name="items[0][remarks]"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="Any additional notes">
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('teacher.schemes-of-work.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Cancel</a>
        <button type="submit" name="action" value="save" class="inline-flex items-center rounded-full bg-slate-700 px-4 py-1.5 text-xs font-medium text-white hover:bg-slate-600 transition">Save as Draft</button>
        <button type="submit" name="action" value="preview" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Preview & Submit</button>
    </div>
</form>

@push('scripts')
<script>
let sessionIndex = 1;

function addSession() {
    const container = document.getElementById('sessions-container');
    const template = `
        <div class="session-item rounded-xl border border-slate-700 bg-slate-800/50 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-medium text-slate-400">Session ${'{'}sessionIndex + 1{'}'}</span>
                <button type="button" onclick="removeSession(this)" class="text-red-400 hover:text-red-300 text-[11px]">Remove</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Week *</label>
                    <input type="number" name="items[${'{'}sessionIndex{'}'}][week_number]" min="1" value="1" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Day *</label>
                    <select name="items[${'{'}sessionIndex{'}'}][day_of_week]" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Topic *</label>
                    <input type="text" name="items[${'{'}sessionIndex{'}'}][topic]" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Introduction to Algebra">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Sub-topic</label>
                <input type="text" name="items[${'{'}sessionIndex{'}'}][sub_topic]"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Variables and Expressions">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Learning Objectives *</label>
                <textarea name="items[${'{'}sessionIndex{'}'}][objectives]" rows="2" required
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="What students will learn..."></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Teaching Methods</label>
                    <input type="text" name="items[${'{'}sessionIndex{'}'}][teaching_methods]"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Lecture, Discussion">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Resources</label>
                    <input type="text" name="items[${'{'}sessionIndex{'}'}][resources]"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Textbook Ch.3, Whiteboard">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Assessment</label>
                    <input type="text" name="items[${'{'}sessionIndex{'}'}][assessment]"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Class exercise p.45">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Remarks</label>
                <input type="text" name="items[${'{'}sessionIndex{'}'}][remarks]"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="Any additional notes">
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
    sessionIndex++;
}

function removeSession(btn) {
    const container = document.getElementById('sessions-container');
    if (container.children.length > 1) {
        btn.closest('.session-item').remove();
    }
}
</script>
@endpush
@endsection
