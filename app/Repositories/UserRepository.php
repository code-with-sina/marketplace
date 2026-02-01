<?php 

namespace App\Repositories;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\PersonalAccount;
use App\Models\EscrowAccount;
use App\Models\VirtualNuban;


use Illuminate\Support\Collection;

class UserRepository {
    public function findByEmail(string $email): ?User
    {
        return User::with([
            'customerstatus',
            'customerstatus.customer',
            'customerstatus.customer.personalaccount',
            'customerstatus.customer.escrowaccount',
            'customerstatus.customer.personalaccount.virtualnuban',
            'customerstatus.customer.escrowaccount.virtualnuban'
        ])
        ->where('email', $email)->first();
    }

    
}