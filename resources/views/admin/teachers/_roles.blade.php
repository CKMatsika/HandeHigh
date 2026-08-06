<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-50">Current Roles & Positions</h3>
            <button onclick="document.getElementById('addRoleForm').classList.toggle('hidden')" class="text-xs text-emerald-400 hover:text-emerald-300">+ Assign Role</button>
        </div>

        <form id="addRoleForm" method="POST" action="{{ route('admin.teachers.roles.store', $teacher) }}" class="hidden mb-4 p-3 rounded-lg border border-slate-700 bg-slate-800/50 space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-slate-400 mb-1">Position *</label>
                <select name="staff_position_id" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" required>
                    <option value="">Select Position</option>
                    @foreach($availablePositions as $pos)
                        <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Notes</label>
                <input type="text" name="notes" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-50 text-xs" placeholder="e.g. Head of Science Department">
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded">Assign Role</button>
        </form>

        @php
            $activeAssignments = $teacher->activePositionAssignments;
        @endphp

        @if($activeAssignments->isNotEmpty())
            <div class="space-y-2">
                @foreach($activeAssignments as $assignment)
                    <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-800/50">
                        <div>
                            <p class="text-sm text-slate-100">{{ $assignment->position->name }}</p>
                            @if($assignment->target)
                                <p class="text-[10px] text-slate-400">
                                    {{ class_basename($assignment->target_type) }}: 
                                    {{ $assignment->target->name ?? $assignment->target->title ?? 'N/A' }}
                                </p>
                            @endif
                            @if($assignment->notes)
                                <p class="text-[10px] text-slate-500">{{ $assignment->notes }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.teachers.roles.destroy', [$teacher, $assignment]) }}" onsubmit="return confirm('Remove this role?')">
                            @csrf
                            <button type="submit" class="text-xs text-red-400 hover:text-red-300">Remove</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-400">No roles assigned.</p>
        @endif
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-4">Available Positions</h3>
        @if($availablePositions->isNotEmpty())
            <div class="space-y-2">
                @foreach($availablePositions as $pos)
                    <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-800/30">
                        <div>
                            <p class="text-xs text-slate-100">{{ $pos->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $pos->category }} · {{ $pos->description ?? '' }}</p>
                        </div>
                        <span class="text-[10px] text-slate-500">{{ $activeAssignments->where('staff_position_id', $pos->id)->count() }} assigned</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6">
                <p class="text-xs text-slate-400 mb-3">No positions defined yet.</p>
                <a href="#" onclick="alert('Create positions in the HR settings.')" class="text-xs text-emerald-400 hover:text-emerald-300">Create Positions</a>
            </div>
        @endif
    </div>
</div>
