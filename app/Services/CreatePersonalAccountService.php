<?php 

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\AdminFacades\HasObjectConverter;
use App\WalletFacades\HasPersonalAccount;



class CreatePersonalAccountService 
{
    use HasObjectConverter, HasPersonalAccount;
    protected $uuid;


    public function __construct($uuid) 
    {
        $this->uuid = $uuid;
        Log::info("Personal Account UUID: {$this->uuid}");
    }


    public function handleProcess() 
    {
        try {

            $this->setPersonal($this->uuid)->createPersonalAccount()->addPersonalNuban()->throwPersonalStatus();
        } catch (\Throwable $e) {
            Log::error("Account creation failed for UUID: {$this->uuid} - Error: {$e->getMessage()}");
            
        }
    }
}