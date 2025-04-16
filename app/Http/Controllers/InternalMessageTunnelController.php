<?php

namespace App\Http\Controllers;

use App\Audit\Trail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\WalletNotificationJob;
use App\Mail\MicroserviceMessage;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class InternalMessageTunnelController extends Controller
{
    public function walletNotifications(Request $request) {
        
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

        $request->validate([
            'message'  => ['required', 'string']
        ]);

        WalletNotificationJob::dispatch($request->message, $request->uuid, $request->notificationType);
    }


    public function fetchNotification(Request $request) {
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
        
        $user = User::find(auth()->user()->id);
        return response()->json(['nofitication' => $user->notifications->where('read_at', null)->get()]);

        
        // $kotes =  User::where('uuid', '788f38c8-c15a-42fe-8590-1dd663664b11')->first();
        // $user = User::find($kotes->id);
        // $notification = Notification::where('uuid', $request->uuid)->get();
    }

    public function readNotification(Request $request, $uuid) {
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

        
        Notification::where('uuid', $uuid)->update([
            'read_at'   => now()
        ]);
    }

    public function promptNotification(Request $request) {
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
        
        $user = User::where('uuid', $request->uuid)->first();
        Mail::to($user)->send(new MicroserviceMessage($request->subject, $request->title, $request->body, $request->from)); 
    }


    
}
