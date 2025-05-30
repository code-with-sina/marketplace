<?php

namespace App\Services;


use App\Models\PToP;
use Illuminate\Support\Str;
use App\Models\TradeRequest;
use App\Services\DebitService;
use App\TradeFacades\HasCreatePeerToPeer;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\TransactionHookController;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Log;

class BuyApprovalService
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

    public function validate($data)
    {
        $validation = Validator::make($data, [
            'id'            => ['required'],
            "owner"         => ['required', 'string'],
            "amount"        => ['required'],
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

    public function getTradeInvoice()
    {
        if ($this->failstate) {
            return $this;
        }
        $this->trade = TradeRequest::where('id', $this->data->id)->first();
        if (!$this->trade) {
            $this->setFailedState(status: 404, title: 'Trade request not found');
            return $this;
        }
        $this->charge = (float)$this->trade->charge()->first()->total;
        return $this;
    }


    public function validateNoPreviousTrade()
    {
        if ($this->failstate) {
            return $this;
        }

        $check = PToP::where('trade_registry', $this->trade->trade_registry)->count();
        if ($check > 0) {
            $this->setFailedState(status: 400, title: 'A transaction has already been made for this trade');
            return $this;
        }

        return $this;
    }

    public function validateBalance()
    {
        if ($this->failstate) {
            return $this;
        }

        $balance = app(BalanceService::class)
            ->payload(uuid: auth()->user()->uuid, amount: (float)$this->trade->amount_to_receive)
            ->getBalance()
            ->compareBalance()
            ->throwStatus();

        if ($balance->status !== 200) {
            $this->setFailedState(status: 400, title: $balance->title);
        }

        return $this;
    }


    public function debit()
    {
        if ($this->failstate) {
            return $this;
        }

        $initReceipt = app(TransactionHookController::class);
        $initReceipt->initBuyerApprovalDebit(uuid: auth()->user()->uuid, reference: $this->trade->fund_reg);
        $debitAmount = ((float) $this->trade->trade_rate * (float) $this->charge);
        $this->debitTrack = app(DebitService::class)
            ->getAmount(amount: $debitAmount, ref: $this->trade->fund_reg, uuid: auth()->user()->uuid)
            ->getInitialBalance()
            ->compareBalance()
            ->processTransaction()
            ->createJournal()
            ->throwState();
        if ($this->debitTrack->status !== 200) {
            $this->setFailedState(status: $this->debitTrack->status, title: $this->debitTrack->title);
            return $this;
        }
        return $this;
    }

    // public function processPeerToPeer()
    // {
    //     if ($this->failstate) {
    //         return $this;
    //     }

    //     $acceptanceId = Str::uuid();
    //     $sessionId = Str::uuid();
    //     $paymentId = Str::uuid();
    //     $approval = TradeRequest::where('id', $this->data->id)->first();
    //     TradeRequest::where('id', $this->data->id)->update(['status' => 'accepted']);
    //     $this->payload(
    //         buyeraccept: $approval,
    //         acceptanceId: $acceptanceId,
    //         sessionId: $sessionId,
    //         paymentId: $paymentId,
    //         sessionStatus: self::PRECEDE_STATE['sessionStatus'],
    //         paymentStatus: self::PRECEDE_STATE['paymentStatus'],
    //         proofOfPayment: self::PRECEDE_STATE['proofOfPayment'],
    //         reportage: self::PRECEDE_STATE['reportage'],
    //         durationStatus: self::PRECEDE_STATE['durationStatus'],
    //     )->createPeerToPeer();



    //     $this->setSuccessState(status: 200, title: __('Trade request approved successfully. You can proceed to transaction page'));
    //     return $this;
    // }

    public function throwStatus()
    {
        $this->setSuccessState(status: 200, title: __('Trade request approved successfully. You can proceed to transaction page'));
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
