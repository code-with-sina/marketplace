<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatOnlinePresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_uuid',
        'recipient_uuid',
        'session_id',
        'owner_last_seen',
        'recipient_last_seen'
    ];
}
