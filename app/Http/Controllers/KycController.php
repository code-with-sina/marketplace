<?php

namespace App\Http\Controllers;



use Carbon\Carbon;
use App\Models\User;
use App\Models\Work;
use App\Models\Profile;
use App\Models\Customer;
use App\Models\KycDetail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Authorization;
use App\Models\EscrowAccount;
use App\Models\ShopperMigrant;
use App\Models\CustomerStatus;
use App\Models\PersonalAccount;
use Illuminate\Validation\Rule;
use App\Services\DojahKycService;
use Illuminate\Support\Facades\Log;
use App\Services\KycCheckerService;
use App\Services\SubAccountService;
use Illuminate\Support\Facades\Hash;
use App\Services\UpdateAccountService;
use App\Services\OnboardingLogService;
use Illuminate\Support\Facades\Validator;
use App\Services\MetaPixelConversionService;
use App\Services\OnboardCustomerTestService;
use App\Services\AddVirtualNubanService;
use App\Jobs\WalletStatusNotifier;  
use App\Services\WalletStatusObserverAndNotifier;


class KycController extends Controller
{
    public function kycGate(Request $request) {
        $probeKyc = @KycDetail::where('user_id', auth()->user()->id)->first();
        if($probeKyc !== null ) {

            if($probeKyc->selfie_verification_status !== null && $probeKyc->selfie_verification_status == true) {
                 return response()->json(["message"=> "User already verified", "status" => 200], 200);
            }else {
                $validator = Validator::make([
                    'bvn' => $request->bvn,
                    'selfie_image' => $request->selfieimage,
                    'street' => $request->street,
                    'city' => $request->city,
                    'state' => $this->splitState($request->state),
                    'house_number' => $request->house_number,
                    'zip_code' => $request->zip_code,
                    'nin' => $request->nin,
                ], [
                    'bvn' => ['required', 'digits:11'],
                    'selfie_image' => ['required', function ($attribute, $value, $fail) {
                        if (!is_string($value) || !str_starts_with($value, '/9')) {
                            $fail('The selfie image must be a valid base64 JPEG truncated buffer.');
                        }
                    }],
                    'street' => ['required', 'string'],
                    'city' => ['required', 'string'],
                    'state' => ['required', 'string'],
                    'house_number' => ['required', 'string'],
                    'zip_code' => ['nullable', 'string', 'max:6'],
                    'nin' => ['required', 'digits:11']
                ]);

                if ($validator->fails()) {
                    return response()->json(["message" => $validator->errors()->first(), "status" => 422], 422);
                }


                $processKYC = app(DojahKycService::class)
                ->primitiveState(editState: true)
                ->getUserDetail(auth()->user()->uuid)
                ->getValidationDetails(
                    bvn: $request->bvn,
                    selfieImage: $request->selfieimage,
                    street: $request->street,
                    city: $request->city,
                    state: $this->splitState($request->state),
                    house_number: $request->house_number,
                    zip_code: $request->zip_code,
                    nin: $request->nin
                )
                ->savePrimitiveData()
                ->validateUserViaDojahKyc()
                ->throwResponse();

                return response()->json($processKYC, $processKYC->status);
            }
           
        }else {
            
            $validator = Validator::make([
                'bvn' => $request->bvn,
                'selfie_image' => $request->selfieimage,
                'street' => $request->street,
                'city' => $request->city,
                'state' => $this->splitState($request->state),
                'house_number' => $request->house_number,
                'zip_code' => $request->zip_code,
                'nin' => $request->nin,
            ], [
                'bvn' => ['required', 'digits:11', 'unique:kyc_details,bvn'],
                'selfie_image' => ['required', function ($attribute, $value, $fail) {
                    if (!is_string($value) || !str_starts_with($value, '/9')) {
                        $fail('The selfie image must be a valid base64 JPEG truncated buffer.');
                    }
                }],
                'street' => ['required', 'string'],
                'city' => ['required', 'string'],
                'state' => ['required', 'string'],
                'house_number' => ['required', 'string'],
                'zip_code' => ['nullable', 'string', 'max:6'],
                'nin' => ['required', 'digits:11']
            ]);
            
            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 422);
            }


