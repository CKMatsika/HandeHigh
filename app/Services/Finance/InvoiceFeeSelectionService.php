<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use Illuminate\Support\Collection;

class InvoiceFeeSelectionService
{
    public function select(
        Collection $feeStructures,
        ?string $studentGrade,
        bool $isBoarder,
        bool $hasTransport,
        bool $includeOptional,
        ?array $selectedIds = null,
        bool $includeBoarding = true,
        bool $includeTransport = true,
    ): Collection {
        $selectedIds ??= $feeStructures->pluck('id')->all();
        $selectedIds = array_fill_keys(array_map('intval', $selectedIds), true);
        $subVotesByParent = $feeStructures
            ->filter(fn (FeeStructure $fee) => filled($fee->vote_code) && $fee->parent_id !== null)
            ->groupBy('parent_id');
        $selectedFees = $feeStructures->filter(fn (FeeStructure $fee) => isset($selectedIds[$fee->id]));
        $result = collect();

        foreach ($selectedFees as $fee) {
            $subVotes = $subVotesByParent->get($fee->id, collect());
            if ($subVotes->isNotEmpty()) {
                if ($this->residencyMatches($fee, $isBoarder)) {
                    foreach ($subVotes as $subVote) {
                        if ($this->isApplicable($subVote, $studentGrade, $isBoarder, $hasTransport, $includeOptional, $includeBoarding, $includeTransport)) {
                            $result->put($subVote->id, $subVote);
                        }
                    }
                }

                continue;
            }

            if ($this->isApplicable($fee, $studentGrade, $isBoarder, $hasTransport, $includeOptional, $includeBoarding, $includeTransport)) {
                $result->put($fee->id, $fee);
            }
        }

        return $result->sortBy(fn (FeeStructure $fee) => $fee->allocation_priority ?? PHP_INT_MAX)->values();
    }

    private function isApplicable(
        FeeStructure $fee,
        ?string $studentGrade,
        bool $isBoarder,
        bool $hasTransport,
        bool $includeOptional,
        bool $includeBoarding,
        bool $includeTransport,
    ): bool {
        if (! $this->gradeMatches($fee->grade, $studentGrade) || ! $this->residencyMatches($fee, $isBoarder)) {
            return false;
        }

        if ($fee->is_optional && ! $includeOptional) {
            return false;
        }

        if ($this->isBoardingFee($fee) && (! $isBoarder || ! $includeBoarding)) {
            return false;
        }

        if ($this->isTransportFee($fee) && (! $hasTransport || ! $includeTransport)) {
            return false;
        }

        return true;
    }

    private function gradeMatches(?string $feeGrade, ?string $studentGrade): bool
    {
        if (blank($feeGrade)) {
            return true;
        }

        if (blank($studentGrade)) {
            return false;
        }

        $feeGrade = mb_strtolower(trim($feeGrade));
        foreach ($this->gradeVariations($studentGrade) as $grade) {
            if ($feeGrade === mb_strtolower($grade)) {
                return true;
            }
        }

        return false;
    }

    private function gradeVariations(string $grade): array
    {
        $cleaned = trim($grade);
        $variations = [$cleaned];

        if (preg_match('/^form\s*(\d+)/i', $cleaned, $matches)) {
            $number = $matches[1];
            $variations = array_merge($variations, [$number, "Form {$number}", "Form{$number}", "Grade {$number}"]);
        } elseif (is_numeric($cleaned)) {
            $variations = array_merge($variations, ["Form {$cleaned}", "Form{$cleaned}", "Grade {$cleaned}"]);
        }

        return array_unique($variations);
    }

    private function residencyMatches(FeeStructure $fee, bool $isBoarder): bool
    {
        $residency = strtolower((string) $fee->residency_type);
        if (in_array($residency, ['boarding', 'day'], true)) {
            return $residency === ($isBoarder ? 'boarding' : 'day');
        }

        return true;
    }

    private function isBoardingFee(FeeStructure $fee): bool
    {
        return $fee->vote_code === 'BOARDING_FEES'
            || str_contains(strtolower((string) $fee->category), 'boarding')
            || $fee->service_type === 'boarding'
            || str_contains(strtolower((string) $fee->label), 'boarding');
    }

    private function isTransportFee(FeeStructure $fee): bool
    {
        return str_contains(strtolower((string) $fee->category), 'transport')
            || strtolower((string) $fee->service_type) === 'transport'
            || str_contains(strtolower((string) $fee->label), 'transport');
    }
}
