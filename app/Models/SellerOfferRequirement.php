<?php

namespace App\Models;



use App\Models\Requirement;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SellerOfferRequirement extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'seller_offer_id',
        'requirement_id'
    ];

    public function selleroffer(): BelongsTo 
    {
        return $this->belongsTo(SellerOffer::class);
    }

    public function requirement(): BelongsTo 
    {
        return $this->belongsTo(Requirement::class);
    }



    public function searchableAs(): string
    {
        return 'seller_offer_term_requirements_index';
    }

    #[SearchUsingPrefix(['id', 'seller_offer_id', 'requirement_id'])]
    #[SearchUsingFullText(['seller_offer_id', 'requirement_id'])]

    public function toSearchableArray(): array
    {
        return [
            'seller_offer_id' => (int) $this->seller_offer_id,
            'requirement_id' => (int) $this->requirement_id
        ];
    }
}
