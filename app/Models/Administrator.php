<?php

namespace App\Models;

use App\Models\AdminAccount;
use App\Models\AdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Administrator extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'adminId',
        'adminType',
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
        'registered',
    ];

    public function adminaccount(): HasOne
    {
        return $this->hasOne(AdminAccount::class);
    }

    public function adminactivity(): HasMany
    {
        return $this->hasOne(AdminActivity::class);
    }
}
