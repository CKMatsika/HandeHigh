<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FlashCardSet;
use App\Models\FlashCardItem;
use App\Models\FlashCardStudySession;
use App\Models\FlashCardItemResult;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentFlashCardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || !$user->hasRole(['student', 'super-admin'])) {
            abort(403);
        }

        $student = null;
        $enrollment = null;
        if ($user->hasRole('student')) {
            $student = $school->students()->where('user_id', $user->id)->first();
            if (!$student) abort(403);
            $enrollment = $student->enrollments()->with('class')->first();
        }

        $sets = FlashCardSet::with(['subject', 'teacher.user', 'items'])
            ->published()
            ->where('school_id', $school->id)
            ->when($enrollment?->class_id, fn($q) => $q->where(function ($q) use ($enrollment) {
                $q->whereNull('school_class_id')->orWhere('school_class_id', $enrollment->class_id);
            }))
            ->latest()
            ->get();

        $previousSessions = FlashCardStudySession::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->with('flashCardSet')
            ->latest()
            ->limit(10)
            ->get();

        return view('portal.student.flash-cards.index', compact('school', 'user', 'sets', 'previousSessions'));
    }

    public function study(FlashCardSet $flashCardSet)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $flashCardSet->school_id !== $school->id || !$flashCardSet->isPublished()) {
            abort(404);
        }

        if (!$user->hasRole(['student', 'super-admin'])) {
            abort(403);
        }

        $flashCardSet->load('items');

        return view('portal.student.flash-cards.study', [
            'school' => $school,
            'user' => $user,
            'set' => $flashCardSet,
        ]);
    }

    public function startSession(Request $request, FlashCardSet $flashCardSet)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $flashCardSet->school_id !== $school->id || !$flashCardSet->isPublished()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (!$user->hasRole(['student', 'super-admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $session = FlashCardStudySession::create([
            'user_id' => $user->id,
            'flash_card_set_id' => $flashCardSet->id,
            'started_at' => now(),
        ]);

        return response()->json(['session_id' => $session->id]);
    }

    public function submitResult(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || !$user->hasRole(['student', 'super-admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'session_id' => 'required|exists:flash_card_study_sessions,id',
            'item_id' => 'required|exists:flash_card_items,id',
            'confidence' => 'required|in:know,unsure,dont_know',
        ]);

        $session = FlashCardStudySession::with('flashCardSet')->findOrFail($validated['session_id']);

        if ($session->user_id !== $user->id || $session->flashCardSet?->school_id !== $school->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $itemExists = FlashCardItem::where('flash_card_set_id', $session->flash_card_set_id)
            ->where('id', $validated['item_id'])
            ->exists();

        if (!$itemExists) {
            return response()->json(['error' => 'Invalid item'], 422);
        }

        $result = FlashCardItemResult::updateOrCreate(
            [
                'study_session_id' => $session->id,
                'flash_card_item_id' => $validated['item_id'],
            ],
            [
                'confidence' => $validated['confidence'],
                'reviewed_at' => now(),
            ]
        );

        $session->increment('cards_studied');
        if ($validated['confidence'] === 'know') {
            $session->increment('cards_confident');
        }

        return response()->json(['success' => true]);
    }

    public function completeSession(Request $request, FlashCardStudySession $session)
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id || $session->flashCardSet?->school_id !== $user->school_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $session->update(['completed_at' => now()]);

        return response()->json([
            'success' => true,
            'cards_studied' => $session->cards_studied,
            'cards_confident' => $session->cards_confident,
        ]);
    }
}
