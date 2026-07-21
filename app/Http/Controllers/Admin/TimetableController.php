<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Room;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index()
    {
        $school = auth()->user()->school;
        $timetables = Timetable::where('school_id', $school->id)
            ->withCount('slots')
            ->latest()
            ->get();

        return view('admin.timetables.index', compact('timetables'));
    }

    public function create()
    {
        $school = auth()->user()->school;
        $academicYears = ['2024-2025', '2025-2026', '2026-2027'];
        $terms = ['First Term', 'Second Term', 'Third Term'];

        return view('admin.timetables.create', compact('academicYears', 'terms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'academic_year' => 'required|string',
            'term' => 'required|string',
        ]);

        $school = auth()->user()->school;
        
        $timetable = Timetable::create([
            'school_id' => $school->id,
            'name' => $request->name,
            'description' => $request->description,
            'academic_year' => $request->academic_year,
            'term' => $request->term,
            'status' => 'draft',
            'settings' => [
                'max_daily_hours' => 8,
                'break_duration' => 30,
                'lunch_break' => '12:00-13:00',
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'time_slots' => [
                    '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00',
                    '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00'
                ],
                'preferences' => [
                    'balance_teacher_workload' => true,
                    'minimize_room_changes' => true,
                    'avoid_consecutive_same_subject' => true,
                    'prefer_morning_core_subjects' => true
                ]
            ],
        ]);

        // Log the creation
        AuditService::log($school->id, auth()->id(), 'create', 'timetable', $timetable->id, 
            "Timetable created: {$timetable->name}", request()->ip(), request()->userAgent(), 'timetable');

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Timetable created successfully. Now configure and generate the schedule.');
    }

    public function show(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        $timetable->load('slots.schoolClass', 'slots.subject', 'slots.teacher', 'slots.room');
        $slotsByDay = $timetable->slots->groupBy('day_of_week');
        $timeSlots = ['08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00', '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00'];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return view('admin.timetables.show', compact('timetable', 'slotsByDay', 'timeSlots', 'days'));
    }

    public function edit(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        return view('admin.timetables.edit', compact('timetable'));
    }

    public function update(Request $request, Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $timetable->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Log the update
        AuditService::log($timetable->school_id, auth()->id(), 'update', 'timetable', $timetable->id, 
            "Timetable updated: {$timetable->name}", request()->ip(), request()->userAgent(), 'timetable');

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Timetable updated successfully.');
    }

    public function generate(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        if ($timetable->status !== 'draft') {
            return redirect()->route('admin.timetables.show', $timetable)
                ->with('error', 'Can only generate timetables in draft status.');
        }

        // Clear existing slots
        $timetable->slots()->delete();

        // Generate new timetable using AI algorithm
        $generator = new TimetableGenerator($timetable);
        $result = $generator->generate();

        if ($result['success']) {
            $timetable->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            // Log the generation
            AuditService::log($timetable->school_id, auth()->id(), 'generate', 'timetable', $timetable->id, 
                "Timetable generated: {$timetable->name}", request()->ip(), request()->userAgent(), 'timetable');

            return redirect()->route('admin.timetables.show', $timetable)
                ->with('success', "Timetable generated successfully! {$result['slots_created']} slots created, {$result['conflicts']} conflicts detected.");
        } else {
            return redirect()->route('admin.timetables.show', $timetable)
                ->with('error', 'Failed to generate timetable: ' . $result['message']);
        }
    }

    public function publish(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        if ($timetable->status !== 'generated') {
            return redirect()->route('admin.timetables.show', $timetable)
                ->with('error', 'Can only publish generated timetables.');
        }

        if ($timetable->hasConflicts()) {
            return redirect()->route('admin.timetables.show', $timetable)
                ->with('error', 'Cannot publish timetable with conflicts. Please resolve conflicts first.');
        }

        $timetable->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Log the publication
        AuditService::log($timetable->school_id, auth()->id(), 'publish', 'timetable', $timetable->id, 
            "Timetable published: {$timetable->name}", request()->ip(), request()->userAgent(), 'timetable');

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Timetable published successfully!');
    }

    public function resolveConflicts(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        $conflicts = $timetable->slots()->where('status', 'conflict')->with(['schoolClass', 'subject', 'teacher', 'room'])->get();
        
        return view('admin.timetables.conflicts', compact('timetable', 'conflicts'));
    }

    public function autoResolveConflicts(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        
        $resolver = new ConflictResolver($timetable);
        $result = $resolver->resolveAll();

        return redirect()->route('admin.timetables.resolve-conflicts', $timetable)
            ->with('success', $result['message']);
    }

    /**
     * Display timetable conflicts
     *
     * @return \Illuminate\View\View
     */
    public function conflicts()
    {
        $school = auth()->user()->school;
        
        // Get all timetables for the school
        $timetables = Timetable::where('school_id', $school->id)
            ->with(['slots' => function($query) {
                $query->with(['schoolClass', 'subject', 'teacher', 'room']);
            }])
            ->get();

        // Detect conflicts (simplified example)
        $conflicts = [];
        
        // Check for teacher double bookings
        $teacherSlots = [];
        foreach ($timetables as $timetable) {
            foreach ($timetable->slots as $slot) {
                $key = $slot->teacher_id . '_' . $slot->day_of_week . '_' . $slot->start_time;
                if (isset($teacherSlots[$key])) {
                    $conflicts[] = [
                        'type' => 'teacher',
                        'severity' => 'high',
                        'message' => 'Teacher double booking',
                        'details' => [
                            'teacher' => $slot->teacher->name,
                            'day' => $slot->day_of_week,
                            'time' => $slot->start_time . ' - ' . $slot->end_time,
                            'conflicts_with' => [
                                'class' => $teacherSlots[$key]['class'],
                                'subject' => $teacherSlots[$key]['subject'],
                                'room' => $teacherSlots[$key]['room']
                            ]
                        ]
                    ];
                } else {
                    $teacherSlots[$key] = [
                        'class' => $slot->schoolClass->name,
                        'subject' => $slot->subject->name,
                        'room' => $slot->room->name
                    ];
                }
            }
        }

        // Check for room double bookings
        $roomSlots = [];
        foreach ($timetables as $timetable) {
            foreach ($timetable->slots as $slot) {
                $key = $slot->room_id . '_' . $slot->day_of_week . '_' . $slot->start_time;
                if (isset($roomSlots[$key])) {
                    $conflicts[] = [
                        'type' => 'room',
                        'severity' => 'medium',
                        'message' => 'Room double booking',
                        'details' => [
                            'room' => $slot->room->name,
                            'day' => $slot->day_of_week,
                            'time' => $slot->start_time . ' - ' . $slot->end_time,
                            'conflicts_with' => [
                                'class' => $roomSlots[$key]['class'],
                                'subject' => $roomSlots[$key]['subject'],
                                'teacher' => $roomSlots[$key]['teacher']
                            ]
                        ]
                    ];
                } else {
                    $roomSlots[$key] = [
                        'class' => $slot->schoolClass->name,
                        'subject' => $slot->subject->name,
                        'teacher' => $slot->teacher->name
                    ];
                }
            }
        }

        // Group conflicts by type
        $conflictsByType = collect($conflicts)->groupBy('type');

        return view('admin.timetables.conflicts', [
            'conflicts' => $conflicts,
            'conflictsByType' => $conflictsByType,
            'timetables' => $timetables
        ]);
    }

    protected function authorizeSchoolAccess(Timetable $timetable)
    {
        if ($timetable->school_id !== auth()->user()->school_id) {
            abort(403);
        }
    }
}

