<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use App\Services\StudentClassMappingService;
use Illuminate\Support\Collection;

class InvoiceFeeSelectionService
{
    public function __construct(private readonly StudentClassMappingService $classMapping)
    {
    }

    public function select(
        Collection $feeStructures,
        ?string $studentGrade,
        bool $isBoarder,
        bool $hasTransport,
        bool $includeOptional,
        ?array $selectedIds = null,
        bool $includeBoarding = true,
        bool $includeTransport = true,
        bool $matchGrade = true,
        ?string $studentClassName = null,
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
                if ($this->isApplicable($fee, $studentGrade, $studentClassName, $isBoarder, $hasTransport, $includeOptional, $includeBoarding, $includeTransport, $matchGrade)) {
                    foreach ($subVotes as $subVote) {
                        if ($this->isApplicable($subVote, $studentGrade, $studentClassName, $isBoarder, $hasTransport, $includeOptional, $includeBoarding, $includeTransport, $matchGrade)) {
                            $result->put($subVote->id, $subVote);
                        }
                    }
                }

                continue;
            }

            if ($this->isApplicable($fee, $studentGrade, $studentClassName, $isBoarder, $hasTransport, $includeOptional, $includeBoarding, $includeTransport, $matchGrade)) {
                $result->put($fee->id, $fee);
            }
        }

        return $result->sortBy(fn (FeeStructure $fee) => $fee->allocation_priority ?? PHP_INT_MAX)->values();
    }

    private function isApplicable(
        FeeStructure $fee,
        ?string $studentGrade,
        ?string $studentClassName,
        bool $isBoarder,
        bool $hasTransport,
        bool $includeOptional,
        bool $includeBoarding,
        bool $includeTransport,
        bool $matchGrade,
    ): bool {
        $feeClass = filled($fee->grade)
            ? $this->classMapping->resolveClass($fee->school_id, $fee->grade, $studentGrade, $fee->academic_year)
            : null;
        $feeIsStreamSpecific = $feeClass
            && mb_strtolower(trim($feeClass->name), 'UTF-8') === mb_strtolower(trim($fee->grade), 'UTF-8');

        if ((($matchGrade || $feeIsStreamSpecific) && ! $this->gradeMatches($fee, $studentGrade, $studentClassName))
            || ! $this->residencyMatches($fee, $isBoarder)) {
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

    private function gradeMatches(FeeStructure $fee, ?string $studentGrade, ?string $studentClassName): bool
    {
        if (blank($fee->grade)) {
            return true;
        }

        if (blank($studentGrade)) {
            return false;
        }

        $feeClass = $this->classMapping->resolveClass($fee->school_id, $fee->grade, $studentGrade, $fee->academic_year);
        if ($feeClass && mb_strtolower(trim($feeClass->name), 'UTF-8') === mb_strtolower(trim($fee->grade), 'UTF-8')) {
            return filled($studentClassName)
                && mb_strtolower(trim($studentClassName), 'UTF-8') === mb_strtolower(trim($feeClass->name), 'UTF-8')
                && $this->classMapping->gradesMatch($feeClass->grade, $studentGrade);
        }

        $feeGrade = $feeClass?->grade
            ?: ($this->classMapping->canonicalGrade($fee->school_id, $fee->grade, $fee->grade, $fee->academic_year) ?? $fee->grade);

        return count(array_intersect(
            $this->classMapping->gradeAliases($feeGrade),
            $this->classMapping->gradeAliases($studentGrade),
        )) > 0;
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
