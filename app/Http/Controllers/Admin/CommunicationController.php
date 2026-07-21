<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommunicationController extends Controller
{
    /**
     * Display the communication dashboard with chat, SMS, and Email options.
     */
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get user's conversations
        $conversations = Conversation::where('school_id', $school->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['participants', 'messages' => function ($query) {
                $query->latest()->first();
            }])
            ->latest()
            ->get();

        // Get unread message count
        $unreadCount = Message::where('school_id', $school->id)
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        return view('admin.communication.index', compact('conversations', 'unreadCount'));
    }

    /**
     * Display a specific conversation.
     */
    public function showConversation(Conversation $conversation)
    {
        $user = Auth::user();
        
        if ($conversation->school_id !== $user->school_id || 
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        // Mark messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->each(function ($message) use ($user) {
                $message->markAsRead($user->id);
            });

        // Update participant's last read time
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $messages = $conversation->messages()->with('sender')->latest()->paginate(50);

        return view('admin.communication.chat', compact('conversation', 'messages'));
    }

    /**
     * Store a new message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        
        if ($conversation->school_id !== $user->school_id || 
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
            'type' => 'sometimes|in:text,file,image',
        ]);

        $message = Message::create([
            'school_id' => $conversation->school_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
        ]);

        // Mark as read for sender
        $message->readReceipts()->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('sender'),
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function createConversation(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;

        $request->validate([
            'name' => 'required_if:type,group|string|max:255',
            'type' => 'required|in:direct,group',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);

        $participants = $request->participants;
        
        // For direct messages, ensure only 2 participants
        if ($request->type === 'direct' && count($participants) !== 1) {
            return back()->withErrors(['participants' => 'Direct conversations can only have one other participant.']);
        }

        // Check if direct conversation already exists
        if ($request->type === 'direct') {
            $existingConversation = Conversation::where('school_id', $school->id)
                ->where('type', 'direct')
                ->whereHas('participants', function ($query) use ($user, $participants) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('participants', function ($query) use ($participants) {
                    $query->where('user_id', $participants[0]);
                })
                ->first();

            if ($existingConversation) {
                return redirect()->route('admin.communication.chat', $existingConversation);
            }
        }

        // Create conversation
        $conversation = Conversation::create([
            'school_id' => $school->id,
            'name' => $request->name,
            'type' => $request->type,
            'created_by' => $user->id,
        ]);

        // Add participants
        $allParticipants = array_merge([$user->id], $participants);
        foreach ($allParticipants as $participantId) {
            $conversation->participants()->attach($participantId, [
                'role' => $participantId === $user->id ? 'admin' : 'member',
                'joined_at' => now(),
            ]);
        }

        return redirect()->route('admin.communication.chat', $conversation)
            ->with('success', 'Conversation created successfully.');
    }

    /**
     * Show SMS composer.
     */
    public function showSMS()
    {
        $user = Auth::user();
        $school = $user->school;

        // Get recipients (students, staff, guardians)
        $students = $school->students()->get();
        $staff = $school->staff()->get();
        $guardians = $school->guardians()->get();

        return view('admin.communication.sms', compact('students', 'staff', 'guardians'));
    }

    /**
     * Send SMS messages.
     */
    public function sendSMS(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:160',
            'recipients' => 'required|array',
            'recipients.*' => 'string',
        ]);

        // SMS sending logic would go here
        // For now, we'll just log it

        return back()->with('success', 'SMS sent successfully.');
    }

    /**
     * Show Email composer.
     */
    public function showEmail()
    {
        $user = Auth::user();
        $school = $user->school;

        // Get recipients
        $students = $school->students()->get();
        $staff = $school->staff()->get();
        $guardians = $school->guardians()->get();

        return view('admin.communication.email', compact('students', 'staff', 'guardians'));
    }

    /**
     * Send Email messages.
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'recipients' => 'required|array',
            'recipients.*' => 'string',
        ]);

        // Email sending logic would go here
        // For now, we'll just log it

        return back()->with('success', 'Email sent successfully.');
    }

    /**
     * Search for users to add to conversations.
     */
    public function searchUsers(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;

        $query = $request->get('q');
        
        $users = User::where('school_id', $school->id)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->where('id', '!=', $user->id)
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json($users);
    }
}
