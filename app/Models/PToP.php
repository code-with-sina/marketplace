<?php

namespace App\Models;

// use App\Models\User;
// use App\Models\BuyerOffer;
// use App\Models\SellerOffer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PToP extends Model
{
    use HasFactory;

    protected $fillable = [
        'acceptance_id',
        'session_id',
        'session_status',
        'amount_to_receive',
        'item_id',
        'item_name',
        'item_for',
        'amount',
        'duration',
        'duration_status',
        'payment_id',
        'payment_status',
        'proof_of_payment',
        'reportage',
        'recipient',
        'owner',
        'recipient_id',
        'owner_id',
        'start_time',
        'end_time',
        'fund_attached',
        'fund_reg',
        'trade_registry',
        'trade_rate',
        'charges_for',
        'ratefy_fee',
        'percentage',
        'status'
    ];

    public function ownerDetail(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'uuid');
    }

    public function recipientDetail(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id', 'uuid');
    }


    // public function offer(): BelongsTo
    // {
    //     // return $this->belongsTo(BuyerOffer::class, 'item_id', 'id');
    //         if ("item_for" === "sell") {
    //             return $this->belongsTo(BuyerOffer::class, 'item_id', 'id');
    //         } else {
    //             return $this->belongsTo(SellerOffer::class, 'item_id', 'id');
    //         }

    //         return null;
    // }


    public function buyerOffer()
    {
        return $this->belongsTo(BuyerOffer::class, 'item_id', 'id');
    }

    public function sellerOffer()
    {
        return $this->belongsTo(SellerOffer::class, 'item_id', 'id');
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(TradeRequest::class, 'trade_registry', 'trade_registry');
    }
}
