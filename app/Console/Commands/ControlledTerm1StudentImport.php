<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Finance\Term1StudentReconciliationPlanner;
use App\Services\Finance\Term1StudentReconciliationService;
use Illuminate\Console\Command;
use Throwable;

class ControlledTerm1StudentImport extends Command
{
    protected $signature = 'finance:term1-student-import {--manifest=} {--snapshot=} {--report=} {--execute} {--approval=} {--operator=} {--deployed-commit=}';

    protected $description = 'Preview or execute separately approved missing-student creation for Term 1 2026';

    public function handle(Term1StudentReconciliationPlanner $planner, Term1StudentReconciliationService $writer): int
    {
        $handle = null;
        $path = null;
        try {
            $directory = storage_path('app/private/term1-import');
            $read = function (?string $input) use ($directory): array {
                $realDirectory = realpath($directory);
                $realInput = $input ? realpath($input) : false;
                if (! $realDirectory || ! $realInput || ! str_starts_with($realInput, $realDirectory.DIRECTORY_SEPARATOR)
                    || filesize($realInput) > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Input must be a private JSON file in the Term 1 import directory.');
                }

                return json_decode(file_get_contents($realInput), true, 512, JSON_THROW_ON_ERROR);
            };
            $manifest = $read($this->option('manifest'));
            $snapshot = $read($this->option('snapshot'));
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Private Term 1 storage is unavailable.');
            }
            $name = $this->option('report') ?: 'term1-students-'.now()->format('Ymd-His').'.json';
            if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*\.json$/D', $name)) {
                throw new \RuntimeException('Report must be a safe private JSON basename.');
            }
            $path = $directory.DIRECTORY_SEPARATOR.$name;
            $handle = @fopen($path, 'x');
            if (! $handle) {
                throw new \RuntimeException('Private report already exists or cannot be created.');
            }
            @chmod($path, 0600);
            if ($this->option('execute')) {
                $approval = $read($this->option('approval'));
                $operator = User::findOrFail((int) $this->option('operator'));
                $result = $writer->execute($manifest, $approval, $snapshot, $operator, (string) $this->option('deployed-commit'));
            } else {
                $result = $planner->preview($manifest, $snapshot);
                $result['mode'] = 'READ_ONLY_PLAN';
                $result['manifest_sha256'] = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                $result['snapshot_sha256'] = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            }
            $json = json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (fwrite($handle, $json) !== strlen($json) || ! fflush($handle)) {
                throw new \RuntimeException('Private report write failed. Inspect persisted import batch before retrying execution.');
            }
            fclose($handle);
            $handle = null;
            $this->info($this->option('execute') ? 'Approved Term 1 student import completed.' : 'Read-only Term 1 student plan completed.');
            $this->line('Private report: '.$path);

            return self::SUCCESS;
        } catch (Throwable) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            $this->error('Student import refused or failed safely. No student data or credentials printed; inspect private evidence and persisted batch before retrying.');

            return self::FAILURE;
        }
    }
}
