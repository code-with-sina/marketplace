<?php

namespace App\Models;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CustomerStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'customerId',
        'status',
        'type'
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): HasOne 
    {
        return $this->hasOne(Customer::class);
    }
}
