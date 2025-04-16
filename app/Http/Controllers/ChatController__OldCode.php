<?php

namespace App\Http\Controllers;

use App\Audit\Trail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    //

    public function checkHeader($requestHeader) {
        if( $requestHeader !== 'Ratefy') {
            return false;
        }else {
            return true;
        }
    }


    public function chat(Request $request) {
        $marks = Str::ulid();
        Trail::post(
            url: $request->url(), 
            ip: $request->ip(),  
            mark: $marks, 
            method: $request->method(), 
            action: __FUNCTION__, 
            post: $request->collect() ?? null, 
            uuid: @auth()->user()->uuid ?? null);

        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            $request->validate([
                'message'   => ['required', 'string'],
                'sender'    => ['required', 'string'],
                'receiver'  => ['required', 'string'],
                'session'   => ['required', 'string'],
                'assets'    => ['required', 'string']
            ]);
    
            $payload = [
                'message'   => $request->message,
                'sender'    => $request->sender,
                'receiver'  => $request->receiver,
                'session'   => $request->session,
                'assets'    => $request->time
            ];
           

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage', $payload)->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $payload ?? null);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }  
        
    }

    public function uploadPop(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage')->throw();
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 
    }

    public function approveAndReleasePayment(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage')->throw();
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 
    }

    public function confirmPop(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage')->throw();
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        } 
    }

    public function cancelTransaction(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage')->throw();
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }

        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }   
    }

    public function OpenTransactionTicket(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage')->throw();
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        }else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }  
    }

    public function emoji(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if($status === true){
            

            try {
                Http::post('https://chat.ratefy.co/api/sendMessage')->throw();
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
