<?php 

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;


class GetBalanceService 
{
    public $failed;
    public $success;
    public $balance;
    public $accountId;
    public User $user;
    public $accountType;
    public $processedPath;
    public $state = false;
    
    const EscrowType = "escrow";
    const PersonalType = "personal";


    public function getPayload($accountType, $accountId, $id) 
    {

        $this->accountType = $accountType;
        $this->accountId = $accountId;
        $this->user = User::find($id);

        return $this;
    }

    public function processPayload() 
    {
        if($this->state)
            return $this;

        $this->processedPath = $this->accountType === self::EscrowType 
            ? $this->getEscrowDetail($this->accountId) : $this->getPersonalDetail($this->accountId);

        return $this;
    }


    public function getBalance() 
    {
        if($this->state)
            return $this;

        
        $response = $this->makeRequest($this->processedPath);

        if(!in_array($response->statusCode, [200, 201, 202])) {
            $this->setFailedState(status: 400, message: $response->message);
            return $this;
        }

       
        $item = $response->data->data ?? null;
      

        $this->balance = $this->accountType === self::EscrowType 
            ? $this->updateEscrowAccountBalance(
                availableBalance: ($item->availableBalance / 100),
                ledgerBalance: ($item->ledgerBalance / 100),
                hold: $item->hold,
                pending: $item->pending
            ) 
            : $this->updatePersonalAccountBalance(
                availableBalance: ($item->availableBalance / 100),
                ledgerBalance: ($item->ledgerBalance / 100),
                hold: $item->hold,
                pending: $item->pending
            );
        

        
        $this->setSuccessState(status: 200, data: $this->balance);
        return $this;
    }


    public function show() 
    {
        return $this->state ? $this->failed : $this->success;
    }

    public function setFailedState($status = 400, $message) 
    {
        $this->state =  true;
        return $this->failed = (object)[
            'status'    => $status,
            'message'   => $message
        ];
    }

    public function setSuccessState($status = 200, $data) 
    {
        return $this->success = (object)[
            'status'    => $status,
            'data'   => $data
        ];
    }

    public function getPersonalDetail($accountId) 
    {
        return $accountId->personalaccount()->first()->personalId;
        
    }


    public function getEscrowDetail($accountId) 
    {
        return $accountId->escrowaccount()->first()->escrowId;
    }


    public function updatePersonalAccountBalance($availableBalance, $ledgerBalance, $hold, $pending) 
    {
            $status = $this->user->customerstatus()->first();
            $customer = $status->customer()->first();
            $personalAccount = $customer->personalaccount()->first();
            $balance = $personalAccount->personalbalance()->first();
        if (!$balance) {
            
            $balance = $personalAccount->personalbalance()->create([
                'uuid'              => $this->user->uuid,
                'availableBalance'  => $availableBalance,
                'ledgerBalance'     => $ledgerBalance,
                'hold'              => $hold,
                'pending'           => $pending,
            ]);
         
            return $personalAccount->personalbalance()->first();
        } else {
            $balance = $personalAccount->personalbalance()->update([
                'uuid'              => $this->user->uuid,
                'availableBalance'  => $availableBalance,
                'ledgerBalance'     => $ledgerBalance,
                'hold'              => $hold,
                'pending'           => $pending,
            ]);

            return $personalAccount->personalbalance()->first();
        }
    }


    public function updateEscrowAccountBalance($availableBalance, $ledgerBalance, $hold, $pending) 
    {
        $status = $this->user->customerstatus()->first();
        $customer = $status->customer()->first();
        $escrowAccount = $customer->escrowaccount()->first();
        $balance = $escrowAccount->escrowbalance()->first();

        if ($balance == null || empty($balance)) {

            $balance = $escrowAccount->escrowbalance()->create([
                'uuid'              => $this->user->uuid,
                'availableBalance'  => $availableBalance,
                'ledgerBalance'     => $ledgerBalance,
                'hold'              => $hold,
                'pending'           => $pending,
            ]);


            return $escrowAccount->escrowbalance()->first();
        } else {
            $balance = $escrowAccount->escrowbalance()->update([
                'uuid'              => $this->user->uuid,
                'availableBalance'  => $availableBalance,
                'ledgerBalance'     => $ledgerBalance,
                'hold'              => $hold,
                'pending'           => $pending,
            ]);


            return $escrowAccount->escrowbalance()->first();
            
        }
    }

    public function makeRequest($processedPath) 
    {
        try{
            $response = Http::withHeaders([
                'accept'        => 'application/json',
                'content-type'  => 'application/json',
                'x-anchor-key'  => env('ANCHOR_KEY'),
            ])->get(env('ANCHOR_SANDBOX') 
                . "accounts/balance/" 
                . $processedPath 
                . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

            return (object)['statusCode' => $response->status(), 'data' => $response->object()];
        }catch(\Exception $e) {
            return (object)['statusCode' => 500, 'data' => $e->getMessage()];
        }
    }   

}