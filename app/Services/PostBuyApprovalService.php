<?php

namespace App\Services;


use Illuminate\Support\Str;
use App\Models\TradeRequest;
use App\TradeFacades\HasCreatePeerToPeer;


class PostBuyApprovalService 
{
    use HasCreatePeerToPeer;

    private $failstate = false;
    private $fail;
    private $trade;
    private $data;
    private $debitTrack;
    private $success;
    private $charge;
    private $amount;

    const PRECEDE_STATE = [
        'sessionStatus' => 'open',
        'paymentStatus' => 'void',
        'proofOfPayment' => 'void',
        'reportage' => 'good',
        'durationStatus' => 'started',
    ];


    public function processPeerToPeer()
    {
        if ($this->failstate) {
            return $this;
        }

        $acceptanceId = Str::uuid();
        $sessionId = Str::uuid();
        $paymentId = Str::uuid();
        $approval = TradeRequest::where('id', $this->data->id)->first();
        TradeRequest::where('id', $this->data->id)->update(['status' => 'accepted']);
        $this->payload(
            buyeraccept: $approval,
            acceptanceId: $acceptanceId,
            sessionId: $sessionId,
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

    public function throwStatus()
    {

        return $this->failstate ? $this->fail : $this->success;
    }


    public function setSuccessState($status, $title)
    {
        $this->success = (object) [
            'status'    => $status,
            'title'     => $title,
            'data'      => $this->trade,
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