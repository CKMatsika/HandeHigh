@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/80 p-6 rounded-2xl border border-slate-800 backdrop-blur-xl shadow-xl">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-400 mb-1">
                <a href="{{ route('admin.exams.performance-reports.my-subjects') }}" class="hover:underline">My Subjects</a>
                <span>/</span>
                <span class="text-slate-300">{{ $class->name }}</span>
                <span>/</span>
                <span class="text-slate-400">{{ $subject->name }}</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                <span>{{ $subject->name }} — {{ $class->name }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    {{ $term }} ({{ $academicYear }})
                </span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">Capture marks and formatted teacher evaluations. Automatic grading is calculated in real-time according to school standards.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exams.performance-reports.my-subjects', ['academic_year' => $academicYear, 'term' => $term]) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 transition border border-slate-700">
                ← Back to Allocations
            </a>
        </div>
    </div>

    <!-- Student Mark & Evaluation Cards / Roster -->
    <div class="space-y-4">
        @if(empty($reportItems))
            <div class="p-12 text-center bg-slate-900/60 rounded-2xl border border-slate-800">
                <p class="text-sm font-semibold text-slate-400">No students are currently enrolled in {{ $class->name }}.</p>
            </div>
        @else
            @foreach($reportItems as $index => $item)
                @php
                    $student = $item['student'];
                    $report = $item['report'];
                    $subjectItem = $item['subjectItem'];
                    $isItemLocked = $report->isFinalized() || $report->is_locked;
                @endphp

                <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-6 shadow-xl transition hover:border-slate-700 evaluation-row" id="row-{{ $subjectItem->id }}" data-item-id="{{ $subjectItem->id }}">
                    <form action="{{ route('admin.exams.performance-reports.save-subject', $subjectItem->id) }}" method="POST" class="evaluation-form space-y-4">
                        @csrf
                        <input type="hidden" name="action" value="save_draft" class="action-field">

                        <!-- Top Student Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-800 gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center font-black text-indigo-400 text-sm">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-white flex items-center gap-2">
                                        <span>{{ $student->full_name }}</span>
                                        <span class="text-xs font-semibold text-slate-400">({{ $student->admission_number }})</span>
                                    </h3>
                                    <span class="text-xs text-slate-400">{{ $class->grade }} • {{ $student->gender ? ucfirst($student->gender) : 'Student' }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="status-badge px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider {{ $subjectItem->status === 'complete' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : ($subjectItem->status === 'draft' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700') }}">
                                    {{ $subjectItem->status === 'complete' ? '✓ Complete' : ($subjectItem->status === 'draft' ? 'Draft' : 'Not Started') }}
                                </span>
                            </div>
                        </div>

                        <!-- Mark Capture & Automatic Calculations -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                            <!-- Sitting Status -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Sitting Status</label>
                                <select name="result_status" class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2.5 font-medium focus:ring-2 focus:ring-indigo-500 result-status-input" {{ $isItemLocked ? 'disabled' : '' }}>
                                    <option value="present" {{ ($subjectItem->result_status ?? 'present') === 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="absent" {{ ($subjectItem->result_status ?? '') === 'absent' ? 'selected' : '' }}>Absent (ABS)</option>
                                    <option value="no_result" {{ ($subjectItem->result_status ?? '') === 'no_result' ? 'selected' : '' }}>No Result (NR)</option>
                                    <option value="withheld" {{ ($subjectItem->result_status ?? '') === 'withheld' ? 'selected' : '' }}>Withheld (W)</option>
                                    <option value="cancelled" {{ ($subjectItem->result_status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled (CAN)</option>
                                </select>
                            </div>

                            <!-- Mark Obtained -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Mark Obtained</label>
                                <input type="number" step="0.5" min="0" max="1000" name="mark_obtained" value="{{ $subjectItem->mark_obtained !== null ? $subjectItem->mark_obtained : '' }}" 
                                       placeholder="e.g. 72" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white text-sm font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 mark-input"
                                       {{ $isItemLocked ? 'disabled' : '' }}>
                            </div>

                            <!-- Max Mark -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Max Mark</label>
                                <input type="number" step="1" min="1" max="1000" name="max_mark" value="{{ $subjectItem->max_mark ?? 100 }}" 
                                       class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm font-semibold rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 max-mark-input"
                                       {{ $isItemLocked ? 'disabled' : '' }}>
                            </div>

                            <!-- Reactive Percentage Output -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Percentage</label>
                                <div class="bg-slate-900/80 border border-slate-800 rounded-xl px-3 py-2 text-sm font-black text-white percentage-display">
                                    {{ $subjectItem->percentage !== null ? number_format($subjectItem->percentage, 1) . '%' : '—' }}
                                </div>
                            </div>

                            <!-- Reactive Grade & Pass/Fail Badge -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Grade / Status</label>
                                <div class="grade-badge-container flex items-center gap-2">
                                    <span class="grade-display text-base font-black {{ $subjectItem->is_pass === false ? 'text-rose-500' : ($subjectItem->is_pass ? 'text-emerald-400' : 'text-slate-400') }}">
                                        {{ $subjectItem->grade ?? '—' }}
                                    </span>
                                    <span class="pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $subjectItem->is_pass === false ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : ($subjectItem->is_pass ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400') }}">
                                        {{ $subjectItem->is_pass === false ? 'FAIL' : ($subjectItem->is_pass ? 'PASS' : '—') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher Formatted Comment Section -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="text-xs font-bold text-slate-300">Subject Teacher's Formatted Comment</label>

                                <!-- Typography & Formatting Controls -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <!-- Font Family -->
                                    <select name="comment_font" class="bg-slate-950 border border-slate-800 text-slate-300 text-[11px] rounded-lg px-2 py-1 font-medium font-selector" {{ $isItemLocked ? 'disabled' : '' }}>
                                        @foreach(['Arial', 'Times New Roman', 'Calibri', 'Georgia', 'Verdana'] as $font)
                                            <option value="{{ $font }}" {{ ($subjectItem->comment_font ?? 'Arial') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                        @endforeach
                                    </select>

                                    <!-- Font Size -->
                                    <select name="comment_font_size" class="bg-slate-950 border border-slate-800 text-slate-300 text-[11px] rounded-lg px-2 py-1 font-medium size-selector" {{ $isItemLocked ? 'disabled' : '' }}>
                                        @foreach([8, 9, 10, 11, 12, 14, 16, 18] as $sz)
                                            <option value="{{ $sz }}" {{ ($subjectItem->comment_font_size ?? 11) == $sz ? 'selected' : '' }}>{{ $sz }}pt</option>
                                        @endforeach
                                    </select>

                                    <!-- Bold & Italic Toggles -->
                                    <label class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400 cursor-pointer bg-slate-950 border border-slate-800 px-2 py-1 rounded-lg">
                                        <input type="checkbox" name="bold" value="1" class="rounded bg-slate-900 border-slate-700 text-indigo-500 font-bold-checkbox" {{ !empty($subjectItem->comment_formatting['bold']) ? 'checked' : '' }} {{ $isItemLocked ? 'disabled' : '' }}>
                                        <span>B</span>
                                    </label>

                                    <label class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400 cursor-pointer bg-slate-950 border border-slate-800 px-2 py-1 rounded-lg italic">
                                        <input type="checkbox" name="italic" value="1" class="rounded bg-slate-900 border-slate-700 text-indigo-500 font-italic-checkbox" {{ !empty($subjectItem->comment_formatting['italic']) ? 'checked' : '' }} {{ $isItemLocked ? 'disabled' : '' }}>
                                        <span>I</span>
                                    </label>
                                </div>
                            </div>

                            <textarea name="comment" rows="2" 
                                      placeholder="Enter specific subject remarks on student performance and recommendations for improvement..." 
                                      class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 comment-textarea transition"
                                      style="font-family: {{ $subjectItem->comment_font ?? 'Arial' }}; font-size: {{ $subjectItem->comment_font_size ?? 11 }}pt; {{ !empty($subjectItem->comment_formatting['bold']) ? 'font-weight: bold;' : '' }} {{ !empty($subjectItem->comment_formatting['italic']) ? 'font-style: italic;' : '' }}"
                                      {{ $isItemLocked ? 'disabled' : '' }}>{{ $subjectItem->comment }}</textarea>
                        </div>

                        <!-- Row Actions & Status -->
                        @if(!$isItemLocked)
                            <div class="flex items-center justify-between pt-2">
                                <span class="text-[11px] text-slate-500 last-saved-text">
                                    @if($subjectItem->completed_at)
                                        Completed on {{ $subjectItem->completed_at->format('d M Y H:i') }}
                                    @elseif($subjectItem->updated_at)
                                        Last saved {{ $subjectItem->updated_at->diffForHumans() }}
                                    @endif
                                </span>

                                <div class="flex items-center gap-2">
                                    <button type="button" class="btn-save-draft px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 transition border border-slate-700">
                                        Save Draft
                                    </button>
                                    <button type="button" class="btn-mark-complete px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-lg shadow-emerald-600/20">
                                        Mark Complete ✓
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="pt-2 text-right">
                                <span class="text-xs font-semibold text-slate-500 bg-slate-800/80 px-3 py-1 rounded-lg">
                                    Report Finalized / Locked
                                </span>
                            </div>
                        @endif
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Real-Time Grade Calculation & AJAX Evaluation Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('.evaluation-row');

    rows.forEach(row => {
        const markInput = row.querySelector('.mark-input');
        const maxMarkInput = row.querySelector('.max-mark-input');
        const resultStatusInput = row.querySelector('.result-status-input');
        const percentageDisplay = row.querySelector('.percentage-display');
        const gradeDisplay = row.querySelector('.grade-display');
        const passFailBadge = row.querySelector('.pass-fail-badge');
        const fontSelector = row.querySelector('.font-selector');
        const sizeSelector = row.querySelector('.size-selector');
        const boldCheckbox = row.querySelector('.font-bold-checkbox');
        const italicCheckbox = row.querySelector('.font-italic-checkbox');
        const commentTextarea = row.querySelector('.comment-textarea');
        const form = row.querySelector('.evaluation-form');
        const btnSaveDraft = row.querySelector('.btn-save-draft');
        const btnMarkComplete = row.querySelector('.btn-mark-complete');
        const actionField = row.querySelector('.action-field');
        const statusBadge = row.querySelector('.status-badge');
        const lastSavedText = row.querySelector('.last-saved-text');

        // Real-Time Client-Side Calculation
        function updateCalculation() {
            const mark = markInput ? parseFloat(markInput.value) : null;
            const maxMark = maxMarkInput ? parseFloat(maxMarkInput.value) || 100 : 100;
            const sittingStatus = resultStatusInput ? resultStatusInput.value : 'present';

            if (sittingStatus === 'absent') {
                percentageDisplay.textContent = 'ABS';
                gradeDisplay.textContent = 'ABS';
                gradeDisplay.className = 'grade-display text-base font-bold text-rose-500';
                passFailBadge.textContent = 'ABSENT';
                passFailBadge.className = 'pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/30';
                return;
            }

            if (sittingStatus === 'no_result') {
                percentageDisplay.textContent = 'NR';
                gradeDisplay.textContent = 'NR';
                gradeDisplay.className = 'grade-display text-base font-bold text-amber-500';
                passFailBadge.textContent = 'NO RESULT';
                passFailBadge.className = 'pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30';
                return;
            }

            if (sittingStatus === 'withheld') {
                percentageDisplay.textContent = 'W';
                gradeDisplay.textContent = 'W';
                gradeDisplay.className = 'grade-display text-base font-bold text-purple-500';
                passFailBadge.textContent = 'WITHHELD';
                passFailBadge.className = 'pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-purple-500/10 text-purple-400 border border-purple-500/30';
                return;
            }

            if (isNaN(mark) || mark === null || markInput.value.trim() === '') {
                percentageDisplay.textContent = '—';
                gradeDisplay.textContent = '—';
                gradeDisplay.className = 'grade-display text-base font-bold text-slate-400';
                passFailBadge.textContent = '—';
                passFailBadge.className = 'pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-400';
                return;
            }

            const percentage = Math.min(100, Math.max(0, (mark / maxMark) * 100));
            percentageDisplay.textContent = percentage.toFixed(1) + '%';

            // ZIMSEC Default Grade Preview (Validated Authoritatively on Server)
            let grade = 'U';
            let isPass = false;

            if (percentage >= 75) {
                grade = 'A';
                isPass = true;
            } else if (percentage >= 65) {
                grade = 'B';
                isPass = true;
            } else if (percentage >= 50) {
                grade = 'C';
                isPass = true;
            } else if (percentage >= 45) {
                grade = 'D';
                isPass = true;
            } else if (percentage >= 40) {
                grade = 'E';
                isPass = true;
            } else {
                grade = 'U';
                isPass = false;
            }

            gradeDisplay.textContent = grade;
            if (isPass) {
                gradeDisplay.className = 'grade-display text-base font-black text-emerald-400';
                passFailBadge.textContent = 'PASS';
                passFailBadge.className = 'pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
            } else {
                gradeDisplay.className = 'grade-display text-base font-black text-rose-500';
                passFailBadge.textContent = 'FAIL';
                passFailBadge.className = 'pass-fail-badge px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/30';
            }
        }

        // Live Font Styling Updates
        function updateFontStyles() {
            if (!commentTextarea) return;
            if (fontSelector) commentTextarea.style.fontFamily = fontSelector.value;
            if (sizeSelector) commentTextarea.style.fontSize = sizeSelector.value + 'pt';
            if (boldCheckbox) commentTextarea.style.fontWeight = boldCheckbox.checked ? 'bold' : 'normal';
            if (italicCheckbox) commentTextarea.style.fontStyle = italicCheckbox.checked ? 'italic' : 'normal';
        }

        if (markInput) markInput.addEventListener('input', updateCalculation);
        if (maxMarkInput) maxMarkInput.addEventListener('input', updateCalculation);
        if (resultStatusInput) resultStatusInput.addEventListener('change', updateCalculation);
        if (fontSelector) fontSelector.addEventListener('change', updateFontStyles);
        if (sizeSelector) sizeSelector.addEventListener('change', updateFontStyles);
        if (boldCheckbox) boldCheckbox.addEventListener('change', updateFontStyles);
        if (italicCheckbox) italicCheckbox.addEventListener('change', updateFontStyles);

        // AJAX Form Submission for Draft / Complete
        async function submitEvaluation(actionType) {
            if (!form) return;
            actionField.value = actionType;

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const result = await response.json();
                if (result.success) {
                    if (statusBadge) {
                        if (actionType === 'mark_complete') {
                            statusBadge.textContent = '✓ Complete';
                            statusBadge.className = 'status-badge px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
                        } else {
                            statusBadge.textContent = 'Draft';
                            statusBadge.className = 'status-badge px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30';
                        }
                    }

                    if (lastSavedText) {
                        lastSavedText.textContent = actionType === 'mark_complete' ? 'Marked complete just now' : 'Draft saved just now';
                    }
                } else {
                    alert(result.message || 'Failed to save evaluation.');
                }
            } catch (err) {
                form.submit(); // fallback to standard form post
            }
        }

        if (btnSaveDraft) btnSaveDraft.addEventListener('click', () => submitEvaluation('save_draft'));
        if (btnMarkComplete) btnMarkComplete.addEventListener('click', () => submitEvaluation('mark_complete'));
    });
});
</script>
@endsection
