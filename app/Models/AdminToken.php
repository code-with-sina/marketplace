<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_auth_id',
        'token',
        'status',
        'expires_at'
    ];

    public function adminauth():BelongsTo
    {
        return $this->belongsTo(AdminAuth::class);
    }
}