            $processKYC = app(DojahKycService::class)
            ->primitiveState(editState: false)
            ->getUserDetail(auth()->user()->uuid)
            ->getValidationDetails(
                bvn: $request->bvn,
                selfieImage: $request->selfieimage,
                street: $request->street,
                city: $request->city,
                state: $this->splitState($request->state),
                house_number: $request->house_number,
                zip_code: $request->zip_code,
                nin: $request->nin
            )
            ->savePrimitiveData()
            ->validateUserViaDojahKyc()
            ->throwResponse();

            return response()->json($processKYC, $processKYC->status);

        }
        
    }



    public function workDeclarationAndWalletOnboarding(Request $request){
        $validator = Validator::make($request->all(), [
            'profession' => ['required', 'string'],
            'group' => ['nullable', 'array'],
            'group.*' => ['in:buyer,seller'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $makeGroup = in_array('buyer', $request->group) && in_array('seller', $request->group) ? 'both' : $request->group[0] ?? null;

        $user = User::find(auth()->user()->id);
        
        if($user->kycdetail()->first()->selfie_verification_status === true || $user->kycdetail()->first()->selfie_verification_status === "true"){

            $works = $user->works()->first();
                if(!$works){
                    $user->works()->create([
                        'profession' => $request->profession,
                        'group' => $makeGroup,
                    ]);
                }else {
                    $user->works()->update([
                        'profession' => $request->profession,
                        'group' => $makeGroup,
                    ]);
                }
           
            $user->authorization()->update([
                'type'       => 'both',
            ]);

            if($user->works()->first() !== null) {
                $this->memberCreate(data: $user->kycdetail()->first());
                return response()->json(["message" => "we are processing your wallet at the moment", "status" => 200], 200);
            }

            
        }else {
            return response()->json([
                'message' => 'First, complete your identity verification above'
            ], 422);
            
        }
    }


    public function fetchProfile() {
        $user = User::where('uuid', auth()->user()->uuid)->first();
        $data = $user->kycdetail()->select([
            'house_number',
            'street',
            'city',
            'state',
            'country',
            'gender',
            'zip_code',
            'selfie_image_initiated',
            'nin'
        ])->first();
        if(!$data) {
            return response()->json("sorry, you have no profile yet");
        }

        return response()->json($data);
    }


    public function fetchDeclaration() {
        $user = User::where('uuid', auth()->user()->uuid)->first();
        $data = $user->works()->first();

        if(!$data) {
            return response()->json("sorry, you have not declare what you do yet");
        }
        return response()->json($data);
    }


    public function memberCreate($data) {
            
        $payload = [
            "dateOfBirth"   => $data->date_of_birth,
            "bvn"           => $data->bvn,
            "idNumber"      => $data->nin ?? '00000000000',
            "idType"        => "NIN_SLIP",
            "gender"        => $data->gender,
            "expiryDate"    => "2025-12-12",
        ];

        Log::info(["member create" => [$payload, $data]]);


        // dispatch()->job(new WalletStatusNotifier(
        //     app(WalletStatusObserverAndNotifier::class), 
        //     app(OnboardCustomerTestService::class,['user' => auth()->user()]), 
        //     auth()->user(),
        //     false
        //     ));

        WalletStatusNotifier::dispatch(
            app(WalletStatusObserverAndNotifier::class), 
            app(OnboardCustomerTestService::class,['user' => auth()->user()]), 
            $payload,
            $data->selfie_image_initiated,
            auth()->user(),
            false
        )->onQueue('default')->delay(now()->addSeconds(10));

        // OnboardingLogService::log(
        //     auth()->user(), 
        //     $statusResource->status == 200 ? "success": "failed",
        //     "OnboardCustomerTestService",
        //     (string)$statusResource->message,
        //     (array)$statusResource
        // );
        return response()->json((object)[
            'message' => 'we are processing your wallet at the moment',
            'status'  => 200
        ], 200);

    }



    public function WalletRetriggerAndSubAccountUpdate(Request $request) {
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


    public function updateUserSubAccount(Request $request) {
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


    public function metaPixelTest(Request $request) {
        $response = app(MetaPixelConversionService::class)
        ->eventId(Str::uuid()->toString())
        ->eventName('Register')
        ->eventTime(time())
        ->userData(email: 'test@example.com', phone: '08012345678',  customerIp: null, customerUserAgent: null, fbc: null, fbp: null )
        ->customData(userId: '123', actionTaken: 'currency', segment: 'value', status: 'success')
        ->eventSourceURL(env('APP_URL'))
        ->actionSource('website')
        ->sendToMeta();

        return response()->json([
           'data'   => $response
        ]);
    }


    public function getUsersData() {
        $users = User::whereBetween('id', [5812, 6224])->get();
        return response()->json($users);
    }  
    
    
    public function updateUserPriviledges() 
    {
        $json = file_get_contents(resource_path('data/privilege.json'));
        $users = json_decode($json, true);
        $counts = $this->insertUserData($users);

        return response()->json([
            'message' => 'User privileges counts loop and updates',
            'users' => $counts
        ]);
    }


    public function insertUserData($usersJson) {
        $count = [];
        foreach ($usersJson as $user) {
            $userModel = User::find($user['id']);

            if (!$userModel) {
                $count[] = ['id' => $user['id'], 'error' => 'User not found'];
                continue;
            }

            $authorization = $userModel->authorization()->updateOrCreate([
                'user_id' => $user['id'],
            ], [
                'priviledge'    => $user['email_verified_at'] ? 'activated' : 'blocked',
                'email'         => $user['email_verified_at'] ? 'verified' : 'unverified',
                'type'          => 'none',
                'wallet_status' => 'no_wallet',
                'internal_kyc'  => 'unapproved',
                'profile'       => 'no_profile',
                'kyc'           => 'pending'
            ]);


             $count[] = ['id' => $user['id'],  'details' => $authorization];
        }

        return $count;
    }


    
    public function updateUsers() 
    {
        $json = file_get_contents(resource_path('data/newuserdetail.json'));
        $users = json_decode($json, true);
        $counts = $this->inputUserData($users);

        return response()->json([
            'message' => 'User data successfully parsed and flattened',
            'users' => $counts
        ]);
    }

    public function inputUserData($usersJson) 
    {
        $count = [];
        foreach ($usersJson as $user) {
            $userModel = User::where('email', $user['email'])->first();

            if ($userModel) {
                $count[] = ["source" => "user already exists", "detail" => $userModel];
                continue;
            }else {
                $nameParts = explode(' ', trim($user['name']), 2);
                $insertingUser = User::create([
                    'email' => $user['email'],
                    'firstname' => $nameParts[0] ?? null,
                    'lastname' => $nameParts[1] ?? null,
                    'mobile' => $user['mobile_number'],
                    'username' => $user['username'],
                    'password' => $user['password'], // already hashed
                    'email_verified_at' => $user['email_verified_at'],
                    'exp_id' => $user['id'],
                    'remember_token' => $user['remember_token'],
                    'uuid'  => $user['uuid']
                ]);

                $insertingUser->authorization()->updateOrCreate(
                    ['user_id' => $insertingUser->id],
                  [
                    'priviledge'    => $user['email_verified_at'] ? 'activated' : 'blocked',
                    'email'         => $user['email_verified_at'] ? 'verified' : 'unverified',
                    'type'          => 'none',
                    'wallet_status' => 'no_wallet',
                    'internal_kyc'  => 'unapproved',
                    'profile'       => 'no_profile',
                    'kyc'           => 'pending'
                ]);
                $count[] = ['id' => $insertingUser->id, 'detail' => $insertingUser];
                
            }

        }

        return $count;
    }


    public function fetchUserProfile() 
    {
        $props = [];
        $profile = Profile::all();
        foreach($profile as $item) {
            $user = User::with('customerstatus.customer')
            ->find($item->user_id);

            if(@$user->customerstatus->customer !== null) {
                $kyc = $user->kycdetail()->first() ?? null;
                if($kyc === null) {
                    $profile = $user->profile()->first() ?? null;
                    $tpas = $user->kycdetail()->create([
                        "house_number" => $profile->house_number,
                        "street" => $profile->address,
                        "city" => $profile->city,
                        "state" => $profile->state,
                        "country" => $user->customerstatus->customer->country,
                        "zip_code" => $profile->zip_code, 
                        "bvn" => $user->customerstatus->customer->bvn,
                        "first_name" => $user->customerstatus->customer->firstName,
                        "last_name" => $user->customerstatus->customer->lastName,
                        "date_of_birth" => $user->customerstatus->customer->dateOfBirth,
                        "phone_number1" => $user->customerstatus->customer->phoneNumber,
                        "phone_number2" => null,
                        "gender" => $user->customerstatus->customer->gender,
                        "image" => $user->customerstatus->customer->selfieImage,
                        "selfie_verification_value" => null,
                        "selfie_verification_status" => null,
                        "selfie_image_initiated" => null,
                    ]);

                    $props[] = $tpas;
                }
                
            }

        }

        return response()->json([count($props), $props], 200);
    }
       

    public function getAccountFromDate(){
        $plots = [];
        $stuff = Customer::whereBetween('created_at', [Carbon::parse('2025-06-30 00:00:00'), now()])->get();
        foreach($stuff as $item) 
        {

            if($item->personalaccount()->first() === null && $item->escrowaccount()->first() === null) 
            {
                $userAccount = CustomerStatus::where('customerId', $item->customerId)->first();
            
                $plots[] = ['accounts' => $userAccount];
                
            } 
        }
        return response()->json([count($plots), $plots], 200);
    }



    public function fetchUserProfileFromDate() 
    {
        $users = [];
        $items = $this->fetchUserProfileFromDates();
        foreach($items as $item) {
            if(!isset($item['customerstatus']['customer']['id'])) 
                continue;
            

            $user = User::find($item['id']);


            if($user->kycdetail()->exists())
                continue;

            $kyc = $user->kycdetail()->create([
                        "house_number"      => $item['profile']['home_number'] ?? null,
                        "street"            => $item['profile']['address'] ?? null,
                        "city"              => $item['profile']['city'] ?? null,
                        "state"             => $item['profile']['state'] ?? null,
                        "country"           => $item['profile']['country'] ?? null,
                        "zip_code"          => $item['profile']['zip_code'] ?? null, 
                        "bvn"               => $item['customerstatus']['customer']['bvn'] ?? null,
                        "first_name"        => $item['customerstatus']['customer']['firstName'] ?? null,
                        "last_name"         => $item['customerstatus']['customer']['lastName'] ?? null,
                        "date_of_birth"     => $item['customerstatus']['customer']['dateOfBirth'] ?? null,
                        "phone_number1"     => $item['customerstatus']['customer']['phoneNumber'] ?? null, 
                        "phone_number2"     => null,
                        "gender"            => $item['customerstatus']['customer']['gender']?? null, 
                        "image"             => $item['customerstatus']['customer']['selfieImage'] ?? null,
                        "selfie_verification_value" => null,
                        "selfie_verification_status" => null,
                        "selfie_image_initiated" => null,
                    ]);
            $users[]  = $kyc;
        }
        return response()->json([count($users),$users]);
        
    }


    public function fetchUserProfileFromDates() 
    {
        $users = [];
        $profile = Profile::whereBetween('created_at', [Carbon::parse('2025-07-12')->startOfDay(), Carbon::parse('2025-07-14')->endOfDay()])->get();

        foreach($profile as $item)
        {
            $user = User::where('id', $item->user_id)->first();
            $users[] = $user->load(['profile', 'customerstatus.customer']);

        }

        return $users;
    }


    public function workDeclarationAndWalletOnboardingTest(Request $request) 
    {
       $validator = Validator::make($request->all(), [
            'profession' => ['required', 'string'],
            'group' => ['nullable', 'array'],
            'group.*' => ['in:buyer,seller'],
            'id'    => ['required']
        ]); 

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $makeGroup = in_array('buyer', $request->group) 
            && in_array('seller', $request->group) 
            ? 'both' : $request->group[0] ?? null;

        $user = User::find($request->id);
        

        if($user->kycdetail()->first()->selfie_verification_status === true 
            || $user->kycdetail()->first()->selfie_verification_status === "true"){
                
            $result = $this->memberCreateTest(data: $user->kycdetail()->first());
            return $result;
            
        }else {
            return response()->json([
                'message' => 'Please complete your KYC verification before proceeding with wallet onboarding.'
            ], 422);
        }
    }
    

    public function memberCreateTest($data) {
            
        $payload = [
            "dateOfBirth"   => $data->date_of_birth,
            "bvn"           => $data->bvn,
            "idNumber"      => "00000000000",
            "idType"        => "NIN_SLIP",
            "gender"        => $data->gender,
            "expiryDate"    => "2025-12-12",
        ];

        Log::info(["member create" => [$payload, $data]]);
        $user = User::find($data->user_id);
        // $statusResource = app(OnboardCustomerTestService::class, ['user' => $user])
        //     ->acquireUserDataAndValidate(edit: false)
        //     ->createMember(collections: $payload, selfieimage: $data->image)
        //     ->validateLevelOneKyc()
        //     ->monitorKycStatus()
        //     ->throwStatus();

        // OnboardingLogService::log(
        //     $user, 
        //     $statusResource->status == 200 ? "success": "failed",
        //     "OnboardCustomerTestService",
        //     (string)$statusResource->message,
        //     (array)$statusResource
        // );
        // return response()->json($statusResource, $statusResource->status);  


        // dispatch()->job(new WalletStatusNotifier(
        //     app(WalletStatusObserverAndNotifier::class), 
        //     app(OnboardCustomerTestService::class,['user' => $user]), 
        //     $user,
        //     false
        //     ));

        WalletStatusNotifier::dispatch(
            app(WalletStatusObserverAndNotifier::class), 
            app(OnboardCustomerTestService::class,['user' => $user]), 
            $payload,
            $data->image,
            $user,
            false
        )->onQueue('default')->delay(now()->addSeconds(10));

        // OnboardingLogService::log(
        //     auth()->user(), 
        //     $statusResource->status == 200 ? "success": "failed",
        //     "OnboardCustomerTestService",
        //     (string)$statusResource->message,
        //     (array)$statusResource
        // );
        return response()->json((object)[
            'message' => 'we are processing your wallet at the moment',
            'status'  => 200
        ], 200);
    }


    public function getAllEmail() 
    {

        $emailListingFiltered = [];

        User::select('id', 'firstname', 'lastname', 'email')
            ->latest()
            ->chunk(500, function ($users) use (&$emailListingFiltered) {
                foreach ($users as $user) {
                    if (!$user->kycdetail()->exists()) { // Only if no KYC record
                        $emailListingFiltered[] = [
                            'firstname' => $user->firstname,
                            'lastname'  => $user->lastname,
                            'email'     => $user->email,
                        ];
                    }
                }
            });


         
        return response()->json($emailListingFiltered);
    }


    public function splitState($state)
    {
        $state = (string)$state;
       
            $parts = explode(' ', $state, 3);
            $prefix = $parts[0]; 
            $surfix = $parts[1] ?? null;
            $surfixal = $parts[2] ?? null;

            if($prefix == "Akwa") {
                $prefix = "Akwa Ibom";
            }elseif($prefix == "Federal") {
                $prefix = "FCT";
            }elseif($prefix == "Cross"){
                $prefix = "Rivers";
            }
        return $prefix;
    }


    public function getAllAuthorization() 
    {
        Authorization::where("kyc", "approved")->update([
            'profile'   => 'has_profile',
            'type'      => 'both'
        ]);

        $auth = Authorization::where("kyc", "approved")->get();

        return response()->json($auth, 200);
    }



    public function updateVeirtualNuban(Request $request) 
    {
        $user = User::where('id', $request->id)->first();
        if($user) 
        {
            $status = $user->customerstatus()->first();
            if($status) 
            {
                $customer = $status->customer()->first();
                if($customer) 
                {
                    $personal = $customer->personalaccount()->first();

                    if($personal) 
                    {
                        $virtual = $personal->virtualnuban()->first();
                        if($virtual) {
                            return response()->json($virtual, 200);
                        }else {
                            return response()->json("no records", 400); 
                        }
                        
                    }else {
                       return response()->json("no records", 400);  
                    }
                }else {
                    return response()->json("no records", 400); 
                }
            }else {
                return response()->json("no records", 400); 
            } 
            
        }else {
           return response()->json("no records", 400); 
        }
    }


    public function getKycAndUser()
    {
        $userWithKyc = [];
        $kycs = KycDetail::all();

        foreach($kycs as $item)
        {
            $user = User::find($item->user_id);
            $status = $user->customerstatus()->first() ?? null;
            if($status === null) {
                $userWithKyc[] = [$user, Work::where('user_id', $item->user_id)->first()];
            }
        }

        return response()->json([count($userWithKyc), $userWithKyc]);
    }


    public function addVirtualNubanAccount(Request $request) 
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|string'
        ]);

        if($validation->fails()) {
            return response()->json($validation->errors()->first(), 422);
        }

        $response = app(AddVirtualNubanService::class)
        ->getVirtualNuban(id: $request->id)
        ->createVirtualNuban()
        ->show();

        return response()->json($response, $response->status);
    }


    public function addVirtualNubanAccountToAuthUser(Request $request) 
    {

        $response = app(AddVirtualNubanService::class)
        ->getVirtualNuban(id: auth()->user()->id)
        ->createVirtualNuban()
        ->show();

        return response()->json($response, $response->status);
    }


    public function multipleAddVirtualNuban(Request $request) 
    {
        $ids = [];
        foreach($request->id as $id){
            $response = app(AddVirtualNubanService::class)
            ->getVirtualNuban(id: $id)
            ->createVirtualNuban()
            ->show();
            $ids[] = $response;
        }   
        return response()->json($ids);
    }

    public function getUnupdatedVirtualnuban()
    {
        $local = [];
        CustomerStatus::where("status", "semi-verified")->update(["status"    => "fully-verified"]);
        $status = CustomerStatus::where("status", "fully-verified")->get();

        foreach($status as $item){
            $customer = $item->customer()->first() ?? null;
            if($customer !== null) {
                    $personal = $customer->personalaccount()->first() ?? null;
                    if($personal)
                    {
                        
                    }
            }

            $local[] = $customer;
            
        }

        return response()->json([count($local), $local], 200);
    }


    public function getDepositAccountsForPersonal()
    {
        $virtualNuban = [];
        $personalAccounts = PersonalAccount::whereBetween('created_at', [Carbon::parse('2025-03-15')->startOfDay(), now()])->get();

        foreach($personalAccounts as $account)
        {
            $accounts = $account->virtualnuban()->first() ?? null;
            if($accounts === null ) {
                $customer = Customer::where('id', $account->customer_id)->first();
                $status = CustomerStatus::where('id', $customer->customer_status_id)->first();
                $virtualNuban[] = $status->user_id;
            }
        }

        $uniqueUserIds = array_unique($virtualNuban);
        return response()->json([count($uniqueUserIds), $uniqueUserIds]);
    }


    public function getDepostAccountsForEscrow() 
    {
        $escrowAccount = EscrowAccount::all();
        return response()->json($escrowAccount);
    }


    public function getKycDetailsForNow()
    {
        $j=1;
        $from = Carbon::parse('2025-07-16 04:15:11');
        $to = Carbon::now();
        $result = ['user::'.$j => 0, 'action' => true];
        $detail = KycDetail::whereBetween('created_at',[$from, $to])->where(function($query) {
            $query->where('selfie_verification_status', true)->where('selfie_verification_value', '>=', 80);
        })->get();


        foreach($detail as $item){
            $update  = User::where('id', $item->user_id)->update([
                'firstname' => $item->first_name,
                'lastname' => $item->last_name
            ]);

            if($update == 1) {
                 $result['user::'.$j++] =  $item->user_id;
                 $result['action']=  true;
            }
        }
       
        return response()->json($result);
    }


}
