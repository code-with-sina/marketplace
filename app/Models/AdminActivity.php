<?php

namespace App\Models;


use App\Models\Administrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class AdminActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'fullname',
        'activity_performed',
        'amount',
        'buyer',
        'seller',
        'reg',
        'trnx_ref',
        'session_acceptance_id'
    ];

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
