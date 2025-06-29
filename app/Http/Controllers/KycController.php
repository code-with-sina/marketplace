<?php

namespace App\Http\Controllers;

use App\Models\KycDetail;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\DojahKycService;
use App\Services\OnboardCustomerService;
use App\Services\KycCheckerService;
use App\Services\UpdateAccountService;
use Illuminate\Support\Facades\Hash;
use App\Services\MetaPixelConversionService;

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
        // $validator = Validator::make($request->all(), [
        //     'profession' => ['required', 'string'],
        //     'group' => ['nullable', 'array'],
        //     'group.*' => ['in:buyer,seller'],
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'errors' => $validator->errors()
        //     ], 422);
        // }


        // $user = User::find(auth()->user()->id);
        // $user->works()->create([
        //     'profession' => $request->profession,
        //     'group' => $request->group,
        // ]);

        // if($user->kycdetails()->first()->selfie_verification_status === true || $user->kycdetails()->first()->selfie_verification_status === "true"){
        //     $this->memberCreate(data: $this->kycdetails()->first());
        // }else {
        //     return response()->json([
        //         'message' => 'Please complete your KYC verification before proceeding with wallet onboarding.'
        //     ], 422);
            
        // }
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


        $statusResource = app(OnboardCustomerService::class, ['user' => auth()->user()])
            ->acquireUserDataAndValidate(edit: false)
            ->createMember(collections: $payload, selfieimage: $data->image)
            ->validateLevelOneKyc()
            ->monitorKycStatus()
            ->throwStatus();

        return response()->json($statusResource, $statusResource->status);  
        
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


    public function updateUsercustomerAccount() {
        $json = file_get_contents(resource_path('data/customers.json'));
        $customers = json_decode($json, true);

        $customerList = $customers['data'] ?? [];
        $counts = $this->flattenCustomerData($customerList);

        return response()->json([
            'message' => 'Customer data successfully parsed and flattened',
            'users' => $counts
        ]);
    }

    public function flattenCustomerData($customerJson) {
        $result = [];
        foreach ($customerJson as $data) {
            $flattened = [
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

            $users = User::where("email", $flattened['email'])->first();
            $userCustomer = null;
            $customerData = null;
            if($users == null) {
                $userCustomer = null;
                $customerData = null;
            }else {
                $userCustomer = @$users->customerstatus()->first();
                if($userCustomer !== null) {
                    $primitive = @$userCustomer->customer()->first();
                    $customerData = [@$userCustomer->customer()->first(), $primitive->personalaccount()->first(), $primitive->escrowaccount()->first()];
                }else {
                    $primitiveInitiatoryUserData = $users->customerstatus()->create([
                        'user_id' => $users->id,
                        'customer_id' => $flattened['id'],
                        'type' => $flattened['type'],
                        'status' => "pending"
                    ]);

                    $completeUserDataCreated = $primitiveInitiatoryUserData->customer()->create([
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
                    ]);

                    
                  $customerData = $completeUserDataCreated;  
                }
            }

            $result[] = [
                'id' => $data['id'],
                'details' => $customerData ?? [$flattened['email'],  @$customerData, $users]
            ];
        }

        return $result;
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
   

}
