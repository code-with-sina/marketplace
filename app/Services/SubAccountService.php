<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\CreateEscrowAccountService;
use App\Services\CreatePersonalAccountService;
use App\Jobs\ProcessEscrowAccountJob;
use App\Jobs\ProcessPersonalAccountJob;


class SubAccountService
{
    private $state;
    private $errorMessage;
    private $statefulError = false;
    protected User $user;

    public function validateUser($uuid)
    {
        $this->user = User::where('uuid', $uuid)->first();
        if (!$this->user) {
            $this->setTrueErrorState(status: 400, title: __("User not found"));
        }

        return $this;
    }

    public function validateUserKyc()
    {
        if ($this->statefulError) {
            return $this;
        }

        $authKyc = $this->user->authorization()->first();
        $customerKyc = $this->user->customerstatus()->first();

        if ($authKyc->kyc === 'approved' && $customerKyc->status === 'fully-verified') {
            return $this;
        } else {
            $this->setTrueErrorState(status: 400, title: __("User KYC is not approved"));
        }
        return $this;
    }

    public function processEscrow()
    {
        if ($this->statefulError) {
            return $this;
        }

        app(CreateEscrowAccountService::class, ['uuid' => $this->user->uuid])
            ->handleProcess();
        return $this;
    }

    public function processPersonal()
    {
        if ($this->statefulError) {
            return $this;
        }

        app(CreatePersonalAccountService::class, ['uuid' => $this->user->uuid])
            ->handleProcess();
        return $this;
    }

    public function createSubAccount()
    {
        if ($this->statefulError) {
            return $this;
        }

        $this->setSuccessState(status: 200, title: __("Your deposit accounts are on the way! We are completing some background tasks and will notify you once everything is ready."));
        return $this;
    }


    public function throwStatus()
    {
        return $this->statefulError ?  $this->errorMessage : $this->state;
    }

    public function setTrueErrorState(mixed $title, int $status = 400)
    {

        $this->statefulError = true;
        $this->errorMessage = (object) [
            'status' => $status,
            'title' => $title
        ];

        Log::info(['Sub Account Error State', $this->errorMessage = (object) [
            'status' => $status,
            'title' => $title
        ]]);

        return $this;
    }

    public function setSuccessState($status = 200, $title)
    {


        $this->state = (object) [
            'status' => $status,
            'title' => $title
        ];

        Log::info(['Sub Account Success State', $this->state = (object) [
            'status' => $status,
            'title' => $title
        ]]);
        return $this;
    }
}
