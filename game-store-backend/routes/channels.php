<?php

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Phase 4 / Batch D — приватный канал чата.
 * Auth: только active participant чата может subscribe'ить и получать
 * NewChatMessage event'ы.
 */
Broadcast::channel('chat-room.{roomId}', function ($user, $roomId) {
    return \App\Models\ChatParticipant::where('chat_room_id', $roomId)
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->exists();
});
