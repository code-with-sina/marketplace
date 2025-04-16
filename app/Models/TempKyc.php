<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class TempKyc extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'bvn',
        'selfie',
        'dateOfBirth',
        'gender',
        'idNumber',
        'idType',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
