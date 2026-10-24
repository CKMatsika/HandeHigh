@php
    $currentCategory = isset($student) ? ($student->funding_status === 'scholarship' ? 'scholarship' : ($student->is_boarding ? 'boarding' : 'day')) : 'day';
    $selectedCategory = old('student_category', $currentCategory);
@endphp
<div class="space-y-3" data-category-fields>
    <label class="text-sm text-slate-300 block">Student category
        <select name="student_category" onchange="const field = this.closest('[data-category-fields]').querySelector('[data-scholarship-residency]'); field.style.display = this.value === 'scholarship' ? 'block' : 'none'; field.querySelector('select').required = this.value === 'scholarship';" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2">
            <option value="boarding" @selected($selectedCategory === 'boarding')>Boarding</option>
            <option value="day" @selected($selectedCategory === 'day')>Day</option>
            <option value="scholarship" @selected($selectedCategory === 'scholarship')>Scholarship</option>
        </select>
    </label>
    <label data-scholarship-residency style="display: {{ $selectedCategory === 'scholarship' ? 'block' : 'none' }}" class="text-sm text-slate-300">Scholarship residency
        <select name="scholarship_residency" @required($selectedCategory === 'scholarship') class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2">
            <option value="">Select residency</option>
            <option value="boarding" @selected(old('scholarship_residency', isset($student) ? ($student->is_boarding ? 'boarding' : 'day') : '') === 'boarding')>Scholarship — Boarding</option>
            <option value="day" @selected(old('scholarship_residency', isset($student) ? ($student->is_boarding ? 'boarding' : 'day') : '') === 'day')>Scholarship — Day</option>
        </select>
    </label>
    <p class="text-xs text-slate-400">For scholarships, save the profile then open Scholarships to record coverage and approval. Fees follow residency until approved coverage is applied.</p>
</div>
