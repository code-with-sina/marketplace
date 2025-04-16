<?php

namespace App\Models;


use App\Models\Ewallet;
use App\Models\BuyerOffer;
use App\Models\Requirement;
use App\Models\SellerOffer;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PaymentOption extends Model
{
    use HasFactory, Searchable;
    protected $fillable = [
        'ewallet_id',
        'option',
        'status',
    ];

    public function ewallet(): BelongsTo 
    {
        return $this->belongsTo(Ewallet::class);
    }

    public function requirement(): HasMany  
    {
        return $this->hasMany(Requirement::class);
    }

    public function buyeroffer(): HasMany  
    {
        return $this->hasMany(BuyerOffer::class);
    }
    

    public function selleroffer(): HasMany  
    {
        return $this->hasMany(SellerOffer::class);
    }


    public function searchableAs(): string
    {
        return 'payment_options_index';
    }



    #[SearchUsingPrefix(['id', 'option', 'status'])]
    // #[SearchUsingFullText(['option', 'status'])]
    public function toSearchableArray(): array
    {
        return [
            'option' =>  $this->option,
            'status' =>  $this->status,
        ];
    }
}
