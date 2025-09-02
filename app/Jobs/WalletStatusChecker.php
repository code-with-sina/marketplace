<?php

namespace App\Jobs;


use Exception;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Services\OnboardingLogService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\OnboardCustomerTestService;
use Illuminate\Contracts\Queue\ShouldBeUnique;



class WalletStatusChecker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public string $image;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, string $image, User $user)
    {
        $this->payload = $payload;
        $this->image = $image;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $statusResource = app(OnboardCustomerTestService::class, ['user' => $this->user])
            ->acquireUserDataAndValidate(edit: false)
            ->createMember(collections: $this->payload, selfieimage: $this->image)
            ->validateLevelOneKyc()
            ->monitorKycStatus()
            ->throwStatus();

            $status = $statusResource->status ?? null;
            $logStatus = $status === 200 ? "success" : "failed";


            OnboardingLogService::log(
                $this->user,
                $logStatus,
                "OnboardCustomerTestService",
                (string)($statusResource->message ?? 'No message'),
                method_exists($statusResource, 'toArray') ? $statusResource->toArray() : (array)$statusResource
            );
        }catch(Exception $e) {
            OnboardingLogService::log(
                $this->user, 
                "failed",
                "OnboardCustomerTestService",
                $e->getMessage(),
                (array)$statusResource
            );
        }
        

        
    }



}
