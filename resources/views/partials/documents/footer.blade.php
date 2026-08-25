@include('components.documents.school-footer', [
    'school' => $school ?? null,
    'snapshot' => $snapshot ?? null,
    'branding' => $branding ?? null,
    'showBanking' => $showBanking ?? false,
    'customNote' => $customNote ?? null,
    'generatedBy' => $generatedBy ?? null,
])
