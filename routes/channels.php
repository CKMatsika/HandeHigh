<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    // Must belong to the exact same school tenant and be an active participant
    return (int) $conversation->school_id === (int) $user->school_id
        && $conversation->participants()->where('user_id', $user->id)->exists();
});

Broadcast::channel('user.{schoolId}.{userId}', function ($user, $schoolId, $userId) {
    return (int) $user->school_id === (int) $schoolId && (int) $user->id === (int) $userId;
});
