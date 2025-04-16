<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\BuyerOffer;
use App\Models\SellerOffer;



class ChargeService
{
    private $fee;
    private $user;
    private $item;
    private $total;
    private $offer;
    private $error;
    private $account;
    private $itemFor;
    private $prepared = [];
    private $tradeTotal;
    private $errorState = false;
    private $percentage;


    public function getOffer($itemFor = null, $tradeTotal, $itemId): mixed
    {
        $this->item = $itemId;
        $this->itemFor = $itemFor;
        $this->tradeTotal = $tradeTotal;

        if ($this->itemFor === null) {
            $this->setError(status: 400, title: __("The Item For is null please provide the item"));
            return $this;
        }

        if ($this->tradeTotal === null) {
            $this->setError(status: 400, title: __("The total amount to send is null please provide the amount"));
            return $this;
        }

        if ($this->item === null) {
            $this->setError(status: 400, title: __("The ItemId  is null please provide the item"));
            return $this;
        }


        $this->offer = $this->itemFor === "sell" ?
            BuyerOffer::where('id', $this->item)->first()
            : SellerOffer::where('id', $this->item)->first();



        if (!$this->offer) {
            $this->setError(status: 400, title: throw new \Exception("Offer not found"));
        }

        $this->prepared = array_merge($this->prepared, [
            'product'   => $this->offer->ewallet()->first()->ewallet_name,
            'offer'     => $this->itemFor === "sell" ? "Buyer Offer" : "Seller Offer",
        ]);

        return $this;
    }


    public function getTradeOwner()
    {
        $this->user = $this->offer->user()->first();
        $this->prepared = array_merge($this->prepared, [
            'owner' => $this->user->username,
            'uuid'  => $this->user->uuid
        ]);
        return $this;
    }

    public function prepareChargeStatement()
    {
        $operations =  $this->itemFor == "sell" ? "+" : "-";
        $this->account = $this->calculateTotal(operations: $operations);

        $this->prepared = array_merge($this->prepared, ['prepared invoice' => $this->account]);

        return $this;
    }

    public function state()
    {
        return $this->errorState ? $this->error : $this->prepared;
    }


    public function calculateTotal($operations)
    {
        $fee = Fee::latest()->first();

        if (!$fee || !is_numeric($fee->percentage)) {
            throw new \Exception("Invalid fee information");
        }

        $this->percentage = ($fee->percentage * $this->tradeTotal) / 100;
        $this->fee = $this->percentage;

        if (!in_array($operations, ['-', '+'], true)) {
            throw new \InvalidArgumentException("Invalid operation type");
        }

        $this->total = $operations === "-"
            ? $this->tradeTotal - $this->percentage
            : $this->tradeTotal + $this->percentage;

        return [
            'fee' => (float)$this->fee,
            'total' => (float)$this->total
        ];
    }


    public function setError($status, $title)
    {
        $this->errorState = true;
        $this->error = [
            'status'    => $status,
            'title'     => $title
        ];
        return $this;
    }
}
