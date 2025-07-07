<?php 

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\WalletFacades\HasEscrowAccount;
use App\AdminFacades\HasObjectConverter;

class CreateEscrowAccountService 
{
    use HasObjectConverter, HasEscrowAccount;
    protected $uuid;

    public function __construct($uuid) 
    {
        $this->uuid = $uuid;
        Log::info("Escrow Account UUID: {$this->uuid}");
    }


    public function handleProcess() 
    {
        try {

            $this->setEscrow($this->uuid)->createEscrowAccount()->addEscrowNuban()->throwEscrowStatus();
        } catch (\Throwable $e) {
            Log::error("Account creation failed for UUID: {$this->uuid} - Error: {$e->getMessage()}");
            
        }
    }
}