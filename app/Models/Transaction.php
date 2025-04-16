<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'owner',
        'recipient',
        'wallet_name',
        'wallet_id',
        'item_for', 
        'session',
        'acceptance'
    ];
}
