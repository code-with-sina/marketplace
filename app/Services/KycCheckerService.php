<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\OnboardCustomerService;

class KycCheckerService
{
    protected User $user;
    public $errorState = false;
    public $successState;
    public function getUuid($uuid)
    {

        $this->user = User::where('uuid', $uuid)->first();
        if (!$this->user) {
            $this->setErrorState(status: 400, title: __("User not found"));
            return $this;
        }

        return $this;
    }

    public function checkStatus()
    {

        if ($this->errorState) {
            return $this;
        }
        $kyc = $this->user->authorization()->whereIn('kyc', ['rejected', 'approved', 'error'])->exists();

        if ($kyc) {
            $this->deleteTempData();
            $this->setErrorState(status: 400, title: __("User KYC is not approved"));
        }

        return $this;
    }


    public function OnboardCustomerAgain()
    {

        if ($this->errorState) {
            return $this;
        }



        $tempKyc = $this->user->tempkyc()->first();
        $kycs = [
            "user_id" => $tempKyc->user_id,
            "selfieimage" => $tempKyc->selfie,
            "bvn" => $tempKyc->bvn,
            "dateOfBirth" => $tempKyc->dateOfBirth,
            "gender" => $tempKyc->gender,
            "idNumber" => $tempKyc->idNumber,
            "idType" => $tempKyc->idType,
            "expiryDate" => now(),
        ];


        $statusResource = app(OnboardCustomerService::class, ['user' => $this->user])
            ->acquireUserDataAndValidate(edit: true)
            ->createMember(collections: $kycs, selfieimage: $tempKyc->selfie)
            ->validateLevelOneKyc()
            ->throwStatus();

        $this->setSuccessState(status: $statusResource->status ?? 200, title: $statusResource);
    }


    public function deleteTempData()
    {
        $this->user->tempkyc()->delete();
    }

    public function setErrorState($status = 400, $title)
    {
        $this->errorState = true;
        Log::info([
            'status' => $status,
            'title' => $title
        ]);
    }

    public function setSuccessState($status = 200, $title)
    {
        $this->successState = true;
        Log::info([
            'status' => $status,
            'title' => $title
        ]);
    }
}
