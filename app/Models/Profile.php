<?php

namespace App\Models;


use App\Models\Kyc;
use App\Models\User;
use App\Models\Freelance;
use App\Models\ShopperMigrant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'sex',
        'dob',
        'address',
        'city',
        'state',
        'country',
        'zip_code', 
        'home_number'
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function freelance(): HasOne 
    {
        return $this->hasOne(Freelance::class);
    }

    public function shoppermigrant(): HasOne
    {
        return $this->hasOne(ShopperMigrant::class);
    }

    public function kyc(): HasOne 
    {
        return $this->hasOne(Kyc::class);
    }
}
