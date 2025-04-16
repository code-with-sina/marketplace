<?php

namespace App\Models;

use App\Models\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class TradeRequest extends Model
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
        'status'
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Ewallet::class, 'wallet_id', 'id');
    }

    // public function options(): BelongsTo
    // {

    //     if ($this->item_for === "sell") {
    //         return $this->belongsTo(BuyerOffer::class, 'item_id', 'id');
    //     } else {
    //         return $this->belongsTo(SellerOffer::class, 'item_id', 'id');
    //     }
    // }

    public function buyerOffer()
    {
        return $this->belongsTo(BuyerOffer::class, 'item_id', 'id');
    }

    public function sellerOffer()
    {
        return $this->belongsTo(SellerOffer::class, 'item_id', 'id');
    }

    public function charge(): HasOne
    {
        return $this->hasOne(Charge::class);
    }


    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner', 'uuid');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient', 'uuid');
    }
}
