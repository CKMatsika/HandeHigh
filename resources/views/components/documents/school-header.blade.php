@props([
    'school' => null,
    'snapshot' => null,
    'branding' => null,
    'title' => null,
    'subtitle' => null,
    'reference' => null,
    'date' => null,
    'status' => null,
    'statusClass' => null,
    'compact' => false,
])

@php
    if (!$branding) {
        $brandingService = app(\App\Services\Branding\SchoolDocumentBrandingService::class);
        $resolvedSchool = $school ?: (Auth::check() ? Auth::user()->school : null);
        $branding = $resolvedSchool ? $brandingService->getBrandingPayload($resolvedSchool, $snapshot) : [
            'name' => 'Hande High School',
            'display_name' => 'Hande High School',
            'motto' => 'Excellence in Education',
            'logo_url' => null,
            'logo_base64' => null,
            'has_logo' => false,
            'formatted_address' => 'Zimbabwe',
            'formatted_contacts' => 'info@handehigh.ac.zw',
            'email' => 'info@handehigh.ac.zw',
            'website' => 'www.handehigh.ac.zw',
            'registration_number' => null,
            'zimsec_center_number' => null,
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#d97706',
        ];
    }
@endphp

<div class="document-header mb-6 pb-4 border-b-2 border-slate-700/60 print:border-slate-800 print:mb-4 print:pb-3">
    <div class="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4">
        <!-- School Identity (Logo & Names) -->
        <div class="flex items-center sm:items-start gap-4 text-center sm:text-left">
            @if(!empty($branding['logo_base64']) || !empty($branding['logo_url']))
                <div class="flex-shrink-0">
                    <img src="{{ $branding['logo_base64'] ?: $branding['logo_url'] }}" 
                         alt="{{ $branding['name'] }}" 
                         class="h-16 w-16 object-contain rounded-lg border border-slate-700/50 bg-white/5 p-1 print:border-none print:p-0">
                </div>
            @else
                <div class="flex-shrink-0 h-16 w-16 rounded-xl flex items-center justify-center font-bold text-lg text-white shadow-inner"
                     style="background: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#1e3a8a' }}, {{ $branding['secondary_color'] ?? '#d97706' }});">
                    {{ strtoupper(substr($branding['name'] ?? 'H', 0, 2)) }}
                </div>
            @endif

            <div class="space-y-0.5">
                <h1 class="text-xl font-bold tracking-tight text-slate-100 print:text-slate-900 font-sans uppercase">
                    {{ $branding['name'] }}
                </h1>
                @if(!empty($branding['motto']))
                    <p class="text-xs italic text-slate-400 print:text-slate-600 font-serif">
                        "{{ $branding['motto'] }}"
                    </p>
                @endif
                <p class="text-[11px] text-slate-400 print:text-slate-600">
                    {{ $branding['formatted_address'] }}
                </p>
                <p class="text-[11px] text-slate-400 print:text-slate-600">
                    {{ $branding['formatted_contacts'] }}
                </p>
                @if(!empty($branding['registration_number']) || !empty($branding['zimsec_center_number']))
                    <p class="text-[10px] font-mono text-slate-500 print:text-slate-500">
                        @if(!empty($branding['registration_number'])) Reg: {{ $branding['registration_number'] }} @endif
                        @if(!empty($branding['registration_number']) && !empty($branding['zimsec_center_number'])) &bull; @endif
                        @if(!empty($branding['zimsec_center_number'])) ZIMSEC Centre: {{ $branding['zimsec_center_number'] }} @endif
                    </p>
                @endif
            </div>
        </div>

        <!-- Document Title & Reference Metadata -->
        @if($title || $reference || $date)
            <div class="text-center sm:text-right space-y-1">
                @if($title)
                    <div class="text-xs uppercase tracking-widest font-bold text-indigo-400 print:text-slate-800">
                        {{ $title }}
                    </div>
                @endif
                @if($subtitle)
                    <div class="text-sm font-semibold text-slate-200 print:text-slate-900">
                        {{ $subtitle }}
                    </div>
                @endif
                @if($reference)
                    <div class="text-xs font-mono font-medium text-slate-300 print:text-slate-800">
                        Ref: <span class="font-bold">{{ $reference }}</span>
                    </div>
                @endif
                @if($date)
                    <div class="text-[11px] text-slate-400 print:text-slate-600">
                        Date: {{ is_string($date) ? $date : $date->format('d M Y') }}
                    </div>
                @endif
                @if($status)
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $statusClass ?: 'bg-slate-800 text-slate-300 print:border print:border-slate-400' }}">
                            {{ $status }}
                        </span>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
