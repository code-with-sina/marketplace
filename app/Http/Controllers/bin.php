<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Otp\OneTimePassword;
use Illuminate\Http\Request;
use App\Models\AnchorBankList;
use App\Jobs\CreateAccountJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use App\WalletFacades\HasValidateKyc;
use App\AdminFacades\HasObjectConverter;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\MessengerController;

class CustomersController extends Controller
{
    use HasObjectConverter, HasValidateKyc;

    public function createCustomers(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "selfieimage"   => ['required', 'mimes:png,jpg,jpeg', 'max:2048'],
            "dateOfBirth"   => ['required', 'string'],
            "bvn"           => ['required', 'string'],
            "idNumber"      => ['required', 'string'],
            "idType"        => ['required', 'string'],
            "gender"        => ['required', 'string']
        ]);


        if ($validation->fails()) {
            return response()->json([
                'status' => ["failed", $validation->errors()]
            ]);
        } else {
            if ($request->has('selfieimage')) {
                try {
                    $filename = time() . '.' . $request->selfieimage->extension();
                    $request->selfieimage->move(public_path('upload/validation/'), $filename);
                    $user = User::find(auth()->user()->id);
                    $payload = [
                        'firstname'     => $user->firstname,
                        'lastname'      => $user->lastname,
                        'country'       => $user->profile()->first()->country,
                        'state'         => $user->profile()->first()->state,
                        'city'          => $user->profile()->first()->city,
                        'postalcode'    => $user->profile()->first()->zip_code,
                        'address'       => $user->profile()->first()->address,
                        'email'         => $user->email,
                        'phonenumber'   => $user->mobile
                    ];

                    $customer = $user->customerstatus()->first();
                    if (empty($customer) === true) {
                        $user->customerstatus()->create(['status'    => 'unverified', 'type'  => 'IndividualCustomer']);
                        $data = $this->createMember(user: $user, load: $payload, selfieimage: 'https://p2p.ratefy.co/upload/validation/' . $filename,  collection: $request->collect());
                        return response()->json($data);
                    } else {
                        $data = $this->updateMember(user: $user, load: $payload,  selfieimage: 'https://p2p.ratefy.co/upload/validation/' . $filename, collection: $request->collect());
                        return response()->json($data);
                    }
                } catch (Exception $e) {
                }
            }
        }
    }

    public function createMember($user, $collection, $load, $selfieimage)
    {
        $this->makePendingKyc(collections: $collection);
        $payload = [
            'data' => [
                'attributes'    => [
                    'fullName'  => [
                        'firstName' => $load['firstname'],
                        'lastName'  => $load['lastname']
                    ],
                    'address'       => [
                        'country'       => 'NG',
                        'state'         => $load['state'],
                        'city'          => $load['city'],
                        'postalCode'    => $load['postalcode'],
                        'addressLine_1'     => $load['address']
                    ],
                    'identificationLevel2'  => [
                        'dateOfBirth'   => Carbon::parse($collection['dateOfBirth']),
                        'selfieImage'   => $selfieimage,
                        'gender'        => $collection['gender'],
                        'bvn'           => $collection['bvn']
                    ],
                    'identificationLevel3'  => [
                        'idNumber'      => $collection['idNumber'],
                        'expiryDate'    => Carbon::parse($collection['expiryDate']),
                        'idType'        => $collection['idType'],
                    ],
                    'email'         => $load['email'],
                    'phoneNumber'   => $load['phonenumber']
                ],
                'type'  => 'IndividualCustomer'
            ]
        ];

        $objData = $this->ToObject($payload);

        $customerObject = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . 'customers', $objData);

        if ($customerObject->status() === 200) {

            $customers = $customerObject->object();
            $user->customerstatus()->update(['status' => $customers->data->attributes->verification->status, 'customerId' => $customers->data->id]);
            $user->customerstatus()->first()->customer()->create([
                'customerId'    => $customers->data->id,
                'customerType'  => $customers->data->type,
                'firstName'     => $customers->data->attributes->fullName->firstName,
                'lastName'      => $customers->data->attributes->fullName->lastName,
                'address'       => $customers->data->attributes->address->addressLine_1,
                'country'       => $customers->data->attributes->address->country,
                'state'         => $customers->data->attributes->address->state,
                'city'          => $customers->data->attributes->address->city,
                'postalCode'    => $customers->data->attributes->address->postalCode,
                'phoneNumber'   => $customers->data->attributes->phoneNumber,
                'status'        => $customers->data->attributes->status,
                'email'         => $customers->data->attributes->email,
                'gender'        => $customers->data->attributes->identificationLevel2->gender,
                'dateOfBirth'   => $customers->data->attributes->identificationLevel2->dateOfBirth,
                'bvn'           => $customers->data->attributes->identificationLevel2->bvn,
                'selfieImage'   => $customers->data->attributes->identificationLevel2->selfieImage,
                'expiryDate'    => $customers->data->attributes->identificationLevel3->expiryDate,
                'idType'        => $customers->data->attributes->identificationLevel3->idType,
                'idNumber'      => $customers->data->attributes->identificationLevel3->idNumber,
                'registered'    => Hash::make($customers->data->attributes->status . '-' . uniqid())
            ]);

            $validate = $this->validateKyc($user->customerstatus()->first()->customer()->first());

            $this->notifyStaffs(direction: 'Kyc', content: createCustomer . 'created on >>> ', id: $createCustomer->id);

            if ($validate === 200) {
                $data  = $user->customerstatus()->first();
                CreateAccountJob::dispatch($user->uuid);
                $user->authorization()->update(['kyc'   => 'approved']);
                return ["data" => $data->load(['customerstatus.customer']), "status" => 200];
            }
        } else {
            return ["status" => $customerObject->status(), "data" => $customerObject->object()];
        }
    }


    public function makePendingKyc($collections)
    {
        $user = User::find(auth()->user()->id);
        $user->kycstate()->create([
            'bvn'           => $collections['bvn'],
            'selfieImage'   => $collections['selfieImage'],
            'idType'        => $collections['idType'],
            'idNumber'      => $collections['idNumber'],
            'dateOfBirth'   => $collections['dateOfBirth'],
            'gender'        => $collections['gender'],
            'status'        => 'pending'
        ]);
    }

    public function updateMember($user, $collection, $load, $selfieimage)
    {
        $payload = [
            'data' => [
                'attributes'    => [
                    'fullName'  => [
                        'firstName' => $load['firstname'],
                        'lastName'  => $load['lastname']
                    ],
                    'address'       => [
                        'country'       => 'NG',
                        'state'         => $load['state'],
                        'city'          => $load['city'],
                        'postalCode'    => $load['postalcode'],
                        'addressLine_1'     => $load['address']
                    ],
                    'identificationLevel2'  => [
                        'dateOfBirth'   => Carbon::parse($collection['dateOfBirth']),
                        'selfieImage'   => $selfieimage,
                        'gender'        => $collection['gender'],
                        'bvn'           => $collection['bvn']
                    ],
                    'identificationLevel3'  => [
                        'idNumber'      => $collection['idNumber'],
                        'expiryDate'    => Carbon::parse($collection['expiryDate']),
                        'idType'        => $collection['idType'],
                    ],
                    'email'         => $load['email'],
                    'phoneNumber'   => $load['phonenumber']
                ],
                'type'  => 'IndividualCustomer'
            ]
        ];


        $objData = $this->ToObject($payload);

        $updateObject = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . 'customers', $objData);

        if ($updateObject->status() === 200) {
            $updateCustomers = $updateObject->object();
            $user->customerstatus()->update(['status' => $updateCustomers->data->attributes->verification->status, 'customerId' => $updateCustomers->data->id]);
            $user->customerstatus()->first()->customer()->create([
                'customerId'    => $updateCustomers->data->id,
                'customerType'  => $updateCustomers->data->type,
                'firstName'     => $updateCustomers->data->attributes->fullName->firstName,
                'lastName'      => $updateCustomers->data->attributes->fullName->lastName,
                'address'       => $updateCustomers->data->attributes->address->addressLine_1,
                'country'       => $updateCustomers->data->attributes->address->country,
                'state'         => $updateCustomers->data->attributes->address->state,
                'city'          => $updateCustomers->data->attributes->address->city,
                'postalCode'    => $updateCustomers->data->attributes->address->postalCode,
                'phoneNumber'   => $updateCustomers->data->attributes->phoneNumber,
                'status'        => $updateCustomers->data->attributes->status,
                'email'         => $updateCustomers->data->attributes->email,
                'gender'        => $updateCustomers->data->attributes->identificationLevel2->gender,
                'dateOfBirth'   => $updateCustomers->data->attributes->identificationLevel2->dateOfBirth,
                'bvn'           => $updateCustomers->data->attributes->identificationLevel2->bvn,
                'selfieImage'   => $updateCustomers->data->attributes->identificationLevel2->selfieImage,
                'expiryDate'    => $updateCustomers->data->attributes->identificationLevel3->expiryDate,
                'idType'        => $updateCustomers->data->attributes->identificationLevel3->idType,
                'idNumber'      => $updateCustomers->data->attributes->identificationLevel3->idNumber,
                'registered'    => Hash::make($updateCustomers->data->attributes->status . '-' . uniqid())
            ]);

            $validate = $this->validateKyc($upsertCustomer->customer()->first());

            if ($validate === 200) {
                $data = CustomerStatus::where('uuid', $user->uuid)->first();
                CreateAccountJob::dispatch($user->uuid);
                $user->authorization()->update(['kyc'   => 'approved']);
                return ["data" => $data->load(['customerstatus.customer']), "status" => 200];
            }
        } else {
            return ["status" => $updateObject->status(), "data" => $updateObject->object()];
        }
    }

    public function fetchEscrowBalance(Request $request)
    {
        $user = User::find(auth()->user()->id);
        // $user = user->customerstatus()->first();
        $balance = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customerstatus()->first()->customer()->first()->escrowaccount()->first()->escrowId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

        return response()->json($balance->object());
    }


    public function fetchPersonalBalance(Request $request)
    {
        $user = User::find(auth()->user()->id);
        // $user = CustomerStatus::where('uuid', $request->uuid)->first();
        $balance = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");



        return response()->json($balance->object());
    }


    public function createCounterPartyAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nipcode'       => ['required', 'string'],
            'accountnumber' => ['required', 'string'],
            'bank_id'       => ['required', 'string'],
            'bank_name'     => ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => $validation->errors()
            ]);
        } else {
            $status = $this->verifyBankAccount($request->nipcode, $request->accountnumber);
            if ($status['status'] == 400) {
                return response()->json(["status" => 400, "error" => $status['message']]);
            } else {

                $user = User::find(auth()->user()->id);
                $nameCheck = array();
                $nameCheck = explode(" ", $status['message'], 2);
                $getname = explode(" ", $status['message'], 2);

                // change this in life production
                if (in_array($user->customerstatus()->first()->customer()->first()->lastName, $nameCheck)) {

                    // if(in_array((string)$getname[0], $nameCheck)){

                    $load = [
                        'data' => [
                            'type'          => 'CounterParty',
                            'attributes'    => [
                                'verifyName'    => true,
                                'bankCode'      => $request->nipcode,
                                'accountName'   => $status['message'],
                                'accountNumber' => $request->accountnumber
                            ],
                            'relationships' => [
                                'bank'  => [
                                    'data'  => [
                                        'id'    =>  $request->bank_id,
                                        'type'  => 'Bank'
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $payload = Convert::ToObject($load);
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'x-anchor-key'  => env('ANCHOR_KEY')
                    ])->post(env('ANCHOR_SANDBOX') . 'counterparties', $payload);

                    if ($response->status() == 201) {
                        $accounts = $response->object();
                        $user->customerstatus()->first()->customer()->first()->counterpartyaccount()->create([
                            'counterPartyId'    => $accounts->data->id,
                            'counterPartyType'  => $accounts->data->type,
                            'bankId'            => $accounts->data->attributes->bank->id,
                            'bankName'          => $accounts->data->attributes->bank->name,
                            'bankNipCode'       => $accounts->data->attributes->bank->nipCode,
                            'accountName'       => $accounts->data->attributes->accountName,
                            'accountNumber'     => $accounts->data->attributes->accountNumber,
                            'status'            => $accounts->data->attributes->status
                        ]);
                        return response()->json(['data' => $user->load(['customer', 'customer.counterpartyaccount'])]);
                    } else {
                        return response()->json(['data' => $response->object()]);
                    }
                } else {
                    return response()->json(["status" => 400, "error" => "Your Names do not match your account number"]);
                }
            }
        }
    }


    public function verifyBankAccount($bankCode, $accountNumber)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'payments/verify-account/' . $bankCode . '/' . $accountNumber)->throw();

        $data = $response->object();
        $user = User::find(auth()->user()->id);
        $accounts = $user->customerstatus()->first()->customer()->first()->counterpartyaccount()->where('bankNipCode', $data->data->attributes->bank->nipCode)->where('accountNumber', $data->data->attributes->accountNumber)->first();
        if ($accounts !== null) {
            return ['status' => 400, 'message' => "Your account number already exist"];
        } else {
            return ['status' => 200, 'message' => $data->data->attributes->accountName];
        }
    }


    public function getBankList()
    {
        $response = AnchorBankList::all();
        return response()->json(['data' => $response]);
    }


    public function validateUser(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $status = $user->customerstatus()->where('status', 'fully-verified')->count();
        return response()->json(['status' => $status]);
    }



    public function fetchWallet(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $wallet = $user->customerstatus()->first();
        return response()->json($wallet->load([
            'customer',
            'customer.escrowaccount',
            'customer.personalaccount',
            'customer.escrowaccount.escrowbalance',
            'customer.escrowaccount.virtualnuban',
            'customer.personalaccount.personalbalance',
            'customer.personalaccount.virtualnuban',
            'customer.counterpartyaccount',
            'customer.withdrawalhistory'
        ]));
    }


    public function fetchExpositonAccount(Request $request)
    {
        $patron = User::find(auth()->user()->id);
        $user = $patron->customerstatus()->first();
        return response()->json(['Personal account detail' => $user->customer()->first()->personalaccount()->first()->virtualnuban()->first(), 'Escrow account detail' => $user->customer()->first()->escrowaccount()->first()->virtualnuban()->first()]);
    }

    public function getCounterPartyAccount(Request $request)
    {
        $patron = User::find(auth()->user()->id);
        $accountDetail = $patron->customerstatus()->first();
        return response()->json([
            'detail'    => $accountDetail->customer()->first()->counterpartyaccount()->get(),
        ]);
    }


    public function deleteCounterPartyAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'bankaccountid' => ['required', 'string']
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status' => $validation->errors()
            ]);
        } else {
            $patron = User::find(auth()->user()->id);
            $delete = $patron->customerstatus()->first();
            $delete->customer()->first()->counterpartyaccount()->where('id', $request->bankaccountid)->delete();

            return response()->json([
                'status'    => 'ok',
            ]);
        }
    }


    // public function customerDetail($uuid){
    //     $user = Http::post('https://userbased.ratefy.co/api/detail', ['uuid' => $uuid]);
    //     return $user->object();
    // }


    /* To come back for latter  */
    public function customerProfile($uuid)
    {
        $profile = Http::post('https://profilebased.ratefy.co/api/get-full-profile', ['uuid' => $uuid]);
        return $profile->object();
    }




    public function getTransactionHistory($customerId, $from, $to, $type, $direction)
    {
        $data = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'transactions?accountId=' . $accountId . '&customerId=' . $customerId . '&from=' . $from . '&to=' . $to . '&type=' . $type . '&direction=' . $direction);

        return response()->json($data->object());
    }




    public function withdrawal(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'amount'        => ['required', 'string'],
            'accountId'     => ['required', 'string'],
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status' => $validation->errors()
            ]);
        } else {
            $balance = $this->fetchBalance();
            if ($balance['status'] === 400) {
                return response()->json(['response'  => $balance['data']], 400);
            } else {
                if ((int)$balance['data'] >= (int)$request->amount) {

                    $check = OneTimePassword::initProcess(amount: $request->amount, accountId: $request->accountId);
                    return response()->json(['status' => 200, 'message' => $check]);
                } else {
                    return response()->json(['response'  => 'insufficient fund'], 400);
                }
            }
        }
    }

    public function confirmOtp(Request $request)
    {
        $check = OneTimePassword::confirmPassword(otp: $request->otp);
        if ($check['status'] == 200) {
            $this->processWithdrawal(amount: $check['message']->amount, accountId: $check['message']->accountId);
        } else {
            return response()->json($check);
        }
    }


    public function resendOtp(Request $request)
    {
        $check = OneTimePassword::reProcess(tally: $request->hash);
        return response()->json(['status' => 200, 'message' => $check]);
    }


    public function processWithdrawal($amount, $accountId, MessengerController $messengerController)
    {
        $user = User::find(auth()->user()->id);

        $fetchCustomerAccount = $user->customerstatus()->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency" => "NGN",
                    "amount" => ($amount * 100),
                    "reason" => "withdrawal",
                    "reference" => Str::uuid()
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data" => [
                            "type" => "SubAccount"
                        ]
                    ],
                    "account" => [
                        "data" => [
                            "id" =>     $fetchCustomerAccount->customer()->first()->personalaccount()->first()->personalId,
                            "type" =>   $fetchCustomerAccount->customer()->first()->personalaccount()->first()->personalType
                        ]
                    ],
                    "counterParty" => [
                        "data" => [
                            "type" => "CounterParty",
                            "id" => $accountId
                        ]
                    ]
                ],
                "type" => "NIPTransfer"
            ]
        ];

        $makingWithdrawal = $this->ToObject($payload);
        $transfer =  Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . "transfers", $makingWithdrawal)->throw();


        if ($transfer->status() === 200 || $transfer->status() === 202) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: Str::uuid(),
                acceptance: 'withdrawal',
                itemFor: 'withdrawal',
                buyerId: auth()->user()->uuid,
                state: 'release',
                direction: 'incoming',
                walletName: 'withdrawal',
                sellerId: auth()->user()->uuid
            );

            $amount = $transfer->object();
            $messengerController->sendWithdrawalNotification(auth()->user()->uuid, $amount->data->attributes->amount);

            return response()->json($createTradeLog);
        } elseif ($transfer->status() === 201) {
            $createTradeLog = $this->createTradeLog(
                trnxObj: $transfer->object(),
                regs: Str::uuid(),
                acceptance: 'withdrawal',
                itemFor: 'withdrawal',
                buyerId: auth()->user()->uuid,
                state: 'release',
                direction: 'incoming',
                sellerId: auth()->user()->uuid,
                walletName: 'withdrawal',
            );

            $amount = $transfer->object();
            $messengerController->sendWithdrawalNotification(auth()->user()->uuid, $amount->data->attributes->amount);

            return response()->json(['status' => 200, 'data' =>  $transfer->object()]);
        } else {
            return response()->json(['status' => 200, 'data' => $transfer->object()]);
        }
    }




    public function createTradeLog(
        $regs,
        $acceptance,
        $itemFor,
        $buyerId,
        $sellerId,
        $walletName,
        $state,
        $direction,
        $trnxObj
    ) {

        $trade = TradeLog::create([
            'reg'    => $regs,
            'acceptance_id'  => $acceptance ?? null,
            'item_for'   => $itemFor ?? null,
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


    public function fetchBalance()
    {
        $user = User::find(auth()->user()->id);
        $balance = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");
        if ($balance->status() == 200) {
            $bals = $balance->object();
            return  ['status' => 200, 'data' => $bals->data->availableBalance];
        } else {
            $bals = $balance->object();
            return ['status' => 400, 'data' =>  $bals->errors[0]->detail];
        }
    }


    // $validation = Validator::make($request->all(), [
    //     'wallet_name'       => ['required', 'string'],
    //     'offer_rate'        => ['required'],
    //     "wallet_id"         => ['required'],
    //     "item_for"          => ['required', 'string'],
    //     "item_id"           => ['required', 'string'],
    //     "amount"            => ['required'],
    //     "offer_rate"        => ['required'],
    //     "amount_to_receive" => ['required'],
    //     "recipient"         => ['required', 'string']
    // ]);

    // if ($validation->fails()) {
    //     return response()->json(['error' => $validation->errors()]);
    // } else {
    //     $balance = $this->fetchBalance();
    //     if ($balance['status'] === 400) {
    //         return response()->json(['response'  => $balance['data']], 400);
    //     } else {
    //         if ((int)$balance['data'] >= (int)$request->amount) {
    //             $regKey = Str::uuid();
    //             $offerDetail = new FeeDeterminantAid();
    //             $offerItem = $request->item_for === 'buy' ? $offerDetail->detailOffer(direction: 'selleroffer', id: $request->item_id) : $offerDetail->detailOffer(direction: 'buyeroffer', id: $request->item_id);

    //             $prepared = app(ChargeService::class)
    //                 ->getOffer(itemFor: $request->item_for, tradeTotal: $request->amount, itemId: $request->item_id)
    //                 ->getTradeOwner()
    //                 ->prepareChargeStatement()
    //                 ->state();

    //             $unique = TradeRequest::create([
    //                 'wallet_name'       =>  $request->wallet_name,
    //                 'wallet_id'         =>  $request->wallet_id,
    //                 'item_for'          =>  $request->item_for,
    //                 'item_id'           =>  $request->item_id,
    //                 'amount'            =>  $request->amount,
    //                 'trade_rate'        =>  $request->offer_rate,
    //                 'amount_to_receive' =>  $request->amount_to_receive,
    //                 'owner'             =>  auth()->user()->uuid,
    //                 'recipient'         =>  $request->recipient,
    //                 'duration'          =>  "30",
    //                 'start'             =>  Carbon::now(),
    //                 'end'               =>  Carbon::now()->addMinutes(30),
    //                 'notify_time'       =>  'start',
    //                 'fund_attached'     =>  'yes',
    //                 'fund_reg'          =>  $regKey,
    //                 'charges_for'       =>  $request->charges_for === "buyeroffer" ? "buyer" : "seller",
    //                 'ratefy_fee'        =>  $offerItem->ratefyfee  == null ? 'null' : $offerItem->ratefyfee,
    //                 'percentage'        =>  $offerItem->percentage  == null ? 'null' : $offerItem->percentage,
    //                 'trade_registry'     => Str::uuid(),
    //                 'status'            => 'active'
    //             ]);

    //             $unique->charge()->create([
    //                 'product'   => $prepared['product'],
    //                 'offer'   => $prepared['offer'],
    //                 'owner'   => $prepared['owner'],
    //                 'uuid'   => $prepared['uuid'],
    //                 'fee'   => $prepared['prepared invoice']['fee'],
    //                 'total'   => $prepared['prepared invoice']['total'],
    //             ]);

    //             $this->holdFundAtTradeInitiated(
    //                 ledgerKey: $regKey,
    //                 amount: $request->amount,
    //                 offerRate: $request->offer_rate,
    //                 sellerId: $request->recipient,
    //                 walletName: $request->wallet_name,
    //                 itemFor: $request->item_for
    //             );

    //             $this->notifyStaffs(direction: 'Trade Request', content: $unique . ' created on >>>> ', uuid: $unique->owner, id: $unique->id);
    //             TradeRequestNotificationJob::dispatch($unique->wallet_name, $unique->wallet_id, $unique->item_for, $unique->item_id, $unique->amount, $unique->owner, $unique->recipient, $unique->status, $unique->duration, $request->charges_for, $request->ratefy_fee, $request->percentage);

    //             return response()->json([
    //                 'response'  => $unique,
    //                 'message'   => 'Trade request sent! Kindly wait for a response in 30 minutes.',
    //                 'status'    => 200
    //             ]);
    //         }
    //     }
    // }


    // $acceptanceId =  Str::uuid();
    // $sessionId = Str::uuid();
    // $paymentId = Str::uuid();
    // $validation = Validator::make($request->all(), [
    //     'id'            => ['required'],
    //     "owner"         => ['required', 'string'],
    //     "amount"        => ['required'],
    //     "charges_for"   => ['required'],
    //     "ratefy_fee"    => ['required'],
    //     "percentage"    => ['required'],
    // ]);

    // if ($validation->fails()) {
    //     return response()->json(['error' => $validation->errors()]);
    // } else {
    //     $balance = $this->fetchBalance();
    //     if ($balance['status'] === 400) {
    //         return response()->json(['response'  => $balance['data']], 400);
    //     } else {
    //         if ((int)$balance['data'] >= (int)$request->amount) {
    //             $buyeraccept = TradeRequest::where('id', $request->id)->first();
    //             $getStatus = PToP::where('trade_registry', $buyeraccept->trade_registry)->count();
    //             if ($getStatus < 1) {
    //                 $this->holdFundAtTradeRequestAccepted(
    //                     ledgerKey: $buyeraccept->fund_reg,
    //                     amount: $buyeraccept->amount_to_receive,
    //                     walletName: $buyeraccept->wallet_name,
    //                     sellerId: $buyeraccept->owner,
    //                     itemFor: $buyeraccept->item_for,
    //                     acceptance: $acceptanceId
    //                 );
    //                 TradeRequest::where('id', $request->id)->update(['status' => 'accepted']);
    //                 $this->payload(
    //                     buyeraccept: $buyeraccept,
    //                     acceptanceId: $acceptanceId,
    //                     sessionId: $sessionId,
    //                     paymentId: $paymentId,
    //                     sessionStatus: 'open',
    //                     paymentStatus: 'void',
    //                     proofOfPayment: 'void',
    //                     reportage: 'good',
    //                     durationStatus: 'started'
    //                 )->createPeerToPeer();

    //                 return response()->json([
    //                     'response'  =>  PToP::where('trade_registry', $buyeraccept->trade_registry)->first(),
    //                     'status'    => 200
    //                 ]);
    //             }
    //         } else {
    //             return response()->json(['response'  => 'Insufficient balance'], 400);
    //         }
    //     }
    // }


    // $request->validate([
    //     'id'            => ['required'],
    //     "owner"         => ['required', 'string'],
    //     "charges_for"   => ['required'],
    //     "ratefy_fee"    => ['required'],
    //     "percentage"    => ['required'],
    // ]);

    // TradeRequest::where('id', $request->id)->update(['status' => 'accepted']);
    // $data = TradeRequest::where('id', $request->id)->first();

    // $getStatus = PToP::where('trade_registry', $data->trade_registry)->count();

    // if ($getStatus < 1) {
    //     $acceptanceId =  Str::uuid();
    //     $sessionId = Str::uuid();
    //     $paymentId = Str::uuid();

    //     $this->payload(
    //         buyeraccept: $data,
    //         acceptanceId: $acceptanceId,
    //         sessionId: $sessionId,
    //         paymentId: $paymentId,
    //         sessionStatus: 'open',
    //         paymentStatus: 'void',
    //         proofOfPayment: 'void',
    //         reportage: 'good',
    //         durationStatus: 'started'
    //     )->createPeerToPeer();

    //     return response()->json([
    //         'response'  =>  PToP::where('trade_registry', $data->trade_registry)->first(),
    //         'status'    => 200
    //     ]);
    // }


    // $request->validate([
    //     'id'            => ['required'],
    //     "owner"         => ['required', 'string'],
    // ]);

    // $trade = TradeRequest::where('id', $request->id)->first();

    // if ($trade) {
    //     TradeRequest::where('id', $request->id)->update(['status' => 'rejected']);
    //     if ($trade->fund_attached === "yes") {
    //         $this->returnBackFromRejectedTrade($trade->fund_reg);
    //         $messenger = app(MessengerController::class);
    //         $messenger->sendRejectTradeRequestNotification(
    //             owner: $trade->owner,
    //             recipient: $trade->recipient,
    //             amount: $trade->amount,
    //             itemFor: $trade->item_for,
    //             walletName: $trade->wallet_name,
    //             itemId: $trade->item_id
    //         );
    //         return response()->json([
    //             'message' =>  'success',
    //             'status'    => 200
    //         ]);
    //     } else {
    //         $messenger = app(MessengerController::class);
    //         $messenger->sendRejectTradeRequestNotification(
    //             owner: $trade->owner,
    //             recipient: $trade->recipient,
    //             amount: $trade->amount,
    //             itemFor: $trade->item_for,
    //             walletName: $trade->wallet_name,
    //             itemId: $trade->item_id
    //         );
    //         return response()->json([
    //             'message' =>  'success',
    //             'status'    => 200
    //         ]);
    //     }
    // } else {
    //     return response()->json([
    //         'status'    => 400,
    //         'title'     => 'This trade does not exists',
    //         'data'      => null,
    //         'error'     => null
    //     ], 400);
    // }

    // PToP::where('session_id', $request->session_id)->update([
    //     'session_status'   => 'closed'
    // ]);

    // $data = PToP::where('session_id', $request->session_id)->first();
    // event(new Update(
    //     acceptance: $request->acceptance,
    //     session: $request->session_id,
    //     updateState: '3'
    // ));
    // $trade = app(TradeController::class);
    // $trade->returnBackFundFromACancelledTradeRequest(reg: $data->fund_reg);
    // return response()->json([
    //     'response'   => $data,
    //     'status'    => 200
    // ]);


    // $request->validate([
    //     'id'            => ['required'],
    // ]);

    // $check = TradeRequest::where('id', $request->id)->first();
    // if ($check->status !== 'cancelled' || $check->status !== 'accepted' || $check->status !== 'rejected') {
    //     TradeRequest::where('id', $request->id)->update(['status' => 'cancelled']);
    //     $cancelled = TradeRequest::where('id', $request->id)->first();
    //     if ($cancelled->fund_attached === 'yes') {
    //         $this->returnBackFund($cancelled->fund_reg);
    //         $messenger = app(MessengerController::class);
    //         $messenger->sendCancelTradeRequestNotification(
    //             owner: $cancelled->owner,
    //             recipient: $cancelled->recipient,
    //             amount: $cancelled->amount,
    //             itemFor: $cancelled->item_for,
    //             walletName: $cancelled->wallet_name,
    //             itemId: $cancelled->item_id
    //         );


    //         return response()->json([
    //             'message'   => 'Trade cancelled, An email has been sent to the recipient',
    //             'status'    => 200
    //         ]);
    //     } else {
    //         $messenger = app(MessengerController::class);
    //         $messenger->sendCancelTradeRequestNotification(
    //             owner: $cancelled->owner,
    //             recipient: $cancelled->recipient,
    //             amount: $cancelled->amount,
    //             itemFor: $cancelled->item_for,
    //             walletName: $cancelled->wallet_name,
    //             itemId: $cancelled->item_id
    //         );

    //         return response()->json([
    //             'message'   => 'Trade cancelled, An email has been sent to the recipient',
    //             'status'    => 200
    //         ]);
    //     }
    // } else {
    //     return response()->json('ok');
    // }


    // $request->validate([
        //     'id'            => ['required'],
        // ]);

        // TradeRequest::where('id', $request->id)->update(['status' => 'cancelled']);
        // $cancelled = TradeRequest::where('id', $request->id)->first();
        // $this->returnBackFund($cancelled->fund_reg);
        // $messenger = app(MessengerController::class);
        // $messenger->sendCancelTradeRequestNotification(
        //     owner: $cancelled->owner,
        //     recipient: $cancelled->recipient,
        //     amount: $cancelled->amount,
        //     itemFor: $cancelled->item_for,
        //     walletName: $cancelled->wallet_name,
        //     itemId: $cancelled->item_id
        // );


        // return response()->json([
        //     'message'   => 'Trade cancelled, An email has been sent to the recipient',
        //     'status'    => 200
        // ]);

        // PToP::where('session_id', $request->session_id)->update([
        //     'proof_of_payment_status'   => 'accept',
        //     'payment_status'    => 'released',
        //     'session_status'   => 'closed'
        // ]);

        // event(new Update(
        //     acceptance: $request->acceptance,
        //     session: $request->session_id,
        //     updateState: '2'
        // ));
        // $data = PToP::where('session_id', $request->session_id)->first();
        // $this->acceptAndRelease($data->fund_reg);


        // $messenger = app(MessengerController::class);
        // $messenger->sendTradeCompletionSuccessNotification(
        //     owner: $data->owner_id,
        //     recipient: $data->recipient_id,
        //     amount: $data->amount,
        //     itemFor: $data->item_for,
        //     itemName: $data->item_name,
        //     itemId: $data->item_id,
        //     amountToRecieve: $data->amount_to_receive
        // );

        // return response()->json([
        //     'response'   => $data,
        //     'status'    => 200
        // ]);
}
