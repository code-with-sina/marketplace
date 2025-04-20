<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TransactionEvent extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'type',
        'reference',
        'status',
        'message',
        'payload',
        'event_time',
        'event_id',
        'event_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
