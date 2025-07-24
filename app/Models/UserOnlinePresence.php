<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOnlinePresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'sender_id', 'receiver_id', 'user_id', 'last_seen_at'
    ];


    public function updatePresence($sessionId, $senderId, $receiverId)
    {
        $userId = auth()->id(); // current user

        // ChatSessionPresence::updateOrCreate(
        //     ['session_id' => $sessionId, 'user_id' => $userId],
        //     [
        //         'sender_id' => $senderId,
        //         'receiver_id' => $receiverId,
        //         'last_seen_at' => now()
        //     ]
        // );
    }
}
