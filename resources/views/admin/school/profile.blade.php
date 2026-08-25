@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-100 flex items-center gap-2">
                <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                School Profile & Document Branding
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Configure your school identity, official contact details, branding colors, and banking instructions that automatically propagate to all printed documents, reports, and invoices.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Active Tenant: {{ $school->name }} ({{ $school->code }})
            </span>
        </div>
    </div>

    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-800 text-emerald-200 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-800 text-rose-200 text-sm">
            <div class="font-semibold mb-1">Please correct the errors below:</div>
            <ul class="list-disc list-inside text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.school.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Live Document Preview -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Live Document Identity Preview (Print & PDF Appearance)
                </div>
                <span class="text-[10px] text-slate-500">Auto-generated header and footer preview</span>
            </div>

            <div class="p-6 rounded-xl border border-slate-700 bg-slate-950/80 shadow-2xl space-y-6">
                <x-documents.school-header 
                    :school="$school" 
                    title="Official School Statement / Invoice" 
                    subtitle="Document Identity Preview"
                    reference="DOC-2026-PREVIEW"
                    :date="now()"
                    status="Verified"
                    statusClass="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                />

                <div class="py-4 text-center text-xs text-slate-500 border-y border-dashed border-slate-800">
                    [ Document Body Content: Tables, Financial Data, Timetable Grids, or Student Records ]
                </div>

                <x-documents.school-footer 
                    :school="$school" 
                    :showBanking="true"
                    customNote="Preview of the standard enterprise footer with banking and payment instructions."
                />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Identity & Administration -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. School Identity -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                        1. School Identity & Registration
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="md:col-span-2">
                            <label class="block font-medium text-slate-300 mb-1">Official School Name *</label>
                            <input type="text" name="name" value="{{ old('name', $school->name) }}" required
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Display / Short Name</label>
                            <input type="text" name="display_name" value="{{ old('display_name', $school->display_name) }}"
                                   placeholder="e.g. Hande High"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">School Motto</label>
                            <input type="text" name="motto" value="{{ old('motto', $school->motto) }}"
                                   placeholder="e.g. Excellence Through Dedication"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">School Ministry Registration No.</label>
                            <input type="text" name="registration_number" value="{{ old('registration_number', $school->registration_number) }}"
                                   placeholder="e.g. MOE/REG/2026/042"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">ZIMSEC / Exam Centre Number</label>
                            <input type="text" name="zimsec_center_number" value="{{ old('zimsec_center_number', $school->zimsec_center_number) }}"
                                   placeholder="e.g. ZW-6042"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Established Year</label>
                            <input type="number" name="established_year" value="{{ old('established_year', $school->established_year) }}"
                                   placeholder="e.g. 1985"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">School Type</label>
                            <input type="text" name="school_type" value="{{ old('school_type', $school->school_type) }}"
                                   placeholder="e.g. High School / Secondary / Boarding"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- 2. Contacts & Addresses -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        2. Contact Information & Addresses
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Official Email *</label>
                            <input type="email" name="email" value="{{ old('email', $school->email) }}" required
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Official Website</label>
                            <input type="text" name="website" value="{{ old('website', $school->website) }}"
                                   placeholder="e.g. www.handehigh.ac.zw"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Primary Telephone (Landline)</label>
                            <input type="text" name="telephone" value="{{ old('telephone', $school->telephone ?: $school->phone) }}"
                                   placeholder="+263 242 123456"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Mobile Contact</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $school->mobile) }}"
                                   placeholder="+263 77 123 4567"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">WhatsApp Line</label>
                            <input type="text" name="whatsapp" value="{{ old('whatsapp', $school->whatsapp) }}"
                                   placeholder="+263 77 123 4567"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">City / Town</label>
                            <input type="text" name="city" value="{{ old('city', $school->city) }}"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block font-medium text-slate-300 mb-1">Physical Address</label>
                            <input type="text" name="address" value="{{ old('address', $school->address) }}"
                                   placeholder="e.g. Stand 452, Educational Way, Hande"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block font-medium text-slate-300 mb-1">Postal Address</label>
                            <input type="text" name="postal_address" value="{{ old('postal_address', $school->postal_address) }}"
                                   placeholder="e.g. P.O. Box 1024, Hande"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- 3. Key Administration -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                        3. Key Administration Personnel
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Principal / Headmaster</label>
                            <input type="text" name="principal_name" value="{{ old('principal_name', $school->principal_name) }}"
                                   placeholder="e.g. Dr. T. Moyo"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Bursar</label>
                            <input type="text" name="bursar_name" value="{{ old('bursar_name', $school->bursar_name) }}"
                                   placeholder="e.g. Mrs. C. Ndlovu"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Administrator</label>
                            <input type="text" name="administrator_name" value="{{ old('administrator_name', $school->administrator_name) }}"
                                   placeholder="e.g. Mr. K. Sibanda"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Logo, Colors & Banking -->
            <div class="space-y-6">
                <!-- 4. Logo Management -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        School Logo
                    </h2>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center gap-4">
                            @if($branding['logo_url'])
                                <div class="relative group">
                                    <img src="{{ $branding['logo_url'] }}" alt="{{ $school->name }}" class="h-20 w-20 rounded-xl object-contain border border-slate-700 bg-slate-950 p-1 shadow-lg">
                                </div>
                            @else
                                <div class="h-20 w-20 rounded-xl bg-slate-950 border border-dashed border-slate-700 flex items-center justify-center text-slate-600 text-center text-[10px] p-2">
                                    No Logo Uploaded
                                </div>
                            @endif

                            <div class="space-y-1">
                                <div class="font-medium text-slate-200">Official Brand Mark</div>
                                <p class="text-[11px] text-slate-400">
                                    Accepted: PNG, JPEG, WEBP.<br>Max file size: 2MB.<br>SVGs are blocked for security.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/webp"
                                   class="block w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 cursor-pointer">
                            
                            @if($school->logo)
                                <label class="inline-flex items-center gap-2 text-rose-400 cursor-pointer pt-1">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-700 text-rose-500 focus:ring-rose-500">
                                    <span>Remove current logo</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 5. Visual Colors & Footer -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                        Visual Colors & Footer Note
                    </h2>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Primary Color</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="primary_color" value="{{ old('primary_color', $school->primary_color ?: '#1e3a8a') }}"
                                           class="h-9 w-9 rounded-lg border-0 bg-transparent cursor-pointer">
                                    <input type="text" readonly value="{{ old('primary_color', $school->primary_color ?: '#1e3a8a') }}"
                                           class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-2.5 py-1.5 text-xs text-slate-300 font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Secondary Color</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="secondary_color" value="{{ old('secondary_color', $school->secondary_color ?: '#d97706') }}"
                                           class="h-9 w-9 rounded-lg border-0 bg-transparent cursor-pointer">
                                    <input type="text" readonly value="{{ old('secondary_color', $school->secondary_color ?: '#d97706') }}"
                                           class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-2.5 py-1.5 text-xs text-slate-300 font-mono">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Document Footer Text / Motto</label>
                            <textarea name="footer_text" rows="2"
                                      placeholder="e.g. Hande High School — Striving for excellence in character and scholarship."
                                      class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">{{ old('footer_text', $school->footer_text) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 6. Banking & Payment Details -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-100 flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Official Banking & Invoicing Details
                    </h2>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $school->bank_name) }}"
                                   placeholder="e.g. Stanbic Bank Zimbabwe"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Account Name</label>
                            <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $school->bank_account_name) }}"
                                   placeholder="e.g. Hande High School Operating Account"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $school->bank_account_number) }}"
                                   placeholder="e.g. 9140001234567"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 font-mono focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Branch Name</label>
                            <input type="text" name="bank_branch" value="{{ old('bank_branch', $school->bank_branch) }}"
                                   placeholder="e.g. Harare Main Branch"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Payment Instructions</label>
                            <textarea name="payment_instructions" rows="2"
                                      placeholder="e.g. Please quote student admission number on all bank deposits and proof of payment slips."
                                      class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 focus:border-emerald-500 focus:outline-none">{{ old('payment_instructions', $school->payment_instructions) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-emerald-500 transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save School Profile & Branding
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
