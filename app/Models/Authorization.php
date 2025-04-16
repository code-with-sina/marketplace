<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Authorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc',
        'type',
        'email',
        'profile',
        'user_id',
        'priviledge',
        'internal_kyc',
        'wallet_status',
        
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
