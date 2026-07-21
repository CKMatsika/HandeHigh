@extends('layouts.app')

@section('content')
    <h1 class="text-lg font-semibold text-slate-50 mb-4">New Enrollment</h1>

    <form method="POST" action="{{ route('admin.enrollments.store') }}" class="space-y-6 text-xs text-slate-100">
        @csrf

        @if ($errors->any())
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
                <ul class="list-disc ml-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 space-y-3">
                <h2 class="text-sm font-semibold text-slate-100 mb-1">Guardian details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_first_name">First name</label>
                        <input id="guardian_first_name" type="text" name="guardian_first_name" value="{{ old('guardian_first_name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_last_name">Last name</label>
                        <input id="guardian_last_name" type="text" name="guardian_last_name" value="{{ old('guardian_last_name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_email">Email</label>
                        <input id="guardian_email" type="email" name="guardian_email" value="{{ old('guardian_email') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_phone">Phone</label>
                        <input id="guardian_phone" type="text" name="guardian_phone" value="{{ old('guardian_phone') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_address">Address</label>
                    <input id="guardian_address" type="text" name="guardian_address" value="{{ old('guardian_address') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_city">City</label>
                        <input id="guardian_city" type="text" name="guardian_city" value="{{ old('guardian_city') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="guardian_country">Country</label>
                        <input id="guardian_country" type="text" name="guardian_country" value="{{ old('guardian_country') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="relationship">Relationship to student</label>
                    <input id="relationship" type="text" name="relationship" value="{{ old('relationship', 'Parent') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 space-y-3">
                <h2 class="text-sm font-semibold text-slate-100 mb-1">Student details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="student_first_name">First name</label>
                        <input id="student_first_name" type="text" name="student_first_name" value="{{ old('student_first_name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="student_last_name">Last name</label>
                        <input id="student_last_name" type="text" name="student_last_name" value="{{ old('student_last_name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="student_gender">Gender</label>
                        <input id="student_gender" type="text" name="student_gender" value="{{ old('student_gender') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="Male / Female">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="student_date_of_birth">Date of birth</label>
                        <input id="student_date_of_birth" type="date" name="student_date_of_birth" value="{{ old('student_date_of_birth') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="grade">Grade / Form</label>
                        <input id="grade" type="text" name="grade" value="{{ old('grade') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. Grade 7 or Form 2">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium mb-1 text-slate-300" for="class_name">Class name</label>
                        <input id="class_name" type="text" name="class_name" value="{{ old('class_name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. A, B">
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-2">
                    <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                        <input type="checkbox" name="is_boarding" value="1" {{ old('is_boarding') ? 'checked' : '' }} class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500">
                        <span>Boarding</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                        <input type="checkbox" name="has_transport" value="1" {{ old('has_transport') ? 'checked' : '' }} class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500">
                        <span>School transport</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 space-y-3">
            <h2 class="text-sm font-semibold text-slate-100 mb-1">Enrollment & fees context</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="academic_year">Academic year</label>
                    <input id="academic_year" type="text" name="academic_year" value="{{ old('academic_year', $defaultAcademicYear) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs" placeholder="e.g. 2025 or 2025/2026">
                </div>
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="term">Term</label>
                    <select id="term" name="term" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                        @foreach($terms as $term)
                            <option value="{{ $term }}" {{ old('term') === $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium mb-1 text-slate-300" for="enrollment_date">Enrollment date</label>
                    <input id="enrollment_date" type="date" name="enrollment_date" value="{{ old('enrollment_date', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
                </div>
            </div>

            <p class="mt-2 text-[11px] text-slate-400">
                When you submit this form, the system will automatically build an invoice based on your configured fee structure for this school, academic year, term, grade, and selected services (boarding, transport) and post the relevant ledger entries.
            </p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                Enroll student & generate invoice
            </button>
        </div>
    </form>
@endsection
