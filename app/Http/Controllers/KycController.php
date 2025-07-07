<?php

namespace App\Http\Controllers;



use Carbon\Carbon;
use App\Models\User;
use App\Models\Profile;
use App\Models\Customer;
use App\Models\KycDetail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
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
use Illuminate\Support\Facades\Validator;
use App\Services\MetaPixelConversionService;
use App\Services\OnboardCustomerTestService;



class KycController extends Controller
{
    public function kycGate(Request $request) {
        $probeKyc = @KycDetail::where('user_id', auth()->user()->id)->first();
        if($probeKyc !== null ) {
            // return response()->json($probeKyc->selfie_verification_status, 200);
            

            if($probeKyc->selfie_verification_status !== null && $probeKyc->selfie_verification_status == true) {
                 return response()->json(["message"=> "User already verified", "status" => 200], 200);
            }else {
                $validator = Validator::make([
                    'bvn' => $request->bvn,
                    'selfie_image' => $request->selfieimage,
                    'street' => $request->street,
                    'city' => $request->city,
                    'state' => $request->state,
                    'house_number' => $request->house_number,
                    'zip_code' => $request->zip_code,
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
                ]);

                if ($validator->fails()) {
                    return response()->json($validator->errors()->first(), 422);
                }


                $processKYC = app(DojahKycService::class)
                ->primitiveState(editState: true)
                ->getUserDetail(auth()->user()->uuid)
                ->getValidationDetails(
                    bvn: $request->bvn,
                    selfieImage: $request->selfieimage,
                    street: $request->street,
                    city: $request->city,
                    state: $request->state,
                    house_number: $request->house_number,
                    zip_code: $request->zip_code
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
                'state' => $request->state,
                'house_number' => $request->house_number,
                'zip_code' => $request->zip_code,
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
                state: $request->state,
                house_number: $request->house_number,
                zip_code: $request->zip_code
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
        $user->works()->create([
            'profession' => $request->profession,
            'group' => $makeGroup,
        ]);

        if($user->kycdetail()->first()->selfie_verification_status === true || $user->kycdetail()->first()->selfie_verification_status === "true"){
            $this->memberCreate(data: $user->kycdetail()->first());
        }else {
            return response()->json([
                'message' => 'Please complete your KYC verification before proceeding with wallet onboarding.'
            ], 422);
            
        }
    }



    public function workDeclarationAndWalletOnboardingTest(Request $request){
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

        $user = User::find($request->user_id);
        $user->works()->create([
            'profession' => $request->profession,
            'group' => $makeGroup,
        ]);

        if($user->kycdetail()->first()->selfie_verification_status === true || $user->kycdetail()->first()->selfie_verification_status === "true"){
            $this->memberCreate(data: $user->kycdetail()->first());
        }else {
            return response()->json([
                'message' => 'Please complete your KYC verification before proceeding with wallet onboarding.'
            ], 422);
            
        }
    }

    public function memberCreate($data) {
            
        $payload = [
            "dateOfBirth"   => $data->dateOfBirth,
            "bvn"           => $data->bvn,
            "idNumber"      => "00000000000",
            "idType"        => "NIN",
            "gender"        => $data->gender,
            "expiryDate"    => "2025-12-12",
        ];


        $statusResource = app(OnboardCustomerTestService::class, ['user' => auth()->user()])
            ->acquireUserDataAndValidate(edit: false)
            ->createMember(collections: $payload, selfieimage: $data->image)
            ->validateLevelOneKyc()
            ->monitorKycStatus()
            ->throwStatus();

        return response()->json($statusResource, $statusResource->status);  
        return response()->json($payload, 200);
        
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


    // public function updateUsercustomerAccount() {
    //     $json = file_get_contents(resource_path('data/customers.json'));
    //     $customers = json_decode($json, true);

    //     $customerList = $customers['data'] ?? [];
    //     $counts = $this->flattenCustomerData($customerList);

    //     return response()->json([
    //         'message' => 'Customer data successfully parsed and flattened',
    //         'users' => $counts
    //     ]);
    // }

    // public function flattenCustomerData($customerJson) {
    //     $result = [];
    //     foreach ($customerJson as $data) {
    //         $flattened = [
    //             'id' => $data['id'],
    //             'type' => $data['type'],
    //             'created_at' => $data['attributes']['createdAt'],
    //             'phone_number' => $data['attributes']['phoneNumber'],
    //             'address' => $data['attributes']['address']['addressLine_1'],
    //             'country' => $data['attributes']['address']['country'],
    //             'state' => $data['attributes']['address']['state'],
    //             'city' => $data['attributes']['address']['city'],
    //             'postal_code' => $data['attributes']['address']['postalCode'],
    //             'sole_proprietor' => $data['attributes']['soleProprietor'],
    //             'first_name' => $data['attributes']['fullName']['firstName'],
    //             'last_name' => $data['attributes']['fullName']['lastName'],
    //             'middle_name' => $data['attributes']['fullName']['middleName'],
    //             'maiden_name' => $data['attributes']['fullName']['maidenName'],
    //             'id_level3_type' => $data['attributes']['identificationLevel3']['idType'],
    //             'id_level3_number' => $data['attributes']['identificationLevel3']['idNumber'],
    //             'id_level3_expiry' => $data['attributes']['identificationLevel3']['expiryDate'],
    //             'dob' => $data['attributes']['identificationLevel2']['dateOfBirth'],
    //             'gender' => $data['attributes']['identificationLevel2']['gender'],
    //             'bvn' => $data['attributes']['identificationLevel2']['bvn'],
    //             'selfie' => $data['attributes']['identificationLevel2']['selfieImage'],
    //             'email' => $data['attributes']['email'],
    //             'verification_level' => $data['attributes']['verification']['level'],
    //             'verification_status' => $data['attributes']['verification']['status'],
    //             'verification_details' => $data['attributes']['verification']['details'],
    //             'status' => $data['attributes']['status'],
    //             'organization_id' => $data['relationships']['organization']['data']['id'],
    //             'organization_type' => $data['relationships']['organization']['data']['type'],
    //         ];

    //         $users = User::where("email", $flattened['email'])->first();
    //         $userCustomer = null;
    //         $customerData = null;
    //         if($users == null) {
    //             $userCustomer = null;
    //             $customerData = null;
    //         }else {
    //             $userCustomer = @$users->customerstatus()->first() ?? null;
    //             if($userCustomer !== null) {
                    
    //                 $primitiveInitiatoryUserData = $users->customerstatus()->update([
    //                     'user_id' => $users->id,
    //                     'customerId' => $flattened['id'],
    //                     'type' => $flattened['type'],
    //                     'status' => $flattened['verification_status']
    //                 ]);

    //                  $completeUserDataCreated = $primitiveInitiatoryUserData->customer()->updateOrCreate([
    //                     'customerId' => $flattened['id'],
    //                     'customerType' => $flattened['type'],
    //                     'soleProprietor' => $flattened['sole_proprietor'],
    //                     'firstName' => $flattened['first_name'],
    //                     'lastName' => $flattened['last_name'],
    //                     'email' => $flattened['email'],
    //                     'phoneNumber' => $flattened['phone_number'],
    //                     'address' => $flattened['address'],
    //                     'country' => $flattened['country'],
    //                     'state' => $flattened['state'],
    //                     'city' => $flattened['city'],
    //                     'postalCode' => $flattened['postal_code'],
    //                     'gender' => $flattened['gender'],
    //                     'bvn' => $flattened['bvn'],
    //                     'selfieImage' => $flattened['selfie'],
    //                     'expiryDate' => $flattened['id_level3_expiry'],
    //                     'idType' => $flattened['id_level3_type'],
    //                     'idNumber' => $flattened['id_level3_number'],
    //                     'status' => $flattened['status'],
    //                     'registered' => Hash::make($flattened['status'] . '-' . uniqid())
    //                 ]);

                    
    //               $customerData = $completeUserDataCreated;  
    //             }else {
    //                 $primitiveInitiatoryUserData = $users->customerstatus()->create([
    //                     'user_id' => $users->id,
    //                     'customerId' => $flattened['id'],
    //                     'type' => $flattened['type'],
    //                     'status' => $flattened['verification_status']
    //                 ]);

    //                 $completeUserDataCreated = $primitiveInitiatoryUserData->customer()->create([
    //                     'customerId' => $flattened['id'],
    //                     'customerType' => $flattened['type'],
    //                     'soleProprietor' => $flattened['sole_proprietor'],
    //                     'firstName' => $flattened['first_name'],
    //                     'lastName' => $flattened['last_name'],
    //                     'email' => $flattened['email'],
    //                     'phoneNumber' => $flattened['phone_number'],
    //                     'address' => $flattened['address'],
    //                     'country' => $flattened['country'],
    //                     'state' => $flattened['state'],
    //                     'city' => $flattened['city'],
    //                     'postalCode' => $flattened['postal_code'],
    //                     'gender' => $flattened['gender'],
    //                     'bvn' => $flattened['bvn'],
    //                     'selfieImage' => $flattened['selfie'],
    //                     'expiryDate' => $flattened['id_level3_expiry'],
    //                     'idType' => $flattened['id_level3_type'],
    //                     'idNumber' => $flattened['id_level3_number'],
    //                     'status' => $flattened['status'],
    //                     'registered' => Hash::make($flattened['status'] . '-' . uniqid())
    //                 ]);

                    
    //               $customerData = $completeUserDataCreated;  
    //             }
    //         }

    //         $result[] = [
    //             'id' => $data['id'],
    //             'details' => $customerData ?? [$flattened['email'],  @$customerData, $users]
    //         ];
    //     }

    //     return $result;
    // }


    public function updateUsercustomerAccount()
    {
        $json = file_get_contents(resource_path('data/customers.json'));
        $customers = json_decode($json, true);

        $customerList = $customers['data'] ?? [];
        $result = $this->processCustomerList($customerList);

        return response()->json([
            'message' => 'Customer data successfully parsed and processed.',
            'users' => $result
        ]);
    }


    private function processCustomerList(array $customerList): array
    {
        $result = [];

        foreach ($customerList as $data) {
            $flattened = $this->flattenCustomerData($data);
            $user = User::where('email', $flattened['email'])->first();
            $customerStatus = $user->customerstatus()->first();
            $primitiveAccount = $customerStatus->update([
                
                'customerId' => $flattened['id'],
                'type' => $flattened['type'],
                'status' => $flattened['verification_status'] === "approved" ? "fully-verified" : ($flattened['verification_status'] === "rejected" ? "rejected" : "pending")
            ]);

            $fullCustomerData = CustomerStatus::where('customerId', $flattened['id'])->first();

            $result[] = [@$fullCustomerData->customer()->first(), $flattened['id']];
            
        }

        return $result;
    }



    private function flattenCustomerData(array $data): array
    {
        return [
            'id' => $data['id'],
            'type' => $data['type'],
            'created_at' => $data['attributes']['createdAt'],
            'phone_number' => $data['attributes']['phoneNumber'],
            'address' => $data['attributes']['address']['addressLine_1'],
            'country' => $data['attributes']['address']['country'],
            'state' => $data['attributes']['address']['state'],
            'city' => $data['attributes']['address']['city'],
            'postal_code' => $data['attributes']['address']['postalCode'],
            'sole_proprietor' => $data['attributes']['soleProprietor'],
            'first_name' => $data['attributes']['fullName']['firstName'],
            'last_name' => $data['attributes']['fullName']['lastName'],
            'middle_name' => $data['attributes']['fullName']['middleName'],
            'maiden_name' => $data['attributes']['fullName']['maidenName'],
            'id_level3_type' => $data['attributes']['identificationLevel3']['idType'],
            'id_level3_number' => $data['attributes']['identificationLevel3']['idNumber'],
            'id_level3_expiry' => $data['attributes']['identificationLevel3']['expiryDate'],
            'dob' => $data['attributes']['identificationLevel2']['dateOfBirth'],
            'gender' => $data['attributes']['identificationLevel2']['gender'],
            'bvn' => $data['attributes']['identificationLevel2']['bvn'],
            'selfie' => $data['attributes']['identificationLevel2']['selfieImage'],
            'email' => $data['attributes']['email'],
            'verification_level' => $data['attributes']['verification']['level'],
            'verification_status' => $data['attributes']['verification']['status'],
            'verification_details' => $data['attributes']['verification']['details'],
            'status' => $data['attributes']['status'],
            'organization_id' => $data['relationships']['organization']['data']['id'],
            'organization_type' => $data['relationships']['organization']['data']['type'],
        ];
    }

    private function mapCustomerData(array $flattened): array
    {
        return [
            'customerId' => $flattened['id'],
            'customerType' => $flattened['type'],
            'soleProprietor' => $flattened['sole_proprietor'],
            'firstName' => $flattened['first_name'],
            'lastName' => $flattened['last_name'],
            'email' => $flattened['email'],
            'phoneNumber' => $flattened['phone_number'],
            'address' => $flattened['address'],
            'country' => $flattened['country'],
            'state' => $flattened['state'],
            'city' => $flattened['city'],
            'postalCode' => $flattened['postal_code'],
            'gender' => $flattened['gender'],
            'bvn' => $flattened['bvn'],
            'selfieImage' => $flattened['selfie'],
            'expiryDate' => $flattened['id_level3_expiry'],
            'idType' => $flattened['id_level3_type'],
            'idNumber' => $flattened['id_level3_number'],
            'status' => $flattened['status'],
            'registered' => Hash::make($flattened['status'] . '-' . uniqid())
        ];
    }

    public function getEmailCheck() {
        $json = file_get_contents(resource_path('data/email.json'));
        $email = json_decode($json, true);
  
        $counts = $this->verifyEmail($email);

        return response()->json([
            'message' => 'Customer data successfully parsed and flattened',
            'users' => $counts
        ]);
    }

    public function verifyEmail($email) {
        $count = [];
        foreach ($email as $user) {
            $users = @User::where("email", $user['details'])->first();
            $count[] =  $users !== null ? $users : $user['details'];
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
   


    public function updateCustomersAccounts() 
    {
        $json = file_get_contents(resource_path('data/deposit.json'));
        $customers = json_decode($json, true);
        $customerList = $customers['data'] ?? [];
        $stale = $this->splitdep($customerList);
        // $menate = $this->makeMenated($stale);
        return response()->json([
            'message' => 'Customer data successfully parsed and processed.',
            'users' => [count($stale), "splited" =>  $stale]
        ]);
    }

    public function splitdep($data)
    {
        $result = [];

        foreach ($data as $item) {
            $accountId = $item['id'] ?? null;

            $personalAccount = PersonalAccount::where('personalId', $accountId)->first() ?? null;
            $escrowAccount =  EscrowAccount::where('escrowId', $accountId)->first() ?? null;

            

            if($personalAccount === null && $escrowAccount === null) 
            {
                // $customerDetail = Customer::where('customerId', $item['relationships']['customer']['data']['id'])->first() ?? null;
                // $result[] = ["acoountToGenerate" => $accountId, "customer_id" => $item['relationships']['customer']['data']['id']];

                $customerId = $item['relationships']['customer']['data']['id'] ?? null;

                if ($customerId) {
                    if (!isset($result[$customerId])) {
                        $result[$customerId] = [
                            'customer_id' => $customerId,
                            'acoountToGenerate' => [],
                        ];
                    }

                    // Push the actual account item alongside the ID
                    $result[$customerId]['acoountToGenerate'][] = [
                        'account_id' => $accountId,
                        'account_detail' => $item
                    ];

                   
                }
            }
            
        }


        // Now convert the plain list to named keys (freelance, escrow)
        foreach ($result as &$entry) {
            $accounts = $entry['acoountToGenerate'];
            $entry['acoountToGenerate'] = [];

            if (isset($accounts[0])) {
                $entry['acoountToGenerate']['personal'] = $accounts[0];
            }

            if (isset($accounts[1])) {
                $entry['acoountToGenerate']['escrow'] = $accounts[1];
            }

        }

        return $result;
    }


    public function makeMenated($users) 
    {
        $plot = [];
        foreach($users as $item) {
            $checkAccount = Customer::where("customerId", $item['customer_id'])->first() ?? null;
            if($checkAccount !== null ){
                $personal = null;
                $escrow = null;
                if(@$item['acoountToGenerate']['personal'] !== null) {
                    $freelanceDetail = $item['acoountToGenerate']['personal']['account_detail'] ?? [];
                    $flattened = [
                        "personalId"        => $freelanceDetail['id'] ?? null,
                        "personalType"      => $freelanceDetail['type'] ?? null,
                        "customer_id"       => $checkAccount->id ?? null,
                        "bankId"            => $freelanceDetail['attributes']['bank']['id'] ?? null,
                        "bankName"          => $freelanceDetail['attributes']['bank']['name'] ?? null,
                        "cbnCode"           => $freelanceDetail['attributes']['bank']['cbnCode'] ?? null,
                        "nipCode"           => $freelanceDetail['attributes']['bank']['nipCode'] ?? null,
                        "accountName"       => $freelanceDetail['attributes']['accountName'] ?? null,
                        "accountNumber"     => $freelanceDetail['attributes']['accountNumber'] ?? null,
                        "type"              => $freelanceDetail['attributes']['type'] ?? null,
                        "status"            => $freelanceDetail['attributes']['status'] ?? null,
                        "frozen"            => $freelanceDetail['attributes']['frozen'] ?? null,
                        "currency"          => $freelanceDetail['attributes']['currency'] ?? null,
                        "availableBalance"  => $freelanceDetail['attributes']['availableBalance'] ?? null,
                        "pendingBalance"    => $freelanceDetail['attributes']['pendingBalance'] ?? null,
                        "ledgerBalance"     => $freelanceDetail['attributes']['ledgerBalance'] ?? null,
                        "virtualNubans_id"  => $freelanceDetail['relationships']['virtualNubans']['data'][0]['id'] ?? null,
                        "virtualNubans_type"=> $freelanceDetail['relationships']['virtualNubans']['data'][0]['type'] ?? null,
                    ];

                    $flatters = $checkAccount->personalaccount()->create($flattened);
                    $personal =  $flatters ?? null;
                }


                if(@$item['acoountToGenerate']['escrow'] !== null) {
                    $freelanceDetail = $item['acoountToGenerate']['escrow']['account_detail'] ?? [];
                    $flattened = [
                        "escrowId"          => $freelanceDetail['id'] ?? null,
                        "escrowType"        => $freelanceDetail['type'] ?? null,
                        "customer_id"       => $checkAccount->id ?? null,
                        "bankId"            => $freelanceDetail['attributes']['bank']['id'] ?? null,
                        "bankName"          => $freelanceDetail['attributes']['bank']['name'] ?? null,
                        "cbnCode"           => $freelanceDetail['attributes']['bank']['cbnCode'] ?? null,
                        "nipCode"           => $freelanceDetail['attributes']['bank']['nipCode'] ?? null,
                        "accountName"       => $freelanceDetail['attributes']['accountName'] ?? null,
                        "accountNumber"     => $freelanceDetail['attributes']['accountNumber'] ?? null,
                        "type"              => $freelanceDetail['attributes']['type'] ?? null,
                        "status"            => $freelanceDetail['attributes']['status'] ?? null,
                        "frozen"            => $freelanceDetail['attributes']['frozen'] ?? null,
                        "currency"          => $freelanceDetail['attributes']['currency'] ?? null,
                        "availableBalance"  => $freelanceDetail['attributes']['availableBalance'] ?? null,
                        "pendingBalance"    => $freelanceDetail['attributes']['pendingBalance'] ?? null,
                        "ledgerBalance"     => $freelanceDetail['attributes']['ledgerBalance'] ?? null,
                        "virtualNubans_id"  => $freelanceDetail['relationships']['virtualNubans']['data'][0]['id'] ?? null,
                        "virtualNubans_type"=> $freelanceDetail['relationships']['virtualNubans']['data'][0]['type'] ?? null,
                    ];

                    $flatters = $checkAccount->escrowaccount()->create($flattened);
                    $escrow = $flatters ?? null;
                }


                $plot[] = [
                    'customer_id' => $item['customer_id'],
                    'personal' => $personal,
                    'escrow' => $escrow,
                    'customer' => $checkAccount
                ];
            }
        }

        return $plot;
    }


    public function getAccountFromDate(){
        $plots = [];
        $stuff = Customer::whereBetween('created_at', [Carbon::parse('2025-06-30 00:00:00'), now()])->get();
        foreach($stuff as $item) 
        {
            // if($item->personalaccount()->first() === null && $item->escrowaccount()->first() === null) {
            //     $user = User::where('email', $item->email)->first();
            //     $user->authorization()->update(['kyc' => 'approved']);
            //     $user->customerstatus()->update(['status' => 'fully-verified']);
            //     app(SubAccountService::class)->validateUser($user->uuid)
            //         ->validateUserKyc()
            //         ->processEscrow()
            //         ->processPersonal()
            //         ->createSubAccount()
            //         ->throwStatus(); 

            //     $plots[] = ["userData" => ["customer" => $user]];
            // }else {
            //     $plots[] = ["userData" => ["account" => $item->personalaccount()->first() ?? null, "acounts" => $item->escrowaccount()->first() ?? null]];
            // }

            if($item->personalaccount()->first() === null && $item->escrowaccount()->first() === null) 
            {
                $userAccount = CustomerStatus::where('customerId', $item->customerId)->first();
                if($userAccount->status === "fully-verified") 
                {
                    
                    $user = User::where('email', $userAccount->customer()->first()->email)->first();
                    $user->authorization()->update(['kyc' => 'approved']);
                    $user->customerstatus()->update(['status' => 'fully-verified']);
                    app(SubAccountService::class)->validateUser($user->uuid)
                        ->validateUserKyc()
                        ->processEscrow()
                        ->processPersonal()
                        ->createSubAccount()
                        ->throwStatus(); 
                    $plots[] = ['accounts' => $userAccount];
                    sleep(5);
                }


                // $plots[] = ['accounts' => $item->escrowaccount()->first()];
                
            }

           
            
        }
        return response()->json([count($plots), $plots], 200);
    }
    
}
