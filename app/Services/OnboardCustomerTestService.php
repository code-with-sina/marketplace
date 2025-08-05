<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\KycCheckJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\MetaPixelConversionService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;



class OnboardCustomerTestService
{

    protected User $user;

    private $state;
    protected $lastMethod;
    private $errorMessage;
    private $isEditMode                 = false;
    private $statefulError              = false;


    private ?string $tempImage          = null;
    private ?array $tempCollections     = null;


    const COUNTRY                       = "NG";
    const CUSTOMER_TYPE                 = "IndividualCustomer";
    const STATUS_OK                     = 200;
    const STATUS_BAD_REQUEST            = 400;


    
    

    public function __construct(?User $user = null) 
    {
        $this->user = $user ?? Auth::user();
        if(!$this->user) 
        {
            $this->setTrueErrorState(status: self::STATUS_BAD_REQUEST, title: __("User not found"));
        } 

    }


    public function acquireUserDataAndValidate(bool $edit = false)
    {
        if ($this->statefulError) {
            return $this;
        }

        $customer = $this->user->customerstatus()->first();
        $profile = $this->user->kycdetail()->first();

        if($profile == null) 
        {
            $this->setTrueErrorState(status: self::STATUS_BAD_REQUEST, title: __("You have no profile yet in our system"));
        }
        else
        {
            if(!is_null($customer) && $edit === false) 
            {
                $this->setTrueErrorState(status: self::STATUS_BAD_REQUEST, title: __("Sorry, we have already onboarded you. Please see an admin if there are any issues to resolve this."));
            }
            else 
            {

                $this->isEditMode = $edit;
                return $this;
            }
        }

        return $this;
    }


    public function createMember(array $collections, string $selfieimage): self 
    {
        if ($this->statefulError) {
            return $this;
        }

        $this->tempCollections = $collections;
        $this->tempImage = $selfieimage;


        if ($this->hasMissingFields(collections: $collections)) {
            return $this;
        }

        $this->processCreateMember(collections: $collections, selfieimage: $selfieimage);
        return $this;
    }


    public function validateLevelOneKyc() 
    {
        if ($this->statefulError) {
            return $this;
        }

        $account = $this->user->customerstatus()->first();
        $customers = $account->customer()->first();
        $kycPayload = $this->buildTierOnePayload(customers: $customers);
        $response = $this->transportClient(method: "post", params: $customers->customerId . '/verification/individual', objectData: $kycPayload);
        if ($response->statusCode === 200 || $response->statusCode === 202) {
            $this->user->customerstatus()->update(['status' => 'pending']);
            $this->user->authorization()->update(['kyc' => 'processing']);
            return $this;
        } else {
            $this->setTrueErrorState(status: $response->statusCode, title: $response->data);
        }
        return $this;
    }

    public function monitorKycStatus() 
    {
        if ($this->statefulError) {
            return $this;
        }

        $userTem = $this->user->tempKyc()->first();
        if ($userTem !== null) {
            $this->user->tempKyc()->update([
                'bvn'           => $this->tempCollections['bvn'],
                'selfie'        => $this->tempImage,
                'dateOfBirth'   => Carbon::parse($this->tempCollections['dateOfBirth'])->format('Y-m-d'),
                'gender'        => $this->tempCollections['gender'],
                'idNumber'      => $this->tempCollections['idNumber'],
                'idType'        => $this->tempCollections['idType'],
            ]);
        } else {
            $this->user->tempKyc()->create([
                'bvn'           => $this->tempCollections['bvn'],
                'selfie'        => $this->tempImage,
                'dateOfBirth'   => Carbon::parse($this->tempCollections['dateOfBirth'])->format('Y-m-d'),
                'gender'        => $this->tempCollections['gender'],
                'idNumber'      => $this->tempCollections['idNumber'],
                'idType'        => $this->tempCollections['idType'],
            ]);
        }

        KycCheckJob::dispatch($this->user->uuid)->delay(now()->addHours(3));
        $this->setSuccessState(status: 200, title: __("Your wallet has been successfully created! We're completing some background tasks and will notify you once everything is ready."));
        return $this;
    }

