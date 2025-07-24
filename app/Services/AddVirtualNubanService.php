<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class AddVirtualNubanService
{
    public $processData;
    public $customer;
    public $state = false;
    public $success;
    public $failed;

    public function getVirtualNuban($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->setFailedState(400, __("User not found"));
        }

        $status = $user->customerstatus()->first();
        if (!$status) {
            return $this->setFailedState(400, __("User status record not found"));
        }

        $this->customer = $status->customer()->first();
        if (!$this->customer) {
            return $this->setFailedState(400, __("User customer record not found"));
        }

        $this->processData = [
            'personal' => $this->getPersonalAccount($this->customer),
            'escrow'   => $this->getEscrowAccount($this->customer),
        ];

        return $this;
    }

    public function createVirtualNuban()
    {
        if (is_null($this->processData['personal'])) {
            return $this->setFailedState(400, __("User personal account created"));
        }

        if (is_null($this->processData['escrow'])) {
            return $this->setFailedState(400, __("User escrow account created"));
        }

        $personal = $this->makeRequest((string) $this->processData['personal']);
        if (in_array($personal->statusCode, [200, 202])) {
            $this->savePersonalToDb($personal->data->data);
        }

        $escrow = $this->makeRequest((string) $this->processData['escrow']);
        if (in_array($escrow->statusCode, [200, 202])) {
            $this->saveEscrowToDb($escrow->data->data);
        }

        return $this->setSuccessState(200, __("Account updated successfully"));
    }

    public function show()
    {
        return $this->state ? $this->failed : $this->success;
    }

    public function savePersonalToDb($personal)
    {
        $this->customer->personalaccount()->first()?->virtualnuban()->create([
            'nubanId'       => $personal->id,
            'nubanType'     => $personal->type,
            'bankId'        => $personal->attributes->bank->id,
            'bankName'      => $personal->attributes->bank->name,
            'nipCode'       => $personal->attributes->bank->nipCode,
            'accountName'   => $personal->attributes->accountName,
            'accountNumber' => $personal->attributes->accountNumber,
            'currency'      => $personal->attributes->currency,
            'permanent'     => $personal->attributes->permanent,
            'isDefault'     => $personal->attributes->isDefault,
        ]);
    }

    public function saveEscrowToDb($escrow)
    {
        $this->customer->escrowaccount()->first()?->virtualnuban()->create([
            'nubanId'       => $escrow->id,
            'nubanType'     => $escrow->type,
            'bankId'        => $escrow->attributes->bank->id,
            'bankName'      => $escrow->attributes->bank->name,
            'nipCode'       => $escrow->attributes->bank->nipCode,
            'accountName'   => $escrow->attributes->accountName,
            'accountNumber' => $escrow->attributes->accountNumber,
            'currency'      => $escrow->attributes->currency,
            'permanent'     => $escrow->attributes->permanent,
            'isDefault'     => $escrow->attributes->isDefault,
        ]);
    }

    public function getPersonalAccount($customer)
    {
        $account = $customer->personalaccount()->first();
        
        if (!$account) {
            return null;
        }

        return $account->virtualNubans_id;
    }

    public function getEscrowAccount($customer)
    {
        $account = $customer->escrowaccount()->first();

        if (!$account) {
            return null;
        }

        return $account->virtualNubans_id;
    }

    public function setFailedState($status = 400, $message)
    {
        $this->state = true;
        $this->failed = (object)[
            'status'  => $status,
            'message' => $message,
        ];
        return $this;
    }

    public function setSuccessState($status = 200, $message)
    {
        $this->success = (object)[
            'status'  => $status,
            'message' => $message,
        ];
        return $this;
    }

    public function makeRequest($processedPath)
    {
        try {
            $response = Http::withHeaders([
                'accept'       => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->get(env('ANCHOR_SANDBOX') .
                "virtual-nubans/{$processedPath}" .
                "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

            return (object)[
                'statusCode' => $response->status(),
                'data'       => $response->object(),
            ];
        } catch (\Exception $e) {
            return (object)[
                'statusCode' => 500,
                'data'       => $e->getMessage(),
            ];
        }
    }
}