// Helper Classes for AI Scheduling
class TimetableGenerator
{
    protected $timetable;
    protected $school;
    protected $settings;
    protected $classes;
    protected $subjects;
    protected $teachers;
    protected $rooms;

    public function __construct(Timetable $timetable)
    {
        $this->timetable = $timetable;
        $this->school = $timetable->school;
        $this->settings = $timetable->settings;
        
        $this->loadData();
    }

    protected function loadData()
    {
        $this->classes = SchoolClass::where('school_id', $this->school->id)->get();
        $this->subjects = Subject::where('school_id', $this->school->id)->get();
        $this->teachers = Teacher::where('school_id', $this->school->id)->get();
        $this->rooms = Room::where('school_id', $this->school->id)->active()->get();
    }

    public function generate(): array
    {
        $slotsCreated = 0;
        $conflicts = 0;
        $timeSlots = $this->settings['time_slots'];
        $days = $this->settings['school_days'];

        foreach ($this->classes as $class) {
            $requiredSubjects = $this->getClassSubjects($class);
            
            foreach ($requiredSubjects as $subject) {
                $weeklyHours = $subject->weekly_hours ?? 4;
                $assignedHours = 0;
                
                while ($assignedHours < $weeklyHours) {
                    $slot = $this->findBestSlot($class, $subject, $days, $timeSlots);
                    
                    if ($slot) {
                        $conflictCheck = $this->checkConflicts($slot);
                        
                        $timetableSlot = TimetableSlot::create([
                            'timetable_id' => $this->timetable->id,
                            'school_class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $slot['teacher_id'],
                            'room_id' => $slot['room_id'],
                            'day_of_week' => $slot['day'],
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'status' => $conflictCheck ? 'conflict' : 'scheduled',
                            'conflicts' => $conflictCheck,
                            'ai_score' => $this->calculateScore($slot),
                        ]);
                        
                        $slotsCreated++;
                        if ($conflictCheck) $conflicts++;
                        $assignedHours++;
                    } else {
                        break; // No available slots
                    }
                }
            }
        }

        return [
            'success' => true,
            'slots_created' => $slotsCreated,
            'conflicts' => $conflicts,
        ];
    }

