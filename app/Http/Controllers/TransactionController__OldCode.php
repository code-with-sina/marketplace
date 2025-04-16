<?php

namespace App\Http\Controllers;


use App\Audit\Trail;
use App\Models\User;
use App\Models\Rate;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Prop\FeeDeterminantAid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;



class TransactionController extends Controller
{

    public function checkHeader($requestHeader)
    {
        if ($requestHeader !== 'Ratefy') {
            return false;
        } else {
            return true;
        }
    }


    public function SellerInitiateOrder(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {

            $validation = Validator::make($request->all(), [
                'wallet_name'       => ['required', 'string'],
                "wallet_id"         => ['required'],
                "item_for"          => ['required', 'string'],
                "item_id"           => ['required', 'string'],
                "amount"            => ['required'],
                "offer_rate"        => ['required'],
                "amount_to_receive" => ['required'],
                "recipient"         => ['required', 'string']
            ]);


            if ($validation->fails()) {
                Trail::retrieve(mark: $marks, retrieveData: $validation->errors() ?? null);
                return response()->json([
                    'status' => ["failed", $validation->errors()]
                ]);
            } else {

                try {

                    $regKey = Str::uuid();
                    $offerDetail = new FeeDeterminantAid();

                    $offerItem = $request->item_for === 'buy' ? $offerDetail->detailOffer(direction: 'selleroffer', id: $request->item_id) : $offerDetail->detailOffer(direction: 'buyeroffer', id: $request->item_id);

                    $response = Http::post('https://transactionbased.ratefy.co/api/initiate-request', [
                        'wallet_name'       => $request->wallet_name,
                        "wallet_id"         => $request->wallet_id,
                        "item_for"          => $request->item_for,
                        "amount"            => $request->amount,
                        "item_id"           => $request->item_id,
                        "owner"             => $request->owner,
                        'trade_rate'        => $request->offer_rate,
                        "amount_to_receive" => $request->amount_to_receive,
                        "owner"             => auth()->user()->uuid,
                        "recipient"         => $request->recipient,
                        'fund_attached'     => 'no',
                        'fund_reg'          => $regKey,
                        'status'            => "active",
                        'duration'          => "30",
                        'charges_for'       => $request->item_for === 'buy' ? 'selleroffer' : 'buyeroffer',
                        'ratefy_fee'        => $offerItem->ratefyfee,
                        'percentage'        => $offerItem->percentage

                    ])->throw();

                    Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
                    return response()->json([
                        'data'      => $request->all(),
                        'response'  => $response->object(),
                        'message'   => 'Trade request sent! Kindly wait for a response in 30 minutes.',
                        'status'    => 200
                    ]);
                } catch (Exception $e) {
                    Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
                }
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    public function BuyerOrderInitiate(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $validation = Validator::make($request->all(), [
                'wallet_name'       => ['required', 'string'],
                'offer_rate'        => ['required'],
                "wallet_id"         => ['required'],
                "item_for"          => ['required', 'string'],
                "item_id"           => ['required', 'string'],
                "amount"            => ['required'],
                "offer_rate"        => ['required'],
                "amount_to_receive" => ['required'],
                "recipient"         => ['required', 'string']
            ]);


            if ($validation->fails()) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $validation->errors(), traceId: $marks, action: __FUNCTION__);
                Trail::retrieve(mark: $marks, retrieveData: $validation->errors() ?? null);
                return response()->json([
                    'status' => ["failed", $validation->errors()]
                ]);
            } else {
                $balance = $this->fetchBalance();
                if ($balance['status'] === 400) {
                    return response()->json(['response'  => $balance['data']], 400);
                } else {
                    if ((int)$balance['data'] >= (int)$request->amount) {
                        try {
                            $regKey = Str::uuid();
                            $offerDetail = new FeeDeterminantAid();
                            $offerItem = $request->item_for === 'buy' ? $offerDetail->detailOffer(direction: 'selleroffer', id: $request->item_id) : $offerDetail->detailOffer(direction: 'buyeroffer', id: $request->item_id);
                            $response = Http::post('https://transactionbased.ratefy.co/api/initiate-request', [
                                'wallet_name'       => $request->wallet_name,
                                "wallet_id"         => $request->wallet_id,
                                "item_for"          => $request->item_for,
                                "item_id"           => $request->item_id,
                                "amount"            => $request->amount,
                                'trade_rate'        => $request->offer_rate,
                                'amount_to_receive' => $request->amount_to_receive,
                                "owner"             => auth()->user()->uuid,
                                "recipient"         => $request->recipient,
                                'status'            => "active",
                                'duration'          => "30",
                                'fund_attached'     => 'yes',
                                'fund_reg'          => $regKey,
                                'charges_for'       => $request->item_for === 'buy' ? 'selleroffer' : 'buyeroffer',
                                'ratefy_fee'        => $offerItem->ratefyfee,
                                'percentage'        => $offerItem->percentage
                            ])->throw();

                            $this->holdFundAtTradeInitiated(
                                ledgerKey: $regKey,
                                authorizer: auth()->user()->uuid,
                                amount: $request->amount,
                                offerRate: $request->offer_rate,
                                sellerId: $request->recipient,
                                walletName: $request->wallet_name,
                                itemFor: $request->item_for
                            );
                            Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
                            return response()->json([
                                'response'  => $response->object(),
                                'message'   => 'Trade request sent! Kindly wait for a response in 30 minutes.',
                                'status'    => 200
                            ]);
                        } catch (Exception $e) {
                            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
                        }
                    } else {
                        Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
                        Trail::retrieve(mark: $marks, retrieveData: 'insufficient fund' ?? null);
                        return response()->json(['response'  => 'insufficient fund'], 400);
                    }
                }
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    public function BuyerAcceptRequest(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                'id'            => ['required'],
                "owner"         => ['required', 'string'],
                "amount"        => ['required'],
                "charges_for"   => ['required'],
                "ratefy_fee"    => ['required'],
                "percentage"    => ['required'],

            ]);


            $balance = $this->fetchBalance();
            if ($balance['status'] === 400) {
                return response()->json(['response'  => $balance['data']], 400);
            } else {
                if ((int)$balance['data'] >= (int)$request->amount) {
                    try {
                        // $regKey = Str::uuid();
                        $response = Http::post('https://transactionbased.ratefy.co/api/buyer-accept-request', [
                            "id"            => $request->id,
                            "owner"         => $request->owner,
                            "charges_for"   => $request->charges_for,
                            "ratefy_fee"    => $request->ratefy_fee,
                            "percentage"    => $request->percentage,
                            // "fund_attach"   => "yes",
                            // "fund_reg"      => $regKey,
                        ])->throw();

                        // $this->holdFundAtTradeRequestAccepted(authorizer: auth()->user()->uuid, ledgerKey: $regKey, amount: $request->amount);
                        // Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);

                        return response()->json([
                            'response'  => $response->object(),
                            'status'    => 200
                        ]);
                    } catch (Exception $e) {
                        Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
                    }
                } else {
                    Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
                    Trail::retrieve(mark: $marks, retrieveData: 'insufficient fund' ?? null);
                    return response()->json(['response'  => 'insufficient fund']);
                }
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function rejectRequest(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                'id'            => ['required'],
                "owner"         => ['required', 'string'],
            ]);



            try {
                Http::post('https://transactionbased.ratefy.co/api/reject-request', [
                    "id"        => $request->id,
                    "owner"     => $request->owner
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData: 'success' ?? null);
                return response()->json([
                    'message' =>  'success',
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function SellerAcceptRequest(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                'id'            => ['required'],
                "owner"         => ['required', 'string'],
                "charges_for"   => ['required'],
                "ratefy_fee"    => ['required'],
                "percentage"    => ['required'],
            ]);

            try {
                $response = Http::post('https://transactionbased.ratefy.co/api/accept-request', [
                    "id"            => $request->id,
                    "owner"         => $request->owner,
                    "charges_for"   => $request->charges_for,
                    "ratefy_fee"    => $request->ratefy_fee,
                    "percentage"    => $request->percentage
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function fetchBalance()
    {
        $marks = Str::ulid();
        try {
            $balance = Http::post('https://walletbased.ratefy.co/api/customer/personal/get-balance', [
                'uuid' => auth()->user()->uuid
            ])->throw()->json();

            if (empty($balance['data']['id']) == false) {
                return  ['status' => 200, 'data' => $balance['data']['availableBalance']];
            } else {
                return ['status' => 400, 'data' => $balance['data']['errors'][0]['detail']];
            }


            Trail::log(user: @auth()->user()->uuid, errorTrace: $balance['data'], traceId: $marks, action: __FUNCTION__);
        } catch (Exception $e) {
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
        }
    }

    public function fetchOrders(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {

            try {
                $data = Http::post('https://transactionbased.ratefy.co/api/fetch-initiated-order', [
                    'uuid' => auth()->user()->uuid
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data'  => $data->object(),
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function fetchActiveTransaction(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {

            try {
                $data = Http::get('https://transactionbased.ratefy.co/api/fetch-active-transactions/' . auth()->user()->uuid)->throw();
                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data'  => $data->object(),
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    public function fetchNextActiveTransaction(Request $request, $pageNumber)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            try {
                $data = Http::get('https://transactionbased.ratefy.co/api/fetch-active-transactions/' . auth()->user()->uuid . '?uuid=' . auth()->user()->uuid . '&page%5Bnumber%5D=' . $pageNumber)->throw();
                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data'  => $data->object(),
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    public function fetchPastTransaction(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            try {
                $data = Http::get('https://transactionbased.ratefy.co/api/fetch-past-transactions/' . auth()->user()->uuid)->throw();
                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data'  => $data->object(),
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function fetchNextPastTransaction(Request $request,  $pageNumber)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            try {
                $data = Http::get('https://transactionbased.ratefy.co/api/fetch-past-transactions/' . auth()->user()->uuid . '?uuid=' . auth()->user()->uuid . '&page%5Bnumber%5D=' . $pageNumber)->throw();
                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data'  => $data->object(),
                    'status'    => 200
                ]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    /* Comming back to this area */
    public function getActiveTransaction(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );

        try {
            $data = Http::post('https://transactionbased.ratefy.co/api/get-active-transaction', [
                'uuid' => auth()->user()->uuid
            ])->throw();

            Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
            return response()->json(['data' => $data->object()]);
        } catch (Exception $e) {
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
        }
    }

    public function fetchWalletTransaction(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );

        try {
            $data = Http::get('https://walletbased.ratefy.co/api/fetch-tranasction-history/' . auth()->user()->uuid)->throw();
            Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
            return response()->json(['data' => $data->object()]);
        } catch (Exception $e) {
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
        }
    }

    public function fetchNextWalletTransaction(Request $request, $pageNumber)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );

        try {
            $data = Http::get('https://walletbased.ratefy.co/api/fetch-tranasction-history/' . auth()->user()->uuid . '/' . $pageNumber)->throw();
            Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
            return response()->json(['data' => $data->object()]);
        } catch (Exception $e) {
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
        }
    }

    public function fetchWalletTransactionStatus(Request $request, $transferId)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );

        try {
            $data = Http::get('https://walletbased.ratefy.co/api/fetch-tranasction-status/' . $transferId)->throw();
            Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
            return response()->json(['data' => $data->object()]);
        } catch (Exception $e) {
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
        }
    }

    public function fetchSingleWallet(Request $request, $id)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            try {
                $data = Http::post('https://offerbased.ratefy.co/api/fetch-single-ewallet-only', [
                    'id' => $id
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json(['data' => $data->object()]);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function cancelRequest(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                'id'            => ['required'],
            ]);


            try {
                $data = Http::post('https://transactionbased.ratefy.co/api/cancel-trade-request', [
                    'id' => $request->id
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data' => $data->object(),
                    'message'   => 'Trade cancelled, An email has been sent to the recipient',
                    'status'    => 200
                ], 200);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    public function cancelBuyingRequest(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(),
            ip: $request->ip(),
            mark: $marks,
            method: $request->method(),
            action: __FUNCTION__,
            post: $request->collect() ?? null,
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                'id'            => ['required'],
            ]);


            try {
                $data = Http::post('https://transactionbased.ratefy.co/api/cancel-buying-trade-request', [
                    'id' => $request->id
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData: $data->object() ?? null);
                return response()->json([
                    'data' => $data->object(),
                    'message'   => 'Trade cancelled, An email has been sent to the recipient',
                    'status'    => 200
                ], 200);
            } catch (Exception $e) {
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__);
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function holdFundAtTradeInitiated($ledgerKey, $authorizer, $amount, $offerRate, $walletName, $sellerId, $itemFor)
    {
        Log::info([$ledgerKey, $authorizer, $amount, $offerRate, $walletName, $sellerId, $itemFor]);
        try {
            Log::info([$ledgerKey, $authorizer, $amount, $offerRate, $walletName, $sellerId, $itemFor]);
            Http::post('https://walletbased.ratefy.co/api/customer/trade/hold-fund-at-trade-request', [
                'uuid'          => $authorizer,
                'amount'        => ($amount * $offerRate),
                'regs'          => $ledgerKey,
                'state'         => 'withhold',
                'direction'     => 'incoming',
                'walletName'    => $walletName,
                'sellerId'      => $sellerId,
                'item_for'      => $itemFor

            ])->throw();
        } catch (Exception $e) {
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__);
        }
    }


    public function calculate($percentage, $amount, $rate)
    {
        $accurateAmount = (int)$amount * (int)$rate->rate_normal;

        return $accurateAmount;
    }

    //    public function makeCalculate() {
    //         try {
    //             $balance = Http::post('https://walletbased.ratefy.co/api/customer/personal/get-balance', [
    //                 'uuid' => '9b38eb79-b1ee-4a1f-a3f3-bfcaca1bbb0b'
    //             ])->throw()->json();

    //             if(empty($balance['data']['id']) == false) {
    //                 return response()->json(['status' => 200, 'data' => $balance['data']['availableBalance']]);
    //             }else {
    //                 return response()->json($balance['data']['errors'][0]['detail']);
    //             }


    //         }catch(Exception $e) {
    //             return response()->json($e->getMessage());
    //         } 
    //    }



    public function freedetermine(Request $request)
    {
        $determinant = new FeeDeterminantAid();
        $result = $determinant->getUser(direction: $request->direction, id: $request->id);

        return response()->json($result);
    }
}
