<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trail extends Model
{
    use HasFactory;
    protected $fillable = [
        'ip',
        'method',
        'api_url',
        'action',
        'post_data',
        'return_value',
        'status',
        'uniquestamps'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
