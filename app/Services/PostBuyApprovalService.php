<?php

namespace App\Services;

use App\Models\User;
use App\Events\Update;
use Illuminate\Support\Str;
use App\Models\TradeRequest;
use App\Events\Chat as Dialogue;
use App\TradeFacades\HasCreatePeerToPeer;


class PostBuyApprovalService 
{
    use HasCreatePeerToPeer;

    private $failstate = false;
    private $fail;
    private $debitTrack;
    private $success;
    private $charge;
    private $amount;
    private $acceptanceUUid;
    private $sessionUUid;
    private $approval;

    const PRECEDE_STATE = [
        'sessionStatus' => 'open',
        'paymentStatus' => 'void',
        'proofOfPayment' => 'void',
        'reportage' => 'good',
        'durationStatus' => 'started',
    ];
  

    public function processPeerToPeer($reference)
    {
        if ($this->failstate) {
            return $this;
        }

        $this->acceptanceUUid = Str::uuid();
        $this->sessionUUid = Str::uuid();
        $paymentId = Str::uuid();
        $this->approval = TradeRequest::where('fund_reg', $reference)->first();
        TradeRequest::where('fund_reg', $reference)->update(['status' => 'accepted']);
        $this->payload(
            buyeraccept: $this->approval,
            acceptanceId: $this->acceptanceUUid,
            sessionId: $this->sessionUUid,
            paymentId: $paymentId,
            sessionStatus: self::PRECEDE_STATE['sessionStatus'],
            paymentStatus: self::PRECEDE_STATE['paymentStatus'],
            proofOfPayment: self::PRECEDE_STATE['proofOfPayment'],
            reportage: self::PRECEDE_STATE['reportage'],
            durationStatus: self::PRECEDE_STATE['durationStatus'],
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

               
        $dBuyer = User::where('uuid', $this->approval->owner)->first();
        $dSeller = User::where('uuid', $this->approval->recipient)->first();
        broadcast(new Dialogue(
            acceptance: $this->acceptanceUUid,
            session: $this->sessionUUid,
            sender: "0eb8dc26-5a2a-403b-be04-58f3d659158c",
            receiver: "0eb8dc26-5a2a-403b-be04-58f3d659158c",
            admin: "0eb8dc26-5a2a-403b-be04-58f3d659158c",
            filename: null,
            message: "This transaction is taking place on this day " . now()->format('d-m-y') . ", between [Party A: {$dBuyer->username}] and [Party B: {$dSeller->username}], for the sum of about ₦{$this->approval->amount_to_receive}.",
            contentType: 'text'
        ))->toOthers();
    
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
            'const'    => self::PRECEDE_STATE['sessionStatus']
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