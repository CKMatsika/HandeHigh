@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
            body { background: white !important; color: black !important; font-size: 11px !important; }
            .print-card { border: 1px solid #334155 !important; background: white !important; color: black !important; box-shadow: none !important; border-radius: 0 !important; }
            .print-table th, .print-table td { border-color: #cbd5e1 !important; color: black !important; padding: 4px 6px !important; }
            .print-table th { background: #f1f5f9 !important; font-weight: bold !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .print-text-dark { color: black !important; }
        }
    </style>

    <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.fees.index') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Fee Structure</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Official Schedule</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Official Approved Fee Schedule</h1>
            <p class="text-xs text-slate-400 mt-0.5">MOPSE Authorised Votes schedule & Committee Signatures for Academic Year {{ $academicYear }}{{ $term ? ' · ' . $term : '' }}</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Schedule
            </button>
            <a href="{{ route('admin.fees.index', ['academic_year' => $academicYear, 'term' => $term]) }}" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-800 transition">
                Back to Tariffs
            </a>
        </div>
    </div>

    <div class="print-card rounded-2xl border border-slate-800 bg-slate-900/95 p-6 md:p-8 shadow-2xl space-y-6 text-slate-100">
        
        <!-- Official Crest Header -->
        <div class="border-b-2 border-slate-700 pb-5 text-center space-y-1">
            <div class="text-[12px] font-bold tracking-widest uppercase text-amber-400">Buhera Rural District Council</div>
            <div class="text-2xl font-black uppercase tracking-wider text-slate-100">Hande Secondary School</div>
            <div class="text-[11px] text-slate-400">P.O. Box 90, Buhera · Manicaland, Zimbabwe · Official MOPSE Centre</div>
            <div class="inline-block mt-2 px-4 py-1 rounded-full bg-emerald-950/80 border border-emerald-700/60 text-emerald-300 font-bold uppercase tracking-wider text-xs">
                Official Approved School Fee Schedule & Vote Allocation Breakdown
            </div>
            <div class="text-xs text-slate-400 mt-2 font-mono">
                Academic Year: <span class="text-slate-200 font-bold">{{ $academicYear }}</span> · Term: <span class="text-slate-200 font-bold">{{ $term ?: 'All Terms' }}</span> · Reference: <span class="text-slate-200 font-bold">FEE-VOTE-{{ $academicYear }}</span>
            </div>
        </div>

        <!-- 1. OFFICIAL VOTE SCHEDULE CARDS (IMAGE 1 SPECIFICATION) -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                1. MOPSE Approved Fee Schedules & Sub-Vote Allocations
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Boarding Student Package Card -->
                <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div>
                            <span class="text-xs font-bold uppercase text-indigo-400">Boarding Student Package</span>
                            <div class="text-[10px] text-slate-400">Inclusive of 1 Science Subject</div>
                        </div>
                        <div class="text-lg font-black font-mono text-emerald-400">$470.00</div>
                    </div>

                    <table class="w-full text-xs print-table">
                        <thead class="text-[10px] text-slate-400 uppercase border-b border-slate-800">
                            <tr>
                                <th class="text-left py-1.5 font-semibold">Sub-Vote Category</th>
                                <th class="text-right py-1.5 font-semibold">Tariff ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-200">
                            @if($boardingParent && $boardingParent->subVotes->count())
                                @foreach($boardingParent->subVotes as $bv)
                                    <tr>
                                        <td class="py-1.5 text-slate-300">{{ $bv->label }}</td>
                                        <td class="py-1.5 text-right font-mono font-medium">${{ number_format($bv->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="py-1.5 text-slate-300">Boarding Fees</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$380.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Tuition</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$20.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Building Fund</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$15.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">General Purpose Fund</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$10.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Sports</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$10.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">ICT Levy</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$15.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Science Levy (Per Subject)</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$20.00</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="border-t-2 border-slate-700 font-bold">
                            <tr>
                                <td class="py-2 text-slate-100 uppercase text-[11px]">Total Boarding Package:</td>
                                <td class="py-2 text-right font-mono text-emerald-400 text-sm">$470.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Day Scholar Student Package Card -->
                <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div>
                            <span class="text-xs font-bold uppercase text-teal-400">Day-Scholar Student Package</span>
                            <div class="text-[10px] text-slate-400">Inclusive of Lunch & 1 Science Subject</div>
                        </div>
                        <div class="text-lg font-black font-mono text-emerald-400">$135.00</div>
                    </div>

                    <table class="w-full text-xs print-table">
                        <thead class="text-[10px] text-slate-400 uppercase border-b border-slate-800">
                            <tr>
                                <th class="text-left py-1.5 font-semibold">Sub-Vote Category</th>
                                <th class="text-right py-1.5 font-semibold">Tariff ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-200">
                            @if($dayParent && $dayParent->subVotes->count())
                                @foreach($dayParent->subVotes as $dv)
                                    <tr>
                                        <td class="py-1.5 text-slate-300">{{ $dv->label }}</td>
                                        <td class="py-1.5 text-right font-mono font-medium">${{ number_format($dv->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="py-1.5 text-slate-300">Food (Lunch Monday - Friday)</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$45.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Tuition</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$20.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Building Fund</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$15.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">General Purpose Fund</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$10.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Sports</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$10.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">ICT Levy</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$15.00</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-slate-300">Science Levy (Per Subject)</td>
                                    <td class="py-1.5 text-right font-mono font-medium">$20.00</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="border-t-2 border-slate-700 font-bold">
                            <tr>
                                <td class="py-2 text-slate-100 uppercase text-[11px]">Total Day-Scholar Package:</td>
                                <td class="py-2 text-right font-mono text-emerald-400 text-sm">$135.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. FULL ITEMIZED TARIFF REGISTER -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                2. Complete Master Tariffs Register
            </h2>

            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-xs print-table">
                    <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="text-left py-2 px-3 font-semibold">Grade / Form</th>
                            <th class="text-left py-2 px-3 font-semibold">Category</th>
                            <th class="text-left py-2 px-3 font-semibold">Vote Code</th>
                            <th class="text-left py-2 px-3 font-semibold">Fee Description</th>
                            <th class="text-right py-2 px-3 font-semibold">Tariff ($)</th>
                            <th class="text-center py-2 px-2 font-semibold">Priority</th>
                            <th class="text-left py-2 px-3 font-semibold">Classification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @foreach($feeStructures as $fee)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-2 px-3 font-medium text-slate-200">{{ $fee->grade ?: 'All Grades' }}</td>
                                <td class="py-2 px-3 capitalize text-slate-400">{{ $fee->category }}</td>
                                <td class="py-2 px-3 font-mono text-indigo-300 font-semibold">{{ $fee->vote_code ?: $fee->code }}</td>
                                <td class="py-2 px-3 font-semibold text-slate-100">
                                    {{ $fee->label }}
                                    @if($fee->parent_id === null && $fee->subVotes->count())
                                        <span class="ml-1 text-[9px] px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-700/50">Parent Total</span>
                                    @elseif($fee->parent_id)
                                        <span class="ml-1 text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">Sub-Vote</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-400">
                                    ${{ number_format($fee->amount, 2) }}
                                </td>
                                <td class="py-2 px-2 text-center font-mono text-slate-400">{{ $fee->allocation_priority ?? '—' }}</td>
                                <td class="py-2 px-3 text-slate-400 text-[11px]">
                                    {{ $fee->residency_type ? ucfirst($fee->residency_type) : ($fee->is_optional ? 'Optional' : 'Core') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. OFFICIAL AUTHORISED COMMITTEE SIGNATORIES BLOCK (EXACT REPLICA OF IMAGE 1) -->
        <div class="pt-4 border-t-2 border-slate-700 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    3. Official School Development Committee (SDC) Authorised Signatories
                </h2>
                <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">MOPSE Authorisation Seal</span>
            </div>
            
            <p class="text-[11px] text-slate-400 italic">
                We, the undersigned executive members of the Hande High School Development Committee (SDC) and School Administration, hereby approve and publish the above fee structure and vote distribution schedule in accordance with Ministry of Primary and Secondary Education (MOPSE) guidelines:
            </p>

            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-xs print-table">
                    <thead class="bg-slate-950 text-slate-300 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-12 font-bold">No.</th>
                            <th class="py-2.5 px-4 text-left font-bold">Committee Member Name</th>
                            <th class="py-2.5 px-4 text-left font-bold">Official Role / Designation</th>
                            <th class="py-2.5 px-6 text-center font-bold">Physical Signature</th>
                            <th class="py-2.5 px-4 text-center font-bold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @foreach($signatories as $idx => $sig)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-100 uppercase tracking-wide">{{ $sig['name'] }}</td>
                                <td class="py-3 px-4 font-semibold text-indigo-300 uppercase text-[11px]">{{ $sig['role'] }}</td>
                                <td class="py-3 px-6 text-center">
                                    <div class="w-48 mx-auto border-b border-dashed border-slate-600 h-6"></div>
                                </td>
                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    _____ / _____ / 2026
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Official Stamp & Bank Instructions Block -->
            <div class="mt-4 p-4 rounded-xl bg-slate-950/70 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="text-xs text-slate-400 space-y-1.5 flex-1">
                    <div class="font-bold text-slate-200 uppercase text-[11px]">Official Banking Directives:</div>
                    <p>All school fees and levies must be paid into the official Hande Secondary School commercial bank accounts. Proof of bank transfer or physical cash deposit slip must be presented to the Accounts Office for issuance of the official numbered Ministry voucher receipt.</p>
                    <p class="text-[10px] text-amber-400 font-mono">No student shall be admitted to boarding facilities or attend classes without verified receipting.</p>
                </div>
                <div class="w-48 h-24 border-2 border-dashed border-slate-600 rounded-xl flex items-center justify-center text-slate-500 text-[10px] uppercase font-bold tracking-wider text-center p-2 shrink-0">
                    Official School Stamp & Seal
                </div>
            </div>
        </div>

        <div class="text-center text-[10px] text-slate-500 pt-3 border-t border-slate-800">
            Hande Secondary School · Buhera Rural District Council · Certified Official Fee Tariff Document
        </div>
    </div>
@endsection
