<?php 

namespace App\Services;

use App\Models\User;
use App\Models\AdminAuth;
use Illuminate\Support\Sleep;
use App\Models\CustomerStatus;
use App\Mail\WalletCreationState;
use App\Services\SubAccountService;
use App\Services\KycCheckerService;
use Illuminate\Support\Facades\Mail;
use App\Services\AddVirtualNubanService;



class WalletStatusObserverAndNotifier 
{
    
    protected $user;
    protected $email;
    protected $adminEmail;
    protected $customer;
    protected $customerStatusCreation = false;
    protected $customerCreation = false;
    protected $customerKyc = false;
    protected $personalAccountCreated = false;
    protected $escrowAccountCreated = false;
    protected $escrowNubanAccountCreated = false;
    protected $personalNubanAccountCreated = false;

    protected $customerStatus;
    protected $customerRecord;
    protected $personalAccount;
    protected $escrowAccount;
    
    public $maxRetry = 0;

    const STATE_SUBACCOUNTS = 'subaccounts';
    const STATE_KYC = 'kycChecks';
    const STATE_VIRTUAL_NUBAN = 'virtualnuban';

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function OngoingCurrentState($message) 
    {
        $this->email = $this->user->email;   
        $this->adminEmail = $this->fetchAdmin();

        $content = [
            "message"   => $message,
            'isAdmin'   => $this->adminEmail !== null ? true: false,
            "name"      => $this->user->firstname." ".$this->user->lastname,
            "time"      => now()
        ];


        foreach ([$this->email, $this->adminEmail?->email] as $recipient) {
            if ($recipient) {
                $this->notifyParties(email: $recipient, content: $content);
            }
        }
            
    }

    public function notifyParties($email, $content)
    {
        Mail::to($email)->send(new WalletCreationState($content));
    }

    public function fetchAdmin() {
        return  AdminAuth::where('role', 'admin')->latest()->first() ?? null;
    }


    
    public function makeObservation() 
    {

        if ($this->maxRetry >= 3) {
            return $this->strictlyFailure();
        }

        if ($this->checkCustomerOnboarded()) {
            if ($this->checkCustomerUpdated()) {
                if ($this->checkPersonalAccountCreated() && $this->checkEscrowAccountCreated()) {
                    if ($this->checkPersonalNubanUpdated() && $this->checkEscrowNubanUpdated()) {
                        return $this->sendCongratsNotification();
                    }
                    return $this->manageRecursiveProcess(self::STATE_VIRTUAL_NUBAN); 
                }
                return $this->manageRecursiveProcess(self::STATE_SUBACCOUNTS);
            }
            return $this->manageRecursiveProcess(self::STATE_KYC);
        }

        return $this->manageRecursiveProcess(self::STATE_KYC);
        
    }

    public function checkCustomerOnboarded()
    {
        $this->customerStatus = CustomerStatus::where('user_id', $this->user->id)->first() ?? null;
        if($this->customerStatus !== null) {
             $this->customerStatusCreation = true;
        }else {
             $this->customerStatusCreation = false;
        }

        return $this->customerStatusCreation;
    }

    public function checkCustomerUpdated() 
    {
        $this->customerRecord = $this->customerStatus->customer()->first() ?? null;

        if($this->customerRecord !== null){
            $this->customerCreation = true;
        }else {
            $this->customerCreation = false;
        }

        return $this->customerCreation;
    }


    public function checkPersonalAccountCreated() 
    {
        $this->personalAccount = $this->customerRecord->personalaccount()->first() ?? null;

        if($this->personalAccount !== null){
            $this->personalAccountCreated = true;
        }else {
            $this->personalAccountCreated = false;
        }

        return $this->personalAccountCreated;

    }

    public function checkEscrowAccountCreated()
    {
        $this->escrowAccount = $this->customerRecord->escrowaccount()->first() ?? null;

        if($this->escrowAccount !== null){
            $this->escrowAccountCreated = true;
        }else {
            $this->escrowAccountCreated = false;
        }

        return $this->escrowAccountCreated;
    }


    public function checkPersonalNubanUpdated() 
    {
        $this->customer = $this->personalAccount->virtualnuban()->first() ?? null;

        if($this->customer !== null){
            $this->personalNubanAccountCreated = true;
        }else {
            $this->personalNubanAccountCreated = false;
        }

        return $this->personalNubanAccountCreated;
    }


    public function checkEscrowNubanUpdated() 
    {
        $this->customer = $this->escrowAccount->virtualnuban()->first() ?? null;

        if($this->customer !== null){
            $this->escrowNubanAccountCreated = true;
        }else {
            $this->escrowNubanAccountCreated = false;
        }

        return $this->escrowNubanAccountCreated;
    }


    public function rerunCustomerOnboarding($state)
    {

        if($state === self::STATE_SUBACCOUNTS) {
            $this->subAccountGeneration();
        }
        
        if($state === self::STATE_KYC) {
            $this->kycChecking();
        }

        if($state === self::STATE_VIRTUAL_NUBAN) {
            $this->virtualNubanGeneration();
        } 
    }

    public function manageRecursiveProcess($state)
    {
        $this->maxRetry++;

        $this->rerunCustomerOnboarding(state: $state);
        Sleep::for(10)->seconds()->then(fn () =>  $this->makeObservation());
        
    }

    public function sendCongratsNofication() 
    {
        $message = <<<EOD
            Hello There, 

            Congratulations, your account has been fully created.
            You are now able to perform many actions on our platform. 

            Best regards.
        EOD;

        $content = [
            "message"   => $message,
            "name"      => $this->user->firstname." ".$this->user->lastname,
            "time"      => now()
        ];

        Mail::to($this->user->email)->send(new WalletCreationState($content));
    }


    public function subAccountGeneration() 
    {
        return app(SubAccountService::class)->validateUser($this->user->uuid)
                ->validateUserKyc()
                ->processEscrow()
                ->processPersonal()
                ->createSubAccount()
                ->throwStatus();
    }

    public function kycChecking() 
    {
        return app(KycCheckerService::class)
                ->getUuid($this->user->uuid)
                ->checkStatus()
                ->OnboardCustomerAgain();
    }

    public function virtualNubanGeneration() 
    {
        return app(AddVirtualNubanService::class)
                ->getVirtualNuban(id: $this->user->id)
                ->createVirtualNuban()
                ->show();
    }

    public function StrictlyFailure() 
    {
        $message = <<<EOD
            Hello Admin, 

            {$this->user->email}
            
            This account is experiencing unknown issues. 
            Please visit GetAnchor or escalate to the technical team for resolution. 

            Thanks.
        EOD;

        $content = [
            "message" => $message,
            'isAdmin' => true,
            "name"    => $this->user->firstname." ".$this->user->lastname,
            "time"    => now()
        ];

        if ($this->adminEmail) {
            Mail::to($this->adminEmail->email)->send(new WalletCreationState($content));
        }

        logger()->info("Retry attempt: {$this->maxRetry} for user {$this->user->id}");
        
    }

}