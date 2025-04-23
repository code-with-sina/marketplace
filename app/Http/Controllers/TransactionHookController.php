<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\PostBuyRequestService;

class TransactionHookController extends Controller
{

    public function initPeerPaymentDebit($uuid, $reference)
    {
        $user = User::where('uuid', $uuid)->first();
        $user->transactionevent()->create([
            'type' => 'Disbursement',
            'reference' => $reference,
            'status' => 'initiated',
        ]);
    }

    public function initFeeDebit($uuid, $reference)
    {
        $user = User::where('uuid', $uuid)->first();
        $user->transactionevent()->create([
            'type' => 'PeerPaymentFee',
            'reference' => $reference,
            'status' => 'initiated',
        ]);
    }

    


    public function initBuyerApprovalDebit($uuid, $reference)
    {
        $user = User::where('uuid', $uuid)->first();
        $user->transactionevent()->create([
            'type' => 'BuyerApproval',
            'reference' => $reference,
            'status' => 'initiated',
        ]);
    }

   

    public function initBuyerRequestDebit($uuid, $reference) 
    {
        $user = User::where('uuid', $uuid)->first();
        $user->transactionevent()->create([
            'type' => 'BuyerRequest',
            'reference' => $reference,
            'status' => 'initiated',
        ]);
        
    }
  
}
