<?php 

namespace App\Services;


use App\Repositories\UserRepository;
use App\Services\SubAccountService;

class SubaccountCreationService {

    public function __construct(private UserRepository $userRepository, private SubAccountService $subAccountService) {}

    public function handle(string $email): void 
    {
        $user = $this->userRepository->findByEmail($email);

        if(!$user) {
            throw new DomainException('User not found');
        }

        $customer = $user->customerstatus?->customer;

        if(! $customer) {
            throw new DomainException('User has no customer record');
        }

        if(! $customer->personalaccount || ! $customer->escrowaccount) {
            $this->process($user);
            
        }
       
    }

    public function process($user) {
        $this->subAccountService->validateUser($user->uuid)
            ->validateUserKyc()
            ->processEscrow()
            ->processPersonal()
            ->createSubAccount()
            ->throwStatus();
    }

 
}