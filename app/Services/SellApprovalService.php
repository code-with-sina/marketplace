<?php

namespace App\Services;

use App\Models\PToP;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\TradeRequest;
use App\Events\Chat as Dialogue;
use App\Models\ChatOnlinePresence;
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
    private $acceptanceUUId;
    private $sessionUUId;


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

        $this->acceptanceUUId   = Str::uuid();
        $this->sessionUUId      = Str::uuid();
        $paymentId      = Str::uuid();
        $this->payload(
            buyeraccept: $this->trade,
            acceptanceId: $this->acceptanceUUId,
            sessionId: $this->sessionUUId,
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

    public function broadcastPeerToPeer()
    {
        if ($this->failstate) 
        {
            return $this;
        }

               
        $dBuyer = User::where('uuid', $this->trade->owner)->first();
        $dSeller = User::where('uuid', $this->trade->recipient)->first();
        broadcast(new Dialogue(
            acceptance: $this->acceptanceId,
            session: $this->sessionId,
            sender: "0eb8dc26-5a2a-403b-be04-58f3d659158c",
            receiver: "0eb8dc26-5a2a-403b-be04-58f3d659158c",
            admin: "0eb8dc26-5a2a-403b-be04-58f3d659158c",
            filename: null,
            message: "The Naira payment for this transaction has been held. Stay polite. Be active & responsive.  Switch to 'order details' page (at the top right corner) to report any issue with this transaction.",
            contentType: 'text'
        ))->toOthers();
    
        return $this;
    }

    public function createPresence() 
    {
        if ($this->failstate) 
        {
            return $this;
        }

        ChatOnlinePresence::create([
            'owner_uuid'    => $this->trade->owner,
            'recipient_uuid'    => $this->trade->recipient,
            'session_id'        => $this->sessionId,
            'owner_last_seen'   => now(),
            'recipient_last_seen'   => now()
        ]);

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
