@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-50">Student Career Interests</h1>
        <p class="text-xs text-slate-400 mt-1">Careers that students have expressed interest in.</p>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Desired Career</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Matched Path</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Hobbies</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($interests as $interest)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-50">{{ $interest->student?->first_name }} {{ $interest->student?->last_name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $interest->student?->grade ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-indigo-500/20 text-indigo-400">{{ $interest->desired_career ?? 'Not specified' }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $interest->careerPath?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $interest->hobbies ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-400 max-w-xs truncate">{{ $interest->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400"><p class="text-sm">No student interests recorded yet.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($interests->hasPages())<div class="px-4 py-3 border-t border-slate-800">{{ $interests->links() }}</div>@endif
    </div>
</div>
@endsection
