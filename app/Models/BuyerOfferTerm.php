<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerOfferTerm extends Model
{
    use HasFactory;
    protected $fillable = [
        'buyeroffer_id',
        'title',
        'condition'
    ];

    public function buyeroffer(): BelongsTo 
    {
        return $this->belongsTo(BuyerOffer::class);
    }



    public function searchableAs(): string
    {
        return 'buyer_terms_index';
    }

    #[SearchUsingPrefix(['id', 'buyer_offer_id', 'title', 'condition'])]
    #[SearchUsingFullText(['buyer_offer_id', 'title', 'condition'])]

    public function toSearchableArray(): array
    {
        return [
            'buyer_offer_id' => (int) $this->buyer_offer_id,
            'title' =>  $this->title,
            'condition'=>  $this->condition
        ];
    }
   
}
