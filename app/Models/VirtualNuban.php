<?php

namespace App\Models;

use App\Models\EscrowAccount;
use App\Models\PersonalAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class VirtualNuban extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function escrowaccount():BelongsTo 
    {
        return $this->belongsTo(EscrowAccount::class);
    }

    public function personalaccount():BelongsTo 
    {
        return $this->belongsTo(PersonalAccount::class);
    }
}
