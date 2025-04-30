<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\User;
use App\Models\PToP;
use App\Events\Update;
use App\Models\Charge;
use App\Models\TradeRequest;
use App\Events\Chat as Dialogue;
use App\Models\TransactionalJournal;
use App\Http\Controllers\MessengerController;



class PostPeerPaymentService 
{

    private $fail;
    private $failstate = false;
    private $success;
    private $amount;
    private $fee;
    private $notifyData;
    private $feestate = "";
    private $paystate = "";
    private $tradeRate;
    private $data;
    private $reference;


    public function getData($reference) 
    {
        $this->data = PToP::where('fund_reg', $reference)->first();
        if (!$this->data) {
            $this->setFailState(400, 'transaction not found');
            return $this;
        }

        return $this;
    }


    public function updateTransaction()
    {
        if ($this->failstate)
            return $this;

        PToP::where('session_id', $this->data->session_id)->update([
            'proof_of_payment_status'   => 'accept',
            'payment_status'    => 'released',
            'session_status'   => 'closed'
        ]);

        $this->notifyData = PTop::where('session_id', $this->data->session_id)->first();
        return $this;
    }



    public function sendPaymentNotification()
    {
        if ($this->failstate)
            return $this;

        $messenger = app(MessengerController::class);
        $messenger->sendTradeCompletionSuccessNotification(
            owner: $this->notifyData->owner_id,
            recipient: $this->notifyData->recipient_id,
            amount: $this->notifyData->amount,
            itemFor: $this->notifyData->item_for,
            itemName: $this->notifyData->item_name,
            itemId: $this->notifyData->item_id,
            amountToRecieve: $this->notifyData->amount_to_receive
        );

        return $this;
    }

    public function broadcastUpdate()
    {
        if ($this->failstate)
            return $this;
        
        broadcast(new Dialogue(
            acceptance: $this->data->acceptance_id,
            session: $this->data->session_id,
            sender: $this->data->owner_id,
            receiver: $this->data->recipient_id,
            admin: null,
            message: " I acknowledged your order fulfillment and I have released your payment. Kindly check your Ratefy wallet.",
            filename: null,
            contentType: 'text'
        ))->toOthers();


        broadcast(new Update(
            acceptance: $this->data->acceptance_id,
            session: $this->data->session_id,
            updateState: '2'
        ))->toOthers();

        $this->setSuccessState(status: 200, title: 'Transaction completed successfully');
        return $this;
    }


    public function throwState()
    {
        return $this->failstate ? $this->fail : $this->success;
    }



    public function setFailState($status = 400, $title)
    {
        $this->failstate = true;
        return $this->fail = (object)[
            'status' => $status,
            'title' => $title,
            'fee state' => $this->feestate,
            'charge state' => $this->paystate
        ];
        return $this;
    }


    public function setSuccessState($status = 200, $title)
    {
        return $this->success = (object)[
            'status' => $status,
            'title' => $title,
            'reference' => $this->reference,
            'charge state' => $this->amount
        ];

        return $this;
    }



    public function isSell($charges)
    {
        $this->fee = $charges->charge()->first()->fee;
        $this->amount = $charges->charge()->first()->total;
    }


    public function isBuy($charges)
    {
        $debitedAmount = $this->fetcthDebited();
        $getPercent = $this->fees();
        $debitTotal = ($debitedAmount / $this->tradeRate);

        $percentage = round(((float)$getPercent->percentage * $debitTotal) / 100, 2);
        $balance = $debitTotal - $percentage;


        $this->fee = $percentage;
        $this->amount =  $balance;
    }

    public function fetcthDebited()
    {
        $previousDebit = TransactionalJournal::where('source_reference', $this->reference)->where('account_type', 'Debit')->first();
        return $previousDebit->amount;
    }

    public function fees()
    {
        $fee = Fee::latest()->first();
        return $fee;
    }



    public function deterUtils()
    {
        $distribute = PToP::where('session_id', $this->data->session_id)->first();
        $charge = TradeRequest::where('fund_reg', $distribute->fund_reg)->first();
        $totalCharge = Charge::where('trade_request_id', $charge->id)->first();

        if ($totalCharge->offer == "Seller Offer") {
            $this->fee = (float)$totalCharge->fee;
            $this->amount = (float)$totalCharge->total;
        } else {
            $this->fee = (float)$totalCharge->fee;
            $this->amount = (float)$totalCharge->total - $totalCharge->fee;
        }

        return $this;
    }
}