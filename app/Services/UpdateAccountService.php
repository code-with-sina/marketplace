<?php

namespace App\Services;


use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class UpdateAccountService
{
    private User $user;
    private $error;
    private $errorState = false;
    private $success;
    private $personal;
    private $escrow;
    private $accountPersonal;
    private $accountEscrow;
    private $escrowVirtual;
    private $personalVitual;
    private $escrowNuban;
    private $personalNuban;


    public function getUser($uuid)
    {
        $this->user = User::where('uuid', $uuid)->first();

        if (!$this->user) {
            $this->setErrorState(status: 400, title: __("User not found"));
            return $this;
        }

        return $this;
    }

    public function validateUserHasPersonalAccount()
    {
        if ($this->errorState) {
            return $this;
        }

        $customerStatus = $this->user->customerstatus()->first();
        $customer = $customerStatus?->customer()->first();
        $this->personal = $customer?->personalaccount()->first();
        if ($this->personal && (empty($this->personal->bankId) || empty($this->personal->bankName) || empty($this->personal->nipCode))) {
            $this->updatePersonalAccount($this->personal->personalId);
        }

        return $this;
    }


    public function validateUserHasEscrowAccount()
    {
        if ($this->errorState) {
            return $this;
        }

        $customerStatus = $this->user->customerstatus()->first();
        $customer = $customerStatus?->customer()->first();
        $this->escrow = $customer?->escrowaccount()->first();

        if ($this->escrow && (empty($this->escrow->bankId) || empty($this->escrow->bankName) || empty($this->escrow->nipCode))) {
            $this->updateEscrowAccount($this->escrow->escrowId);
        }

        return $this;
    }

    public function makePersonalNuban()
    {
        if ($this->errorState) {
            return $this;
        }
        $this->setPersonalNuban();
        return $this;
    }


    public function makeEscrowNuban()
    {
        if ($this->errorState) {
            return $this;
        }
        $this->setEscrowNuban();
        return $this;
    }


    public  function updatePersonalAccount($source)
    {
        if ($this->errorState) {
            return $this;
        }

        $this->accountPersonal = $this->transportClient(
            method: "get",
            params: "{$source}?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
            objectData: null,
            endpoint: "accounts"
        );

        Log::info([$this->accountPersonal->data->data->relationships]);
        $customerStatus = $this->user->customerstatus()->first();
        $customer = $customerStatus?->customer()->first();
        $this->personal = $customer?->personalaccount()->first();
        if ($this->personal && (empty($this->personal->bankId) || empty($this->personal->bankName) || empty($this->personal->nipCode))) {

            $this->personal->update([
                "bankId" => $this->accountPersonal->data->data->attributes->bank->id,
                "bankName" =>  $this->accountPersonal->data->data->attributes->bank->name,
                "cbnCode" => null,
                "nipCode" =>  $this->accountPersonal->data->data->attributes->bank->nipCode,
                "accountName" => $this->accountPersonal->data->data->attributes->accountName,
                "accountNumber" => $this->accountPersonal->data->data->attributes->accountNumber,
                "type" => $this->accountPersonal->data->data->attributes->type,
                "status" => $this->accountPersonal->data->data->attributes->status,
                "frozen" => $this->accountPersonal->data->data->attributes->frozen,
                "currency" => $this->accountPersonal->data->data->attributes->currency,
                "availableBalance" => $this->accountPersonal->data->data->attributes->availableBalance,
                "pendingBalance" => null,
                "ledgerBalance" => null,
                "virtualNubans_id" => $this->accountPersonal->data->data->relationships->virtualNubans->data[0]->id,
                "virtualNubans_type" => $this->accountPersonal->data->data->relationships->virtualNubans->data[0]->type,
            ]);

            return $this;
        }

        return $this;
    }




    public  function updateEscrowAccount($source)
    {
        if ($this->errorState) {
            return $this;
        }

        $this->accountEscrow = $this->transportClient(
            method: "get",
            params: "{$source}?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
            objectData: null,
            endpoint: "accounts"
        );

        $customerStatus = $this->user->customerstatus()->first();
        $customer = $customerStatus?->customer()->first();
        $this->escrow = $customer?->escrowaccount()->first();

        if ($this->escrow && (empty($this->escrow->bankId) || empty($this->escrow->bankName) || empty($this->escrow->nipCode))) {
            $this->escrow->update([
                "bankId" => $this->accountEscrow->data->data->attributes->bank->id,
                "bankName" =>  $this->accountEscrow->data->data->attributes->bank->name,
                "cbnCode" => null,
                "nipCode" =>  $this->accountEscrow->data->data->attributes->bank->nipCode,
                "accountName" => $this->accountEscrow->data->data->attributes->accountName,
                "accountNumber" => $this->accountEscrow->data->data->attributes->accountNumber,
                "type" => $this->accountEscrow->data->data->attributes->type,
                "status" => $this->accountEscrow->data->data->attributes->status,
                "frozen" => $this->accountEscrow->data->data->attributes->frozen,
                "currency" => $this->accountEscrow->data->data->attributes->currency,
                "availableBalance" => $this->accountEscrow->data->data->attributes->availableBalance,
                "pendingBalance" => null,
                "ledgerBalance" => null,
                "virtualNubans_id" => $this->accountEscrow->data->data->relationships->virtualNubans->data[0]->id,
                "virtualNubans_type" => $this->accountEscrow->data->data->relationships->virtualNubans->data[0]->type,
            ]);

            return $this;
        }

        return $this;
    }


    public function setEscrowNuban()
    {
        $escrowAccount = $this->user->customerstatus()->first()?->customer()?->first()?->escrowaccount()?->first();
        $this->escrowVirtual = $escrowAccount?->virtualnuban()?->first();

        if ($this->escrowVirtual === null) {
            $this->escrowNuban = $this->transportClient(
                method: "get",
                params: $escrowAccount->virtualNubans_id . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
                objectData: null,
                endpoint: "virtual-nubans"
            );
            $escrowAccount->virtualnuban()->create([
                "nubanId"       => $this->escrowNuban->data->data->id,
                "nubanType"     => $this->escrowNuban->data->data->type,
                "bankId"        => $this->escrowNuban->data->data->attributes->bank->id,
                "bankName"      => $this->escrowNuban->data->data->attributes->bank->name,
                "nipCode"       => $this->escrowNuban->data->data->attributes->bank->nipCode,
                "accountName"   => $this->escrowNuban->data->data->attributes->accountName,
                "accountNumber" => $this->escrowNuban->data->data->attributes->accountNumber,
                "currency"      => $this->escrowNuban->data->data->attributes->currency,
                "permanent"     => $this->escrowNuban->data->data->attributes->permanent,
                "isDefault"     => $this->escrowNuban->data->data->attributes->isDefault
            ]);
        }

        return $this;
    }

    public function setPersonalNuban()
    {
        $personalAccount = $this->user->customerstatus()->first()?->customer()?->first()?->personalaccount()?->first();
        $this->personalVitual = $personalAccount?->virtualnuban()?->first();

        if ($this->personalVitual === null) {
            $this->personalNuban = $this->transportClient(
                method: "get",
                params: $personalAccount->virtualNubans_id . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
                objectData: null,
                endpoint: "virtual-nubans"
            );
            $personalAccount->virtualnuban()->create([
                "nubanId"       => $this->personalNuban->data->data->id,
                "nubanType"     => $this->personalNuban->data->data->type,
                "bankId"        => $this->personalNuban->data->data->attributes->bank->id,
                "bankName"      => $this->personalNuban->data->data->attributes->bank->name,
                "nipCode"       => $this->personalNuban->data->data->attributes->bank->nipCode,
                "accountName"   => $this->personalNuban->data->data->attributes->accountName,
                "accountNumber" => $this->personalNuban->data->data->attributes->accountNumber,
                "currency"      => $this->personalNuban->data->data->attributes->currency,
                "permanent"     => $this->personalNuban->data->data->attributes->permanent,
                "isDefault"     => $this->personalNuban->data->data->attributes->isDefault
            ]);
        }

        return $this;
    }

    public function setState()
    {
        if ($this->errorState) {
            return $this;
        }

        $this->setSuccessState(status: 200, title: __("This is success"));
        return $this;
    }

    public function updateAccount()
    {
        return $this->errorState ? $this->error : $this->success;
    }

    public function setErrorState($status = 400, $title)
    {
        $this->errorState = true;
        $this->error = (object)[
            "status"    => $status,
            "title"     => $title
        ];
        return $this;
    }


    public function setSuccessState($status = 200, $title)
    {
        $this->success = (object)[
            "status"    => $status,
            "title"     => $title,
        ];

        return  $this;
    }

    public function transportClient(string $method = 'post', ?string $params = null,  object $objectData = null, $endpoint = 'customers'): mixed
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url = $params ? env('ANCHOR_SANDBOX') . $endpoint . '/' . $params : env('ANCHOR_SANDBOX') . $endpoint;

        Log::error($url);

        try {
            $customerObject = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->$method($url, $objectData);
            return (object)['statusCode' => $customerObject->status(), 'data' => $customerObject->object()];
        } catch (ConnectionException $e) {
            Log::error("Connection timeout: " . $e->getMessage());
            return (object)[
                'statusCode' => 500,
                'data' => 'Connection timeout. Please try again.',
            ];
        } catch (RequestException $e) {
            Log::error("HTTP request error: " . $e->getMessage());
            return (object)[
                'statusCode' => $e->response?->status() ?? 500,
                'data' => $e->response?->json() ?? 'Unexpected error occurred.',
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error: " . $e->getMessage());
            return (object)[
                'statusCode' => 500,
                'data' => 'Something went wrong. Please try again later.'
            ];
        }
    }
}
