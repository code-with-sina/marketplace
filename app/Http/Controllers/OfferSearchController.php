<?php

namespace App\Http\Controllers;


use App\Audit\Trail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OfferSearchController extends Controller
{

    public function checkHeader($requestHeader) {
        if( $requestHeader !== 'Ratefy') {
            return false;
        }else {
            return true;
        }
    }

    public function ewalletSearchForBuyers(Request $request, $ewallet_id, $payment_option_id)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );

        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            try {
                $response = Http::get('https://offerbased.ratefy.co/api/search-buyer-ewallet/'.$ewallet_id.'/'.$payment_option_id.'')->throw();

                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }

            
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 

    }

    public function ewalletBuyerSearchNextPage(Request $request, $ewallet_id, $payment_option_id, $pageNumber) {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            
            try {
                $response = Http::get('https://offerbased.ratefy.co/api/search-buyer-ewallet/'.$ewallet_id.'/'.$payment_option_id.'?page%5Bnumber%5D='.$pageNumber)->throw();

                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);

                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
            
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response(['message' => 'bad header'], 400);
        } 
    }


    public function ewalletBuyerSearchPreviousPage(Request $request, $ewallet_id, $payment_option_id, $pageNumber) {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            try {
                $response = Http::get('https://offerbased.ratefy.co/api/search-buyer-ewallet/'.$ewallet_id.'/'.$payment_option_id.'?page%5Bnumber%5D='.$pageNumber)->throw();

                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);

                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response(['message' => 'bad header'], 400);
        } 
    }

    public function ewalletSearchForSellers(Request $request, $ewallet_id, $payment_option_id)
    {


        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            
            try {
                $response = Http::get('https://offerbased.ratefy.co/api/search-seller-ewallet/'.$ewallet_id.'/'.$payment_option_id.'')->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response(['message' => 'bad header'], 400);
        } 
    }
    

    public function ewalletSellerSearchNextPage(Request $request, $ewallet_id, $payment_option_id, $pageNumber) 
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){

            

            try {
                $response = Http::get('https://offerbased.ratefy.co/api/search-seller-ewallet/'.$ewallet_id.'/'.$payment_option_id.'?page%5Bnumber%5D='.$pageNumber)->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response(['message' => 'bad header'], 400);
        }
    }


    public function ewalletSellerSearchPreviousPage(Request $request, $ewallet_id, $payment_option_id, $pageNumber) 
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            
            try {
                $response = Http::get('https://offerbased.ratefy.co/api/search-seller-ewallet/'.$ewallet_id.'/'.$payment_option_id.'?page%5Bnumber%5D='.$pageNumber)->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response(['message' => 'bad header'], 400);
        }
    }


    public function searchForBuyerPaymentOption(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            $request->validate([
                'query'     => ['required', 'string']
            ]);
    
            try {
                $response = Http::post('https://offerbased.ratefy.co/api/search-buyer-payment-option', [
                    'query'  => $request->query
                ])->throw();
                
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData: 'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 

    }

    public function searchForSellerPaymentOption(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            $request->validate([
                'query'     => ['required', 'string']
            ]);
    

            try {
                $response = Http::post('https://offerbased.ratefy.co/api/search-seller-payment-option', [
                    'query'  => $request->query
                ])->throw();
                
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }  
    }

    public function searchForBuyerRequirements(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            $request->validate([
                'query'     => ['required', 'string']
            ]);
    
            try {
                $response = Http::post('https://offerbased.ratefy.co/api/search-buyer-requirement', [
                    'query'  => $request->query
                ])->throw();
                
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 
    }

    public function searchSellerRequirement(Request $request)
    {

        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null
        );


        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            $request->validate([
                'query'     => ['required', 'string']
            ]);
    

            try {
                $response = Http::post('https://offerbased.ratefy.co/api/search-seller-requirement', [
                    'query'  => $request->query
                ])->throw();
                
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 
    }
}
