<?php

namespace App\Services;

use App\Models\TradeRequest;
use App\Services\ReversalService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\MessengerController;


class CancelTradeService
{
    private $failstate = false;
    private $fail;
    private $success;
    private $data;
    private $trade;
    private $skipDebit = false;


    public function validate($data)
    {
        $validation = Validator::make($data, [
            'id'            => 'required',
        ]);

        if ($validation->fails()) {
            $this->setFailedState(400, $validation->errors()->first());
            return $this;
        }

        $this->data = (object) $data;
        return $this;
    }

    public function validateTradeExists()
    {
        if ($this->failstate)
            return $this;

        $this->trade = TradeRequest::where('id', $this->data->id)->first();
        if (!$this->trade) {
            $this->setFailedState(400, __("Sorry, we could not find a trade with the id"));
            return $this;
        }

        if ($this->trade->fund_attached !== "yes")
            $this->skipDebit = true;

        return $this;
    }

    public function validateTradeHasNoAction()
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

    public function ReverseDebit()
    {
        if ($this->failstate)
            return $this;

        if ($this->skipDebit)
            return $this;

        $reversal = app(ReversalService::class)
            ->getPreviousTransaction($this->trade->fund_reg)
            ->reverseTransaction()
            ->createJournal()
            ->throwState();


        if ($reversal->status !== 200) {
            $this->setFailedState($reversal->status, $reversal->title);
            return $this;
        }

        return $this;
    }


    public function updateTrade()
    {
        if ($this->failstate)
            return $this;

        TradeRequest::where('id', $this->trade->id)->update(['status' => 'cancelled']);

        return $this;
    }

    public function informStakeholder()
    {
        if ($this->failstate)
            return $this;
        try {


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


    public function throwState()
    {

        return $this->failstate ? $this->fail : $this->success;
    }

    public function setSuccessState($status, $title)
    {
        $this->success = (object) [
            'status'    => $status,
            'title'     => $title
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
