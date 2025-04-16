<?php

namespace App\Models;


use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ShopperMigrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'option',
        'experience',
        'purpose'
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
