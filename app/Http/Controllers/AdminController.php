<?php

namespace App\Http\Controllers;

use stdClass;

use Carbon\Carbon;
use App\Models\Chat;
use App\Models\PToP;
use App\Models\User;
use App\Models\Ewallet;
use App\Models\BuyerOffer;
use App\Models\SellerOffer;
use App\Models\PaymentOption;
use App\Models\KycState;
use App\Models\Fee;
use App\Events\Update;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TradeRequest;
use App\Models\AdminActivity;
use App\Models\Administrator;
use App\Models\CustomerStatus;
use App\StaffNotifier\KycNotify;
use App\Models\StaffNotification;
use App\Services\KycCheckerService;
use App\Models\Trail as AuditTrail;
use App\Events\Chat as Dialogue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\AdminFacades\HasObjectConverter;
use App\Services\CancelTransactionService;
use App\Services\AdminPeerPaymentService;
use App\Services\SubAccountService;
use Illuminate\Support\Facades\Log;
use App\Jobs\CreateAccountJob;
use App\Services\UpdateAccountService;
use App\Services\SubaccountCreationService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\User\UsersAccountDeletionService;


class AdminController extends Controller
{

    use HasObjectConverter;

    public function allUsers()
    {
        $user = User::orderBy('id', 'DESC')->with(['authorization', 'activity', 'tag'])->paginate(10);
        return response()->json($user);
    }

    public function usersType()
    {
        $user = User::all();
        return response()->json([
            'data'      => $user->load('authorization')
        ], 200);
    }

    public function fetchTradeRequest()
    {

        $trades = TradeRequest::whereBetween('created_at', [now()->subDays(5), now()])
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->with(['charge', 'owner', 'recipient'])
            ->latest()
            ->paginate(100);


        return response()->json($trades);
    }


    public function fetchPeerToPeer()
    {

        $ptop = PToP::where('created_at', '<',  now())
            ->orderBy('id', 'DESC')
            ->with(['ownerDetail', 'recipientDetail', 'trade.charge', 'trade']) // Load trade
            ->paginate(100);

        return response()->json($ptop);
    }


    public function getEwallet()
    {
        $data = Ewallet::all();
        return response()->json($data->load('paymentoption', 'paymentoption.requirement'));
    }

    public function singleUser(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        return response()->json([
            'data'  => $user->load(['authorization', 'activity', 'tag'])
        ], 200);
    }

    public function searchSingleUser(Request $request)
    {
        if ($request->determinant  === "email") {
            $user = User::where('email', $request->search)->first();
            return response()->json([
                'data'  => $user->load(['authorization', 'activity', 'tag'])
            ], 200);
        } else {
            $user = User::where('username', $request->search)->first();
            return response()->json([
                'data'  => $user->load(['authorization', 'activity', 'tag'])
            ], 200);
        }
    }


    public function getUserWithoutDetail($uuid)
    {
        $user = User::where('uuid', $uuid)->first();
        return response()->json($user, 200);
    }

    public function emailVerified(Request $request)
    {
        $emailVerified = User::where('uuid', $request->uuid)->first()->authorization()->where('priviledge', 'activated')->count();
        return response()->json([
            'count'      => $emailVerified,
        ], 200);
    }

    public function kycVerified(Request $request)
    {
        $kycVerification = User::where('uuid', $request->uuid)->first()->authorization()->where('status', 'approved')->count();
        return response()->json([
            'count'      => $kycVerification,
        ], 200);
    }

    public function workVerified(Request $request)
    {
        $workVerification = User::where('uuid', $request->uuid)->first()->authorization()->first();
        return response()->json([
            'status'      => $workVerification->type,
        ], 200);
    }




    public function adminBlockUser(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        if ($user !== null) {
            $user->authorization()->update([
                'priviledge' => 'blocked'
            ]);
            return response()->json(200);
        } else {
            return response()->json('This users is never with us. is there an hanky-panky here?', 400);
        }
    }


    public function adminVerifyUser(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        if ($user !== null) {
            $user->authorization()->update([
                'email' => 'verified'
            ]);
            return response()->json(200);
        } else {
            return response()->json('This users is never with us. is there an hanky-panky here?', 400);
        }
    }

