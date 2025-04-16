<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\TradeRequest;
use App\Models\CustomerStatus;
use App\Services\RejectTradeService;
use App\Services\KycCheckerService;
use App\Jobs\ProcessEscrowAccountJob;
use App\Jobs\ProcessPersonalAccountJob;
use Illuminate\Support\Facades\Log;


class QueueObserverService
{

    public function observeTradeRejection()
    {
        $trades = TradeRequest::where('status', 'active')
            ->where('created_at', '>=', Carbon::now()->subHours(22))
            ->get();

        foreach ($trades as $trade) {
            $this->processTradeRequestRejection(trade: $trade, data: ['id' => $trade->id, 'owner' => $trade->owner]);
        }
    }

    public function observeCreateSubAccount()
    {
        $customers = $this->getTodaysRecords(model: CustomerStatus::class);
        foreach ($customers as $customer) {
            $this->validateCustomerHasSubAccount(customer: $customer);
        }
    }

    public function observeReTriggerKyc()
    {
        $customers =  $this->getTodaysRecords(model: CustomerStatus::class);
        foreach ($customers as $customer) {
            $this->validateKycApproval(customer: $customer);
        }
    }

    public function processTradeRequestRejection($trade, $data)
    {
        if (!in_array($trade->status, ['rejected', 'cancelled', 'accepted'])) {

            app(RejectTradeService::class)
                ->validate($data)
                ->validateTradeExists()
                ->ReverseDebit()
                ->updateTrade()
                ->informStakeholder()
                ->throwState();
        } else {
            Log::info([$data, $trade->status]);
        }
    }


    public function checkDepositAccounts()
    {

        $customers =  $this->getWeeklysRecords(model: CustomerStatus::class);
        foreach ($customers as $customer) {
            $this->validateVerifiedCustomerForAccountUpdate(customer: $customer);
        }
    }


    public function validateCustomerHasSubAccount($customer)
    {


        if ($customer->first()->status && $customer->first()->status === "fully-verified") {
            if (!$customer->first()->escrowaccount()->exists()) {
                $this->processEscrowAccount(customer: $customer);
            }

            if (!$customer->first()->personalaccount()->exists()) {
                $this->processPersonalAccount(customer: $customer);
            }
        }
    }


    public function processEscrowAccount($customer)
    {
        $uuid = $customer->user->uuid;
        dispatch(new ProcessEscrowAccountJob($uuid))->delay(now()->addSeconds(2));
    }


    public function processPersonalAccount($customer)
    {
        $uuid = $customer->user->uuid;
        dispatch(new ProcessPersonalAccountJob($uuid))->delay(now()->addSeconds(2));
    }


    public function validateKycApproval($customer)
    {
        if ($customer->first()->status && !in_array($customer->first()->status, ["fully-verified", "rejected", "unverified", "semi-verified"])) {
            app(KycCheckerService::class)
                ->getUuid($customer->user->uuid)
                ->checkStatus()
                ->OnboardCustomerAgain();
        }
    }

    public function validateVerifiedCustomerForAccountUpdate($customer)
    {
        if ($customer->status == "fully-verified") {

            $response = app(UpdateAccountService::class)
                ->getUser(uuid: $customer->user->uuid)
                ->validateUserHasPersonalAccount()
                ->validateUserHasEscrowAccount()
                ->makePersonalNuban()
                ->makeEscrowNuban()
                ->setState()
                ->updateAccount();
            Log::info([$response]);
        }
    }

    public function getTodaysRecords($model)
    {

        return  $model::whereDate('created_at', Carbon::yesterday())->get();
    }


    public function getWeeklysRecords($model)
    {

        return  $model::where('created_at', '>=', Carbon::now()->subHours(3))->get();
    }
}
