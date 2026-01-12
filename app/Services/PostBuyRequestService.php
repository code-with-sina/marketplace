<?php

namespace App\Services;

use App\Models\User;
use App\Models\AdminAuth;
use Illuminate\Support\Str;
use App\Models\TradeRequest;
use Illuminate\Support\Carbon;
use App\Services\ChargeService;
use App\Prop\FeeDeterminantAid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\AutoCancelTradeRequest;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessengerController;
use App\Models\TempTradeData;

class PostBuyRequestService
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


    public function retreiveTempTradeData($reference) 
    {
        $this->data = TempTradeData::where('fund_reg', $reference)->first();
        
        if (!$this->data) {
            $this->setErrorState(status: 400, message: __("Sorry! We couldn't find a trade request with this reference."));
            return $this;
        }

        Log::info("step 1");
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

    public function createTradeRequest()
    {
        if ($this->errorState) {
            return $this;
        }

        
        try {

            $this->trade = TradeRequest::create([
                'wallet_name'       => $this->data->wallet_name,
                'wallet_id'         => $this->data->wallet_id,
                'item_for'          => $this->data->item_for,
                'item_id'           => $this->data->item_id,
                'amount'            => $this->data->amount,
                'trade_rate'        => $this->data->trade_rate,
                'amount_to_receive' => $this->data->amount_to_receive,
                'owner'             => $this->data->owner,
                'recipient'         => $this->data->recipient,
                'duration'          => $this->data->duration,
                'start'             => $this->data->start,
                'end'               => $this->data->end,
                'notify_time'       => $this->data->notify_time,
                'fund_attached'     => $this->data->fund_attached,
                'fund_reg'          => $this->data->fund_reg,
                'charges_for'       => $this->data->charges_for,
                'ratefy_fee'        => $this->data->ratefy_fee,
                'percentage'        => $this->data->percentage,
                'trade_registry'    => $this->data->trade_registry,
                'status'            => $this->data->status
            ]);
            Log::info($this->trade);
            $this->trade->charge()->create([
                'product'   => $this->charge['product'],
                'offer'   => $this->charge['offer'],
                'owner'   => $this->charge['owner'],
                'uuid'   => $this->charge['uuid'],
                'fee'   => $this->charge['prepared invoice']['fee'],
                'total'   => $this->charge['prepared invoice']['total'],
            ]);

            return $this;
        } catch (\Exception $e) {
            Log::info("Sorry! We couldn't create a trade request at the moment. Please try again later." . $e->getMessage());
            $this->setErrorState(status: 400, message: __("Sorry! We couldn't create a trade request at the moment. Please try again later." . $e->getMessage()));
            return $this;
        }

        Log::info("step 2");
        return $this;
    }

    public function sendAdminNotification()
    {
        if ($this->errorState) {
            return $this;
        }

        // $staffing = Http::get('https://staffbased.ratefy.co/api/admin-staff');
        // $staffs = $staffing->object();
        // $groupStaff = [];

        
        // foreach ($staffs as $staff) {
        //     $groupStaff[] = $staff->email;
        // }

        $adminsTable = AdminAuth::get();
        $groupStaff = [];
        $allowedEmails = [
            'femiivictorr@gmail.com',
            'gafaromolabakesoliat171@gmail.com',
            'judithmbama6@gmail.com',
        ];
        foreach ($adminsTable as $staff) {
             Log::info(['admin emails', $staff->email]);
            if (!in_array($staff->email, $allowedEmails)) {
                continue;
            }
            
            $groupStaff[] = $staff->email;
            Log::info(['staff email', $staff->email]);
        }
        $content = $this->createContent($this->trade);
        $admin = app(AdminController::class);
        $admin->staffsNotification(direction: $this->direction, content: $content, groupStaff: $groupStaff, uuid: $this->trade->owner,   id: $this->trade->id);

        Log::info("step 3");
        return $this;
    }


    public function notifyRecipient()
    {
        if ($this->errorState) {
            return $this;
        }

        $messenger = app(MessengerController::class);
        $messenger->sendInitiatedTradeRequestNotification(
            owner: $this->trade->owner,
            recipient: $this->trade->recipient,
            amount: (float)$this->trade->amount,
            amountInNaira: ($this->trade->amount * $this->trade->trade_rate),
            itemFor: $this->trade->item_for,
            itemId: $this->trade->item_id,
            walletName: $this->trade->wallet_name
        );

        Log::info("step 4");
        return $this;
    }


    
    

    public function autoCancelTradeRequest()
    {
        if ($this->errorState)
            return $this;

        AutoCancelTradeRequest::dispatch($this->trade->id, $this->trade->owner)->delay(now()->addMinutes(30));
        Log::info('I was here, auto cancel');
        return $this;
    }

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
            "response"  => $this->trade
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