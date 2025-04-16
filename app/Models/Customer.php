<?php

namespace App\Models;



use App\Models\EscrowAccount;
use App\Models\CustomerStatus;
use App\Models\PersonalAccount;
use App\Models\WithdrawlHistory;
use App\Models\CounterPartyAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'customerstatus_id',
        'customerId',
        'customerType',
        'soleProprietor',
        'firstName',
        'lastName',
        'email',
        'phoneNumber',
        'address',
        'country',
        'state',
        'city',  
        'postalCode',
        'gender',
        'dateOfBirth',
        'bvn',
        'selfieImage',
        'expiryDate',
        'idType',
        'idNumber',
        'status',
        'registered'
    ];

    public function customerstatus():BelongsTo 
    {
        return $this->belongsTo(CustomerStatus::class);
    }


    public function personalaccount(): HasOne
    {
        return $this->hasOne(PersonalAccount::class);
    }

    public function escrowaccount(): HasOne 
    {
        return $this->hasOne(EscrowAccount::class);
    }

    public function counterpartyaccount(): HasMany
    {
        return $this->hasMany(CounterPartyAccount::class);
    }

    public function withdrawalhistory(): HasMany
    {
        return $this->hasMany(WithdrawlHistory::class);
    }

    

}
