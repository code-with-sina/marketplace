<?php

namespace App\Models;

use App\Models\EscrowAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class EscrowBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'availableBalance',
        'ledgerBalance',
        'hold',
        'pending'
    ];

    public function escrowaccount(): BelongsTo
    {
        return $this->belongsTo(EscrowAccount::class);
    }
}
