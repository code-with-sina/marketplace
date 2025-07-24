<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TradeLog;
use App\Otp\OneTimePassword;
use Illuminate\Http\Request;
use App\Models\AnchorBankList;
use App\Jobs\CreateAccountJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\WalletFacades\HasValidateKyc;
use App\AdminFacades\HasObjectConverter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Services\OnboardCustomerService;
use App\Services\BalanceService;
use App\Models\CustomerStatus;


class CustomersController extends Controller
{
    use HasObjectConverter, HasValidateKyc;

    public function createCustomers(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "selfieimage"   => ['required', 'mimes:png,jpg,jpeg', 'max:2048'],
            "dateOfBirth"   => ['required', 'string'],
            "bvn"           => ['required', 'string', 'max:11'],
            "idNumber"      => ['required', 'string', 'max:11'],
            "idType"        => ['required', 'string'],
            "gender"        => ['required', 'string'],
            "edit"          => ['required', 'in:true,false,1,0']
            // "edit"          => ['required', 'boolean'],
        ]);


        if ($validation->fails()) {
            return response()->json([
                'status' => ["failed", $validation->errors()]
            ]);
        } else {


            if ($request->has('selfieimage')) {

                $filename = time() . '.' . $request->selfieimage->extension();
                $request->selfieimage->move(public_path('upload/validation/'), $filename);
                $payload = [
                    "dateOfBirth"   => $request->dateOfBirth,
                    "bvn"           => $request->bvn,
                    "idNumber"      => $request->idNumber,
                    "idType"        => $request->idType,
                    "gender"        => $request->gender,
                    "expiryDate"    => $request->expiryDate
                ];


                $edit = $request->edit == "true" || $request->edit == 1 ? true : false;

                $statusResource = app(OnboardCustomerService::class, ['user' => Auth::user()])
                    ->acquireUserDataAndValidate(edit: $edit)
                    ->createMember(collections: $payload, selfieimage: 'https://p2p.ratefy.co/upload/validation/' . $filename)
                    ->validateLevelOneKyc()
                    ->monitorKycStatus()
                    ->throwStatus();

                return response()->json($statusResource, $statusResource->status);
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

            // $this->notifyStaffs(direction: 'Kyc', content: $createCustomer . 'created on >>> ', id: $createCustomer->id);

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

            $validate = $this->validateKyc($updateCustomers->customer()->first());

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
                $nameCheck = explode(" ",  strtolower($status['message']));


                // change this in life production
                // if (in_array($user->customerstatus()->first()->customer()->first()->lastName, $nameCheck)) {

                if (in_array(strtolower(trim($user->lastname)), $nameCheck) && in_array(strtolower(trim($user->firstname)), $nameCheck)) {

                    $loads = [
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

                    $payload = (object)$loads;
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
                        return response()->json(['data' => $user->load(['customerstatus', 'customerstatus.customer.counterpartyaccount'])]);
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
        ])->get(env('ANCHOR_SANDBOX') . 'payments/verify-account/' . $bankCode . '/' . $accountNumber);

        if ($response->status() == 400) {
            return ['status' => 400, 'message' => $response->object()->errors[0]->detail];
        } else {
            $data = $response->object();
            $user = User::find(auth()->user()->id);
            $accounts = $user->customerstatus()->first()->customer()->first()->counterpartyaccount()->where('bankNipCode', $data->data->attributes->bank->nipCode)->where('accountNumber', $data->data->attributes->accountNumber)->first();
            Log::info([$accounts]);
            if ($accounts !== null) {
                return ['status' => 400, 'message' => "Your account number already exist"];
            } else {

                return ['status' => 200, 'message' => $data->data->attributes->accountName];
            }
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

        if(!$wallet) {
            return response()->json(['message' => 'You have not been onboraded yet', 'status' => 400], 400);
        }

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


    public function fetchExpositonAccount()
    {
        $accountDetail = null;
        $patron = User::find(auth()->user()->id);
        $user = $patron->customerstatus()->first();
        if($user !== null) {
            $customerDetail = $user->customer()->first();
            if($customerDetail !== null) {
                $personalAccount = $customerDetail->personalaccount()->first();
                $escrowAccount = $customerDetail->escrowaccount()->first();

                $accountDetail= [
                    "Personal account detail" => $personalAccount !== null ? $personalAccount->virtualnuban()->first() : "No Personal Account created yet",
                    "Escrow account detail" => $escrowAccount !== null ? $escrowAccount->virtualnuban()->first() : "No Escrow Account created yet",
                ];
                
            }
            
        }else {
            return response()->json(['message' => "You have not been onborded yet", "status" => 404], 404);
        }

        return response()->json($accountDetail, 200);
    }

    public function getCounterPartyAccount(Request $request)
    {
        $detail = null;
        $patron = User::find(auth()->user()->id);
        $accountDetail = $patron->customerstatus()->first();
        if($accountDetail !== null) {
            $customer = $accountDetail->customer()->first();
            if($customer !== null) {
                $detail = $customer->counterpartyaccount()->get();
            }else {
                return response()->json(["message" => "You have not been onboard yet", "status" => 404], 404);
            }
            
        }else {
            return response()->json(["message" => "You have not been onboard yet", "status" => 404], 404);
        }
        return response()->json([
            'detail'    => $detail
        ]);
    }


    public function deleteCounterPartyAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'bank_account_id' => ['required']
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status' => $validation->errors()
            ]);
        } else {
            $patron = User::find(auth()->user()->id);
            $delete = $patron->customerstatus()->first();

            $deletion = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->get(env('ANCHOR_SANDBOX') . "counterparties/" . $delete->customer()->first()->counterpartyaccount()->first()->counterPartyId);


            $delete->customer()->first()->counterpartyaccount()->where('id', $request->bank_account_id)->delete();


            return response()->json([
                'status'    => $deletion->status(),
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




    public function getTransactionHistory($accountId, $customerId, $from, $to, $type, $direction)
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
        }


        $balance = app(BalanceService::class)
            ->payload(uuid: auth()->user()->uuid, amount: ((int)$request->amount + 20))
            ->getBalance()
            ->compareBalance()
            ->throwStatus();

        if ($balance->status !== 200) {
            return response()->json(['status' => 400, 'response'  => 'insufficient fund'], 400);
        }


        $check = OneTimePassword::initProcess(amount: ((int)$request->amount + 20), accountId: $request->accountId);
        return response()->json(['status' => 200, 'message' => $check], 200);
    }

    public function confirmOtp(Request $request)
    {
        $check = OneTimePassword::confirmPassword(otp: $request->otp);
        if ($check['status'] == 200) {
            $this->processWithdrawal(amount: $check['message']->amount, accountId: $check['message']->accountId);
            return response()->json(['status' => 200, 'message' => "Your withdrawal has been successfully initiated."], 200);
        } else {
            return response()->json($check);
        }
    }


    public function resendOtp(Request $request)
    {
        $check = OneTimePassword::reProcess(tally: $request->hash);
        return response()->json(['status' => 200, 'message' => $check]);
    }


    public function processWithdrawal($amount, $accountId)
    {


        $spliter = $this->splitWithdrawal(amount: $amount);
        $user = User::find(auth()->user()->id);
        $trust_id = Str::uuid();
        $reference = Str::uuid();



        $fetchCustomerAccount = $user->customerstatus()->first();
        $payload = [
            "data" => [
                "attributes" => [
                    "currency" => "NGN",
                    "amount" => ($spliter->withdrawal * 100),
                    "reason" => "withdrawal",
                    "reference" => $reference
                ],
                "relationships" => [
                    "destinationAccount" => [
                        "data" => [
                            "type" => "DepositAccount"
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


        if (in_array($transfer->status(), [200, 201, 202])) {
            $withdrawalObject = $transfer->object();
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

            Log::info([$withdrawalObject]);
            $naration = $this->createNaration(
                amount: $amount,
                reference: $reference,
                status: "pending",
                failureReason: $failureReason ?? "null",
                transferId: $withdrawalObject->data->id
            );


            $user->withdrawjournal()->create([
                'account_type'  => "Withdrawal",
                'narration'     => $naration,
                'trust_id'      => $trust_id,
                'amount'        => $amount,
                'reference'     => $withdrawalObject->data->attributes->reference,
                'reason_for_failure'        => $withdrawalObject->data->attributes->failureReason  ?? "null",
                'status' => $withdrawalObject->data->attributes->status,
                'trnx_ref'        => $withdrawalObject->data->id,
            ]);

            return response()->json($createTradeLog);
        } else {
            return response()->json(['status' => 200, 'data' => $transfer->object()]);
        }
    }



    public function splitWithdrawal($amount)
    {
        $fee = 20;
        $initialAmount =  $amount;
        $withdrawal = $initialAmount - $fee;

        return (object)[
            "withdrawal"    => $withdrawal,
            "fee"           => $fee
        ];
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



    public function buildPersonalPayload($customer)
    {

        return (object) [
            'data'  => [
                'attributes' => [
                    'productName' => 'SAVINGS',
                ],
                'relationships'  => [
                    'customer'  => [
                        'data' => [
                            'id' => $customer->customerId,
                            'type' => $customer->customerType
                        ]
                    ]
                ],
                'type' => 'DepositAccount'
            ]
        ];
    }


    public function testData()
    {
        $user = User::find(auth()->user()->id);
        $customer = $user->customerstatus()->first()->customer()->first();
        $obj = $this->buildPersonalPayload($customer);

        return response()->json($obj);
    }


    public function createNaration($amount, $reference, $status, $failureReason, $transferId)
    {
        /* This is a sample naration. we are coming to it later */


        $isHtml = true;
        return __(
            "You have successfully made a withdrawal of the sum of :amount " . ($isHtml ? "<br>" : "\n") .
                "Your transaction reference is :reference " . ($isHtml ? "<br>" : "\n") .
                "Your transaction status is :status " . ($isHtml ? "<br>" : "\n") .
                "Your transaction failure reason is :failureReason " . ($isHtml ? "<br>" : "\n") .
                "Your transaction transfer id is :transferId " . ($isHtml ? "<br>" : "\n"),
            [
                'amount' => $amount,
                'reference' => $reference,
                'status' => $status,
                'failureReason' => $failureReason,
                'transferId' => $transferId,
            ]
        );
    }


    public function fetchWithdrawalHistory()
    {
        $user = User::find(auth()->user()->id);
        $history = $user->withdrawjournal()->latest()->paginate(10);

        if (!empty($history)) {
            return response()->json(['stat' => 200, 'data' => $history]);
        } else {
            return response()->json(['status' => 400, 'message' => 'No withdrawal history']);
        }
    }





    public function adminTestCreateCounterPartyAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nipcode'       => ['required', 'string'],
            'accountnumber' => ['required', 'string'],
            'bank_id'       => ['required', 'string'],
            'bank_name'     => ['required'],
            'id'            => ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => $validation->errors()
            ]);
        } else {
            $status = $this->adminTestVerifyBankAccount($request->nipcode, $request->accountnumber, $request->id);
            if ($status['status'] == 400) {
                return response()->json(["status" => 400, "error" => $status['message']]);
            } else {

                $user = User::find($request->id);
                $nameCheck = array();
                $nameCheck = explode(" ",  strtolower($status['message']));


                // change this in life production
                // if (in_array($user->customerstatus()->first()->customer()->first()->lastName, $nameCheck)) {

                if (in_array(strtolower(trim($user->lastname)), $nameCheck) && in_array(strtolower(trim($user->firstname)), $nameCheck)) {

                    $loads = [
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

                    $payload = (object)$loads;
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
                        return response()->json(['data' => $user->load(['customerstatus', 'customerstatus.customer.counterpartyaccount'])]);
                    } else {
                        return response()->json(['data' => $response->object()]);
                    }
                } else {

                    return response()->json(["status" => 400, "error" => "Your Names do not match your account number", "name" => $nameCheck, "user" => in_array(strtolower(trim($user->firstname)), $nameCheck), "firstname" => trim($user->firstname)]);
                }
            }
        }
    }


    public function adminTestVerifyBankAccount($bankCode, $accountNumber, $id)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'payments/verify-account/' . $bankCode . '/' . $accountNumber);

        if ($response->status() == 400) {
            return ['status' => 400, 'message' => $response->object()->errors[0]->detail];
        } else {
            $data = $response->object();
            $user = User::find($id);
            $accounts = $user->customerstatus()->first()->customer()->first()->counterpartyaccount()->where('bankNipCode', $data->data->attributes->bank->nipCode)->where('accountNumber', $data->data->attributes->accountNumber)->first();
            Log::info([$accounts]);
            if ($accounts !== null) {
                return ['status' => 400, 'message' => "Your account number already exist"];
            } else {

                return ['status' => 200, 'message' => $data->data->attributes->accountName];
            }
        }
    }
}
