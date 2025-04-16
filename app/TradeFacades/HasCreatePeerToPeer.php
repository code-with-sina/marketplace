<?php

namespace App\TradeFacades;

use App\Models\PToP;
use Illuminate\Support\Carbon;
use App\Http\Controllers\MessengerController;
use Illuminate\Support\Facades\Log;

trait HasCreatePeerToPeer
{

    protected $buyeraccept;
    protected $acceptanceId;
    protected $sessionId;
    protected $paymentId;
    protected $sessionStatus;
    protected $paymentStatus;
    protected $proofOfPayment;
    protected $reportage;
    protected $durationStatus;

    public function payload($buyeraccept, $acceptanceId, $sessionId, $paymentId, $sessionStatus, $paymentStatus, $proofOfPayment, $reportage, $durationStatus)
    {
        $this->buyeraccept = $buyeraccept;
        $this->acceptanceId = $acceptanceId;
        $this->sessionId = $sessionId;
        $this->paymentId = $paymentId;
        $this->sessionStatus = $sessionStatus;
        $this->paymentStatus = $paymentStatus;
        $this->proofOfPayment = $proofOfPayment;
        $this->reportage = $reportage;
        $this->durationStatus = $durationStatus;
        return $this;
    }


    public function createPeerToPeer()
    {
        if ($this->buyeraccept->item_for == "sell") {
            $recipient = 'buyer';
            $owner = 'seller';

            PToP::create([
                'trade_registry'    => $this->buyeraccept->trade_registry,
                'acceptance_id'     =>  $this->acceptanceId,
                'session_id'        =>  $this->sessionId,
                'session_status'    =>   $this->sessionStatus,
                'item_for'          =>  $this->buyeraccept->item_for,
                'item_id'           =>  $this->buyeraccept->item_id,
                'item_name'         =>  $this->buyeraccept->wallet_name,
                'amount'            =>  $this->buyeraccept->amount,
                'amount_to_receive' =>  $this->buyeraccept->amount_to_receive,
                'trade_rate'        =>  $this->buyeraccept->trade_rate,
                'duration'          =>  $this->buyeraccept->duration,
                'duration_status'   =>  $this->durationStatus,
                'payment_id'        =>   $this->paymentId,
                'payment_status'    =>   $this->paymentStatus,
                'proof_of_payment'  =>   $this->proofOfPayment,
                'reportage'         =>  $this->reportage,
                'recipient'         =>  $recipient,
                'owner'             =>  $owner,
                'recipient_id'      =>  $this->buyeraccept->recipient,
                'owner_id'          =>  $this->buyeraccept->owner,
                'start_time'        =>  Carbon::now(),
                'end_time'          =>  Carbon::now()->addMinutes((int)$this->buyeraccept->duration),
                "fund_attached"     =>  $this->buyeraccept->fund_attached,
                "fund_reg"          =>  $this->buyeraccept->fund_reg,
                "charges_for"       =>  $this->buyeraccept->charges_for,
                "ratefy_fee"        =>  $this->buyeraccept->ratefy_fee,
                "percentage"        =>  $this->buyeraccept->percentage,

            ]);

            $messenger = app(MessengerController::class);
            $messenger->sendAcceptedTradeRequestNotification(
                owner: $this->buyeraccept->owner,
                recipient: $this->buyeraccept->recipient,
                amount: $this->buyeraccept->amount,
                amountInNaira: ((float)$this->buyeraccept->amount * (float)$this->buyeraccept->trade_rate),
                itemFor: $this->buyeraccept->item_for,
                walletName: $this->buyeraccept->wallet_name,
                itemId: $this->buyeraccept->item_id,
                acceptanceId: $this->acceptanceId,
                sessionId: $this->sessionId
            );
        } else {
            $recipient = 'seller';
            $owner = 'buyer';

            PToP::create([
                'trade_registry'    => $this->buyeraccept->trade_registry,
                'acceptance_id'     =>  $this->acceptanceId,
                'session_id'        =>  $this->sessionId,
                'session_status'    =>   $this->sessionStatus,
                'item_for'          =>  $this->buyeraccept->item_for,
                'item_id'           =>  $this->buyeraccept->item_id,
                'item_name'         =>  $this->buyeraccept->wallet_name,
                'amount'            =>  $this->buyeraccept->amount,
                'amount_to_receive' =>  $this->buyeraccept->amount_to_receive,
                'trade_rate'        =>  $this->buyeraccept->trade_rate,
                'duration'          =>  $this->buyeraccept->duration,
                'duration_status'   =>  $this->durationStatus,
                'payment_id'        =>   $this->paymentId,
                'payment_status'    =>   $this->paymentStatus,
                'proof_of_payment'  =>   $this->proofOfPayment,
                'reportage'         =>  $this->reportage,
                'recipient'         =>  $recipient,
                'owner'             =>  $owner,
                'recipient_id'      =>  $this->buyeraccept->recipient,
                'owner_id'          =>  $this->buyeraccept->owner,
                'start_time'        =>  Carbon::now(),
                'end_time'          =>  Carbon::now()->addMinutes((int)$this->buyeraccept->duration),
                "fund_attached"     =>  $this->buyeraccept->fund_attached,
                "fund_reg"          =>  $this->buyeraccept->fund_reg,
                "charges_for"       =>  $this->buyeraccept->charges_for,
                "ratefy_fee"        =>  $this->buyeraccept->ratefy_fee,
                "percentage"        =>  $this->buyeraccept->percentage,
            ]);


            $messenger = app(MessengerController::class);
            $messenger->sendAcceptedTradeRequestNotification(
                owner: $this->buyeraccept->owner,
                recipient: $this->buyeraccept->recipient,
                amount: $this->buyeraccept->amount,
                amountInNaira: ((float)$this->buyeraccept->amount * (float)$this->buyeraccept->trade_rate),
                itemFor: $this->buyeraccept->item_for,
                walletName: $this->buyeraccept->wallet_name,
                itemId: $this->buyeraccept->item_id,
                acceptanceId: $this->acceptanceId,
                sessionId: $this->sessionId
            );
        }
    }
}
