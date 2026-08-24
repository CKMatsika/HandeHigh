<?php

namespace App\Services\Timetable\Generation;

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflictService;
use Illuminate\Support\Collection;

class DeterministicAllocator
{
    public function __construct(
        protected TimetableConflictService $conflictService,
        protected AllocationFailureExplainer $failureExplainer
    ) {
    }

    /**
     * Run deterministic constrained-first allocation.
     */
    public function allocate(
        Timetable $timetable,
        Collection $preservedSlots,
        Collection $requirements,
        array $options = []
    ): array {
        $seed = $options['seed'] ?? '20260823';
        $candidateIndex = (int) ($options['candidate_index'] ?? 0);
        $days = $timetable->settings['school_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // Deterministic PRNG seed
        $numericSeed = (int) sprintf('%u', crc32((string) $seed . '_cand_' . $candidateIndex));
        mt_srand($numericSeed);

        // Load School Context
        $periods = SchoolPeriod::where('school_id', $timetable->school_id)
            ->active()
            ->where('period_type', 'lesson')
            ->orderBy('period_sequence')
            ->get();

        $rooms = Room::where('school_id', $timetable->school_id)->get();
        $teachers = Teacher::where('school_id', $timetable->school_id)->with('subjects')->get();
        $classes = SchoolClass::where('school_id', $timetable->school_id)->get();
        $subjects = Subject::where('school_id', $timetable->school_id)->get();

        $fixedActivities = $timetable->school->fixedActivities()
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
            })
            ->get();

