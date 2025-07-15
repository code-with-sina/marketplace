<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ThirdPartyServiceStatusController extends Controller
{
    
    public function callGetAnchorStatus() {
        $response = $this->callApiTransport("https://api.getanchor.co/api/v1/events/event-types");
        return response()->json($response->message, $response->status);
    }


    public function callApiTransport($url) 
    {
            try {
                $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-anchor-key' => env('ANCHOR_KEY'),
            ])->timeout(5)->get($url);
                return (object) [
                    'message'   => [
                        'url'   => $url,
                        'status_code'   => $response->status(),
                        'message'       => $response->successful() ? "Website is up" : "Website responded with an error"
                    ],
                    'status'    => 200
                ];
            }catch(Exception $e) {
                return (object) [
                    'message' => [
                        'url'           => $url,
                        'status_code'   => null,
                        'message'       => 'Website is down or unreachable',
                        'error'         => $e->getMessage()
                    ], 
                    'status' => 500
                ];
            }
    }
}
