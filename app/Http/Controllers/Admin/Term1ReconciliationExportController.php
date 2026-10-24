<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Finance\Term1ReconciliationExportService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Term1ReconciliationExportController extends Controller
{
    public static function permitted(?User $user): bool
    {
        return $user && $user->is_active && $user->school_id
            && $user->hasAnyRole(['school-admin', 'super-admin']);
    }

    public function __invoke(Request $request, Term1ReconciliationExportService $export): Response
    {
        $user = $request->user();
        $school = app(TenantContext::class)->school();
        abort_unless(self::permitted($user) && $school && (int) $school->id === (int) $user->school_id, 403);
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^20\d{2}$/D'],
            // Never accept a caller-selected tenant, student or server file path.
            'school_id' => ['prohibited'], 'school' => ['prohibited'],
            'student_id' => ['prohibited'], 'path' => ['prohibited'], 'filename' => ['prohibited'],
        ]);

        try {
            $snapshot = $export->snapshot((int) $school->id, $validated['academic_year']);
            $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $sha256 = hash('sha256', $json);
            // Persist metadata only, after the read-only snapshot has committed.
            // If audit recording fails, no student payload is delivered.
            AuditLog::create([
                'school_id' => $school->id, 'user_id' => $user->id,
                'action' => 'term1_reconciliation_export_generated', 'module' => 'reports',
                'description' => 'Private reconciliation download generated; delivery completion is not inferred.',
                'new_values' => ['academic_year' => $validated['academic_year'], 'exported_at' => $snapshot['exported_at'],
                    'counts' => $snapshot['counts'], 'sha256' => $sha256,
                    'missing_schema_groups' => count($snapshot['missing_schema_fields'])],
            ]);
        } catch (Throwable $e) {
            abort(503, 'The private reconciliation export could not be generated and audited. No download was released.');
        }

        // Generate and deliver in this request: no shared disk or object URL is needed.
        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="hande-term1-'.$validated['academic_year'].'-reconciliation-'.now()->format('Ymd-His').'.json"',
            'Cache-Control' => 'private, no-store, no-cache, max-age=0, must-revalidate',
            'Pragma' => 'no-cache', 'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'X-Export-SHA256' => $sha256,
        ]);
    }
}
