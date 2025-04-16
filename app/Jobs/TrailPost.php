<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Trail as AuditTrail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrailPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $apiUrl; 
    public $mark; 
    public $ip;
    public $method; 
    public $action; 
    public $data;
    public $uuid;

    /**
     * Create a new job instance.
     */
    public function __construct($apiUrl = null, $mark = null, $ip = null, $method = null, $action = null, $data = null, $uuid = null)
    {
        $this->apiUrl   = $apiUrl;
        $this->mark     = $mark;
        $this->ip       = $ip;
        $this->method   = $method;
        $this->action   = $action;
        $this->data     = $data;
        $this->uuid     = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        if($this->uuid !== null) {
            $track = User::where('uuid', $this->uuid)->first();
            $track->trail()->create([
                'api_url'       => $this->apiUrl,
                'uniquestamps'  => $this->mark,
                'ip'            => $this->ip,
                'method'        => $this->method,
                'status'        => 'went',
                'action'        => $this->action,
                'post_data'     => $this->data ?? null
            ]);
        }else {
            AuditTrail::create([
                'api_url'       => $this->apiUrl,
                'uniquestamps'  => $this->mark,
                'ip'            => $this->ip,
                'method'        => $this->method,
                'status'        => 'went',
                'action'        => $this->action,
                'post_data'     => $this->data ?? null
            ]);
        }
        
    }
}
