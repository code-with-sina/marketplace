<?php

namespace App\Http\Controllers;


use App\Audit\Trail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use stdClass;

class OfferController extends Controller
{

    public function checkHeader($requestHeader) {
        if( $requestHeader !== 'Ratefy') {
            return false;
        }else {
            return true;
        }
    }

    // public function getUserAuthorization(Request $request) {
    //     $marks = Str::ulid();
    //     Trail::post(
    //         url: $request->url(), 
    //         ip: $request->ip(),  
    //         mark: $marks, 
    //         method: $request->method(), 
    //         action: __FUNCTION__, 
    //         post: $request->collect() ?? null, 
    //         uuid: @auth()->user()->uuid ?? null
    //     );
    //     $detail = Http::post('https://userbased.ratefy.co/api/detail', [
    //         'uuid'  => auth()->user()->uuid
    //     ])->throw();
    //     $data = $detail->object();

    //     $payload = [
    //         'authorization' => $data->data->authorization->priviledge,
    //         'kyc_authorization' => $data->data->authorization->kyc->status,
    //         'kyc_authorization_badge' => $data->data->authorization->kyc->badge
    //     ];

    //     $getObject = $this->authPayload($payload);
    //     Trail::retrieve(mark: $marks, retrieveData:  $getObject ?? null);
    //     return $getObject;
    // }

