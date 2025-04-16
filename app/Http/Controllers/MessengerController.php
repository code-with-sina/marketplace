<?php

namespace App\Http\Controllers;


use App\Audit\Trail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\TradeRequestJob;
use App\Jobs\RejectRequestJob;
use App\Jobs\AcceptTradeRequestJob;
use App\Jobs\CancelTradeRequestJob;
use App\Jobs\PaymentReleasedJob;
use App\Mail\BalanceWithdrawal;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Facades\Log;



class MessengerController extends Controller
{

    public function sendInitiatedTradeRequestNotification($owner, $recipient, $amount, $amountInNaira, $itemFor, $walletName, $itemId)
    {
        dispatch(new TradeRequestJob($owner, $recipient, $amount, $amountInNaira, $itemFor, $walletName, $itemId))->delay(now()->addSeconds(2));
    }

    public function sendRejectTradeRequestNotification($owner, $recipient, $amount, $amountInNaira, $itemFor, $walletName, $itemId)
    {
        RejectRequestJob::dispatch(
            recipient: $recipient,
            owner: $owner,
            amount: $amount,
            amountInNaira: $amountInNaira,
            item: $itemFor,
            wallet_name: $walletName,
            item_id: $itemId
        );
    }

    public function sendAcceptedTradeRequestNotification($owner, $recipient, $amount, $amountInNaira, $itemFor, $walletName, $itemId, $acceptanceId, $sessionId)
    {
        AcceptTradeRequestJob::dispatch(
            owner: $owner,
            recipient: $recipient,
            amount: $amount,
            amountInNaira: $amountInNaira,
            item: $itemFor,
            item_id: $itemId,
            wallet_name: $walletName,
            acceptance_id: $acceptanceId,
            session_id: $sessionId
        );
    }


    public function sendCancelTradeRequestNotification($owner, $recipient, $amount, $amountInNaira,  $itemFor, $walletName, $itemId)
    {
        CancelTradeRequestJob::dispatch(
            owner: $owner,
            recipient: $recipient,
            amount: $amount,
            amountInNaira: $amountInNaira,
            item: $itemFor,
            wallet_name: $walletName,
            item_id: $itemId
        );
    }


    public function sendTradeCompletionSuccessNotification($owner, $recipient, $amount, $itemFor, $itemName, $itemId, $amountToRecieve)
    {
        PaymentReleasedJob::dispatch(
            owner: $owner,
            recipient: $recipient,
            amount: $amount,
            item: $itemFor,
            wallet_name: $itemName,
            item_id: $itemId,
            amount_to_receive: $amountToRecieve
        );
    }

    public function sendWithdrawalSuccessNotification($uuid, $amount)
    {
        $user = User::where('uuid', $uuid)->first();
        Mail::to($user)->send(new BalanceWithdrawal(amount: $amount, firstname: $user->firstname));
    }
}
