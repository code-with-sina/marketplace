<?php

namespace App\Http\Controllers;


use App\Audit\Trail;
use Illuminate\Support\Str;
use App\Jobs\CreateWallet;
use App\Otp\OneTimePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class WalletController extends Controller
{

    public function checkHeader($requestHeader) {
        if($requestHeader !== 'Ratefy') {
            return false;
        }else {
            return true;
        }
    }


    public function createWalletandValidateKyc(Request $request){

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
                "selfieimage"   => ['required', 'mimes:png,jpg,jpeg', 'max:2048'],        
                "dateOfBirth"   => ['required', 'string'],        
                "bvn"           => ['required', 'string'],        
                "idNumber"      => ['required', 'string'],            
                "idType"        => ['required', 'string'],        
                "gender"        => ['required', 'string']  
            ]);
    
                if($validation->fails()) {
                    return response()->json([
                        'status' => ["failed", $validation->errors()]
                    ]);
                }else {
                    if($request->has('selfieimage')){

                        try {
                            $filename = time() . '.' . $request->selfieimage->extension();
                            $request->selfieimage->move(public_path('upload/validation/'), $filename);
                        
                            $status = Http::post('https://walletbased.ratefy.co/api/customer/create', [
                                'uuid' => auth()->user()->uuid,
                                "selfieimage"   => 'https://p2p.ratefy.co/upload/validation/'.$filename,        
                                "dateOfBirth"   => $request->dateOfBirth,        
                                "bvn"           => $request->bvn,        
                                "idNumber"      => $request->idNumber,        
                                "expiryDate"    => $request->expiryDate ?? null,       
                                "idType"        => $request->idType,        
                                "gender"        => $request->gender  
                            ])->throw();
                            

                            $state = $status->object();
                            if($state->status == 200) {

                                Trail::retrieve(mark: $marks, retrieveData:  $status->object() ?? null);
                                return response()->json(['data' => $status->object(), "status" => 200]);
                            }else {
                                Trail::log(user: @auth()->user()->uuid, errorTrace: $status->object(), traceId: $marks, action: __FUNCTION__ );
                                Trail::retrieve(mark: $marks, retrieveData:  $status->object() ?? null);
                                return response()->json(['data' => $status->object(), "status" => 400]);
                            } 
                        }catch(Exception $e){
                            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
                        }
                        
                }
                
            }
        }else{
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response()->json(['message' => 'bad header'], 400);

        }
    }

    public function checkKycValidity(Request $request) {
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

        try {
            $status = Http::post('https://walletbased.ratefy.co/api/customer/validate-user', [
                'uuid' => auth()->user()->uuid
            ])->throw();
    
            Trail::retrieve(mark: $marks, retrieveData:  $status->body() ?? null);
            return response()->json(['status' => $status->body()]);
        }catch(Exception $e){
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
        }
    }

    public function fetchbankList(Request $request) {

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
                $wallet = Http::get('https://walletbased.ratefy.co/api/customer/get-bank-list')->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $wallet->object() ?? null);
                return response()->json(['wallet' => $wallet->object()]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
            
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        } 
    }

    public function fetchWallet(Request $request)
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
                $wallet = Http::post('https://walletbased.ratefy.co/api/customer/fetch-wallet', ['uuid' => auth()->user()->uuid])->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $wallet->object() ?? null);
                return response()->json(['wallet' => $wallet->object()]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
            
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        } 
    }



    public function fetchExpositionAccount(Request $request)
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
                $wallet = Http::post('https://walletbased.ratefy.co/api/customer/fetch-exposition-account', ['uuid' => auth()->user()->uuid])->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $wallet->object() ?? null);
                return response()->json(['wallet' => $wallet->object()]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        } 
    }

    public function addBank(Request $request)
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
                'accountnumber'     => ['required', 'string'],
                'bank_name'         => ['required', 'string'],
                'nipcode'           => ['required', 'string'],
                'bank_id'           => ['required', 'string']
            ]);
    
            

            try {
                $response = Http::post('https://walletbased.ratefy.co/api/customer/trade/create-counter-party-account', [
                    "uuid"              => auth()->user()->uuid, 
                    "accountnumber"     => $request->accountnumber,
                    "accountname"       => $request->bank_name,
                    "bankcode"           => $request->nipcode,
                    "id"                => $request->bank_id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json(['data' => $response->object(), 'status' => 200], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }

    public function getBankAccounts(Request $request) {
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
                $response = Http::post('https://walletbased.ratefy.co/api/customer/get-counter-party-account', [
                    "uuid"              => auth()->user()->uuid, 
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json(['data' => $response->object()]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }

    }

    public function deleteBankAccount(Request $request) {

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
                $response = Http::post('https://walletbased.ratefy.co/api/customer/delete-counter-party-account', [
                    "uuid"              => auth()->user()->uuid,
                    'bankaccountid'           => $request->bank_account_id
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json(['data' => $response->object()]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
            
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }

    }


    public function getBalance(Request $request)
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
                $response = Http::post('https://walletbased.ratefy.co/api/customer/escrow/get-balance', [
                    "uuid"  => auth()->user()->uuid
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response' => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }


    public function getPersonalBalance(Request $request)
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
                $response = Http::post('https://walletbased.ratefy.co/api/customer/personal/get-balance', [
                    "uuid"  => auth()->user()->uuid
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response' => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }


    // question to ask
    public function buyCurrency(Request $request)
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
                'amount'    => ['required']
            ]);
    
            try {
                $response = Http::post('https://walletbased.ratefy.co/api/buy-ewallet-fund', [
                    "uuid"  => auth()->user()->uuid,
                    "amount" => $request->amount
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response' => $response->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }


    public function withdrawal(Request $request) {

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
                'amount'        => ['required', 'string'], 
                'accountId'     => ['required', 'string'],
            ]);

            $balance = $this->fetchBalance();
            if($balance['status'] === 400) {
                return response()->json(['response'  => $balance['data']], 400);
            }else {
                if((int)$balance['data'] >= (int)$request->amount) {
                    if($validation->fails()){
                        Trail::retrieve(mark: $marks, retrieveData:  $validation->errors() ?? null);
                        return response()->json([
                            'status' => $validation->errors()
                        ]);
                    }else {
                        $check = OneTimePassword::initProcess(amount: $request->amount, accountId: $request->accountId);
                        return response()->json(['status' => 200, 'message' => $check]);
                        
                    }
                }else {
                    Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
                    Trail::retrieve(mark: $marks, retrieveData:  'insufficient fund' ?? null);
                    return response()->json(['response'  => 'insufficient fund'], 400);
                } 
                
            }
            
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }


    public function confirmOtp(Request $request){
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
        $check = OneTimePassword::confirmPassword(otp: $request->otp);
        if($check['status'] == 200){
            try {
                $response = Http::post('https://walletbased.ratefy.co/api/customer/trade/withdrawal', [
                    "uuid"          => auth()->user()->uuid,
                    "amount"        => $check['message']->amount,
                    'accountId'     => $check['message']->accountId,
                ])->throw();

                Trail::retrieve(mark: $marks, retrieveData:  $response->object() ?? null);
                return response()->json([
                    'response' => $response->object(),
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            return response()->json($check);
        }
        
    }

    public function resendOtp(Request $request) {
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
            $check = OneTimePassword::reProcess(tally: $request->hash);
            return response()->json(['status' => 200, 'message' => $check]);
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }


    public function getUserAuthorization(Request $request) {

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

        try {
            $detail = Http::post('https://userbased.ratefy.co/api/detail', [
                "uuid" => auth()->user()->uuid
            ])->throw();
            $data = $detail->object();
    
            $getObject = $data->data->authorization->type;
    
            $status =  $getObject;
            if($status !== "freelance") {
                if($status !== "both") {
    
                    Trail::retrieve(mark: $marks, retrieveData:  $status ?? null);
                    return response()->json(['message' => 'start by chosing a category', 'status_auth' => $status], 400);
                }else {
    
                    Trail::retrieve(mark: $marks, retrieveData:  $getObject ?? null);
                    return $getObject;
                }
            }
        }catch(Exception $e){
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
        }
       

    }

    public function fetchBalance() {
        $marks = Str::ulid();
        try {
            $balance = Http::post('https://walletbased.ratefy.co/api/customer/personal/get-balance', [
                'uuid' => auth()->user()->uuid
            ])->throw()->json();

            if(empty($balance['data']['id']) == false) {
                return  ['status' => 200, 'data' => $balance['data']['availableBalance']];
            }else {
                return ['status' => 400, 'data' => $balance['data']['errors'][0]['detail']];
            }

            Trail::log(user: @auth()->user()->uuid, errorTrace: $balance['data'], traceId: $marks, action: __FUNCTION__ );
        }catch(Exception $e){
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
        }
    }
}
