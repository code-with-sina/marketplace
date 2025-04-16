<?php

namespace App\Models;

use App\Models\Administrator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAccount extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function administrator():BelongsTo 
    {
        return $this->belongsTo(Administrator::class);
    }
}
