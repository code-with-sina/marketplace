<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    private $error;
    private $state;
    private User $user;
    private $amount;
    private $initialBalance;
    private $errorState = false;


    public function payload($uuid, $amount = null)
    {
        $this->user = User::where('uuid', $uuid)->first();

        if (!$this->user) {
            $this->setErrorState(status: 400, title: __("User can not be found"));
        }

        Log::info(["payload" => $this->user->uuid]);
        $this->amount = (100 * (int)$amount) ?? null;
        return $this;
    }
    public function getBalance()
    {
        if ($this->errorState) {
            return $this;
        }


        $getBalance = $this->transportClient(
            method: "get",
            params: "balance/" . $this->user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer",
            endpoint: "accounts",
            objectData: null
        );

        if ($getBalance->statusCode !== 200) {
                $this->setErrorState(status: 400, title: $getBalance->data->errors[0]->detail);
            return $this;
        }

        Log::info(["getBalance" => $getBalance->data->data->availableBalance]);

        $this->initialBalance = $getBalance->data->data->availableBalance;

        return $this;
    }

    public function compareBalance()
    {
        if ($this->errorState) {
            return $this;
        }

        if ($this->amount === null) {
            return $this;
        }


        if ($this->initialBalance < $this->amount) {
            Log::info([
                "compareBalance" => $this->initialBalance < $this->amount,
                "initial amount"    => $this->initialBalance,
                "incoming amount" => $this->amount
            ]);
            $this->setErrorState(status: 400, title: __("Sorry, you have insufficient balance"));
            return $this;
        } else {
            Log::info([
                "compareBalance" => $this->initialBalance < $this->amount,
                "initial amount"    => $this->initialBalance,
                "incoming amount" => $this->amount
            ]);
            $this->setSuccessStatus();
        }




        return $this;
    }


    public function throwStatus()
    {

        Log::info(["throwStatus" => $this->errorState ? $this->error : $this->state]);
        return $this->errorState ? $this->error : $this->state;
    }


    public function setErrorState($status, $title)
    {
        $this->errorState = true;

        $this->error = (object)[
            'status'    => $status,
            'title'     => $title
        ];
    }


    public function setSuccessStatus()
    {
        $this->state = (object)[
            'status'    => 200,
            'title'     => "sufficient balance"
        ];
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
            $customerObject = Http::retry(3, 2000, function ($exception) {
                return $exception instanceof ConnectionException;
            })->timeout(30)->withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->$method($url, $objectData ?? []);


            return (object)['statusCode' => $customerObject->status(), 'data' => $customerObject->object()];
        } catch (ConnectionException  $e) {

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
