<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class WebhookEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'event_id',
        'event_type',
        'message',
        'payload',
        'event_time',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
