<?php

namespace App\Models;

use App\Models\TradeRequest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Charge extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function traderequest(): BelongsTo
    {
        return $this->belongsTo(TradeRequest::class);
    }
}
