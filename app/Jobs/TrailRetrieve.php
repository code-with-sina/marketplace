<?php

namespace App\Jobs;

use App\Models\Trail as AuditTrail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrailRetrieve implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $retrieveData; 
    public $mark;
    /**
     * Create a new job instance.
     */
    public function __construct($mark  = null, $retrieveData = null)
    {
        $this->mark = $mark;
        $this->retrieveData = $retrieveData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AuditTrail::where('uniquestamps', $this->mark)->update([
            'return_value'  => $this->retrieveData ?? null,
            'status'    => 'returned'
        ]);
    }
}
