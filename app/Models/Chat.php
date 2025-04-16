<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'session',
        'acceptance',
        'sender',
        'receiver',
        'admin',
        'content',
        'image',
        'timestamp',
        'status',
        'contentType'
    ];

    protected $appends = ['admin'];
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender', 'uuid');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver', 'uuid');
    }


    


    public function getAdminAttribute()
    {

        $admin = $this->attributes['admin'] !== "null" ? "admin": null;
        return [
            'admin' =>  $admin,
            'uuid' => $this->attributes['admin'] ?? null, // Ensure it pulls from attributes
        ];
    }
}
