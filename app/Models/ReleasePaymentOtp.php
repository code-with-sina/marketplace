<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ReleasePaymentOtp extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'session_id',
        'acceptance',
        'otp_code',
        'status',
        'tally',
        'used_at',
        'expires_at'
    ];


    public function user(): BelongsTo 
    {
        return $this->belongsTo(User::class);
    }
}
