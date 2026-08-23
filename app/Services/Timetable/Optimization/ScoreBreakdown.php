<?php

namespace App\Services\Timetable\Optimization;

class ScoreBreakdown
{
    public function __construct(
        public float $totalScore,
        public array $categories,
        public array $weights,
        public array $rawScores,
        public array $explanations = []
    ) {
    }

    public function getCategoryScore(string $category): float
    {
        return $this->categories[$category] ?? 0.00;
    }

    public function toArray(): array
    {
        return [
            'total_score' => round($this->totalScore, 2),
            'categories' => $this->categories,
            'weights' => $this->weights,
            'raw_scores' => $this->rawScores,
            'explanations' => $this->explanations,
        ];
    }
}
