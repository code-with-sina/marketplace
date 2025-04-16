<?php

namespace App\Http\Controllers;


use App\Audit\Trail;
use Illuminate\Support\Str;
use App\Enums\Gender;
use App\Jobs\ProfileJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class UserProfileController_OldCode extends Controller
{

    public function checkHeader($requestHeader)
    {
        if ($requestHeader !== 'Ratefy') {
            return false;
        } else {
            return true;
        }
    }

    public function createProfile(Request $request)
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


        if ($status === true) {
            $enum = new Enum(Gender::class);
            $request->validate([
                'sex'           => ['required', 'string', Rule::enum(Gender::class)],
                'dob'           => ['required', 'string'],
                'address'       => ['required', 'string'],
                'city'          => ['required', 'string'],
                'state'         => ['required', 'string'],
                'country'       => ['required', 'string'],
                'zip_code'      => ['required', 'string'],
            ]);

            $payload = [
                'sex'           => $request->sex,
                'dob'           => $request->dob,
                'address'       => $request->address,
                'city'          => $request->city,
                'state'         => trim($request->state, 'State'),
                'country'       => $request->country,
                'zip_code'      => $request->zip_code,
                'uuid'          => auth()->user()->uuid
            ];

            
            try {
                ProfileJob::dispatch($payload);
                Trail::retrieve(mark: $marks, retrieveData:  'processing' ?? null);
                return response()->json(['message' => 'processing'], 200);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response()->json(['message' => 'bad header'], 400);
        }
    }


    public function fetchProfile(Request $request)
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

        if ($status === true) {
            try {
                $profile = Http::post('https://profilebased.ratefy.co/api/get-full-profile', ['uuid' => auth()->user()->uuid])->throw();
                Trail::retrieve(mark: $marks, retrieveData:  $profile->body() ?? null);
                return response()->json(['profile' => $profile->body(), 'profile-test' => auth()->user()->uuid]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function getFullProfile(Request $request)
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

        if ($status === true) {
            try {
                $getFull = Http::post('https://profilebased.ratefy.co/api/get-full-profile', [
                    'uuid'  => auth()->user()->uuid
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $getFull->object() ?? null);
                return response()->json([
                    'status' => $getFull->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function createFreelanceProfile(Request $request)
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

        if ($status === true) {
            $request->validate([
                "options"       => ['required', 'string'],
                "service_offer" => ['required', 'string'],
                "work_history"  => ['required', 'string'],
                "purpose"       => ['required', 'string'],
                "experience"    => ['required', 'string']
            ]);

            
            try {
                $createFreelanceProfile = Http::post('https://profilebased.ratefy.co/api/create-freelance', [
                    'uuid'          => auth()->user()->uuid,
                    'options'       => $request->options,
                    'service_offer' => $request->service_offer,
                    'work_history'  => $request->work_history,
                    'portfolio'     => $request->portfolio,
                    'purpose'       => $request->purpose,
                    'experience'    => $request->experience,
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $createFreelanceProfile->body() ?? null);
                return response()->json([
                    'freelance' => $createFreelanceProfile->body()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function createDiasporaProfile(Request $request)
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

        if ($status === true) {
            $request->validate([
                "option"       => ['required', 'string'],
                "purpose"       => ['required', 'string'],
                "experience"    => ['required', 'string']
            ]);

            try {
                $createDiasporaProfile = Http::post('https://profilebased.ratefy.co/api/create-shoppermigrant', [
                    'uuid'          => auth()->user()->uuid,
                    'option'       => $request->option,
                    'purpose'       => $request->purpose,
                    'experience'    => $request->experience,
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $createDiasporaProfile->body() ?? null);
    
                return response()->json([
                    'diaspora' => $createDiasporaProfile->body()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function createKyc(Request $request)
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

        if ($status === true) {
            $request->validate([
                "uuid"              => ['required', 'string'],
                "bvn"               => ['required', 'string'],
                "document_id"       => ['required', 'string'],
                "document_type"     => ['required', 'string'],

            ]);

            try {
                $createKyc = Http::post('https://profilebased.ratefy.co/api/create-kyc', [
                    'uuid'          => auth()->user()->uuid,
                    'bvn'           => $request->options,
                    'document_id'   => $request->options,
                    'document_type' => $request->options
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $createKyc->body() ?? null);
                return response()->json([
                    'kyc' => $createKyc->body()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }



    public function singleUpdateProfile(Request $request)
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

        if ($status === true) {
            $request->validate([
                "data"          =>  ['required', 'string'],
                "single_column" =>  ['required', 'string']
            ]);

            try {
                $singleUpdate = Http::post('https://profilebased.ratefy.co/api/single-update-profile', [
                    'uuid'              => auth()->user()->uuid,
                    'data'              => $request->data,
                    'single_column'     => $request->single_column
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $singleUpdate->object() ?? null);
                return response()->json([
                    'status' => $singleUpdate->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function multipleUpdateProfile(Request $request)
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

        if ($status === true) {
            $enum = new Enum(Gender::class);
            $request->validate([
                'sex'           => ['required', 'string', Rule::enum(Gender::class)],
                'dob'           => ['required', 'string'],
                'address'       => ['required', 'string'],
                'city'          => ['required', 'string'],
                'state'         => ['required', 'string'],
                'country'       => ['required', 'string'],
                'zip_code'      => ['required', 'string'],
            ]);

            $payload = [
                'sex'           => $request->sex,
                'dob'           => $request->dob,
                'address'       => $request->address,
                'city'          => $request->city,
                'state'         => $request->state,
                'country'       => $request->country,
                'zip_code'      => $request->zip_code,
                'uuid'          => auth()->user()->uuid
            ];

            try {
                $profile = Http::post('https://profilebased.ratefy.co/api/multiple-update-profile', [
                    "uuid"      =>  $this->payload->uuid,
                    "sex"       =>  $this->payload->sex,
                    "dob"       =>  $this->payload->dob,
                    "address"   =>  $this->payload->address,
                    "city"      =>  $this->payload->city,
                    "state"     =>  $this->payload->state,
                    "country"   =>  $this->payload->country,
                    "zip_code"  =>  $this->payload->zip_code
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $profile->object() ?? null);
                return response()->json([
                    'status' => $profile->object(),
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            return response(['message' => 'bad header'], 400);
        }
    }

    public function singleUpdateFreelance(Request $request)
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

        if ($status === true) {
            $request->validate([
                "data"          => ['required', 'string'],
                "single_column" =>  ['required', 'string']
            ]);

            try {
                $reelance = Http::post('https://profilebased.ratefy.co/api/single-update-freelance', [
                    'uuid'              => auth()->user()->uuid,
                    'data'              => $request->data,
                    'single_column'     => $request->single_column
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $reelance->object() ?? null);
                return response()->json([
                    'status' => $reelance->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function singleUpdateShopperMigrant(Request $request)
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

        if ($status === true) {
            $request->validate([
                "data"          => ['required', 'string'],
                "single_column" =>  ['required', 'string']
            ]);

            try {
                $perMigrant = Http::post('https://profilebased.ratefy.co/single-update-shoppermigrant', [
                    'uuid'              => auth()->user()->uuid,
                    'data'              => $request->data,
                    'single_column'     => $request->single_column
                ])->throw();
    
                Trail::retrieve(mark: $marks, retrieveData:  $perMigrant->object() ?? null);
                return response()->json([
                    'status' => $perMigrant->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function singleUpdateKYC(Request $request)
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

        if ($status === true) {
            $request->validate([
                "data"          => ['required', 'string'],
                "single_column" =>  ['required', 'string']
            ]);

            try {
                $teKYC = Http::post('https://profilebased.ratefy.co/api/single-update-kyc', [
                    'uuid'              => auth()->user()->uuid,
                    'data'              => $request->data,
                    'single_column'     => $request->single_column
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $teKYC->object() ?? null);
                return response()->json([
                    'status' => $teKYC->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function multipleUpdateFreelance(Request $request)
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

        if ($status === true) {
            $request->validate([
                "options"       => ['required', 'string'],
                "service_offer" => ['required', 'string'],
                "work_history"  => ['required', 'string'],
                "purpose"       => ['required', 'string'],
                "experience"    => ['required', 'string']
            ]);

            try {
                $dateFreelance = Http::post('https://profilebased.ratefy.co/api/multiple-update-freelance', [
                    'uuid'  => auth()->user()->uuid,
                    'multi_column'  => $wrapper
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $dateFreelance->object() ?? null);
                return response()->json([
                    'status' => $dateFreelance->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function multipleUpdateShopperMigrant(Request $request)
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

        if ($status === true) {
            $request->validate([
                "options"       => ['required', 'string'],
                "purpose"       => ['required', 'string'],
                "experience"    => ['required', 'string']
            ]);

            try {
                $hopperMigrant = Http::post('https://profilebased.ratefy.co/api/multiple-update-shoppermigrant', [
                    'uuid'          => auth()->user()->uuid,
                    'option'       =>   $request->option,
                    'purpose'       => $request->options,
                    'experience'    => $request->options,
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $hopperMigrant->object() ?? null);
                return response()->json([
                    'status' => $hopperMigrant->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }

    public function multipleUpdateKYC(Request $request)
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

        if ($status === true) {
            $request->validate([
                "uuid"              => ['required', 'string'],
                "bvn"               => ['required', 'string'],
                "document_id"       => ['required', 'string'],
                "document_type"     => ['required', 'string'],

            ]);

            try {
                $dateKYC = Http::post('https://profilebased.ratefy.co/api/multiple-update-kyc', [
                    'uuid'          => auth()->user()->uuid,
                    'bvn'           => $request->options,
                    'document_id'   => $request->options,
                    'document_type' => $request->options
                ])->throw();
    
    
                Trail::retrieve(mark: $marks, retrieveData:  $dateKYC->object() ?? null);
                return response()->json([
                    'status' => $dateKYC->object()
                ]);
            }catch(Exception $e){
                Trail::log(user: @auth()->user()->uuid, errorTrace: $e->getMessage(), traceId: $marks, action: __FUNCTION__ );
            }
        } else {
            Trail::log(user: @auth()->user()->uuid, errorTrace: 'bad header', traceId: $marks, action: __FUNCTION__ );
            Trail::retrieve(mark: $marks, retrieveData:  'bad header' ?? null);
            return response(['message' => 'bad header'], 400);
        }
    }
}
