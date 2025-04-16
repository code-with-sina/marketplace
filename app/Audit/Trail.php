<?php

namespace App\Audit;

use App\Jobs\TrailLog;
use App\Jobs\TrailPost;
use App\Jobs\TrailRetrieve;


class Trail {

    public static function post($url, $mark = null, $ip = null, $method = null, $action = null, $post = null, $uuid = null) {

        TrailPost::dispatch(apiUrl: $url, mark: $mark, ip: $ip, method: $method, action: $action, data: $post, uuid: $uuid);
    }

    public static function retrieve($mark = null, $retrieveData = null) {
        TrailRetrieve::dispatch(mark: $mark, retrieveData: $retrieveData);
    }

    public static function log($user = null,  $errorTrace = null, $traceId, $action) {
        TrailLog::dispatch(user: $user, error: $errorTrace, trace: $traceId, action: $action);
    }
    
}