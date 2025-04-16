<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\WalletFacades\HasPersonalAccount;
use Illuminate\Support\Facades\Log;
use App\AdminFacades\HasObjectConverter;

class ProcessPersonalAccountJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasObjectConverter, HasPersonalAccount;
    protected $uuid;
    public $tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
        Log::info("Account UUID: {$this->uuid}");
    }

    public function uniqueId(): string
    {
        return $this->uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $this->setPersonal($this->uuid)->createPersonalAccount()->addPersonalNuban()->throwPersonalStatus();
            Log::info("Account creation successful for UUID: {$this->uuid}");
        } catch (\Throwable $e) {
            Log::error("Account creation failed for UUID: {$this->uuid} - Error: {$e->getMessage()}");
            $this->fail($e); // Mark the job as failed in the queue system
        }
    }
}
