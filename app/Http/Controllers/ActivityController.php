<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function createBankStatement(Request $request)
    {

        $statementId = Str::uuid();
        $payload = $this->payload($request->fromDate, $request->toDate, $request->depositAccountId, $statementId);
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->post(env('ANCHOR_SANDBOX') . 'statements', $payload);

        $statementObject = $data->object();

        $statementData = $this->getBankStatement($statementObject->data->id);
        $downloadData = $this->downloadBankStatement($statementObject->data->id);
        return response()->json([$data->object(), $statementData, $downloadData]);
    }

    public function getBankStatement($statementId)
    {
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'statements/' . $statementId);

        return $data->object();
    }


    public function downloadBankStatement($statementId)
    {
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'statements/download/' . $statementId);

        return $data->object();
    }





    public function fecthAllTransaction($accountId, $id, $inOut, $type)
    {
        $pageNumber = $id;
        $size = 10;
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'transactions?type=' . $type . '&direction=' . $inOut . '&accountId=' . $accountId . '&page=' . $pageNumber . '&size=' . $size);

        return $data->object();
    }



    public function fecthNextAllTransaction($accountId, $customerId, $pageNumber)
    {
        $size = 10;
        return
            Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->get(env('ANCHOR_SANDBOX') . "transactions?accountId=" . $accountId . "&customerId=" . $customerId . "&from=" . now() . "&to=" . now()->subDays(30) . "&page=" . $pageNumber . "&size=" . $size . "&include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer")->throw()->json();
    }




    public function controllAction($id, $inOut = "Debit", $type = "BOOK_TRANSFER")
    {
        $user = User::find(auth()->user()->id);
        $data = $user->customerstatus()->first();
        $personal = $data->customer()->first()->personalaccount()->first()->personalId;
        $in = $this->fecthAllTransaction($personal, $id,  $inOut !== "Debit" ? "Credit" : "Debit", $type);
        return response()->json($in);
    }





    public function controllNextAction($pageNumber)
    {
        $user = User::find(auth()->user()->id);
        $data = $user->customerstatus()->first();
        $in = $this->fecthNextAllTransaction($data->customer()->first()->personalaccount()->first()->personalId, $data->customer()->first()->customerId, $pageNumber);


        return response()->json($in);
    }

    public function fetchTransactionStatus($transferId)
    {
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'transfers/verify/' . $transferId);

        return response()->json($data->object());
    }


    public function payload($fromDate, $toDate, $depositAccountId, $statementId)
    {
        $payload = new stdClass();
        $payload = [
            "data" => [
                "attributes" => [
                    "fromDate"  => $fromDate,
                    "toDate"    => $toDate
                ],
                "relationships" => [
                    "account"   => [
                        "data"  => [
                            "type" => "DepositAccount",
                            "id"    => $depositAccountId
                        ]
                    ]
                ],
                "type" => $statementId

            ]
        ];
        return $payload;
    }

    public function fetchPersonalAccountTrasanctionHistory($accountId)
    {
        $pageNumber = 1;
        $size = 20;
        Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'transactions?type=BOOK_TRANSFER&direction=Debit&accountId' . $accountId . '&page=' . $pageNumber . '&size=' . $size . '&include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer');
    }
}
