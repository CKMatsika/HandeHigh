@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 max-w-2xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ url()->previous() }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Back</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-xl font-bold tracking-tight text-slate-50">Request ICT Support</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">Submit an issue directly to the school ICT Coordinator and technical desk.</p>
        </div>
        <a href="{{ route('ict.tickets.my-tickets') }}" class="text-xs text-cyan-400 hover:underline">
            View My Requests &rarr;
        </a>
    </div>

    <!-- Ticket Form Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl">
        <form method="POST" action="{{ route('ict.tickets.store') }}" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block font-semibold text-slate-300 mb-1.5">Subject / Brief Summary *</label>
                <input type="text" name="title" required value="{{ old('title') }}"
                       placeholder="e.g. Need assistance with Form 2 Mathematics class allocation on ERP"
                       class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3.5 py-2.5 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                @error('title')
                    <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-300 mb-1.5">Category *</label>
                    <select name="category" required
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3.5 py-2.5 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                        <option value="erp_support" {{ old('category') === 'erp_support' ? 'selected' : '' }}>School ERP System Support</option>
                        <option value="login_account" {{ old('category') === 'login_account' ? 'selected' : '' }}>Login & Account Access</option>
                        <option value="hardware_lab" {{ old('category') === 'hardware_lab' ? 'selected' : '' }}>Computer Lab & Workstations</option>
                        <option value="printer_scanner" {{ old('category') === 'printer_scanner' ? 'selected' : '' }}>Printer & Photocopier</option>
                        <option value="network_wifi" {{ old('category') === 'network_wifi' ? 'selected' : '' }}>School Network & Wi-Fi</option>
                        <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other Technical Support</option>
                    </select>
                    @error('category')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1.5">Priority Level *</label>
                    <select name="priority" required
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3.5 py-2.5 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">
                        <option value="medium" selected>Medium (Standard request)</option>
                        <option value="high">High (Impacting teaching/classes today)</option>
                        <option value="urgent">Urgent (System offline / Exam room blocked)</option>
                        <option value="low">Low (Non-urgent enhancement)</option>
                    </select>
                    @error('priority')
                        <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-300 mb-1.5">Detailed Description of the Issue *</label>
                <textarea name="description" rows="5" required
                          placeholder="Please provide details (e.g. what class, student, or lab PC number is affected, what error is displayed, or what you are trying to accomplish)..."
                          class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3.5 py-2.5 text-xs text-slate-100 focus:border-cyan-500 focus:outline-none">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-rose-400 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl bg-cyan-950/30 border border-cyan-500/20 p-3.5 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-cyan-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-[11px] text-cyan-200/90 leading-relaxed">
                    This request will be routed directly to the ICT Coordinator and technical desk. You will be able to track resolution status under <strong>My Support Requests</strong>.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('teacher.dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold shadow-lg shadow-cyan-600/20 transition">
                    Submit Support Ticket
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
