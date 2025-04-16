<?php

namespace App\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CounterPartyAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'counterPartyId',
        'counterPartyType',
        'bankId',
        'bankName',
        'bankNipCode',
        'accountName',
        'accountNumber',
        'status'
    ];

    public function customer(): BelongsTo 
    {
        return $this->belongsTo(Customer::class);
    }
}
