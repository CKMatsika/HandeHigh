<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Clearance Certificate - {{ $student->full_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white !important; color: black !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-border { border: 4px double #333 !important; }
        }
        @page { size: A4 portrait; margin: 15mm; }
    </style>
</head>
<body class="bg-slate-950 min-h-screen py-10 px-4 text-slate-900 font-sans flex flex-col items-center">

    <!-- Action Toolbar (Hidden on Print) -->
    <div class="no-print w-full max-w-3xl flex justify-between items-center mb-6 bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow-xl">
        <a href="{{ route('admin.year-end.clearance.show', $clearance) }}" class="text-xs font-semibold text-slate-300 hover:text-white flex items-center gap-1">
            ← Back to Clearance Record
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-md flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Certificate</span>
            </button>
        </div>
    </div>

    <!-- Official Certificate Document Container -->
    <div class="w-full max-w-3xl bg-white p-12 rounded-2xl border-4 border-double border-slate-800 shadow-2xl space-y-8 print-border">
        
        <!-- Header -->
        <div class="text-center border-b-2 border-slate-800 pb-6">
            <div class="inline-block p-2 rounded-full border border-slate-300 mb-2">
                <svg class="w-10 h-10 text-slate-800 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </div>
            <h1 class="text-2xl font-serif font-bold uppercase tracking-wider text-slate-900">{{ $school->name }}</h1>
            <p class="text-xs text-slate-600 tracking-wide uppercase mt-1">{{ $school->address ?? 'Main Campus' }} • Tel: {{ $school->phone ?? '—' }}</p>
            <div class="mt-4 inline-block bg-slate-100 border border-slate-300 px-4 py-1 rounded-full">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-800">Official School Leaving & Clearance Certificate</span>
            </div>
        </div>

        <!-- Certificate Serial & Verification -->
        <div class="flex justify-between items-center text-xs font-mono text-slate-600 border-b border-slate-200 pb-3">
            <span>Certificate No: <strong class="text-slate-900">{{ $clearance->certificate_number ?? ('CLR-' . str_pad($clearance->id, 6, '0', STR_PAD_LEFT)) }}</strong></span>
            <span>Date Issued: <strong class="text-slate-900">{{ $clearance->exited_at ? $clearance->exited_at->format('d F Y') : date('d F Y') }}</strong></span>
        </div>

        <!-- Certification Statement -->
        <div class="text-center space-y-4 py-2">
            <p class="text-sm italic text-slate-700">This is to officially certify that</p>
            <h2 class="text-2xl font-bold font-serif text-slate-900 border-b-2 border-dotted border-slate-400 pb-2 inline-block min-w-[320px]">
                {{ $student->full_name }}
            </h2>
            <div class="grid grid-cols-3 gap-4 text-xs text-slate-700 max-w-lg mx-auto pt-2">
                <div>
                    <span class="block text-slate-500 uppercase text-[10px]">Admission No:</span>
                    <strong class="font-mono text-sm text-slate-900">{{ $student->admission_number ?? '—' }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 uppercase text-[10px]">Graduation Grade:</span>
                    <strong class="text-sm text-slate-900">{{ $clearance->graduation_grade ?? $student->grade }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 uppercase text-[10px]">Academic Year:</span>
                    <strong class="text-sm text-slate-900">{{ $clearance->academic_year }}</strong>
                </div>
            </div>
            <p class="text-xs text-slate-700 leading-relaxed max-w-xl mx-auto pt-3">
                has completed all required academic studies and satisfied all institutional obligations across all school departments with zero remaining liabilities.
            </p>
        </div>

        <!-- Department Verification Stamps Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 border-t border-slate-200 text-[11px]">
            <!-- Finance -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-1">
                <span class="font-bold text-slate-800 block uppercase text-[10px]">1. Bursar & Finance</span>
                <span class="inline-block px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                    {{ strtoupper($clearance->finance_status) }}
                </span>
                <p class="text-[9px] text-slate-500">Balance: $0.00</p>
            </div>

            <!-- Library -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-1">
                <span class="font-bold text-slate-800 block uppercase text-[10px]">2. School Library</span>
                <span class="inline-block px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                    {{ strtoupper($clearance->library_status) }}
                </span>
                <p class="text-[9px] text-slate-500">Books Returned</p>
            </div>

            <!-- Assets -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-1">
                <span class="font-bold text-slate-800 block uppercase text-[10px]">3. Asset / Stores</span>
                <span class="inline-block px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                    {{ strtoupper($clearance->assets_status) }}
                </span>
                <p class="text-[9px] text-slate-500">Equipment Returned</p>
            </div>

            <!-- Boarding -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-1">
                <span class="font-bold text-slate-800 block uppercase text-[10px]">4. Boarding Master</span>
                <span class="inline-block px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                    {{ strtoupper($clearance->boarding_status) }}
                </span>
                <p class="text-[9px] text-slate-500">Hostel Cleared</p>
            </div>
        </div>

        <!-- Remarks -->
        @if($clearance->general_remarks)
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs text-slate-700">
                <span class="font-bold text-slate-800">Administrative Remarks:</span>
                <p class="italic mt-0.5">{{ $clearance->general_remarks }}</p>
            </div>
        @endif

        <!-- Signatures & Stamp -->
        <div class="grid grid-cols-2 gap-12 pt-8 text-xs text-slate-800">
            <div class="text-center space-y-2">
                <div class="border-b-2 border-slate-800 pb-1 h-12 flex items-end justify-center">
                    <span class="font-serif italic font-semibold text-slate-700">Official Stamp</span>
                </div>
                <p class="font-bold uppercase tracking-wider text-[10px]">Registrar / Bursar Signature</p>
            </div>

            <div class="text-center space-y-2">
                <div class="border-b-2 border-slate-800 pb-1 h-12 flex items-end justify-center">
                    <span class="font-serif italic font-semibold text-slate-700">Approved & Verified</span>
                </div>
                <p class="font-bold uppercase tracking-wider text-[10px]">Headmaster / Principal Signature</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-[10px] text-slate-400 border-t border-slate-200 pt-4">
            <p>This is a computer-verified official educational clearance document issued by {{ $school->name }}.</p>
        </div>
    </div>
</body>
</html>
