<?php

namespace App\Models;

use App\models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Freelance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'profile_id',
        'options',
        'service_offer',
        'portfolio',
        'work_history',
        'experience',
        'purpose'
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
