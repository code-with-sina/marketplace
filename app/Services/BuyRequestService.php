<?php

namespace App\Services;


use App\Models\User;
use Illuminate\Support\Str;
use App\Models\TradeRequest;
use App\Services\DebitService;
use Illuminate\Support\Carbon;
use App\Prop\FeeDeterminantAid;
use App\Services\ChargeService;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessengerController;
use Illuminate\Support\Facades\Validator;
use App\Jobs\TradeRequestNotificationJob;
use App\Jobs\AutoCancelTradeRequest;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\TransactionHookController;
use App\Models\TempTradeData;
use App\Services\BalanceService;

class BuyRequestService
{

    private $errorState = false;
    private $error;
    private $state;
    private $data;
    private $offerItem;
    private $charge;
    private TradeRequest $trade;
    private $direction;
    private $debit;
    private $reference;
    const DURATION = "30";
    const  NOTIFY_TIME = "start";
    const  FUND_ATTACHED = "yes";
    const  STATUS = "active";
    public function validate($data)
    {
        $validation = Validator::make($data, [
            'wallet_name'       => ['required', 'string'],
            'offer_rate'        => ['required'],
            "wallet_id"         => ['required'],
            "item_for"          => ['required', 'string'],
            "item_id"           => ['required', 'string'],
            "amount"            => ['required'],
            "amount_to_receive" => ['required'],
            "recipient"         => ['required', 'string']
        ]);

        if ($validation->fails()) {
            $this->setErrorState(status: 400, message: $validation->errors());
            return $this;
        }

        $this->data = (object)$data;
        return $this;
    }

    public function validateBalance()
    {
        if ($this->errorState) {
            return $this;
        }

        $balance = app(BalanceService::class)
            ->payload(uuid: auth()->user()->uuid, amount: (float)$this->data->amount_to_receive)
            ->getBalance()
            ->compareBalance()
            ->throwStatus();

        if ($balance->status !== 200) {
            $this->setErrorState(status: 400, message: $balance->title);
        }

        return $this;
    }



    public function DeterminantToolKit()
    {
        if ($this->errorState) {
            return $this;
        }

        $offerDetail = new FeeDeterminantAid();

        $this->offerItem = $this->data->item_for === 'buy'
            ? $offerDetail->detailOffer(direction: ($this->direction = 'selleroffer'), id: $this->data->item_id)
            : $offerDetail->detailOffer(direction: ($this->direction = 'buyeroffer'), id: $this->data->item_id);

        if (!$this->offerItem) {
            $this->setErrorState(status: 400, message: __("Sorry! This offer no longer exists on the recipient's list of offerings"));
            return $this;
        }

        return $this;
    }


    public function prepareCharge()
    {
        if ($this->errorState) {
            return $this;
        }

        try {
            $this->charge = app(ChargeService::class)
                ->getOffer(itemFor: $this->data->item_for, tradeTotal: (float)$this->data->amount, itemId: $this->data->item_id)
                ->getTradeOwner()
                ->prepareChargeStatement()
                ->state();

            return $this;
        } catch (\Exception $e) {
            $this->setErrorState(status: 400, message: __("Sorry! We couldn't determine the owner of this trade. The owner might be blocked, and the trade cannot proceed." . $e->getMessage()));
            return $this;
        }

        return $this;
    }


    public function debit()
    {
        if ($this->errorState) {
            return $this;
        }

        $initReceipt = app(TransactionHookController::class);
        $debitAmount = ((float)$this->data->offer_rate * (float)$this->data->amount);
        $this->reference = Str::uuid();
        $initReceipt->initBuyerRequestDebit(uuid: auth()->user()->uuid, reference: $this->reference);
        $this->debit = app(DebitService::class)
            ->getAmount(amount: $debitAmount, ref: $this->reference, uuid: auth()->user()->uuid)
            ->getInitialBalance()
            ->compareBalance()
            ->processTransaction()
            ->createJournal()
            ->throwState();

        if ($this->debit->status !== 200) {
            $this->setErrorState(status: $this->debit->status, message: $this->debit->title);
            return $this;
        }

        return $this;
    }

    public function createTempTradeData()
    {
        if ($this->errorState) {
            return $this;
        }

        try {

            TempTradeData::create([
                'wallet_name'       => $this->data->wallet_name,
                'wallet_id'         => $this->data->wallet_id,
                'item_for'          => $this->data->item_for,
                'item_id'           => $this->data->item_id,
                'amount'            => $this->data->amount,
                'trade_rate'        => $this->data->offer_rate,
                'amount_to_receive' => $this->data->amount_to_receive,
                'owner'             => auth()->user()->uuid,
                'recipient'         => $this->data->recipient,
                'duration'          => self::DURATION,
                'start'             => Carbon::now(),
                'end'               => Carbon::now()->addMinutes(30),
                'notify_time'       => self::NOTIFY_TIME,
                'fund_attached'     => self::FUND_ATTACHED,
                'fund_reg'          => $this->reference,
                'charges_for'       => $this->direction === "buyeroffer" ? "buyer" : "seller",
                'ratefy_fee'        => $this->offerItem->ratefyfee  == null ? 'null' : $this->offerItem->ratefyfee,
                'percentage'        => $this->offerItem->percentage  == null ? 'null' : $this->offerItem->percentage,
                'trade_registry'    => Str::uuid(),
                'status'            => self::STATUS
            ]);

            return $this;
        } catch (\Exception $e) {
            $this->setErrorState(status: 400, message: __("Sorry! We couldn't create a trade request at the moment. Please try again later." . $e->getMessage()));
            return $this;
        }

        return $this;
    }

