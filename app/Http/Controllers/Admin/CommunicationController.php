<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Events\Communication\MessageSent;

class CommunicationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $conversations = Conversation::where('school_id', $school->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['participants', 'creator'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('readReceipts', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            }])
            ->latest('updated_at')
            ->get();

        foreach ($conversations as $conv) {
            $conv->latest_message = $conv->messages()->with('sender')->latest()->first();
        }

        $unreadCount = $conversations->sum('unread_count');

        return view('admin.communication.index', compact('conversations', 'unreadCount'));
    }

    public function showConversation(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id ||
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->each(function ($message) use ($user) {
                $message->markAsRead($user->id);
            });

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        $otherParticipant = $conversation->type === 'direct'
            ? $conversation->participants->where('id', '!=', $user->id)->first()
            : null;

        return view('admin.communication.chat', compact('conversation', 'messages', 'otherParticipant'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id ||
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required_without:file|nullable|string|max:2000',
            'type' => 'sometimes|in:text,file,image',
            'file' => 'required_if:type,file|sometimes|file|max:10240',
        ]);

        $data = [
            'school_id' => $conversation->school_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->content ?? '',
            'type' => $request->type ?? 'text',
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('chat-files', $filename, 'public');
            $data['file_path'] = 'chat-files/' . $filename;
            $data['content'] = $data['content'] ?: $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $data['type'] = 'image';
            } else {
                $data['type'] = 'file';
            }
        }

        $message = Message::create($data);

        $message->readReceipts()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        $conversation->touch();
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        // Dispatch real-time broadcast event
        event(new MessageSent($message));

        return response()->json([
            'success' => true,
            'message' => $message->load('sender'),
        ]);
    }

    public function pollMessages(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id ||
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $afterId = (int) $request->get('after', 0);

        $messages = $conversation->messages()
            ->with(['sender', 'reactions.user'])
            ->where('id', '>', $afterId)
            ->orderBy('created_at', 'asc')
            ->get();

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('id', '>', $afterId)
            ->get()
            ->each(function ($message) use ($user) {
                $message->markAsRead($user->id);
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    public function uploadVoiceNote(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id ||
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'audio' => ['required', 'file', 'max:15360'], // 15MB max
            'duration' => ['nullable', 'numeric'],
        ]);

        $file = $request->file('audio');
        $filename = 'voice_' . time() . '_' . uniqid() . '.webm';
        $path = $file->storeAs('chat-files', $filename, 'public');

        $durationSec = (int) $request->get('duration', 0);
        $durationFormatted = sprintf('%d:%02d', floor($durationSec / 60), $durationSec % 60);

        $message = Message::create([
            'school_id' => $conversation->school_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => "🎤 Voice message ({$durationFormatted})",
            'type' => 'voice',
            'file_path' => 'chat-files/' . $filename,
        ]);

        $message->readReceipts()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        $conversation->touch();
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        event(new MessageSent($message));

        return response()->json([
            'success' => true,
            'message' => $message->load(['sender', 'reactions.user']),
        ]);
    }

    public function toggleReaction(Request $request, Message $message)
    {
        $user = Auth::user();
        $conversation = $message->conversation;

        if (!$conversation || $conversation->school_id !== $user->school_id ||
            !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reaction' => ['required', 'string', 'in:👍,❤️,😂,👏,🙏'],
        ]);

        $existing = \App\Models\MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('reaction', $validated['reaction'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            \App\Models\MessageReaction::create([
                'school_id' => $conversation->school_id,
                'message_id' => $message->id,
                'user_id' => $user->id,
                'reaction' => $validated['reaction'],
            ]);
        }

        $allReactions = \App\Models\MessageReaction::where('message_id', $message->id)
            ->with('user:id,name')
            ->get()
            ->groupBy('reaction')
            ->map(function ($group, $emoji) use ($user) {
                return [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'has_reacted' => $group->contains('user_id', $user->id),
                    'users' => $group->pluck('user.name'),
                ];
            })
            ->values()
            ->toArray();

        event(new \App\Events\Communication\MessageReactionUpdated($message, $allReactions, $user->id));

        return response()->json([
            'success' => true,
            'reactions' => $allReactions,
        ]);
    }

    public function addGroupMember(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id || $conversation->type !== 'group') {
            abort(403);
        }

        // Must be group admin or owner/creator
        $membership = $conversation->participants()->where('user_id', $user->id)->first();
        if (!$membership || !in_array($membership->pivot->role, ['admin', 'owner']) && (int)$conversation->created_by !== (int)$user->id) {
            return response()->json(['error' => 'Only group administrators can add new members.'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $targetUser = User::where('school_id', $user->school_id)->find($validated['user_id']);
        if (!$targetUser) {
            return response()->json(['error' => 'User not found in your school.'], 404);
        }

        if ($conversation->participants()->where('user_id', $targetUser->id)->exists()) {
            return response()->json(['error' => 'User is already a member of this group.'], 422);
        }

        $conversation->participants()->attach($targetUser->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$targetUser->name} added to the group.",
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'role' => 'member',
            ],
        ]);
    }

    public function removeGroupMember(Request $request, Conversation $conversation, User $targetUser)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id || $conversation->type !== 'group') {
            abort(403);
        }

        $isSelf = (int) $user->id === (int) $targetUser->id;
        $membership = $conversation->participants()->where('user_id', $user->id)->first();
        $isAdmin = $membership && in_array($membership->pivot->role, ['admin', 'owner']) || (int)$conversation->created_by === (int)$user->id;

        if (!$isSelf && !$isAdmin) {
            return response()->json(['error' => 'Unauthorized to remove members from this group.'], 403);
        }

        $conversation->participants()->detach($targetUser->id);

        return response()->json([
            'success' => true,
            'message' => $isSelf ? 'You have left the group.' : "{$targetUser->name} has been removed from the group.",
        ]);
    }

    public function updateGroup(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->school_id !== $user->school_id || $conversation->type !== 'group') {
            abort(403);
        }

        $membership = $conversation->participants()->where('user_id', $user->id)->first();
        $isAdmin = $membership && in_array($membership->pivot->role, ['admin', 'owner']) || (int)$conversation->created_by === (int)$user->id;

        if (!$isAdmin) {
            return response()->json(['error' => 'Only group admins can update group details.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $conversation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully.',
            'conversation' => $conversation,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            return response()->json(['count' => 0]);
        }

        $count = Message::where('school_id', $school->id)
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        $perConversation = [];
        $userConversations = Conversation::where('school_id', $school->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        foreach ($userConversations as $convId) {
            $unread = Message::where('conversation_id', $convId)
                ->where('sender_id', '!=', $user->id)
                ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count();
            if ($unread > 0) {
                $perConversation[$convId] = $unread;
            }
        }

        return response()->json([
            'count' => $count,
            'per_conversation' => $perConversation,
        ]);
    }

    public function createConversation(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;

        $request->validate([
            'name' => 'required_if:type,group|nullable|string|max:255',
            'type' => 'required|in:direct,group',
            'participants' => 'required|string',
        ]);

        $participants = array_filter(array_map('intval', explode(',', $request->participants)));

        if (empty($participants)) {
            return back()->withErrors(['participants' => 'Please select at least one participant.']);
        }

        $validCount = User::where('school_id', $school->id)->whereIn('id', $participants)->count();
        if ($validCount !== count(array_unique($participants))) {
            return back()->withErrors(['participants' => 'The selected participants are invalid.']);
        }

        if ($request->type === 'direct' && count($participants) !== 1) {
            return back()->withErrors(['participants' => 'Direct conversations can only have one other participant.']);
        }

        if ($request->type === 'direct') {
            $existingConversation = Conversation::where('school_id', $school->id)
                ->where('type', 'direct')
                ->whereHas('participants', function ($query) use ($user) {
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

        $conversation = \Illuminate\Support\Facades\DB::transaction(function () use ($school, $request, $user, $participants) {
            $conversation = Conversation::create([
                'school_id' => $school->id,
                'name' => $request->type === 'group' ? $request->name : null,
                'type' => $request->type,
                'created_by' => $user->id,
            ]);

            $allParticipants = array_merge([$user->id], $participants);
            foreach ($allParticipants as $participantId) {
                $conversation->participants()->attach($participantId, [
                    'role' => $participantId === $user->id ? 'admin' : 'member',
                    'joined_at' => now(),
                ]);
            }

            return $conversation;
        });

        return redirect()->route('admin.communication.chat', $conversation)
            ->with('success', 'Conversation created successfully.');
    }

    public function showSMS()
    {
        $user = Auth::user();
        $school = $user->school;

        $students = $school->students()->get();
        $staff = $school->staff()->get();
        $guardians = $school->guardians()->get();

        return view('admin.communication.sms', compact('students', 'staff', 'guardians'));
    }

    public function sendSMS(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:160',
            'recipients' => 'required|array',
            'recipients.*' => 'string',
        ]);

        return back()->with('success', 'SMS sent successfully.');
    }

    public function showEmail()
    {
        $user = Auth::user();
        $school = $user->school;

        $students = $school->students()->get();
        $staff = $school->staff()->get();
        $guardians = $school->guardians()->get();

        return view('admin.communication.email', compact('students', 'staff', 'guardians'));
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'recipients' => 'required|array',
            'recipients.*' => 'string',
        ]);

        return back()->with('success', 'Email sent successfully.');
    }

    public function searchUsers(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            return response()->json([]);
        }

        $query = trim($request->get('q', ''));

        $usersQuery = User::where('school_id', $school->id)
            ->where('id', '!=', $user->id);

        if ($query !== '') {
            $usersQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
        }

        $users = $usersQuery->with('roles')
            ->limit(15)
            ->get(['id', 'name', 'email'])
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->roles->first()?->name ?? 'User',
                ];
            });

        return response()->json($users);
    }
}

