<?php

namespace App\WalletFacades;

use Illuminate\Support\Facades\Auth;
use App\AdminFacades\HasObjectConverter;
trait HasValidateKyc 
{
    use HasObjectConverter;
    public function validateKyc($customers){

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

        $firstValidate = $this->ToObject($tierOne);
        $secondValidate = $this->ToObject($tiertwo);

        $levelOne = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'), 
        ])->post(env('ANCHOR_SANDBOX').'customers/'.$customers->customerId.'/verification/individual', $firstValidate);

        if($levelOne->status() === 200) {
            $auth = Auth::user();

            $user = User::find($auth->id);
            $user->customerstatus()->update(['status' => 'semi-verified']);
            $levelTwo = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'), 
            ])->post(env('ANCHOR_SANDBOX').'customers/'.$customers->customerId.'/verification/individual', $secondValidate);
            if($levelTwo->status() === 200) {
                $user->customerstatus()->update(['status' => 'fully-verified']);
                return  $levelTwo->status();
            }
        }  
    }
}