    // public function createTradeRequest()
    // {
    //     if ($this->errorState) {
    //         return $this;
    //     }


    //     try {

    //         $this->trade = TradeRequest::create([
    //             'wallet_name'       => $this->data->wallet_name,
    //             'wallet_id'         => $this->data->wallet_id,
    //             'item_for'          => $this->data->item_for,
    //             'item_id'           => $this->data->item_id,
    //             'amount'            => $this->data->amount,
    //             'trade_rate'        => $this->data->offer_rate,
    //             'amount_to_receive' => $this->data->amount_to_receive,
    //             'owner'             => auth()->user()->uuid,
    //             'recipient'         => $this->data->recipient,
    //             'duration'          => self::DURATION,
    //             'start'             => Carbon::now(),
    //             'end'               => Carbon::now()->addMinutes(30),
    //             'notify_time'       => self::NOTIFY_TIME,
    //             'fund_attached'     => self::FUND_ATTACHED,
    //             'fund_reg'          => $this->reference,
    //             'charges_for'       => $this->direction === "buyeroffer" ? "buyer" : "seller",
    //             'ratefy_fee'        => $this->offerItem->ratefyfee  == null ? 'null' : $this->offerItem->ratefyfee,
    //             'percentage'        => $this->offerItem->percentage  == null ? 'null' : $this->offerItem->percentage,
    //             'trade_registry'    => Str::uuid(),
    //             'status'            => self::STATUS
    //         ]);

    //         $this->trade->charge()->create([
    //             'product'   => $this->charge['product'],
    //             'offer'   => $this->charge['offer'],
    //             'owner'   => $this->charge['owner'],
    //             'uuid'   => $this->charge['uuid'],
    //             'fee'   => $this->charge['prepared invoice']['fee'],
    //             'total'   => $this->charge['prepared invoice']['total'],
    //         ]);

    //         return $this;
    //     } catch (\Exception $e) {
    //         $this->setErrorState(status: 400, message: __("Sorry! We couldn't create a trade request at the moment. Please try again later." . $e->getMessage()));
    //         return $this;
    //     }

    //     return $this;
    // }

    // public function sendAdminNotification()
    // {
    //     if ($this->errorState) {
    //         return $this;
    //     }

    //     $staffing = Http::get('https://staffbased.ratefy.co/api/admin-staff');
    //     $staffs = $staffing->object();
    //     $groupStaff = [];
    //     foreach ($staffs as $staff) {
    //         $groupStaff[] = $staff->email;
    //     }
    //     $content = $this->createContent($this->trade);
    //     $admin = app(AdminController::class);
    //     $admin->staffsNotification(direction: $this->direction, content: $content, groupStaff: $groupStaff, uuid: $this->trade->owner,   id: $this->trade->id);

    //     return $this;
    // }
    // public function notifyRecipient()
    // {
    //     if ($this->errorState) {
    //         return $this;
    //     }

    //     $messenger = app(MessengerController::class);
    //     $messenger->sendInitiatedTradeRequestNotification(
    //         owner: $this->trade->owner,
    //         recipient: $this->trade->recipient,
    //         amount: (float)$this->trade->amount,
    //         amountInNaira: ($this->trade->amount * $this->trade->trade_rate),
    //         itemFor: $this->trade->item_for,
    //         itemId: $this->trade->item_id,
    //         walletName: $this->trade->wallet_name
    //     );

    //     return $this;
    // }

    // public function autoCancelTradeRequest()
    // {
    //     if ($this->errorState)
    //         return $this;

    //     AutoCancelTradeRequest::dispatch($this->trade->id, $this->trade->owner)->delay(now()->addMinutes(30));
    //     Log::info('I was here, auto cancel');
    //     return $this;
    // }

    public function successState()
    {
        if ($this->errorState) {
            return $this;
        }

        $this->setSuccessState(
            status: 200,
            message: __("Trade request sent! Kindly wait for a response in 30 minutes."),
            data: $this->data
        );
        return $this;
    }

    public function throwStatus()
    {

        return $this->errorState ? $this->error : $this->state;
    }



    public function setSuccessState($status = 200, mixed $message, mixed $data)
    {
        $this->state = (object) [
            "status"    => $status,
            "message"   => $message,
            "state"     => $this->debit,
            // "response"  => $this->trade
        ];

        return $this;
    }


    public function setErrorState(int $status = 400, mixed $message)
    {
        $this->errorState = true;
        $this->error = (object) [
            "status"    => $status,
            "message"   => $message
        ];

        return $this;
    }

    public function createContent($trade)
    {
        return __(
            "Product Name : {$trade->wallet_name},\n" .
                "Amount : {$trade->amount},\n" .
                "Time:" . Carbon::now()
        );
    }
}
