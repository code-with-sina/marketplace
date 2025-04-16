<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeExtract extends Model
{
    use HasFactory;
    protected $fillable = [
        'offer_owner',
        'product',
        'type',
        'type_id', 
        'reg_key',
        'total_amount',
        'valued_amount',
        'valued_fee',
    ];
}
