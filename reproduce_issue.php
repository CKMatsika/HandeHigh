<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\School;
use App\Models\User;
use App\Models\Account;
use App\Models\Receipt;
use App\Services\AccountingService;
use App\Models\JournalBatch;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Running reproduction script...\n";

// Setup data
DB::beginTransaction();
try {
    // get a school or create one
    $school = School::first();
    if (!$school) {
        $school = School::create(['name' => 'Test School', 'slug' => 'test-school']);
    }

    // Get a user
    $user = User::first();
    if (!$user) {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'school_id' => $school->id
        ]);
    }
    auth()->login($user);

    // Create necessary accounts if not exist
    $cashAccount = Account::firstOrCreate(
        ['school_id' => $school->id, 'code' => '1100'],
        ['name' => 'Cash', 'type' => 'asset']
    );
    
    $revenueAccount = Account::firstOrCreate(
        ['school_id' => $school->id, 'code' => '5801'],
        ['name' => 'Other Revenue', 'type' => 'revenue']
    );

    // Get initial balance
    $initialBalance = AccountBalance::where('account_id', $cashAccount->id)->sum('balance') ?? 0;
    
    echo "Initial Cash Balance: $initialBalance\n";

    // Create a receipt
    $receipt = Receipt::create([
        'school_id' => $school->id,
        'receipt_number' => 'TEST-001',
        'receipt_date' => now(),
        'type' => 'other',
        'total_amount' => 100,
        'tax_amount' => 0,
        'grand_total' => 100,
        'payment_method' => 'cash',
        'created_by' => $user->id,
    ]);

    echo "Created Receipt: {$receipt->id}\n";

    // Post the receipt using AccountingService
    $service = new AccountingService();
    $batch = $service->postReceipt($receipt);

    echo "Created Journal Batch: {$batch->id} with status: {$batch->status}\n";

    // Check balance again
    $newBalance = AccountBalance::where('account_id', $cashAccount->id)->sum('balance') ?? 0;
    echo "New Cash Balance: $newBalance\n";

    if ($batch->status === 'draft') {
        echo "FAIL: Batch is in DRAFT status. Balances not updated.\n";
    } elseif ($batch->status === 'posted') {
        echo "SUCCESS: Batch is POSTED. Balances should be updated.\n";
    }

    // Manual clean up if needed or rollback
    // DB::rollBack(); // Uncomment to not persist data
    // We want to see the fail, so we can keep it or rollback. Let's rollback to not pollute.
    DB::rollBack();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    DB::rollBack();
}
