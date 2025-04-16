<?php

namespace App\Services;


use App\Models\User;
use Illuminate\Support\Str;
use App\Models\TransactionalJournal;
use Illuminate\Support\Facades\Http;


class DebitService
{
    private $debitAmount;
    private $initialBalance;
    private $failstate = false;
    private $fail;
    private $success;
    private $user;
    private $apiRef;
    private $naration;
    private $sourceReference;
    private $reference;
    const SOURCE_ACCOUNT = "PERSONAL";
    const DESTINATION_ACCOUNT = "ESCROW";
    const SOURCE_TYPE = "Debit";
    const DESTINATION_TYPE = "Credit";
    const PROCCESS_COMPAY = 'get-Anchor';
    const ACCOUNTTYPE = 'Debit';



    public function getAmount($amount, $ref, $uuid)
    {

        $this->user = User::where('uuid', $uuid)->first();
        $this->reference = 'Api-' . self::PROCCESS_COMPAY . '-' . Str::uuid();
        $this->debitAmount = (float)$amount;
        $this->sourceReference  = $ref;
        if (empty($this->debitAmount) && empty($this->sourceReference)) {
            $this->setFailedState(status: 400, title: __("Sorry, You have not added an amount and we cant access your source reference"));

            return $this;
        }
    }
    public function getInitialBalance()
    {
        if ($this->failstate) {
            return $this;
        }
        $getBalance = $this->transportClient(
            method: "get",
            params: "balance/" . $user->customerstatus()->first()
                ->customer()->first()
                ->personalaccount()->first() . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer"
        );

        if ($getBalance->statusCode === 200) {
            $this->initialBalance = (float)$getBalance->data->data->availableBalance;
            return $this;
        } else {
            $this->setFailedState(status: 400, title: $getBalance->data->errors[0]->detail);

            return $this;
        }

        return $this;
    }

    public function compareBalance()
    {
        if ($this->failstate) {
            return $this;
        }

        if ($this->initialBalance < $this->debitAmount) {
            $this->setFailedState(status: 400, title: __("Sorry, you have insufficient balance"));
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

        if ($response->statusCode !== 201 || $response->statusCode !== 202) {
            $this->setFailedState(status: 400, title: $response->data->errors[0]->detail);

            return $this;
        }

        $this->apiRef = (object)[
            'status' => $response->data->attributes->status,
            'failureReason' => $response->data->attributes->failureReason,
            'transferId' => $response->data->id,
        ];
        return $this;
    }

    public function createJournal()
    {
        if ($this->failstate) {
            return $this;
        }

        $this->naration = $this->createNaration();
        $journal = new TransactionalJournal();
        $journal->create([
            'source_account' => self::SOURCE_ACCOUNT,
            'source_name' => $this->user->customer()->first()->personalaccount()->first()->personalId,
            'source_type'   => self::SOURCE_TYPE,
            'destination_account' => self::DESTINATION_ACCOUNT,
            'destination_name' => $this->user->customer()->first()->escrowaccount()->first()->escrowId,
            'destination_type' => self::DESTINATION_TYPE,
            'source_reference' => $this->sourceReference,
            'api_reference' => $this->reference,
            'trnx_id' => $this->apiRef->transferId,
            'reason_for_failure' => $this->apiRef->failureReason,
            'amount' => $this->debitAmount,
            'reference' => $this->reference,
            'naration' => $this->naration,
            'status' => 'pending',
            'accountType' => self::ACCOUNTTYPE
        ]);


        $this->setSuccessState(status: 200, title: __("Debit Transaction was successful"));
        return $this;
    }

    public function setSuccessState($status = 200, $title)
    {
        $this->success = (object) [
            'status'    => $status,
            'title'     => $title
        ];

        return $this;
    }

    public function state()
    {
        return $this->failstate ? $this->fail : $this->success;
    }


    public function setFailedState($status = 400, $title)
    {
        $this->failstate = true;

        $this->fail = (object) [
            'status'    => $status,
            'title'     => $title
        ];

        return $this;
    }

    /* 
        Prepared Api Query Request
    */

    public function transportClient(string $method = 'post', ?string $params = null,  object $objectData, $endpoint = 'customers'): mixed
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url = $params ? env('ANCHOR_SANDBOX') . $endpoint . '/' . $params : env('ANCHOR_SANDBOX') . $endpoint;

        \Log::error($url);

        try {
            $customerObject = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->$method($url, $objectData);
            return (object)['statusCode' => $customerObject->status(), 'data' => $customerObject->object()];
        } catch (\Exception $e) {
            return (object)['statusCode' => 500, 'data' => $e->getMessage()];
        }
    }



    public function buildPayload()
    {
        return (object) [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => ($this->debitAmount * 100),
                    "reason"    => "witholding funds for an intended transaction",
                    "reference" => $this->reference
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $this->user->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $this->user->customer()->first()->escrowaccount()->first()->escrowId

                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $this->user->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $this->user->customer()->first()->personalaccount()->first()->personalId

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
            "Your personal asset account has been successfully debited your account with the sum of :amount " . ($isHtml ? "<br>" : "\n") .
                "Your transaction reference is :reference " . ($isHtml ? "<br>" : "\n") .
                "Your transaction status is :status " . ($isHtml ? "<br>" : "\n") .
                "Your transaction failure reason is :failureReason " . ($isHtml ? "<br>" : "\n") .
                "Your transaction transfer id is :transferId " . ($isHtml ? "<br>" : "\n"),
            [
                'amount' => $this->debitAmount,
                'reference' => $this->reference,
                'status' => $this->apiRef->status,
                'failureReason' => $this->apiRef->failureReason,
                'transferId' => $this->apiRef->transferId,
            ]
        );
    }



    
}
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
try {

}catch(ConnectionException $e) {
    Log::error("Connection timeout: " . $e->getMessage());
    return (object)[
        'statusCode' => 500,
        'data' => 'Connection timeout. Please try again.',
    ];
}catch(RequestException $e) {
    Log::error("HTTP request error: " . $e->getMessage());
    return (object)[
        'statusCode' => $e->response?->status() ?? 500,
        'data' => $e->response?->json() ?? 'Unexpected error occurred.',
    ];
}catch(\Exception $e) {
    Log::error("Unexpected error: " . $e->getMessage());
    return (object)[
        'statusCode' => 500,
        'data' => 'Something went wrong. Please try again later.'
    ];
}