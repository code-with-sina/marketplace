<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteActivity extends Model
{
    use HasFactory;
    // protected $table = 'route_activity';

    protected $fillable = [
        'user_id',
        'method',
        'url',
        'parameters',
        'ip_address',
        'user_agent',
        'controller_action'
    ];
}
