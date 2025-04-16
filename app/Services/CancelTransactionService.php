<?php

namespace App\Services;

use App\Models\PToP;
use App\Events\Update;
use App\Models\TradeRequest;
use App\Models\TransactionalJournal;
use App\Services\ReversalService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\MessengerController;

class CancelTransactionService
{
    private $fail;
    private $success;
    private $failstate = false;
    private $trade;
    private $data;
    private $transaction;


    public function validate($data)
    {
        $validation = Validator::make($data, [
            'session_id'    => ['required'],
            'acceptance'    => ['required'],
        ]);

        if ($validation->fails()) {
            $this->setFailedState(status: 400, title: $validation->errors()->first());
            return $this;
        }

        $this->data = (object)$data;
        return $this;
    }

    public function validateTransactionExists()
    {
        if ($this->failstate)
            return $this;

        $this->transaction = PToP::where('session_id', $this->data->session_id)->first();
        if (!$this->transaction) {
            $this->setFailedState(status: 404, title: 'Transaction not found');
            return $this;
        }

        return $this;
    }
    public function validateIfPaymentOccured()
    {
        if ($this->failstate) {
            return $this;
        }


        if (PToP::where([
            ['session_id', '=', $this->data->session_id],
            ['proof_of_payment_status', '=', 'accept'],
            ['payment_status', '=', 'released'],
            ['session_status', '=', 'closed'],
        ])->exists()) {
            $this->setFailedState(400, 'Nope, You cant do that. Payment has already been disbursed');
            return $this;
        }

        return $this;
    }


    public function validateIfReversalOccurred()
    {
        if ($this->failstate) {
            return $this;
        }

        if (TransactionalJournal::where([
            ['source_reference', '=', $this->transaction->fund_reg],
            ['account_type', '=', 'Reverse']
        ])->exists()) {
            $this->setFailedState(400, 'Sorry, you already cancelled this transaction');
            return $this;
        }


        return $this;
    }
    public function reFund()
    {
        if ($this->failstate)
            return $this;


        $reversal = app(ReversalService::class)
            ->getPreviousTransaction($this->transaction->fund_reg)
            ->reverseTransaction()
            ->createJournal()
            ->throwState();

        if ($reversal->status !== 200) {
            $this->setFailedState(status: 500, title: 'Failed to reverse transaction');
            return $this;
        }

        return $this;
    }

    public function cancelCurrentTransaction()
    {
        if ($this->failstate)
            return $this;


        $this->transaction->update([
            'session_status'    => 'closed',
            'status'            => 'cancelled'
        ]);

        return $this;
    }

    public function broadcastUpdate()
    {
        if ($this->failstate)
            return $this;

        event(new Update(
            acceptance: $this->data->acceptance,
            session: $this->data->session_id,
            updateState: '3'
        ));

        $this->setSuccessState(status: 200, title: 'Transaction cancelled successfully');
        return $this;
    }

    public function informStakeholder()
    {
        if ($this->failstate)
            return $this;
        try {

            $this->trade = TradeRequest::where('fund_reg', $this->transaction->fund_reg)->first();
            $messenger = app(MessengerController::class);
            $messenger->sendCancelTradeRequestNotification(
                owner: $this->trade->owner,
                recipient: $this->trade->recipient,
                amount: $this->trade->amount,
                amountInNaira: ((float)$this->trade->amount * (float)$this->trade->rate),
                itemFor: $this->trade->item_for,
                walletName: $this->trade->wallet_name,
                itemId: $this->trade->item_id
            );
            $this->setSuccessState(200, __("Trade has been cancelled successfully"));
            return $this;
        } catch (\Exception $e) {
            $this->setFailedState(400, __("Trade has been cancelled successfully but we could not inform the stakeholder >>>  " . $e->getMessage()));
            return $this;
        }

        return $this;
    }

    public function throwStatus()
    {
        return $this->failstate ? $this->fail : $this->success;
    }


    public function setFailedState($status = 400, $title)
    {
        $this->failstate = true;
        $this->fail = (object) [
            'status'    => $status,
            'title'     => $title
        ];
        return $this;
    }

    public function setSuccessState($status = 400, $title)
    {
        $this->success = (object) [
            'status'    => $status,
            'title'     => $title,
        ];

        return $this;
    }
}
