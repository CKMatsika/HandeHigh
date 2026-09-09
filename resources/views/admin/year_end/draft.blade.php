@extends('layouts.app')

@section('title', "Transition Draft: {$process->source_academic_year} → {$process->target_academic_year}")

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.year-end.index') }}" class="hover:text-slate-200">Year-End Processes</a>
                <span>/</span>
                <span class="text-slate-200 font-medium">Transition Draft #{{ str_pad($process->id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-3">
                <span>Transition Plan: {{ $process->source_academic_year }} → {{ $process->target_academic_year }}</span>
                @if($process->status === 'draft')
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                        Draft (Pending Approval)
                    </span>
                @elseif($process->status === 'executed')
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Executed on {{ $process->executed_at ? $process->executed_at->format('d M Y') : '—' }}
                    </span>
                @endif
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.year-end.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                ← Back to Dashboard
            </a>
            @if($process->status === 'draft')
                <button type="button" 
                        onclick="document.getElementById('approveExecuteModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-lg shadow-emerald-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Approve & Execute Transition</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Statistics Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-xs font-medium uppercase text-slate-400">Total Students</span>
            <div class="text-xl font-bold font-mono text-slate-100 mt-1">{{ count($process->draft_payload ?? []) }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/20 p-4">
            <span class="text-xs font-medium uppercase text-emerald-400">To Be Promoted</span>
            <div class="text-xl font-bold font-mono text-emerald-300 mt-1">{{ $process->summary['promotions_count'] ?? 0 }}</div>
        </div>
        <div class="rounded-2xl border border-purple-500/20 bg-purple-950/20 p-4">
            <span class="text-xs font-medium uppercase text-purple-400">To Graduation Clearance</span>
            <div class="text-xl font-bold font-mono text-purple-300 mt-1">{{ $process->summary['clearance_count'] ?? 0 }}</div>
        </div>
        <div class="rounded-2xl border border-amber-500/20 bg-amber-950/20 p-4">
            <span class="text-xs font-medium uppercase text-amber-400">Retentions / Repeats</span>
            <div class="text-xl font-bold font-mono text-amber-300 mt-1">{{ $process->summary['retentions_count'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 shadow-md">
        <form method="GET" action="{{ route('admin.year-end.draft', $process) }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search student name or admission number..." 
                       class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-100 text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none">
            </div>
            <div>
                <select name="grade" onchange="this.form.submit()" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                    <option value="">All Current Grades</option>
                    @foreach($distinctGrades as $g)
                        <option value="{{ $g }}" {{ request('grade') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="action" onchange="this.form.submit()" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                    <option value="">All Proposed Actions</option>
                    <option value="promote" {{ request('action') == 'promote' ? 'selected' : '' }}>Promote</option>
                    <option value="move_to_clearance" {{ request('action') == 'move_to_clearance' ? 'selected' : '' }}>Move to Clearance</option>
                    <option value="repeat" {{ request('action') == 'repeat' ? 'selected' : '' }}>Repeat / Retain</option>
                    <option value="transfer_out" {{ request('action') == 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                </select>
            </div>
            @if(request('search') || request('grade') || request('action'))
                <a href="{{ route('admin.year-end.draft', $process) }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 text-xs transition">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Students Batch Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100">Proposed Student Movements</h3>
                <p class="text-xs text-slate-400 mt-0.5">Showing {{ count($filteredItems) }} students in current filter</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Admission #</th>
                        <th class="px-3 py-2.5 text-left font-medium">Student Name</th>
                        <th class="px-3 py-2.5 text-left font-medium">Current Grade & Class</th>
                        <th class="px-3 py-2.5 text-center font-medium">Proposed Action</th>
                        <th class="px-3 py-2.5 text-left font-medium">Target Grade & Class</th>
                        <th class="px-3 py-2.5 text-center font-medium">Clearance Preview</th>
                        @if($process->status === 'draft')
                            <th class="px-3 py-2.5 text-right font-medium">Adjust Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($filteredItems as $item)
                        @php
                            $actionMap = [
                                'promote' => ['color' => 'emerald', 'label' => 'Promote to Next Grade'],
                                'repeat' => ['color' => 'amber', 'label' => 'Repeat / Retain'],
                                'retain' => ['color' => 'amber', 'label' => 'Repeat / Retain'],
                                'move_to_clearance' => ['color' => 'purple', 'label' => 'Move to Clearance Hub'],
                                'transfer_out' => ['color' => 'orange', 'label' => 'Transfer Out'],
                                'exit_now' => ['color' => 'rose', 'label' => 'Direct Exit'],
                            ];
                            $act = $actionMap[$item['proposed_action']] ?? ['color' => 'slate', 'label' => ucfirst($item['proposed_action'])];
                            $cl = $item['clearance_preview'] ?? null;
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5 font-mono text-slate-300">
                                {{ $item['admission_number'] }}
                            </td>
                            <td class="px-3 py-2.5 font-medium text-slate-100">
                                {{ $item['name'] }}
                                @if(!empty($item['is_boarding']))
                                    <span class="ml-1 px-1.5 py-0.2 text-[9px] rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">Boarder</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-300">
                                <span class="font-semibold">{{ $item['current_grade'] }}</span>
                                <span class="text-slate-500">({{ $item['current_class_name'] ?: 'No Class' }})</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full bg-{{ $act['color'] }}-500/20 text-{{ $act['color'] }}-400 border border-{{ $act['color'] }}-500/30">
                                    {{ $act['label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-slate-200">
                                @if($item['proposed_action'] === 'promote')
                                    <span class="font-semibold text-emerald-400">{{ $item['target_grade'] }}</span>
                                    <span class="text-slate-400">({{ $item['target_class_name'] }})</span>
                                @elseif($item['proposed_action'] === 'move_to_clearance')
                                    <span class="text-purple-400 italic">Graduation Clearance Queue</span>
                                @elseif($item['proposed_action'] === 'repeat')
                                    <span class="text-amber-400">{{ $item['current_grade'] }} (Retained)</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                @if($cl)
                                    <div class="inline-flex items-center gap-1.5 text-[10px]">
                                        <span class="px-1.5 py-0.5 rounded {{ $cl['finance_status'] === 'cleared' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}" title="Finance Balance: ${{ number_format($cl['finance_balance'], 2) }}">
                                            Fee: {{ $cl['finance_status'] === 'cleared' ? '✓' : '$' . number_format($cl['finance_balance'], 0) }}
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded {{ $cl['library_status'] === 'cleared' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}" title="Unreturned Books: {{ $cl['unreturned_books_count'] }}">
                                            Lib: {{ $cl['library_status'] === 'cleared' ? '✓' : $cl['unreturned_books_count'] . ' unreturned' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            @if($process->status === 'draft')
                                <td class="px-3 py-2.5 text-right">
                                    <button type="button" 
                                            onclick="openEditStudentModal({{ json_encode($item) }})"
                                            class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 text-[11px] font-medium transition">
                                        Customize
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No students found matching current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Customize Individual Student Movement -->
<div id="editStudentModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <h4 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Customize Student Transition
            </h4>
            <button type="button" onclick="closeEditStudentModal()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.year-end.draft.update-student', $process) }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="student_id" id="edit_student_id">

            <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                <p class="text-xs font-semibold text-slate-200" id="edit_student_name"></p>
                <p class="text-[11px] text-slate-400" id="edit_student_grade"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Proposed Transition Action</label>
                <select name="proposed_action" id="edit_proposed_action" onchange="handleActionChange(this.value)" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                    <option value="promote">Promote to Next Grade</option>
                    <option value="repeat">Repeat / Retain in Current Grade</option>
                    <option value="move_to_clearance">Move to Graduation Clearance Hub</option>
                    <option value="transfer_out">Transfer Out of School</option>
                </select>
            </div>

            <div id="targetClassGroup" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Grade</label>
                    <input type="text" name="target_grade" id="edit_target_grade" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Class Name</label>
                    <input type="text" name="target_class_name" id="edit_target_class_name" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditStudentModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold transition">
                    Save Preference
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Approve & Execute Transition Batch -->
<div id="approveExecuteModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <h4 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Confirm Year-End Execution
            </h4>
            <button type="button" onclick="document.getElementById('approveExecuteModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.year-end.draft.execute', $process) }}" class="p-5 space-y-4">
            @csrf
            <div class="p-4 bg-emerald-950/30 border border-emerald-500/20 rounded-xl space-y-2 text-xs text-slate-200">
                <p class="font-bold text-emerald-300 text-sm">Ready to execute batch transition?</p>
                <p>Executing will:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-300">
                    <li>Promote <strong>{{ $process->summary['promotions_count'] ?? 0 }}</strong> students to their next classes for <strong>{{ $process->target_academic_year }}</strong>.</li>
                    <li>Move <strong>{{ $process->summary['clearance_count'] ?? 0 }}</strong> graduating students (Form 4s, Form 6s, Grade 7s) to the <strong>Graduation Clearance Center</strong>.</li>
                    <li>Create new active enrollments for academic year <strong>{{ $process->target_academic_year }}</strong>.</li>
                </ul>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('approveExecuteModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                    Yes, Approve & Execute
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditStudentModal(item) {
        document.getElementById('edit_student_id').value = item.student_id;
        document.getElementById('edit_student_name').innerText = item.name + ' (' + item.admission_number + ')';
        document.getElementById('edit_student_grade').innerText = 'Current: ' + item.current_grade + ' ' + (item.current_class_name || '');
        document.getElementById('edit_proposed_action').value = item.proposed_action;
        document.getElementById('edit_target_grade').value = item.target_grade || item.current_grade;
        document.getElementById('edit_target_class_name').value = item.target_class_name || item.current_class_name;

        handleActionChange(item.proposed_action);

        const modal = document.getElementById('editStudentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditStudentModal() {
        const modal = document.getElementById('editStudentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function handleActionChange(val) {
        const group = document.getElementById('targetClassGroup');
        if (val === 'move_to_clearance' || val === 'transfer_out') {
            group.style.display = 'none';
        } else {
            group.style.display = 'block';
        }
    }
</script>
@endsection
