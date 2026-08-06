<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Result;
use App\Models\CareerPath;
use App\Models\CareerGuidanceAssessment;
use App\Models\StudentCareerInterest;
use Illuminate\Support\Facades\DB;

class CareerGuidanceEngine
{
    const EXCELLENT_MIN = 80;
    const GOOD_MIN = 65;
    const FAIR_MIN = 50;

    const ENCOURAGING_PHRASES = [
        'excellent' => [
            'Outstanding performance in {subject}! Keep up the exceptional work.',
            'You have a natural talent for {subject}. This is a real strength.',
            'Excellent results in {subject}. This opens many career doors.',
        ],
        'good' => [
            'Good work in {subject}! With continued effort, you can excel even further.',
            'You are doing well in {subject}. Keep building on this foundation.',
            'Solid performance in {subject}. Consider challenging yourself further.',
        ],
        'fair' => [
            'You are making progress in {subject}. With more focus, you can improve significantly.',
            '{subject} shows potential. Try different study techniques to boost your understanding.',
            'Keep working on {subject} — every effort brings improvement.',
        ],
        'needs_improvement' => [
            'Everyone learns at their own pace. {subject} may need extra attention — try seeking help from your teacher.',
            'Don\'t be discouraged by {subject}. Many students find it challenging at first. Practice makes progress.',
            '{subject} is an area where you can grow. Start with small goals and build up.',
        ],
    ];

    const IMPROVEMENT_TIPS = [
        'mathematics' => [
            'Practice problem-solving daily with mixed topics.',
            'Work on understanding concepts rather than memorizing formulas.',
            'Join a study group to tackle difficult topics together.',
            'Use online resources and practice past exam papers.',
        ],
        'english' => [
            'Read widely — newspapers, novels, and articles.',
            'Practice writing essays and get feedback from your teacher.',
            'Build your vocabulary by learning 5 new words each day.',
            'Watch educational videos with subtitles to improve comprehension.',
        ],
        'science' => [
            'Relate scientific concepts to everyday life for better understanding.',
            'Perform simple experiments at home to see theory in action.',
            'Create mind maps linking different topics together.',
            'Ask "why" and "how" questions to deepen understanding.',
        ],
        'general' => [
            'Create a study schedule and stick to it.',
            'Take regular breaks — your brain needs rest to absorb information.',
            'Teach what you learn to someone else — it reinforces understanding.',
            'Stay curious and connect subjects to your career interests.',
        ],
    ];

