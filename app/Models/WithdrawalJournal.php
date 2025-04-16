<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class WithdrawalJournal extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "account_type",
        "narration",
        "trust_id",
        "amount",
        "reference",
        "status",
        'trnx_ref',
        'reason_for_failure',
    ];


    public function user():BelongsTo 
    {
        return $this->belongsTo(User::class);
    }
}
