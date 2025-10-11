<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\WalletStatusObserverAndNotifier;
use App\Services\OnboardCustomerTestService;

class WalletStatusNotifier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $status;
    protected $payload;
    protected $selfieImage;
    /**
     * Create a new job instance.
     */
    public function __construct(protected WalletStatusObserverAndNotifier $service, protected OnboardCustomerTestService $onboarding, $payload, $image,  $user, $status)
    {
        $this->user = $user;
        $this->status = $status;
        $this->payload = $payload;
        $this->selfieImage = $image;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // $statusResource = app(OnboardCustomerTestService::class, ['user' => auth()->user()])
        //     ->acquireUserDataAndValidate(edit: false)
        //     ->createMember(collections: $payload, selfieimage: $data->image)
        //     ->validateLevelOneKyc()
        //     ->monitorKycStatus()
        //     ->throwStatus();
        $this->onboarding->acquireUserDataAndValidate(edit: $this->status)
            ->createMember(collections: $this->payload, selfieimage: $this->selfieImage)
            ->validateLevelOneKyc()
            ->monitorKycStatus()
            ->throwStatus();
        $this->service->OngoingCurrentState($this->user);
    }
}