    public function throwStatus()
    {
         return $this->statefulError ?  $this->errorMessage : tap($this->state, fn() => $this->callMetaPixel() );
    }

    public function processCreateMember(array $collections, string $selfieimage) 
    {
        if ($this->isEditMode === true) {

            $payload = $this->buildPayload(collections: $collections, selfieimage: $selfieimage);
            $response = $this->transportClient(method: "put", objectData: $payload, params: "update/" . $this->user->customerstatus()->first()->customerId);

            if ($response->statusCode == 200 || $response->statusCode == 202) {
                $customers = $response->data;
                $this->updateCustomerStateAndOnboardCustomer(customers: $customers);
                $this->setSuccessState(status: 200, title: __("Your wallet now reflect the changes you made successfully"));
                return $this;
            } else {
                $this->setTrueErrorState(status: $response->statusCode, title: $response->data);
                return $this;
            }
        } else {
            $payload = $this->buildPayload(collections: $collections, selfieimage: $selfieimage);
            $response = $this->transportClient(method: "post", objectData: $payload, params: null);
            if ($response->statusCode == 200 || $response->statusCode == 202) {
                $customers = $response->data;
                $this->updateCustomerStateAndOnboardCustomer(customers: $customers);
                return $this;
            } else {
                $this->setTrueErrorState(status: $response->statusCode, title: $response->data);
                return $this;
            }
        }
    }


    public function buildTierOnePayload($customers)
    {
        return (object) [
            'data'  => [
                'type'  => 'Verification',
                'attributes' => [
                    'level' => 'TIER_2',
                    'level2'    => [
                        'bvn'           => $customers->bvn,
                        'selfie'        => $customers->selfie,
                        'dateOfBirth'   => $customers->dateOfBirth,
                        'gender'        => $customers->gender
                    ],
                    'level3'    => [
                        'idNumber'      => $customers->idNumber,
                        'idType'        => $customers->idType,
                    ],
                    "metadata" => [
                        'uuid'  => $this->user->uuid
                    ]
                ],

            ]
        ];
    }


    public function buildPayload(array $collections, string $selfieimage) 
    {
        $profile = $this->user->kycdetail()->first();
        return (object) [
            'data' => [
                'attributes'    => [
                    'fullName'  => [
                        'firstName' => $profile->first_name,
                        'lastName'  => $profile->last_name
                    ],
                    'address'       => [
                        'country'       => self::COUNTRY,
                        'state'         => $profile->state,
                        'city'          => $profile->city,
                        'postalCode'    => $profile->zip_code,
                        'addressLine_1'     =>  ($profile->house_number ?? null) . ' '.  $profile->street 
                    ],
                    'identificationLevel2'  => [
                        'dateOfBirth'   => Carbon::parse($collections['dateOfBirth'])->format('Y-m-d'),
                        'selfieImage'   => $selfieimage,
                        'gender'        => $profile->gender,
                        'bvn'           => $collections['bvn']
                    ],
                    'identificationLevel3'  => [
                        'idNumber'      => $collections['idNumber'],
                        'expiryDate'    => Carbon::parse($collections['expiryDate']),
                        'idType'        => $collections['idType'],
                    ],
                    'email'         => $this->user->email,
                    'phoneNumber'   => preg_replace('/^\+234/', '0', $this->user->mobile)
                ],
                'type'  => self::CUSTOMER_TYPE
            ]
        ];
    }

    public function updateCustomerStateAndOnboardCustomer($customers) 
    {
        $attributes = $this->extractCustomerAttributes($customers->data);
        if ($this->isEditMode === true) {
            $account = $this->user->customerstatus()->first();
            $account->customer()->update($attributes);
        } else {
            $account = $this->user->customerstatus()->create([
                'type'      => self::CUSTOMER_TYPE,
                'status'    => $customers->data->attributes->verification->status,
                'customerId' => $customers->data->id
            ]);

            $account->customer()->create(array_merge($attributes, [
                'registered' => Hash::make($customers->data->attributes->status . '-' . uniqid(time()))
            ]));
           
        }
    }

