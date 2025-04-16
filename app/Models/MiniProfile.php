<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MiniProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bvn',
        'selfieImage',
        'idType',
        'idNumber',
        'dateOfBirth',
        'gender',
        'status',
        'badge'
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
