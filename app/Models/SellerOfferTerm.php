<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOfferTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_offer_id',
        'title',
        'condition'
    ];

    public function selleroffer(): BelongsTo 
    {
        return $this->belongsTo(SellerOffer::class);
    }
    



    public function searchableAs(): string
    {
        return 'seller_terms_index';
    }



    public function toSearchableArray(): array
    {
        return [
            'seller_offer_id' => (int) $this->seller_offer_id,
            'title' =>  $this->title,
            'condition'=>  $this->condition
        ];
    }
}