    public function assess(Student $student, ?int $generatedBy = null): CareerGuidanceAssessment
    {
        $school = $student->school;

        $results = Result::where('student_id', $student->id)
            ->where('school_id', $school->id)
            ->with('subject')
            ->selectRaw('subject_id, AVG(average) as avg_score, MAX(grade) as best_grade')
            ->groupBy('subject_id')
            ->get();

        $subjectPerformance = [];
        $strengths = [];
        $areasForImprovement = [];

        foreach ($results as $result) {
            $subjectName = $result->subject?->name ?? 'Unknown';
            $score = (float) $result->avg_score;
            $tier = $this->getTier($score);

            $subjectPerformance[] = [
                'subject_id' => $result->subject_id,
                'subject_name' => $subjectName,
                'average_score' => round($score, 1),
                'tier' => $tier,
                'feedback' => $this->generateSubjectFeedback($subjectName, $tier),
            ];

            if ($tier === 'excellent' || $tier === 'good') {
                $strengths[] = [
                    'subject' => $subjectName,
                    'score' => round($score, 1),
                    'message' => "Strong performance in {$subjectName} ({$tier}).",
                ];
            } else {
                $areasForImprovement[] = [
                    'subject' => $subjectName,
                    'score' => round($score, 1),
                    'message' => $this->generateEncouragingTip($subjectName, $tier),
                    'tips' => $this->getImprovementTips($result->subject),
                ];
            }
        }

        $interest = StudentCareerInterest::where('student_id', $student->id)->first();
        $careerPaths = CareerPath::where('school_id', $school->id)
            ->where('is_active', true)->get();

        $suggestedCareers = $this->matchCareers($results, $careerPaths, $interest);

        $overallFeedback = $this->generateOverallFeedback(
            $student->first_name,
            $strengths,
            $areasForImprovement,
            $suggestedCareers,
            $interest
        );

        $improvementTips = collect($areasForImprovement)
            ->pluck('tips')
            ->flatten()
            ->unique()
            ->take(5)
            ->values()
            ->toArray();

        if (empty($improvementTips)) {
            $improvementTips = collect(self::IMPROVEMENT_TIPS['general'])->take(3)->values()->toArray();
        }

        $assessment = CareerGuidanceAssessment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'assessment_date' => now(),
            'subject_performance' => $subjectPerformance,
            'strengths' => $strengths,
            'areas_for_improvement' => $areasForImprovement,
            'suggested_careers' => $suggestedCareers,
            'improvement_tips' => $improvementTips,
            'overall_feedback' => $overallFeedback,
            'status' => 'published',
            'generated_by' => $generatedBy,
        ]);

        return $assessment;
    }

    private function getTier(float $score): string
    {
        if ($score >= self::EXCELLENT_MIN) return 'excellent';
        if ($score >= self::GOOD_MIN) return 'good';
        if ($score >= self::FAIR_MIN) return 'fair';
        return 'needs_improvement';
    }

    private function generateSubjectFeedback(string $subject, string $tier): string
    {
        $phrases = self::ENCOURAGING_PHRASES[$tier] ?? self::ENCOURAGING_PHRASES['fair'];
        $phrase = $phrases[array_rand($phrases)];
        return str_replace('{subject}', $subject, $phrase);
    }

    private function generateEncouragingTip(string $subject, string $tier): string
    {
        if ($tier === 'excellent') {
            return "Excellent in {$subject}! Consider mentoring other students.";
        }
        if ($tier === 'good') {
            return "Good progress in {$subject}. With focused effort, you can reach excellence.";
        }
        if ($tier === 'fair') {
            return "{$subject} is developing well. Consistent practice will boost your confidence.";
        }
        return "{$subject} is a growth area. Small steps each day lead to big improvements — you've got this!";
    }

    private function getImprovementTips($subject): array
    {
        if (!$subject) return [self::IMPROVEMENT_TIPS['general'][array_rand(self::IMPROVEMENT_TIPS['general'])]];

        $name = strtolower($subject->name);
        $tips = [];

        if (str_contains($name, 'math')) {
            $tips = self::IMPROVEMENT_TIPS['mathematics'];
        } elseif (str_contains($name, 'english') || str_contains($name, 'literature')) {
            $tips = self::IMPROVEMENT_TIPS['english'];
        } elseif (str_contains($name, 'science') || str_contains($name, 'physics') || str_contains($name, 'chemistry') || str_contains($name, 'biology')) {
            $tips = self::IMPROVEMENT_TIPS['science'];
        }

        if (empty($tips)) {
            $tips = self::IMPROVEMENT_TIPS['general'];
        }

        $selected = [];
        $keys = array_rand($tips, min(2, count($tips)));
        $keys = is_array($keys) ? $keys : [$keys];
        foreach ($keys as $k) {
            $selected[] = $tips[$k];
        }
        return $selected;
    }

    private function matchCareers($results, $careerPaths, $interest): array
    {
        $suggested = [];

        if ($interest && $interest->desired_career) {
            $suggested[] = [
                'name' => $interest->desired_career,
                'match_percentage' => null,
                'reason' => 'This is the career you expressed interest in.',
                'is_student_choice' => true,
            ];
        }

        $studentSubjectScores = [];
        foreach ($results as $r) {
            $studentSubjectScores[$r->subject_id] = (float) $r->avg_score;
        }

        foreach ($careerPaths as $path) {
            $requirements = $path->subject_requirements ?? [];
            if (empty($requirements)) continue;

            $metCount = 0;
            $totalCount = count($requirements);
            $details = [];

            foreach ($requirements as $req) {
                $subjId = $req['subject_id'] ?? null;
                $minScore = $req['min_score'] ?? 50;

                if ($subjId && isset($studentSubjectScores[$subjId])) {
                    $met = $studentSubjectScores[$subjId] >= $minScore;
                    if ($met) $metCount++;
                    $details[] = [
                        'subject' => $req['subject_name'] ?? 'Unknown',
                        'required' => $minScore,
                        'achieved' => $studentSubjectScores[$subjId],
                        'met' => $met,
                    ];
                }
            }

            $matchPercentage = $totalCount > 0 ? round(($metCount / $totalCount) * 100) : 0;

            if ($matchPercentage >= 50) {
                $suggested[] = [
                    'name' => $path->name,
                    'description' => $path->description,
                    'match_percentage' => $matchPercentage,
                    'details' => $details,
                    'skills' => $path->skills,
                    'outlook' => $path->outlook,
                    'is_student_choice' => false,
                ];
            }
        }

        usort($suggested, function ($a, $b) {
            $aPct = $a['match_percentage'] ?? 0;
            $bPct = $b['match_percentage'] ?? 0;
            return $bPct <=> $aPct;
        });

        return $suggested;
    }

    private function generateOverallFeedback($firstName, $strengths, $areasForImprovement, $suggestedCareers, $interest): string
    {
        $parts = [];

        $parts[] = "Hello {$firstName}! Here is your personalized career guidance assessment.";

        if (!empty($strengths)) {
            $top = $strengths[0];
            $parts[] = "Your strength in {$top['subject']} is impressive — this is a subject you can build your future around.";
        }

        $strongSubjects = array_map(fn($s) => $s['subject'], array_slice($strengths, 0, 3));
        if (!empty($strongSubjects)) {
            $parts[] = "Your strongest subjects are: " . implode(', ', $strongSubjects) . ". These are areas where you truly shine!";
        }

        if (!empty($areasForImprovement)) {
            $growthAreas = array_map(fn($a) => $a['subject'], array_slice($areasForImprovement, 0, 2));
            $parts[] = "Every student has room to grow. With a little extra focus on " . implode(' and ', $growthAreas) . ", you can make great progress. Remember, improvement is a journey, not a race.";
        }

        if (!empty($suggestedCareers)) {
            $careerNames = array_map(fn($c) => $c['name'], array_slice($suggestedCareers, 0, 3));
            $parts[] = "Based on your performance, careers like " . implode(', ', $careerNames) . " could be great paths to explore. Your skills align well with these fields!";
        }

        if ($interest && $interest->desired_career) {
            $parts[] = "We see you're interested in becoming a {$interest->desired_career}. That's wonderful! Keep working toward your goal — your current strengths are a great foundation.";
        }

        $parts[] = "Remember: your grades today do not define your future. They are simply signposts showing where you excel and where you can grow. Every successful person started exactly where you are now. Keep believing in yourself!";

        return implode(' ', $parts);
    }
}
