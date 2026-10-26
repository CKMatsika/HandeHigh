<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Finance\Term1ControlledImportService;
use App\Services\Finance\Term1ImportPlanner;
use Illuminate\Console\Command;
use Throwable;

class ControlledTerm1Import extends Command
{
    protected $signature = 'finance:term1-controlled-import
        {--manifest= : Private approved source manifest JSON}
        {--snapshot= : Current private production reconciliation export JSON}
        {--report= : New private report basename, without path}
        {--execute : Explicitly execute only the independently approved reference list}
        {--approval= : Private maker/checker, backup and deployment evidence JSON}
        {--operator= : Authorised administrator user ID, execution only}
        {--deployed-commit= : Full production commit confirmed from Cloud, execution only}';

    protected $description = 'Default offline dry run; explicitly gated, invoice-only Term 1 2026 historical import';

    public function handle(Term1ImportPlanner $planner, Term1ControlledImportService $writer): int
    {
        try {
            $read = function ($path): array {
                if (! is_string($path) || ! is_file($path) || filesize($path) > 50 * 1024 * 1024) {
                    throw new \RuntimeException('A private JSON file of at most 50 MB is required.');
                }

                return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            };
            $plan = $read($this->option('manifest'));
            $snapshot = $read($this->option('snapshot'));
            $name = $this->option('report') ?: 'term1-dry-run-'.now()->format('Ymd-His').'.json';
            if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*\.json$/D', $name)) {
                throw new \RuntimeException('Use a private report basename, not a path.');
            }
            // Reserve report output before any optional execution, preventing an
            // existing report/permission error from hiding a successful posting.
            $directory = storage_path('app/private/term1-import');
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Private report storage is unavailable.');
            }
            $handle = @fopen($directory.DIRECTORY_SEPARATOR.$name, 'x');
            if (! $handle) {
                throw new \RuntimeException('Report already exists or cannot be created.');
            }
            @chmod($directory.DIRECTORY_SEPARATOR.$name, 0600);
            try {
                if ($this->option('execute')) {
                    $approval = $read($this->option('approval'));
                    $operator = User::findOrFail((int) $this->option('operator'));
                    $report = $writer->execute($plan, $approval, $snapshot, $operator, (string) $this->option('deployed-commit'));
                } else {
                    $report = $planner->preview($plan, $snapshot);
                    $report['mode'] = 'OFFLINE_DRY_RUN_NO_DATABASE_WRITES';
                    $report['snapshot_captured_at'] = $snapshot['exported_at'];
                    $report['manifest_sha256'] = $planner->fingerprint($plan);
                    $report['snapshot_sha256'] = $planner->fingerprint($snapshot);
                    $report['execution_guard_status'] = 'NOT_VERIFIED: backup, deployment, fresh snapshot and independent approvals required';
                }
                $bytes = json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                if (fwrite($handle, $bytes) !== strlen($bytes) || ! fflush($handle)) {
                    throw new \RuntimeException('Report write incomplete; inspect persisted import batch before any execution retry.');
                }
            } finally {
                fclose($handle);
            }
            $this->info($this->option('execute') ? 'Controlled execution completed; inspect private report and persisted migration batch.' : 'Offline dry run completed. No database writes.');
            $this->line('Private report: '.$directory.DIRECTORY_SEPARATOR.$name);
            if (! $this->option('execute')) {
                $this->line('Cleared invoices: '.$report['cleared_invoices'].'; cents: '.$report['cleared_amount_cents'].'; receipts: 0.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Term 1 operation refused or failed. No credentials or student payload printed. Check private evidence and persisted batch before retrying.');

            return self::FAILURE;
        }
    }
}
