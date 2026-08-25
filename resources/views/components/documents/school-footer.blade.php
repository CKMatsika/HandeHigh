@props([
    'school' => null,
    'snapshot' => null,
    'branding' => null,
    'showBanking' => false,
    'customNote' => null,
    'generatedBy' => null,
])

@php
    if (!$branding) {
        $brandingService = app(\App\Services\Branding\SchoolDocumentBrandingService::class);
        $resolvedSchool = $school ?: (Auth::check() ? Auth::user()->school : null);
        $branding = $resolvedSchool ? $brandingService->getBrandingPayload($resolvedSchool, $snapshot) : [];
    }
@endphp

<div class="document-footer mt-8 pt-4 border-t border-slate-800 print:border-slate-300 text-xs text-slate-400 print:text-slate-600 space-y-3">
    <!-- Banking & Payment Instructions -->
    @if($showBanking && (!empty($branding['bank_name']) || !empty($branding['bank_account_number']) || !empty($branding['payment_instructions'])))
        <div class="p-3 rounded-lg border border-slate-800 bg-slate-950/40 print:border-slate-300 print:bg-slate-50 space-y-1">
            <div class="font-bold text-[11px] uppercase tracking-wider text-slate-300 print:text-slate-800">
                Official Banking & Payment Details
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                @if(!empty($branding['bank_name']))
                    <div><span class="text-slate-500">Bank:</span> <strong class="text-slate-200 print:text-slate-900">{{ $branding['bank_name'] }}</strong></div>
                @endif
                @if(!empty($branding['bank_account_name']))
                    <div><span class="text-slate-500">Account Name:</span> <strong class="text-slate-200 print:text-slate-900">{{ $branding['bank_account_name'] }}</strong></div>
                @endif
                @if(!empty($branding['bank_account_number']))
                    <div><span class="text-slate-500">Account Number:</span> <strong class="font-mono text-slate-200 print:text-slate-900">{{ $branding['bank_account_number'] }}</strong></div>
                @endif
                @if(!empty($branding['bank_branch']))
                    <div><span class="text-slate-500">Branch:</span> <span class="text-slate-200 print:text-slate-900">{{ $branding['bank_branch'] }}</span></div>
                @endif
            </div>
            @if(!empty($branding['payment_instructions']))
                <div class="text-[10px] text-slate-400 print:text-slate-600 pt-1 border-t border-slate-800/60 print:border-slate-200">
                    <strong>Instructions:</strong> {{ $branding['payment_instructions'] }}
                </div>
            @endif
        </div>
    @endif

    <!-- Custom Note / Disclaimer -->
    @if($customNote)
        <div class="text-center italic text-[11px] text-slate-400 print:text-slate-700">
            {{ $customNote }}
        </div>
    @elseif(!empty($branding['footer_text']))
        <div class="text-center italic text-[11px] text-slate-400 print:text-slate-700">
            {{ $branding['footer_text'] }}
        </div>
    @endif

    <!-- System Watermark / Timestamp -->
    <div class="flex flex-col sm:flex-row items-center justify-between text-[10px] text-slate-500 print:text-slate-500 gap-1">
        <div>
            <span>This is an official computer-generated document issued by {{ $branding['display_name'] ?? $branding['name'] ?? 'School System' }}.</span>
            @if(!empty($branding['is_historical_snapshot']))
                <span class="ml-1 text-amber-500/80 font-mono">(Historical Snapshot)</span>
            @endif
        </div>
        <div>
            Generated on {{ now()->format('d M Y, H:i') }}
            @if($generatedBy)
                &bull; By {{ $generatedBy }}
            @endif
        </div>
    </div>
</div>
