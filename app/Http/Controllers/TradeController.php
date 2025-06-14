<?php

namespace App\Http\Controllers;


use App\Models\Fee;
use App\Models\User;
use App\Models\TradeLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AdminActivity;
use App\Models\Administrator;
use App\Models\CustomerStatus;
use Illuminate\Support\Sleep;
use Illuminate\Support\Carbon;
use App\Prop\FeeDeterminantAid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\AdminFacades\HasObjectConverter;

class TradeController extends Controller
{

    use HasObjectConverter;

    public function holdFundAtTradeRequestInitiated(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $fetchBuyerAccount = $user->customerstatus()->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => ($request->amount * 100),
                    "reason"    => "withheld for trade transaction",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId

                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalId

                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];

        $makeWithHolding = $this->ToObject($payload);

        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_PRODUCTION'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeWithHolding);

        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $request->regs,
                acceptance: $request->acceptance_id,
                itemFor: $request->item_for,
                buyerId: auth()->user()->uuid,
                state: $request->state,
                direction: $request->direction,
                walletName: $request->walletName,
                sellerId: $request->sellerId
            );
            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $request->regs,
                acceptance: $request->acceptance_id,
                itemFor: $request->item_for,
                buyerId: auth()->user()->uuid,
                state: $request->state,
                direction: $request->direction,
                walletName: $request->walletName,
                sellerId: $request->sellerId
            );
            return response()->json($createTradeLog);
        } else {
            return response()->json($transfer->object());
        }
    }

    public function holdFundAtTradeRequestAccepted($amount, $regs, $acceptance_id, $item_for, $state, $direction, $walletName, $sellerId)
    {
        $user = User::find(auth()->user()->id);
        // $fetchBuyerAccount = $user->customerstatus()->first();
        $fetchBuyerAccount = $user->customerstatus()->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => ($amount * 100),
                    "reason"    => "withheld for trade transaction",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId
                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalId

                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];

        $makeWithHolding = $this->ToObject($payload);

        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeWithHolding);

        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $regs,
                acceptance: $acceptance_id,
                itemFor: $item_for,
                buyerId: auth()->user()->uuid,
                state: $state,
                direction: $direction,
                walletName: $walletName,
                sellerId: $sellerId
            );
            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $regs,
                direction: $direction,
                acceptance: $acceptance_id,
                itemFor: $item_for,
                buyerId: auth()->user()->uuid,
                state: $state,
                sellerId: $sellerId,
                walletName: $walletName,
            );
            return response()->json($createTradeLog);
        } else {
            return response()->json($transfer->object());
        }
    }


    // public function returnFundToBuyerAccount(Request $request)
    // {
    //     $user = User::find(auth()->user()->id);
    //     $fetchAdminAccount = $user->customerstatus()->first();
    //     // $fetchBuyerAccount = CustomerStatus::where('uuid', $request->uuid)->where('reg', $request->reg)->first();
    //     $payload = [
    //         "data" => [
    //             "attributes" => [
    //                 "currency"  => "NGN",
    //                 "amount"    => ($fetchBuyerAccount->amount * 100),
    //                 "reason"    => "withheld for trade transaction",
    //                 "reference" => Str::uuid()
    //             ],
    //             "relationships" => [
    //                 "destinationAccount" => [
    //                     "data"  => [
    //                         "type"  => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalType,
    //                         "id"    => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalId
    //                     ]
    //                 ],
    //                 "account" => [
    //                     "data"  => [
    //                         "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
    //                         "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId


    //                     ]
    //                 ]
    //             ],
    //             "type" => "BookTransfer"
    //         ]
    //     ];

    //     $makeWithHolding = $this->ToObject($payload);

    //     $transfer =  Http::withHeaders([
    //         'accept' => 'application/json',
    //         'content-type' => 'application/json',
    //         'x-anchor-key' => env('ANCHOR_KEY'),
    //     ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeWithHolding);

    //     if ($transfer->status() === 200 || $transfer->status() === 202) {
    //         $createTradeLog = $this->createTradeLog(
    //             trnxObj: $transfer->object(),
    //             regs: $request->regs,
    //             acceptance: $request->acceptance_id,
    //             itemFor: $request->item_for,
    //             buyerId: auth()->user()->uuid,
    //             state: $request->state,
    //             direction: $request->direction,
    //             walletName: $request->walletName,
    //             sellerId: $request->sellerId
    //         );
    //         return response()->json($createTradeLog);
    //     } elseif ($transfer->status() === 201) {
    //         $createTradeLog = $this->createTradeLog(
    //             trnxObj: $transfer->object(),
    //             regs: $request->regs,
    //             direction: $request->direction,
    //             acceptance: $request->acceptance_id,
    //             itemFor: $request->item_for,
    //             buyerId: auth()->user()->uuid,
    //             state: $request->state,
    //             sellerId: $request->sellerId,
    //             walletName: $request->walletName,
    //         );
    //         return response()->json($createTradeLog);
    //     } else {
    //         return response()->json($transfer->object());
    //     }
    // }




    public function releaseFund($reg)
    {
        $tradeLog = TradeLog::where('reg', $reg)->first();
        $expend = $this->charge(amount: $tradeLog->amount);
        $this->sendToSeller(amount: $expend['seller'], ledgerDetails: $tradeLog);
        Sleep::for(5)->second();
        $this->sendToAdmin(amount: $expend['admin'], ledgerDetails: $tradeLog);
    }


    public function fetchEscrowBalance()
    {
        $props = User::find(auth()->user()->id);
        $user = $props->customerstatus()->first();

        $data = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customer()->first()->escrowaccount()->first()->escrowId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

        if ($data->status() == 200 || $data->status() == 202) {
            $account = $data->object();
            $balance = $user->customer()->first()->escrowaccount()->first()->escrowbalance()->first();
            if ($balance == null || empty($balance)) {
                $user->customer()->first()->escrowaccount()->first()->escrowbalance()->create([
                    'uuid'              => $props->uuid,
                    'availableBalance'  => ($account->data->availableBalance / 100),
                    'ledgerBalance'     => ($account->data->ledgerBalance / 100),
                    'hold'              => $account->data->hold,
                    'pending'           => $account->data->pending,
                ]);

                $balance = $user->customer()->first()->escrowaccount()->first()->escrowbalance()->first();
                return response()->json(['data'  => $balance]);
            } else {
                $user->customer()->first()->escrowaccount()->first()->escrowbalance()->update([
                    'uuid'              => $props->uuid,
                    'availableBalance'  => ($account->data->availableBalance / 100),
                    'ledgerBalance'     => ($account->data->ledgerBalance / 100),
                    'hold'              => $account->data->hold,
                    'pending'           => $account->data->pending,
                ]);

                $balance = $user->customer()->first()->escrowaccount()->first()->escrowbalance()->first();
                return response()->json(['data'  => $balance]);
            }
        } else {
            return response()->json(['data'  => $data->object()]);
        }
    }


    public function fetchPersonalBalance(Request $request)
    {

        $props = User::find(auth()->user()->id);
       
        $user = $props->customerstatus()->first();

        $data = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customer()->first()->personalaccount()->first()->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

        if ($data->status() == 200 || $data->status() == 202) {
            $account = $data->object();
            $balance = $user->customer()->first()->personalaccount()->first()->personalbalance()->first();
            if ($balance == null || empty($balance)) {
                $user->customer()->first()->personalaccount()->first()->personalbalance()->create([
                    'uuid'              => $props->uuid,
                    'availableBalance'  => ($account->data->availableBalance / 100),
                    'ledgerBalance'     => ($account->data->ledgerBalance / 100),
                    'hold'              => $account->data->hold,
                    'pending'           => $account->data->pending,
                ]);

                $balance = $user->customer()->first()->personalaccount()->first()->personalbalance()->first();
                return response()->json(['data'  => $balance]);
            } else {
                $user->customer()->first()->personalaccount()->first()->personalbalance()->update([
                    'uuid'              => $props->uuid,
                    'availableBalance'  => ($account->data->availableBalance / 100),
                    'ledgerBalance'     => ($account->data->ledgerBalance / 100),
                    'hold'              => $account->data->hold,
                    'pending'           => $account->data->pending,
                ]);

                $balance = $user->customer()->first()->personalaccount()->first()->personalbalance()->first();
                return response()->json(['data'  => $balance]);
            }
        } else {
            return response()->json(['data'  => $data->object()]);
        }
    }

    public function withdrawalHistory($account, $trnxObj)
    {
        $withdrawal = $account->customer()->first()->withdrawalhistory()->create([
            'transfer_id'    => $trnxObj->data->id ?? null,
            'type'   => $trnxObj->data->id ?? null,
            'reason' => $trnxObj->data->attributes->reason ?? null,
            'reference'  => $trnxObj->data->attributes->reference ?? null,
            'amount' => $trnxObj->data->attributes->amount ?? null,
            'failureReason'  => $trnxObj->data->attributes->failureReason ?? null,
            'currency'   => $trnxObj->data->attributes->currency ?? null,
            'status' => $trnxObj->data->attributes->status ?? null,
        ]);

        return $withdrawal;
    }

    // public function adminFee()
    // {
    //     $payload = [
    //         "data" => [
    //             "attributes" => [
    //                 "currency"  => "NGN",
    //                 "amount"    => $tradeLog->amount,
    //                 "reason"    => "withheld for trade transaction",
    //                 "reference" => Str::uuid()
    //             ],
    //             "relationships" => [
    //                 "destinationAccount" => [
    //                     "data"  => [
    //                         "type"  => $fetchSellerAccount->customer()->first()->personalaccount()->first()->personalType,
    //                         "id"    => $fetchSellerAccount->customer()->first()->personalaccount()->first()->personalId
    //                     ]
    //                 ],
    //                 "account" => [
    //                     "data"  => [
    //                         "type"  => $fetchAdminAccount->adminaccount()->first()->botType,
    //                         "id"    => $fetchAdminAccount->adminaccount()->first()->botId

    //                     ]
    //                 ]
    //             ],
    //             "type" => "BookTransfer"
    //         ]
    //     ];
    // }

    public function charge($amount)
    {
        $fee = Fee::latest()->first();
        $amount = (float)$amount;
        $percentage = $fee->percentage;
        $deduction = ($percentage / 100) * $amount;
        $finalAmount = $amount - $deduction;
        return ['seller' => $finalAmount, 'admin' => $deduction];
    }


    public function sendToSeller($amount, $ledgerDetails)
    {
        $seller = User::where('uuid', $ledgerDetails->seller_uuid)->first();
        $buyer = User::where('uuid', $ledgerDetails->buyer_uuid)->first();

        $fetchSellerAccount     = $seller->customerstatus()->first();
        $fetchBuyerAccount      = $buyer->customerstatus()->first();
        // $fetchSellerAccount = CustomerStatus::where('uuid', $ledgerDetails->seller_uuid)->first();
        // $fetchBuyerAccount = CustomerStatus::where('uuid', $ledgerDetails->buyer_uuid)->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => $amount,
                    "reason"    => "withheld for trade transaction",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $fetchSellerAccount->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $fetchSellerAccount->customer()->first()->personalaccount()->first()->personalId
                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId

                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];
        $makeRelease = $this->ToObject($payload);
        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeRelease)->throw();

        Log::info([$transfer->status(), 'to seller']);

        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $ledgerDetails->reg,
                acceptance: $ledgerDetails->acceptance_id,
                itemFor: $ledgerDetails->item_for,
                buyerId: $ledgerDetails->buyer_uuid,
                state: 'release',
                direction: 'outgoing',
                walletName: $ledgerDetails->wallet_name,
                sellerId: $ledgerDetails->seller_uuid
            );
            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $ledgerDetails->reg,
                acceptance: $ledgerDetails->acceptance_id,
                itemFor: $ledgerDetails->item_for,
                buyerId: $ledgerDetails->buyer_uuid,
                state: 'release',
                direction: 'outgoing',
                sellerId: $ledgerDetails->seller_uuid,
                walletName: $ledgerDetails->wallet_name,
            );
            return response()->json($createTradeLog);
        } else {
            return response()->json($transfer->object());
        }
    }


    public function sendToAdmin($amount, $ledgerDetails)
    {
        $fetchAdminAccount = Administrator::latest()->first();
        $buyer = User::where('uuid', $ledgerDetails->buyer_uuid)->first();
        $fetchBuyerAccount = $buyer->customerstatus()->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => $amount,
                    "reason"    => "trade fee",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $fetchAdminAccount->adminaccount()->first()->botType,
                            "id"    => $fetchAdminAccount->adminaccount()->first()->botId

                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId

                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];

        $makeRelease = $this->ToObject($payload);
        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeRelease)->throw();

        Log::info([$transfer->status(), 'admin']);

        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $ledgerDetails->reg,
                acceptance: $ledgerDetails->acceptance_id,
                itemFor: $ledgerDetails->item_for,
                buyerId: $ledgerDetails->buyer_uuid,
                state: 'release',
                direction: 'outgoing',
                walletName: $ledgerDetails->wallet_name,
                sellerId: $ledgerDetails->seller_uuid
            );
            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $ledgerDetails->reg,
                acceptance: $ledgerDetails->acceptance_id,
                itemFor: $ledgerDetails->item_for,
                buyerId: $ledgerDetails->buyer_uuid,
                state: 'release',
                direction: 'outgoing',
                sellerId: $ledgerDetails->seller_uuid,
                walletName: $ledgerDetails->wallet_name,
            );
            return response()->json($createTradeLog);
        } else {
            return response()->json($transfer->object());
        }
    }



    public function returnBackFundFromACancelledTradeRequest($reg)
    {
        $tradeLog = TradeLog::where('reg', $reg)->first();
        // return $tradeLog;
        $this->returnToWallet(ledgerDetails: $tradeLog);
    }

    public function returnBackFundFromRejectedTradeRequest(Request $request)
    {
        $tradeLog = TradeLog::where('reg', $request->reg)->first();
        $this->returnToWallet(ledgerDetails: $tradeLog);
    }


    public function returnToWallet($ledgerDetails)
    {

        $user = User::where('uuid', $ledgerDetails->buyer_uuid)->first();
        $fetchBuyerAccount = $user->customerstatus()->first();

        // $fetchBuyerAccount = CustomerStatus::where('uuid', $ledgerDetails->buyer_uuid)->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => $ledgerDetails->amount,
                    "reason"    => "return back to personal balance",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalId
                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId
                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];

        $makeWithHolding = $this->ToObject($payload);

        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeWithHolding)->throw();

        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $ledgerDetails->reg,
                acceptance: $ledgerDetails->acceptance_id,
                itemFor: $ledgerDetails->item_for,
                buyerId: auth()->user()->uuid,
                state: 'release',
                direction: 'incoming',
                walletName: $ledgerDetails->wallet_name,
                sellerId: $ledgerDetails->seller_uuid
            );
            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $ledgerDetails->reg,
                acceptance: $ledgerDetails->acceptance_id,
                itemFor: $ledgerDetails->item_for,
                buyerId: auth()->user()->uuid,
                state: 'release',
                direction: 'incoming',
                sellerId: $ledgerDetails->seller_uuid,
                walletName: $ledgerDetails->wallet_name,
            );
            return response()->json($createTradeLog);
        } else {
            return response()->json($transfer->object());
        }
    }



    public function adminReinburseSeller(Request $request)
    {
        $data = $this->getTrade(session: $request->session);
        if ($data['status'] === 200) {
            $collections = [
                'uuid'                      => $request->uuid,
                'email'                     => $request->email,
                'fullname'                  => $request->fullname,
                'activity_performed'        => 'The admin staff by the email above performed this action ' . __FUNCTION__ . ' :: disburse transaction of ' . $data['data'] . '.',
                'amount'                    => $data['data']->amount_to_receive,
                'buyer'                     =>  $data['data']->buyer,
                'seller'                    =>  $data['data']->reg,
                'reg'                       =>  $data['data']->trnx_ref,
                'trnx_ref'                  =>  $data['data']->amount_to_receive,
                'session_acceptance_id'     =>  $data['data']->session_id . ' ' . $data['data']->acceptance_id,

            ];

            $this->regitserAdminActivity(collections: $collections);
            $tradeLog = TradeLog::where('reg', $data['message']->reg)->first();
            $expend = $this->charge(amount: $tradeLog->amount);
            $this->sendToSeller(amount: $expend['seller'], ledgerDetails: $tradeLog);
            Sleep::for(5)->second();
            $this->sendToAdmin(amount: $expend['admin'], ledgerDetails: $tradeLog);
        }
    }


    public function regitserAdminActivity($collections)
    {
        AdminActivity::create([
            'uuid'                  => $collections['uuid'],
            'email'                 => $collections['email'],
            'fullname'              => $collections['fullname'],
            'activity_performed'    => $collections['activity_performed'],
            'amount'                => $collections['amount'],
            'buyer'                 => $collections['buyer'],
            'seller'                => $collections['seller'],
            'reg'                   => $collections['reg'],
            'trnx_ref'              => $collections['trnx_ref'],
            'session_acceptance_id' => $collections['session_acceptance_id'],
        ]);
    }


    public function fetchPersonalBalanceForInjection()
    {

        $props = User::find(auth()->user()->id);
        $user = $props->customerstatus()->first();
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customer()->first()->personalaccount()->first()->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

        if ($data->status() == 200 || $data->status() == 202) {
            $account = $data->object();
            $balance = $user->customer()->first()->personalaccount()->first()->personalbalance()->first();
            if ($balance == null || empty($balance)) {
                $user->customer()->first()->personalaccount()->first()->personalbalance()->create([
                    'availableBalance'  => ($account->data->availableBalance / 100),
                    'ledgerBalance'     => ($account->data->ledgerBalance / 100),
                    'hold'              => $account->data->hold,
                    'pending'           => $account->data->pending,
                ]);

                $balance = $user->customer()->first()->personalaccount()->first()->personalbalance()->first();
                return response()->json(['data'  => $balance]);
            } else {
                $user->customer()->first()->personalaccount()->first()->personalbalance()->update([
                    'availableBalance'  => ($account->data->availableBalance / 100),
                    'ledgerBalance'     => ($account->data->ledgerBalance / 100),
                    'hold'              => $account->data->hold,
                    'pending'           => $account->data->pending,
                ]);

                $balance = $user->customer()->first()->personalaccount()->first()->personalbalance()->first();
                return response()->json(['data'  => $balance]);
            }
        } else {
            return response()->json(['data'  => $data->object()]);
        }
    }

    public function holdFundAtTradeRequestInitiatedForInjection($amount, $regs, $itemFor, $sellerId, $walletName, $state, $direction)
    {
        $user = User::find(auth()->user()->id);
        $fetchBuyerAccount = $user->customerstatus()->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency"  => "NGN",
                    "amount"    => ($amount * 100),
                    "reason"    => "withheld for trade transaction",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowType,
                            "id"    => $fetchBuyerAccount->customer()->first()->escrowaccount()->first()->escrowId

                        ]
                    ],
                    "account" => [
                        "data"  => [
                            "type"  => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalType,
                            "id"    => $fetchBuyerAccount->customer()->first()->personalaccount()->first()->personalId

                        ]
                    ]
                ],
                "type" => "BookTransfer"
            ]
        ];

        $makeWithHolding = $this->ToObject($payload);

        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makeWithHolding);

        // return  $transfer->object();


        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $regs,
                acceptance: null,
                itemFor: $itemFor,
                buyerId: auth()->user()->uuid,
                state: $state,
                direction: $direction,
                walletName: $walletName,
                sellerId: $sellerId
            );
            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: $regs,
                acceptance: null,
                itemFor: $itemFor,
                buyerId: auth()->user()->uuid,
                state: $state,
                direction: $direction,
                walletName: $walletName,
                sellerId: $sellerId
            );
            return response()->json($createTradeLog);
        } else {
            return response()->json($transfer->object());
        }
    }



    public function createTradeLog(
        $regs = null,
        $acceptance = null,
        $itemFor,
        $buyerId = null,
        $sellerId = null,
        $walletName,
        $state,
        $direction,
        $trnxObj
    ) {

        $trade = TradeLog::create([
            'reg'    => $regs,
            'acceptance_id'  => $acceptance ?? null,
            'item_for'   => $itemFor,
            'wallet_name'    => $walletName ?? null,
            'buyer_uuid' => $buyerId ?? null,
            'seller_uuid'    => $sellerId ?? null,
            'trade_request_ref'  =>  null,
            'transfer_id'    => $trnxObj->data->id ?? null,
            'type'   => $trnxObj->data->id ?? null,
            'reason' => $trnxObj->data->attributes->reason ?? null,
            'reference'  => $trnxObj->data->attributes->reference ?? null,
            'amount' => $trnxObj->data->attributes->amount ?? null,
            'failureReason'  => $trnxObj->data->attributes->failureReason ?? null,
            'currency'   => $trnxObj->data->attributes->currency ?? null,
            'status' => $trnxObj->data->attributes->status ?? null,
            'state'  => $state ?? null,
            'direction'  => $direction ?? null,
            'destination_id' => $trnxObj->data->relationships->destinationAccount->data->id ?? null,
            'from_id'    => $trnxObj->data->relationships->account->data->id ?? null,
        ]);

        return $trade;
    }
}
