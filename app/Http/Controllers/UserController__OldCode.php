<?php

namespace App\Http\Controllers;


use Exception;
use App\Models\User;
use App\Audit\Trail;
use App\Models\ErrorTrace;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules;
use App\Models\StaffNotification;
use App\Models\Trail as AuditTrail;
use App\StaffNotifier\KycNotify;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{

    public function checkHeader($requestHeader)
    {
        if ($requestHeader !== 'Ratefy') {
            return false;
        } else {
            return true;
        }
    }


    public function dashboard(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));

        if ($status === true) {
            try{
                $detail = Http::post('https://userbased.ratefy.co/api/detail', [
                    'uuid'  => auth()->user()->uuid
                ])->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $detail ?? null);
                return response(['user' => $detail->object()], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
            
        } else {
            Log::error('Error tracking signUp: ' . $request->header('User-Agents'), [
                '$status' => $status

            ]);

            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad headers', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad headers' ?? null);
            return response(['message' => 'bad headers'], 400);
        }
    }

    public function updateMultipleDetail(Request $request)
    {
        

        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);

        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                'email'         => ['required', 'string'],
                'firstname'     => ['required', 'string'],
                'lastname'      => ['required', 'string'],
                'phonenumber'   => ['required', 'string'],
                'ip'            => ['required', 'string'],
                'device'        => ['required', 'string'],
            ]);

            try{
                $detail = Http::post('https://userbased.ratefy.co/api/multiple_update_detail', [
                    'uuid'  => auth()->user()->uuid,
                    'email'         => $request->email,
                    'firstname'     => $request->firstname,
                    'lastname'      => $request->lastname,
                    'phonenumber'   => $request->mobile,
                    'ip'            => $request->ip,
                    'last_login'    => Carbon::now(),
                    'device'        => $request->device
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $detail->object() ?? null);
                return response(['user' => $detail], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }

            
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function singleUpdateDetail(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                "data"          =>  ['required', 'string'],
                "single_column" =>  ['required', 'string']
            ]);

            try {
                $detail = Http::post('https://userbased.ratefy.co/api/single_update_detail', [
                    'uuid'  => auth()->user()->uuid,
                    'data'              => $request->data,
                    'single_column'     => $request->single_column
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $detail->object() ?? null);
                return response(['user' => $detail->object()], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }

            
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function singleUpdateAuthorization(Request $request)
    {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            $request->validate([
                "data"          =>  ['required', 'string'],
                "single_column" =>  ['required', 'string']
            ]);

            try {
                $detail = Http::post('https://userbased.ratefy.co/api/single_update_authorization', [
                    'uuid'  => auth()->user()->uuid,
                    'data'              => $request->data,
                    'single_column'     => $request->single_column
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $detail->object() ?? null);
                return response(['user' => $detail->object()], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }

            
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }


    public function getUserDetail(Request $request, $uuid) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $status = $this->checkHeader($request->header('User-Agents'));
        if ($status === true) {
            try {
                $detail = Http::post('https://userbased.ratefy.co/api/detail', [
                    'uuid'  => $uuid
                ])->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $detail->object() ?? null);
                return response(['user' => $detail->object()], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
            
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  $data->object() ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function getProfileOption() {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        

        try {
            $data = Http::post('https://profilebased.ratefy.co/api/get-work-options', [
                'uuid' => auth()->user()->uuid
                // 'uuid'  => '928be980-1936-4e4a-ab6d-66affbaa6fc3'
            ])->throw();
        }catch(Exception $e){
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
        }
        Trail::retrieve(mark: $marks, retrieveData:  $data->object() ?? null);
        return response()->json(['data' => $data->object()]);
    }

    public function getStatus(Request $request) {
        $marks = Str::ulid();
        Trail::post(url: $request->url(), ip: $request->ip(),  mark: $marks, method: $request->method(), action: __FUNCTION__, post: $request->collect() ?? null, uuid: @auth()->user()->uuid ?? null);
        $uuid = auth()->user()->uuid;

        try {
            $status = [
                'profile'   => 0,
                'email'     => 0,
                'kyc'       => 0,
                'work-experience'   => 0
            ];
    
            $status['profile'] =  Http::post('https://profilebased.ratefy.co/api/profile-verified', ['uuid' => $uuid])->throw()->json();
            $status['email'] =  Http::post('https://userbased.ratefy.co/api/email-verified',['uuid' => $uuid])->throw()->json();
            $status['kyc'] =  Http::post('https://userbased.ratefy.co/api/kyc-verified', ['uuid' => $uuid])->throw()->json();
            $status['work-experience'] =  Http::post('https://userbased.ratefy.co/api/work-verified', ['uuid' => $uuid])->throw()->json();
    
            Trail::retrieve(mark: $marks, retrieveData: $status ?? null);
    
            return response()->json($status, 200);
        }catch(Exception $e){
            Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            return response()->json($e->getMessage(), 400);
        }

    }

    public function listUsers() {
        $data = User::orderBy('id', 'DESC')->paginate(10);
        return response()->json($data);
    }

   
    
    

}
