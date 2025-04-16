<?php

namespace App\Models;


use App\Models\Customer;
use App\Models\VirtualNuban;
use App\Models\PersonalBalance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PersonalAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customer():BelongsTo 
    {
        return $this->belongsTo(Customer::class);
    }

    public function virtualnuban(): HasOne
    {
        return $this->hasOne(VirtualNuban::class);
    }

    public function personalbalance(): HasOne
    {
        return $this->hasOne(PersonalBalance::class);
    }
}
