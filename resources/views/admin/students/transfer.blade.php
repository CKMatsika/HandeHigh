@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.students.show', $student) }}" class="text-slate-400 hover:text-slate-200 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Transfer Out — {{ $student->full_name }}</h1>
            <p class="text-slate-400 mt-1">Process student transfer to another school</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <form method="POST" action="{{ route('admin.students.transfer', $student) }}">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Destination School *</label>
                            <input type="text" name="destination_school" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Transfer Date *</label>
                            <input type="date" name="transfer_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Destination Address</label>
                            <input type="text" name="destination_address" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Destination Contact</label>
                            <input type="text" name="destination_contact" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Academic Year *</label>
                            <input type="text" name="academic_year" value="{{ date('Y') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 mb-1 block">Term *</label>
                            <select name="term" required class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                                <option value="1">Term 1</option>
                                <option value="2">Term 2</option>
                                <option value="3">Term 3</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Reason for Transfer</label>
                        <input type="text" name="reason" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Academic Remarks</label>
                        <textarea name="academic_remarks" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-1 block">Conduct Remarks</label>
                        <textarea name="conduct_remarks" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition" onclick="return confirm('This will mark the student as TRANSFERRED. Continue?')">
                            Process Transfer
                        </button>
                        <a href="{{ route('admin.students.show', $student) }}" class="text-slate-400 hover:text-slate-200 text-sm transition">Cancel</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Student Info -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-300 mb-4">Student Details</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Name</span><span class="text-slate-100 text-sm">{{ $student->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Grade</span><span class="text-slate-100 text-sm">{{ $student->grade ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Class</span><span class="text-slate-100 text-sm">{{ $student->class_name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Admission #</span><span class="text-slate-100 text-sm">{{ $student->admission_number ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400 text-sm">Status</span><span class="text-slate-100 text-sm">{{ ucfirst($student->status) }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
