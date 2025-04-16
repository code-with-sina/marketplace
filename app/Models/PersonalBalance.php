<?php

namespace App\Models;


use App\Models\PersonalAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonalBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'availableBalance',
        'ledgerBalance',
        'hold',
        'pending'
    ];

    public function personalaccount(): BelongsTo
    {
        return $this->belongsTo(PersonalAccount::class);
    }
}
