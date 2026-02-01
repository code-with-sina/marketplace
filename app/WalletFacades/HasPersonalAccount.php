<?php

namespace App\WalletFacades;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

trait HasPersonalAccount
{
    private $state;
    private $errorMessage;
    private $statefulError = false;
    protected User $customer;
    public function setPersonal($uuid)
    {
        $this->customer = User::where('uuid', $uuid)->first();

        $personalPayload = $this->buildPersonalPayload();
        $response = $this->transportPersonalClient(
            method: "post",
            endpoint: 'accounts',
            params: null,
            objectData: $personalPayload
        );

        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $personal = $response->data;
            $this->customer->customerstatus()->first()->customer()->first()->personalaccount()->create([
                'personalId'      => $personal->data->id,
                'personalType'    => $personal->data->type
            ]);
        } else {
            $this->setPersonalTrueErrorState(status: $response->statusCode, title: $response->data);
        }

        return $this;
    }

    public function createPersonalAccount()
    {

        if ($this->statefulError) {
            return $this;
        }

        $response = $this->transportPersonalClient(
            method: "get",
            params: $this->customer->customerstatus()
                ->first()->customer()->first()
                ->personalaccount()->first()
                ->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
            endpoint: 'accounts',
            objectData: null
        );

        Log::info(['True Data for Personal Account' => $response->statusCode]);

        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $accounts = $response->data;
            Log::info(['personal account' => $response->data]);
            $this->customer->customerstatus()->first()->customer()->first()->personalaccount()->update([
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
        } else {
            $this->setPersonalTrueErrorState(status: $response->statusCode, title: $response->data);
        }

        return $this;
    }

    public function addPersonalNuban()
    {

        if ($this->statefulError) {
            return $this;
        }


        $response = $this->transportPersonalClient(
            method: "get",
            params: $this->customer->customerstatus()->first()->customer()->first()->personalaccount()->first()->virtualNubans_id,
            endpoint: "virtual-nubans",
            objectData: null
        );

        Log::info(['True Data for Personal Account Nuban' => $response->statusCode]);
        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $nuban = $response->data;
            Log::info(['personal account' => $response->data]);
            $this->customer->customerstatus()->first()->customer()->first()->personalaccount()->first()->virtualnuban()->create([
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

            $this->setPersonalSuccessState(status: 200, title: __("Hurray! Your personal account has been successfully created with us!"));
        } else {
            $this->setPersonalTrueErrorState(status: $response->statusCode, title: $response->data);
        }

        return $this;
    }

    public function throwPersonalStatus()
    {
        return $this->statefulError ?  $this->errorMessage : $this->state;
    }


    public function transportPersonalClient(string $method = 'post', string $params = null,  object $objectData = null, $endpoint = 'customers'): mixed
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url = $params == null ? env('ANCHOR_SANDBOX') . $endpoint : env('ANCHOR_SANDBOX') . $endpoint . '/' . $params;




        try {
            $customerObject = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->$method($url, $objectData);

            Log::info(['status-throw', $customerObject]);
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

    public function buildPersonalPayload()
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


    public function setPersonalTrueErrorState(mixed $title, int $status = 400)
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

    public function setPersonalSuccessState($status = 200, $title)
    {
        $this->state = [
            'status' => $status,
            'title' => $title
        ];

        return $this;
    }
}
