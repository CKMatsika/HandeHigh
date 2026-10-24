<?php

// Explicit opt-in runner. Never uses RefreshDatabase, migrate:fresh, truncation or production.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\JournalBatch;
use App\Models\SponsorClaim;
use App\Models\SponsorSettlement;
use App\Models\User;
use App\Services\Finance\ScholarshipBeamReportService;
use App\Services\Finance\SponsorSettlementService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\BeamRehearsal;

$config = config('database.connections.staging');
if (app()->environment('production') || ($config['driver'] ?? null) !== 'mysql' || ($config['database'] ?? null) !== 'staging'
    || empty($config['host']) || empty($config['username']) || ! isset($config['password'])) {
    fwrite(STDERR, "BLOCKED: configure the explicit isolated MySQL staging connection locally. Production and fallback connections are refused.\n");
    exit(2);
}
try {
    DB::setDefaultConnection('staging');
    $identity = DB::connection('staging')->selectOne('SELECT DATABASE() AS db, VERSION() AS version');
    if ($identity->db !== 'staging' || stripos($identity->version, 'MariaDB') !== false) {
        throw new RuntimeException('An isolated MySQL staging database is required.');
    }
} catch (Throwable $e) {
    fwrite(STDERR, "BLOCKED: isolated MySQL identity/connection verification failed; connection details redacted.\n");
    exit(3);
}

if (($argv[1] ?? '') === 'worker') {
    try {
        $settlement = SponsorSettlement::findOrFail((int) ($argv[2] ?? 0));
        $user = User::where('school_id', $settlement->school_id)->findOrFail((int) ($argv[3] ?? 0));
        if (! str_starts_with($user->school->code, 'BEAM-TEST-') || ! str_ends_with($user->email, '@example.test')) {
            throw new RuntimeException('Synthetic records only.');
        }
        Auth::login($user);
        app(SponsorSettlementService::class)->approve($settlement, 'SYNTHETIC-CONCURRENT-CHECK');
        echo json_encode(['status' => 'posted', 'journal' => $settlement->fresh()->journal_batch_id]);
        exit(0);
    } catch (Illuminate\Validation\ValidationException $e) {
        echo json_encode(['status' => 'rejected', 'reason' => 'allocation_or_approval_validation']);
        exit(4);
    } catch (Throwable $e) {
        echo json_encode(['status' => 'failed', 'exception' => $e::class]);
        exit(5);
    }
}

try {
    if (! Illuminate\Support\Facades\Schema::hasTable('account_mappings')) {
        throw new RuntimeException('Financial stabilisation schema must already exist in staging.');
    }
    $migrationOutput = Artisan::call('migrate', ['--database' => 'staging', '--path' => [
        'database/migrations/2026_10_24_000002_add_student_scholarships.php',
        'database/migrations/2026_10_24_000003_add_sponsor_claims_and_settlements.php'], '--force' => true]);
    if ($migrationOutput !== 0) {
        throw new RuntimeException('Additive staging migrations failed.');
    }
    DB::beginTransaction();
    $runner = new BeamRehearsal;
    $result = $runner->run();
    DB::commit(); // Synthetic records must be visible to independent MySQL worker sessions.
    $jobs = function (array $ids, int $checker) {
        $processes = [];
        foreach ($ids as $id) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $id, (string) $checker],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            if (! is_resource($process)) {
                throw new RuntimeException('Worker failed to start.');
            }
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $results = [];
        foreach ($processes as [$process,$pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $decoded = json_decode($out, true);
            if (! is_array($decoded)) {
                throw new RuntimeException('Worker output was not valid evidence.');
            }$results[] = $decoded;
        }

        return $results;
    };
    Auth::login(User::findOrFail($result['maker_id']));
    $sponsor = App\Models\FundingSponsor::findOrFail($result['sponsor_id']);
    $prepare = function (string $suffix, float $amount) use ($result, $sponsor) {
        return app(SponsorSettlementService::class)->prepare($sponsor, [
            'reference' => 'SYN-CONCURRENT-'.$result['school_id'].'-'.$suffix, 'received_on' => '2026-04-01', 'amount' => $amount,
            'bank_account_id' => $result['bank_id'], 'cash_received' => 1, 'supporting_reference' => 'SYNTHETIC-CONCURRENCY',
            'allocations' => [['target_type' => 'claim', 'target_id' => $result['claim_id'], 'amount' => $amount]]]);
    };
    $same = $prepare('SAME', 10);
    $sameJobs = $jobs([$same->id, $same->id], $result['checker_id']);
    if (collect($sameJobs)->where('status', 'posted')->count() !== 2 || JournalBatch::where('school_id', $result['school_id'])->where('source_type', 'sponsor_settlement')->where('source_id', $same->id)->count() !== 1) {
        throw new RuntimeException('Concurrent idempotency failed.');
    }
    $first = $prepare('COMPETE-A', 100);
    $second = $prepare('COMPETE-B', 100);
    $competingJobs = $jobs([$first->id, $second->id], $result['checker_id']);
    if (collect($competingJobs)->where('status', 'posted')->count() !== 1 || collect($competingJobs)->where('status', 'rejected')->count() !== 1
        || SponsorClaim::findOrFail($result['claim_id'])->outstanding < 0) {
        throw new RuntimeException('Concurrent over-allocation prevention failed.');
    }
    Auth::login(User::findOrFail($result['checker_id']));
    $report = app(ScholarshipBeamReportService::class)->build($result['school_id'], '2026-12-31');
    if (! $report['controls']->every(fn ($r) => $r['difference'] === 0.0) || ! $report['trial_balance']['is_balanced']) {
        throw new RuntimeException('Post-concurrency controls failed.');
    }
    $result['engine'] = $identity->version;
    $result['mysql_schema'] = 'PASS';
    $result['concurrent_same_receipt'] = $sameJobs;
    $result['concurrent_competing_allocations'] = $competingJobs;
    $result['final_controls'] = $report['controls']->all();
    file_put_contents(storage_path('logs/beam-mysql-rehearsal.json'), json_encode($result, JSON_PRETTY_PRINT));
    echo "PASS: isolated MySQL schema, synthetic BEAM workflow, concurrent idempotency and competing allocations. Synthetic school #{$result['school_id']} retained for review.\n";
} catch (Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }fwrite(STDERR,'FAILED: synthetic staging rehearsal ('.$e::class."). Details redacted; preserve the staged evidence for diagnosis.\n");
    exit(1);
}