        $examinations = $timetable->school->timetableExaminations()
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
            })
            ->get();

        $context = [
            'days' => $days,
            'periods' => $periods,
            'periods_by_id' => $periods->keyBy('id')->all(),
            'rooms' => $rooms,
            'teachers' => $teachers,
            'classes' => $classes,
            'subjects' => $subjects,
            'fixed_activities' => $fixedActivities,
            'examinations' => $examinations,
            'hard_only' => true,
        ];

        // Clone preserved slots into working collection
        $currentSlots = collect();
        foreach ($preservedSlots as $ps) {
            $slot = new TimetableSlot($ps->toArray());
            $slot->id = $ps->id ?? null;
            $slot->timetable_id = $timetable->id;
            $slot->is_locked = (bool) $ps->is_locked;
            $currentSlots->push($slot);
        }

        // Sort requirements by Allocation Priority (Most Constrained First)
        $sortedRequirements = $this->prioritizeRequirements($requirements, $teachers, $rooms);

        $unallocatedRequirements = [];
        $totalAllocatedCount = 0;
        $totalRequiredCount = 0;

        foreach ($sortedRequirements as $req) {
            $classId = $req['school_class_id'] ?? $req['class_id'];
            $subjectId = $req['subject_id'];
            $teacherId = $req['teacher_id'] ?? null;
            $roomId = $req['room_id'] ?? null;
            $weeklyPeriods = (int) ($req['weekly_periods'] ?? 1);
            $maxDaily = (int) ($req['max_daily_lessons'] ?? 2);
            $priority = (int) ($req['priority'] ?? 1);
            $totalRequiredCount += $weeklyPeriods;

            // If teacher is not specified, resolve all eligible teachers
            $eligibleTeachers = collect();
            if ($teacherId) {
                $t = $teachers->firstWhere('id', $teacherId);
                if ($t) {
                    $eligibleTeachers->push($t);
                }
            } else {
                $eligibleTeachers = $teachers->filter(function ($t) use ($subjectId) {
                    return $t->subjects->contains('id', $subjectId);
                });
            }

            if ($eligibleTeachers->isEmpty() && $teacherId) {
                $eligibleTeachers->push((object) ['id' => $teacherId]);
            } elseif ($eligibleTeachers->isEmpty()) {
                $eligibleTeachers->push((object) ['id' => null]);
            }

            // If room is not specified and school has rooms, auto-assign if available
            $candidateRooms = collect();
            if ($roomId) {
                $candidateRooms->push((object) ['id' => $roomId]);
            } elseif ($rooms->isNotEmpty()) {
                $candidateRooms = $rooms;
            } else {
                $candidateRooms->push((object) ['id' => null]);
            }

            // Count existing preserved allocations for this requirement
            $alreadyAllocated = $currentSlots->filter(function ($s) use ($classId, $subjectId) {
                return (int) $s->school_class_id === (int) $classId
                    && (int) $s->subject_id === (int) $subjectId
                    && $s->isLesson()
                    && $s->status !== 'cancelled';
            })->count();

            $needed = max(0, $weeklyPeriods - $alreadyAllocated);
            $allocatedForThisReq = $alreadyAllocated;

            if ($needed > 0) {
                // Generate candidate day/period pairs respecting preferences
                $candidateTimeSlots = $this->generateCandidateSlots($days, $periods, $candidateIndex, $priority, $subjectId, $subjects, $req);

                for ($n = 0; $n < $needed; $n++) {
                    $placed = false;

                    foreach ($candidateTimeSlots as $timeOption) {
                        $day = $timeOption['day'];
                        $period = $timeOption['period'];

                        // Check daily limit for this class & subject
                        $dailyCount = $currentSlots->filter(function ($s) use ($classId, $subjectId, $day) {
                            return (int) $s->school_class_id === (int) $classId
                                && (int) $s->subject_id === (int) $subjectId
                                && strcasecmp($s->day_of_week, $day) === 0
                                && $s->isLesson()
                                && $s->status !== 'cancelled';
                        })->count();

                        if ($dailyCount >= $maxDaily) {
                            continue;
                        }

                        // Try eligible teachers and rooms
                        foreach ($eligibleTeachers as $tOption) {
                            $targetTeacherId = $tOption->id ?? null;

                            foreach ($candidateRooms as $rOption) {
                                $targetRoomId = $rOption->id ?? null;

                                // Build candidate slot
                                $tempSlot = new TimetableSlot([
                                    'timetable_id' => $timetable->id,
                                    'school_class_id' => $classId,
                                    'subject_id' => $subjectId,
                                    'teacher_id' => $targetTeacherId,
                                    'room_id' => $targetRoomId,
                                    'school_period_id' => $period->id,
                                    'day_of_week' => $day,
                                    'start_time' => substr((string) $period->start_time, 0, 5),
                                    'end_time' => substr((string) $period->end_time, 0, 5),
                                    'slot_type' => 'lesson',
                                    'status' => 'scheduled',
                                    'is_locked' => false,
                                ]);

                                // Evaluate against hard constraints incrementally
                                if (! $this->hasSlotConflictFast($tempSlot, $currentSlots, $context)) {
                                    $currentSlots->push($tempSlot);
                                    $allocatedForThisReq++;
                                    $totalAllocatedCount++;
                                    $placed = true;
                                    break 3; // successfully placed this lesson
                                }
                            }
                        }
                    }

                    if (! $placed) {
                        break;
                    }
                }
            } else {
                $totalAllocatedCount += $alreadyAllocated;
            }

            if ($allocatedForThisReq < $weeklyPeriods) {
                $reqSummary = $req;
                $reqSummary['allocated_count'] = $allocatedForThisReq;
                $explanation = $this->failureExplainer->explainFailure($timetable, $reqSummary, $currentSlots, $context);
                $unallocatedRequirements[] = $explanation;
            }
        }

        return [
            'slots' => $currentSlots,
            'total_required' => $totalRequiredCount,
            'total_allocated' => $totalAllocatedCount,
            'unallocated_requirements' => $unallocatedRequirements,
            'context' => $context,
        ];
    }

    /**
     * Sort requirements using Most Constrained First heuristics.
     */
    protected function prioritizeRequirements(Collection $requirements, Collection $teachers, Collection $rooms): array
    {
        $items = $requirements->map(function ($r) {
            return is_array($r) ? $r : $r->toArray();
        })->toArray();

        usort($items, function ($a, $b) use ($teachers) {
            // 1. Explicit high priority
            $pA = (int) ($a['priority'] ?? 1);
            $pB = (int) ($b['priority'] ?? 1);
            if ($pA !== $pB) {
                return $pB <=> $pA;
            }

            // 2. Specific room requirement
            $rA = ! empty($a['room_id']) ? 1 : 0;
            $rB = ! empty($b['room_id']) ? 1 : 0;
            if ($rA !== $rB) {
                return $rB <=> $rA;
            }

            // 3. Teacher constraint (fewer eligible teachers -> higher priority)
            $subA = $a['subject_id'];
            $subB = $b['subject_id'];
            $eligCountA = $teachers->filter(fn ($t) => $t->subjects->contains('id', $subA))->count();
            $eligCountB = $teachers->filter(fn ($t) => $t->subjects->contains('id', $subB))->count();
            if ($eligCountA !== $eligCountB && $eligCountA > 0 && $eligCountB > 0) {
                return $eligCountA <=> $eligCountB;
            }

            // 4. Weekly periods count (more periods -> higher priority)
            $wA = (int) ($a['weekly_periods'] ?? 1);
            $wB = (int) ($b['weekly_periods'] ?? 1);
            if ($wA !== $wB) {
                return $wB <=> $wA;
            }

            return 0;
        });

        return $items;
    }

    /**
     * Generate deterministic list of candidate day-period slots.
     */
    protected function generateCandidateSlots(
        array $days,
        Collection $periods,
        int $candidateIndex,
        int $priority,
        int $subjectId,
        Collection $subjects,
        array $req = []
    ): array {
        $subject = $subjects->firstWhere('id', $subjectId);
        $subjectName = strtolower($subject?->name ?? '');
        $isCore = false;
        foreach (['math', 'mathematics', 'physic', 'chemist', 'biolog', 'science', 'english'] as $kw) {
            if (str_contains($subjectName, $kw)) {
                $isCore = true;
                break;
            }
        }

        $preferredDays = $req['preferred_days'] ?? [];
        if (is_string($preferredDays)) {
            $preferredDays = json_decode($preferredDays, true) ?? [];
        }
        $preferredPeriods = $req['preferred_periods'] ?? [];
        if (is_string($preferredPeriods)) {
            $preferredPeriods = json_decode($preferredPeriods, true) ?? [];
        }

        $list = [];
        foreach ($days as $day) {
            foreach ($periods as $period) {
                $seq = $period->period_sequence;
                // Base score preference
                $prefScore = 100;

                // Preferred days boost
                if (! empty($preferredDays) && in_array($day, $preferredDays)) {
                    $prefScore += 100;
                }

                // Preferred periods boost
                if (! empty($preferredPeriods) && (in_array($seq, $preferredPeriods) || in_array($period->id, $preferredPeriods))) {
                    $prefScore += 100;
                }

                // If core subject, prefer morning periods (seq 1 to 4)
                if ($isCore && $seq <= 4) {
                    $prefScore += 50 - ($seq * 5);
                }

                // Deterministic variation based on candidateIndex & day
                $offset = (crc32($day . '_' . $seq . '_' . $candidateIndex) % 30);
                $prefScore += $offset;

                $list[] = [
                    'day' => $day,
                    'period' => $period,
                    'pref_score' => $prefScore,
                ];
            }
        }

        // Sort descending by preference score
        usort($list, function ($a, $b) {
            return $b['pref_score'] <=> $a['pref_score'];
        });

        return $list;
    }

    /**
     * High-performance incremental conflict check for a proposed candidate slot.
     */
    protected function hasSlotConflictFast(TimetableSlot $slot, Collection $currentSlots, array $context): bool
    {
        $day = (string) $slot->day_of_week;
        $start = (string) $slot->start_time;
        $end = (string) $slot->end_time;
        $classId = (int) $slot->school_class_id;
        $teacherId = $slot->teacher_id ? (int) $slot->teacher_id : null;
        $roomId = $slot->room_id ? (int) $slot->room_id : null;

        // 1. Period check
        $period = $context['periods_by_id'][$slot->school_period_id] ?? null;
        if ($period && (! $period->is_active || ! $period->isLesson())) {
            return true;
        }

        // 2. Check against current slots
        foreach ($currentSlots as $existing) {
            if ($existing->status === 'cancelled' || strcasecmp($existing->day_of_week, $day) !== 0) {
                continue;
            }

            $eStart = (string) $existing->start_time;
            $eEnd = (string) $existing->end_time;

            // Overlap condition: start < eEnd && end > eStart
            if ($start < $eEnd && $end > $eStart) {
                // Class double-booking
                if ((int) $existing->school_class_id === $classId) {
                    return true;
                }

                // Teacher double-booking
                if ($teacherId && (int) $existing->teacher_id === $teacherId) {
                    return true;
                }

                // Room double-booking
                if ($roomId && (int) $existing->room_id === $roomId) {
                    return true;
                }
            }
        }

        // 3. Check Fixed Activities
        $fixedActivities = $context['fixed_activities'] ?? [];
        foreach ($fixedActivities as $activity) {
            if ($activity->overlapsWith($day, $start, $end)) {
                if ($activity->appliesToClass($classId) || ($teacherId && $activity->appliesToTeacher($teacherId))) {
                    return true;
                }
            }
        }

        // 4. Check Examinations
        $examinations = $context['examinations'] ?? [];
        foreach ($examinations as $exam) {
            if ($exam->overlapsWith($day, $start, $end)) {
                $appliesToClass = ($exam->school_class_id === null || (int) $exam->school_class_id === $classId);
                $appliesToRoom = ($roomId && $exam->room_id !== null && (int) $exam->room_id === $roomId);
                $appliesToSupervisor = ($teacherId && $exam->supervisor_teacher_id !== null && (int) $exam->supervisor_teacher_id === $teacherId);

                if ($appliesToClass || $appliesToRoom || $appliesToSupervisor) {
                    return true;
                }
            }
        }

        return false;
    }
}
