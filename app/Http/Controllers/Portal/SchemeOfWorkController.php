<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SchemeOfWork;
use App\Models\SchemeOfWorkItem;
use App\Models\Teacher;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SchemeOfWorkController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }

        $teacher = Teacher::where('school_id', $school->id)->where('user_id', $user->id)->first();

        if (! $teacher && ! $user->hasRole('super-admin')) {
            abort(403);
        }

        $schemes = SchemeOfWork::with(['subject', 'schoolClass', 'items'])
            ->where('school_id', $school->id)
            ->where('teacher_id', $teacher?->id ?? 0)
            ->latest()
            ->paginate(15);

        return view('portal.teacher.schemes-of-work.index', [
            'school' => $school,
            'user' => $user,
            'schemes' => $schemes,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }

        $teacher = Teacher::where('school_id', $school->id)->where('user_id', $user->id)->first();

        $subjects = $school->subjects()->get();
        $classes = $school->classes()->get();
        $terms = ['Term 1', 'Term 2', 'Term 3'];
        $currentYear = date('Y');

        return view('portal.teacher.schemes-of-work.create', [
            'school' => $school,
            'user' => $user,
            'teacher' => $teacher,
            'subjects' => $subjects,
            'classes' => $classes,
            'terms' => $terms,
            'currentYear' => $currentYear,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }

        $teacher = Teacher::where('school_id', $school->id)->where('user_id', $user->id)->first();

        if (! $teacher) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'general_topic' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'aims' => 'nullable|string',
            'syllabus_reference' => 'nullable|string|max:255',
            'cross_cutting_themes' => 'nullable|array',
            'cross_cutting_themes.*' => 'string',
            'subject_id' => ['required', TenantExists::make('subjects')],
            'school_class_id' => ['required', TenantExists::make('classes')],
            'academic_year' => 'required|string',
            'term' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.week_number' => 'required|integer|min:1',
            'items.*.week_ending' => 'nullable|date',
            'items.*.day_of_week' => 'nullable|string',
            'items.*.topic' => 'required|string|max:255',
            'items.*.sub_topic' => 'nullable|string|max:255',
            'items.*.objectives' => 'required|string',
            'items.*.competencies_skills' => 'nullable|string',
            'items.*.som_media' => 'nullable|string',
            'items.*.facility_equipment' => 'nullable|string',
            'items.*.methods_activities' => 'nullable|string',
            'items.*.teaching_methods' => 'nullable|string',
            'items.*.resources' => 'nullable|string',
            'items.*.assessment' => 'nullable|string',
            'items.*.evaluation' => 'nullable|string',
            'items.*.remarks' => 'nullable|string',
        ]);

        $scheme = DB::transaction(function () use ($validated, $school, $teacher, $request) {
            $scheme = SchemeOfWork::create([
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $validated['subject_id'],
                'school_class_id' => $validated['school_class_id'],
                'title' => $validated['title'],
                'general_topic' => $validated['general_topic'] ?? null,
                'description' => $validated['description'] ?? null,
                'aims' => $validated['aims'] ?? null,
                'syllabus_reference' => $validated['syllabus_reference'] ?? null,
                'cross_cutting_themes' => $validated['cross_cutting_themes'] ?? null,
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'status' => $request->input('action') === 'preview' ? 'preview' : 'draft',
            ]);

            foreach ($validated['items'] as $index => $item) {
                $scheme->items()->create([
                    'week_number' => $item['week_number'],
                    'week_ending' => $item['week_ending'] ?? null,
                    'day_of_week' => $item['day_of_week'] ?? 'Friday',
                    'topic' => $item['topic'],
                    'sub_topic' => $item['sub_topic'] ?? null,
                    'objectives' => $item['objectives'],
                    'competencies_skills' => $item['competencies_skills'] ?? null,
                    'som_media' => $item['som_media'] ?? $item['resources'] ?? null,
                    'facility_equipment' => $item['facility_equipment'] ?? null,
                    'methods_activities' => $item['methods_activities'] ?? $item['teaching_methods'] ?? null,
                    'teaching_methods' => $item['teaching_methods'] ?? $item['methods_activities'] ?? null,
                    'resources' => $item['resources'] ?? $item['som_media'] ?? null,
                    'assessment' => $item['assessment'] ?? null,
                    'evaluation' => $item['evaluation'] ?? $item['remarks'] ?? null,
                    'remarks' => $item['remarks'] ?? $item['evaluation'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $scheme;
        });

        $action = $request->input('action');
        if ($action === 'preview') {
            return redirect()->route('teacher.schemes-of-work.preview', $scheme)
                ->with('status', 'Scheme of work created. Review before submitting.');
        }

        return redirect()->route('teacher.schemes-of-work.index')
            ->with('status', 'Scheme of work saved as draft.');
    }

    public function show(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        $schemeOfWork->load(['subject', 'schoolClass', 'items', 'teacher']);

        return view('portal.teacher.schemes-of-work.show', [
            'school' => $school,
            'user' => $user,
            'scheme' => $schemeOfWork,
        ]);
    }

    public function edit(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        if (! in_array($schemeOfWork->status, ['draft', 'rejected'])) {
            abort(403, 'Only draft or rejected schemes can be edited.');
        }

        $schemeOfWork->load(['items']);

        $subjects = $school->subjects()->get();
        $classes = $school->classes()->get();
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('portal.teacher.schemes-of-work.edit', [
            'school' => $school,
            'user' => $user,
            'scheme' => $schemeOfWork,
            'subjects' => $subjects,
            'classes' => $classes,
            'terms' => $terms,
        ]);
    }

    public function update(Request $request, SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        if (! in_array($schemeOfWork->status, ['draft', 'rejected'])) {
            abort(403, 'Only draft or rejected schemes can be updated.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'general_topic' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'aims' => 'nullable|string',
            'syllabus_reference' => 'nullable|string|max:255',
            'cross_cutting_themes' => 'nullable|array',
            'cross_cutting_themes.*' => 'string',
            'subject_id' => ['required', TenantExists::make('subjects')],
            'school_class_id' => ['required', TenantExists::make('classes')],
            'academic_year' => 'required|string',
            'term' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.week_number' => 'required|integer|min:1',
            'items.*.week_ending' => 'nullable|date',
            'items.*.day_of_week' => 'nullable|string',
            'items.*.topic' => 'required|string|max:255',
            'items.*.sub_topic' => 'nullable|string|max:255',
            'items.*.objectives' => 'required|string',
            'items.*.competencies_skills' => 'nullable|string',
            'items.*.som_media' => 'nullable|string',
            'items.*.facility_equipment' => 'nullable|string',
            'items.*.methods_activities' => 'nullable|string',
            'items.*.teaching_methods' => 'nullable|string',
            'items.*.resources' => 'nullable|string',
            'items.*.assessment' => 'nullable|string',
            'items.*.evaluation' => 'nullable|string',
            'items.*.remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $schemeOfWork, $request) {
            $schemeOfWork->update([
                'title' => $validated['title'],
                'general_topic' => $validated['general_topic'] ?? null,
                'description' => $validated['description'] ?? null,
                'aims' => $validated['aims'] ?? null,
                'syllabus_reference' => $validated['syllabus_reference'] ?? null,
                'cross_cutting_themes' => $validated['cross_cutting_themes'] ?? null,
                'subject_id' => $validated['subject_id'],
                'school_class_id' => $validated['school_class_id'],
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'status' => $request->input('action') === 'preview' ? 'preview' : 'draft',
            ]);

            $schemeOfWork->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                $schemeOfWork->items()->create([
                    'week_number' => $item['week_number'],
                    'week_ending' => $item['week_ending'] ?? null,
                    'day_of_week' => $item['day_of_week'] ?? 'Friday',
                    'topic' => $item['topic'],
                    'sub_topic' => $item['sub_topic'] ?? null,
                    'objectives' => $item['objectives'],
                    'competencies_skills' => $item['competencies_skills'] ?? null,
                    'som_media' => $item['som_media'] ?? $item['resources'] ?? null,
                    'facility_equipment' => $item['facility_equipment'] ?? null,
                    'methods_activities' => $item['methods_activities'] ?? $item['teaching_methods'] ?? null,
                    'teaching_methods' => $item['teaching_methods'] ?? $item['methods_activities'] ?? null,
                    'resources' => $item['resources'] ?? $item['som_media'] ?? null,
                    'assessment' => $item['assessment'] ?? null,
                    'evaluation' => $item['evaluation'] ?? $item['remarks'] ?? null,
                    'remarks' => $item['remarks'] ?? $item['evaluation'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });

        $action = $request->input('action');
        if ($action === 'preview') {
            return redirect()->route('teacher.schemes-of-work.preview', $schemeOfWork)
                ->with('status', 'Scheme updated. Review before submitting.');
        }

        return redirect()->route('teacher.schemes-of-work.index')
            ->with('status', 'Scheme of work updated.');
    }

    public function preview(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        $schemeOfWork->load(['subject', 'schoolClass', 'items', 'teacher']);

        return view('portal.teacher.schemes-of-work.preview', [
            'school' => $school,
            'user' => $user,
            'scheme' => $schemeOfWork,
        ]);
    }

    public function print(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        $schemeOfWork->load(['subject', 'schoolClass', 'items', 'teacher', 'reviewer']);

        return view('portal.teacher.schemes-of-work.print', [
            'school' => $school,
            'user' => $user,
            'scheme' => $schemeOfWork,
        ]);
    }

    public function submit(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        if (! in_array($schemeOfWork->status, ['draft', 'preview', 'rejected'])) {
            abort(403, 'This scheme cannot be submitted.');
        }

        $schemeOfWork->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'review_notes' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        return redirect()->route('teacher.schemes-of-work.index')
            ->with('status', 'Scheme of work submitted for review.');
    }

    public function destroy(SchemeOfWork $schemeOfWork)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $schemeOfWork->school_id !== $school->id) {
            abort(403);
        }

        if ($schemeOfWork->status === 'submitted') {
            abort(403, 'Cannot delete a submitted scheme.');
        }

        $schemeOfWork->delete();

        return redirect()->route('teacher.schemes-of-work.index')
            ->with('status', 'Scheme of work deleted.');
    }
}
