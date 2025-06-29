<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use stdClass;

class MetaPixelConversionService
{
    protected $failed = false;
    protected $success;
    protected $metaPixelData;

    public function __construct()
    {
        $this->metaPixelData = new stdClass();
        $this->metaPixelData->data = new stdClass(); 
    }

    public function eventId($id) 
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($id)) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be empty.'));
        }

        $this->metaPixelData->data->event_id = $id;

        return $this;
    }


    public function eventName($name) 
    {

        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($name)) {
            return $this->setFailedState(status: 400, message: __('Event name cannot be empty.'));
        }

        $this->metaPixelData->data->event_name = $name;
        
        return $this;
    }

    

    public function eventTime($time)
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($time) || !is_numeric($time)) {
            return $this->setFailedState(status: 400, message: __('Event time must be a valid timestamp.'));
        }

        $this->metaPixelData->data->event_time = $time;


        return $this;
    }

    public function eventSourceURL($sourceURL) 
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($sourceURL)) {
            return $this->setFailedState(status: 400, message: __('Event source URL cannot be empty.'));
        }

        $this->metaPixelData->data->event_source_url = $sourceURL;

        return $this;
    }


    public function actionSource($appSource = 'website') 
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($appSource)) {
            return $this->setFailedState(status: 400, message: __('Action source cannot be empty.'));
        }

        $this->metaPixelData->data->action_source = $appSource;

        return $this;
    }


    public function userData($email, $phone,  $customerIp, $customerUserAgent, $fbc, $fbp) 
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($email) && empty($phone)) {
            return $this->setFailedState(status: 400, message: __('At least one user data field (email or phone) must be provided.'));
        }

        $this->metaPixelData->data->user_data = (object)[
            
            'em' => [hash('sha256', $email)],
            'ph' => [hash('sha256', $phone)],
            'client_ip_address' => $customerIp ?? null,
            'client_user_agent' => $customerUserAgent ?? null,
            'fbc'   => $fbc ?? null,
            'fbp'   => $fbp ?? null,
        ];

        return $this;
    }
   

    public function customData($actionTaken, $segment, $status, $userId) 
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }

        if (empty($actionTaken)) {
            return $this->setFailedState(status: 400, message: __('actionTaken cannot be empty.'));
        }

        if (empty($segment)) {
            return $this->setFailedState(status: 400, message: __('segment cannot be empty.'));
        }

        if (empty($status)) {
            return $this->setFailedState(status: 400, message: __('status cannot be empty.'));
        }

        $this->metaPixelData->data->custom_data = (object)[
            'user_id'   => $userId,
            'action_taken'  => $actionTaken ?? null,
            'segment'       => $segment ?? null,
            'status'    => $status ?? null
        ];

        return $this;
    }


    public function sendToMeta() 
    {
        if ($this->failed) {
            return $this->setFailedState(status: 400, message: __('Event ID cannot be set due to previous failure.'));
        }


        $payload = [
            'data' => [$this->metaPixelData->data],
            'access_token' => env('META_PIXEL_ACCESS_TOKEN'),
        ];
        $response = $this->appTransport(method: 'post', objectData: $payload);

        return $this->setSuccessState(status: $response->statusCode, message: [$response->data, $this->metaPixelData]);
    }

    public function appTransport(string $method = 'post',  $objectData) 
    {
        

        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException("Invalid HTTP method: $method");
        }

        $url =  env('META_PIXEL_API_URL')  . env('META_PIXEL_API_VERSION') . "/" . env('META_PIXEL_ID') . "/events?access_token=" . env('META_PIXEL_ACCESS_TOKEN');

        Log::error($url);

        try {
            $customerObject = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
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


    public function setFailedState($status, $message) 
    {
        $this->failed = true;
        return (object) [
            'status' => $status,
            'message' => $message
        ];
    }

    public function setSuccessState($status, $message) 
    {
        return (object) [
            'status' => $status,
            'message' => $message
        ];
    }



}