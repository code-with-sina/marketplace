<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use App\Models\PaymentOption;
use App\Models\BuyerOfferRequirement;
use App\Models\SellerOfferRequirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Requirement extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'payment_option_id',
        'requirement',
        'status',
    ];


    public function paymentoption(): BelongsTo 
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function buyerofferrequirement(): HasMany 
    {
        return $this->hasMany(BuyerOfferRequirement::class);
    }

    public function sellerofferrequirement(): HasMany 
    {
        return $this->hasMany(SellerOfferRequirement::class);
    }


    public function searchableAs(): string
    {
        return 'requirements_index';
    }


    // #[SearchUsingPrefix(['id', 'requirement', 'status'])]
    // #[SearchUsingFullText(['requirement', 'status'])]
    public function toSearchableArray(): array
    {
        return [
            'requirement' =>  $this->requirement,
            'status' =>  $this->status,
        ];;
    }
}
