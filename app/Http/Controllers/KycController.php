<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\DojahKycService;
use App\Services\OnboardCustomerService;

class KycController extends Controller
{
    public function kycGate(Request $request) {
        $validator = Validator::make([
            'bvn' => $request->bvn,
            'selfie_image' => $request->selfieImage,
            'street' => $request->street,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'house_number' => $request->house_number,
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
            'country' => ['required', 'string'],
            'house_number' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([$validator->errors()->first()], 422);
        }

        $processKYC = app(DojahKycService::class)
        ->getUserDetail(auth()->user()->uuid)
        ->getValidationDetails(
            bvn: $request->bvn,
            selfieImage: $request->selfieImage,
            street: $request->street,
            city: $request->city,
            state: $request->state,
            country: $request->country,
            house_number: $request->house_number
        )
        ->savePrimitiveData()
        ->validateUserViaDojahKyc();


        return response()->json($processKYC->title, $processKYC->status);
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


        $user = User::find(auth()->user()->id);
        $user->works()->create([
            'profession' => $request->profession,
            'group' => $request->group,
        ]);

        if($user->kycdetails()->first()->selfie_verification_status === true || $user->kycdetails()->first()->selfie_verification_status === "true"){
            $this->memberCreate(data: $this->kycdetails()->first());
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


        $statusResource = app(OnboardCustomerService::class, ['user' => auth()->user()])
            ->acquireUserDataAndValidate(edit: false)
            ->createMember(collections: $payload, selfieimage: $data->image)
            ->validateLevelOneKyc()
            ->monitorKycStatus()
            ->throwStatus();

        return response()->json($statusResource, $statusResource->status);  
        
    }
}
