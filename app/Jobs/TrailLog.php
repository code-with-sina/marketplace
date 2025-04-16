<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\ErrorTrace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrailLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $user;
    public $error;
    public $trace;
    public $action;
    /**
     * Create a new job instance.
     */
    public function __construct($user = null, $error  = null, $trace  = null, $action = null)
    {
        $this->user     = $user;
        $this->error    = $error;
        $this->trace    = $trace;
        $this->action   = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->user !== null) {
            $user = User::where('uuid', $this->user)->first();
            $user->errortrace()->create([
                'action'    => $this->action,
                'trace_id'  => $this->trace,
                'content'   => $this->error
            ]);
        }else {
            ErrorTrace::create([
                'action'    => $this->action,
                'trace_id'  => $this->trace,
                'content'   => $this->error
            ]);
        }
    }
}
