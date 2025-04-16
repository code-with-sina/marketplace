<?php

namespace App\WalletFacades;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;


trait HasEscrowAccount
{
    private $state;
    private $errorMessage;
    private $statefulError = false;
    protected User $customer;
    public function setEscrow($uuid)
    {
        $this->customer = User::where('uuid', $uuid)->first();


        $escrowPayload = $this->buildEscrowPayload();
        $response = $this->transportEscrowClient(
            method: "post",
            params: null,
            objectData: $escrowPayload,
            endpoint: 'accounts',
        );

        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $escrow = $response->data;
            $this->customer->customerstatus()->first()->customer()->first()->escrowaccount()->create([
                'escrowId'      => $escrow->data->id,
                'escrowType'    => $escrow->data->type
            ]);
        } else {
            $this->setEscrowTrueErrorState(status: $response->statusCode, title: $response->data);
        }

        return $this;
    }

    public function createEscrowAccount()
    {

        if ($this->statefulError) {
            return $this;
        }

        $response = $this->transportEscrowClient(
            method: "get",
            params: $this->customer->customerstatus()->first()
                ->customer()->first()
                ->escrowaccount()->first()
                ->escrowId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
            endpoint: 'accounts',
        );

        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $accounts = $response->data;
            $this->customer->customerstatus()->first()->customer()->first()->escrowaccount()->update([
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
        } else {
            $this->setEscrowTrueErrorState(status: $response->statusCode, title: $response->data);
        }

        return $this;
    }

    public function addEscrowNuban()
    {

        if ($this->statefulError) {
            return $this;
        }

        $response = $this->transportEscrowClient(
            method: "get",
            params: $this->customer->customerstatus()->first()->customer()->first()->escrowaccount()->first()->virtualNubans_id,
            endpoint: "virtual-nubans"

        );

        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $nuban = $response->data;
            $this->customer->customerstatus()->first()->customer()->first()->escrowaccount()->first()->virtualnuban()->create([
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

            $this->setEscrowSuccessState(status: 200, title: __("Hurray! Your escrow account has been successfully created with us!"));
        } else {
            $this->setEscrowTrueErrorState(status: $response->statusCode, title: $response->data);
        }

        return $this;
    }

    public function throwEscrowStatus()
    {
        return $this->statefulError ?  $this->errorMessage : $this->state;
    }


    public function transportEscrowClient(string $method = 'post', string $params = null,  object $objectData = null, $endpoint = 'customers'): mixed
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url = $params == null ? env('ANCHOR_SANDBOX') . $endpoint : env('ANCHOR_SANDBOX') . $endpoint . '/' . $params;

        Log::info($url);
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

    public function buildEscrowPayload()
    {

        return (object) [
            'data'  => [
                'attributes' => [
                    'productName' => 'SAVINGS',
                ],
                'relationships'  => [
                    'customer'  => [
                        'data' => [
                            'id' => $this->customer->customerstatus()->first()->customerId,
                            'type' => $this->customer->customerstatus()->first()->type
                        ]
                    ]
                ],
                'type' => 'DepositAccount'
            ]
        ];
    }


    public function setEscrowTrueErrorState(mixed $title, int $status = 400)
    {
        $this->statefulError = true;
        $this->errorMessage = [
            'status' => $status,
            'title' => $title
        ];

        Log::error('Custom error message', [
            'context_key' => $status,
            'title' => $title
        ]);
        return $this;
    }

    public function setEscrowSuccessState($status = 200, $title)
    {
        $this->state = [
            'status' => $status,
            'title' => $title
        ];

        return $this;
    }
}
