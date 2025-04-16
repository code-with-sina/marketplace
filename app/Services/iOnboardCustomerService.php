<?php

namespace App\Services;

use Exception;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Jobs\CreateAccountJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\OnboardCustomerServiceTrack;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;


class OnboardCustomerService
{
    private $state;
    private $errorMessage;
    private $statefulError = false;
    private $editState = false;
    private $edit = false;
    const COUNTRY = 'NG';
    const CUSTOMER_TYPE = 'IndividualCustomer';
    protected $lastMethod;
    protected User $user;


    public function __construct()
    {
        $this->user = Auth::user();
        if (!$this->user) {

            throw new \Exception("User not authenticated.");
        }

        $this->lastMethod = $this->getLastFailedMethod();
    }

    public function acquireUserDataAndValidate(bool $edit = false)
    {

        return $this->executeMethod(__FUNCTION__, function () use ($edit) {
            $this->edit = $edit;
            $customer = $this->user->customerstatus()->first();
            $profile = $this->user->profile()->first();
            if ($profile == null) {
                $this->setTrueErrorState(status: 400, title: __("You have no profile yet in our system"), method: 'acquireUserDataAndValidate');
            } else {
                if (!is_null($customer) && $edit === false) {
                    $this->setTrueErrorState(status: 400, title: __("Sorry, we have already onboarded you. Please see an admin if there are any issues to resolve this."), method: 'acquireUserDataAndValidate');
                } else {

                    if ($this->edit === true) {
                        $this->editState = true;
                        return $this;
                    }

                    return $this;
                }
            }

            return $this;
        });
    }

    public function createMember(array $collections, string $selfieimage): self
    {
        return $this->executeMethod(__FUNCTION__, function () use ($collections, $selfieimage) {
            if ($this->statefulError) {
                return $this;
            }

            if ($this->hasMissingFields(collections: $collections)) {
                return $this;
            }

            $errorLog = OnboardCustomerServiceTrack::where('user_id', $this->user->id)->latest()->first();
            if ($errorLog !== null) {
                if ($errorLog->editState === 'true') {

                    $payload = $this->buildPayload(collections: $collections, selfieimage: $selfieimage);
                    $response = $this->transportClient(method: "put", objectData: $payload, params: "update/" . $this->user->customerstatus()->first()->customerId);

                    if ($response->statusCode == 200 || $response->statusCode == 202) {
                        $customers = $response->data;
                        $this->updateCustomerStateAndOnboardCustomer(customers: $customers);
                        $this->setSuccessState(status: 200, title: __("Your wallet now reflect the changes you made successfully"));
                        return $this;
                    } else {
                        $this->setTrueErrorState(status: $response->statusCode, title: $response->data, method: 'createMember');
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
                        $this->setTrueErrorState(status: $response->statusCode, title: $response->data, method: 'createMember');
                        return $this;
                    }
                }
            } else {
                if ($this->editState === true) {

                    $payload = $this->buildPayload(collections: $collections, selfieimage: $selfieimage);
                    $response = $this->transportClient(method: "put", objectData: $payload, params: "update/" . $this->user->customerstatus()->first()->customerId);

                    if ($response->statusCode == 200 || $response->statusCode == 202) {
                        $customers = $response->data;
                        $this->updateCustomerStateAndOnboardCustomer(customers: $customers);
                        $this->setSuccessState(status: 200, title: __("Your wallet now reflect the changes you made successfully"));
                        return $this;
                    } else {
                        $this->setTrueErrorState(status: $response->statusCode, title: $response->data, method: 'createMember');
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
                        $this->setTrueErrorState(status: $response->statusCode, title: $response->data, method: 'createMember');
                        return $this;
                    }
                }
            }

            return $this;
        });
    }