    private function hasMissingFields(array $collections): bool
    {
        $requiredFields = ['dateOfBirth', 'bvn', 'idNumber', 'expiryDate', 'idType'];
        foreach ($requiredFields as $field) {
            if (!isset($collections[$field])) {
                $this->setTrueErrorState(status: self::STATUS_BAD_REQUEST,  title: __("Missing required field: $field"));
                return true;
            }
        }
        return false;
    }

    public function transportClient(string $method = 'post', ?string $params = null,  object $objectData, $endpoint = 'customers') 
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url = $params ? env('ANCHOR_SANDBOX') . $endpoint . '/' . $params : env('ANCHOR_SANDBOX') . $endpoint;

        try {
            $customerObject = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->$method($url, $objectData);
            return (object)['statusCode' => $customerObject->status(), 'data' => $customerObject->object()];
        } catch (ConnectionException $e) {
            Log::error("Connection timeout: " . $e->getMessage());
            return (object)[
                'statusCode' => 500,
                'data' => 'Connection timeout. Please try again.',
            ];
        } catch (RequestException $e) {
            Log::error("HTTP request error: " . $e->getMessage());
            return (object)[
                'statusCode' => $e->response?->status() ?? 500,
                'data' => $e->response?->json() ?? 'Unexpected error occurred.',
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error: " . $e->getMessage());
            return (object)[
                'statusCode' => 500,
                'data' => 'Something went wrong. Please try again later.'
            ];
        }
    }


    private function extractCustomerAttributes($data): array
    {
        return [
        'customerId'    => $data->id,
        'customerType'  => $data->type,
        'firstName'     => $data->attributes->fullName->firstName,
        'lastName'      => $data->attributes->fullName->lastName,
        'address'       => $data->attributes->address->addressLine_1,
        'country'       => $data->attributes->address->country,
        'state'         => $data->attributes->address->state,
        'city'          => $data->attributes->address->city,
        'postalCode'    => $data->attributes->address->postalCode,
        'phoneNumber'   => $data->attributes->phoneNumber,
        'status'        => $data->attributes->status,
        'email'         => $data->attributes->email,
        'gender'        => $data->attributes->identificationLevel2->gender,
        'dateOfBirth'   => $data->attributes->identificationLevel2->dateOfBirth,
        'bvn'           => $data->attributes->identificationLevel2->bvn,
        'selfieImage'   => $data->attributes->identificationLevel2->selfieImage,
        'expiryDate'    => $data->attributes->identificationLevel3->expiryDate,
        'idType'        => $data->attributes->identificationLevel3->idType,
        'idNumber'      => $data->attributes->identificationLevel3->idNumber,
    ];
    }

    public function setTrueErrorState($status, $title)
    {
        $this->statefulError = true;
        $this->errorMessage = (object) [
            'status' => $status,
            'message' => $title
        ];


        return $this;
    }


    public function setSuccessState($status, $title)
    {
        $this->state = (object) [
            'status' => $status,
            'message' => $title
        ];

        return $this;
    }

    public function setSpecialMessage($status, $title) 
    {
        $this->statefulError = true;
        $this->state = (object)[

            'status' => $status,
            'message' => $title
        ];
        return $this;
    }


    public function callMetaPixel() 
    {
        app(MetaPixelConversionService::class)
        ->eventId($this->user->uuid)
        ->eventName('OnboardCustomer')
        ->eventTime(time())
        ->userData(email: $this->user->email, phone: $this->user->mobile,  customerIp: null, customerUserAgent: null, fbc: null, fbp: null)
        ->customData(userId: $this->user->id, actionTaken: 'Onboarding new customer', segment: 'Account Creattion', status: 'success')
        ->eventSourceURL(env('APP_URL'))
        ->actionSource('website')
        ->sendToMeta();
    }
}