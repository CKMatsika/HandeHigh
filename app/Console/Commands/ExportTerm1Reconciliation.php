<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ExportTerm1Reconciliation extends Command
{
    protected $signature = 'finance:export-term1-reconciliation
        {--school= : Exact school code; required, no default tenant}
        {--year=2026 : Academic year being reconciled}
        {--filename= : Optional JSON basename within storage/app/private/reconciliation}';

    protected $description = 'Export a private, read-only tenant snapshot for student identity and Term 1 fee reconciliation';

    public function handle(): int
    {
        $code = trim((string) $this->option('school'));
        $year = (string) $this->option('year');
        $filename = $this->option('filename') ?: 'term1-reconciliation-'.now()->format('Ymd-His').'.json';
        if ($code === '' || ! preg_match('/^20\d{2}$/D', $year)
            || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*\.json$/D', $filename)) {
            $this->error('Supply an exact school code, four-digit academic year and a safe JSON basename.');

            return self::FAILURE;
        }

        $file = null;
        $path = null;
        try {
            $schools = DB::table('schools')->where('code', $code)->pluck('id');
            if ($schools->count() !== 1) {
                throw new RuntimeException('School code must resolve to exactly one tenant.');
            }
            $snapshot = app(\App\Services\Finance\Term1ReconciliationExportService::class)->snapshot((int) $schools->first(), $year);
            $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $directory = storage_path('app/private/reconciliation');
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new RuntimeException('Private export directory could not be created.');
            }
            $path = $directory.DIRECTORY_SEPARATOR.$filename;
            $file = @fopen($path, 'x');
            if ($file === false) {
                $path = null; // Never remove or overwrite an existing export.
                throw new RuntimeException('Export file already exists or cannot be opened.');
            }
            @chmod($path, 0600);
            if (fwrite($file, $json) !== strlen($json)) {
                throw new RuntimeException('Export write was incomplete.');
            }
            fclose($file);
            $file = null;
            $this->info('Read-only export saved: '.$path);
            $this->line('Students: '.$snapshot['counts']['students'].'; SHA256: '.hash('sha256', $json));
            $this->line('Missing schema groups: '.count($snapshot['missing_schema_fields']).'; inspect before reconciliation.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            if (is_resource($file)) {
                fclose($file);
                if ($path) {
                    @unlink($path);
                }
            }
            // Do not print SQL, credentials, connection strings or student records.
            $this->error('Export failed safely. Check school code, schema and private output permissions; no database changes were requested.');

            return self::FAILURE;
        }
    }
}
