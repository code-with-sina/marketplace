<?php

namespace App\Models;


use App\Models\User;
use App\Models\Ewallet;
use App\Models\BuyerOfferTerm;
use Laravel\Scout\Searchable;
use App\Models\PaymentOption;
use App\Models\BuyerOfferRequirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class BuyerOffer extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'guide',
        'duration',
        'min_amount',
        'max_amount',
        'percentage',
        'fixed_rate',
        'ewallet_id',
        'payment_option_id',
        'status',
        'approval'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ewallet(): BelongsTo
    {
        return $this->belongsTo(Ewallet::class);
    }

    public function buyerterm(): HasMany
    {
        return $this->hasMany(BuyerOfferTerm::class);
    }

    public function buyerofferrequirement(): HasMany
    {
        return $this->hasMany(BuyerOfferRequirement::class);
    }


    public function paymentoption(): BelongsTo
    {
        return $this->belongsTo(PaymentOption::class, 'payment_option_id');
    }

    public function searchableAs(): string
    {
        return 'buyer_offers_index';
    }


    #[SearchUsingPrefix(['id', 'guide', 'duration', 'min_amount', 'max_amount', 'percentage', 'fixed_rate'])]
    #[SearchUsingFullText(['guide', 'duration', 'min_amount', 'max_amount', 'percentage', 'fixed_rate'])]

    public function toSearchableArray(): array
    {
        return [
            'uuid' =>  $this->uuid,
            'guide' =>  $this->guide,
            'duration' => $this->duration,
            'min_amount' => (float) $this->min_amount,
            'max_amount' => (float) $this->max_amount,
            'percentage' => (float) $this->percentage,
            'fixed_rate' => (float) $this->fixed_rate,
            'payment_option_id' => (int) $this->payment_option_id,
            'payment_option_id' => (int) $this->payment_option_id,
            'status' =>  $this->status,
            'approval'  => $this->approval
        ];
    }
}
