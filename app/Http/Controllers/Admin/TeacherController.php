<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Qualification;
use App\Models\Subject;
use App\Models\StaffPosition;
use App\Models\StaffPositionAssignment;
use App\Models\SchoolClass;
use App\Models\Curriculum;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $school = $request->user()?->school;
        if (!$school) abort(403);

        $query = Teacher::where('school_id', $school->id)->with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('employee_id', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active');
        }

        if ($request->filled('specialization')) {
            $query->where('specialization', $request->specialization);
        }

        $teachers = $query->latest()->paginate(15);
        $specializations = Teacher::where('school_id', $school->id)
            ->whereNotNull('specialization')
            ->distinct()->pluck('specialization');

        return view('admin.teachers.index', compact('school', 'teachers', 'specializations'));
    }

    public function create(Request $request)
    {
        $school = $request->user()?->school;
        if (!$school) abort(403);

        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $positions = StaffPosition::where('school_id', $school->id)->where('is_active', true)->get();

        return view('admin.teachers.create', compact('school', 'subjects', 'positions'));
    }

    public function store(Request $request)
    {
        $school = $request->user()?->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:70'],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:teachers,employee_id'],
            'password' => ['required', Password::defaults()],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => [TenantExists::make('subjects')],
            'positions' => ['nullable', 'array'],
            'positions.*' => [TenantExists::make('staff_positions')],
        ]);

        try {
            DB::beginTransaction();

            $employeeId = $validated['employee_id'] ?? 'TCH-' . strtoupper(\Str::random(6));

            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'school_id' => $school->id,
                'password' => Hash::make($validated['password']),
            ]);
            $user->assignRole('teacher');

            $teacher = Teacher::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'experience_years' => $validated['experience_years'] ?? 0,
                'hire_date' => $validated['hire_date'] ?? null,
                'salary' => $validated['salary'] ?? null,
                'status' => true,
            ]);

            if (!empty($validated['subjects'])) {
                $teacher->subjects()->attach($validated['subjects']);
            }

            if (!empty($validated['positions'])) {
                foreach ($validated['positions'] as $positionId) {
                    StaffPositionAssignment::create([
                        'school_id' => $school->id,
                        'staff_position_id' => $positionId,
                        'assignable_id' => $teacher->id,
                        'assignable_type' => Teacher::class,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.teachers.show', $teacher)
                ->with('success', 'Teacher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create teacher: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $tab = $request->query('tab', 'overview');

        $teacher->load([
            'user',
            'subjects',
            'qualifications',
            'activePositionAssignments.position',
            'activePositionAssignments.target',
        ]);

        $classes = SchoolClass::where('school_id', $school->id)
            ->where('teacher_id', $teacher->user_id)
            ->with('enrollments')
            ->get();

        $subjectCurricula = Curriculum::where('school_id', $school->id)
            ->where('teacher_id', $teacher->user_id)
            ->with(['class', 'subject'])
            ->get();

        $allSubjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $availablePositions = StaffPosition::where('school_id', $school->id)
            ->where('is_active', true)->get();
        $allClasses = SchoolClass::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.teachers.show', compact(
            'school', 'teacher', 'tab', 'classes', 'subjectCurricula',
            'allSubjects', 'availablePositions', 'allClasses'
        ));
    }

    public function edit(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $teacher->load('user', 'subjects');
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.teachers.edit', compact('school', 'teacher', 'subjects'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $teacher->user_id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:70'],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:teachers,employee_id,' . $teacher->id],
            'password' => ['nullable', Password::defaults()],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => [TenantExists::make('subjects')],
        ]);

        try {
            DB::beginTransaction();

            $teacher->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'experience_years' => $validated['experience_years'] ?? 0,
                'hire_date' => $validated['hire_date'] ?? null,
                'salary' => $validated['salary'] ?? null,
                'employee_id' => $validated['employee_id'] ?? $teacher->employee_id,
            ]);

            if ($teacher->user) {
                $userData = [
                    'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                ];
                $teacher->user->update($userData);

                if (!empty($validated['password'])) {
                    $teacher->user->password = Hash::make($validated['password']);
                    $teacher->user->save();
                }
            }

            if (isset($validated['subjects'])) {
                $teacher->subjects()->sync($validated['subjects']);
            }

            DB::commit();

            return redirect()->route('admin.teachers.show', $teacher)
                ->with('success', 'Teacher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update teacher: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $hasClass = SchoolClass::where('school_id', $school->id)
            ->where('teacher_id', $teacher->user_id)->exists();
        $hasCurricula = Curriculum::where('school_id', $school->id)
            ->where('teacher_id', $teacher->user_id)->exists();

        if ($hasClass || $hasCurricula) {
            return back()->with('error', 'Cannot delete teacher. They have assigned classes or subjects.');
        }

        try {
            DB::beginTransaction();
            $user = $teacher->user;
            $teacher->subjects()->detach();
            $teacher->qualifications()->delete();
            $teacher->positionAssignments()->delete();
            $teacher->delete();
            if ($user) $user->delete();
            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete teacher.');
        }
    }

    // --- Tab Management Methods ---

    public function addQualification(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'grade' => 'nullable|string|max:50',
            'year_start' => 'nullable|integer|min:1900|max:2099',
            'year_end' => 'nullable|integer|min:1900|max:2099',
            'notes' => 'nullable|string|max:500',
        ]);

        Qualification::create([
            'school_id' => $school->id,
            'qualifiable_id' => $teacher->id,
            'qualifiable_type' => Teacher::class,
            ...$validated,
        ]);

        return back()->with('success', 'Qualification added.');
    }

    public function deleteQualification(Request $request, Teacher $teacher, Qualification $qualification)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);
        if ($qualification->qualifiable_id !== $teacher->id || $qualification->qualifiable_type !== Teacher::class) abort(403);

        $qualification->delete();
        return back()->with('success', 'Qualification deleted.');
    }

    public function assignSubjects(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'subjects' => 'required|array',
            'subjects.*' => [TenantExists::make('subjects')],
        ]);

        $teacher->subjects()->sync($validated['subjects']);
        return back()->with('success', 'Subjects updated.');
    }

    public function addRole(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'staff_position_id' => ['required', TenantExists::make('staff_positions')],
            'target_id' => 'nullable|string',
            'target_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $position = StaffPosition::findOrFail($validated['staff_position_id']);

        StaffPositionAssignment::create([
            'school_id' => $school->id,
            'staff_position_id' => $position->id,
            'assignable_id' => $teacher->id,
            'assignable_type' => Teacher::class,
            'target_id' => $validated['target_id'] ?? null,
            'target_type' => $validated['target_type'] ?? null,
            'start_date' => $validated['start_date'] ?? now(),
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Role assigned.');
    }

    public function removeRole(Request $request, Teacher $teacher, StaffPositionAssignment $assignment)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);
        if ($assignment->assignable_id !== $teacher->id || $assignment->assignable_type !== Teacher::class) abort(403);

        $assignment->update(['is_active' => false, 'end_date' => now()]);
        return back()->with('success', 'Role removed.');
    }

    public function assignClassTeacher(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'class_id' => ['required', TenantExists::make('classes')],
        ]);

        $class = SchoolClass::where('school_id', $school->id)->findOrFail($validated['class_id']);
        $class->update(['teacher_id' => $teacher->user_id]);

        return back()->with('success', 'Teacher assigned as class teacher.');
    }

    public function removeClassTeacher(Request $request, Teacher $teacher, SchoolClass $class)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id || $class->school_id !== $school->id) abort(403);

        $class->update(['teacher_id' => null]);
        return back()->with('success', 'Class teacher assignment removed.');
    }

    public function addTeachingAssignment(Request $request, Teacher $teacher)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'class_id' => ['required', TenantExists::make('classes')],
            'subject_id' => ['required', TenantExists::make('subjects')],
            'weekly_periods' => 'nullable|integer|min:1|max:40',
            'term' => 'nullable|string|max:50',
            'academic_year' => 'nullable|string|max:20',
        ]);

        $class = SchoolClass::where('school_id', $school->id)->findOrFail($validated['class_id']);

        $exists = Curriculum::where('school_id', $school->id)
            ->where('class_id', $class->id)
            ->where('subject_id', $validated['subject_id'])
            ->where('teacher_id', $teacher->user_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This teacher is already assigned to teach this subject in this class.');
        }

        Curriculum::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $teacher->user_id,
            'weekly_periods' => $validated['weekly_periods'] ?? 4,
            'term' => $validated['term'] ?? 'Term 1',
            'academic_year' => $validated['academic_year'] ?? date('Y'),
        ]);

        return back()->with('success', 'Teaching assignment added.');
    }

    public function removeTeachingAssignment(Request $request, Teacher $teacher, Curriculum $curriculum)
    {
        $school = $request->user()?->school;
        if (!$school || $teacher->school_id !== $school->id) abort(403);
        if ($curriculum->teacher_id !== $teacher->user_id) abort(403);

        $curriculum->delete();
        return back()->with('success', 'Teaching assignment removed.');
    }
}
