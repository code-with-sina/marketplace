<?php

namespace App\Prop;

use App\Http\Controllers\OffersController;

class FeeDeterminantAid
{

    public function detailOffer($direction, $id)
    {

        $offer = app(OffersController::class);
        $offers = $offer->getOffer(direction: $direction, id: $id);
        return $offers;
    }
}
