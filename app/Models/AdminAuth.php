<?php

namespace App\Models;
use App\Enums\AdminRole;
use App\Enums\AdminAccess;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminAuth extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'firstname',
        'lastname',
        'mobile',
        'email',
        'password',
        'role',
        'access'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $casts = [
        'role'   => AdminRole::class,
        'access' => AdminAccess::class,
    ];

    public function admintoken():HasMany
    {
        return $this->hasMany(AdminToken::class);
    }


    public function isSuperadmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === AdminRole::Administrator;
    }

    public function isHigherAccess(): bool
    {
        return $this->access === AdminAccess::High;
    }

    public function isLowerAccess(): bool
    {
        return $this->access === AdminAccess::Low;
    }
}
