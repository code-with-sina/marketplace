<?php 

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class DojahKycService 
{

    protected $user;
    public $fail;
    public $failstate;
    public $success;
    public $kycData;

    protected const BVN_VALIDATION_FAILED = "Sorry, We couldn't validate your bvn";

    public function getFetchUserDetail($uuid) 
    {
        $this->user = User::where('uuid', $uuid)->first();
        if (!$this->user) {
            $this->setFailedState(status: 400, title: __("User not found."));
        }
        return $this;
    }


    public function getValidationDetails($bvn, $selfieImage) 
    {
        if (!$this->user) {
            $this->setFailedState(status: 400, title: __("Sorry, We couldn't find the user"));
            return $this;
        }
        if (!$bvn) {
            $this->setFailedState(status: 400, title: __("Sorry, no bvn added"));
            return $this;
        }
        if (!$selfieImage) {
            $this->setFailedState(status: 400, title: __("Sorry, no selfie image added"));
            return $this;
        }

        $selfieImage = Storage::disk('public')->put('selfie', $selfieImage);

        $this->kycData = [
            "bvn" => $bvn,
            "selfie" => $selfieImage,
            "first_name" => $this->user->firstname,
            "last_name" => $this->user->lastname,
            
        ];

        return $this;
    }

    public function validateUserViaDojahKyc() 
    {
        if ($this->failstate)
            return $this;

        $response = $this->appTransport('post', null, (object)$this->kycData, '/api/v1/kyc/bvn/verify');

        if ($response->statusCode != 200 || $response->data->status != 'success') {
            return $this->setFailedState(400, __("Sorry, We couldn't validate your bvn"));
        }

        $resData = $response->data->data;
        if (
            $resData->bvn !== $this->kycData['bvn'] ||
            $resData->first_name !== $this->kycData['first_name'] ||
            $resData->last_name !== $this->kycData['last_name']
        ) {
            return $this->setFailedState(400, __("Sorry, We couldn't validate your bvn"));
        }

        return $this->setSuccessState(200, __("BVN validation successful"));
    }   




    public function appTransport(string $method = 'post', ?string $params = null,  object $objectData = null, $endpoint = 'customers')
    {
       $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
            if (!in_array($method, $allowedMethods)) {
                throw new \InvalidArgumentException("Invalid HTTP method: $method");
            }

            $url = $params ? env('DOJAH_KYC_URL') . $endpoint . '/' . $params : env('DOJAH_KYC_URL') . $endpoint;

            Log::error($url);

            try {
                $customerObject = Http::withHeaders([
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' =>  env('DOJAH_KYC_API_KEY'),
                    'AppId' => env('DOJAH_KYC_API_KEY'),
                ])->$method($url, $objectData);
                return (object)['statusCode' => $customerObject->status(), 'data' => $customerObject->object()];
            
            } catch (\Exception $e) {
                Log::error("Unexpected error: " . $e->getMessage());
                return (object)[
                    'statusCode' => 500,
                    'data' => 'Something went wrong. Please try again later.'
                ];
            }

    }


    public function setFailedState($status = 400, $title)
    {
        $this->failstate = true;
        $this->fail = (object) [
            "status" => $status,
            "title" => $title
        ];
    }
    public function setSuccessState($status = 200, $title)
    {
        $this->success = (object) [
            "status" => $status,
            "title" => $title
        ];
    }
    public function getFailState()
    {
        return $this->failstate;
    }
}