    public function adminActivateUser(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        if ($user !== null) {
            $user->authorization()->update([
                'priviledge' => 'activated'
            ]);
            return response()->json(200);
        } else {
            return response()->json('This users is never with us. is there an hanky-panky here?', 400);
        }
    }


    public function userDetails(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        return response()->json($user->load(['authorization', 'activity', 'tag', 'miniprofile', 'buyeroffer.ewallet', 'selleroffer.ewallet', 'profile', 'profile.freelance', 'profile.shoppermigrant', 'customerstatus.customer']));
    }



    public function activateUser(Request $request)
    {
        $upsert = User::where('uuid', $request->uuid)->first();
        $upsert->authorization()->update([
            'priviledge' => 'activated'
        ]);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }


    public function adminTrack()
    {
        $data = AuditTrail::where('user_id', null)->orderBy('id', 'DESC')->paginate(10);
        return response()->json($data);
    }

    // public function logTrack()
    // {
    //     $data = ErrorTrace::where('user_id', null)->orderBy('id', 'DESC')->paginate(10);
    //     return response()->json($data);
    // }

    public function userTrack($uuid)
    {
        $data = User::where('uuid', $uuid)->first();
        return response()->json($data->errortrace()->latest()->take(10)->get());
    }

    public function userTrail($uuid)
    {
        $data = User::where('uuid', $uuid)->first();
        return response()->json($data->trail()->latest()->take(10)->get());
    }

    public function userTrailByDate($uuid, $date)
    {
        $search = User::where('uuid', $uuid)->first()->trail()->where('created_at', $date)->get();

        return response()->json($search);
    }

    public function staffNotification(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        $kycNotification = new KycNotify();
        $kycNotification->mailDispatch(adminStaffs: $request->staffs, direction: $request->direction, content: $request->content, fromUser: $user->email, id: $request->id);
    }

    /* This is for controller injection */
    public function staffsNotification($direction, $content, $uuid, $id, $groupStaff)
    {
        $user = User::where('uuid', $uuid)->first();
        $kycNotification = new KycNotify();
        $kycNotification->mailDispatch(adminStaffs: $groupStaff, direction: $direction, content: $content, fromUser: $user->email,  id: $id);
    }

    public function staffGetNotification()
    {
        $data = StaffNotification::where('readline', 'unread')->latest()->paginate(10);
        return response()->json($data);
    }

    public function getOfferNotification()
    {

        $perPage = 500;
        $page = request()->get('page', 1);

        $buy = BuyerOffer::where('approval', 'pending')
            ->with(['user', 'ewallet', 'paymentoption'])
            ->latest() // Sorting here instead of after merge
            ->get()
            ->map(fn($item) => array_merge($item->toArray(), ['identification' => 'buy']));

        $sell = SellerOffer::where('approval', 'pending')
            ->with(['user', 'ewallet', 'paymentoption'])
            ->latest()
            ->get()
            ->map(fn($item) => array_merge($item->toArray(), ['identification' => 'sell']));

        $merged = collect($buy)->merge($sell)->sortByDesc('created_at'); // Keep sorting here only if necessary

        $sliced = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $sliced,
            $merged->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json($paginated);
    }


    public function approveUserOffer(Request $request)
    {
        if ($request->identity === 'sell') {
            SellerOffer::where('id', $request->id)->update(['approval' => 'approved']);
        } else {
            BuyerOffer::where('id', $request->id)->update(['approval' => 'approved']);
        }

        return response()->json('ok');
    }


    public function rejectUserOffer(Request $request)
    {
        if ($request->identity === 'sell') {
            SellerOffer::where('id', $request->id)->update(['approval' => 'rejected']);
        } else {
            BuyerOffer::where('id', $request->id)->update(['approval' => 'rejected']);
        }

        return response()->json('ok');
    }


    public function createAdmin(Request $request)
    {
        $customer = Administrator::where('uuid', $request->uuid)->first();
        if (empty($customer) === true) {
            $data = $this->createMember($request->collect());
            return response()->json($data);
        } else {
            return response()->json($customer);
        }
    }


