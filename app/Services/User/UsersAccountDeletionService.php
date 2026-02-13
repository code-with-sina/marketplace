<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\AnchorAccountDeletionService;


class UsersAccountDeletionService 
{
    protected AnchorAccountDeletionService $deleteService;

    public function __construct(AnchorAccountDeletionService $deleteService) {
        $this->deleteService = $deleteService;
    }

    public function execute($email) : array
    {
        $user = User::where('email', $email)->first();
        if(!$user) {
            return [
                'status' => false,
                'message' => 'User not found'
            ];
        }

        if(!$user->kycdetail()->exists()) {
             return [
                'status' => false,
                'message' => 'User has no KYC record'
            ];
        }

        $customerStatus = $user->customerstatus;

        if(!$customerStatus) {
            return [
                'status' => false,
                'message' => 'User account not provisioned'
            ];
        }


        $response = $this->deleteService->deleteCustomer($customerStatus->customerId);

        if (!$response->successful) {
            return [
                'status' => false,
                'message' => 'Remote deletion failed',
                'error' => $response->data
            ];
        }


        DB::transaction(function () use ($customerStatus) {
            $customerStatus->delete();
        });

        return [
            'status' => true,
            'message' => 'Account deleted successfully'
        ];
    }
}   










