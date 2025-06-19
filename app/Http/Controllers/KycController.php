<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\DojahKycService;

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
}
