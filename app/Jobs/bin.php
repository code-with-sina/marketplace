<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\AdminFacades\HasObjectConverter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class CreateAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasObjectConverter, HasEscrowAccount, HasPersonalAccount;
    public $uuid;
    public $tries = 2;
    /**
     * Create a new job instance.
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->createEscrowAccount($this->uuid);
        Sleep::for(2)->second();
        $this->createPersonalAccount($this->uuid);
    }


    public function createEscrowAccount() {
        Sleep::for(2)->second();
        $user = User::where('uuid', $this->uuid)->first();

        $checkRoot = $user->customerstatus()->first();
        $payload = [
           'data'  => [
                'attributes' => [
                    'productName' => 'SAVINGS',
                ],
                'relationships'  => [
                    'customer'  => [
                        'data' => [
                            'id' => $checkRoot->customerId,
                            'type' => $checkRoot->type
                        ]
                    ]

                ],
                'type' => 'DepositAccount'
            ] 
        ];
        $escrowPayload = $this->ToObject($payload);
        $escrowDeposit = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'), 
        ])->post(env('ANCHOR_SANDBOX').'accounts', $escrowPayload);



        if($escrowDeposit->status() === 200 || $escrowDeposit->status() === 202) {
            $escrow = $escrowDeposit->object();
            $checkRoot->customerstatus()->first()->customer()->first()->escrowaccount()->create([
                'escrowId'      => $escrow->data->id,
                'escrowType'    => $escrow->data->type
            ]);

            $fetchEscrowAccount = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'), 
            ])->get(env('ANCHOR_SANDBOX')."accounts/".$checkRoot->customerstatus()->first()->customer()->first()->escrowaccount()->first()->escrowId."?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer" );

            if($fetchEscrowAccount->status() === 200 || $fetchEscrowAccount->status() === 202) {
                $accounts = $fetchEscrowAccount->object();
                $checkRoot->customer()->first()->escrowaccount()->update([
                    'escrowId'      => $accounts->data->id,
                    'escrowType'    => $accounts->data->type,
                    'bankId'        =>  $accounts->data->attributes->bank->id, 
                    'bankName'      =>  $accounts->data->attributes->bank->name, 
                    'cbnCode'       =>  $accounts->data->attributes->bank->cbnCode, 
                    'nipCode'       =>  $accounts->data->attributes->bank->nipCode, 
                    'accountName'       =>  $accounts->data->attributes->accountName, 
                    'accountNumber'     =>  $accounts->data->attributes->accountNumber, 
                    'type'      =>  $accounts->data->attributes->type, 
                    'status'        =>  $accounts->data->attributes->status, 
                    'frozen'        =>  $accounts->data->attributes->frozen, 
                    'currency'      =>  $accounts->data->attributes->currency, 
                    'virtualNubans_id'      =>  $accounts->data->relationships->virtualNubans->data[0]->id, 
                    'virtualNubans_type'        =>  $accounts->data->relationships->virtualNubans->data[0]->type,
                ]);

                $this->createEscrowAccountNuban($checkRoot->uuid);

                // return response()->json($checkRoot->load(['customer', 'customer.escrowaccount']));
            }

            
        }
    }

    public function createPersonalAccount($uuid) {
        Sleep::for(2)->second();

        $user = User::where('uuid', $this->uuid)->first();
        $checkRoot = $user->customerstatus()->first();
        $payload = [
           'data'  => [
                'attributes' => [
                    'productName' => 'SAVINGS',
                ],
                'relationships'  => [
                    'customer'  => [
                        'data' => [
                            'id' => $checkRoot->customerId,
                            'type' => $checkRoot->type
                        ]
                    ]

                ],
                'type' => 'DepositAccount'
            ] 
        ];

        $personalPayload = $this->ToObject($payload);
        $personalDeposit = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'), 
        ])->post(env('ANCHOR_SANDBOX').'accounts', $personalPayload);

        if($personalDeposit->status() === 200 || $personalDeposit->status() === 202) {
            $personal = $personalDeposit->object();
            $checkRoot->customerstatus()->first()->customer()->first()->personalaccount()->create([
                'personalId'      => $personal->data->id,
                'personalType'    => $personal->data->type
            ]);

            $fetchPersonalAccount = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'), 
            ])->get(env('ANCHOR_SANDBOX')."accounts/".$checkRoot->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId."?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer" );

            if($fetchPersonalAccount->status() === 200 || $fetchPersonalAccount->status() === 202) {
                $accounts = $fetchPersonalAccount->object();
                $checkRoot->customerstatus()->first()->customer()->first()->personalaccount()->update([
                    'personalId'      => $accounts->data->id,
                    'personalType'    => $accounts->data->type,
                    'bankId'        =>  $accounts->data->attributes->bank->id, 
                    'bankName'      =>  $accounts->data->attributes->bank->name, 
                    'cbnCode'       =>  $accounts->data->attributes->bank->cbnCode, 
                    'nipCode'       =>  $accounts->data->attributes->bank->nipCode, 
                    'accountName'       =>  $accounts->data->attributes->accountName, 
                    'accountNumber'     =>  $accounts->data->attributes->accountNumber, 
                    'type'      =>  $accounts->data->attributes->type, 
                    'status'        =>  $accounts->data->attributes->status, 
                    'frozen'        =>  $accounts->data->attributes->frozen, 
                    'currency'      =>  $accounts->data->attributes->currency, 
                    'virtualNubans_id'      =>  $accounts->data->relationships->virtualNubans->data[0]->id, 
                    'virtualNubans_type'        =>  $accounts->data->relationships->virtualNubans->data[0]->type,
                ]);

                $this->createPersonalAccountNuban($checkRoot->uuid);
                // return response()->json($checkRoot->load(['customer', 'customer.personalaccount']));
            }
        }   
    }


    public function createEscrowAccountNuban($uuid) {
        Sleep::for(2)->second();
        $user = User::where('uuid', $this->uuid)->first();
        $getUser = $user->customerstatus()->first();
        $fetchEscrowNuban = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'), 
        ])->get(env('ANCHOR_SANDBOX')."virtual-nubans/".$getUser->customerstatus()->first()->customer()->first()->escrowaccount()->first()->virtualNubans_id);
        if($fetchEscrowNuban->status() === 200 || $fetchEscrowNuban->status() === 202) {
            $nuban = $fetchEscrowNuban->object();
            $getUser->customerstatus()->first()->customer()->first()->escrowaccount()->first()->virtualnuban()->create([
                'nubanId'       =>  $nuban->data->id, 
                'nubanType'     =>  $nuban->data->type, 
                'bankId'        =>  $nuban->data->attributes->bank->id, 
                'bankName'      =>  $nuban->data->attributes->bank->name, 
                'nipCode'       =>  $nuban->data->attributes->bank->nipCode, 
                'accountName'       =>  $nuban->data->attributes->accountName, 
                'accountNumber'     =>  $nuban->data->attributes->accountNumber, 
                'currency'      =>  $nuban->data->attributes->currency, 
                'permanent'     =>  $nuban->data->attributes->permanent, 
                'isDefault'     =>  $nuban->data->attributes->isDefault,
            ]);

            // return response()->json($getUser->load(['customer', 'customer.escrowaccount', 'customer.escrowaccount.virtualnuban']));

        }

        
    }


    public function createPersonalAccountNuban($uuid) {
        Sleep::for(2)->second();
        $user = User::where('uuid', $this->uuid)->first();
        $getUser = $user->customerstatus()->first();
        $fetchPersonalNuban = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'), 
        ])->get(env('ANCHOR_SANDBOX')."virtual-nubans/".$getUser->customerstatus()->first()->customer()->first()->personalaccount()->first()->virtualNubans_id);
        if($fetchPersonalNuban->status() === 200 || $fetchPersonalNuban->status() === 202) {
            $nuban = $fetchPersonalNuban->object();
            $getUser->customerstatus()->first()->customer()->first()->personalaccount()->first()->virtualnuban()->create([
                'nubanId'       =>  $nuban->data->id, 
                'nubanType'     =>  $nuban->data->type, 
                'bankId'        =>  $nuban->data->attributes->bank->id, 
                'bankName'      =>  $nuban->data->attributes->bank->name, 
                'nipCode'       =>  $nuban->data->attributes->bank->nipCode, 
                'accountName'       =>  $nuban->data->attributes->accountName, 
                'accountNumber'     =>  $nuban->data->attributes->accountNumber, 
                'currency'      =>  $nuban->data->attributes->currency, 
                'permanent'     =>  $nuban->data->attributes->permanent, 
                'isDefault'     =>  $nuban->data->attributes->isDefault,
            ]);

            // return response()->json($getUser->load(['customer', 'customer.personalaccount', 'customer.personalaccount.virtualnuban']));
        }
    }
}
