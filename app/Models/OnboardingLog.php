<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        "endpoint",
        "status",
        "message",
        "payload",
        "response"
    ];

    protected $casts = [
        "payload"   => 'array',
        "response"  => 'array'
    ];
}
