<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TeacherImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\TeachersImport;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class TeacherBulkImportController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->user()?->school, 403);
        return view('admin.teachers.bulk-import');
    }

    public function template(Request $request)
    {
        abort_unless($request->user()?->school, 403);
        return Excel::download(new TeacherImportTemplateExport(), 'teachers_import_template.xlsx');
    }

    public function store(Request $request)
    {
        $school = $request->user()?->school;
        abort_unless($school, 403);

        $validated = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        $import = new TeachersImport((int) $school->id);
        Excel::import($import, $validated['file']);
        $results = $import->results();

        if (Schema::hasTable('audit_logs')) {
            try {
                AuditLog::create([
                    'school_id' => $school->id,
                    'user_id' => $request->user()->id,
                    'action' => 'bulk_import',
                    'module' => 'HR Teachers',
                    'description' => 'Teacher workbook import completed. Created: '.count($results['created']).'; skipped: '.count($results['skipped']).'; invalid: '.count($results['invalid']).'.',
                    'new_values' => ['created_count' => count($results['created']), 'skipped_count' => count($results['skipped']), 'invalid_count' => count($results['invalid'])],
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                ]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return view('admin.teachers.bulk-import-results', compact('results'));
    }
}
