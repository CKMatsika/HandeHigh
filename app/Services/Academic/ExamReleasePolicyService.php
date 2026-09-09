<?php

namespace App\Services\Academic;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\TimetableExamination;
use Carbon\Carbon;

class ExamReleasePolicyService
{
    /**
     * Determine if performance reports are available for entry/viewing based on timetable completion and release policy.
     */
    public function isReportAvailable(School $school, string $academicYear, string $term, ?Carbon $now = null): bool
    {
        $status = $this->getReleaseStatus($school, $academicYear, $term, $now);
        return $status['is_available'];
    }

    /**
     * Get detailed examination timetable completion and release policy status.
     */
    public function getReleaseStatus(School $school, string $academicYear, string $term, ?Carbon $now = null): array
    {
        $timezone = $school->timezone ?: config('app.timezone', 'Africa/Harare');
        $currentTime = $now ? $now->copy()->setTimezone($timezone) : Carbon::now($timezone);

        // 1. Fetch school release policy settings
        $policySetting = SchoolSetting::where('school_id', $school->id)
            ->where('key', 'performance_report_release_policy')
            ->first();
        $policy = $policySetting ? $policySetting->value : 'auto_immediate'; // auto_immediate, auto_delay_hours, scheduled_datetime, manual

        $delayHoursSetting = SchoolSetting::where('school_id', $school->id)
            ->where('key', 'performance_report_delay_hours')
            ->first();
        $delayHours = $delayHoursSetting ? (int)$delayHoursSetting->value : 0;

        $scheduledSetting = SchoolSetting::where('school_id', $school->id)
            ->where('key', 'performance_report_scheduled_release_at')
            ->first();
        $scheduledAt = $scheduledSetting && !empty($scheduledSetting->value) 
            ? Carbon::parse($scheduledSetting->value, $timezone) 
            : null;

        $manualUnlockSetting = SchoolSetting::where('school_id', $school->id)
            ->where('key', "performance_report_unlocked_{$academicYear}_{$term}")
            ->first();
        $isManuallyUnlocked = $manualUnlockSetting ? (bool)$manualUnlockSetting->typed_value : false;

        // 2. Identify the latest scheduled examination for the academic year and term
        $latestExamEnd = $this->findLatestExamEndTime($school, $academicYear, $term, $timezone);

        // If no exams exist for this timetable session, check if manual policy or default open
        if (!$latestExamEnd) {
            if ($policy === 'manual') {
                return [
                    'is_available' => $isManuallyUnlocked,
                    'policy' => $policy,
                    'latest_exam_end' => null,
                    'release_at' => null,
                    'message' => $isManuallyUnlocked 
                        ? 'Reports have been manually released by Academic Administration.' 
                        : 'Reports are awaiting manual release by Academic Administration.',
                ];
            }

            // If no exams are scheduled in the timetable, allow entry by default
            return [
                'is_available' => true,
                'policy' => $policy,
                'latest_exam_end' => null,
                'release_at' => null,
                'message' => 'No examination timetable constraints configured. Reports are available.',
            ];
        }

        // 3. Evaluate based on policy
        if ($policy === 'manual') {
            return [
                'is_available' => $isManuallyUnlocked,
                'policy' => $policy,
                'latest_exam_end' => $latestExamEnd,
                'release_at' => null,
                'message' => $isManuallyUnlocked 
                    ? 'Reports manually released by Examination Administrator.' 
                    : 'Examination period has concluded. Awaiting manual release by Administrator.',
            ];
        }

        if ($policy === 'scheduled_datetime' && $scheduledAt) {
            $isAvailable = $currentTime->greaterThanOrEqualTo($scheduledAt);
            return [
                'is_available' => $isAvailable,
                'policy' => $policy,
                'latest_exam_end' => $latestExamEnd,
                'release_at' => $scheduledAt,
                'message' => $isAvailable 
                    ? "Reports scheduled and released on {$scheduledAt->format('d M Y H:i')}." 
                    : "Reports scheduled to release on {$scheduledAt->format('d M Y H:i')} (Server time: {$currentTime->format('d M Y H:i')}).",
            ];
        }

        // Auto release with optional delay hours
        $releaseAt = $latestExamEnd->copy()->addHours($delayHours);
        $isAvailable = $currentTime->greaterThanOrEqualTo($releaseAt);

        if ($isAvailable) {
            $message = $delayHours > 0 
                ? "Examination period ended at {$latestExamEnd->format('d M Y H:i')}. Released after {$delayHours}h delay." 
                : "Examination period concluded at {$latestExamEnd->format('d M Y H:i')}. Reports are available for teacher marks entry.";
        } else {
            $remaining = $currentTime->diffForHumans($releaseAt, true);
            $message = "Examination period is still active or within the release delay window (Final exam: {$latestExamEnd->format('d M Y H:i')}). Available in {$remaining}.";
        }

        return [
            'is_available' => $isAvailable,
            'policy' => $policy,
            'latest_exam_end' => $latestExamEnd,
            'release_at' => $releaseAt,
            'message' => $message,
        ];
    }

    /**
     * Find latest scheduled exam end time for a given school, academic year, and term.
     */
    public function findLatestExamEndTime(School $school, string $academicYear, string $term, string $timezone): ?Carbon
    {
        $examRows = TimetableExamination::where('school_id', $school->id)
            ->whereHas('timetable', function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear)
                  ->where('term', $term);
            })
            ->get();

        if ($examRows->isEmpty()) {
            // Check direct examinations without timetable relation
            $examRows = TimetableExamination::where('school_id', $school->id)
                ->whereNotNull('exam_date')
                ->get();
        }

        if ($examRows->isEmpty()) {
            return null;
        }

        $latest = null;

        foreach ($examRows as $exam) {
            $examDateStr = $exam->exam_date ? $exam->exam_date->format('Y-m-d') : null;
            $endTimeStr = $exam->end_time ? (is_string($exam->end_time) ? substr($exam->end_time, 0, 5) : Carbon::parse($exam->end_time)->format('H:i')) : '17:00';

            if ($examDateStr) {
                $dateTime = Carbon::parse("{$examDateStr} {$endTimeStr}", $timezone);
                if ($latest === null || $dateTime->greaterThan($latest)) {
                    $latest = $dateTime;
                }
            }
        }

        return $latest;
    }
}
