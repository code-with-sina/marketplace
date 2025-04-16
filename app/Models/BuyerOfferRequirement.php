<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class BuyerOfferRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_offer_id',
        'requirement_id'
    ];

    public function buyeroffer(): BelongsTo 
    {
        return $this->belongsTo(BuyerOffer::class);
    }

     

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function searchableAs(): string
    {
        return 'buyer_offer_requirements_index';
    }


    #[SearchUsingPrefix(['buyer_offer_id', 'requirement_id'])]
    // #[SearchUsingFullText(['buyer_offer_id', 'requirement_id'])]

    public function toSearchableArray(): array
    {
        return [
            'buyer_offer_id' => (int) $this->buyer_offer_id,
            'requirement_id' => (int) $this->requirement_id
        ];
    }
}
