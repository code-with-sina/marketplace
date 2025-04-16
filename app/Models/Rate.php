<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;
    protected $fillable = [
        'rate_decimal',
        'rate_normal',
        'assets_id_from',
        'assets_id_to',
        'status',
        'compare',
        'ordering',
    ];
}
