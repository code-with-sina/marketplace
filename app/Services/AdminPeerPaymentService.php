<?php

namespace App\Services;

use App\Models\User;
use App\Models\PToP;
use App\Events\Update;
use App\Models\TradeRequest;
use App\Models\TransactionalJournal;
use App\Events\Chat as Dialogue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\MessengerController;

class AdminPeerPaymentService
{
    private $fail;
    private $failstate = false;
    private $success;
    private $seller;
    private $buyer;
    private $amount;
    private $fee;
    private $notifyData;
    private $feestate = "";
    private $paystate = "";
    private $tradeRate;
    private $data;
    private $reference;


    public function validate($data)
    {
        $validation = Validator::make($data, [
            'acceptance'    => 'required|string',
            'session_id'    => 'required|string',
        ]);

        if ($validation->fails()) {
            $this->setFailState(400, 'session_id and acceptance required');
            return $this;
        }

        $this->data = (object)$data;
        return $this;
    }



    public function allocate()
    {
        if ($this->failstate) {
            return $this;
        }

        $distribute = PToP::where('session_id', $this->data->session_id)->first();
        if (!$distribute) {
            $this->setFailState(400, 'Sorry, We could not find the transaction');
            return $this;
        }
        $charge = TradeRequest::where('fund_reg', $distribute->fund_reg)->first();
        if (!$charge) {
            $this->setFailState(400, 'Sorry, We could not find the transaction');
            return $this;
        }

        $this->reference = $charge->fund_reg;
        $this->tradeRate = $charge->trade_rate;
        $this->seller = $distribute->owner === "seller" ? $distribute->owner_id : $distribute->recipient_id;
        $this->buyer = $distribute->owner === "buyer" ? $distribute->owner_id : $distribute->recipient_id;

        $this->fee = $charge->charge()->first()->fee;
        $this->amount = $charge->charge()->first()->total;
        
        // $this->amount = ((float)$charge->charge()->first()->total -  (float)$charge->charge()->first()->fee);

        return $this;
    }

    public function validateIfCancellationOccured()
    {
        if ($this->failstate) {
            return $this;
        }


        if (PToP::where([
            ['session_id', '=', $this->data->session_id],
            ['session_status', '=', 'closed'],
            ['status', '=', 'cancelled']
        ])->exists()) {
            $this->setFailState(400, 'Sorry, This transaction has been cancelled');
            return $this;
        }

        return $this;
    }

    public function validateIfTransactionExist()
    {
        if ($this->failstate) {
            return $this;
        }


        if (TransactionalJournal::where([
            ['source_reference', '=', $this->reference],
            ['account_type', '=', 'Payment']
        ])->exists()) {
            $this->setFailState(400, 'Sorry, You already released the payment for this trasaction');
            return $this;
        }

        return $this;
    }

    public function chargeFee()
    {
        if ($this->failstate) {
            return $this;
        }

        $feeState = app(FeeService::class)
            ->getAuthorizer($this->buyer, $this->reference)
            ->getAdmin()
            ->fetchAmount(($this->fee * $this->tradeRate))
            ->processTransaction()
            ->createJournal()
            ->throwState();

        if ($feeState->status !== 200) {
            $this->setFailState($feeState->status, $feeState->title);
            return $this;
        }

        return $this;
    }


    public function makePayment()
    {
        if ($this->failstate) {
            return $this;
        }

        $paymentState = app(PaymentService::class)
            ->fetchAmount(($this->amount * $this->tradeRate), $this->reference)
            ->getParticipants($this->seller, $this->buyer)
            ->processTransaction()
            ->createJournal()
            ->throwState();

        if ($paymentState->status !== 200) {
            $this->setFailState($paymentState->status, $paymentState->title);
            return $this;
        }
        $this->setSuccessState(status: 200, title: __("success"));
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
        $sender = $this->notifyData->owner_id;
        $receiver = $this->notifyData->recipient_id;

        broadcast(new Dialogue(
            acceptance: $this->data->acceptance,
            session: $this->data->session_id,
            sender: $sender,
            receiver: $receiver,
            admin: null,
            message: " I acknowledged your order fulfillment and I have released your payment. Kindly check your Ratefy wallet.",
            filename: null,
            contentType: 'text'
        ))->toOthers();


        broadcast(new Update(
            acceptance: $this->data->acceptance,
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
}
