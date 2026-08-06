<div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
    <div class="p-4 border-b border-slate-800">
        <h3 class="text-sm font-semibold text-slate-50">Leave Records</h3>
    </div>
    @php $leaves = $employee->leaves; @endphp
    @if($leaves->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">From</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">To</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Days</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($leaves as $leave)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-xs text-slate-100 capitalize">{{ str_replace('_', ' ', $leave->leave_type) }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $leave->start_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $leave->end_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $leave->start_date->diffInDays($leave->end_date) + 1 }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $leave->status === 'approved' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $leave->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $leave->status === 'rejected' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $leave->status === 'cancelled' ? 'bg-slate-500/20 text-slate-400' : '' }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.leaves.show', $leave) }}" class="text-emerald-400 hover:text-emerald-300">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-8 text-center">
            <p class="text-sm text-slate-400">No leave records found.</p>
            <a href="{{ route('admin.leaves.create') }}" class="text-xs text-emerald-400 hover:text-emerald-300 mt-2 inline-block">Apply for Leave</a>
        </div>
    @endif
</div>