    public function createMember($object)
    {
        $payload = [
            'data' => [
                'attributes'    => [
                    'fullName'  => [
                        'firstName' => $object['firstname'],
                        'lastName'  => $object['lastname']
                    ],
                    'address'       => [
                        'country'       => 'NG',
                        'state'         => $object['state'],
                        'city'          => $object['city'],
                        'postalCode'    => $object['postalcode'],
                        'addressLine_1'     => $object['address']
                    ],
                    'identificationLevel2'  => [
                        'dateOfBirth'   => $object['dateOfBirth'],
                        'selfieImage'   => $object['selfieimage'],
                        'gender'        => $object['gender'],
                        'bvn'           => $object['bvn']
                    ],
                    'identificationLevel3'  => [
                        'idNumber'      => $object['idNumber'],
                        'expiryDate'    => $object['expiryDate'],
                        'idType'        => $object['idType'],
                    ],
                    'email'         => $object['email'],
                    'phoneNumber'   => $object['phonenumber']
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

            $admin = Administrator::create([
                'uuid'          => $object['uuid'],
                'adminId'       => $customers->data->id,
                'adminType'     => $customers->data->type,
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
                'status'        => $customers->data->attributes->verification->status,
                'registered'    => Hash::make($customers->data->attributes->status . '-' . uniqid())
            ]);
            $validate = $this->validateKyc($admin);

            if ($validate === 200 || $validate === 200) {
                $data = Administrator::where('uuid', $object['uuid'])->first();
                return $data;
            }
        } else {
            return [$customerObject->status(), $customerObject->object()];
        }
    }


    public function validateKyc($customers)
    {

        $tierOne = [
            'data'  => [
                'type'  => 'Verification',
                'attributes' => [
                    'level' => 'TIER_2',
                    'level2'    => [
                        'bvn'           => $customers->bvn,
                        'selfie'        => $customers->selfie,
                        'dateOfBirth'   => $customers->dateOfBirth,
                        'gender'        => $customers->gender
                    ]
                ]
            ]
        ];


        $tiertwo = [
            'data'  => [
                'attributes' => [
                    'level' => 'TIER_3',
                    'level3'    => [
                        'idNumber'      => $customers->idNumber,
                        'idType'        => $customers->idType,
                    ],
                ],
                'type'  => 'Verification',
            ]
        ];


        $firstValidate =    $this->ToObject($tierOne);
        $secondValidate =   $this->ToObject($tiertwo);

        $levelOne = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . 'customers/' . $customers->adminId . '/verification/individual', $firstValidate);

        if ($levelOne->status() === 200) {
            Administrator::where('uuid', $customers->uuid)->update(['status' => 'semi-verified']);
            $levelTwo = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->post(env('ANCHOR_SANDBOX') . 'customers/' . $customers->adminId . '/verification/individual', $secondValidate);
            if ($levelTwo->status() === 200) {
                Administrator::where('uuid', $customers->uuid)->update(['status' => 'fully-verified']);
                return  $levelTwo->status();
            }
        }
    }


    public function createAdminAccount(Request $request)
    {
        $checkRoot = Administrator::where('uuid', $request->uuid)->first();
        $payload = [
            'data'  => [
                'attributes' => [
                    'productName' => 'SAVINGS',
                ],
                'relationships'  => [
                    'customer'  => [
                        'data' => [
                            'id' => $checkRoot->adminId,
                            'type' => $checkRoot->adminType
                        ]
                    ]

                ],
                'type' => 'DepositAccount'
            ]
        ];
        $adminPayload =  $this->ToObject($payload);
        $adminDeposit = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->post(env('ANCHOR_SANDBOX') . 'accounts', $adminPayload);



        if ($adminDeposit->status() === 200 || $adminDeposit->status() === 202) {
            $admin = $adminDeposit->object();
            $checkRoot->adminaccount()->create([
                'botId'      => $admin->data->id,
                'botType'    => $admin->data->type
            ]);

            $fetchadminAccount = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->get(env('ANCHOR_SANDBOX') . "accounts/" . $checkRoot->adminaccount()->first()->botId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");

            if ($fetchadminAccount->status() === 200 || $fetchadminAccount->status() === 202) {
                $accounts = $fetchadminAccount->object();
                $checkRoot->adminaccount()->update([
                    'botId'      => $accounts->data->id,
                    'botType'    => $accounts->data->type,
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
                    // 'availableBalance'      =>  $accounts->data->attributes->availableBalance, 
                    // 'pendingBalance'        =>  $accounts->data->attributes->pendingBalance, 
                    // 'ledgerBalance'     =>  $accounts->data->attributes->ledgerBalance, 
                    'virtualNubans_id'      =>  $accounts->data->relationships->virtualNubans->data[0]->id,
                    'virtualNubans_type'        =>  $accounts->data->relationships->virtualNubans->data[0]->type,
                ]);

                return response()->json($checkRoot->load(['adminaccount']));
            }
        }
    }


    public function getUserImage(Request $request)
    {
        $data = CustomerStatus::where('uuid', $request->uuid)->first();
        return response()->json($data->customer()->first());
    }


    public function adminCompleteTransaction(Request $request)
    {
        $data = $this->getTrade(session: $request->session);
        if ($data['status'] === 200) {

            $collections = [
                'uuid'                      =>  $request->uuid,
                'email'                     =>  $request->email,
                'fullname'                  =>  $request->fullname,
                'activity_performed'        =>  'The admin staff by the email above performed this action ' . __FUNCTION__ . ' :: complete transaction of ' . json_encode($data['message']) . '.',
                'amount'                    =>  $data['message']['amount_to_receive'],
                'buyer'                     =>  $data['message'],
                'buyer'                     =>  $data['message']['owner'] == 'buyer' ? $data['message']['owner_id'] :  $data['message']['recipient_id'],
                'seller'                    =>  $data['message']['owner'] == 'seller' ? $data['message']['owner_id'] :  $data['message']['recipient_id'],
                'reg'                       =>  $data['message']['fund_reg'],
                'trnx_ref'                  =>  null,
                'session_acceptance_id'     =>  $data['message']['session_id'] . ' ' . $data['message']['acceptance_id'],

            ];
            $this->regitserAdminActivity(collections: $collections);
            $this->completeTrade(session: $request->session);
            return response()->json('ok');
        }
    }


    public function adminCancelTransaction(Request $request)
    {
        $data = $this->getTrade(session: $request->session);

        if ($data['status'] === 200) {
            $collections = [
                'uuid'                      => $request->uuid,
                'email'                     => $request->email,
                'fullname'                  => $request->fullname,
                'activity_performed'        => 'The admin staff by the email above performed this action ' . __FUNCTION__ . ' :: cancel transaction of ' . json_encode($data['message']) . '.',
                'amount'                    => $data['message']['amount_to_receive'],
                'buyer'                     =>  $data['message'],
                'buyer'                     =>  $data['message']['owner'] == 'buyer' ? $data['message']['owner_id'] :  $data['message']['recipient_id'],
                'seller'                    =>  $data['message']['owner'] == 'seller' ? $data['message']['owner_id'] :  $data['message']['recipient_id'],
                'reg'                       =>  $data['message']['fund_reg'],
                'trnx_ref'                  =>  null,
                'session_acceptance_id'     =>  $data['message']['session_id'] . ' ' . $data['message']['acceptance_id'],

            ];

            $cancel = [
                'acceptance'    => $data['message']['acceptance_id'],
                'session_id'    => $data['message']['session_id']
            ];
            $this->regitserAdminActivity(collections: $collections);
            $this->cancelSession(collections: $cancel);
            return response()->json('ok');
        }
    }

    public function getTrade($session)
    {
        $obj = PToP::where('session_id', $session)->first();

        if (!empty($obj)) {
            return  ['status' => 200, 'message' => $obj];
        } else {
            return  ['status' => 400, 'message' => 'something is not right or data missing'];
        }
    }

    public function completeTrade($session)
    {
        PToP::where('session_id', $session)->update([
            'session_status'    => 'closed',
            'duration_status'   => 'expired'
        ]);
        $data = PToP::where('session_id', $session)->first();

        event(new Update(
            acceptance: $data->acceptance,
            session: $session,
            updateState: '4'
        ));
        return response()->json([
            'p2p'       => $data,
            'status'    => 200
        ]);
    }


    public function cancelTrade($session)
    {
        PToP::where('session_id', $session)->update([
            'session_status'    => 'closed',
            'status'   => 'cancelled'
        ]);
    }


    public function regitserAdminActivity($collections)
    {
        AdminActivity::create([
            'uuid'                  => $collections['uuid'],
            'email'                 => $collections['email'],
            'fullname'              => $collections['fullname'],
            'activity_performed'    => $collections['activity_performed'],
            'amount'                => $collections['amount'],
            'buyer'                 => json_encode($collections['buyer']),
            'seller'                => json_encode($collections['seller']),
            'reg'                   => $collections['reg'],
            'trnx_ref'              => $collections['trnx_ref'],
            'session_acceptance_id' => $collections['session_acceptance_id'],
        ]);
    }


    public function getFee()
    {
        $fee = Fee::lastest()->first();
        return response()->json($fee);
    }


    public function getExternalKycApprovalStatus(Request $request)
    {
        $data = KycState::where('uuid', $request->uuid)->first();

        if ($data === null) {
            return response()->json('kyc not yet submited', 400);
        } else {
            return response()->json($data->status, 200);
        }
    }

    public function approveExternalKycStatus(Request $request)
    {
        $data = KycState::where('uuid', $request->uuid)->first();

        if ($data === null) {
            return response()->json('kyc not yet submited');
        } else {
            KycState::where('uuid', $request->uuid)->update([
                'status'    => 'approve'
            ]);
            return response()->json('external kyc successfull updated');
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
                'activity_performed'        => 'The admin staff by the email above performed this action ' . __FUNCTION__ . ' :: disburse transaction of ' .  $data['message'] . '.',
                'amount'                    => $data['message']['amount_to_receive'],
                'buyer'                     =>  $data['message']['buyer'],
                'seller'                    =>  $data['message']['reg'],
                'reg'                       =>  $data['message']['trnx_ref'],
                'trnx_ref'                  =>  $data['message']['amount_to_receive'],
                'session_acceptance_id'     =>  $data['message']['session_id'] . ' ' . $data['message']['acceptance_id'],

            ];

            $accept = [
                'acceptance'    => $data['message']['acceptance_id'],
                'session_id'    =>  $data['message']['session_id']
            ];

            $this->regitserAdminActivity(collections: $collections);
            $this->acceptPayment(collections: $accept);            // $tradeLog = TradeLog::where('reg', $data['message']->reg)->first();
            // $expend = $this->charge(amount: $tradeLog->amount);
            // $this->sendToSeller(amount: $expend['seller'], ledgerDetails: $tradeLog);
            // Sleep::for(5)->second();
            // $this->sendToAdmin(amount: $expend['admin'], ledgerDetails: $tradeLog);
        }
    }


    public function charge($amount)
    {
        $fee = Fee::latest()->first();
        $amount = $amount;
        $percentage = $fee->percentage;
        $deduction = ($percentage / 100) * $amount;
        $finalAmount = $amount - $deduction;
        return ['seller' => $finalAmount, 'admin' => $deduction];
    }


    public function sendToSeller($amount, $ledgerDetails)
    {
        $fetchSellerAccount = CustomerStatus::where('uuid', $ledgerDetails->seller_uuid)->first();
        $fetchBuyerAccount = CustomerStatus::where('uuid', $ledgerDetails->buyer_uuid)->first();
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
        $fetchBuyerAccount = CustomerStatus::where('uuid', $ledgerDetails->buyer_uuid)->first();
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


    /* 
    This code downward is from the admin activity controller 
     */

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





    public function fecthAllTransaction($accountId)
    {
        $pageNumber = 1;
        $size = 10;
        $data = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'transactions?accountId=' . $accountId . '&page=' . $pageNumber . '&size=' . $size . '&sort=-');

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
            ])->get(env('ANCHOR_SANDBOX') . "transactions?accountId=" . $accountId . "&customerId=" . $customerId . "&page=" . $pageNumber . "&size=" . $size . "&include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer")->throw()->json();
    }




    public function controllAction($uuid)
    {
        $data = CustomerStatus::where('uuid', $uuid)->first();
        $in = $this->fecthAllTransaction($data->customer()->first()->personalaccount()->first()->personalId);
        return response()->json($in);
    }


    public function controllNextAction($uuid, $pageNumber)
    {
        $data = CustomerStatus::where('uuid', $uuid)->first();
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


    /* This is code is from offerbased */

    public function createEwallet(Request $request)
    {

        $request->validate([
            'ewallet_name'  => 'required|unique:ewallets,ewallet_name'
        ]);

        $unique = Ewallet::where('ewallet_name', $request->ewallet_name)->first();

        if ($unique == null) {
            $fileName = time() . '.' . $request->photo->extension();
            $request->photo->storeAs('public/images/ewallets', $fileName);

            $data =  Ewallet::create([
                'uuid'          => Str::uuid(),
                'status'        => 'active',
                'ewallet_name'  => $request->ewallet_name,
                'currency'      => 'USD',
                'image_url'     =>  $fileName
            ]);
            return response()->json(['data' => $data], 200);
        } else {
            return response()->json(['data' => 'Taken'], 404);
        }
    }

    public function fetchEwallet()
    {
        $data = Ewallet::all();
        return response()->json(['data' => $data->load('paymentoption', 'paymentoption.requirement')], 200);
    }

    public function pauseEwallet(Request $request)
    {
        Ewallet::where('uuid', $request->uuid)->update([
            'status'    => 'paused'
        ]);

        return response()->json(['data' => 'successfully updated'], 200);
    }


    public function activateEwallet(Request $request)
    {
        Ewallet::where('uuid', $request->uuid)->update([
            'status'    => 'active'
        ]);

        return response()->json(['data' => 'successfully updated'], 200);
    }


    public function createEwalletOption(Request $request)
    {
        $request->validate([
            'option'   => 'required|string'
        ]);
        $createEwalletOption = Ewallet::where('uuid', $request->uuid)->first();
        $createEwalletOption->paymentoption()->create([
            'option'   => $request->option,
            'status'        => 'active'
        ]);

        return response()->json([
            'data'  => $createEwalletOption->load('paymentoption', 'paymentoption.requirement')
        ], 200);
    }

    public function fetchEwalletOption(Request $request)
    {
        $createEwalletOption = Ewallet::where('uuid', $request->uuid)->first();
        return response()->json([
            'data'  => $createEwalletOption->load('paymentoption')
        ], 200);
    }

    public function createEwalletOptionRequirement(Request $request)
    {
        $request->validate([
            'requirement'   => 'required|unique:requirements,requirement'
        ]);
        $createEwalletOptionRequirement = Ewallet::where('uuid', $request->uuid)->first();
        $createEwalletOptionRequirement->paymentoption()->where('option', $request->option)->first()->requirement()->create([
            'requirement'   => $request->requirement,
            'status'        => 'active'
        ]);

        return response()->json([
            'data'  => $createEwalletOptionRequirement->load('paymentoption', 'paymentoption.requirement')
        ], 200);
    }


    public function createRequirement(Request $request)
    {
        $create = PaymentOption::where('id', $request->id)->first();
        $create->requirement()->create([
            'requirement'   => $request->requirement,
            'status'        => $request->status
        ]);

        return response()->json($create);
    }


    public function fetchRequirement(Request $request)
    {
        $fetch = PaymentOption::where('id', $request->id)->first();

        return response()->json($fetch->requirement()->get());
    }


    public function getP2PData()
    {
        $p2p = PToP::latest()->latest()->paginate(10);
        return response()->json($p2p);
    }


    public function getPendingP2PData()
    {
        $p2p = PToP::where('payment_status', 'pending')->latest()->paginate(10);
        return response()->json($p2p);
    }

    public function getProcessingP2PData()
    {
        $p2p = PToP::where('session_status', 'open')->latest()->paginate(10);
        return response()->json($p2p);
    }

    public function getCompleteP2PData()
    {
        $p2p = PToP::where('proof_of_payment_status', 'accept')->where('session_status', 'closed')->latest()->paginate(10);
        return response()->json($p2p);
    }

    public function getCanceledP2PData()
    {
        $p2p = PToP::where('session_status', 'closed')->latest()->paginate(10);
        return response()->json($p2p);
    }

    public function getDisputedP2PData()
    {
        $p2p = PToP::where('reportage', 'open_ticket')->latest()->paginate(10);
        return response()->json($p2p);
    }




    public function getFullORderDetails($session)
    {

        $peertopeer = PToP::where('session_id', $session)
            ->with(['ownerDetail', 'recipientDetail', 'trade.charge', 'trade'])->first();
        if ($peertopeer) {

            if ($peertopeer->item_for === 'sell') {
                $offer = $peertopeer->buyerOffer()->with(['ewallet', 'paymentoption'])->first();
            } else {
                $offer = $peertopeer->sellerOffer()->with(['ewallet', 'paymentoption'])->first();
            }

            return response()->json([
                'Peer'  => $peertopeer->setRelation('offer', $offer)
            ]);
        }
    }


    public function getChat(Request $request)
    {

        $chat = Chat::where('session', $request->session)->where('acceptance', $request->acceptance)->with(['sender', 'receiver'])->get();
        return response()->json($chat);
    }

    public function sendChat(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'acceptance' => 'required|string',
            'session' => 'required|string',
            'sender' => 'nullable|string',
            'receiver' => 'nullable|string',
            'message' => 'nullable|string',
            'assets' => 'nullable|string',
            'contentType' => 'nullable|string',
        ]);

        // If validation fails, return a response with the errors
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }


        broadcast(
            new Dialogue(
                acceptance: $request->acceptance,
                session: $request->session,
                sender: $request->sender,
                receiver: $request->receiver,
                admin: $request->admin,
                message: $request->message ?? null,
                filename: $request->assets ?? null,
                contentType: $request->contentType
            )
        )->toOthers();
    }

    public function acceptPayment($collections)
    {
        $response = app(AdminPeerPaymentService::class)
            ->validate($collections)
            ->allocate()
            ->validateIfCancellationOccured()
            ->validateIfTransactionExist()
            ->chargeFee()
            ->makePayment()
            ->updateTransaction()
            ->sendPaymentNotification()
            ->broadcastUpdate()
            ->throwState();

        return response()->json($response, $response->status);
    }


    public function cancelSession($collections)
    {
        $response = app(CancelTransactionService::class)
            ->validate($collections)
            ->validateTransactionExists()
            ->validateIfPaymentOccured()
            ->validateIfReversalOccurred()
            ->reFund()
            ->cancelCurrentTransaction()
            ->broadcastUpdate()
            ->throwStatus();



        return response()->json($response, $response->status);
    }

    public function createSubAccountforCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        app(SubAccountService::class)->validateUser($request->uuid)
            ->validateUserKyc()
            ->processEscrow()
            ->processPersonal()
            ->createSubAccount()
            ->throwStatus();

        // CreateAccountJob::dispatch($request->uuid);
    }


    public function reTriggerKycWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        app(KycCheckerService::class)
            ->getUuid($request->uuid)
            ->checkStatus()
            ->OnboardCustomerAgain();
    }


    public function updateUserAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = app(UpdateAccountService::class)
            ->getUser(uuid: $request->uuid)
            ->validateUserHasPersonalAccount()
            ->validateUserHasEscrowAccount()
            // ->fetchAccount()
            ->setState()
            ->updateAccount();

        return response()->json($response);
    }


    public function updateUsersfromExpress()
    {
        $json = file_get_contents(resource_path('data/users.json'));
        $users = json_decode($json, true);
        $this->insertUserData($users);
    }

    public function insertUserData($usersJson)
    {
        foreach ($usersJson as $user) {
            $nameParts = explode(' ', trim($user['name']), 2);

            User::updateOrCreate(['uuid' => $user['uuid']], [
                'email' => $user['email'],
                'firstname' => $nameParts[0] ?? null,
                'lastname' => $nameParts[1] ?? null,
                'mobile' => $user['mobile_number'],
                'username' => $user['username'],
                'password' => $user['password'], // already hashed
                'email_verified_at' => $user['email_verified_at'],
                'exp_id' => $user['id'],
                'remember_token' => $user['remember_token'],
            ]);
        }
    }


    public function updateAuthorization() {}



    public function createCounterPartyAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nipcode'       => ['required', 'string'],
            'accountnumber' => ['required', 'string'],
            'bank_id'       => ['required', 'string'],
            'bank_name'     => ['required'],
            'uuid'          => ['required']
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => $validation->errors()
            ]);
        } else {


            $user = User::where('uuid', $request->uuid)->first();
            $status = $this->verifyBankAccount($request->nipcode, $request->accountnumber, $request->uuid);
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
        }
    }



    public function verifyBankAccount($bankCode, $accountNumber, $uuid)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-anchor-key'  => env('ANCHOR_KEY')
        ])->get(env('ANCHOR_SANDBOX') . 'payments/verify-account/' . $bankCode . '/' . $accountNumber);

        if ($response->status() == 400) {
            return ['status' => 400, 'message' => $response->object()->errors[0]->detail];
        } else {
            $data = $response->object();
            $user = User::where('uuid', $uuid)->first();
            $accounts = $user->customerstatus()->first()->customer()->first()->counterpartyaccount()->where('bankNipCode', $data->data->attributes->bank->nipCode)->where('accountNumber', $data->data->attributes->accountNumber)->first();
            Log::info([$accounts]);
            if ($accounts !== null) {
                return ['status' => 400, 'message' => "Your account number already exist"];
            } else {

                return ['status' => 200, 'message' => $data->data->attributes->accountName];
            }
        }
    }


    public function allUsersForMailing()
    {
        $user = User::select([
            'users.firstname',
            'users.lastname',
            'users.email'
        ])
            ->join('authorizations', 'authorizations.user_id', '=', 'users.id')
            ->where('authorizations.priviledge', 'activated')
            ->where('authorizations.email', 'verified')
            ->orderBy('users.id', 'DESC')
            ->get();
        return response()->json($user);
    }

    public function createSubAccounts(Request $request, SubaccountCreationService $subaccountCreationService)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $subaccountCreationService->handle($request->email);

            return response()->json([
                'message' => 'Subaccounts processed successfully',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }


    public function getUsers()
    {
        $start = Carbon::create(2025, 7, 1);
        $end   = now();
        $users = User::whereBetween('created_at', [$start, $end])->get();
        $usersData = $users->map(function ($user) {
            return (object)[
                'email' => $user->email,
                'firstName' => $user->firstname,
                'lastName' => $user->lastname
            ];
        });

        return response()->json($usersData, 200);
    }



    public function getUsersTotal()
    {
        $start = Carbon::create(2026, 2, 14);
        $end   = now();
        $users = User::whereBetween('created_at', [$start, $end])->get();
        // $usersData = $users->map(function ($user) {
        //     return (object)[
        //         'email' => $user->email,
        //         'firstName' => $user->firstname,
        //         'lastName' => $user->lastname
        //     ];
        // });

        return response()->json($users->load(['kycdetail', 'customerstatus', 'customerstatus.customer', 'customerstatus.customer.escrowaccount', 'customerstatus.customer.personalaccount', 'customerstatus.customer.personalaccount.virtualnuban', 'customerstatus.customer.escrowaccount.virtualnuban']), 200);
    }


    public function getUsersByCount(Request $request)
    {
        $validated = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $users = User::latest()->limit((int) $validated['number'])->get();
        $usersData = $users->map(function ($user) {
            return (object)[
                'email' => $user->email,
                'firstName' => $user->firstname,
                'lastName' => $user->lastname
            ];
        });

        return response()->json($usersData, 200);
    }


    public function getAndDeleteOnboardedUser(Request $request, UsersAccountDeletionService $deletionService)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        $result = $deletionService->execute($validated['email']);

        return response()->json($result, $result['status'] ? 200 : 400);
    }
}
