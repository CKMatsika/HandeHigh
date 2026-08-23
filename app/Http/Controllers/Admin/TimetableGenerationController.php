<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\GenerateTimetableRequest;
use App\Http\Requests\Timetable\SimulateGenerationRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableCandidate;
use App\Services\Timetable\Candidate\TimetableCandidateService;
use App\Services\Timetable\Candidate\TimetableComparisonService;
use App\Services\Timetable\Candidate\TimetableSimulationService;
use App\Services\Timetable\Generation\TimetableGenerator;
use App\Services\Timetable\Optimization\TimetableScorer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimetableGenerationController extends Controller
{
    public function __construct(
        protected TimetableGenerator $generator,
        protected TimetableCandidateService $candidateService,
        protected TimetableComparisonService $comparisonService,
        protected TimetableSimulationService $simulationService
    ) {
    }

    public function showGenerate(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        $school = auth()->user()->school;

        $classes = SchoolClass::where('school_id', $school->id)->orderBy('grade')->orderBy('name')->get();
        $teachers = Teacher::where('school_id', $school->id)->active()->orderBy('first_name')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $defaultWeights = TimetableScorer::DEFAULT_WEIGHTS;

        $recentRuns = $timetable->generationRuns()->withCount('candidates')->latest()->take(5)->get();

        return view('admin.timetables.generate', compact(
            'timetable',
            'classes',
            'teachers',
            'subjects',
            'defaultWeights',
            'recentRuns'
        ));
    }

    public function generate(Timetable $timetable, GenerateTimetableRequest $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $validated = $request->validated();
        $validated['preserve_locked'] = $request->boolean('preserve_locked', true);

        $result = $this->generator->generate($timetable, $validated, auth()->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Generation completed successfully.',
                'run' => $result['run'],
                'candidates' => $result['candidates'],
                'results' => array_map(fn ($r) => $r->toArray(), $result['results']),
            ]);
        }

        return redirect()->route('admin.timetables.candidates.index', $timetable)
            ->with('success', 'Generated ' . count($result['candidates']) . ' candidate schedule(s) successfully.');
    }

    public function simulate(Timetable $timetable, SimulateGenerationRequest $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $validated = $request->validated();
        $validated['preserve_locked'] = $request->boolean('preserve_locked', true);

        if (! empty($validated['candidate_id'])) {
            $candidate = TimetableCandidate::where('school_id', $timetable->school_id)
                ->where('timetable_id', $timetable->id)
                ->findOrFail($validated['candidate_id']);
            $simResult = $this->simulationService->simulateCandidate($timetable, $candidate);
        } else {
            $simResult = $this->simulationService->simulateGeneration($timetable, $validated);
        }

        return response()->json([
            'success' => true,
            'simulation' => $simResult,
        ]);
    }

    public function candidates(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        $candidates = $timetable->candidates()->with('generationRun')->latest('score')->get();
        $comparison = $this->comparisonService->compare($timetable, $candidates);

        return view('admin.timetables.candidates', compact('timetable', 'candidates', 'comparison'));
    }

    public function showCandidate(Timetable $timetable, TimetableCandidate $candidate)
    {
        $this->authorizeSchoolAccess($timetable);

        if ((int) $candidate->timetable_id !== (int) $timetable->id || (int) $candidate->school_id !== (int) $timetable->school_id) {
            abort(404);
        }

        $comparison = $this->comparisonService->compare($timetable, [$candidate]);

        return view('admin.timetables.candidate_show', compact('timetable', 'candidate', 'comparison'));
    }

    public function compare(Timetable $timetable, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $candidateIds = $request->input('candidate_ids', []);
        $query = $timetable->candidates();
        if (! empty($candidateIds) && is_array($candidateIds)) {
            $query->whereIn('id', $candidateIds);
        }

        $candidates = $query->get();
        $comparison = $this->comparisonService->compare($timetable, $candidates);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comparison' => $comparison,
            ]);
        }

        return view('admin.timetables.candidates', compact('timetable', 'candidates', 'comparison'));
    }

    public function applyCandidate(Timetable $timetable, TimetableCandidate $candidate, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        try {
            $result = $this->candidateService->applyCandidate($timetable, $candidate, auth()->user());

            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return redirect()->route('admin.timetables.show', $timetable)
                ->with('success', "Candidate #{$candidate->candidate_number} (Score: {$candidate->score}) applied successfully to timetable!");
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }

            return redirect()->route('admin.timetables.candidates.show', [$timetable, $candidate])
                ->withErrors($e->validator);
        }
    }

    protected function authorizeSchoolAccess(Timetable $timetable): void
    {
        $school = auth()->user()?->school;
        if (! $school || (int) $timetable->school_id !== (int) $school->id) {
            abort(403);
        }
    }
}
