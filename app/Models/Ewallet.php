<?php

namespace App\Models;

use App\Models\BuyerOffer;
use App\Models\SellerOffer;
use App\Models\Requirement;
use Laravel\Scout\Searchable;
use App\Models\PaymentOption;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ewallet extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'uuid',
        'ewallet_name',
        'status',
        'currency',
        'image_url'
    ];

    public function paymentoption(): HasMany 
    {
        return $this->hasMany(PaymentOption::class);
    }

    public function buyeroffer(): HasMany 
    {
        return $this->hasMany(BuyerOffer::class);
    }

    public function selleroffer(): HasMany 
    {
        return $this->hasMany(SellerOffer::class);
    }


    public function requirement(): HasMany 
    {
        return $this->hasMany(Requirement::class);
    }



    public function searchableAs(): string
    {
        return 'ewallets_index';
    }

    #[SearchUsingPrefix(['id', 'ewallet_name', 'status', 'currency'])]
    // #[SearchUsingFullText(['ewallet_name', 'status', 'currency'])]

    public function toSearchableArray(): array
    {
        return [
            'ewallet_name' =>  $this->ewallet_name,
            'status' =>  $this->status,
            'currency'=>  $this->currency
        ];
    }
}
