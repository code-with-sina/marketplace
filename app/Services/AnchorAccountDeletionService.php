<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Exception;



class AnchorAccountDeletionService 
{

    protected string $baseUrl;
    protected string $key;


    public function __construct() 
    {
        $this->baseUrl = rtrim(env('ANCHOR_SANDBOX'), '/');
        $this->key = env('ANCHOR_KEY');
    }

    public function deleteCustomer(string $customerId): object
    {
         $url = "{$this->baseUrl}/customers/{$customerId}";
         try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'x-anchor-key' => $this->key,
            ])->delete($url);
         } catch (Exception $e) {
            Log::error("Anchor  error: " . $e->getMessage());

            return (object)[
                'successful' => false,
                'statusCode' => 500,
                'data' => 'Unexpected error occurred'
            ];
         }

         return (object)[
            'successful' => $response->successful(),
            'statusCode' => $response->status(),
            'data' => $response->json(),
        ];
    }    
}