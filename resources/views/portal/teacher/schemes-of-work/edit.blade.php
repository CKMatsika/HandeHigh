@extends('layouts.app')

@section('content')
@include('portal.teacher.schemes-of-work.ai-resource-finder')

<div id="edit-header" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-50 tracking-tight">Edit Scheme of Work / Scheme-Cum Plan</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $scheme->title }} (MoPSE Standard)</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="openAiFinder()" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-500 to-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:from-violet-600 hover:to-indigo-700 shadow-lg shadow-indigo-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            AI Curriculum Assistant
        </button>
        <a href="{{ route('teacher.schemes-of-work.show', $scheme) }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Cancel
        </a>
    </div>
</div>

<!-- Success Toast -->
<div id="ai-success-toast" class="fixed bottom-4 right-4 z-50 hidden bg-emerald-500 text-white px-4 py-2.5 rounded-xl shadow-xl text-xs font-semibold flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    Resources inserted into active scheme session!
</div>

@if($errors->any())
    <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100 mb-6">
        <div class="font-semibold text-rose-200 mb-1">Please correct the following errors:</div>
        <ul class="list-disc ml-4 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('teacher.schemes-of-work.update', $scheme) }}" class="space-y-6 text-xs text-slate-100">
    @csrf
    @method('PUT')

    <!-- Scheme Identification & MoPSE Meta Section -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                <h2 class="text-sm font-semibold text-slate-100 uppercase tracking-wider">1. Scheme Identification & General Aims</h2>
            </div>
            <span class="text-[10px] text-slate-400 font-mono">Forms 1–4 National Curriculum</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Scheme Title *</label>
                <input type="text" name="title" value="{{ old('title', $scheme->title) }}" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">General Topic / Theme *</label>
                <input type="text" name="general_topic" value="{{ old('general_topic', $scheme->general_topic) }}" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Learning Area / Subject *</label>
                <select name="subject_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ old('subject_id', $scheme->subject_id) == $subject->id ? 'selected' : '' }}>{{ $subject->name }} ({{ $subject->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Class / Form *</label>
                <select name="school_class_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('school_class_id', $scheme->school_class_id) == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Academic Year *</label>
                    <input type="text" name="academic_year" value="{{ old('academic_year', $scheme->academic_year) }}" required
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Term *</label>
                    <select name="term" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                        @foreach($terms as $term)
                            <option value="{{ $term }}" {{ old('term', $scheme->term) == $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">General Aims (Broad Scheme Aims) *</label>
                <textarea name="aims" rows="3" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition">{{ old('aims', $scheme->aims) }}</textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Syllabus Reference (SOM)</label>
                <input type="text" name="syllabus_reference" value="{{ old('syllabus_reference', $scheme->syllabus_reference) }}"
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition">
            </div>

            <div class="md:col-span-3">
                <label class="block text-[11px] font-medium text-slate-300 mb-1.5">Integrated Cross-Cutting Themes</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 bg-slate-950/40 p-3 rounded-xl border border-slate-800">
                    @php
                        $themes = [
                            'Heritage Studies', 'Financial Literacy', 'Children Rights', 'Disaster Risk Management',
                            'Sexuality / HIV & AIDS', 'Child Protection', 'Human Rights', 'Collaboration',
                            'Environmental Issues', 'Gender Sensitivity'
                        ];
                        $selectedThemes = old('cross_cutting_themes', $scheme->cross_cutting_themes ?? []);
                    @endphp
                    @foreach($themes as $theme)
                        <label class="flex items-center gap-2 cursor-pointer text-[11px] text-slate-300 hover:text-slate-100">
                            <input type="checkbox" name="cross_cutting_themes[]" value="{{ $theme }}"
                                {{ is_array($selectedThemes) && in_array($theme, $selectedThemes) ? 'checked' : '' }}
                                class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-0">
                            <span>{{ $theme }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- MoPSE 8-Column Scheme-Cum Plan Matrix -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 backdrop-blur-xl p-5 space-y-4 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <div>
                    <h2 class="text-sm font-semibold text-slate-100 uppercase tracking-wider">2. Scheme-Cum Plan Matrix (8 Columns)</h2>
                    <p class="text-[11px] text-slate-400">Standard columns: Week Ending, Content/Topic, Objectives, Competencies/Skills, SOM/Media, Facility/Equipment, Methods/Activities, Evaluation</p>
                </div>
            </div>
            <button type="button" onclick="addSession()" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600 shadow-lg shadow-emerald-500/20 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Week / Unit
            </button>
        </div>

        <div id="sessions-container" class="space-y-5">
            @foreach($scheme->items as $index => $item)
                <div class="session-item rounded-2xl border border-slate-800 bg-slate-950/70 p-4 space-y-4 transition duration-200 hover:border-slate-700">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                        <span class="text-xs font-semibold text-indigo-400 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                            Week {{ $item->week_number }} Plan
                        </span>
                        <button type="button" onclick="removeSession(this)" class="text-rose-400 hover:text-rose-300 text-[11px] font-medium transition">Remove</button>
                    </div>

                    <!-- Row 1: Week metadata & Topics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Week Number *</label>
                            <input type="number" name="items[{{ $index }}][week_number]" min="1" value="{{ old("items.{$index}.week_number", $item->week_number) }}" required
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Week Ending (Friday) *</label>
                            <input type="date" name="items[{{ $index }}][week_ending]" value="{{ old("items.{$index}.week_ending", $item->week_ending ? $item->week_ending->format('Y-m-d') : '') }}" required
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Content / Main Topic *</label>
                            <input type="text" name="items[{{ $index }}][topic]" value="{{ old("items.{$index}.topic", $item->topic) }}" required
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                    </div>

                    <!-- Row 2: Sub-Topic & SMART Objectives -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Sub-Topic / Specific Area</label>
                            <input type="text" name="items[{{ $index }}][sub_topic]" value="{{ old("items.{$index}.sub_topic", $item->sub_topic) }}"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">SMART Objectives ("By the end of the week pupils should be able to:") *</label>
                            <textarea name="items[{{ $index }}][objectives]" rows="2" required
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">{{ old("items.{$index}.objectives", $item->objectives) }}</textarea>
                        </div>
                    </div>

                    <!-- Row 3: Competencies/Skills & SOM/Media & Facility/Equipment -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Competencies & Skills</label>
                            <textarea name="items[{{ $index }}][competencies_skills]" rows="2"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">{{ old("items.{$index}.competencies_skills", $item->competencies_skills) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">SOM / Media (Source of Material)</label>
                            <textarea name="items[{{ $index }}][som_media]" rows="2"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">{{ old("items.{$index}.som_media", $item->som_media ?? $item->resources) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Facility / Equipment</label>
                            <textarea name="items[{{ $index }}][facility_equipment]" rows="2"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">{{ old("items.{$index}.facility_equipment", $item->facility_equipment) }}</textarea>
                        </div>
                    </div>

                    <!-- Row 4: Methods/Activities & Evaluation -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Methods & Activities (Learner-Centered)</label>
                            <textarea name="items[{{ $index }}][methods_activities]" rows="2"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">{{ old("items.{$index}.methods_activities", $item->methods_activities ?? $item->teaching_methods) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-300 mb-1">Evaluation (Post-Lesson Reflection)</label>
                            <textarea name="items[{{ $index }}][evaluation]" rows="2"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">{{ old("items.{$index}.evaluation", $item->evaluation ?? $item->remarks) }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('teacher.schemes-of-work.show', $scheme) }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Cancel</a>
        <button type="submit" name="action" value="save" class="inline-flex items-center rounded-xl bg-slate-800 border border-slate-700 px-5 py-2 text-xs font-semibold text-slate-100 hover:bg-slate-700 transition">Save as Draft</button>
        <button type="submit" name="action" value="preview" class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-2 text-xs font-semibold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-500/20 transition">Preview & Submit</button>
    </div>
</form>

@push('scripts')
<script>
var sessionIndex = {{ count($scheme->items) }};

function addSession() {
    var container = document.getElementById('sessions-container');
    var div = document.createElement('div');
    div.className = 'session-item rounded-2xl border border-slate-800 bg-slate-950/70 p-4 space-y-4 transition duration-200 hover:border-slate-700 mt-4';
    
    var weekNum = sessionIndex + 1;
    
    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
            <span class="text-xs font-semibold text-indigo-400 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                Week ${weekNum} Plan
            </span>
            <button type="button" onclick="removeSession(this)" class="text-rose-400 hover:text-rose-300 text-[11px] font-medium transition">Remove</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Week Number *</label>
                <input type="number" name="items[${sessionIndex}][week_number]" min="1" value="${weekNum}" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Week Ending (Friday) *</label>
                <input type="date" name="items[${sessionIndex}][week_ending]" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Content / Main Topic *</label>
                <input type="text" name="items[${sessionIndex}][topic]" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Sub-Topic / Specific Area</label>
                <input type="text" name="items[${sessionIndex}][sub_topic]"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">SMART Objectives ("By the end of the week pupils should be able to:") *</label>
                <textarea name="items[${sessionIndex}][objectives]" rows="2" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Competencies & Skills</label>
                <textarea name="items[${sessionIndex}][competencies_skills]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">SOM / Media (Source of Material)</label>
                <textarea name="items[${sessionIndex}][som_media]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Facility / Equipment</label>
                <textarea name="items[${sessionIndex}][facility_equipment]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Methods & Activities (Learner-Centered)</label>
                <textarea name="items[${sessionIndex}][methods_activities]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Evaluation (Post-Lesson Reflection)</label>
                <textarea name="items[${sessionIndex}][evaluation]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition"></textarea>
            </div>
        </div>
    `;
    
    container.appendChild(div);
    sessionIndex++;
}

function removeSession(btn) {
    var items = document.querySelectorAll('.session-item');
    if (items.length > 1) {
        btn.closest('.session-item').remove();
    } else {
        alert('A scheme of work must have at least one teaching session/week.');
    }
}

document.addEventListener('resources-selected', function(e) {
    var textareas = document.querySelectorAll('textarea[name*="[som_media]"]');
    if (textareas.length > 0) {
        var ta = textareas[textareas.length - 1];
        ta.value = ta.value ? ta.value + '\n' + e.detail.resources : e.detail.resources;
    }
    var toast = document.getElementById('ai-success-toast');
    if (toast) {
        toast.classList.remove('hidden');
        setTimeout(function() { toast.classList.add('hidden'); }, 3000);
    }
});
</script>
@endpush

@endsection
