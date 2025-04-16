<?php

namespace App\Services;

use App\Models\PToP;
use Illuminate\Support\Str;
use App\Models\TradeRequest;
use App\TradeFacades\HasCreatePeerToPeer;
use Illuminate\Support\Facades\Validator;


class SellApprovalService
{

    use HasCreatePeerToPeer;

    private $failstate = false;
    private $fail;
    private $data;
    private $success;
    private $trade;


    const PRECEDE_STATE = [
        'sessionStatus' => 'open',
        'paymentStatus' => 'void',
        'proofOfPayment' => 'void',
        'reportage' => 'good',
        'durationStatus' => 'started',
    ];


    public function validate($data)
    {
        $validation = Validator::make($data, [
            'id'            => ['required'],
            "owner"         => ['required', 'string'],
            "charges_for"   => ['required'],
            "ratefy_fee"    => ['required'],
            "percentage"    => ['required'],
        ]);

        if ($validation->fails()) {
            $this->setFailedState(status: 400, title: $validation->errors()->first());
            return $this;
        }


        $this->data = (object) $data;
        return $this;
    }

    public function validateNoCancelledRequest()
    {
        if ($this->failstate)
            return $this;

        $verify = TradeRequest::where('id', $this->data->id)->whereIn('status', ['rejected', 'cancelled', 'accepted'])->exists();

        if ($verify) {
            $this->setFailedState(status: 400, title: __("Sorry, this trade is either cancelled, rejected, or has been accepted"));
            return $this;
        }

        return $this;
    }

    public function validateNoPreviousTrade()
    {
        if ($this->failstate) {
            return $this;
        }

        $this->trade = TradeRequest::where('id', $this->data->id)->first();
        $check = PToP::where('trade_registry', $this->trade->trade_registry)->count();
        if ($check > 0) {
            $this->setFailedState(status: 400, title: 'A transaction has already been made for this trade');
            return $this;
        }

        return $this;
    }

    public function processPeerToPeer()
    {
        if ($this->failstate) {
            return $this;
        }

        TradeRequest::where('id', $this->data->id)->update(['status' => 'accepted']);

        $acceptanceId   = Str::uuid();
        $sessionId      = Str::uuid();
        $paymentId      = Str::uuid();
        $this->payload(
            buyeraccept: $this->trade,
            acceptanceId: $acceptanceId,
            sessionId: $sessionId,
            paymentId: $paymentId,
            sessionStatus: self::PRECEDE_STATE['sessionStatus'],
            paymentStatus: self::PRECEDE_STATE['paymentStatus'],
            proofOfPayment: self::PRECEDE_STATE['proofOfPayment'],
            reportage: self::PRECEDE_STATE['reportage'],
            durationStatus: self::PRECEDE_STATE['durationStatus']
        )->createPeerToPeer();

        $this->setSuccessState(status: 200, title: __('Trade request approved successfully. You can proceed to transaction page'));
        return $this;
    }

    public function throwStatus()
    {

        return $this->failstate ? $this->fail : $this->success;
    }


    public function setSuccessState($status, $title)
    {
        $this->success = (object) [
            'status'    => $status,
            'title'     => $title,
        ];

        return $this;
    }




    public function setFailedState($status, $title)
    {
        $this->failstate = true;

        $this->fail = (object) [
            'status'    => $status,
            'title'     => $title
        ];

        return $this;
    }
}
