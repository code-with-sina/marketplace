<?php 

namespace App\Services;

use App\Models\User;
use App\Models\KycDetail;
use Illuminate\Support\Str;
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

    public function getUserDetail($uuid) 
    {
        $this->user = User::where('uuid', $uuid)->first();
        if (!$this->user) {
            $this->setFailedState(status: 400, title: __("User not found."));
        }
        return $this;
    }


    public function getValidationDetails($bvn, $selfieImage, $street, $city, $state,  $house_number) 
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

        if (!$street) {
            $this->setFailedState(status: 400, title: __("Sorry, no street added"));
            return $this;
        }

        if (!$city) {
            $this->setFailedState(status: 400, title: __("Sorry, no city added"));
            return $this;
        }

        if (!$state) {
            $this->setFailedState(status: 400, title: __("Sorry, no state added"));
            return $this;
        }


        if (!$house_number) {
            $this->setFailedState(status: 400, title: __("Sorry, no house_number added"));
            return $this;
        }

        $selfieImage = Storage::disk('public')->put('selfie', $selfieImage);

        $this->kycData = new \stdClass();

        $this->kycData->bvn = $bvn;
        $this->kycData->selfie = $selfieImage;
        $this->kycData->street = $street;
        $this->kycData->city = $city;
        $this->kycData->state = $state;
        $this->kycData->country = "NG"; // Default country set to Nigeria
        $this->kycData->house_number = $house_number;

        return $this;
    }


    public function savePrimitiveData() 
    {

        if ($this->failstate)
            return $this;

        $this->user->kycdetail()->create([
            'street'    => $this->kycData->street ?? null,
            'city'      => $this->kycData->city ?? null,      
            'state'     => $this->kycData->state ?? null,
            'country'   => $this->kycData->country,
            'house_number' => $this->kycData->house_number,
            'bvn'       => $this->kycData->bvn,
        ]);

        return $this;
    }

    public function validateUserViaDojahKyc() 
    {
        if ($this->failstate)
            return $this;

        $data = [
            'bvn' => $this->kycData->bvn,
            'selfie' => $this->kycData->selfie,
        ];

        $response = $this->appTransport('post', null, (object)$data, '/api/v1/kyc/bvn/verify');

        if ($response->statusCode != 200 || $response->data->status != 'success') {
            return $this->setFailedState(400, __("Sorry, We couldn't validate your bvn"));
        }

        $resData = $response->entity;


        $base64Truncated = $resData->image;
        $base64 = "data:image/jpeg;base64," . $base64Truncated;
        $selfieImage = $base64;
        if (!str_starts_with($selfieImage, 'data:image/jpeg;base64,')) {
            $selfieImage = "data:image/jpeg;base64," . $selfieImage;
        }

        $initialImage = $this->saveBase64Image($selfieImage, 'kycselfieimages');


        KycDetail::where('bvn', $resData->bvn)->update([
            'first_name'                    => $resData->first_name,
            'middle_name'                   => $resData->middle_name,
            'last_name'                     => $resData->last_name, 
            'phone_number1'                 => $resData->phone_number1,
            'phone_number2'                 => $resData->phone_number2,
            'gender'                        => $resData->gender,
            'date_of_birth'                 => $resData->date_of_birth,
            'image'                         => $initialImage, 
            'selfie_verification_value'     => $resData->selfie_verification->confidence_value,
            'selfie_verification_status'    => $resData->selfie_verification->match,
            'selfie_image_initiated'        => $resData->selfie_image_url
        ]);

        return $this->setSuccessState(200, __("BVN validation successful"));
    }   



    public function saveBase64Image(string $base64, string $folder = 'images')
    {
            if (!str_contains($base64, ';') || !str_contains($base64, ',')) {
                throw new \Exception('Invalid base64 format');
            }

            list($type, $fileData) = explode(';', $base64);
            list(, $fileData) = explode(',', $fileData);

            preg_match('/data:image\/(.*?);/', $type, $matches);
            $extension = $matches[1] ?? 'jpg';

            $fileData = base64_decode($fileData);
            if ($fileData === false) {
                throw new \Exception('Base64 decode failed');
            }

            $filename = $folder . '/' . Str::random(40) . '.' . $extension;

            Storage::disk('public')->put($filename, $fileData);

            return Storage::url($filename);
    }   


    public function appTransport(string $method = 'post', ?string $params = null,  object $objectData = null)
    {
       $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
            if (!in_array($method, $allowedMethods)) {
                throw new \InvalidArgumentException("Invalid HTTP method: $method");
            }

            $url = $params ? env('DOJAH_KYC_URL')  . '/' . $params : env('DOJAH_KYC_URL');

            Log::error($url);

            try {
                $customerObject = Http::withHeaders([
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' =>  env('DOJAH_KYC_API_KEY'),
                    'AppId' => env('DOJAH_KYC_APP_ID'),
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