<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempTradeData extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_name',
        'wallet_id',
        'item_for',
        'item_id',
        'amount',
        'amount_to_receive',
        'owner',
        'recipient',
        'status',
        'duration',
        'notify_time',
        'start',
        'end',
        'fund_attached',
        'fund_reg',
        'trade_registry',
        'trade_rate',
        'charges_for',
        'ratefy_fee',
        'percentage',
        'status',
        'debit'
    ];
}