    public function ewallet(Request $request) {
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
                $response = Http::get('https://offerbased.ratefy.co/api/fetch-ewallet')->throw();

                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function createBuyerOffer(Request $request)
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

            $validation = Validator::make($request->all(), [
                'ewallet_id'    =>      ['required'],
                'option_id'     =>      ['required'],
                'percentage'    =>      ['nullable'],
                "fixed_rate"    =>      ['nullable'],
                "min_amount"    =>      ['required'],
                "max_amount"    =>      ['required'],
                "duration"      =>      ['required'],
                "guide"         =>      ['required', 'string'],
                "buyer_offer_requiremnet"   => ['json','required'],
                "buyer_terms_and_conditions" => ['json', 'required']
            ]);


            if($validation->fails()){
                Trail::retrieve(mark: $marks, retrieveData:  $validation->errors() ?? null);
                Trail::log(user: @auth()->user()->uuid, errorTrace: $validation->errors(), traceId: $marks, action: __FUNCTION__ );
                return response()->json([
                    'response'  => $validation->errors()
                ]);  
            }

            try {
                $response = Http::post('https://offerbased.ratefy.co/api/create-buyer-offer', [
                    'uuid'  => auth()->user()->uuid,
                    'ewallet_id'    =>      $request->ewallet_id,
                    'option_id'     =>      $request->option_id,
                    'percentage'    =>      $request->percentage,
                    "fixed_rate"    =>      $request->fixed_rate,
                    "min_amount"    =>      $request->min_amount,
                    "max_amount"    =>      $request->max_amount,
                    "duration"      =>      $request->duration,
                    "guide"         =>      $request->guide,
                    "buyer_offer_requiremnet"   => $request->buyer_offer_requiremnet,
                    "buyer_terms_and_conditions" => $request->buyer_terms_and_conditions
        
                ])->throw();
                
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200,
                ], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }

        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 
          
    }

    public function createSellOffer(Request $request)
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

            $validation = Validator::make($request->all(), [
                'ewallet_id'    =>      ['required'],
                'option_id'     =>      ['required'],
                'percentage'    =>      ['nullable'],
                "fixed_rate"    =>      ['nullable'],
                "min_amount"    =>      ['required'],
                "max_amount"    =>      ['required'],
                "duration"      =>      ['required'],
                "guide"         =>      ['required', 'string'],
                "seller_offer_requiremnet"   => ['json', 'required'],
                'seller_terms_and_conditions' => ['json', 'required']
            ]);
            
            if($validation->fails()) {
                Trail::retrieve(mark: $marks, retrieveData:  $validation->errors() ?? null);
                Trail::log(user: @auth()->user()->uuid, errorTrace: $validation->errors(), traceId: $marks, action: __FUNCTION__ );
                return response()->json([
                    'response'  => $validation->errors()
                ]);  
            }
    
    
            


            try {
                $response = Http::post('https://offerbased.ratefy.co/api/create-seller-offer', [
               
                    'uuid'          => auth()->user()->uuid,
                    'ewallet_id'    =>      $request->ewallet_id,
                    'option_id'     =>      $request->option_id,
                    'percentage'    =>      $request->percentage,
                    "fixed_rate"    =>      $request->fixed_rate,
                    "min_amount"    =>      $request->min_amount,
                    "max_amount"    =>      $request->max_amount,
                    "duration"      =>      $request->duration,
                    "guide"         =>      $request->guide,
                    "seller_offer_requiremnet"   => $request->seller_offer_requiremnet,
                    'seller_terms_and_conditions' => $request->seller_terms_and_conditions
                ])->throw();
                
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(), 
                    'status'    => 200
                ], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 

    }


    public function fetchPaymentOptions(Request $request)
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
                $response = Http::post('https://offerbased.ratefy.co/api/get-payment-options', [
                    'uuid'  => auth()->user()->uuid,
                    
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

    public function fetchPaymentOptionsRequirement(Request $request)
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
                'option'        =>      ['string', 'required'],
            ]);
            

            try {
                $response = Http::post('https://offerbased.ratefy.co/api/get-payment-options-requirement', [
                    'uuid'  => auth()->user()->uuid,
                    'option'      =>  $request->option
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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
    
    public function fetchBuyerOffer(Request $request)
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-buyer-offer', [
                    'uuid'  => auth()->user()->uuid
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function pauseBuyerOffer(Request $request)
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
                'id'    => ['required']
            ]);
            

            try {
                $response = Http::post('https://offerbased.ratefy.co/api/pause-buyer-offer', [
                    'uuid'  => auth()->user()->uuid,
                    'id'    => $request->id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function reactivateBuyerOffer(Request $request)
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
                'id'    => ['required']
            ]);
            
            try {
                $response = Http::post('https://offerbased.ratefy.co/api/reactivate-buyer-offer', [
                    'uuid'  => auth()->user()->uuid,
                    'id'     => $request->id   
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function createBuyerOfferTerms(Request $request)
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
                'id'        => ['required'],
                'title'     => ['required', 'string'],
                'condition' => ['required', 'string']
            ]);
    
            try {
                $response = Http::post('https://offerbased.ratefy.co/api/create-buyer-offer-terms', [
                    'uuid'  => auth()->user()->uuid,
                    'id'        => $request->id,
                    'title'     => $request->title,
                    'condition' => $request->condition
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function deleteBuyerOfferTermsDelete(Request $request)
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
                'id'        => ['required'],
                'term_id'   => ['required']
            ]);

            try {
                $response = Http::post('https://offerbased.ratefy.co/api/delete-buyer-offer-terms', [
                    'uuid'      => auth()->user()->uuid,
                    'id'        => $request->id,
                    'term_id'   => $request->term_id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function fetchSellerOffer(Request $request)
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-seller-offer', [
                    'uuid'  => auth()->user()->uuid
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function pauseSellerOffer(Request $request)
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
                'id'    =>  ['required']
            ]);  
    
            try {
                $response = Http::post('https://offerbased.ratefy.co/api/pause-seller-offer', [
                    'uuid'  => auth()->user()->uuid,
                    'id'    => $request->id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
        
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

    public function reactivateSellerOffer(Request $request)
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
                $request->validate([
                    'id'    =>  ['required']
                ]); 
        
                $response = Http::post('https://offerbased.ratefy.co/api/reactivate-seller-offer', [
                    'uuid'  => auth()->user()->uuid,
                    'id'    => $request->id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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


    public function createSellerOfferTerms(Request $request)
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
                $request->validate([
                    'id'        => ['required'],
                    'title'     => ['required', 'string'],
                    'condition' => ['required', 'string']
                ]);
        
                $response = Http::post('https://offerbased.ratefy.co/api/create-seller-offer-terms', [
                    'uuid'  => auth()->user()->uuid,
                    'id'        => $request->id,
                    'title'     => $request->title,
                    'condition' => $request->condition
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function deleteSellerOfferTerms(Request $request)
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
                $request->validate([
                    'id'        => ['required'],
                    'term_id'   => ['required'],
                ]);
        
                $response = Http::post('https://offerbased.ratefy.co/api/delete-seller-offer-terms', [
                    'uuid'      => auth()->user()->uuid,
                    'id'        => $request->id,
                    'term_id'   => $request->term_id,
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    


    public function filterEwallet(Request $request) {
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
                $response = Http::get('https://offerbased.ratefy.co/api/filter-ewallet')->throw();

                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function authPayload($load) {
        $payload = new stdClass();
        $payload = $load;

        return $payload;
    }


    public function fetchSingleBuyerTerm(Request $request, $id) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-single-buyer-term', [
                    'id' => $id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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


    public function fetchSingleSellerTerm(Request $request, $id) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-single-seller-term', [
                    'id' => $id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function offerSellerItem(Request $request){
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-single-offer-seller-detail', [
                    'id' => $request->id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
    
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

    public function offerBuyerItem(Request $request){
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-single-offer-buyer-detail', [
                    'id' => $request->id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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


    public function editBuyerOffer(Request $request) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/edit-buyer-offer', [
                    'offerId'           =>  $request->offerId,
                    'guide'             =>  $request->guide,
                    'duration'          =>  $request->duration,
                    'min_amount'        =>  $request->min_amount,
                    'max_amount'        =>  $request->max_amount,
                    'percentage'        =>  $request->percentage,
                    'fixed_rate'        =>  $request->fixedrate,
                    'ewallet_id'        =>  $request->ewallet_id,
                    'option_id'         =>  $request->option_id,
                    "buyer_offer_requiremnet"   => $request->buyer_offer_requiremnet,
                    "buyer_terms_and_conditions" => $request->buyer_terms_and_conditions
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function deleteBuyerOffer(Request $request) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/delete-buyer-offer', [
                    'id' => $request->offerId
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function editSellerOffer(Request $request) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/edit-seller-offer', [
                    'offerId'           =>  $request->offerId, 
                    'guide'             =>  $request->guide,
                    'duration'          =>  $request->duration,
                    'min_amount'        =>  $request->min_amount,
                    'max_amount'        =>  $request->max_amount,
                    'percentage'        =>  $request->percentage,
                    'fixed_rate'        =>  $request->fixedrate,
                    'ewallet_id'        =>  $request->ewallet_id,
                    'option_id'         =>  $request->option_id,
                    "seller_offer_requiremnet"   => $request->seller_offer_requiremnet,
                    'seller_terms_and_conditions' => $request->seller_terms_and_conditions
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'status'    => 200,
                    
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

    public function deleteSellerOffer(Request $request) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/delete-seller-offer', [
                    'id' => $request->offerId
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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

    public function fetchSingleBuyerOffer(Request $request, $id) {
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
                $response = Http::get('https://offerbased.ratefy.co/api/fetch-single-buyer-offer/'.$id)->throw();

                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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


    Public function fetchSingleSellerOffer(Request $request, $id) {
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
                $response = Http::get('https://offerbased.ratefy.co/api/fetch-single-seller-offer/'.$id)->throw();

                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
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


    public function fetchSingleBuyingOffer(Request $request, $id) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-single-buying-offer', [
                    'id'    => $id
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData: $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'we' => 'we got here',
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


    public function fetchSingleSellingOffer(Request $request, $id) {
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
                $response = Http::post('https://offerbased.ratefy.co/api/fetch-single-selling-offer', [
                    'id'    => $id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response'  => $response->object(),
                    'we' => 'we got here',
                    'status'    => 200
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



    public function testWork(Request $request) {
        $paymentOption = $this->getSellerItemOption($request->id);
        return response()->json([
            'data' => $paymentOption->data->paymentoption->option
        ]);
    }

    
    public function getSellerItemOption($id) {
        try {
            $data = Http::post('https://offerbased.ratefy.co/api/fetch-single-offer-seller-detail', [
                'id' => $id
            ])->throw();
    
            return $data->object();
        }catch(Exception $e){
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
        }
    }
}
