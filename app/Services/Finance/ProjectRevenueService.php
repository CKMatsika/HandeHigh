<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectRevenueService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Record income / revenue for a school commercial project (e.g. Agriculture, Poultry).
     */
    public function recordProjectIncome(Project $project, array $data): Receipt
    {
        return DB::transaction(function () use ($project, $data) {
            $school = $project->school;
            $amount = (float) $data['amount'];
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $date = $data['transaction_date'] ?? now()->toDateString();
            $description = $data['description'] ?? "Income for project {$project->name}";

            if ($amount <= 0) {
                throw new InvalidArgumentException('Project income amount must be greater than zero.');
            }

            // Create Receipt for external customer/sale
            $receiptNumber = 'PRJ-' . $project->code . '-' . now()->format('YmdHis');

            $receipt = Receipt::create([
                'school_id' => $school->id,
                'receipt_number' => $receiptNumber,
                'receipt_date' => $date,
                'type' => 'sale',
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? "Project: {$project->name}",
                'total_amount' => $amount,
                'tax_amount' => 0.00,
                'grand_total' => $amount,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'description' => $description,
                'notes' => "Commercial project revenue for [{$project->code}] {$project->name}",
                'created_by' => auth()->id() ?? 1,
            ]);

            ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'description' => $description,
                'category' => 'project_income',
                'quantity' => 1,
                'unit_price' => $amount,
                'tax_rate' => 0.00,
                'line_total' => $amount,
            ]);

            // Post to Accounting General Ledger with project_id
            $cashAccountCode = match ($paymentMethod) {
                'cash' => '1102',
                'bank_transfer', 'bank' => '1301',
                'mobile_money' => '1303',
                default => '1102',
            };

            $cashAccount = Account::where('school_id', $school->id)
                ->where('code', $cashAccountCode)
                ->first() ?? Account::where('school_id', $school->id)->whereIn('code', ['1100', '1301', '1010'])->firstOrFail();

            $revenueAccount = null;
            if ($project->revenue_account_id) {
                $revenueAccount = Account::where('school_id', $school->id)
                    ->where('id', $project->revenue_account_id)
                    ->where('is_active', true)
                    ->first();
            }

            if (! $revenueAccount) {
                $revenueAccount = Account::where('school_id', $school->id)
                    ->where('is_postable', true)
                    ->whereIn('code', ['5800', '5801', '5802', '5804'])
                    ->first() ?? Account::create([
                        'school_id' => $school->id,
                        'code' => '5800',
                        'name' => 'Other Revenue',
                        'type' => 'revenue',
                        'category' => 'other_revenue',
                        'is_active' => true,
                        'is_postable' => true,
                    ]);
            }

            $this->accountingService->createJournalBatch([
                'school_id' => $school->id,
                'transaction_date' => $date,
                'reference_number' => $receiptNumber,
                'description' => $description,
                'source_type' => 'receipt',
                'source_id' => $receipt->id,
                'status' => 'posted',
                'created_by' => auth()->id() ?? 1,
                'entries' => [
                    [
                        'account_id' => $cashAccount->id,
                        'entry_type' => 'debit',
                        'amount' => $amount,
                        'memo' => $description,
                        'project_id' => $project->id,
                    ],
                    [
                        'account_id' => $revenueAccount->id,
                        'entry_type' => 'credit',
                        'amount' => $amount,
                        'memo' => $description,
                        'project_id' => $project->id,
                    ],
                ],
            ]);

            return $receipt;
        });
    }
}
