<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FlashCardSet;
use App\Models\FlashCardItem;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlashCardController extends Controller
{
    private function getTeacher()
    {
        $user = Auth::user();
        $school = $user?->school;
        if (!$school || !$user->hasRole(['teacher', 'super-admin'])) {
            abort(403);
        }
        $teacher = Teacher::where('school_id', $school->id)->where('user_id', $user->id)->first();
        if (!$teacher && !$user->hasRole('super-admin')) {
            abort(403);
        }
        return [$user, $school, $teacher];
    }

    public function index()
    {
        [$user, $school, $teacher] = $this->getTeacher();

        $sets = FlashCardSet::with(['subject', 'schoolClass'])
            ->withCount('items')
            ->where('school_id', $school->id)
            ->where('teacher_id', $teacher?->id ?? 0)
            ->latest()
            ->paginate(15);

        return view('portal.teacher.flash-cards.index', compact('school', 'user', 'sets'));
    }

    public function create()
    {
        [$user, $school, $teacher] = $this->getTeacher();

        $subjects = $school->subjects()->get();
        $classes = $school->classes()->get();

        return view('portal.teacher.flash-cards.create', compact('school', 'user', 'teacher', 'subjects', 'classes'));
    }

    public function store(Request $request)
    {
        [$user, $school, $teacher] = $this->getTeacher();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'school_class_id' => 'nullable|exists:classes,id',
            'items' => 'required|array|min:1',
            'items.*.front_text' => 'required|string|max:500',
            'items.*.back_text' => 'required|string|max:500',
            'items.*.hint' => 'nullable|string|max:200',
        ]);

        $set = DB::transaction(function () use ($validated, $school, $teacher, $request) {
            $set = FlashCardSet::create([
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $validated['subject_id'] ?? null,
                'school_class_id' => $validated['school_class_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $request->input('action') === 'publish' ? 'published' : 'draft',
                'source_type' => 'manual',
            ]);

            foreach ($validated['items'] as $index => $item) {
                $set->items()->create([
                    'front_text' => $item['front_text'],
                    'back_text' => $item['back_text'],
                    'hint' => $item['hint'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $set;
        });

        return redirect()->route('teacher.flash-cards.show', $set)
            ->with('status', 'Flash card set created.');
    }

    public function show(FlashCardSet $flashCardSet)
    {
        [$user, $school] = $this->getTeacher();

        if ($flashCardSet->school_id !== $school->id) {
            abort(403);
        }

        $flashCardSet->load(['items', 'subject', 'schoolClass']);

        return view('portal.teacher.flash-cards.show', [
            'school' => $school,
            'user' => $user,
            'set' => $flashCardSet,
        ]);
    }

    public function edit(FlashCardSet $flashCardSet)
    {
        [$user, $school] = $this->getTeacher();

        if ($flashCardSet->school_id !== $school->id) {
            abort(403);
        }

        $flashCardSet->load('items');
        $subjects = $school->subjects()->get();
        $classes = $school->classes()->get();

        return view('portal.teacher.flash-cards.edit', [
            'school' => $school,
            'user' => $user,
            'set' => $flashCardSet,
            'subjects' => $subjects,
            'classes' => $classes,
        ]);
    }

    public function update(Request $request, FlashCardSet $flashCardSet)
    {
        [$user, $school] = $this->getTeacher();

        if ($flashCardSet->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'school_class_id' => 'nullable|exists:classes,id',
            'items' => 'required|array|min:1',
            'items.*.front_text' => 'required|string|max:500',
            'items.*.back_text' => 'required|string|max:500',
            'items.*.hint' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($validated, $flashCardSet, $request) {
            $flashCardSet->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'school_class_id' => $validated['school_class_id'] ?? null,
                'status' => $request->input('action') === 'publish' ? 'published' : $flashCardSet->status,
            ]);

            $flashCardSet->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                $flashCardSet->items()->create([
                    'front_text' => $item['front_text'],
                    'back_text' => $item['back_text'],
                    'hint' => $item['hint'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()->route('teacher.flash-cards.show', $flashCardSet)
            ->with('status', 'Flash card set updated.');
    }

    public function publish(FlashCardSet $flashCardSet)
    {
        [$user, $school] = $this->getTeacher();

        if ($flashCardSet->school_id !== $school->id) {
            abort(403);
        }

        $flashCardSet->update(['status' => 'published']);

        return redirect()->route('teacher.flash-cards.show', $flashCardSet)
            ->with('status', 'Flash card set published.');
    }

    public function destroy(FlashCardSet $flashCardSet)
    {
        [$user, $school] = $this->getTeacher();

        if ($flashCardSet->school_id !== $school->id) {
            abort(403);
        }

        $flashCardSet->delete();

        return redirect()->route('teacher.flash-cards.index')
            ->with('status', 'Flash card set deleted.');
    }

    public function generate(Request $request)
    {
        [$user, $school, $teacher] = $this->getTeacher();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'school_class_id' => 'nullable|exists:classes,id',
            'topic' => 'required|string|max:255',
        ]);

        $topic = $validated['topic'];
        $items = [];

        $lines = explode("\n", $topic);
        $words = preg_split('/[\s,;.!?-]+/', $topic);
        $words = array_filter($words, fn($w) => strlen($w) > 3);
        $words = array_slice(array_values($words), 0, 12);

        if (count($words) >= 2) {
            foreach (array_chunk($words, 2) as $i => $pair) {
                if (count($pair) < 2) break;
                $items[] = [
                    'front_text' => 'What is ' . $pair[0] . '?',
                    'back_text' => $pair[0] . ' relates to ' . $pair[1] . ' in the context of ' . $topic . '.',
                    'hint' => 'Think about ' . $pair[1],
                ];
            }
        }

        if (empty($items)) {
            $items[] = [
                'front_text' => 'Define ' . $topic,
                'back_text' => $topic . ' — review your scheme of work for detailed content.',
                'hint' => 'Check your lesson notes',
            ];
        }

        $set = DB::transaction(function () use ($validated, $school, $teacher, $items) {
            $set = FlashCardSet::create([
                'school_id' => $school->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $validated['subject_id'] ?? null,
                'school_class_id' => $validated['school_class_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? 'AI-generated from topic: ' . $validated['topic'],
                'status' => 'draft',
                'source_type' => 'ai',
            ]);

            foreach ($items as $index => $item) {
                $set->items()->create([
                    'front_text' => $item['front_text'],
                    'back_text' => $item['back_text'],
                    'hint' => $item['hint'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $set;
        });

        return redirect()->route('teacher.flash-cards.show', $set)
            ->with('status', 'Flash cards generated from topic.');
    }
}