    public function validateLevelOneKyc()
    {

        return $this->executeMethod(__FUNCTION__, function () {
            if ($this->statefulError) {

                return $this;
            }

            $customers = $this->user->customerstatus()->first()->customer()->first();
            $kycPayload = $this->buildTierOnePayload(customers: $customers);
            $response = $this->transportClient(method: "post", params: $customers->customerId . '/verification/individual', objectData: $kycPayload);
            if ($response->statusCode === 200 || $response->statusCode === 202) {
                $this->user->customerstatus()->update(['status' => 'fully-verified']);
                $this->user->authorization()->update(['kyc' => 'approved']);
            } else {
                $this->setTrueErrorState(status: $response->statusCode, title: $response->data, method: 'validateLevelOneKyc');
            }
            return $this;
        });
    }

    public function createWallet()
    {
        return $this->executeMethod(__FUNCTION__, function () {
            if ($this->statefulError) {
                return $this;
            }

            CreateAccountJob::dispatch($this->user->uuid);
            $this->setSuccessState(status: 200, title: __("Your wallet has been successfully created! We're completing some background tasks and will notify you once everything is ready."));
            return $this;
        });
    }


    public function throwStatus()
    {
        return $this->executeMethod(__FUNCTION__, function () {

            return $this->statefulError ?  $this->errorMessage : (($this->state) && ($this->clearErrorLogs()));
        });
    }






    /* 
        Prepared Api Query Request
    */

    public function transportClient(string $method = 'post', ?string $params = null,  object $objectData, $endpoint = 'customers'): mixed
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url = $params ? env('ANCHOR_SANDBOX') . $endpoint . '/' . $params : env('ANCHOR_SANDBOX') . $endpoint;

        \Log::error($url);

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


