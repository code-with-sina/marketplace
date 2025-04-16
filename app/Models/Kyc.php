<?php

namespace App\Models;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Kyc extends Model
{
    use HasFactory;

    protected $fillable = [
        'bvn',
        'selfie',
        'dateOfBirth',
        'gender',
        'idNumber',
        'idType',
        'expiryDate',
        'document_id',
        'company',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
