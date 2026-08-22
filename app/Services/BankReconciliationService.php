<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\CashbookTransaction;
use App\Models\JournalEntry;
use App\Models\JournalBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankReconciliationService
{
    /**
     * Import bank statement from CSV/Excel
     */
    public function importBankStatement($accountId, $filePath, $options = [])
    {
        $account = Account::findOrFail($accountId);
        $imported = 0;
        $errors = [];

        try {
            // Handle file import based on extension
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if ($extension === 'csv') {
                $transactions = $this->parseCsvFile($filePath, $options);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $transactions = $this->parseExcelFile($filePath, $options);
            } else {
                throw new \Exception('Unsupported file format. Please use CSV or Excel files.');
            }

            foreach ($transactions as $transaction) {
                try {
                    BankTransaction::create([
                        'school_id' => $account->school_id,
                        'account_id' => $accountId,
                        'transaction_date' => $transaction['date'],
                        'reference_number' => $transaction['reference'] ?? null,
                        'description' => $transaction['description'],
                        'amount' => $transaction['amount'],
                        'transaction_type' => $transaction['type'],
                        'source' => 'bank_statement',
                        'status' => 'unmatched',
                        'created_by' => auth()->id(),
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Error importing transaction: " . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'message' => "Successfully imported {$imported} transactions."
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Import failed: " . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Auto-match transactions using intelligent algorithms
     */
    public function autoMatchTransactions($accountId, $options = [])
    {
        $account = Account::findOrFail($accountId);
        $matches = [];
        $confidence = $options['confidence_threshold'] ?? 0.8;

        // Get unmatched transactions
        $bankTransactions = BankTransaction::where('account_id', $accountId)
            ->unmatched()
            ->orderBy('transaction_date')
            ->get();

        $cashbookTransactions = CashbookTransaction::where('account_id', $accountId)
            ->unmatched()
            ->orderBy('transaction_date')
            ->get();

        foreach ($bankTransactions as $bankTx) {
            $potentialMatches = $this->findPotentialMatches($bankTx, $cashbookTransactions, $confidence);
            
            foreach ($potentialMatches as $match) {
                if ($match['confidence'] >= $confidence) {
                    $this->createMatch($bankTx, $match['cashbook_transaction'], $match['amount'], 'auto');
                    $matches[] = [
                        'bank_transaction' => $bankTx->id,
                        'cashbook_transaction' => $match['cashbook_transaction']->id,
                        'amount' => $match['amount'],
                        'confidence' => $match['confidence']
                    ];
                }
            }
        }

        return [
            'matches_found' => count($matches),
            'matches' => $matches,
            'message' => "Auto-matched " . count($matches) . " transaction pairs."
        ];
    }

    /**
     * Find potential matches using multiple algorithms
     */
    private function findPotentialMatches($bankTransaction, $cashbookTransactions, $minConfidence = 0.8)
    {
        $matches = [];

        foreach ($cashbookTransactions as $cashbookTx) {
            $confidence = $this->calculateMatchConfidence($bankTransaction, $cashbookTx);
            
            if ($confidence >= $minConfidence) {
                $matches[] = [
                    'cashbook_transaction' => $cashbookTx,
                    'amount' => min($bankTransaction->unmatched_amount, $cashbookTx->unmatched_amount),
                    'confidence' => $confidence,
                    'match_type' => $this->determineMatchType($bankTransaction, $cashbookTx)
                ];
            }
        }

        // Sort by confidence (highest first)
        usort($matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $matches;
    }

    /**
     * Calculate match confidence using multiple factors
     */
    private function calculateMatchConfidence($bankTx, $cashbookTx)
    {
        $confidence = 0;
        $factors = [];

        // Amount matching (40% weight)
        $amountDiff = abs($bankTx->amount - $cashbookTx->amount);
        $amountTolerance = min($bankTx->amount, $cashbookTx->amount) * 0.01; // 1% tolerance
        if ($amountDiff <= $amountTolerance) {
            $confidence += 0.4;
            $factors[] = 'exact_amount';
        } elseif ($amountDiff <= $amountTolerance * 5) { // 5% tolerance
            $confidence += 0.2;
            $factors[] = 'close_amount';
        }

        // Date proximity (20% weight)
        $dateDiff = abs($bankTx->transaction_date->diffInDays($cashbookTx->transaction_date));
        if ($dateDiff <= 1) {
            $confidence += 0.2;
            $factors[] = 'same_day';
        } elseif ($dateDiff <= 3) {
            $confidence += 0.1;
            $factors[] = 'close_date';
        }

        // Reference number matching (20% weight)
        if ($bankTx->reference_number && $cashbookTx->reference_number) {
            if ($bankTx->reference_number === $cashbookTx->reference_number) {
                $confidence += 0.2;
                $factors[] = 'exact_reference';
            } elseif (levenshtein($bankTx->reference_number, $cashbookTx->reference_number) <= 2) {
                $confidence += 0.1;
                $factors[] = 'similar_reference';
            }
        }

        // Description similarity (20% weight)
        $descriptionSimilarity = $this->calculateStringSimilarity(
            $bankTx->description,
            $cashbookTx->description
        );
        if ($descriptionSimilarity >= 0.8) {
            $confidence += 0.2;
            $factors[] = 'similar_description';
        } elseif ($descriptionSimilarity >= 0.6) {
            $confidence += 0.1;
            $factors[] = 'partial_description_match';
        }

        return [
            'score' => $confidence,
            'factors' => $factors
        ];
    }

    /**
     * Calculate string similarity using Levenshtein distance
     */
    private function calculateStringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        $maxLength = max(strlen($str1), strlen($str2));
        if ($maxLength === 0) return 1.0;
        
        $distance = levenshtein($str1, $str2);
        return 1.0 - ($distance / $maxLength);
    }

    /**
     * Determine match type based on matching factors
     */
    private function determineMatchType($bankTx, $cashbookTx)
    {
        if ($bankTx->amount === $cashbookTx->amount && 
            $bankTx->transaction_date->eq($cashbookTx->transaction_date)) {
            return 'exact';
        }
        return 'partial';
    }

    /**
     * Create a match between bank and cashbook transactions
     */
    public function createMatch($bankTransaction, $cashbookTransaction, $amount, $matchType = 'manual')
    {
        if ((int) $bankTransaction->school_id !== (int) $cashbookTransaction->school_id) {
            throw new \InvalidArgumentException('Transactions must belong to the same school.');
        }

        DB::beginTransaction();
        
        try {
            // Create match record
            DB::table('transaction_matches')->insert([
                'bank_transaction_id' => $bankTransaction->id,
                'cashbook_transaction_id' => $cashbookTransaction->id,
                'match_amount' => $amount,
                'match_type' => $matchType,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update bank transaction
            $bankTransaction->addMatch($cashbookTransaction->id, $amount, $matchType);

            // Update cashbook transaction
            $cashbookTransaction->addMatch($bankTransaction->id, $amount, $matchType);

            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create transaction match: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add manual bank charge transaction
     */
    public function addBankCharge($accountId, $data)
    {
        $account = Account::findOrFail($accountId);
        
        // Create bank transaction
        $bankTransaction = BankTransaction::create([
            'school_id' => $account->school_id,
            'account_id' => $accountId,
            'transaction_date' => $data['date'],
            'reference_number' => $data['reference'] ?? null,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'transaction_type' => 'debit', // Bank charges are typically debits
            'source' => 'manual',
            'status' => 'unmatched',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Create corresponding cashbook transaction
        $cashbookTransaction = CashbookTransaction::create([
            'school_id' => $account->school_id,
            'account_id' => $accountId,
            'transaction_date' => $data['date'],
            'reference_number' => $data['reference'] ?? null,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'transaction_type' => 'debit',
            'category' => 'bank_charge',
        ]);

        return $cashbookTransaction;
    }

    /**
     * Post a reconciliation CashbookTransaction to the general ledger
     */
    public function postCashbookTransactionToLedger(CashbookTransaction $tx): ?int
    {
        try {
            $school = $tx->school;
            $cashAccount = $tx->account;
            
            // Determine offsetting account
            $code = match($tx->category) {
                'bank_charge' => '7100', // Bank Charges Expense
                'interest' => '5800',    // Fallback Interest/Other Revenue
                'fees' => '5100',        // Student Fee Revenue
                'salaries' => '6100',    // Salaries Expense
                'supplies' => '6500',    // Supplies Expense
                default => $tx->transaction_type === 'debit' ? '7200' : '5800',
            };
            
            $offsetAccount = Account::where('school_id', $tx->school_id)
                ->where('code', $code)
                ->first();
                
            if (!$offsetAccount) {
                $offsetAccount = Account::where('school_id', $tx->school_id)
                    ->where('type', $tx->transaction_type === 'debit' ? 'expense' : 'revenue')
                    ->first();
            }
            
            if (!$cashAccount || !$offsetAccount) {
                return null;
            }
            
            $isDebit = $tx->transaction_type === 'debit';
            
            $debitAccountId = $isDebit ? $offsetAccount->id : $cashAccount->id;
            $creditAccountId = $isDebit ? $cashAccount->id : $offsetAccount->id;
            
            $accountingService = app(\App\Services\AccountingService::class);
            $batch = $accountingService->createJournalBatch([
                'school_id' => $tx->school_id,
                'transaction_date' => $tx->transaction_date,
                'reference_number' => $tx->reference_number ?? 'REC-' . $tx->id,
                'description' => $tx->description,
                'source_type' => 'reconciliation',
                'source_id' => $tx->id,
                'status' => 'posted',
                'created_by' => $tx->created_by ?? auth()->id(),
                'entries' => [
                    [
                        'account_id' => $debitAccountId,
                        'entry_type' => 'debit',
                        'amount' => $tx->amount,
                        'memo' => $tx->description,
                    ],
                    [
                        'account_id' => $creditAccountId,
                        'entry_type' => 'credit',
                        'amount' => $tx->amount,
                        'memo' => $tx->description,
                    ],
                ],
            ]);
            
            return $batch->entries->first()?->id;
        } catch (\Exception $e) {
            Log::error('Failed to post reconciliation cashbook transaction to ledger: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get reconciliation summary
     */
    public function getReconciliationSummary($accountId, $startDate, $endDate)
    {
        $bankTransactions = BankTransaction::where('account_id', $accountId)
            ->byDateRange($startDate, $endDate)
            ->get();

        $cashbookTransactions = CashbookTransaction::where('account_id', $accountId)
            ->byDateRange($startDate, $endDate)
            ->get();

        return [
            'bank_summary' => [
                'total_transactions' => $bankTransactions->count(),
                'total_amount' => $bankTransactions->sum('amount'),
                'matched' => $bankTransactions->where('status', 'matched')->count(),
                'unmatched' => $bankTransactions->where('status', 'unmatched')->count(),
                'partially_matched' => $bankTransactions->where('status', 'partially_matched')->count(),
                'matched_amount' => $bankTransactions->sum('matched_amount'),
                'unmatched_amount' => $bankTransactions->sum('unmatched_amount'),
            ],
            'cashbook_summary' => [
                'total_transactions' => $cashbookTransactions->count(),
                'total_amount' => $cashbookTransactions->sum('amount'),
                'matched' => $cashbookTransactions->where('status', 'matched')->count(),
                'unmatched' => $cashbookTransactions->where('status', 'unmatched')->count(),
                'partially_matched' => $cashbookTransactions->where('status', 'partially_matched')->count(),
                'matched_amount' => $cashbookTransactions->sum('matched_amount'),
                'unmatched_amount' => $cashbookTransactions->sum('unmatched_amount'),
            ],
            'variance' => $bankTransactions->sum('unmatched_amount') - $cashbookTransactions->sum('unmatched_amount'),
        ];
    }

    /**
     * Parse CSV file
     */
    private function parseCsvFile($filePath, $options)
    {
        $transactions = [];
        $headers = $options['headers'] ?? ['date', 'description', 'amount', 'type'];
        $dateFormat = $options['date_format'] ?? 'Y-m-d';

        if (($handle = fopen($filePath, 'r')) !== false) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($row === 0 && $options['skip_header'] ?? true) {
                    $row++;
                    continue;
                }

                $transaction = [
                    'date' => $this->parseDate($data[$headers['date']] ?? $data[0], $dateFormat),
                    'description' => $data[$headers['description'] ?? $data[1]] ?? '',
                    'amount' => floatval($data[$headers['amount'] ?? $data[2]] ?? 0),
                    'type' => $data[$headers['type'] ?? $data[3]] ?? 'credit',
                    'reference' => $data[$headers['reference'] ?? $data[4]] ?? null,
                ];

                $transactions[] = $transaction;
                $row++;
            }
            fclose($handle);
        }

        return $transactions;
    }

    /**
     * Parse Excel file (simplified - would need Laravel Excel package for full implementation)
     */
    private function parseExcelFile($filePath, $options)
    {
        // This is a placeholder - would implement with Laravel Excel package
        throw new \Exception('Excel import requires Laravel Excel package. Please use CSV format for now.');
    }

    /**
     * Parse date with multiple formats
     */
    private function parseDate($dateString, $format)
    {
        try {
            return \Carbon\Carbon::createFromFormat($format, $dateString);
        } catch (\Exception $e) {
            // Try common formats
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
            foreach ($formats as $format) {
                try {
                    return \Carbon\Carbon::createFromFormat($format, $dateString);
                } catch (\Exception $e) {
                    continue;
                }
            }
            throw new \Exception("Unable to parse date: {$dateString}");
        }
    }
}
