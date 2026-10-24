<?php

namespace App\Services\Finance;

use App\Exceptions\MissingAccountMappingException;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\BankAccount;
use App\Models\Cashbook;
use App\Models\FeeStructure;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialAccountResolver
{
    /**
     * Cache for resolved accounts within the request lifecycle.
     */
    protected array $cache = [];

    /**
     * Resolve a school model or ID to school ID.
     */
    protected function getSchoolId(School|int $school): int
    {
        return $school instanceof School ? $school->id : $school;
    }

    /**
     * Resolve school model or ID to School instance.
     */
    protected function getSchool(School|int $school): School
    {
        return $school instanceof School ? $school : School::findOrFail($school);
    }

    /**
     * Get a mapped account for a given mapping type and source key.
     *
     * @throws MissingAccountMappingException
     */
    public function getMappedAccount(School|int $school, string $mappingType, string $sourceKey, bool $autoBootstrap = true): Account
    {
        $schoolId = $this->getSchoolId($school);
        $normalizedKey = strtoupper(trim($sourceKey));
        $cacheKey = "{$schoolId}_{$mappingType}_{$normalizedKey}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $mapping = AccountMapping::where('school_id', $schoolId)
            ->where('mapping_type', $mappingType)
            ->where(function ($query) use ($sourceKey, $normalizedKey) {
                $query->where('source_key', $sourceKey)
                      ->orWhere('source_key', $normalizedKey);
            })
            ->where('is_active', true)
            ->with('account')
            ->first();

        if ($mapping && $mapping->account) {
            $account = $mapping->account;
            $this->validateAccountEligibility($account, $schoolId, $mappingType, $sourceKey);
            $this->cache[$cacheKey] = $account;
            return $account;
        }

        // If not found and auto-bootstrap enabled, attempt bootstrap from existing accounts
        if ($autoBootstrap) {
            $this->bootstrapDefaultMappings($schoolId);

            $mapping = AccountMapping::where('school_id', $schoolId)
                ->where('mapping_type', $mappingType)
                ->where(function ($query) use ($sourceKey, $normalizedKey) {
                    $query->where('source_key', $sourceKey)
                          ->orWhere('source_key', $normalizedKey);
                })
                ->where('is_active', true)
                ->with('account')
                ->first();

            if ($mapping && $mapping->account) {
                $account = $mapping->account;
                $this->validateAccountEligibility($account, $schoolId, $mappingType, $sourceKey);
                $this->cache[$cacheKey] = $account;
                return $account;
            }
        }

        $schoolName = $school instanceof School ? $school->name : "ID {$schoolId}";
        throw new MissingAccountMappingException(
            "Account mapping missing for '{$sourceKey}' under mapping type '{$mappingType}' in school '{$schoolName}'. Please configure this account mapping in the Chart of Accounts mappings before posting.",
            $mappingType,
            $sourceKey,
            $schoolId
        );
    }

    /**
     * Validate that an account is valid for financial posting in this school.
     */
    protected function validateAccountEligibility(Account $account, int $schoolId, string $mappingType, string $sourceKey): void
    {
        if ($account->school_id !== $schoolId) {
            throw new MissingAccountMappingException(
                "Cross-tenant violation: Account '{$account->code} - {$account->name}' belongs to school {$account->school_id}, not school {$schoolId}.",
                $mappingType,
                $sourceKey,
                $schoolId
            );
        }

        if (!$account->is_active) {
            throw new MissingAccountMappingException(
                "Account '{$account->code} - {$account->name}' mapped to '{$sourceKey}' is DEACTIVATED. Posting to inactive accounts is prohibited.",
                $mappingType,
                $sourceKey,
                $schoolId
            );
        }

        if (!$account->is_postable) {
            throw new MissingAccountMappingException(
                "Account '{$account->code} - {$account->name}' mapped to '{$sourceKey}' is a non-postable HEADER account. Posting to header accounts is prohibited.",
                $mappingType,
                $sourceKey,
                $schoolId
            );
        }
    }

    /**
     * Resolve control accounts
     */
    public function getReceivableAccount(School|int $school): Account
    {
        return $this->getMappedAccount($school, 'control_account', 'accounts_receivable');
    }

    public function getPayableAccount(School|int $school): Account
    {
        return $this->getMappedAccount($school, 'control_account', 'accounts_payable');
    }

    public function getPrepaidFeesAccount(School|int $school): Account
    {
        return $this->getMappedAccount($school, 'control_account', 'prepaid_fees');
    }

    public function getOpeningBalanceEquityAccount(School|int $school): Account
    {
        return $this->getMappedAccount($school, 'control_account', 'opening_balance_equity');
    }

    public function getBankChargesAccount(School|int $school): Account
    {
        return $this->getMappedAccount($school, 'control_account', 'bank_charges');
    }

    public function getClearingAccount(School|int $school): Account
    {
        return $this->getMappedAccount($school, 'control_account', 'cash_clearing');
    }

    /**
     * Resolve Fee Vote Revenue Account
     */
    public function getFeeVoteRevenueAccount(School|int $school, string $voteCode): Account
    {
        return $this->getMappedAccount($school, 'fee_vote', strtoupper(trim($voteCode)));
    }

    /**
     * Resolve Bank Account GL account
     */
    public function getBankAccountGLAccount(BankAccount|int $bankAccount): Account
    {
        $bankModel = $bankAccount instanceof BankAccount ? $bankAccount : BankAccount::findOrFail($bankAccount);

        if ($bankModel->account_id) {
            $glAccount = Account::where('school_id', $bankModel->school_id)
                ->where('id', $bankModel->account_id)
                ->first();

            if ($glAccount) {
                $this->validateAccountEligibility($glAccount, $bankModel->school_id, 'bank_account', (string) $bankModel->id);
                return $glAccount;
            }
        }

        // Try mapping by bank_account_id
        try {
            return $this->getMappedAccount($bankModel->school_id, 'bank_account', (string) $bankModel->id);
        } catch (MissingAccountMappingException $e) {
            // Check if account with same code or name exists
            throw new MissingAccountMappingException(
                "Bank Account '{$bankModel->account_name}' ({$bankModel->bank_name} - {$bankModel->account_number}) is not linked to any General Ledger Asset account. Please link this bank account in the Chart of Accounts.",
                'bank_account',
                (string) $bankModel->id,
                $bankModel->school_id
            );
        }
    }

    /**
     * Resolve payment method account
     */
    public function getPaymentMethodAccount(School|int $school, string $method): Account
    {
        return $this->getMappedAccount($school, 'payment_method', strtolower(trim($method)));
    }

    /**
     * Resolve expenditure category account
     */
    public function getExpenseAccount(School|int $school, string $category): Account
    {
        return $this->getMappedAccount($school, 'expenditure_category', strtolower(trim($category)));
    }

    /**
     * Dynamically resolve offset account for cashbook transaction according to accounting classification
     */
    public function resolveOffsetAccountForCashbook(Cashbook $cashbook): Account
    {
        $schoolId = $cashbook->school_id;

        // If an explicit offset_account_id was provided and valid, use it!
        if ($cashbook->offset_account_id) {
            $explicit = Account::where('school_id', $schoolId)->find($cashbook->offset_account_id);
            if ($explicit) {
                $this->validateAccountEligibility($explicit, $schoolId, 'cashbook_offset', 'explicit');
                return $explicit;
            }
        }

        $classification = $cashbook->classification ?? match ($cashbook->category) {
            'fees', 'tuition' => 'debtor_receipt',
            'opening_balance' => 'opening_balance',
            'transfer' => 'bank_transfer',
            'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'bank_charge' => 'direct_expense',
            default => $cashbook->transaction_type === 'expense' ? 'direct_expense' : 'direct_revenue',
        };

        return match ($classification) {
            'debtor_receipt' => $this->getReceivableAccount($schoolId),
            'student_advance' => $this->getPrepaidFeesAccount($schoolId),
            'supplier_payment' => $this->getPayableAccount($schoolId),
            'opening_balance' => $this->getOpeningBalanceEquityAccount($schoolId),
            'bank_charges' => $this->getBankChargesAccount($schoolId),
            'clearing', 'bank_transfer', 'transfer' => $this->getClearingAccount($schoolId),
            'direct_revenue' => !empty($cashbook->vote_code)
                ? $this->getFeeVoteRevenueAccount($schoolId, $cashbook->vote_code)
                : $this->getMappedAccount($schoolId, 'revenue_category', $cashbook->category ?? 'other_revenue'),
            'direct_expense' => $this->getExpenseAccount($schoolId, $cashbook->category ?? 'general_expense'),
            default => $cashbook->transaction_type === 'expense'
                ? $this->getExpenseAccount($schoolId, $cashbook->category ?? 'general_expense')
                : $this->getMappedAccount($schoolId, 'revenue_category', $cashbook->category ?? 'other_revenue'),
        };
    }

    /**
     * Set a mapping explicitly
     */
    public function setMapping(School|int $school, string $mappingType, string $sourceKey, Account|int $account, ?string $description = null): AccountMapping
    {
        $schoolId = $this->getSchoolId($school);
        $accountId = $account instanceof Account ? $account->id : $account;
        $accModel = $account instanceof Account ? $account : Account::findOrFail($accountId);

        $this->validateAccountEligibility($accModel, $schoolId, $mappingType, $sourceKey);

        $normalizedKey = strtoupper(trim($sourceKey));

        // Invalidate request cache
        unset($this->cache["{$schoolId}_{$mappingType}_{$normalizedKey}"]);
        unset($this->cache["{$schoolId}_{$mappingType}_{$sourceKey}"]);

        return AccountMapping::updateOrCreate([
            'school_id' => $schoolId,
            'mapping_type' => $mappingType,
            'source_key' => $normalizedKey,
        ], [
            'account_id' => $accModel->id,
            'description' => $description ?? "Mapping for {$mappingType} => {$sourceKey}",
            'is_active' => true,
        ]);
    }

    /**
     * Dynamically bootstrap default mappings from existing chart of accounts for a school.
     * Does NOT alter, overwrite, or delete existing production accounts.
     */
    public function bootstrapDefaultMappings(School|int $school): void
    {
        $schoolId = $this->getSchoolId($school);

        $accounts = Account::where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->get();

        if ($accounts->isEmpty()) {
            app(\App\Services\AccountingService::class)->ensureChartOfAccountsExist($schoolId);
            $accounts = Account::where('school_id', $schoolId)
                ->where('is_active', true)
                ->where('is_postable', true)
                ->get();
        }

        if ($accounts->isEmpty()) {
            return;
        }

        $mappingsToCreate = [];

        // 1. Control Accounts: Accounts Receivable
        if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'control_account')->where('source_key', 'ACCOUNTS_RECEIVABLE')->exists()) {
            $ar = $accounts->first(fn($a) => $a->code === '1201')
                ?? $accounts->first(fn($a) => $a->type === 'asset' && (str_contains(strtolower($a->name), 'student fees') || str_contains(strtolower($a->name), 'fees receivable')))
                ?? $accounts->first(fn($a) => $a->code === '1200')
                ?? $accounts->first(fn($a) => $a->type === 'asset' && ($a->category === 'receivable' || str_contains(strtolower($a->name), 'receivable')));
            if ($ar) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'control_account',
                    'source_key' => 'ACCOUNTS_RECEIVABLE',
                    'account_id' => $ar->id,
                    'description' => 'Student Fees Receivable Control Account',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 2. Control Accounts: Accounts Payable
        if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'control_account')->where('source_key', 'ACCOUNTS_PAYABLE')->exists()) {
            $ap = $accounts->first(fn($a) => $a->type === 'liability' && ($a->category === 'payable' || $a->code === '3100' || $a->code === '3101' || str_contains(strtolower($a->name), 'payable')));
            if ($ap) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'control_account',
                    'source_key' => 'ACCOUNTS_PAYABLE',
                    'account_id' => $ap->id,
                    'description' => 'Trade Creditors / Accounts Payable Control Account',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 3. Control Accounts: Prepaid Fees / Fees in Advance
        if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'control_account')->where('source_key', 'PREPAID_FEES')->exists()) {
            $prepaid = $accounts->first(fn($a) => $a->type === 'liability' && ($a->code === '2105' || str_contains(strtolower($a->name), 'prepaid') || str_contains(strtolower($a->name), 'advance')));
            if (!$prepaid) {
                // Fall back to general current liability
                $prepaid = $accounts->first(fn($a) => $a->type === 'liability' && $a->category === 'current_liability');
            }
            if ($prepaid) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'control_account',
                    'source_key' => 'PREPAID_FEES',
                    'account_id' => $prepaid->id,
                    'description' => 'Prepaid Student Fees Liability Control Account',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 4. Control Accounts: Opening Balance Equity / Retained Funds
        if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'control_account')->where('source_key', 'OPENING_BALANCE_EQUITY')->exists()) {
            $equity = $accounts->first(fn($a) => $a->type === 'equity' && ($a->code === '3900' || $a->code === '3300' || str_contains(strtolower($a->name), 'opening') || str_contains(strtolower($a->name), 'accumulated') || str_contains(strtolower($a->name), 'fund balance') || str_contains(strtolower($a->name), 'retained')));
            if (!$equity) {
                $equity = $accounts->first(fn($a) => $a->type === 'equity');
            }
            if ($equity) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'control_account',
                    'source_key' => 'OPENING_BALANCE_EQUITY',
                    'account_id' => $equity->id,
                    'description' => 'Opening Balance Equity / Accumulated Fund Balance',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 5. Control Accounts: Bank Charges
        if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'control_account')->where('source_key', 'BANK_CHARGES')->exists()) {
            $bankCharge = $accounts->first(fn($a) => $a->type === 'expense' && ($a->code === '7100' || str_contains(strtolower($a->name), 'bank charge')));
            if ($bankCharge) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'control_account',
                    'source_key' => 'BANK_CHARGES',
                    'account_id' => $bankCharge->id,
                    'description' => 'Bank Charges & Financial Fees Expense Account',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 6. Control Accounts: Cash Clearing / Transfers
        if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'control_account')->where('source_key', 'CASH_CLEARING')->exists()) {
            $clearing = $accounts->first(fn($a) => $a->type === 'asset' && ($a->code === '1105' || str_contains(strtolower($a->name), 'clearing') || str_contains(strtolower($a->name), 'in transit')));
            if (!$clearing) {
                // If no specific clearing account exists, use main operating cash/bank account as bridge
                $clearing = $accounts->first(fn($a) => $a->type === 'asset' && ($a->category === 'cash' || $a->category === 'bank'));
            }
            if ($clearing) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'control_account',
                    'source_key' => 'CASH_CLEARING',
                    'account_id' => $clearing->id,
                    'description' => 'Cash Clearing / Cash in Transit Account',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 7. Payment Methods: cash, bank, mobile_money, ecocash
        $pettyCash = $accounts->first(fn($a) => $a->type === 'asset' && ($a->code === '1102' || $a->code === '1100' || $a->category === 'cash'));
        $mainBank = $accounts->first(fn($a) => $a->type === 'asset' && ($a->code === '1301' || $a->code === '1321' || $a->category === 'bank'));
        $mobileMoney = $accounts->first(fn($a) => $a->type === 'asset' && ($a->code === '1303' || str_contains(strtolower($a->name), 'mobile') || str_contains(strtolower($a->name), 'ecocash')));

        $paymentMethods = [
            'CASH' => $pettyCash ?? $mainBank,
            'BANK' => $mainBank ?? $pettyCash,
            'BANK_TRANSFER' => $mainBank ?? $pettyCash,
            'CHECK' => $mainBank ?? $pettyCash,
            'MOBILE_MONEY' => $mobileMoney ?? $mainBank ?? $pettyCash,
            'ECOCASH' => $mobileMoney ?? $mainBank ?? $pettyCash,
        ];

        foreach ($paymentMethods as $methodKey => $acc) {
            if ($acc && !AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'payment_method')->where('source_key', $methodKey)->exists()) {
                $mappingsToCreate[] = [
                    'school_id' => $schoolId,
                    'mapping_type' => 'payment_method',
                    'source_key' => $methodKey,
                    'account_id' => $acc->id,
                    'description' => "Default GL Account for {$methodKey} payments",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 8. Fee Votes
        $standardVotes = [
            'TUITION' => ['5100', 'tuition'],
            'BUILDING_FUND' => ['5120', 'building'],
            'SPORTS' => ['5130', 'sports'],
            'PRACTICALS_ICT' => ['5140', 'ict'],
            'SCIENCE_LEVY' => ['5160', 'science'],
            'GPF_ADMIN' => ['5170', 'gpf'],
            'BOARDING_FEES' => ['5200', 'boarding'],
            'LUNCH' => ['5803', 'canteen'],
        ];

        $revenueAccounts = $accounts->where('type', 'revenue');
        $defaultTuition = $revenueAccounts->first(fn($a) => $a->code === '5100') ?? $revenueAccounts->first();

        foreach ($standardVotes as $voteCode => [$targetCode, $namePattern]) {
            if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'fee_vote')->where('source_key', $voteCode)->exists()) {
                $matched = $revenueAccounts->first(fn($a) => $a->code === $targetCode)
                    ?? $revenueAccounts->first(fn($a) => str_contains(strtolower($a->name), $namePattern))
                    ?? $defaultTuition;

                if ($matched) {
                    $mappingsToCreate[] = [
                        'school_id' => $schoolId,
                        'mapping_type' => 'fee_vote',
                        'source_key' => $voteCode,
                        'account_id' => $matched->id,
                        'description' => "Revenue Account for Fee Vote {$voteCode}",
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 9. Expenditure Categories
        $standardExpenses = [
            'SALARIES' => ['6100', 'salar'],
            'UTILITIES' => ['6300', 'utilit'],
            'MAINTENANCE' => ['6400', 'mainten'],
            'SUPPLIES' => ['6500', 'suppl'],
            'RENT' => ['6600', 'rent'],
            'BANK_CHARGE' => ['7100', 'bank charge'],
            'GENERAL_EXPENSE' => ['7200', 'operating'],
        ];

        $expenseAccounts = $accounts->where('type', 'expense');
        $defaultExpense = $expenseAccounts->first(fn($a) => $a->code === '6500' || $a->code === '7200') ?? $expenseAccounts->first();

        foreach ($standardExpenses as $categoryKey => [$targetCode, $namePattern]) {
            if (!AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'expenditure_category')->where('source_key', $categoryKey)->exists()) {
                $matched = $expenseAccounts->first(fn($a) => $a->code === $targetCode)
                    ?? $expenseAccounts->first(fn($a) => str_contains(strtolower($a->name), $namePattern))
                    ?? $defaultExpense;

                if ($matched) {
                    $mappingsToCreate[] = [
                        'school_id' => $schoolId,
                        'mapping_type' => 'expenditure_category',
                        'source_key' => $categoryKey,
                        'account_id' => $matched->id,
                        'description' => "Expense Account for {$categoryKey}",
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 10. Revenue Categories
        $sundryRevenue = $revenueAccounts->first(fn($a) => in_array($a->code, ['5800', '5801', '5803'])) ?? $defaultTuition;
        if ($sundryRevenue && !AccountMapping::where('school_id', $schoolId)->where('mapping_type', 'revenue_category')->where('source_key', 'OTHER_REVENUE')->exists()) {
            $mappingsToCreate[] = [
                'school_id' => $schoolId,
                'mapping_type' => 'revenue_category',
                'source_key' => 'OTHER_REVENUE',
                'account_id' => $sundryRevenue->id,
                'description' => 'Default Other Revenue Account',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($mappingsToCreate)) {
            AccountMapping::insertOrIgnore($mappingsToCreate);
        }
    }
}
