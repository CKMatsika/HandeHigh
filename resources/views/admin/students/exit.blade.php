@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.students.show', $student) }}" class="text-slate-400 hover:text-slate-200 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Exit / Graduate — {{ $student->full_name }}</h1>
            <p class="text-slate-400 mt-1">Process student exit from the school</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <form method="POST" action="{{ route('admin.students.exit', $student) }}">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Exit Type *</label>
                            <select name="exit_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                                <option value="graduated">Graduated (Completed studies)</option>
                                <option value="withdrawn">Withdrawn (Pulled out by parent)</option>
                                <option value="expelled">Expelled (Disciplinary)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Exit Date *</label>
                            <input type="date" name="exit_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Remarks</label>
                        <textarea name="exit_remarks" rows="4" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm"
                            placeholder="Any additional remarks about the student's exit..."></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition" onclick="return confirm('This will permanently mark the student as exited. Continue?')">
                            Process Exit
                        </button>
                        <a href="{{ route('admin.students.show', $student) }}" class="text-slate-400 hover:text-slate-200 text-sm transition">Cancel</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-300 mb-4">Student Details</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Name</span><span class="text-slate-100 text-sm">{{ $student->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Grade</span><span class="text-slate-100 text-sm">{{ $student->grade ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Class</span><span class="text-slate-100 text-sm">{{ $student->class_name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Admission #</span><span class="text-slate-100 text-sm">{{ $student->admission_number ?? '—' }}</span></div>
                @if($student->isGraduating())
                    <div class="mt-3 p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">
                        <p class="text-blue-400 text-xs font-medium">🎓 This student is in a graduating grade ({{ $student->grade }})</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
