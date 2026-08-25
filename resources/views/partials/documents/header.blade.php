@include('components.documents.school-header', [
    'school' => $school ?? null,
    'snapshot' => $snapshot ?? null,
    'branding' => $branding ?? null,
    'title' => $title ?? null,
    'subtitle' => $subtitle ?? null,
    'reference' => $reference ?? null,
    'date' => $date ?? null,
    'status' => $status ?? null,
    'statusClass' => $statusClass ?? null,
    'compact' => $compact ?? false,
])