    protected function getClassSubjects($class)
    {
        // Get subjects assigned to this class
        return $this->subjects->filter(function($subject) use ($class) {
            // This would be based on curriculum assignments
            return true; // For now, all subjects
        });
    }

    protected function findBestSlot($class, $subject, $days, $timeSlots)
    {
        $bestSlot = null;
        $bestScore = 0;

        foreach ($days as $day) {
            foreach ($timeSlots as $timeSlot) {
                [$start, $end] = explode('-', $timeSlot);
                
                $availableTeachers = $this->getAvailableTeachers($subject, $day, $start, $end);
                $availableRooms = $this->getAvailableRooms($subject, $day, $start, $end);

                if ($availableTeachers->isNotEmpty() && $availableRooms->isNotEmpty()) {
                    $teacher = $availableTeachers->random();
                    $room = $availableRooms->random();

                    $slot = [
                        'day' => $day,
                        'start_time' => $start,
                        'end_time' => $end,
                        'teacher_id' => $teacher->id,
                        'room_id' => $room->id,
                    ];

                    $score = $this->calculateScore($slot);
                    
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestSlot = $slot;
                    }
                }
            }
        }

        return $bestSlot;
    }

    protected function getAvailableTeachers($subject, $day, $start, $end)
    {
        return $this->teachers->filter(function($teacher) use ($subject, $day, $start, $end) {
            // Check if teacher can teach this subject
            // Check if teacher is available at this time
            return $this->isTeacherAvailable($teacher, $day, $start, $end);
        });
    }

    protected function getAvailableRooms($subject, $day, $start, $end)
    {
        return $this->rooms->filter(function($room) use ($subject, $day, $start, $end) {
            // Check if room is suitable for this subject
            // Check if room is available at this time
            return $room->isAvailableAt($day, $start, $end);
        });
    }

    protected function isTeacherAvailable($teacher, $day, $start, $end)
    {
        // Check existing slots for this teacher
        return true; // Simplified for now
    }

    protected function checkConflicts($slot)
    {
        $conflicts = [];

        // Check teacher conflicts
        $teacherConflict = TimetableSlot::where('teacher_id', $slot['teacher_id'])
            ->where('day_of_week', $slot['day'])
            ->where('start_time', '<', $slot['end_time'])
            ->where('end_time', '>', $slot['start_time'])
            ->first();

        if ($teacherConflict) {
            $conflicts[] = 'Teacher conflict with ' . $teacherConflict->subject->name;
        }

        // Check room conflicts
        if ($slot['room_id']) {
            $roomConflict = TimetableSlot::where('room_id', $slot['room_id'])
                ->where('day_of_week', $slot['day'])
                ->where('start_time', '<', $slot['end_time'])
                ->where('end_time', '>', $slot['start_time'])
                ->first();

            if ($roomConflict) {
                $conflicts[] = 'Room conflict with ' . $roomConflict->schoolClass->name;
            }
        }

        return empty($conflicts) ? null : $conflicts;
    }

    protected function calculateScore($slot)
    {
        $score = 100;

        // Prefer morning slots for core subjects
        if ($this->settings['preferences']['prefer_morning_core_subjects']) {
            $hour = (int) substr($slot['start_time'], 0, 2);
            if ($hour >= 8 && $hour <= 11) {
                $score += 10;
            }
        }

        return $score;
    }
}

class ConflictResolver
{
    protected $timetable;

    public function __construct(Timetable $timetable)
    {
        $this->timetable = $timetable;
    }

    public function resolveAll(): array
    {
        $conflicts = $this->timetable->slots()->where('status', 'conflict')->get();
        $resolved = 0;

        foreach ($conflicts as $conflict) {
            if ($this->resolveConflict($conflict)) {
                $resolved++;
            }
        }

        return [
            'success' => true,
            'message' => "Resolved {$resolved} of {$conflicts->count()} conflicts.",
        ];
    }

    protected function resolveConflict($conflict): bool
    {
        // Try to find alternative time/teacher/room
        // This is a simplified version - real implementation would be more complex
        return false;
    }
}
