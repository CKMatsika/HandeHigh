@extends('layouts.app')

@section('content')
@include('portal.teacher.schemes-of-work.ai-resource-finder')

<div id="create-header" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-50 tracking-tight">Create Scheme of Work / Scheme-Cum Plan</h1>
            <p class="text-xs text-slate-400 mt-0.5">Ministry of Primary and Secondary Education (MoPSE) Standard Format</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="openAiFinder()" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-500 to-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:from-violet-600 hover:to-indigo-700 shadow-lg shadow-indigo-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            AI Curriculum Assistant
        </button>
        <a href="{{ route('teacher.schemes-of-work.index') }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back to List
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

<form method="POST" action="{{ route('teacher.schemes-of-work.store') }}" class="space-y-6 text-xs text-slate-100">
    @csrf

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
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Form 3 Art & Design - Term 1 Scheme-Cum Plan">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">General Topic / Theme *</label>
                <input type="text" name="general_topic" value="{{ old('general_topic') }}" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Art and Technology / Algebra">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Learning Area / Subject *</label>
                <select name="subject_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                    <option value="">Select learning area</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }} ({{ $subject->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Class / Form *</label>
                <select name="school_class_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                    <option value="">Select class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Academic Year *</label>
                    <input type="text" name="academic_year" value="{{ old('academic_year', $currentYear) }}" required
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 mb-1">Term *</label>
                    <select name="term" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                        @foreach($terms as $term)
                            <option value="{{ $term }}" {{ old('term') == $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">General Aims (Broad Scheme Aims) *</label>
                <textarea name="aims" rows="3" required class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. &#10;• To develop appreciation of the role of art in a wider culture and society.&#10;• To establish competencies in Art Technology systems.">{{ old('aims') }}</textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Syllabus Reference (SOM)</label>
                <input type="text" name="syllabus_reference" value="{{ old('syllabus_reference') }}"
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. National Syllabus p. 21 / School Syllabus">
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
                    @endphp
                    @foreach($themes as $theme)
                        <label class="flex items-center gap-2 cursor-pointer text-[11px] text-slate-300 hover:text-slate-100">
                            <input type="checkbox" name="cross_cutting_themes[]" value="{{ $theme }}"
                                {{ is_array(old('cross_cutting_themes')) && in_array($theme, old('cross_cutting_themes')) ? 'checked' : '' }}
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
            <div class="session-item rounded-2xl border border-slate-800 bg-slate-950/70 p-4 space-y-4 transition duration-200 hover:border-slate-700">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                    <span class="text-xs font-semibold text-indigo-400 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                        Week 1 Plan
                    </span>
                    <button type="button" onclick="removeSession(this)" class="text-rose-400 hover:text-rose-300 text-[11px] font-medium transition">Remove</button>
                </div>

                <!-- Row 1: Week metadata & Topics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Week Number *</label>
                        <input type="number" name="items[0][week_number]" min="1" value="1" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Week Ending (Friday) *</label>
                        <input type="date" name="items[0][week_ending]" value="{{ date('Y-m-d', strtotime('next friday')) }}" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Content / Main Topic *</label>
                        <input type="text" name="items[0][topic]" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. The development of Art technology in Zimbabwe">
                    </div>
                </div>

                <!-- Row 2: Sub-Topic & SMART Objectives -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Sub-Topic / Specific Area</label>
                        <input type="text" name="items[0][sub_topic]"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Pre-colonial tools and materials">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">SMART Objectives ("By the end of the week pupils should be able to:") *</label>
                        <textarea name="items[0][objectives]" rows="2" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="• Identify tools and materials used during the pre-colonial era...&#10;• Make artworks using pre-colonial tools..."></textarea>
                    </div>
                </div>

                <!-- Row 3: Competencies/Skills & SOM/Media & Facility/Equipment -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Competencies & Skills</label>
                        <textarea name="items[0][competencies_skills]" rows="2"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Critical thinking, Problem solving, Rock art paintings, Basketry, Pottery"></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">SOM / Media (Source of Material)</label>
                        <textarea name="items[0][som_media]" rows="2"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. National Syllabus, School Syllabus, ICT Tools, Resource persons"></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Facility / Equipment</label>
                        <textarea name="items[0][facility_equipment]" rows="2"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Projector, Laptop, Markers, Manila, Art Studio, Chisel, Wax"></textarea>
                    </div>
                </div>

                <!-- Row 4: Methods/Activities & Evaluation -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Methods & Activities (Learner-Centered)</label>
                        <textarea name="items[0][methods_activities]" rows="2"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Listing art tools, Group discussions, Creating artworks using pre-colonial tools"></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Evaluation (Post-Lesson Reflection)</label>
                        <textarea name="items[0][evaluation]" rows="2"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="Evaluation of challenges, successes, and learner performance (can be updated after delivery)"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('teacher.schemes-of-work.index') }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-5 py-2 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Cancel</a>
        <button type="submit" name="action" value="save" class="inline-flex items-center rounded-xl bg-slate-800 border border-slate-700 px-5 py-2 text-xs font-semibold text-slate-100 hover:bg-slate-700 transition">Save as Draft</button>
        <button type="submit" name="action" value="preview" class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-2 text-xs font-semibold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-500/20 transition">Preview & Submit</button>
    </div>
</form>

@push('scripts')
<script>
var sessionIndex = 1;

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
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Graphic Design / Sculpture">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Sub-Topic / Specific Area</label>
                <input type="text" name="items[${sessionIndex}][sub_topic]"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Principles of design">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-slate-300 mb-1">SMART Objectives ("By the end of the week pupils should be able to:") *</label>
                <textarea name="items[${sessionIndex}][objectives]" rows="2" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="• List design principles...&#10;• Produce graphic mockups..."></textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Competencies & Skills</label>
                <textarea name="items[${sessionIndex}][competencies_skills]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Creativity, Design thinking, Visual analysis"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">SOM / Media (Source of Material)</label>
                <textarea name="items[${sessionIndex}][som_media]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. National Syllabus, Textbooks, Online resources"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Facility / Equipment</label>
                <textarea name="items[${sessionIndex}][facility_equipment]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Drawing boards, Inks, Markers, Projector"></textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Methods & Activities (Learner-Centered)</label>
                <textarea name="items[${sessionIndex}][methods_activities]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="e.g. Demonstrations, Practical studio work, Peer review"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Evaluation (Post-Lesson Reflection)</label>
                <textarea name="items[${sessionIndex}][evaluation]" rows="2"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none transition" placeholder="Evaluation of learner performance"></textarea>
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
