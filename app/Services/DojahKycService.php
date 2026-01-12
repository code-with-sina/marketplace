<?php 

namespace App\Services;

use App\Models\User;
use App\Models\KycDetail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\MetaPixelConversionService;


class DojahKycService 
{

    protected $user;
    public $fail;
    public $failstate;
    public $success;
    public $kycData;
    public $editState;

    protected const BVN_VALIDATION_FAILED = "Sorry, We couldn't validate your bvn";

    public function primitiveState($editState) {
        $this->editState = $editState;
        return $this;
    }

    public function getUserDetail($uuid) 
    {
        $this->user = User::where('uuid', $uuid)->first();
        if (!$this->user) {
            $this->setFailedState(status: 400, title: __("User not found."));
        }
        return $this;
    }


    public function getValidationDetails($bvn, $selfieImage, $street, $city, $state,  $house_number, $zip_code, $nin) 
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

        if(!$zip_code) {
             $this->setFailedState(status: 400, title: __("Sorry, no house_number added"));
            return $this;
        }


        if(!$nin) {
             $this->setFailedState(status: 400, title: __("Sorry, no nin added"));
            return $this;
        }
       

        $this->kycData = new \stdClass();

        $this->kycData->bvn = $bvn;
        $this->kycData->selfie = $selfieImage;
        $this->kycData->street = $street;
        $this->kycData->city = $city;
        $this->kycData->state = $state;
        $this->kycData->country = "NG"; // Default country set to Nigeria
        $this->kycData->house_number = $house_number;
        $this->kycData->zip_code = $zip_code;
        $this->kycData->nin = $nin;

        return $this;
    }


    public function savePrimitiveData() 
    {

        if ($this->failstate)
            return $this;

        $initialLocalImage = $this->cookImage($this->kycData->selfie);

        if($this->editState === true) {
             $this->user->kycdetail()->update([
                'street'    => $this->kycData->street ?? null,
                'city'      => $this->kycData->city ?? null,      
                'state'     => $this->kycData->state ?? null,
                'country'   => $this->kycData->country,
                'house_number' => $this->kycData->house_number,
                'bvn'       => $this->kycData->bvn,
                'zip_code'  => $this->kycData->zip_code,
                'nin'       => $this->kycData->nin,
                'initial_image' => $initialLocalImage
            ]);

        }else {
             $this->user->kycdetail()->create([
                'street'    => $this->kycData->street ?? null,
                'city'      => $this->kycData->city ?? null,      
                'state'     => $this->kycData->state ?? null,
                'country'   => $this->kycData->country,
                'house_number' => $this->kycData->house_number,
                'bvn'       => $this->kycData->bvn,
                'zip_code'  => $this->kycData->zip_code,
                'nin'       => $this->kycData->nin,
                'initial_image' => $initialLocalImage
            ]);

        }
       
        return $this;
    }

    public function validateUserViaDojahKyc() 
    {
        if ($this->failstate)
            return $this;

        $data = [
            'bvn' => $this->kycData->bvn,
            'selfie_image' => $this->kycData->selfie,
        ];

        Log::info(["Dojah KYC Response" =>  $data]);
        $response = $this->appTransport('post', '/api/v1/kyc/bvn/verify', (object)$data);

        if ($response->statusCode != 200 ) {
            $this->setFailedState(400, __("Sorry, We couldn't validate your bvn"));
            return $this;
        }


        if($response->data->entity->selfie_verification->match === false) {
             $this->setFailedState(400, __("Sorry, Your selfie verification did not match. Please try again with a clearer image."));
            return $this;
        }

        $resData = $response->data->entity;
        Log::info(["Dojah KYC Response" => $resData]);

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
            'image'                         => "https://p2p.ratefy.co".$initialImage, 
            'selfie_verification_value'     => $resData->selfie_verification->confidence_value,
            'selfie_verification_status'    => $resData->selfie_verification->match,
            'selfie_image_initiated'        => $resData->selfie_image_url
        ]);

        

        $this->user->authorization()->update([
            'kyc'       => 'approved',
            'profile'   => 'has_profile'
        ]);


        $this->user()->update([
            'firstname' => $resData->first_name,
            'lastname'  => $resData->last_name
        ]);
        
        $this->setSuccessState(200, __("BVN validation successful"));
        return $this;
    }   


    public function throwResponse()
    {

         return $this->failstate ? $this->fail : tap($this->success, fn () => $this->callMetaPixel());
    }


    public function callMetaPixel() 
    {
        app(MetaPixelConversionService::class)
                ->eventId($this->user->uuid)
                ->eventName('KYC')
                ->eventTime(time())
                ->userData(email: $this->user->email, phone: $this->user->mobile,  customerIp: null, customerUserAgent: null, fbc: null, fbp: null)
                ->customData(userId: $this->user->id, actionTaken: 'KYC Completion', segment: 'KYC', status: 'success')
                ->eventSourceURL(env('APP_URL'))
                ->actionSource('website')
                ->sendToMeta();
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

    public function cookImage($initialSelfieImage) {
        
        $base64Truncated = $initialSelfieImage;
        $base64 = "data:image/jpeg;base64," . $base64Truncated;
        $selfieImage = $base64;
        if (!str_starts_with($selfieImage, 'data:image/jpeg;base64,')) {
            $selfieImage = "data:image/jpeg;base64," . $selfieImage;
        }

        $initialImage = $this->saveBase64Image($selfieImage, 'initialkycselfieimages');
        return $initialImage;
    }

    public function appTransport(string $method = 'post', ?string $params = null,  object $objectData = null)
    {
       $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
            if (!in_array($method, $allowedMethods)) {
                throw new \InvalidArgumentException("Invalid HTTP method: $method");
            }

            $url = $params ? env('DOJAH_KYC_URL')  . '/' . $params : env('DOJAH_KYC_URL');

            Log::info(["Dojah KYC url" => $url]);
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
        return $this->fail = (object) [
            "status" => $status,
            "message" => $title
        ];
    }
    public function setSuccessState($status = 200, $title)
    {
        return $this->success = (object) [
            "status" => $status,
            "message" => $title
        ];
    }
    public function getFailState()
    {
        return $this->failstate;
    }


    
}