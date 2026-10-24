<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\InvoiceItem;
use App\Models\School;

class FeeRevenueAccountResolver
{
    /**
     * Category to Account Code mapping.
     */
    protected const REVENUE_CODE_MAP = [
        'tuition' => '5100',
        'tuition_fee' => '5100',
        'tuition_fees' => '5100',
        'boarding' => '5200',
        'boarding_fee' => '5200',
        'boarding_fees' => '5200',
        'transport' => '5300',
        'transport_fee' => '5300',
        'bus' => '5300',
        'registration' => '5400',
        'admission' => '5400',
        'exam' => '5500',
        'examination' => '5500',
        'exam_fee' => '5500',
        'zimsec' => '5500',
        'library' => '5600',
        'library_fee' => '5600',
        'levy' => '5700',
        'levies' => '5700',
        'development_levy' => '5700',
        'building_levy' => '5700',
        'sda_levy' => '5700',
        'other' => '5700',
        'other_fees' => '5700',
        'book' => '5801',
        'books' => '5801',
        'book_sales' => '5801',
        'uniform' => '5802',
        'uniforms' => '5802',
        'uniform_sales' => '5802',
        'canteen' => '5803',
        'kiosk' => '5803',
        'canteen_sales' => '5803',
        'kiosk_sales' => '5803',
        'event' => '5804',
        'events' => '5804',
        'project' => '5800',
        'other_revenue' => '5800',
    ];

    /**
     * Vote code to Account Code mapping.
     */
    protected const VOTE_CODE_MAP = [
        'TUITION' => '5100',
        'BUILDING_FUND' => '5120',
        'SPORTS' => '5130',
        'PRACTICALS_ICT' => '5140',
        'SCIENCE_LEVY' => '5160',
        'GPF_ADMIN' => '5170',
        'BOARDING_FEES' => '5200',
        'LUNCH' => '5803',
    ];

    /**
     * Resolve account code from vote_code, category, service_type, or description string.
     */
    public function resolveCode(?string $category, ?string $serviceType = null, ?string $description = null, ?string $voteCode = null): string
    {
        // 0. Match explicit vote code first
        if ($voteCode && isset(self::VOTE_CODE_MAP[strtoupper(trim($voteCode))])) {
            return self::VOTE_CODE_MAP[strtoupper(trim($voteCode))];
        }

        $normalizedCategory = strtolower(trim($category ?? ''));
        $normalizedService = strtolower(trim($serviceType ?? ''));
        $normalizedDesc = strtolower(trim($description ?? ''));

        // 1. Match from explicit service_type
        if ($normalizedService && isset(self::REVENUE_CODE_MAP[$normalizedService])) {
            return self::REVENUE_CODE_MAP[$normalizedService];
        }

        // 2. Match from explicit category
        if ($normalizedCategory && isset(self::REVENUE_CODE_MAP[$normalizedCategory])) {
            return self::REVENUE_CODE_MAP[$normalizedCategory];
        }

        // 3. Match from description patterns
        if (str_contains($normalizedDesc, 'boarding') || str_contains($normalizedDesc, 'hostel') || str_contains($normalizedDesc, 'accommodation')) {
            return '5200';
        }
        if (str_contains($normalizedDesc, 'building')) {
            return '5120';
        }
        if (str_contains($normalizedDesc, 'sports')) {
            return '5130';
        }
        if (str_contains($normalizedDesc, 'ict') || str_contains($normalizedDesc, 'computer')) {
            return '5140';
        }
        if (str_contains($normalizedDesc, 'science')) {
            return '5160';
        }
        if (str_contains($normalizedDesc, 'general purpose') || str_contains($normalizedDesc, 'gpf')) {
            return '5170';
        }
        if (str_contains($normalizedDesc, 'tuition')) {
            return '5100';
        }
        if (str_contains($normalizedDesc, 'transport') || str_contains($normalizedDesc, 'bus')) {
            return '5300';
        }
        if (str_contains($normalizedDesc, 'exam') || str_contains($normalizedDesc, 'zimsec') || str_contains($normalizedDesc, 'cambridge')) {
            return '5500';
        }
        if (str_contains($normalizedDesc, 'levy') || str_contains($normalizedDesc, 'development') || str_contains($normalizedDesc, 'sda')) {
            return '5700';
        }
        if (str_contains($normalizedDesc, 'kiosk') || str_contains($normalizedDesc, 'canteen') || str_contains($normalizedDesc, 'tuckshop') || str_contains($normalizedDesc, 'lunch')) {
            return '5803';
        }

        // Default fallback to Tuition Revenue (5100) or Other Revenue (5800)
        return '5100';
    }

    /**
     * Resolve the actual Account model for a given invoice item in a school.
     */
    public function resolveAccountForItem(School|int $school, InvoiceItem $item): Account
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        // 1. Check if FeeStructure has an explicitly configured revenue account
        if ($item->feeStructure && $item->feeStructure->revenue_account_id) {
            $configuredAccount = Account::where('school_id', $schoolId)
                ->where('id', $item->feeStructure->revenue_account_id)
                ->where('is_active', true)
                ->first();

            if ($configuredAccount) {
                return $configuredAccount;
            }
        }

        // 2. Resolve via vote_code if present using FinancialAccountResolver
        $voteCode = $item->vote_code ?? $item->feeStructure?->vote_code;
        if ($voteCode) {
            try {
                return app(FinancialAccountResolver::class)->getFeeVoteRevenueAccount($schoolId, $voteCode);
            } catch (\Exception $e) {
                // Continue to secondary resolution
            }
        }

        // 3. Resolve via mapped code (incorporating category / service_type / description)
        $code = $this->resolveCode(
            $item->category,
            $item->feeStructure?->service_type,
            $item->description,
            $voteCode
        );

        // Find specific account in school
        $account = Account::where('school_id', $schoolId)
            ->where('code', $code)
            ->first();

        if (! $account) {
            // Check if standard tuition account exists when code is 5100
            if ($code === '5100') {
                $account = Account::where('school_id', $schoolId)->where('code', '5100')->first();
            }
        }

        if (! $account) {
            // Check dynamic mapping for fee vote or revenue category
            try {
                if ($voteCode) {
                    return app(FinancialAccountResolver::class)->getFeeVoteRevenueAccount($schoolId, $voteCode);
                }
                return app(FinancialAccountResolver::class)->getMappedAccount($schoolId, 'revenue_category', $item->category ?? 'other_revenue');
            } catch (\Exception $e) {
                // If account does not exist in chart of accounts, create standard revenue account for this code
                $account = Account::firstOrCreate(
                    [
                        'school_id' => $schoolId,
                        'code' => $code,
                    ],
                    [
                        'name' => match ($code) {
                            '5200' => 'Boarding Fees',
                            '5300' => 'Transport Fees',
                            '5500' => 'Examination Fees',
                            '5700' => 'Other Fees / Levies',
                            '5801' => 'Book Sales',
                            '5802' => 'Uniform Sales',
                            '5803' => 'Canteen / Kiosk Sales',
                            '5120' => 'Building Fund Levy',
                            '5130' => 'Sports Levy',
                            '5140' => 'Practicals & ICT Fees',
                            '5160' => 'Science Levy',
                            '5170' => 'General Purpose Fund (GPF)',
                            default => 'Tuition Fees',
                        },
                        'type' => 'revenue',
                        'category' => 'tuition_revenue',
                        'is_active' => true,
                        'is_postable' => true,
                    ]
                );
            }
        }

        return $account;
    }
}