    public function buildPayload(array $collections, string $selfieimage)
    {
        $profile = $this->user->profile()->first();
        return (object) [
            'data' => [
                'attributes'    => [
                    'fullName'  => [
                        'firstName' => $this->user->firstname,
                        'lastName'  => $this->user->lastname
                    ],
                    'address'       => [
                        'country'       => self::COUNTRY,
                        'state'         => $profile->state,
                        'city'          => $profile->city,
                        'postalCode'    => $profile->zip_code,
                        'addressLine_1'     =>  $profile->address
                    ],
                    'identificationLevel2'  => [
                        'dateOfBirth'   => Carbon::parse($collections['dateOfBirth'])->format('Y-m-d'),
                        'selfieImage'   => $selfieimage,
                        'gender'        => $profile->sex,
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
                ]
            ]
        ];
    }


    private function updateCustomerStateAndOnboardCustomer($customers): void
    {
        if ($this->editState === true) {
            $this->user->customerstatus()->first()->customer()->update([
                'customerId'    => $customers->data->id,
                'customerType'  => $customers->data->type,
                'firstName'     => $customers->data->attributes->fullName->firstName,
                'lastName'      => $customers->data->attributes->fullName->lastName,
                'address'       => $customers->data->attributes->address->addressLine_1,
                'country'       => $customers->data->attributes->address->country,
                'state'         => $customers->data->attributes->address->state,
                'city'          => $customers->data->attributes->address->city,
                'postalCode'    => $customers->data->attributes->address->postalCode,
                'phoneNumber'   => $customers->data->attributes->phoneNumber,
                'status'        => $customers->data->attributes->status,
                'email'         => $customers->data->attributes->email,
                'gender'        => $customers->data->attributes->identificationLevel2->gender,
                'dateOfBirth'   => $customers->data->attributes->identificationLevel2->dateOfBirth,
                'bvn'           => $customers->data->attributes->identificationLevel2->bvn,
                'selfieImage'   => $customers->data->attributes->identificationLevel2->selfieImage,
                'expiryDate'    => $customers->data->attributes->identificationLevel3->expiryDate,
                'idType'        => $customers->data->attributes->identificationLevel3->idType,
                'idNumber'      => $customers->data->attributes->identificationLevel3->idNumber,
                // 'registered'    => Hash::make($customers->data->attributes->status . '-' . uniqid())
            ]);
        } else {
            $this->user->customerstatus()->create([
                'type'      => self::CUSTOMER_TYPE,
                'status'    => $customers->data->attributes->verification->status,
                'customerId' => $customers->data->id
            ]);
            // $this->user->customerstatus()->update(['status' => $customers->data->attributes->verification->status, 'customerId' => $customers->data->id]);
            $this->user->customerstatus()->first()->customer()->create([
                'customerId'    => $customers->data->id,
                'customerType'  => $customers->data->type,
                'firstName'     => $customers->data->attributes->fullName->firstName,
                'lastName'      => $customers->data->attributes->fullName->lastName,
                'address'       => $customers->data->attributes->address->addressLine_1,
                'country'       => $customers->data->attributes->address->country,
                'state'         => $customers->data->attributes->address->state,
                'city'          => $customers->data->attributes->address->city,
                'postalCode'    => $customers->data->attributes->address->postalCode,
                'phoneNumber'   => $customers->data->attributes->phoneNumber,
                'status'        => $customers->data->attributes->status,
                'email'         => $customers->data->attributes->email,
                'gender'        => $customers->data->attributes->identificationLevel2->gender,
                'dateOfBirth'   => $customers->data->attributes->identificationLevel2->dateOfBirth,
                'bvn'           => $customers->data->attributes->identificationLevel2->bvn,
                'selfieImage'   => $customers->data->attributes->identificationLevel2->selfieImage,
                'expiryDate'    => $customers->data->attributes->identificationLevel3->expiryDate,
                'idType'        => $customers->data->attributes->identificationLevel3->idType,
                'idNumber'      => $customers->data->attributes->identificationLevel3->idNumber,
                'registered'    => Hash::make($customers->data->attributes->status . '-' . uniqid())
            ]);
        }
    }


    private function hasMissingFields(array $collections): bool
    {
        $requiredFields = ['dateOfBirth', 'bvn', 'idNumber', 'expiryDate', 'idType'];
        foreach ($requiredFields as $field) {
            if (!isset($collections[$field])) {
                $this->setTrueErrorState(status: 400,  title: __("Missing required field: $field"), method: 'hasMissingFields');
                return true;
            }
        }
        return false;
    }






    /* 
        Stagging Errors for User view
    */
    public function setTrueErrorState(mixed $title, int $status = 400, $method)
    {
        $this->statefulError = true;
        $this->logError($method, json_encode($this->errorMessage) ?? null);
        $this->errorMessage = (object) [
            'status' => $status,
            'title' => $title
        ];

        return $this;
    }

    public function setSuccessState($status = 200, $title)
    {
        $this->state = (object) [
            'status' => $status,
            'title' => $title
        ];
        return $this;
    }


    public function setSpecialMessage($status = 200, $title)
    {
        $this->statefulError = true;
        $this->state = (object)[

            'status' => $status,
            'title' => $title
        ];
        return $this;
    }





    /* 
        Stagging Error for Database Tracking
    */


    protected function getLastFailedMethod()
    {
        $errorLog = OnboardCustomerServiceTrack::where('user_id', $this->user->id)->latest()->first();
        $this->editState = $errorLog && $errorLog->editState !== null ? $errorLog->editState : false;
        return $errorLog ? $errorLog->method : null;
    }

    protected function logError($method, $errorMessage)
    {
        OnboardCustomerServiceTrack::create([
            'user_id'       => $this->user->id,
            'method'        => $method,
            'error_message' => $errorMessage,
            'editState'     => $this->editState ? 'true' : 'false',
            'statefulError' => $this->statefulError ? 'true' : 'false'
        ]);
    }


    protected function clearErrorLogs()
    {
        OnboardCustomerServiceTrack::where('user_id', $this->user->id)->delete();
    }


    /* 
        Track and Execute Sequential Methods
    */

    protected function executeMethod($method, $callback)
    {
        if ($this->lastMethod && $method !== $this->lastMethod) {
            return $this;
        }

        $this->lastMethod = null;

        try {
            return $callback();
        } catch (Exception $e) {
            $this->logError($method, $e->getMessage());
            $this->setTrueErrorState($e->getMessage(), 500, $method);
        }

        return $this;
    }
}
