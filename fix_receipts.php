<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Receipt;
use App\Models\Cashbook;
use App\Models\JournalBatch;
use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Fixing broken receipts...\n\n";

$receipts = Receipt::with('journalBatch')->get();
$fixedCount = 0;

foreach ($receipts as $receipt) {
    echo "Checking Receipt {$receipt->receipt_number} (ID: {$receipt->id})...\n";
    
    // Check Journal Batch
    if (!$receipt->journalBatch) {
        echo "  ❌ Missing Journal Batch. Attempting to post...\n";
        // We can call accounting service postReceipt here, assuming verify fix is deployed
        try {
            $service = new \App\Services\AccountingService();
            $service->postReceipt($receipt);
            echo "  ✅ clean postReceipt called.\n";
            $fixedCount++;
        } catch (\Exception $e) {
            echo "  ❌ Error posting: " . $e->getMessage() . "\n";
        }
        continue;
    }

    $batch = $receipt->journalBatch;
    $needsSave = false;
    
    // Check Status
    if ($batch->status === 'draft') {
        echo "  ⚠️ Journal Batch is DRAFT. Posting...\n";
        
        try {
            DB::transaction(function() use ($batch, $receipt) {
                // Update status
                $batch->status = 'posted';
                $batch->posted_at = now();
                $batch->save();
                
                // Update balances
                // We need to know which accounts to update. The batch has entries.
                foreach ($batch->entries as $entry) {
                   $service = new \App\Services\AccountingService();
                    // Using reflection or just reproducing logic since existing updateAccountBalance is protected
                    // Actually, let's use a workaround or make public wrapper? 
                    // No, updateAccountBalance is protected.
                    // But postBatch IS public.
                    
                    // Wait, postBatch throws if status is already posted.
                    // But we just set it to posted? 
                    // No, change logic:
                }
                
                // Using service->postBatch if status was draft?
                // But postBatch checks if ($batch->status === 'posted') throw Exception.
                // So we should NOT set it to posted before calling postBatch.
                // But wait, the batch in DB is 'draft'.
                
            });
            
            // Re-fetch to be sure
            $batch->refresh();
            if ($batch->status === 'draft') {
                 $service = new \App\Services\AccountingService();
                 $service->postBatch($batch);
                 echo "  ✅ Batch Posted via Service.\n";
            }
            
        } catch (\Exception $e) {
            echo "  ❌ Error posting batch: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  - Journal Batch is POSTED.\n";
    }

    // Check Cashbook
    // Find cashbook entry by reference?
    $cashbookEntry = Cashbook::where('reference_number', $receipt->receipt_number)
                             ->orWhere(function($q) use ($receipt) {
                                 // Or matching amount and date and description?
                                 // Reliable link not present on generic receipts (no related_receipt_id on Cashbook in model?)
                                 // Migration has related_payment_id and related_invoice_id.
                                 // It does NOT have related_receipt_id!
                                 // Wait, the AccountingService puts receipt ID in description?
                                 // In createCashbookEntryForReceipt:
                                 // 'description' => 'Receipt ' . $receipt->receipt_number,
                                 // 'reference_number' => $receipt->receipt_number,
                             })->first();

    if (!$cashbookEntry) {
        echo "  ❌ Missing Cashbook Entry. Creating...\n";
        
        try {
            // Re-create logic from AccountingService
             $school = $receipt->school;
             
             $cashAccountCode = match($receipt->payment_method) {
                'cash' => '1100',
                'bank_transfer' => '1301',
                'mobile_money' => '1303',
                default => '1100',
            };
            
            $cashAccount = Account::where('school_id', $school->id)
                ->where('code', $cashAccountCode)
                ->first();
                
            if ($cashAccount) {
                 // Get current balance
                $lastBalance = Cashbook::where('school_id', $school->id)
                    ->where('account_id', $cashAccount->id) // Note: using account_id now!
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->value('balance_after') ?? 0;
            
                $newBalance = $lastBalance + $receipt->grand_total;
                
                Cashbook::create([
                    'school_id' => $school->id,
                    'account_id' => $cashAccount->id,
                    'transaction_type' => 'income',
                    'category' => 'other_revenue',
                    'description' => 'Receipt ' . $receipt->receipt_number,
                    'amount' => $receipt->grand_total,
                    'balance_after' => $newBalance,
                    'transaction_date' => $receipt->receipt_date,
                    'reference_number' => $receipt->receipt_number,
                    'payment_method' => $receipt->payment_method,
                    'created_by' => $receipt->created_by, // Use receipt creator
                    'notes' => $receipt->notes ?? 'General receipt',
                ]);
                echo "  ✅ Cashbook entry created.\n";
                $fixedCount++;
            } else {
                echo "  ❌ Cannot find cash account {$cashAccountCode}.\n";
            }

        } catch (\Exception $e) {
            echo "  ❌ Error creating cashbook: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  - Cashbook Entry exists.\n";
    }
    echo "\n";
}

echo "Done. Fixed $fixedCount items.\n";
