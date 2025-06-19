<?php

namespace App\Models;

use App\Models\Otp;
use App\Models\Tag;
use App\Models\Trail;
use App\Models\Profile;
use App\Models\KycState;
use App\Models\Activity;
use App\Models\ErrorTrace;
use App\Models\BuyerOffer;
use App\Models\SellerOffer;
use App\Models\MiniProfile;
use App\Models\WebhookEvent;
use App\Models\Authorization;
use App\Models\CustomerStatus;
use App\Models\PeerPaymentOtp;
use App\Models\WithdrawalJournal;
use App\Models\ReleasePaymentOtp;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Notifications\CustomResetPassword;
use Illuminate\Auth\Notifications\ResetPassword;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'password',
        'email',
        'firstname',
        'lastname',
        'mobile',
        'username',
        'exp_id',
        'circulated',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function trail(): HasMany
    {
        return $this->hasMany(Trail::class);
    }


    public function otp(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    public function releasepaymentotp(): HasMany
    {
        return $this->hasMany(ReleasePaymentOtp::class);
    }


    public function peerpaymentotp(): HasMany
    {
        return $this->hasMany(PeerPaymentOtp::class);
    }


    public function errortrace(): HasMany
    {
        return $this->hasMany(ErrorTrace::class);
    }


    public function authorization(): HasOne
    {
        return $this->hasOne(Authorization::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function tag(): HasOne
    {
        return $this->hasOne(Tag::class);
    }


    public function miniprofile(): HasOne
    {
        return $this->hasOne(MiniProfile::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }


    public function customerstatus(): HasOne
    {
        return $this->hasOne(CustomerStatus::class);
    }


    public function kycstate(): HasOne
    {
        return $this->hasOne(KycState::class);
    }

    public function selleroffer(): HasMany
    {
        return $this->hasMany(SellerOffer::class);
    }


    public function buyeroffer(): HasMany
    {
        return $this->hasMany(BuyerOffer::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }


    public function withdrawjournal(): HasMany
    {
        return $this->hasMany(WithdrawalJournal::class);
    }

    public function webhookevent(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function tempkyc(): HasOne
    {
        return $this->hasOne(TempKyc::class);
    }

    public function transactionevent(): HasMany
    {
        return $this->hasMany(TransactionEvent::class);
    }

    public function kycdetail(): HasOne
    {
        return $this->hasOne(KycDetail::class);
    }
}
