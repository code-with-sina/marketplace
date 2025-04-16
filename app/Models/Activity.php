<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'last_login',
        'ip_address',
        'device'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
