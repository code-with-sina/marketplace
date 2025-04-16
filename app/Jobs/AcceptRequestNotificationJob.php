<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AcceptRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $owner;
    public $id;
    /**
     * Create a new job instance.
     */
    public function __construct($owner, $id)
    {
        $this->owner = $owner;
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        TradeRequest::where('id', $this->id)->where('recipient', $this->owner)->orWhere('id', $this->id)->where('owner', $this->owner)->update(['status' => 'accepted']);
        $data = TradeRequest::where('id', $this->id)->where('recipient', $this->owner)->orWhere('id', $this->id)->where('owner', $this->owner)->first();
        $itemFor = '';
        $recipient ='';
        $owner ='';

        $acceptanceId =  Str::uuid();
        $sessionId = Str::uuid();
        $paymentId = Str::uuid();

        if((string)$data->item_for == "sell") {
            $getthod = $data->item_id;       
            $itemFor = $this->buyNode($getthod);
            $recipient = 'seller';
            $owner = 'buyer';
        }else {
            $getthod = $data->item_id;
            $itemFor = $this->sellNode($getthod);
            $recipient = 'buyer';
            $owner = 'seller';
        }


        PToP::create([
            'acceptance_id'     =>  $acceptanceId,
            'session_id'        =>  $sessionId,
            'session_status'    =>  'open',
            'item_for'          =>  $data->item_for,
            'item_id'           =>  $data->item_id,
            'item_name'         =>  $data->wallet_name,
            'amount'            =>  $data->amount,
            'duration'          =>  $itemFor->data->duration,
            'duration_status'   =>  'started',
            'payment_id'        =>  $paymentId, 
            'payment_status'    =>  'void',
            'proof_of_payment'  =>  'void',
            'reportage'         =>  'good',
            'recipient'         =>  $recipient,
            'owner'             =>  $owner,
            'recipient_id'      =>  $data->recipient,
            'owner_id'          =>  $data->owner,
            'start_time'        =>  Carbon::now(),
            'end_time'          =>  Carbon::now()->addMinutes((int)$itemFor->data->duration)
        ]);


        Http::post('https://p2p.ratefy.co/api/accepted-trade-request', [
            'item'          =>  $data->id,
            'owner'         =>  $this->owner,
            'amount'        =>  $data->amount,
            'item_for'      =>  $data->item_for,
            'item_id'       =>  $data->item_id,
            'item_name'     =>  $data->wallet_name,
            'recipient'     =>  $data->recipient,
            'acceptance_id' =>  $acceptanceId,
            'session_id'    =>  $sessionId
        ]);

    }

    
    public function sellNode($itemId) {
        $sellerNode = Http::post('https://offerbased.ratefy.co/api/fetch-single-offer-seller-detail', [
            'id'      => $itemId,
        ]);

        return $sellerNode->object();
    }


    public function buyNode($itemId) {
        $buyerNode = Http::post('https://offerbased.ratefy.co/api/fetch-single-offer-buyer-detail', [
            'id'      => $itemId,
        ]);
        
        return $buyerNode->object();
    }
}
