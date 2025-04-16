<?php

namespace App\Services;


use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionalJournal;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;


class WalletFeeService
{
    private $fail;
    private $success;
    private $failstate = false;
    private $user;
    private $admin;
    private $reference;
    private $sourceReference;
    const SOURCE_ACCOUNT = "ESCROW";
    const DESTINATION_ACCOUNT = "ADMIN";
    const SOURCE_TYPE = "Debit";
    const DESTINATION_TYPE = "Credit";
    const PROCCESS_COMPANY = 'get-Anchor';
    const ACCOUNTTYPE = 'Fee';
    const WITHDRAWAL_FEE = 20;
    const __ADMINACCOUNTNAME = "DepositAccount";
    const __ADMINACCOUNT = "17381592977177-anc_acc";


    public function getAuthorizer($uuid, $ref)
    {
        $this->user = User::where('uuid', $uuid)->first();
        $this->sourceReference = $ref;
        $this->reference =  md5(self::PROCCESS_COMPANY . Str::random(10));
        if (!$this->user) {
            $this->setFailedState(status: 400, title: __("Sorry, We couldn't find the user"));
            return $this;
        }

        if (!$this->reference) {
            $this->setFailedState(status: 400, title: __("Sorry, no reference added"));
            return $this;
        }
        return $this;
    }

    public function getAdmin()
    {
        if ($this->failstate)
            return $this;
        $this->admin = self::__ADMINACCOUNT;

        if (!$this->admin) {
            $this->setFailedState(status: 400, title: __("Sorry, We couldn't find the admin"));
            return $this;
        }

        return $this;
    }


    public function processTransaction()
    {
        if ($this->failstate) {
            return $this;
        }

        $payload = $this->buildPayload();
        $response = $this->transportClient(
            method: "post",
            params: null,
            objectData: $payload,
            endpoint: "transfers"
        );

        if ($response->statusCode !== 201 && $response->statusCode !== 202 && $response->statusCode !== 200) {
            $this->setFailedState(status: $response->statusCode, title: $response->data);
            return $this;
        }

        $this->apiRef = (object)[
            'status' => $response->data->data->attributes->status,
            'failureReason' => $response->data->data->attributes->failureReason ?? null,
            'transferId' => $response->data->data->id,
            'status' => $response->data->data->attributes->status
        ];
        return $this;
    }

    public function createJournal()
    {
        if ($this->failstate) {
            return $this;
        }

        $this->naration = $this->createNaration();

        try {
            TransactionalJournal::create([
                'source_account' => self::SOURCE_ACCOUNT,
                'source_name' => $this->user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId,
                'source_type'   => self::SOURCE_TYPE,
                'destination_account' => self::DESTINATION_ACCOUNT,
                'destination_name' =>  self::__ADMINACCOUNT,
                'destination_type' => self::DESTINATION_TYPE,
                'source_reference' => $this->sourceReference,
                'api_reference' => $this->reference,
                'trnx_id' => $this->apiRef->transferId,
                'reason_for_failure' => $this->apiRef->failureReason,
                'amount' => self::WITHDRAWAL_FEE,
                'reference' => $this->reference,
                'narration' => $this->naration,
                'status' => $this->apiRef->status === "PENDING" ? 'pending' : ($this->apiRef->status === "COMPLETED" ? 'success' : 'failed'),
                'account_type' => self::ACCOUNTTYPE
            ]);
            $this->setSuccessState(status: 200, title: __("Debit Transaction was successful"));
            return $this;
        } catch (\Exception $e) {
            $this->setFailedState(status: 400, title: __("Sorry! We couldn't create a transaction journal  at the moment. Please try again later." . $e->getMessage()));
        }

        return $this;
    }

    public function throwState()
    {
        return $this->failstate ? $this->fail : $this->success;
    }


    public function setFailedState($status = 400, $title)
    {
        $this->failstate = true;
        $this->fail = (object) [
            "status" => $status,
            "title" => $title
        ];
    }

    public function setSuccessState($status = 200, $title)
    {
        $this->success = (object) [
            "status" => $status,
            "title" => $title
        ];
    }


    public function transportClient(string $method = 'post', ?string $params = null,  object $objectData = null, $endpoint = 'customers')
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

    public function buildPayload()
    {
        return (object) [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => (self::WITHDRAWAL_FEE * 100),
                    "reason"    => "The Admin Fee deduction on every trade done",
                    "reference" => $this->reference
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => self::__ADMINACCOUNTNAME,
                            "id"    => self::__ADMINACCOUNT
                            // "type"  => $this->admin->botType,
                            // "id"    => $this->admin->botId

                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $this->user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $this->user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId

                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];
    }

    public function createNaration()
    {
        /* This is a sample naration. we are coming to it later */


        $isHtml = true;
        return __(
            "An administrative fee has been charged on the total withdrawal sum of :amount " . ($isHtml ? "<br>" : "\n") .
                "Your transaction reference is :reference " . ($isHtml ? "<br>" : "\n") .
                "Your transaction status is :status " . ($isHtml ? "<br>" : "\n") .
                "Your transaction failure reason is :failureReason " . ($isHtml ? "<br>" : "\n") .
                "Your transaction transfer id is :transferId " . ($isHtml ? "<br>" : "\n"),
            [
                'amount' => self::WITHDRAWAL_FEE,
                'reference' => $this->reference,
                'status' => $this->apiRef->status,
                'failureReason' => $this->apiRef->failureReason,
                'transferId' => $this->apiRef->transferId,
            ]
        );
    }
}
